<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* ── TOGGLE STATUS FUNCTION (REPLACED DELETE) ── */
if (isset($_GET['toggle_status']) && isset($_GET['current'])) {
    $id = intval($_GET['toggle_status']);
    $currentStatus = trim($_GET['current']);

    // Determine new status based on current state
    $newStatus = ($currentStatus === 'Active') ? 'Inactive' : 'Active';

    $sql = "UPDATE products SET status = :new_status WHERE product_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':new_status', $newStatus, PDO::PARAM_STR);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();

    header("Location: products.php");
    exit;
}

/* ── FILTER INPUTS ── */
$search = trim($_GET['search'] ?? '');
$filterCat = intval($_GET['category'] ?? 0);
$filterBrand = intval($_GET['brand'] ?? 0);
$filterStatus = trim($_GET['status'] ?? ''); // New filter for status

/* ── FETCH CATEGORIES & BRANDS (for dropdowns) ── */
$categories = $dbh->query("SELECT category_id, category_name FROM categories WHERE status=1 ORDER BY category_name ASC")->fetchAll(PDO::FETCH_OBJ);
$brands = $dbh->query("SELECT brand_id, brand_name FROM tblbrand ORDER BY brand_name ASC")->fetchAll(PDO::FETCH_OBJ);

/* ── BUILD PRODUCT QUERY WITH FILTERS ── */
$sql = "SELECT p.*, c.category_name, b.brand_name
           FROM products p
           LEFT JOIN categories c ON p.category_id = c.category_id
           LEFT JOIN tblbrand   b ON p.brand_id    = b.brand_id
           WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR b.brand_name LIKE ? OR c.category_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($filterCat > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $filterCat;
}
if ($filterBrand > 0) {
    $sql .= " AND p.brand_id = ?";
    $params[] = $filterBrand;
}
// Apply status filtering if set
if ($filterStatus !== '') {
    $sql .= " AND p.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY p.product_id DESC";

$query = $dbh->prepare($sql);
$query->execute($params);
$products = $query->fetchAll(PDO::FETCH_OBJ);
$totalCount = count($products);

/* ── SELECTED LABELS ── */
$selectedCatName = 'All Categories';
$selectedBrandName = 'All Brands';
$selectedStatusName = 'All Statuses';

foreach ($categories as $c) {
    if ($c->category_id == $filterCat)
        $selectedCatName = $c->category_name;
}
foreach ($brands as $b) {
    if ($b->brand_id == $filterBrand)
        $selectedBrandName = $b->brand_name;
}
if ($filterStatus === 'Active')
    $selectedStatusName = 'Active';
if ($filterStatus === 'Inactive')
    $selectedStatusName = 'Inactive';

/* ── HELPER: build URL preserving other params ── */
function filterUrl(array $overrides): string
{
    $base = [
        'search' => $_GET['search'] ?? '',
        'category' => $_GET['category'] ?? 0,
        'brand' => $_GET['brand'] ?? 0,
        'status' => $_GET['status'] ?? '',
    ];
    $merged = array_merge($base, $overrides);
    $qs = http_build_query(array_filter($merged, fn($v) => $v !== '' && $v != 0));
    return 'products.php' . ($qs ? "?$qs" : '');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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
            margin-bottom: 20px;
            background: #fff;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .topbar h1 {
            font-size: 1.8rem;
            color: #111;
            font-weight: 600;
        }

        .topbar-links {
            display: flex;
            gap: 15px;
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

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #000;
            color: #d4af37;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
            border: 1px solid #d4af37;
            transition: 0.3s;
        }

        .btn-add:hover {
            background: #d4af37;
            color: #000;
        }

        /* ── FILTER BAR ── */
        .filter-bar {
            background: #fff;
            padding: 18px 24px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 18px;
        }

        .filter-row {
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
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #bbb;
            font-size: 0.85rem;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.88rem;
            color: #333;
            transition: border-color 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .search-input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
        }

        .search-input::placeholder {
            color: #bbb;
        }

        /* Custom dropdown */
        .filter-dropdown {
            position: relative;
            min-width: 160px;
        }

        .filter-dropdown .dd-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 9px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
            font-size: 0.85rem;
            color: #555;
            cursor: pointer;
            transition: border-color 0.2s;
            white-space: nowrap;
            user-select: none;
            font-family: 'Poppins', sans-serif;
        }

        .filter-dropdown .dd-toggle:hover,
        .filter-dropdown .dd-toggle.open {
            border-color: #d4af37;
        }

        .filter-dropdown .dd-toggle.active-filter {
            color: #d4af37;
            font-weight: 600;
            border-color: #d4af37;
        }

        .filter-dropdown .dd-toggle i.arrow {
            font-size: 0.65rem;
            color: #bbb;
            transition: transform 0.2s;
        }

        .filter-dropdown .dd-toggle.open i.arrow {
            transform: rotate(180deg);
        }

        .filter-dropdown .dd-menu {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            z-index: 100;
            max-height: 260px;
            overflow-y: auto;
        }

        .filter-dropdown .dd-menu.show {
            display: block;
        }

        .filter-dropdown .dd-menu::-webkit-scrollbar {
            width: 4px;
        }

        .filter-dropdown .dd-menu::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 2px;
        }

        .filter-dropdown .dd-item {
            padding: 9px 14px;
            font-size: 0.83rem;
            color: #555;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-dropdown .dd-item:hover {
            background: #fafafa;
            color: #111;
        }

        .filter-dropdown .dd-item.selected {
            color: #d4af37;
            font-weight: 600;
            background: #fffdf0;
        }

        .filter-dropdown .dd-divider {
            border: none;
            border-top: 1px solid #f0f0f0;
            margin: 3px 0;
        }

        /* Filter action buttons */
        .btn-filter {
            padding: 9px 18px;
            border-radius: 4px;
            font-size: 0.84rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
        }

        .btn-filter-go {
            background: #000;
            color: #d4af37;
            border: 1px solid #000;
        }

        .btn-filter-go:hover {
            background: #d4af37;
            color: #000;
            border-color: #d4af37;
        }

        .btn-filter-reset {
            background: #fff;
            color: #888;
            border: 1px solid #e0e0e0;
        }

        .btn-filter-reset:hover {
            border-color: #aaa;
            color: #555;
        }

        /* Active filter chips */
        .filter-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fffdf0;
            border: 1px solid rgba(212, 175, 55, 0.4);
            color: #b89a2e;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
        }

        .chip i {
            font-size: 0.6rem;
        }

        .chip:hover {
            background: #fff8d6;
            color: #9a800a;
        }

        /* ── TABLE BOX ── */
        .table-box {
            background: #fff;
            padding: 24px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        .table-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .table-meta .result-count {
            font-size: 0.82rem;
            color: #aaa;
        }

        .table-meta .result-count strong {
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

        .product-img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #eee;
            display: block;
        }

        .no-img {
            width: 52px;
            height: 52px;
            background: #f5f5f5;
            border: 1px solid #eee;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 0.7rem;
        }

        .badge-cat {
            display: inline-block;
            background: #f5f5f5;
            color: #777;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 3px;
        }

        .badge-brand {
            display: inline-block;
            background: #fffbeb;
            color: #b89a2e;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 3px;
            border: 1px solid rgba(212, 175, 55, 0.25);
        }

        /* ── STATUS STYLING ── */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid transparent;
            text-transform: capitalize;
        }

        /* Active Pill Layout */
        .status-active {
            background-color: #e8f5e9;
            color: #1b5e20;
            border-color: #c8e6c9;
        }

        .status-active::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #2e7d32;
            border-radius: 50%;
        }

        /* Inactive Pill Layout */
        .status-inactive {
            background-color: #f5f5f5; 
            color: #757575;            
            border-color: #e0e0e0;    
        }

        .status-inactive::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #757575; 
            border-radius: 50%;
        }

        /* ── STOCK STYLING ── */
        .stock-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 3px;
        }

        .stock-ok {
            background: #f0fdf4;
            color: #16a34a;
        }

        .stock-low {
            background: #fffbeb;
            color: #d97706;
        }

        .stock-out {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn {
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: 0.2s;
        }

        .action-btn.edit {
            color: #ccac3d;
        }

        .action-btn.toggle {
            color: #0284c7;
        }

        .action-btn:hover {
            text-decoration: underline;
        }

        .divider {
            color: #ddd;
            margin: 0 5px;
        }

        .date-text {
            font-size: 0.78rem;
            color: #aaa;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: #ddd;
            margin-bottom: 16px;
            display: block;
        }

        .empty-state p {
            color: #aaa;
            font-size: 0.9rem;
        }

        .empty-state a {
            color: #d4af37;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php" class="sidebar-active">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Products</h1>
            <div class="topbar-links">
                <a href="dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back</a>
                <a href="add_products.php" class="btn-add"><i class="fa fa-plus"></i> Add Product</a>
            </div>
        </div>

        <form method="GET" action="products.php" id="filterForm">
            <div class="filter-bar">
                <div class="filter-row">

                    <div class="search-wrap">
                        <i class="fa fa-search"></i>
                        <input type="text" name="search" class="search-input"
                            placeholder="Search by product name, brand or category…"
                            value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    </div>

                    <button type="submit" class="btn-filter btn-filter-go">
                        <i class="fa fa-search" style="margin-right:5px;"></i> Search
                    </button>

                    <input type="hidden" name="category" id="hiddenCategory" value="<?php echo $filterCat; ?>">
                    <div class="filter-dropdown" id="ddCat">
                        <div class="dd-toggle <?php echo $filterCat > 0 ? 'active-filter' : ''; ?>"
                            onclick="toggleDd('ddCat')">
                            <span id="ddCatLabel">
                                <i class="fa fa-layer-group"
                                    style="color:#d4af37;font-size:0.78rem;margin-right:5px;"></i>
                                <?php echo htmlspecialchars($selectedCatName); ?>
                            </span>
                            <i class="fa fa-chevron-down arrow"></i>
                        </div>
                        <div class="dd-menu" id="ddCatMenu">
                            <div class="dd-item <?php echo $filterCat === 0 ? 'selected' : ''; ?>"
                                onclick="selectFilter('category','0','All Categories','ddCat')">
                                All Categories
                            </div>
                            <hr class="dd-divider">
                            <?php foreach ($categories as $cat): ?>
                                <div class="dd-item <?php echo $filterCat == $cat->category_id ? 'selected' : ''; ?>"
                                    onclick="selectFilter('category','<?php echo $cat->category_id; ?>','<?php echo htmlspecialchars(addslashes($cat->category_name)); ?>','ddCat')">
                                    <?php echo htmlspecialchars($cat->category_name); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="brand" id="hiddenBrand" value="<?php echo $filterBrand; ?>">
                    <div class="filter-dropdown" id="ddBrand">
                        <div class="dd-toggle <?php echo $filterBrand > 0 ? 'active-filter' : ''; ?>"
                            onclick="toggleDd('ddBrand')">
                            <span id="ddBrandLabel">
                                <i class="fa fa-tag" style="color:#d4af37;font-size:0.78rem;margin-right:5px;"></i>
                                <?php echo htmlspecialchars($selectedBrandName); ?>
                            </span>
                            <i class="fa fa-chevron-down arrow"></i>
                        </div>
                        <div class="dd-menu" id="ddBrandMenu">
                            <div class="dd-item <?php echo $filterBrand === 0 ? 'selected' : ''; ?>"
                                onclick="selectFilter('brand','0','All Brands','ddBrand')">
                                All Brands
                            </div>
                            <hr class="dd-divider">
                            <?php foreach ($brands as $b): ?>
                                <div class="dd-item <?php echo $filterBrand == $b->brand_id ? 'selected' : ''; ?>"
                                    onclick="selectFilter('brand','<?php echo $b->brand_id; ?>','<?php echo htmlspecialchars(addslashes($b->brand_name)); ?>','ddBrand')">
                                    <?php echo htmlspecialchars($b->brand_name); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="status" id="hiddenStatus"
                        value="<?php echo htmlspecialchars($filterStatus); ?>">
                    <div class="filter-dropdown" id="ddStatus">
                        <div class="dd-toggle <?php echo $filterStatus !== '' ? 'active-filter' : ''; ?>"
                            onclick="toggleDd('ddStatus')">
                            <span id="ddStatusLabel">
                                <i class="fa fa-eye" style="color:#d4af37;font-size:0.78rem;margin-right:5px;"></i>
                                <?php echo htmlspecialchars($selectedStatusName); ?>
                            </span>
                            <i class="fa fa-chevron-down arrow"></i>
                        </div>
                        <div class="dd-menu" id="ddStatusMenu">
                            <div class="dd-item <?php echo $filterStatus === '' ? 'selected' : ''; ?>"
                                onclick="selectFilter('status','','All Statuses','ddStatus')">
                                All Statuses
                            </div>
                            <hr class="dd-divider">
                            <div class="dd-item <?php echo $filterStatus === 'Active' ? 'selected' : ''; ?>"
                                onclick="selectFilter('status','Active','Active','ddStatus')">
                                Active
                            </div>
                            <div class="dd-item <?php echo $filterStatus === 'Inactive' ? 'selected' : ''; ?>"
                                onclick="selectFilter('status','Inactive','Inactive','ddStatus')">
                                Inactive
                            </div>
                        </div>
                    </div>

                    <a href="products.php" class="btn-filter btn-filter-reset" style="text-decoration:none;">
                        <i class="fa fa-rotate-left" style="margin-right:5px;"></i> Reset
                    </a>

                </div>

                <?php if ($search !== '' || $filterCat > 0 || $filterBrand > 0 || $filterStatus !== ''): ?>
                    <div class="filter-chips">
                        <?php if ($search !== ''): ?>
                            <a href="<?php echo filterUrl(['search' => '']); ?>" class="chip">
                                <i class="fa fa-times"></i> "<?php echo htmlspecialchars($search); ?>"
                            </a>
                        <?php endif; ?>
                        <?php if ($filterCat > 0): ?>
                            <a href="<?php echo filterUrl(['category' => 0]); ?>" class="chip">
                                <i class="fa fa-times"></i> <?php echo htmlspecialchars($selectedCatName); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($filterBrand > 0): ?>
                            <a href="<?php echo filterUrl(['brand' => 0]); ?>" class="chip">
                                <i class="fa fa-times"></i> <?php echo htmlspecialchars($selectedBrandName); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($filterStatus !== ''): ?>
                            <a href="<?php echo filterUrl(['status' => '']); ?>" class="chip">
                                <i class="fa fa-times"></i> Status: <?php echo htmlspecialchars($filterStatus); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </form>

        <div class="table-box">
            <div class="table-meta">
                <div class="result-count">
                    Showing <strong><?php echo $totalCount; ?></strong>
                    product<?php echo $totalCount !== 1 ? 's' : ''; ?>
                    <?php if ($search !== '' || $filterCat > 0 || $filterBrand > 0 || $filterStatus !== ''): ?>
                        <span style="color:#d4af37;"> (filtered)</span>
                    <?php endif; ?>
                </div>
            </div>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>

                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td style="color:#bbb; font-size:0.8rem;">#<?php echo $product->product_id; ?></td>

                            <td>
                                <?php if (!empty($product->image)): ?>
                                    <img src="<?php echo htmlspecialchars($product->image); ?>" class="product-img"
                                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="no-img" style="display:none;"><i class="fa fa-image"></i></div>
                                <?php else: ?>
                                    <div class="no-img"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                            </td>

                            <td style="max-width:200px;">
                                <div
                                    style="font-weight:500;color:#222;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?php
                                    $name = htmlspecialchars($product->name);
                                    if ($search !== '') {
                                        $name = preg_replace(
                                            '/(' . preg_quote(htmlspecialchars($search), '/') . ')/i',
                                            '<mark style="background:#fff8d6;color:#b89a2e;border-radius:2px;padding:0 2px;">$1</mark>',
                                            $name
                                        );
                                    }
                                    echo $name;
                                    ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge-cat"><?php echo htmlspecialchars($product->category_name ?? '—'); ?></span>
                            </td>

                            <td>
                                <span class="badge-brand"><?php echo htmlspecialchars($product->brand_name ?? '—'); ?></span>
                            </td>

                            <td style="font-weight:600; color:#333;">
                                RM <?php echo number_format($product->price, 2); ?>
                            </td>

                            <td>
                                <?php
                                $stock = intval($product->stock);
                                if ($stock <= 0) {
                                    $cls = 'stock-out';
                                    $label = 'Out of Stock';
                                } elseif ($stock < 5) {
                                    $cls = 'stock-low';
                                    $label = "Low ($stock)";
                                } else {
                                    $cls = 'stock-ok';
                                    $label = $stock;
                                }
                                ?>
                                <span class="stock-badge <?php echo $cls; ?>"><?php echo $label; ?></span>
                            </td>

                            <td>
                                <?php
                                $prodStatus = $product->status ?? 'Active';
                                // Use strtolower to make sure it catches 'inactive' or 'Inactive' cleanly
                                $statusCls = (strtolower($prodStatus) === 'inactive') ? 'status-inactive' : 'status-active';
                                ?>
                                <span class="status-badge <?php echo $statusCls; ?>">
                                    <?php echo htmlspecialchars($prodStatus); ?>
                                </span>
                            </td>

                            <td>
                                <span class="date-text">
                                    <?php echo date('d M Y', strtotime($product->created_at)); ?>
                                </span>
                            </td>

                            <td>
                                <a href="edit_products.php?id=<?php echo $product->product_id; ?>" class="action-btn edit">
                                    <i class="fa fa-pen-to-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="fa fa-box-open"></i>
                                <p>No products
                                    found<?php echo ($search !== '' || $filterCat > 0 || $filterBrand > 0 || $filterStatus !== '') ? ' matching your filters' : ''; ?>.
                                </p>
                                <?php if ($search !== '' || $filterCat > 0 || $filterBrand > 0 || $filterStatus !== ''): ?>
                                    <a href="products.php">Clear filters</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script>
        /* ── DROPDOWN TOGGLE ── */
        function toggleDd(id) {
            const wrap = document.getElementById(id);
            const toggle = wrap.querySelector('.dd-toggle');
            const menu = wrap.querySelector('.dd-menu');
            const isOpen = menu.classList.contains('show');

            document.querySelectorAll('.dd-menu').forEach(m => m.classList.remove('show'));
            document.querySelectorAll('.dd-toggle').forEach(t => t.classList.remove('open'));

            if (!isOpen) {
                menu.classList.add('show');
                toggle.classList.add('open');
            }
        }

        /* ── SELECT FILTER VALUE ── */
        function selectFilter(field, value, label, ddId) {
            document.getElementById('hidden' + field.charAt(0).toUpperCase() + field.slice(1)).value = value;

            let icon = '<i class="fa fa-tag" style="color:#d4af37;font-size:0.78rem;margin-right:5px;"></i>';
            if (field === 'category') icon = '<i class="fa fa-layer-group" style="color:#d4af37;font-size:0.78rem;margin-right:5px;"></i>';
            if (field === 'status') icon = '<i class="fa fa-eye" style="color:#d4af37;font-size:0.78rem;margin-right:5px;"></i>';

            document.getElementById('dd' + field.charAt(0).toUpperCase() + field.slice(1) + 'Label').innerHTML = icon + label;

            const toggle = document.querySelector('#' + ddId + ' .dd-toggle');
            toggle.classList.toggle('active-filter', value !== '0' && value !== '');

            document.querySelectorAll('#' + ddId + ' .dd-item').forEach(el => el.classList.remove('selected'));
            event.currentTarget.classList.add('selected');

            document.getElementById(ddId).querySelector('.dd-menu').classList.remove('show');
            document.getElementById(ddId).querySelector('.dd-toggle').classList.remove('open');

            document.getElementById('filterForm').submit();
        }

        /* ── CLOSE DROPDOWNS ON OUTSIDE CLICK ── */
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.filter-dropdown')) {
                document.querySelectorAll('.dd-menu').forEach(m => m.classList.remove('show'));
                document.querySelectorAll('.dd-toggle').forEach(t => t.classList.remove('open'));
            }
        });

        /* ── SEARCH: submit on Enter ── */
        document.querySelector('.search-input').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('filterForm').submit();
            }
        });
    </script>
</body>

</html>