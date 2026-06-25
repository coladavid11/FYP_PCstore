<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* ── DASHBOARD STATS ── */
$statsQ = $dbh->query("
    SELECT
        COUNT(*) AS total,
        SUM(order_status = 'processing') AS cnt_processing,
        SUM(order_status = 'packed')     AS cnt_packed,
        SUM(order_status = 'shipped')    AS cnt_shipped,
        SUM(order_status = 'delivered')  AS cnt_delivered,
        SUM(order_status = 'cancelled')  AS cnt_cancelled
    FROM tblorders
");
$stats = $statsQ->fetch(PDO::FETCH_OBJ);

/* ── FILTER INPUTS ── */
$search = trim($_GET['search'] ?? '');
$filterSt = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'latest';

$validStatuses = ['processing', 'packed', 'shipped', 'delivered', 'cancelled'];

$sortMap = [
    'latest' => 'o.created_at DESC',
    'oldest' => 'o.created_at ASC',
    'amount_hi' => 'o.grand_total DESC',
    'amount_lo' => 'o.grand_total ASC',
];
$orderBy = $sortMap[$sort] ?? 'o.created_at DESC';

/* ── FETCH ORDERS ── */
$sql = "SELECT o.*, u.fullname, u.gmail
           FROM tblorders o
           LEFT JOIN tbluser u ON u.user_id = o.user_id
           WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (o.order_number LIKE ? OR u.fullname LIKE ? OR u.gmail LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterSt !== '' && in_array($filterSt, $validStatuses)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $filterSt;
}

$sql .= " ORDER BY $orderBy";

$query = $dbh->prepare($sql);
$query->execute($params);
$orders = $query->fetchAll(PDO::FETCH_OBJ);
$total = count($orders);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            background: #f5f5f5;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #000;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #d4af37;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .sidebar a {
            display: block;
            color: #adadad;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #d4af37;
            color: #000;
        }

        .sidebar a.sidebar-active {
            background: #d4af37;
            color: #000;
        }

        /* ── MAIN ── */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .topbar h1 {
            font-size: 1.6rem;
            color: #111;
            font-weight: 600;
        }

        .topbar-sub {
            font-size: 0.72rem;
            color: #aaa;
            margin-top: 2px;
        }

        .topbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-back {
            color: #d4af37;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .btn-back:hover {
            opacity: 0.75;
        }

        /* ── STAT CARDS ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 16px 18px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: border-color 0.2s, transform 0.15s;
            text-decoration: none;
            display: block;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.active-filter {
            border-bottom-color: #d4af37;
        }

        .stat-card-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-card-label i {
            font-size: 0.8rem;
        }

        .stat-card-num {
            font-size: 1.7rem;
            font-weight: 700;
            color: #222;
            line-height: 1;
        }

        .stat-card-sub {
            font-size: 0.7rem;
            color: #bbb;
            margin-top: 4px;
        }

        /* colour accents per status */
        .stat-card.s-all {
            border-bottom-color: #d4af37;
        }

        .stat-card.s-processing .stat-card-label i {
            color: #3498db;
        }

        .stat-card.s-packed .stat-card-label i {
            color: #9b59b6;
        }

        .stat-card.s-shipped .stat-card-label i {
            color: #f39c12;
        }

        .stat-card.s-delivered .stat-card-label i {
            color: #27ae60;
        }

        .stat-card.s-cancelled .stat-card-label i {
            color: #e74c3c;
        }

        /* ── FILTER BAR ── */
        .filter-bar {
            background: #fff;
            padding: 16px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 18px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-wrap i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #bbb;
            font-size: 0.82rem;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 9px 12px 9px 32px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #333;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
        }

        .search-input::placeholder {
            color: #bbb;
        }

        .sort-select {
            padding: 9px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #555;
            background: #fff;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: border-color 0.2s;
            min-width: 170px;
        }

        .sort-select:focus {
            outline: none;
            border-color: #d4af37;
        }

        .btn-search {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 4px;
            border: none;
            background: #000;
            color: #d4af37;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
        }

        .btn-search:hover {
            background: #d4af37;
            color: #000;
        }

        .btn-reset {
            padding: 9px 14px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            background: #fff;
            color: #888;
            font-size: 0.85rem;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-reset:hover {
            border-color: #aaa;
            color: #555;
        }

        /* Status filter pills */
        .filter-pills {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-pills span {
            font-size: 0.78rem;
            color: #aaa;
        }

        .fpill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid #e0e0e0;
            color: #777;
            text-decoration: none;
            transition: 0.15s;
            cursor: pointer;
        }

        .fpill:hover {
            border-color: #bbb;
        }

        .fpill.fp-active {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
            font-weight: 700;
        }

        .fpill.fp-processing {}

        .fpill.fp-processing.on {
            background: #dbeafe;
            border-color: #3498db;
            color: #1a5276;
        }

        .fpill.fp-packed.on {
            background: #ede9fe;
            border-color: #9b59b6;
            color: #5b2c8d;
        }

        .fpill.fp-shipped.on {
            background: #fef9c3;
            border-color: #f39c12;
            color: #7d4e00;
        }

        .fpill.fp-delivered.on {
            background: #d4edda;
            border-color: #27ae60;
            color: #155724;
        }

        .fpill.fp-cancelled.on {
            background: #fde8e8;
            border-color: #e74c3c;
            color: #7b0000;
        }

        /* ── TABLE BOX ── */
        .table-box {
            background: #fff;
            padding: 24px 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        .table-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .result-info {
            font-size: 0.8rem;
            color: #aaa;
        }

        .result-info strong {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 13px 12px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            color: #d4af37;
            font-weight: 600;
            background: #fafafa;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
        }

        table td {
            color: #444;
            font-size: 0.88rem;
        }

        table tr:hover td {
            background: #fcfcfc;
        }

        .order-num {
            font-weight: 600;
            color: #222;
            font-size: 0.83rem;
        }

        .order-id {
            color: #bbb;
            font-size: 0.75rem;
        }

        .date-text {
            font-size: 0.78rem;
            color: #aaa;
        }

        .cust-name {
            font-weight: 500;
            color: #333;
        }

        .cust-email {
            font-size: 0.75rem;
            color: #aaa;
        }

        .amount-val {
            font-weight: 600;
            color: #222;
        }

        /* Order status badges */
        .os-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .os-processing {
            background: #dbeafe;
            color: #1a5276;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .os-packed {
            background: #ede9fe;
            color: #5b2c8d;
            border: 1px solid rgba(155, 89, 182, 0.3);
        }

        .os-shipped {
            background: #fef9c3;
            color: #7d4e00;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .os-delivered {
            background: #d4edda;
            color: #155724;
            border: 1px solid rgba(39, 174, 96, 0.2);
        }

        .os-cancelled {
            background: #fde8e8;
            color: #7b0000;
            border: 1px solid rgba(231, 76, 60, 0.25);
        }

        /* Payment badge */
        .pay-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .pay-paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .pay-pending {
            background: #fff8e1;
            color: #7a6000;
            border: 1px solid #ffe082;
        }

        .pay-failed {
            background: #fde8e8;
            color: #7b0000;
            border: 1px solid rgba(231, 76, 60, 0.25);
        }

        .action-btn {
            text-decoration: none;
            font-weight: 500;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
        }

        .action-btn.edit {
            color: #ccac3d;
        }

        .action-btn:hover {
            text-decoration: underline;
        }

        /* Sort pills */
        .sort-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 500;
            border: 1px solid #e0e0e0;
            color: #888;
            text-decoration: none;
            transition: 0.15s;
        }

        .sort-pill.active {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
            font-weight: 700;
        }

        .sort-pill:hover {
            border-color: #aaa;
            color: #555;
        }

        /* Empty */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #bbb;
        }

        .empty-state i {
            font-size: 2.2rem;
            display: block;
            margin-bottom: 12px;
        }
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
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="sales_report.php">📊 Sales Report</a>
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <h1><i class="fa fa-cart-shopping" style="color:#d4af37;margin-right:8px;font-size:1.3rem;"></i>Orders
                </h1>
                <div class="topbar-sub">Manage and track all customer orders</div>
            </div>
            <div class="topbar-right">
                <a href="dashboard.php" class="btn-back"><i class="fa fa-arrow-left"
                        style="margin-right:4px;"></i>Back</a>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="stat-grid">

            <a href="orders.php"
                class="stat-card s-all <?php echo $filterSt === '' && $search === '' ? 'active-filter' : ''; ?>">
                <div class="stat-card-label"><i class="fa fa-list" style="color:#d4af37;"></i> Total Orders</div>
                <div class="stat-card-num"><?php echo $stats->total; ?></div>
                <div class="stat-card-sub">All time</div>
            </a>

            <a href="orders.php?status=processing"
                class="stat-card s-processing <?php echo $filterSt === 'processing' ? 'active-filter' : ''; ?>">
                <div class="stat-card-label"><i class="fa fa-hourglass-half"></i> Processing</div>
                <div class="stat-card-num"><?php echo $stats->cnt_processing; ?></div>
                <div class="stat-card-sub">Awaiting action</div>
            </a>

            <a href="orders.php?status=packed"
                class="stat-card s-packed <?php echo $filterSt === 'packed' ? 'active-filter' : ''; ?>">
                <div class="stat-card-label"><i class="fa fa-box"></i> Packed</div>
                <div class="stat-card-num"><?php echo $stats->cnt_packed; ?></div>
                <div class="stat-card-sub">Ready to ship</div>
            </a>

            <a href="orders.php?status=shipped"
                class="stat-card s-shipped <?php echo $filterSt === 'shipped' ? 'active-filter' : ''; ?>">
                <div class="stat-card-label"><i class="fa fa-truck"></i> Shipped</div>
                <div class="stat-card-num"><?php echo $stats->cnt_shipped; ?></div>
                <div class="stat-card-sub">In transit</div>
            </a>

            <a href="orders.php?status=delivered"
                class="stat-card s-delivered <?php echo $filterSt === 'delivered' ? 'active-filter' : ''; ?>">
                <div class="stat-card-label"><i class="fa fa-circle-check"></i> Delivered</div>
                <div class="stat-card-num"><?php echo $stats->cnt_delivered; ?></div>
                <div class="stat-card-sub">Completed</div>
            </a>

            <a href="orders.php?status=cancelled"
                class="stat-card s-cancelled <?php echo $filterSt === 'cancelled' ? 'active-filter' : ''; ?>">
                <div class="stat-card-label"><i class="fa fa-circle-xmark"></i> Cancelled</div>
                <div class="stat-card-num"><?php echo $stats->cnt_cancelled; ?></div>
                <div class="stat-card-sub">Unsuccessful</div>
            </a>

        </div>

        <!-- FILTER BAR -->
        <form method="GET" action="orders.php" id="filterForm">
            <div class="filter-bar">

                <div class="search-wrap">
                    <i class="fa fa-search"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search order number or customer…"
                        value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                </div>

                <!-- Status filter pills (hidden input updated by JS) -->
                <input type="hidden" name="status" id="statusInput" value="<?php echo htmlspecialchars($filterSt); ?>">

                <div class="filter-pills">
                    <span>Status:</span>
                    <a class="fpill <?php echo $filterSt === '' ? 'fp-active' : ''; ?>" onclick="setStatus('')">All</a>
                    <?php
                    $pillDefs = [
                        'processing' => ['fp-processing', 'fa-hourglass-half', 'Processing'],
                        'packed' => ['fp-packed', 'fa-box', 'Packed'],
                        'shipped' => ['fp-shipped', 'fa-truck', 'Shipped'],
                        'delivered' => ['fp-delivered', 'fa-circle-check', 'Delivered'],
                        'cancelled' => ['fp-cancelled', 'fa-circle-xmark', 'Cancelled'],
                    ];
                    foreach ($pillDefs as $val => [$cls, $ico, $lbl]):
                        $on = $filterSt === $val ? 'on' : '';
                        ?>
                        <a class="fpill <?php echo $cls; ?> <?php echo $on; ?>" onclick="setStatus('<?php echo $val; ?>')">
                            <i class="fa <?php echo $ico; ?>" style="font-size:0.65rem;"></i>
                            <?php echo $lbl; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="amount_hi" <?php echo $sort === 'amount_hi' ? 'selected' : ''; ?>>Amount: High → Low
                    </option>
                    <option value="amount_lo" <?php echo $sort === 'amount_lo' ? 'selected' : ''; ?>>Amount: Low → High
                    </option>
                </select>

                <button type="submit" class="btn-search">
                    <i class="fa fa-search"></i> Search
                </button>
                <a href="orders.php" class="btn-reset">
                    <i class="fa fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>

        <!-- TABLE BOX -->
        <div class="table-box">

            <div class="table-meta">
                <div class="result-info">
                    Showing <strong><?php echo $total; ?></strong> order<?php echo $total !== 1 ? 's' : ''; ?>
                    <?php if ($filterSt !== ''): ?>
                        &nbsp;· Status: <strong style="color:#d4af37;"><?php echo ucfirst($filterSt); ?></strong>
                    <?php endif; ?>
                    <?php if ($search !== ''): ?>
                        &nbsp;· Search: <strong style="color:#d4af37;">"<?php echo htmlspecialchars($search); ?>"</strong>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:6px;">
                    <?php
                    $pills = ['latest' => 'Latest', 'oldest' => 'Oldest', 'amount_hi' => 'High $', 'amount_lo' => 'Low $'];
                    foreach ($pills as $k => $lbl):
                        $url = '?sort=' . $k
                            . ($filterSt !== '' ? '&status=' . urlencode($filterSt) : '')
                            . ($search !== '' ? '&search=' . urlencode($search) : '');
                        ?>
                        <a href="<?php echo $url; ?>"
                            class="sort-pill <?php echo $sort === $k ? 'active' : ''; ?>"><?php echo $lbl; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:6%;">ID</th>
                        <th style="width:18%;">Order Number</th>
                        <th style="width:18%;">Customer</th>
                        <th style="width:11%;">Payment</th>
                        <th style="width:13%;">Order Status</th>
                        <th style="width:11%;">Amount</th>
                        <th style="width:13%;">Date</th>
                        <th style="width:10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="order-id">#<?php echo $o->order_id; ?></td>

                                <td>
                                    <span class="order-num"><?php echo htmlspecialchars($o->order_number); ?></span>
                                </td>

                                <td>
                                    <div class="cust-name"><?php echo htmlspecialchars($o->fullname ?? '—'); ?></div>
                                    <div class="cust-email"><?php echo htmlspecialchars($o->gmail ?? ''); ?></div>
                                </td>

                                <td>
                                    <?php
                                    $payClass = match ($o->payment_status) {
                                        'paid' => 'pay-paid',
                                        'failed' => 'pay-failed',
                                        default => 'pay-pending',
                                    };
                                    $payIcon = match ($o->payment_status) {
                                        'paid' => 'fa-circle-check',
                                        'failed' => 'fa-circle-xmark',
                                        default => 'fa-clock',
                                    };
                                    ?>
                                    <span class="pay-badge <?php echo $payClass; ?>">
                                        <i class="fa <?php echo $payIcon; ?>" style="font-size:0.62rem;"></i>
                                        <?php echo ucfirst($o->payment_status); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $osClass = 'os-' . $o->order_status;
                                    $osIcon = match ($o->order_status) {
                                        'processing' => 'fa-hourglass-half',
                                        'packed' => 'fa-box',
                                        'shipped' => 'fa-truck',
                                        'delivered' => 'fa-circle-check',
                                        'cancelled' => 'fa-circle-xmark',
                                        default => 'fa-circle',
                                    };
                                    ?>
                                    <span class="os-badge <?php echo $osClass; ?>">
                                        <i class="fa <?php echo $osIcon; ?>" style="font-size:0.62rem;"></i>
                                        <?php echo ucfirst($o->order_status); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="amount-val">RM <?php echo number_format($o->grand_total, 2); ?></span>
                                </td>

                                <td>
                                    <span class="date-text"><?php echo date('d M Y', strtotime($o->created_at)); ?></span>
                                    <div style="font-size:0.72rem;color:#ccc;">
                                        <?php echo date('h:i A', strtotime($o->created_at)); ?></div>
                                </td>

                                <td>
                                    <a href="edit_orders.php?id=<?php echo $o->order_id; ?>" class="action-btn edit">
                                        <i class="fa fa-pen-to-square"></i> Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fa fa-cart-shopping"></i>
                                    <p>No orders
                                        found<?php echo ($search !== '' || $filterSt !== '') ? ' for the current filters' : ''; ?>.
                                    </p>
                                    <?php if ($search !== '' || $filterSt !== ''): ?>
                                        <a href="orders.php" style="color:#d4af37;font-size:0.82rem;">Clear filters</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div><!-- table-box -->

    </div><!-- main -->

    <script>
        function setStatus(val) {
            document.getElementById('statusInput').value = val;
            document.getElementById('filterForm').submit();
        }

        document.querySelector('.search-input')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('filterForm').submit(); }
        });

        <?php if (isset($_GET['updated'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Order Updated',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: '#fff',
            });
        <?php endif; ?>
    </script>

</body>

</html>