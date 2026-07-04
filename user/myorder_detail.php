<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? 0);

/* ── FETCH ORDER (must belong to this user) ── */
$stmt = $dbh->prepare("
    SELECT * FROM tblorders
    WHERE order_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<div style="text-align:center;padding:80px;color:#555;font-family:sans-serif;">Order not found or access denied.</div>');
}

/* ── FETCH ORDER ITEMS ── */
$iStmt = $dbh->prepare("
    SELECT oi.*, p.image
    FROM tblorder_item oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$iStmt->execute([$order_id]);
$items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── STATUS HELPERS ── */
// 注意：数据库 enum 用 'delivered'，不是 'completed'
$STATUS_FLOW = ['processing', 'packed', 'shipped', 'delivered'];

function statusConfig(string $status): array
{
    return match (strtolower($status)) {
        'processing' => ['label' => 'Processing', 'color' => '#ffc107', 'bg' => 'rgba(255,193,7,0.12)', 'icon' => 'fa-clock'],
        'packed' => ['label' => 'Packed', 'color' => '#17a2b8', 'bg' => 'rgba(23,162,184,0.12)', 'icon' => 'fa-box'],
        'shipped' => ['label' => 'Shipped', 'color' => '#007bff', 'bg' => 'rgba(0,123,255,0.12)', 'icon' => 'fa-truck'],
        'delivered' => ['label' => 'Delivered', 'color' => '#28a745', 'bg' => 'rgba(40,167,69,0.12)', 'icon' => 'fa-check-circle'],
        'cancelled' => ['label' => 'Cancelled', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,0.12)', 'icon' => 'fa-times-circle'],
        default => ['label' => ucfirst($status), 'color' => '#aaa', 'bg' => 'rgba(170,170,170,0.1)', 'icon' => 'fa-circle'],
    };
}

function paymentStatusConfig(string $status): array
{
    return match (strtolower($status)) {
        'paid' => ['label' => 'Paid', 'color' => '#28a745'],
        'pending' => ['label' => 'Pending', 'color' => '#ffc107'],
        'failed' => ['label' => 'Failed', 'color' => '#dc3545'],
        default => ['label' => ucfirst($status), 'color' => '#aaa'],
    };
}


/* ── FETCH DELIVERY ADDRESS ── */
$addrStmt = $dbh->prepare("
    SELECT oa.*, s.state_name
    FROM tbl_order_address oa
    LEFT JOIN tblstate s ON s.state_id = oa.state_id
    WHERE oa.order_id = ?
");
$addrStmt->execute([$order_id]);
$deliveryAddr = $addrStmt->fetch(PDO::FETCH_ASSOC);

/* ── FETCH PC BUILD linked to this order ── */
// Find a build that has items matching this order's products
$buildData     = null;
$originalSubtotal = 0.00;
$buildDiscountAmt = 0.00;

// Compute original subtotal from DB prices
foreach ($items as $item) {
    $originalSubtotal += floatval($item['product_price']) * $item['quantity'];
}

// Build discount = original - total_amount (what was actually charged for items)
$buildDiscountAmt = max(0, round($originalSubtotal - floatval($order['total_amount']), 2));

// Fetch the PC build record for this user that is closest to this order date
// (match by user_id and created_at proximity — best we can do without a direct FK)
$bStmt = $dbh->prepare("
    SELECT b.build_id, b.assembly_service, b.assembly_fee,
           b.discount_pct, b.discount_amt, b.subtotal AS build_subtotal
    FROM tbl_pc_build b
    WHERE b.user_id = ?
      AND b.status  = 'ordered'
      AND ABS(TIMESTAMPDIFF(SECOND, b.created_at, ?)) <= 60
    ORDER BY ABS(TIMESTAMPDIFF(SECOND, b.created_at, ?)) ASC
    LIMIT 1
");
$bStmt->execute([$user_id, $order['created_at'], $order['created_at']]);
$buildData = $bStmt->fetch(PDO::FETCH_ASSOC);

// Assembly info
$assemblyService = true;  // default: assembled (free)
$assemblyFeeAmt  = 0.00;
if ($buildData) {
    $assemblyService = (bool)$buildData['assembly_service'];
    $assemblyFeeAmt  = floatval($buildData['assembly_fee']); // 0.00 or -25.00
}

$statusCfg = statusConfig($order['order_status']);
$paymentCfg = paymentStatusConfig($order['payment_status'] ?? 'pending');
$isCancelled = strtolower($order['order_status']) === 'cancelled';
$isDelivered = strtolower($order['order_status']) === 'delivered';  // ← 修正：用 'delivered'

/* ── FETCH EXISTING REVIEWS FOR THIS ORDER (for edit prefill) ── */
$existingReviews = [];
if ($isDelivered) {
    $erStmt = $dbh->prepare("
        SELECT product_id, rating, review_text
        FROM tblreviews
        WHERE user_id = ? AND order_id = ?
    ");
    $erStmt->execute([$user_id, $order_id]);
    foreach ($erStmt->fetchAll(PDO::FETCH_ASSOC) as $er) {
        $existingReviews[$er['product_id']] = $er;
    }
}

/* ── TRACKING: find current step ── */
$currentStep = $isCancelled ? -1 : array_search(strtolower($order['order_status']), $STATUS_FLOW);
if ($currentStep === false)
    $currentStep = 0;

/* ── BUILD DATA FOR INVOICE ── */
$invoiceData = json_encode([
    'order_number' => $order['order_number'],
    'created_at' => $order['created_at'],
    'payment_method' => $order['payment_method'],
    'order_status' => $order['order_status'],
    'total_amount' => $order['total_amount'],
    'shipping_fee' => $order['shipping_fee'],
    'service_fee' => $order['service_fee'],
    'grand_total' => $order['grand_total'],
    'items' => array_map(fn($i) => [
        'product_name' => $i['product_name'],
        'product_price' => $i['product_price'],
        'quantity' => $i['quantity'],
        'subtotal' => $i['subtotal'],
    ], $items),
]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($order['order_number']); ?> — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        /* ── PANEL ── */
        .panel {
            background: #121212;
            border: 1px solid #1e1e1e;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .panel-title {
            font-size: 0.72rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title i {
            color: #d4af37;
        }

        /* ── STATUS BADGE ── */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid;
        }

        /* ── TRACKING TIMELINE ── */
        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 10px 0 0;
            overflow: hidden;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 0;
            right: 0;
            height: 2px;
            background: #1e1e1e;
            z-index: 0;
        }

        .tl-progress {
            position: absolute;
            top: 22px;
            left: 0;
            height: 2px;
            background: linear-gradient(90deg, #d4af37, #c5a028);
            z-index: 1;
            transition: width 0.6s ease;
        }

        .tl-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .tl-dot {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0f0f;
            border: 2px solid #2a2a2a;
            font-size: 1rem;
            color: #333;
            transition: all 0.4s;
            margin-bottom: 10px;
        }

        .tl-dot.done {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
        }

        .tl-dot.current {
            background: #0f0f0f;
            border-color: #d4af37;
            color: #d4af37;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.15);
        }

        .tl-dot.cancel {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #dc3545;
        }

        .tl-label {
            font-size: 0.72rem;
            text-align: center;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.3;
        }

        .tl-label.done {
            color: #d4af37;
            font-weight: 600;
        }

        .tl-label.current {
            color: #d4af37;
            font-weight: 700;
        }

        .tl-date {
            font-size: 0.65rem;
            color: #333;
            margin-top: 3px;
            text-align: center;
        }

        /* ── ORDER ITEMS TABLE ── */
        .item-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid #1a1a1a;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #1e1e1e;
            flex-shrink: 0;
        }

        .item-info {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #eee;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-meta {
            font-size: 0.75rem;
            color: #555;
        }

        .item-price {
            text-align: right;
            flex-shrink: 0;
        }

        .item-price .unit {
            font-size: 0.75rem;
            color: #555;
        }

        .item-price .total {
            font-size: 0.95rem;
            font-weight: 700;
            color: #d4af37;
        }

        /* ── SUMMARY BOX ── */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid #1a1a1a;
            font-size: 0.85rem;
            color: #888;
            gap: 8px;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row.grand {
            font-size: 1.05rem;
            font-weight: 700;
            color: #d4af37;
            padding-top: 14px;
            border-top: 1px solid #2a2a2a;
            border-bottom: none;
            margin-top: 4px;
        }
        .sr-label { flex: 1; line-height: 1.4; }
        .sr-label small { display: block; font-size: 0.7rem; color: #555; margin-top: 1px; }
        .sr-val  { white-space: nowrap; text-align: right; font-weight: 500; color: #ccc; }
        .sr-val.green  { color: #4caf50; font-weight: 600; }
        .sr-val.red    { color: #ff6b6b; font-weight: 600; }
        .sr-val.gold   { color: #d4af37; font-weight: 600; }
        .sr-val.strike { text-decoration: line-through; color: #555; font-size: 0.78rem; font-weight: 400; }
        .sr-val.muted  { color: #555; font-style: italic; font-size: 0.76rem; font-weight: 400; }

        /* Assembly service display card */
        .assembly-info-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #0f0f0f;
            border: 1px solid #1e1e1e;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .assembly-info-card.assembled {
            border-color: #4caf5033;
            background: rgba(76,175,80,0.04);
        }
        .assembly-info-card.unassembled {
            border-color: #ff6b6b33;
            background: rgba(255,107,107,0.04);
        }
        .assembly-icon {
            width: 42px; height: 42px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .assembly-icon.assembled  { background: rgba(76,175,80,0.12); color: #4caf50; }
        .assembly-icon.unassembled{ background: rgba(255,107,107,0.12); color: #ff6b6b; }
        .assembly-info-text { flex: 1; }
        .assembly-info-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 2px;
        }
        .assembly-info-desc { font-size: 0.75rem; color: #666; line-height: 1.4; }
        .assembly-fee-badge {
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ── INFO GRID ── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }

        .info-cell .ic-label {
            font-size: 0.7rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .info-cell .ic-val {
            font-size: 0.88rem;
            color: #ccc;
            font-weight: 500;
        }

        /* ── PAYMENT METHOD CARD ── */
        .pay-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #161616;
            border: 1px solid #1e1e1e;
            border-radius: 10px;
            padding: 14px 18px;
        }

        .pay-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        /* ── ACTION BUTTONS ── */
        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-act-gold {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
        }

        .btn-act-gold:hover {
            background: #fff;
            color: #000;
            transform: translateY(-2px);
        }

        .btn-act-outline {
            background: transparent;
            border: 1px solid #2a2a2a;
            color: #aaa;
        }

        .btn-act-outline:hover {
            border-color: #555;
            color: #fff;
        }

        .btn-act-danger {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .btn-act-danger:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .btn-act-info {
            background: rgba(23, 162, 184, 0.1);
            border: 1px solid rgba(23, 162, 184, 0.3);
            color: #17a2b8;
        }

        .btn-act-info:hover {
            background: rgba(23, 162, 184, 0.2);
        }

        /* ── BREADCRUMB ── */
        .breadcrumb-dark {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 28px;
        }

        .breadcrumb-dark a {
            color: #555;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .breadcrumb-dark a:hover {
            color: #d4af37;
        }

        .breadcrumb-dark span {
            color: #333;
            font-size: 0.8rem;
        }

        .breadcrumb-dark .sep {
            color: #222;
        }

        /* ── INVOICE PRINT STYLES ── */
        @media print {

            header,
            footer,
            .btn-act,
            .breadcrumb-dark,
            #actionBar,
            #trackingPanel {
                display: none !important;
            }

            body {
                background: #fff;
                color: #000;
            }

            .panel {
                border: 1px solid #ddd;
                border-radius: 0;
            }

            .panel-title,
            .ic-label,
            .item-meta {
                color: #666 !important;
            }

            .item-name,
            .ic-val,
            .summary-row {
                color: #000 !important;
            }

            .summary-row.grand {
                color: #b8860b !important;
            }
        }
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <div class="container py-5">

        <!-- BREADCRUMB -->
        <div class="breadcrumb-dark">
            <a href="index.php">Home</a>
            <span class="sep"><i class="fa fa-chevron-right" style="font-size:0.6rem;"></i></span>
            <a href="myorder.php">My Orders</a>
            <span class="sep"><i class="fa fa-chevron-right" style="font-size:0.6rem;"></i></span>
            <span style="color:#888;"><?php echo htmlspecialchars($order['order_number']); ?></span>
        </div>

        <!-- ORDER HEADER -->
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <div
                    style="font-size:0.72rem;color:#555;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:4px;">
                    Order Details</div>
                <h2 style="font-family:'Playfair Display',serif; margin:0;">
                    <?php echo htmlspecialchars($order['order_number']); ?></h2>
                <div style="font-size:0.8rem;color:#444;margin-top:4px;">
                    Placed on <?php echo date('d F Y, g:i A', strtotime($order['created_at'])); ?>
                </div>
            </div>
            <span class="status-badge"
                style="color:<?php echo $statusCfg['color']; ?>; background:<?php echo $statusCfg['bg']; ?>; border-color:<?php echo $statusCfg['color']; ?>44; font-size:0.9rem; padding:8px 20px;">
                <i class="fa <?php echo $statusCfg['icon']; ?>"></i>
                <?php echo $statusCfg['label']; ?>
            </span>
        </div>

        <!-- ACTION BAR -->
        <div id="actionBar" class="d-flex flex-wrap gap-2 mb-4">
            <button class="btn-act btn-act-gold" onclick="downloadInvoice()">
                <i class="fa fa-file-invoice"></i> Download Invoice
            </button>
            <?php if (!$isCancelled): ?>
                <button class="btn-act btn-act-outline" onclick="reorder(<?php echo $order_id; ?>)">
                    <i class="fa fa-rotate-right"></i> Reorder
                </button>
            <?php endif; ?>
            <?php if (strtolower($order['order_status']) === 'processing'): ?>
                <button class="btn-act btn-act-danger"
                    onclick="cancelOrder(<?php echo $order_id; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')">
                    <i class="fa fa-times"></i> Cancel Order
                </button>
            <?php endif; ?>
            <a href="myorder.php" class="btn-act btn-act-outline">
                <i class="fa fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="row g-4">

            <!-- LEFT COLUMN -->
            <div class="col-lg-8">

                <!-- ═══ TRACKING TIMELINE ═══ -->
                <?php if (!$isCancelled): ?>
                    <div class="panel" id="trackingPanel">
                        <div class="panel-title"><i class="fa fa-map-marker-alt"></i> Order Tracking</div>

                        <?php
                        $steps = [
                            ['key' => 'processing', 'icon' => 'fa-clock', 'label' => 'Order Placed'],
                            ['key' => 'packed', 'icon' => 'fa-box', 'label' => 'Packed'],
                            ['key' => 'shipped', 'icon' => 'fa-truck', 'label' => 'Shipped'],
                            ['key' => 'delivered', 'icon' => 'fa-circle-check', 'label' => 'Delivered'],  // ← FA6 正确名称
                        ];
                        $progressPct = $currentStep === 0 ? 0 : round(($currentStep / (count($steps) - 1)) * 100);
                        ?>

                        <div class="timeline">
                            <div class="tl-progress" style="width:<?php echo $progressPct; ?>%"></div>

                            <?php foreach ($steps as $i => $step):
                                $done = $i < $currentStep;
                                $current = $i === $currentStep;

                                // When status is delivered (last step), force done so dot shows gold filled + ✓ Done
                                if ($isDelivered && $i === $currentStep) {
                                    $done    = true;
                                    $current = false;
                                }

                                $dotCls = $done ? 'done' : ($current ? 'current' : '');
                                $lblCls = $done ? 'done' : ($current ? 'current' : '');
                                ?>
                                <div class="tl-step">
                                    <div class="tl-dot <?php echo $dotCls; ?>">
                                        <i class="fa <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <div class="tl-label <?php echo $lblCls; ?>"><?php echo $step['label']; ?></div>
                                    <?php if ($done || $current): ?>
                                        <div class="tl-date">
                                            <?php echo $done ? '✓ Done' : 'In Progress'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- estimated note -->
                        <?php if (!in_array(strtolower($order['order_status']), ['delivered', 'cancelled'])): ?>
                            <div
                                style="margin-top:20px; padding:12px 16px; background:#161616; border-radius:8px; border:1px solid #1e1e1e;">
                                <div
                                    style="font-size:0.75rem; color:#555; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">
                                    Estimated Delivery</div>
                                <div style="font-size:0.88rem; color:#aaa;">
                                    <?php echo date('d M Y', strtotime($order['created_at'] . ' +5 days')); ?> —
                                    <?php echo date('d M Y', strtotime($order['created_at'] . ' +7 days')); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- CANCELLED NOTICE -->
                    <div class="panel" style="border-color:rgba(220,53,69,0.25);">
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div
                                style="width:48px;height:48px;border-radius:50%;background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.3);display:flex;align-items:center;justify-content:center;color:#dc3545;font-size:1.2rem;flex-shrink:0;">
                                <i class="fa fa-times"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;color:#dc3545;margin-bottom:2px;">Order Cancelled</div>
                                <div style="font-size:0.82rem;color:#555;">This order has been cancelled. If you were
                                    charged, a refund will be processed within 3–5 business days.</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ═══ ORDER ITEMS ═══ -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-box-open"></i> Order Items (<?php echo count($items); ?>)
                    </div>

                    <?php foreach ($items as $item): ?>
                        <div class="item-row">
                            <img src="<?php echo htmlspecialchars($item['image'] ?? 'assets/images/placeholder.jpg'); ?>"
                                class="item-img" alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                onerror="this.src='assets/images/placeholder.jpg'">

                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-meta">
                                    RM <?php echo number_format($item['product_price'], 2); ?> ×
                                    <?php echo $item['quantity']; ?>
                                </div>
                                <?php if ($isDelivered):
                                    $hasReview = isset($existingReviews[$item['product_id']]);
                                    $er        = $hasReview ? $existingReviews[$item['product_id']] : null;
                                ?>
                                    <button class="btn-act <?php echo $hasReview ? 'btn-act-outline' : 'btn-act-info'; ?> mt-2"
                                            style="padding:5px 12px;font-size:0.72rem;"
                                            onclick="reviewProduct(
                                                <?php echo $item['product_id']; ?>,
                                                '<?php echo htmlspecialchars(addslashes($item['product_name'])); ?>',
                                                <?php echo $hasReview ? $er['rating'] : 0; ?>,
                                                '<?php echo $hasReview ? htmlspecialchars(addslashes($er['review_text'] ?? '')) : ''; ?>'
                                            )">
                                        <i class="fa <?php echo $hasReview ? 'fa-pen-to-square' : 'fa-star'; ?>"></i>
                                        <?php echo $hasReview ? 'Edit Review' : 'Review'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="item-price">
                                <div class="unit">Subtotal</div>
                                <div class="total">RM <?php echo number_format($item['subtotal'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div><!-- end left col -->

            <!-- RIGHT COLUMN -->
            <div class="col-lg-4">

                <!-- ═══ ORDER SUMMARY ═══ -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-receipt"></i> Order Summary</div>

                    <!-- Assembly Service option display -->
                    <?php if ($buildData): ?>
                    <div class="assembly-info-card <?php echo $assemblyService ? 'assembled' : 'unassembled'; ?>">
                        <div class="assembly-icon <?php echo $assemblyService ? 'assembled' : 'unassembled'; ?>">
                            <i class="fa <?php echo $assemblyService ? 'fa-screwdriver-wrench' : 'fa-box-open'; ?>"></i>
                        </div>
                        <div class="assembly-info-text">
                            <div class="assembly-info-title">
                                <?php echo $assemblyService ? 'Assembly &amp; Delivery' : 'Separate Parts Delivery'; ?>
                            </div>
                            <div class="assembly-info-desc">
                                <?php if ($assemblyService): ?>
                                    My PC Store will build &amp; test your PC before delivery.
                                <?php else: ?>
                                    Parts delivered separately, unassembled.
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="assembly-fee-badge <?php echo $assemblyService ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $assemblyService ? 'FREE' : '− RM 25.00'; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Subtotal -->
                    <div class="summary-row">
                        <span class="sr-label">Subtotal</span>
                        <span class="sr-value">RM <?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>

                    <!-- Assembly Service Fee -->
                    <?php if ($buildData): ?>
                    <div class="summary-row">
                        <span class="sr-label">
                            <i class="fa fa-screwdriver-wrench me-1"
                               style="color:<?php echo $assemblyFeeAmt < 0 ? '#ff6b6b' : '#4caf50'; ?>;font-size:0.72rem;"></i>
                            Assembly Service
                        </span>
                        <?php if ($assemblyFeeAmt < 0): ?>
                            <span class="sr-val red">− RM <?php echo number_format(abs($assemblyFeeAmt), 2); ?></span>
                        <?php else: ?>
                            <span class="sr-val green">FREE</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Shipping Fee -->
                    <div class="summary-row">
                        <span class="sr-label">Shipping Fee</span>
                        <span class="sr-val gold">RM <?php echo number_format($order['shipping_fee'], 2); ?></span>
                    </div>

                    <!-- Grand Total -->
                    <div class="summary-row grand">
                        <span>Grand Total</span>
                        <span>RM <?php echo number_format($order['grand_total'], 2); ?></span>
                    </div>
                </div>

                <!-- PAYMENT INFO -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-credit-card"></i> Payment</div>

                    <?php
                    $payMethod = strtolower($order['payment_method'] ?? '');
                    $isCredit = str_contains($payMethod, 'credit');
                    $payIcon = $isCredit ? 'fa-credit-card' : 'fa-credit-card';
                    $payLabel = $isCredit ? 'Credit Card' : 'Debit Card';
                    $payColor = $isCredit ? '#d4af37' : '#17a2b8';
                    ?>

                    <div class="pay-card">
                        <div class="pay-icon"
                            style="background:rgba(<?php echo $isCredit ? '212,175,55' : '23,162,184'; ?>,0.1);color:<?php echo $payColor; ?>;">
                            <i class="fa <?php echo $payIcon; ?>"></i>
                        </div>
                        <div>
                            <div
                                style="font-size:0.75rem;color:#555;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">
                                Method</div>
                            <div style="font-weight:600;color:#ccc;">
                                <?php echo htmlspecialchars(str_replace('Demo Card', 'Card', $order['payment_method'])); ?></div>
                        </div>
                        <div class="ms-auto">
                            <span
                                style="font-size:0.75rem; font-weight:700; color:<?php echo $paymentCfg['color']; ?>;">
                                <?php echo $paymentCfg['label']; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ORDER INFO -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-info-circle"></i> Order Info</div>
                    <div class="info-grid">
                        <div class="info-cell">
                            <div class="ic-label">Order ID</div>
                            <div class="ic-val">#<?php echo $order['order_id']; ?></div>
                        </div>
                        <div class="info-cell">
                            <div class="ic-label">Order Number</div>
                            <div class="ic-val" style="font-size:0.8rem;">
                                <?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                        <div class="info-cell">
                            <div class="ic-label">Order Date</div>
                            <div class="ic-val"><?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="info-cell">
                            <div class="ic-label">Last Updated</div>
                            <div class="ic-val"><?php echo date('d M Y', strtotime($order['updated_at'])); ?></div>
                        </div>
                        <div class="info-cell">
                            <div class="ic-label">Items</div>
                            <div class="ic-val"><?php echo count($items); ?> item(s)</div>
                        </div>
                        <div class="info-cell">
                            <div class="ic-label">Status</div>
                            <div class="ic-val" style="color:<?php echo $statusCfg['color']; ?>; font-weight:700;">
                                <?php echo $statusCfg['label']; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- DELIVERY ADDRESS -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-location-dot"></i> Delivery Address</div>
                    <?php if ($deliveryAddr): ?>
                    <div style="font-size:0.85rem;color:#ccc;line-height:1.8;">
                        <div style="font-weight:700;color:#fff;margin-bottom:2px;">
                            <?php echo htmlspecialchars($deliveryAddr['receiver_name']); ?>
                        </div>
                        <div style="color:#d4af37;font-size:0.8rem;margin-bottom:6px;">
                            <?php echo htmlspecialchars($deliveryAddr['phone']); ?>
                        </div>
                        <div style="color:#aaa;">
                            <?php
                            $addrParts = array_filter([
                                $deliveryAddr['addr_line1'],
                                $deliveryAddr['addr_line2'],
                                $deliveryAddr['postcode'] . ' ' . $deliveryAddr['city'],
                                $deliveryAddr['state_name'] ?? ''
                            ]);
                            echo htmlspecialchars(implode(', ', $addrParts));
                            ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="font-size:0.82rem;color:#555;">No delivery address recorded.</div>
                    <?php endif; ?>
                </div>

            </div><!-- end right col -->

        </div><!-- end row -->

    </div><!-- container -->

    <!-- INVOICE MODAL -->
    <div class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="background:#121212;border:1px solid #2a2a2a;border-radius:12px;">

                <div class="modal-header" style="border-color:#1e1e1e;">
                    <h5 class="modal-title" style="font-family:'Playfair Display',serif;color:#fff;">
                        <i class="fa fa-file-invoice me-2" style="color:#d4af37;"></i> Invoice
                    </h5>
                    <div class="d-flex gap-2">
                        <button onclick="printInvoice()" class="btn-act btn-act-gold"
                            style="padding:6px 14px;font-size:0.75rem;">
                            <i class="fa fa-print"></i> Print / Save PDF
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                <div class="modal-body" id="invoicePrintArea" style="background:#fff;color:#000;padding:32px 40px;">

                    <!-- INVOICE HEADER -->
                    <div
                        style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:30px;padding-bottom:20px;border-bottom:2px solid #000;">
                        <div>
                            <div style="font-size:1.6rem;font-weight:800;letter-spacing:1px;color:#000;">MY PC STORE
                            </div>
                            <div style="font-size:0.75rem;color:#666;text-transform:uppercase;letter-spacing:1px;">
                                Premium Gaming Hardware</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.1rem;font-weight:700;color:#b8860b;">INVOICE</div>
                            <div style="font-size:0.85rem;color:#333;margin-top:4px;">
                                <?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div style="font-size:0.8rem;color:#666;">Date:
                                <?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                        </div>
                    </div>

                    <!-- INVOICE META -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                        <div>
                            <div
                                style="font-size:0.65rem;text-transform:uppercase;letter-spacing:1px;color:#999;margin-bottom:4px;">
                                Bill To</div>
                            <div style="font-weight:600;color:#333;">
                                <?php echo htmlspecialchars($_SESSION['name'] ?? 'Customer'); ?></div>
                            <div style="font-size:0.8rem;color:#666;">
                                <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                        </div>
                        <div>
                            <div
                                style="font-size:0.65rem;text-transform:uppercase;letter-spacing:1px;color:#999;margin-bottom:4px;">
                                Payment</div>
                            <div style="font-weight:600;color:#333;">
                                <?php echo htmlspecialchars(str_replace('Demo Card', 'Card', $order['payment_method'])); ?></div>
                            <div style="font-size:0.8rem;color:#666;">Status:
                                <?php echo htmlspecialchars($order['payment_status'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- ITEMS TABLE -->
                    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                        <thead>
                            <tr style="background:#f5f5f5;border-bottom:2px solid #ddd;">
                                <th
                                    style="padding:10px 12px;text-align:left;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#666;">
                                    Product</th>
                                <th
                                    style="padding:10px 12px;text-align:center;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#666;">
                                    Qty</th>
                                <th
                                    style="padding:10px 12px;text-align:right;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#666;">
                                    Unit Price</th>
                                <th
                                    style="padding:10px 12px;text-align:right;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#666;">
                                    Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="padding:10px 12px;font-size:0.85rem;color:#333;">
                                        <?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td style="padding:10px 12px;text-align:center;font-size:0.85rem;color:#555;">
                                        <?php echo $item['quantity']; ?></td>
                                    <td style="padding:10px 12px;text-align:right;font-size:0.85rem;color:#555;">RM
                                        <?php echo number_format($item['product_price'], 2); ?></td>
                                    <td
                                        style="padding:10px 12px;text-align:right;font-size:0.85rem;font-weight:600;color:#333;">
                                        RM <?php echo number_format($item['subtotal'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- TOTALS -->
                    <div style="display:flex;justify-content:flex-end;">
                        <div style="width:290px;">
                            <!-- Original subtotal -->
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;font-size:0.82rem;color:#666;">
                                <span>Original Subtotal</span>
                                <span <?php echo $buildDiscountAmt > 0 ? 'style="text-decoration:line-through;color:#aaa;"' : ''; ?>>
                                    RM <?php echo number_format($originalSubtotal > 0 ? $originalSubtotal : $order['total_amount'], 2); ?>
                                </span>
                            </div>
                            <?php if ($buildDiscountAmt > 0): ?>
                            <!-- PC Build Discount -->
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;font-size:0.82rem;color:#2e7d32;">
                                <span>PC Build Discount<?php echo $buildData && $buildData['discount_pct'] > 0 ? ' (' . $buildData['discount_pct'] . '%)' : ''; ?></span>
                                <span>− RM <?php echo number_format($buildDiscountAmt, 2); ?></span>
                            </div>
                            <!-- Subtotal after discount -->
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;font-size:0.82rem;color:#666;">
                                <span>Subtotal after Discount</span>
                                <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <?php else: ?>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;font-size:0.82rem;color:#666;">
                                <span>Subtotal</span>
                                <span>RM <?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <!-- Assembly Service -->
                            <?php if ($buildData): ?>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;font-size:0.82rem;<?php echo $assemblyFeeAmt < 0 ? 'color:#c62828;' : 'color:#2e7d32;'; ?>">
                                <span>Assembly Service</span>
                                <span><?php echo $assemblyFeeAmt < 0 ? '− RM ' . number_format(abs($assemblyFeeAmt), 2) : 'FREE'; ?></span>
                            </div>
                            <?php endif; ?>
                            <!-- Shipping -->
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;font-size:0.82rem;color:#666;">
                                <span>Shipping Fee</span>
                                <span>RM <?php echo number_format($order['shipping_fee'], 2); ?></span>
                            </div>
                            <!-- Grand Total -->
                            <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:1rem;font-weight:800;color:#b8860b;">
                                <span>GRAND TOTAL</span>
                                <span>RM <?php echo number_format($order['grand_total'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER NOTE -->
                    <div
                        style="margin-top:40px;padding-top:20px;border-top:1px solid #ddd;text-align:center;font-size:0.72rem;color:#aaa;">
                        Thank you for shopping at My PC Store. For support, contact us at support@mypcstore.com
                    </div>

                </div><!-- #invoicePrintArea -->
            </div>
        </div>
    </div><!-- invoiceModal -->

    <!-- REVIEW MODAL -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background:#121212;border:1px solid #2a2a2a;border-radius:12px;">
                <div class="modal-header" style="border-color:#1e1e1e;">
                    <h5 class="modal-title" style="color:#fff;">
                        <i class="fa fa-star me-2" style="color:#d4af37;"></i>
                        <span id="reviewModalTitle">Review</span>: <span id="reviewProductName"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <input type="hidden" id="reviewProductId">

                    <!-- STAR RATING -->
                    <div style="margin-bottom:16px;">
                        <div
                            style="font-size:0.72rem;color:#555;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">
                            Rating</div>
                        <div id="starRating" style="display:flex;gap:8px;">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fa fa-star" data-star="<?php echo $s; ?>"
                                    style="font-size:1.8rem;color:#2a2a2a;cursor:pointer;transition:color 0.15s;"
                                    onmouseover="hoverStars(<?php echo $s; ?>)" onmouseout="resetStars()"
                                    onclick="selectStar(<?php echo $s; ?>)"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- REVIEW TEXT -->
                    <div style="margin-bottom:16px;">
                        <div
                            style="font-size:0.72rem;color:#555;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
                            Your Review</div>
                        <textarea id="reviewText" rows="4"
                            style="width:100%;background:#1a1a1a;border:1px solid #2a2a2a;color:#fff;border-radius:8px;padding:12px;font-size:0.88rem;resize:vertical;"
                            placeholder="Share your experience with this product…"></textarea>
                    </div>

                    <button id="reviewSubmitBtn" class="btn-act btn-act-gold w-100" onclick="submitReview()">
                        <i class="fa fa-paper-plane"></i> Submit Review
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const ORDER_ID = <?php echo $order_id; ?>;

        /* INVOICE */
        function downloadInvoice() {
            const modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
            modal.show();
        }
        function printInvoice() {
            const content = document.getElementById('invoicePrintArea').innerHTML;
            const win = window.open('', '_blank', 'width=800,height=900');
            win.document.write(`
        <html><head>
        <title>Invoice - <?php echo htmlspecialchars($order['order_number']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 32px; }
            @page { margin: 20mm; }
            * { box-sizing: border-box; }
        </style>
        </head><body>${content}</body></html>
    `);
            win.document.close();
            win.focus();
            setTimeout(() => { win.print(); }, 400);
        }

        /* CANCEL ORDER */
        function cancelOrder(orderId, orderNumber) {
            Swal.fire({
                icon: 'warning',
                title: 'Cancel Order?',
                html: `Cancel order <strong style="color:#d4af37;">${orderNumber}</strong>?<br><small style="color:#888;">This action cannot be undone.</small>`,
                background: '#1a1a1a', color: '#fff',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel',
                cancelButtonText: 'Keep Order',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#2a2a2a',
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order_id=' + orderId
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success', title: 'Order Cancelled',
                                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                            }).then(() => window.location.href = 'myorder.php');
                        } else {
                            Swal.fire({
                                icon: 'error', title: 'Error', text: data.message,
                                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                            });
                        }
                    });
            });
        }

        /* REORDER */
        function reorder(orderId) {
            Swal.fire({
                icon: 'question',
                title: 'Reorder?',
                text: 'Add all items from this order to your cart?',
                background: '#1a1a1a', color: '#fff',
                showCancelButton: true,
                confirmButtonText: 'Yes, Reorder',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#2a2a2a',
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('reorder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'order_id=' + orderId
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success', title: 'Added to Cart!',
                                text: 'All items have been added to your cart.',
                                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                            }).then(() => window.location.href = 'cart.php');
                        } else {
                            Swal.fire({
                                icon: 'error', title: 'Error', text: data.message,
                                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                            });
                        }
                    });
            });
        }

        /* REVIEW */
        let selectedStar = 0;

        function reviewOrder() {
            const firstItem = <?php echo json_encode(!empty($items) ? ['id' => $items[0]['product_id'], 'name' => $items[0]['product_name']] : null); ?>;
            if (firstItem) reviewProduct(firstItem.id, firstItem.name, 0, '');
        }

        function reviewProduct(pid, name, prefillRating, prefillText) {
            document.getElementById('reviewProductId').value = pid;
            document.getElementById('reviewProductName').textContent = name;

            // Update modal title based on edit or new
            const isEdit = prefillRating > 0 || prefillText !== '';
            document.getElementById('reviewModalTitle').textContent = isEdit ? 'Edit Review' : 'Review';

            // Prefill rating
            selectedStar = prefillRating || 0;
            resetStars();

            // Prefill review text
            document.getElementById('reviewText').value = prefillText || '';

            // Update submit button label
            document.getElementById('reviewSubmitBtn').innerHTML =
                '<i class="fa fa-paper-plane"></i> ' + (isEdit ? 'Update Review' : 'Submit Review');

            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        }

        function hoverStars(n) {
            document.querySelectorAll('#starRating i').forEach((el, i) => {
                el.style.color = i < n ? '#d4af37' : '#2a2a2a';
            });
        }
        function resetStars() {
            document.querySelectorAll('#starRating i').forEach((el, i) => {
                el.style.color = i < selectedStar ? '#d4af37' : '#2a2a2a';
            });
        }
        function selectStar(n) {
            selectedStar = n;
            resetStars();
        }

        function submitReview() {
            const pid    = document.getElementById('reviewProductId').value;
            const rating = selectedStar;
            const review = document.getElementById('reviewText').value.trim();

            if (rating === 0) {
                Swal.fire({
                    icon: 'warning', title: 'Please select a rating',
                    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                });
                return;
            }
            fetch('submit_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${pid}&order_id=${ORDER_ID}&rating=${rating}&review=${encodeURIComponent(review)}`
            })
                .then(r => r.json())
                .then(data => {
                    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: data.message.includes('updated') ? 'Review Updated!' : 'Review Submitted!',
                            text: data.message,
                            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37',
                            timer: 2000, showConfirmButton: false, timerProgressBar: true
                        }).then(() => location.reload()); // reload so button switches to "Edit Review"
                    } else {
                        Swal.fire({
                            icon: 'error', title: 'Error', text: data.message,
                            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                        });
                    }
                });
        }
    </script>
</body>

</html>