<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$orders = [];

if ($isLoggedIn) {
    $stmt = $dbh->prepare("
        SELECT * FROM tblorders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ── STATUS CONFIG ── */
// 注意：数据库 enum 用 'delivered'，不是 'completed'
function statusConfig(string $status): array
{
    return match (strtolower($status)) {
        'processing' => ['label' => 'Processing', 'color' => '#ffc107', 'bg' => 'rgba(255,193,7,0.1)', 'icon' => 'fa-clock'],
        'packed' => ['label' => 'Packed', 'color' => '#17a2b8', 'bg' => 'rgba(23,162,184,0.1)', 'icon' => 'fa-box'],
        'shipped' => ['label' => 'Shipped', 'color' => '#007bff', 'bg' => 'rgba(0,123,255,0.1)', 'icon' => 'fa-truck'],
        'delivered' => ['label' => 'Delivered', 'color' => '#28a745', 'bg' => 'rgba(40,167,69,0.1)', 'icon' => 'fa-check-circle'],
        'cancelled' => ['label' => 'Cancelled', 'color' => '#dc3545', 'bg' => 'rgba(220,53,69,0.1)', 'icon' => 'fa-times-circle'],
        default => ['label' => ucfirst($status), 'color' => '#aaa', 'bg' => 'rgba(170,170,170,0.1)', 'icon' => 'fa-circle'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        .page-hero {
            padding: 50px 0 36px;
            border-bottom: 1px solid #1a1a1a;
            margin-bottom: 40px;
        }

        .page-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 4px;
        }

        .page-hero p {
            color: #555;
            font-size: 0.82rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0;
        }

        /* ── STATS ROW ── */
        .stat-pill {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-pill .sp-icon {
            color: #d4af37;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .stat-pill .sp-val {
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-pill .sp-lbl {
            font-size: 0.72rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ── ORDER CARD ── */
        .order-card {
            background: #121212;
            border: 1px solid #1e1e1e;
            border-radius: 12px;
            overflow: hidden;
            transition: border-color 0.25s, box-shadow 0.25s;
            margin-bottom: 16px;
        }

        .order-card:hover {
            border-color: #2a2a2a;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.4);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #1a1a1a;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-number {
            font-size: 0.82rem;
            color: #888;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .order-id-text {
            color: #d4af37;
            font-weight: 700;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid;
        }

        .order-body {
            padding: 16px 20px;
        }

        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 16px;
        }

        .order-meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .order-meta-item .meta-label {
            font-size: 0.7rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-meta-item .meta-value {
            font-size: 0.88rem;
            color: #ccc;
            font-weight: 500;
        }

        .meta-total {
            color: #d4af37 !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
        }

        /* product thumbs strip */
        .thumb-strip {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .thumb-strip img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #2a2a2a;
        }

        .thumb-more {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            border: 1px solid #2a2a2a;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: #666;
            font-weight: 600;
        }

        /* action buttons */
        .order-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-oa {
            padding: 8px 16px;
            border-radius: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-oa-gold {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
        }

        .btn-oa-gold:hover {
            background: #fff;
            color: #000;
        }

        .btn-oa-outline {
            background: transparent;
            border: 1px solid #2a2a2a;
            color: #aaa;
        }

        .btn-oa-outline:hover {
            border-color: #555;
            color: #fff;
        }

        .btn-oa-danger {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .btn-oa-danger:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        /* filter tabs */
        .filter-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .filter-tab {
            padding: 7px 18px;
            border-radius: 20px;
            border: 1px solid #2a2a2a;
            background: transparent;
            color: #666;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            border-color: #444;
            color: #aaa;
        }

        .filter-tab.active {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
            font-weight: 700;
        }

        /* empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state .es-icon {
            font-size: 3.5rem;
            color: #1e1e1e;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: #555;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #333;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <!-- PAGE HERO -->
    <section class="page-hero">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                <div>
                    <h1>My Orders</h1>
                    <p>Track and manage your purchases</p>
                </div>
                <a href="product.php" class="btn-oa btn-oa-gold">
                    <i class="fa fa-shopping-bag"></i> Shop More
                </a>
            </div>
        </div>
    </section>

    <div class="container pb-5">

        <?php if (!$isLoggedIn): ?>

            <!-- NOT LOGGED IN -->
            <div class="order-card text-center p-5">
                <i class="fa fa-lock fa-3x mb-3" style="color:#2a2a2a;"></i>
                <h4 style="color:#777;">Login to view your orders</h4>
                <p style="color:#444;font-size:0.85rem;">Your order history will appear here once you're logged in.</p>
                <a href="login.php" class="btn-oa btn-oa-gold mt-2">
                    <i class="fa fa-sign-in-alt"></i> Login Now
                </a>
            </div>

        <?php else: ?>

            <?php
            /* ── STATS ── */
            $total = count($orders);
            $completed = count(array_filter($orders, fn($o) => strtolower($o['order_status']) === 'delivered'));
            $ongoing = count(array_filter($orders, fn($o) => in_array(strtolower($o['order_status']), ['processing', 'packed', 'shipped'])));
            $cancelled = count(array_filter($orders, fn($o) => strtolower($o['order_status']) === 'cancelled'));
            $spent = array_sum(array_column($orders, 'grand_total'));
            ?>

            <!-- STATS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-pill">
                        <div class="sp-icon"><i class="fa fa-receipt"></i></div>
                        <div>
                            <div class="sp-val"><?php echo $total; ?></div>
                            <div class="sp-lbl">Total Orders</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-pill">
                        <div class="sp-icon"><i class="fa fa-truck"></i></div>
                        <div>
                            <div class="sp-val"><?php echo $ongoing; ?></div>
                            <div class="sp-lbl">Ongoing</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-pill">
                        <div class="sp-icon"><i class="fa fa-check-circle"></i></div>
                        <div>
                            <div class="sp-val"><?php echo $completed; ?></div>
                            <div class="sp-lbl">Delivered</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-pill">
                        <div class="sp-icon"><i class="fa fa-coins"></i></div>
                        <div>
                            <div class="sp-val" style="font-size:1rem;">RM <?php echo number_format($spent, 0); ?></div>
                            <div class="sp-lbl">Total Spent</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($orders)): ?>

                <!-- EMPTY STATE -->
                <div class="order-card">
                    <div class="empty-state">
                        <div class="es-icon"><i class="fa fa-box-open"></i></div>
                        <h4>No orders yet</h4>
                        <p>You haven't placed any orders. Start shopping now!</p>
                        <a href="product.php" class="btn-oa btn-oa-gold mt-3">
                            <i class="fa fa-shopping-bag"></i> Browse Products
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <!-- FILTER TABS -->
                <div class="filter-tabs" id="filterTabs">
                    <button class="filter-tab active" data-filter="all">All (<?php echo $total; ?>)</button>
                    <button class="filter-tab" data-filter="processing">Processing
                        (<?php echo count(array_filter($orders, fn($o) => strtolower($o['order_status']) === 'processing')); ?>)</button>
                    <button class="filter-tab" data-filter="packed">Packed
                        (<?php echo count(array_filter($orders, fn($o) => strtolower($o['order_status']) === 'packed')); ?>)</button>
                    <button class="filter-tab" data-filter="shipped">Shipped
                        (<?php echo count(array_filter($orders, fn($o) => strtolower($o['order_status']) === 'shipped')); ?>)</button>
                    <button class="filter-tab" data-filter="delivered">Delivered (<?php echo $completed; ?>)</button>
                    <button class="filter-tab" data-filter="cancelled">Cancelled (<?php echo $cancelled; ?>)</button>
                </div>

                <!-- ORDER CARDS -->
                <div id="orderList">
                    <?php foreach ($orders as $order):
                        $cfg = statusConfig($order['order_status']);

                        /* fetch first 3 product images for thumbnail strip */
                        $imgStmt = $dbh->prepare("SELECT product_name, product_price, quantity FROM tblorder_item WHERE order_id = ? LIMIT 3");
                        $imgStmt->execute([$order['order_id']]);
                        $thumbItems = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

                        $itemCountStmt = $dbh->prepare("SELECT COUNT(*) FROM tblorder_item WHERE order_id = ?");
                        $itemCountStmt->execute([$order['order_id']]);
                        $totalItems = $itemCountStmt->fetchColumn();
                        ?>

                        <div class="order-card" data-status="<?php echo strtolower($order['order_status']); ?>">

                            <!-- HEADER -->
                            <div class="order-header">
                                <div>
                                    <div class="order-number">Order Number</div>
                                    <div class="order-id-text"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                </div>
                                <span class="status-badge"
                                    style="color:<?php echo $cfg['color']; ?>; background:<?php echo $cfg['bg']; ?>; border-color:<?php echo $cfg['color']; ?>33;">
                                    <i class="fa <?php echo $cfg['icon']; ?>"></i>
                                    <?php echo $cfg['label']; ?>
                                </span>
                            </div>

                            <!-- BODY -->
                            <div class="order-body">

                                <!-- META -->
                                <div class="order-meta">
                                    <div class="order-meta-item">
                                        <span class="meta-label">Date</span>
                                        <span
                                            class="meta-value"><?php echo date('d M Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <div class="order-meta-item">
                                        <span class="meta-label">Items</span>
                                        <span class="meta-value"><?php echo $totalItems; ?>
                                            item<?php echo $totalItems != 1 ? 's' : ''; ?></span>
                                    </div>
                                    <div class="order-meta-item">
                                        <span class="meta-label">Payment</span>
                                        <span class="meta-value"><?php echo htmlspecialchars(str_replace('Demo Card', 'Card', $order['payment_method'])); ?></span>
                                    </div>
                                    <div class="order-meta-item">
                                        <span class="meta-label">Total</span>
                                        <span class="meta-value meta-total">RM
                                            <?php echo number_format($order['grand_total'], 2); ?></span>
                                    </div>
                                </div>

                                <!-- ITEM PREVIEW (text names) -->
                                <?php if (!empty($thumbItems)): ?>
                                    <div style="margin-bottom:14px;">
                                        <div
                                            style="font-size:0.72rem;color:#555;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
                                            Items</div>
                                        <?php foreach ($thumbItems as $ti): ?>
                                            <div style="font-size:0.82rem;color:#888;margin-bottom:2px;">
                                                <i class="fa fa-circle"
                                                    style="font-size:0.4rem;color:#333;vertical-align:middle;margin-right:6px;"></i>
                                                <?php echo htmlspecialchars($ti['product_name']); ?>
                                                <span style="color:#555;"> × <?php echo $ti['quantity']; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($totalItems > 3): ?>
                                            <div style="font-size:0.75rem;color:#444;margin-top:4px;">+<?php echo $totalItems - 3; ?> more
                                                item(s)</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- ACTIONS -->
                                <div class="order-actions">
                                    <a href="myorder_detail.php?id=<?php echo $order['order_id']; ?>" class="btn-oa btn-oa-gold">
                                        <i class="fa fa-eye"></i> View Details
                                    </a>

                                    <?php if (strtolower($order['order_status']) === 'delivered'): ?>
                                        <button class="btn-oa btn-oa-outline" onclick="reorder(<?php echo $order['order_id']; ?>)">
                                            <i class="fa fa-rotate-right"></i> Reorder
                                        </button>
                                    <?php endif; ?>

                                    <?php if (strtolower($order['order_status']) === 'processing'): ?>
                                        <button class="btn-oa btn-oa-danger"
                                            onclick="cancelOrder(<?php echo $order['order_id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>

                    <?php endforeach; ?>
                </div><!-- #orderList -->

            <?php endif; ?>

        <?php endif; ?>

    </div><!-- container -->

    <?php include('includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        /* ── FILTER TABS ── */
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                document.querySelectorAll('.order-card[data-status]').forEach(card => {
                    card.style.display = (filter === 'all' || card.dataset.status === filter) ? 'block' : 'none';
                });
            });
        });

        /* ── CANCEL ORDER ── */
        function cancelOrder(orderId, orderNumber) {
            Swal.fire({
                icon: 'warning',
                title: 'Cancel Order?',
                html: `Are you sure you want to cancel order<br><strong style="color:#d4af37;">${orderNumber}</strong>?`,
                background: '#1a1a1a',
                color: '#fff',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel It',
                cancelButtonText: 'Keep Order',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#2a2a2a',
            }).then(result => {
                if (result.isConfirmed) {
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
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error', title: 'Error', text: data.message,
                                    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
                                });
                            }
                        });
                }
            });
        }

        /* ── REORDER ── */
        function reorder(orderId) {
            Swal.fire({
                icon: 'question',
                title: 'Reorder?',
                text: 'This will add all items from this order back to your cart.',
                background: '#1a1a1a',
                color: '#fff',
                showCancelButton: true,
                confirmButtonText: 'Yes, Reorder',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#2a2a2a',
            }).then(result => {
                if (result.isConfirmed) {
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
                }
            });
        }
    </script>
</body>

</html>