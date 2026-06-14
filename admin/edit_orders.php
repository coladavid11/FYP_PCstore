<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* ── VALIDATE ID ── */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: orders.php");
    exit;
}

/* ── FETCH ORDER ── */
$fetchQ = $dbh->prepare("
    SELECT o.*
    FROM tblorders o
    WHERE o.order_id = :id
");
$fetchQ->bindParam(':id', $id, PDO::PARAM_INT);
$fetchQ->execute();
$order = $fetchQ->fetch(PDO::FETCH_OBJ);

if (!$order) {
    header("Location: orders.php?error=not_found");
    exit;
}

/* ── FETCH ORDER ITEMS ── */
$itemsQ = $dbh->prepare("
    SELECT oi.*, p.image
    FROM tblorder_item oi
    LEFT JOIN products p ON p.product_id = oi.product_id
    WHERE oi.order_id = :id
    ORDER BY oi.order_item_id ASC
");
$itemsQ->bindParam(':id', $id, PDO::PARAM_INT);
$itemsQ->execute();
$items = $itemsQ->fetchAll(PDO::FETCH_OBJ);

/* ── FETCH RECEIVER / DELIVERY ADDRESS ── */
$addrQ = $dbh->prepare("
    SELECT a.*, s.state_name
    FROM tbl_order_address a
    LEFT JOIN tblstate s ON s.state_id = a.state_id
    WHERE a.order_id = :id
    LIMIT 1
");
$addrQ->bindParam(':id', $id, PDO::PARAM_INT);
$addrQ->execute();
$addr = $addrQ->fetch(PDO::FETCH_OBJ);

/* ── FETCH PC BUILD (match by user + total_amount + status ordered) ── */
$buildQ = $dbh->prepare("
    SELECT *
    FROM tbl_pc_build
    WHERE user_id = :uid
      AND final_price = :amt
      AND status = 'ordered'
    ORDER BY created_at DESC
    LIMIT 1
");
$buildQ->bindParam(':uid', $order->user_id, PDO::PARAM_INT);
$buildQ->bindParam(':amt', $order->total_amount);
$buildQ->execute();
$pcBuild = $buildQ->fetch(PDO::FETCH_OBJ);

$success       = false;
$statusError   = false;
$paySuccess    = false;
$payError      = false;

$validStatuses = ['processing','packed','shipped','delivered','cancelled'];
$validPayments = ['pending','paid','failed','refunded'];

/* ── HANDLE POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* Update order status */
    if ($action === 'order_status') {
        $newStatus = $_POST['order_status'] ?? '';
        if (!in_array($newStatus, $validStatuses)) {
            $statusError = true;
        } else {
            $upd = $dbh->prepare("UPDATE tblorders SET order_status = :status WHERE order_id = :id");
            $upd->bindParam(':status', $newStatus);
            $upd->bindParam(':id',     $id, PDO::PARAM_INT);
            $upd->execute();
            $success = true;
            $order->order_status = $newStatus;
        }
    }

    /* Update payment status */
    if ($action === 'payment_status') {
        $newPayment = $_POST['payment_status'] ?? '';
        if (!in_array($newPayment, $validPayments)) {
            $payError = true;
        } else {
            $upd = $dbh->prepare("UPDATE tblorders SET payment_status = :pstatus WHERE order_id = :id");
            $upd->bindParam(':pstatus', $newPayment);
            $upd->bindParam(':id',      $id, PDO::PARAM_INT);
            $upd->execute();
            $paySuccess = true;
            $order->payment_status = $newPayment;
        }
    }
}

/* Timeline steps in order */
$timeline = ['processing','packed','shipped','delivered'];
$isCancelled = $order->order_status === 'cancelled';
$currentIdx  = array_search($order->order_status, $timeline);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?php echo $order->order_id; ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { display:flex; background:#f5f5f5; }

/* ── SIDEBAR ── */
.sidebar { width:220px; height:100vh; background:#000; padding:20px; position:fixed; left:0; top:0; overflow-y:auto; }
.sidebar h2 { color:#d4af37; margin-bottom:30px; text-align:center; font-size:2rem; }
.sidebar a { display:block; color:#adadad; text-decoration:none; padding:12px; margin:10px 0; border-radius:5px; transition:0.3s; }
.sidebar a:hover { background:#d4af37; color:#000; }
.sidebar a.sidebar-active { background:#d4af37; color:#000; }

/* ── MAIN ── */
.main { margin-left:220px; width:calc(100% - 220px); padding:30px; }

/* ── TOPBAR ── */
.topbar {
    display:flex; justify-content:space-between; align-items:center;
    background:#fff; padding:15px 25px; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:24px;
}
.topbar h1 { font-size:1.6rem; color:#111; font-weight:600; }
.topbar-sub { font-size:0.72rem; color:#aaa; margin-top:2px; }
.topbar-right { display:flex; gap:10px; align-items:center; }
.btn-back {
    display:inline-flex; align-items:center; gap:6px;
    color:#d4af37; text-decoration:none; font-size:0.88rem; font-weight:500;
    padding:8px 14px; border:1px solid #d4af37; border-radius:4px; transition:0.2s;
}
.btn-back:hover { background:#d4af37; color:#000; }

/* ── TWO-COLUMN LAYOUT ── */
.edit-layout { display:flex; gap:20px; align-items:flex-start; }
.col-main  { flex:1; min-width:0; }
.col-aside { width:280px; flex-shrink:0; }

/* ── CARDS ── */
.card {
    background:#fff; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    overflow:hidden; margin-bottom:18px;
}
.card:last-child { margin-bottom:0; }
.card-header {
    padding:16px 24px; border-bottom:1px solid #f0f0f0;
    display:flex; align-items:center; gap:10px;
}
.card-header i { color:#d4af37; font-size:0.9rem; }
.card-header h2 { font-size:0.95rem; font-weight:600; color:#111; }
.card-header .badge-pill {
    margin-left:auto; font-size:0.72rem; color:#aaa;
    background:#f5f5f5; border:1px solid #e8e8e8;
    padding:3px 10px; border-radius:20px;
}
.card-body { padding:24px; }

/* ── ORDER SUMMARY TABLE ── */
.summary-table { width:100%; border-collapse:collapse; }
.summary-table th, .summary-table td { padding:10px 12px; border-bottom:1px solid #f5f5f5; text-align:left; vertical-align:middle; }
.summary-table th { font-size:0.75rem; color:#aaa; font-weight:600; text-transform:uppercase; letter-spacing:0.4px; background:#fafafa; }
.summary-table td { font-size:0.87rem; color:#444; }
.item-img { width:40px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #f0f0f0; }
.item-img-placeholder { width:40px; height:40px; background:#f5f5f5; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#ddd; font-size:1rem; border:1px solid #f0f0f0; }
.item-name { font-weight:500; color:#333; }
.totals-row td { border-bottom:none; padding-top:8px; }
.totals-block { margin-top:10px; padding-top:14px; border-top:2px solid #f0f0f0; }
.totals-line { display:flex; justify-content:space-between; font-size:0.84rem; color:#888; margin-bottom:6px; }
.totals-line.grand { font-size:0.95rem; font-weight:700; color:#222; margin-top:8px; padding-top:8px; border-top:1px solid #eee; }
.totals-line span:last-child { color:#333; }
.totals-line.grand span:last-child { color:#d4af37; font-size:1rem; }

/* FREE / deduct tag in totals */
.totals-tag {
    display:inline-block; padding:1px 8px; border-radius:20px;
    font-size:0.72rem; font-weight:700; letter-spacing:0.3px;
}
.totals-tag.green { background:#d4edda; color:#155724; border:1px solid rgba(40,167,69,0.2); }

/* ── READ-ONLY CUSTOMER INFO ── */
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.info-item label {
    display:block; font-size:0.72rem; font-weight:600;
    color:#aaa; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:4px;
}
.info-value {
    background:#f9f9f9; border:1px solid #efefef; border-radius:4px;
    padding:9px 12px; font-size:0.85rem; color:#555; line-height:1.5;
}
.info-item.full { grid-column: 1 / -1; }

/* ── TIMELINE ── */
.timeline-wrap { padding:8px 0; }
.timeline-steps {
    display:flex; align-items:flex-start; justify-content:space-between;
    position:relative; margin-bottom:28px;
}
.timeline-steps::before {
    content:''; position:absolute; top:18px; left:0; right:0; height:2px;
    background:#e0e0e0; z-index:0;
}
.tl-step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; z-index:1; }
.tl-dot {
    width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:0.85rem; border:2px solid #e0e0e0;
    background:#fff; color:#ccc; transition:all 0.2s;
}
.tl-dot.done    { background:#d4edda; border-color:#27ae60; color:#27ae60; }
.tl-dot.current { background:#d4af37; border-color:#d4af37; color:#000; }
.tl-dot.cancel  { background:#fde8e8; border-color:#e74c3c; color:#e74c3c; }
.tl-label { font-size:0.7rem; font-weight:600; color:#bbb; margin-top:7px; text-transform:uppercase; letter-spacing:0.3px; text-align:center; }
.tl-label.done    { color:#27ae60; }
.tl-label.current { color:#b89a2e; }
.tl-label.cancel  { color:#e74c3c; }

/* Status selector buttons */
.status-btn-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.status-btn {
    display:flex; align-items:center; gap:8px;
    padding:11px 14px; border-radius:4px; border:2px solid #e0e0e0;
    cursor:pointer; font-size:0.83rem; font-weight:500;
    color:#888; background:#fff; transition:all 0.2s;
    font-family:'Poppins',sans-serif; width:100%; text-align:left;
}
.status-btn:hover { border-color:#bbb; }
.status-btn i { font-size:0.8rem; }

/* Selected states */
.status-btn.sel-processing { border-color:#3498db; background:#dbeafe; color:#1a5276; }
.status-btn.sel-packed     { border-color:#9b59b6; background:#ede9fe; color:#5b2c8d; }
.status-btn.sel-shipped    { border-color:#f39c12; background:#fef9c3; color:#7d4e00; }
.status-btn.sel-delivered  { border-color:#27ae60; background:#d4edda; color:#155724; }
.status-btn.sel-cancelled  { border-color:#e74c3c; background:#fde8e8; color:#7b0000; }

/* Payment selector buttons */
.pay-btn-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
.pay-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    padding:11px 14px; border-radius:4px; border:2px solid #e0e0e0;
    cursor:pointer; font-size:0.83rem; font-weight:500;
    color:#888; background:#fff; transition:all 0.2s;
    font-family:'Poppins',sans-serif; width:100%; text-align:center;
}
.pay-btn:hover { border-color:#bbb; }
.pay-btn i { font-size:0.8rem; }
.pay-btn.sel-paid    { border-color:#27ae60; background:#d4edda; color:#155724; }
.pay-btn.sel-pending { border-color:#f39c12; background:#fff8e1; color:#7a6000; }
.pay-btn.sel-failed  { border-color:#e74c3c; background:#fde8e8; color:#7b0000; }
.pay-btn.sel-refunded  { border-color:#3498db; background:#dbeafe; color:#1a5276; }

.save-row { margin-top:16px; display:flex; gap:10px; align-items:center; }
.btn-save {
    display:inline-flex; align-items:center; gap:7px;
    background:#000; color:#d4af37; border:1px solid #d4af37;
    padding:10px 24px; border-radius:4px; font-size:0.88rem;
    font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; transition:0.2s;
}
.btn-save:hover { background:#d4af37; color:#000; }
.btn-cancel-link {
    display:inline-flex; align-items:center; gap:6px;
    background:#fff; color:#888; border:1px solid #e0e0e0;
    padding:10px 20px; border-radius:4px; font-size:0.88rem;
    font-weight:500; text-decoration:none; transition:0.2s;
}
.btn-cancel-link:hover { border-color:#aaa; color:#555; }

/* ── SIDE CARDS ── */
.side-card {
    background:#fff; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    overflow:hidden; margin-bottom:16px;
}
.side-card-header {
    padding:12px 18px; border-bottom:1px solid #f0f0f0;
    display:flex; align-items:center; gap:8px;
}
.side-card-header i { color:#d4af37; font-size:0.85rem; }
.side-card-header h3 { font-size:0.8rem; font-weight:600; color:#555; text-transform:uppercase; letter-spacing:0.4px; }
.side-card-body { padding:16px 18px; }
.meta-row { display:flex; gap:8px; align-items:flex-start; font-size:0.82rem; color:#888; margin-bottom:10px; }
.meta-row:last-child { margin-bottom:0; }
.meta-row i { color:#d4af37; width:14px; text-align:center; font-size:0.75rem; margin-top:2px; flex-shrink:0; }
.meta-row strong { color:#444; }

/* Order & payment status badges */
.os-badge, .pay-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; font-size:0.72rem; font-weight:600;
}
.os-processing { background:#dbeafe; color:#1a5276; border:1px solid rgba(52,152,219,0.3); }
.os-packed     { background:#ede9fe; color:#5b2c8d; border:1px solid rgba(155,89,182,0.3); }
.os-shipped    { background:#fef9c3; color:#7d4e00; border:1px solid rgba(243,156,18,0.3); }
.os-delivered  { background:#d4edda; color:#155724; border:1px solid rgba(39,174,96,0.2); }
.os-cancelled  { background:#fde8e8; color:#7b0000; border:1px solid rgba(231,76,60,0.25); }
.pay-paid      { background:#d4edda; color:#155724; border:1px solid rgba(40,167,69,0.2); }
.pay-pending   { background:#fff8e1; color:#7a6000; border:1px solid #ffe082; }
.pay-failed    { background:#fde8e8; color:#7b0000; border:1px solid rgba(231,76,60,0.25); }
.pay-refunded  { background:#dbeafe; color:#1a5276; border:1px solid rgba(52,152,219,0.3); }

.cancelled-notice {
    background:#fde8e8; border:1px solid rgba(231,76,60,0.25); border-radius:4px;
    padding:12px 16px; font-size:0.82rem; color:#7b0000;
    display:flex; gap:8px; align-items:flex-start; margin-bottom:16px;
}
.cancelled-notice i { margin-top:1px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>Admin</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php">📦 Products</a>
    <a href="categories.php">📂 Categories</a>
    <a href="brands.php">🏷️ Brands</a>
    <a href="orders.php" class="sidebar-active">🛒 Orders</a>
    <a href="users.php">👥 Users</a>
    <a href="admin.php">⚙ Admin</a>
</div>

<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <h1><i class="fa fa-receipt" style="color:#d4af37;margin-right:8px;font-size:1.2rem;"></i>Order Details</h1>
            <div class="topbar-sub"><?php echo htmlspecialchars($order->order_number); ?></div>
        </div>
        <div class="topbar-right">
            <a href="orders.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Orders</a>
        </div>
    </div>

    <div class="edit-layout">

        <!-- MAIN COLUMN -->
        <div class="col-main">

            <!-- ORDER SUMMARY -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-bag-shopping"></i>
                    <h2>Order Summary</h2>
                    <span class="badge-pill"><?php echo htmlspecialchars($order->order_number); ?></span>
                </div>
                <div class="card-body">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th style="width:48px;"></th>
                                <th>Product</th>
                                <th style="width:80px;text-align:center;">Qty</th>
                                <th style="width:90px;text-align:right;">Unit Price</th>
                                <th style="width:100px;text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item->image)): ?>
                                <img src="<?php echo htmlspecialchars($item->image); ?>" alt="" class="item-img">
                                <?php else: ?>
                                <div class="item-img-placeholder"><i class="fa fa-box"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="item-name"><?php echo htmlspecialchars($item->product_name); ?></span></td>
                            <td style="text-align:center;"><?php echo $item->quantity; ?></td>
                            <td style="text-align:right;">RM <?php echo number_format($item->product_price, 2); ?></td>
                            <td style="text-align:right;font-weight:600;">RM <?php echo number_format($item->subtotal, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="totals-block">
                        <div class="totals-line">
                            <span>Subtotal</span>
                            <span>RM <?php echo number_format($order->total_amount, 2); ?></span>
                        </div>

                        <?php if ($pcBuild): ?>

                        <?php if ($pcBuild->discount_pct > 0): ?>
                        <div class="totals-line" style="color:#27ae60;">
                            <span>
                                <i class="fa fa-tag" style="font-size:0.75rem;margin-right:4px;"></i>
                                PC Build Discount (<?php echo $pcBuild->discount_pct; ?>% off)
                            </span>
                            <span>− RM <?php echo number_format($pcBuild->discount_amt, 2); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="totals-line" style="color:<?php echo $pcBuild->assembly_service ? '#27ae60' : '#e67e22'; ?>;">
                            <span>
                                <i class="fa <?php echo $pcBuild->assembly_service ? 'fa-screwdriver-wrench' : 'fa-box-open'; ?>"
                                   style="font-size:0.75rem;margin-right:4px;"></i>
                                Assembly Service
                            </span>
                            <span>
                                <?php if ($pcBuild->assembly_service): ?>
                                    <span class="totals-tag green">FREE</span>
                                <?php else: ?>
                                    − RM 25.00
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php endif; /* end $pcBuild */ ?>

                        <div class="totals-line">
                            <span>
                                <i class="fa fa-truck" style="font-size:0.75rem;margin-right:4px;color:#aaa;"></i>
                                Shipping Fee
                            </span>
                            <span>RM <?php echo number_format($order->shipping_fee, 2); ?></span>
                        </div>

                        <?php if ($order->service_fee > 0): ?>
                        <div class="totals-line">
                            <span>Service Fee</span>
                            <span>RM <?php echo number_format($order->service_fee, 2); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="totals-line grand">
                            <span>Grand Total</span>
                            <span>RM <?php echo number_format($order->grand_total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECEIVER INFORMATION (read-only) -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-location-dot"></i>
                    <h2>Receiver Information</h2>
                    <span class="badge-pill" style="display:flex;align-items:center;gap:4px;">
                        <i class="fa fa-lock" style="font-size:0.65rem;"></i> Read-only
                    </span>
                </div>
                <div class="card-body">

                <?php if ($addr): ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Receiver Name</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->receiver_name); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->phone); ?></div>
                        </div>
                        <div class="info-item full">
                            <label>Address Line 1</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->addr_line1); ?></div>
                        </div>
                        <?php if (!empty($addr->addr_line2)): ?>
                        <div class="info-item full">
                            <label>Address Line 2</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->addr_line2); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <label>Postcode</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->postcode); ?></div>
                        </div>
                        <div class="info-item">
                            <label>City</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->city); ?></div>
                        </div>
                        <div class="info-item full">
                            <label>State</label>
                            <div class="info-value"><?php echo htmlspecialchars($addr->state_name ?? '—'); ?></div>
                        </div>
                    </div>

                    <!-- FULL DELIVERY ADDRESS (formatted) -->
                    <div style="margin-top:18px; padding-top:16px; border-top:1px solid #f0f0f0;">
                        <label style="display:block;font-size:0.72rem;font-weight:600;color:#aaa;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:8px;">
                            <i class="fa fa-map-pin" style="color:#d4af37;margin-right:4px;"></i>
                            Full Delivery Address
                        </label>
                        <div class="info-value" style="line-height:1.7; background:#f9f9f9;">
                            <strong><?php echo htmlspecialchars($addr->receiver_name); ?></strong><br>
                            <?php echo htmlspecialchars($addr->addr_line1); ?><br>
                            <?php if (!empty($addr->addr_line2)) echo htmlspecialchars($addr->addr_line2) . '<br>'; ?>
                            <?php echo htmlspecialchars($addr->postcode) . ' ' . htmlspecialchars($addr->city); ?><br>
                            <?php echo htmlspecialchars($addr->state_name ?? ''); ?><br>
                            <span style="color:#888;font-size:0.82rem;">
                                <i class="fa fa-phone" style="font-size:0.7rem;margin-right:3px;"></i>
                                <?php echo htmlspecialchars($addr->phone); ?>
                            </span>
                        </div>
                    </div>

                <?php else: ?>
                    <div style="text-align:center;padding:30px;color:#bbb;">
                        <i class="fa fa-map-location-dot" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                        <p style="font-size:0.85rem;">No delivery address recorded for this order.</p>
                    </div>
                <?php endif; ?>

                </div>
            </div>

            <!-- ORDER STATUS (editable) -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-timeline"></i>
                    <h2>Order Status</h2>
                </div>
                <div class="card-body">

                    <!-- Progress timeline (visual only) -->
                    <div class="timeline-wrap">
                        <?php if ($isCancelled): ?>
                        <div class="cancelled-notice">
                            <i class="fa fa-circle-xmark"></i>
                            <span>This order has been <strong>cancelled</strong>. You can still change the status below if needed.</span>
                        </div>
                        <?php endif; ?>

                        <div class="timeline-steps">
                            <?php
                            $tlDefs = [
                                'processing' => ['fa-hourglass-half', 'Processing'],
                                'packed'     => ['fa-box',            'Packed'],
                                'shipped'    => ['fa-truck',          'Shipped'],
                                'delivered'  => ['fa-circle-check',   'Delivered'],
                            ];
                            foreach ($tlDefs as $step => [$ico, $lbl]):
                                $stepIdx = array_search($step, $timeline);
                                if ($isCancelled) {
                                    $dotClass = $lbl_class = '';
                                } elseif ($stepIdx < $currentIdx) {
                                    $dotClass = $lbl_class = 'done';
                                } elseif ($stepIdx === $currentIdx) {
                                    $dotClass = $lbl_class = 'current';
                                } else {
                                    $dotClass = $lbl_class = '';
                                }
                            ?>
                            <div class="tl-step">
                                <div class="tl-dot <?php echo $dotClass; ?>">
                                    <i class="fa <?php echo $ico; ?>"></i>
                                </div>
                                <div class="tl-label <?php echo $lbl_class; ?>"><?php echo $lbl; ?></div>
                            </div>
                            <?php endforeach; ?>

                            <!-- Cancelled always shown as last step -->
                            <div class="tl-step">
                                <div class="tl-dot <?php echo $isCancelled ? 'cancel' : ''; ?>">
                                    <i class="fa fa-circle-xmark"></i>
                                </div>
                                <div class="tl-label <?php echo $isCancelled ? 'cancel' : ''; ?>">Cancelled</div>
                            </div>
                        </div>
                    </div>

                    <!-- Status selector -->
                    <form method="POST" action="edit_orders.php?id=<?php echo $id; ?>" id="statusForm">
                    <div class="status-btn-grid">
                        <?php
                        $btnDefs = [
                            'processing' => ['fa-hourglass-half', 'Processing'],
                            'packed'     => ['fa-box',            'Packed'],
                            'shipped'    => ['fa-truck',          'Shipped'],
                            'delivered'  => ['fa-circle-check',   'Delivered'],
                            'cancelled'  => ['fa-circle-xmark',   'Cancelled'],
                        ];
                        foreach ($btnDefs as $val => [$ico, $lbl]):
                            $sel = $order->order_status === $val ? 'sel-'.$val : '';
                        ?>
                        <button type="button"
                                class="status-btn <?php echo $sel; ?>"
                                data-val="<?php echo $val; ?>"
                                onclick="selectStatus('<?php echo $val; ?>', '<?php echo $lbl; ?>')">
                            <i class="fa <?php echo $ico; ?>"></i> <?php echo $lbl; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="action" value="order_status">
                    <input type="hidden" name="order_status" id="orderStatusInput"
                           value="<?php echo htmlspecialchars($order->order_status); ?>">

                    <div class="save-row">
                        <button type="button" class="btn-save" onclick="submitStatus()">
                            <i class="fa fa-floppy-disk"></i> Save Status
                        </button>
                        <a href="orders.php" class="btn-cancel-link">
                            <i class="fa fa-xmark"></i> Cancel
                        </a>
                    </div>
                    </form>

                </div>
            </div>


            <!-- PAYMENT STATUS (editable) -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-credit-card"></i>
                    <h2>Payment Status</h2>
                </div>
                <div class="card-body">
                    <p style="font-size:0.82rem;color:#aaa;margin-bottom:18px;">
                        Select the new payment status and click <strong>Save Payment</strong> to update.
                    </p>

                    <form method="POST" action="edit_orders.php?id=<?php echo $id; ?>" id="payForm">
                    <input type="hidden" name="action" value="payment_status">

                    <div class="pay-btn-grid">
                        <?php
                        $payDefs = [
                            'pending' => ['fa-clock',        'Pending'],
                            'paid'    => ['fa-circle-check', 'Paid'],
                            'failed'  => ['fa-circle-xmark', 'Failed'],
                            'refunded'  => ['fa-rotate',       'Refunded'],
                        ];
                        foreach ($payDefs as $val => [$ico, $lbl]):
                            $sel = $order->payment_status === $val ? 'sel-'.$val : '';
                        ?>
                        <button type="button"
                                class="pay-btn <?php echo $sel; ?>"
                                data-pay="<?php echo $val; ?>"
                                onclick="selectPayment('<?php echo $val; ?>', '<?php echo $lbl; ?>')">
                            <i class="fa <?php echo $ico; ?>"></i> <?php echo $lbl; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="payment_status" id="paymentStatusInput"
                           value="<?php echo htmlspecialchars($order->payment_status); ?>">

                    <div class="save-row">
                        <button type="button" class="btn-save" onclick="submitPayment()">
                            <i class="fa fa-floppy-disk"></i> Save Payment
                        </button>
                        <a href="orders.php" class="btn-cancel-link">
                            <i class="fa fa-xmark"></i> Cancel
                        </a>
                    </div>
                    </form>

                </div>
            </div>

        </div><!-- col-main -->

        <!-- ASIDE -->
        <div class="col-aside">

            <!-- Order Info -->
            <div class="side-card">
                <div class="side-card-header">
                    <i class="fa fa-circle-info"></i>
                    <h3>Order Info</h3>
                </div>
                <div class="side-card-body">
                    <div class="meta-row">
                        <i class="fa fa-hashtag"></i>
                        <span>Order ID: <strong>#<?php echo $order->order_id; ?></strong></span>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-barcode"></i>
                        <div>
                            <div style="margin-bottom:2px;">Order Number</div>
                            <strong style="font-size:0.78rem;"><?php echo htmlspecialchars($order->order_number); ?></strong>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-calendar"></i>
                        <div>
                            <div style="margin-bottom:2px;">Order Date</div>
                            <strong><?php echo date('d M Y', strtotime($order->created_at)); ?></strong>
                            <div style="font-size:0.74rem;color:#bbb;margin-top:1px;"><?php echo date('h:i A', strtotime($order->created_at)); ?></div>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-rotate"></i>
                        <div>
                            <div style="margin-bottom:2px;">Last Updated</div>
                            <strong><?php echo date('d M Y', strtotime($order->updated_at)); ?></strong>
                            <div style="font-size:0.74rem;color:#bbb;margin-top:1px;"><?php echo date('h:i A', strtotime($order->updated_at)); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment -->
            <div class="side-card">
                <div class="side-card-header">
                    <i class="fa fa-credit-card"></i>
                    <h3>Payment</h3>
                </div>
                <div class="side-card-body">
                    <div class="meta-row">
                        <i class="fa fa-money-bill-wave"></i>
                        <div>
                            <div style="margin-bottom:2px;">Method</div>
                            <strong><?php echo htmlspecialchars($order->payment_method); ?></strong>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-signal"></i>
                        <div>
                            <div style="margin-bottom:4px;">Payment Status</div>
                            <?php
                            $payClass = match($order->payment_status) {
                                'paid'   => 'pay-paid',
                                'failed' => 'pay-failed',
                                'refunded' => 'pay-refunded',
                                default  => 'pay-pending',
                            };
                            $payIcon = match($order->payment_status) {
                                'paid'   => 'fa-circle-check',
                                'failed' => 'fa-circle-xmark',
                                'refunded' => 'fa-rotate',
                                default  => 'fa-clock',
                            };
                            ?>
                            <span class="pay-badge <?php echo $payClass; ?>" id="asidePayBadge">
                                <i class="fa <?php echo $payIcon; ?>" style="font-size:0.62rem;"></i>
                                <?php echo ucfirst($order->payment_status); ?>
                            </span>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-tag"></i>
                        <div>
                            <div style="margin-bottom:2px;">Grand Total</div>
                            <strong style="color:#d4af37;font-size:1rem;">RM <?php echo number_format($order->grand_total, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Status -->
            <div class="side-card">
                <div class="side-card-header">
                    <i class="fa fa-truck-fast"></i>
                    <h3>Current Status</h3>
                </div>
                <div class="side-card-body">
                    <div class="meta-row">
                        <i class="fa fa-circle-dot"></i>
                        <div>
                            <div style="margin-bottom:4px;">Order Status</div>
                            <?php
                            $osClass = 'os-'.$order->order_status;
                            $osIcon  = match($order->order_status) {
                                'processing' => 'fa-hourglass-half',
                                'packed'     => 'fa-box',
                                'shipped'    => 'fa-truck',
                                'delivered'  => 'fa-circle-check',
                                'cancelled'  => 'fa-circle-xmark',
                                default      => 'fa-circle',
                            };
                            ?>
                            <span class="os-badge <?php echo $osClass; ?>" id="currentStatusBadge">
                                <i class="fa <?php echo $osIcon; ?>" style="font-size:0.62rem;"></i>
                                <?php echo ucfirst($order->order_status); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- col-aside -->

    </div><!-- edit-layout -->

</div><!-- main -->

<script>
/* ── STATUS SELECTION ── */
let selectedStatus = '<?php echo $order->order_status; ?>';
const originalStatus = '<?php echo $order->order_status; ?>';

function selectStatus(val, label) {
    selectedStatus = val;
    document.getElementById('orderStatusInput').value = val;

    /* Update button highlight */
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.className = 'status-btn';
        if (btn.dataset.val === val) btn.classList.add('sel-' + val);
    });
}

/* ── SUBMIT WITH SWAL CONFIRM ── */
function submitStatus() {
    if (selectedStatus === originalStatus) {
        Swal.fire({
            icon: 'info',
            title: 'No Change',
            text: 'The order status is already set to "' + selectedStatus + '".',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d4af37',
            background: '#fff',
        });
        return;
    }

    const labels = {
        processing: 'Processing',
        packed:     'Packed',
        shipped:    'Shipped',
        delivered:  'Delivered',
        cancelled:  'Cancelled',
    };
    const icons = {
        processing: '⏳', packed: '📦', shipped: '🚚', delivered: '✅', cancelled: '❌', 
    };

    Swal.fire({
        icon: 'question',
        title: 'Update Order Status?',
        html: `Change status from
               <b style="color:#888;">${labels[originalStatus]}</b>
               → <b style="color:#d4af37;">${icons[selectedStatus]} ${labels[selectedStatus]}</b>?
               <br><br>
               <span style="font-size:0.85rem;color:#aaa;">This will update the order record immediately.</span>`,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-floppy-disk"></i> Yes, Update',
        cancelButtonText:  'Cancel',
        confirmButtonColor: '#000',
        cancelButtonColor:  '#6c757d',
        background: '#fff',
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('statusForm').submit();
        }
    });
}

/* ── SUCCESS SWAL ── */
<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Status Updated!',
    html: `Order <b style="color:#d4af37;"><?php echo htmlspecialchars(addslashes($order->order_number)); ?></b>
           is now <b><?php echo ucfirst($order->order_status); ?></b>.`,
    confirmButtonText: '<i class="fa fa-check"></i> OK',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>

<?php if ($statusError): ?>
Swal.fire({
    icon: 'error',
    title: 'Invalid Status',
    text: 'The selected status is not valid. Please try again.',
    confirmButtonText: 'OK',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>

/* ── PAYMENT STATUS SELECTION ── */
let selectedPayment = '<?php echo $order->payment_status; ?>';
const originalPayment = '<?php echo $order->payment_status; ?>';

function selectPayment(val) {
    selectedPayment = val;
    document.getElementById('paymentStatusInput').value = val;

    document.querySelectorAll('.pay-btn').forEach(btn => {
        btn.className = 'pay-btn';
        if (btn.dataset.pay === val) btn.classList.add('sel-' + val);
    });
}

/* ── SUBMIT PAYMENT WITH SWAL CONFIRM ── */
function submitPayment() {
    if (selectedPayment === originalPayment) {
        Swal.fire({
            icon: 'info',
            title: 'No Change',
            text: 'The payment status is already set to "' + selectedPayment + '".',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d4af37',
            background: '#fff',
        });
        return;
    }

    const payLabels = { pending: 'Pending', paid: 'Paid', failed: 'Failed', refunded: 'Refunded' };
    const payIcons  = { pending: '🕐', paid: '✅', failed: '❌', refunded: '🔄' };

    Swal.fire({
        icon: 'question',
        title: 'Update Payment Status?',
        html: `Change payment from
               <b style="color:#888;">${payLabels[originalPayment]}</b>
               → <b style="color:#d4af37;">${payIcons[selectedPayment]} ${payLabels[selectedPayment]}</b>?
               <br><br>
               <span style="font-size:0.85rem;color:#aaa;">This will update the payment record immediately.</span>`,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-floppy-disk"></i> Yes, Update',
        cancelButtonText:  'Cancel',
        confirmButtonColor: '#000',
        cancelButtonColor:  '#6c757d',
        background: '#fff',
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('payForm').submit();
        }
    });
}

/* ── PAYMENT SUCCESS / ERROR SWALS ── */
<?php if ($paySuccess): ?>
Swal.fire({
    icon: 'success',
    title: 'Payment Updated!',
    html: `Payment for order <b style="color:#d4af37;"><?php echo htmlspecialchars(addslashes($order->order_number)); ?></b>
           is now <b><?php echo ucfirst($order->payment_status); ?></b>.`,
    confirmButtonText: '<i class="fa fa-check"></i> OK',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>

<?php if ($payError): ?>
Swal.fire({
    icon: 'error',
    title: 'Invalid Payment Status',
    text: 'The selected payment status is not valid. Please try again.',
    confirmButtonText: 'OK',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>
</script>

</body>
</html>