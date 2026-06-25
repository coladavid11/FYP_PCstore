<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$id = intval($_GET['id']);

/* ── FETCH CATEGORIES & BRANDS ── */
$categories = $dbh->query("SELECT * FROM categories WHERE status=1 ORDER BY category_name ASC")->fetchAll(PDO::FETCH_OBJ);
$brands = $dbh->query("SELECT * FROM tblbrand ORDER BY brand_name ASC")->fetchAll(PDO::FETCH_OBJ);

/* ── FETCH PRODUCT ── */
$stmt = $dbh->prepare("SELECT * FROM products WHERE product_id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_OBJ);

if (!$product) {
    header("Location: products.php");
    exit;
}

/* ── SPEC FIELDS (matches DB columns exactly) ── */
$specFields = [
    'cpu' => 'CPU / Processor',
    'gpu' => 'GPU / Graphics Card',
    'ram' => 'RAM / Memory',
    'storage' => 'Storage',
    'display_screen' => 'Display Screen',
    'operating_system' => 'Operating System',
    'motherboard' => 'Motherboard',
    'power_supply' => 'Power Supply',
    'cooler' => 'CPU Cooler',
    'pc_case' => 'PC Case',
    'monitor' => 'Monitor',
    'keyboard' => 'Keyboard',
    'mouse' => 'Mouse',
];

/* ── MESSAGES ── */
$msg = '';
$msgType = 'success';

/* ── UPDATE HANDLER ── */
if (isset($_POST['update_product'])) {

    /* Basic fields */
    $name = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $brand_id = intval($_POST['brand_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $status = trim($_POST['status'] ?? 'active'); 
    $description = trim($_POST['description'] ?? '');

    /* Spec fields */
    $specValues = [];
    foreach ($specFields as $col => $label) {
        $specValues[$col] = trim($_POST[$col] ?? '');
    }

    /* ── IMAGE HANDLING ── */
    $image = $product->image; // keep current by default

    $deleteImage = isset($_POST['delete_image']) && $_POST['delete_image'] === '1';

    if ($deleteImage && !empty($product->image)) {
        /* DELETE existing image from disk */
        $oldPath = '../image/products/' . basename($product->image);
        $oldPathFull = $product->image; 
        if (file_exists($oldPathFull)) {
            unlink($oldPathFull);
        } elseif (file_exists($oldPath)) {
            unlink($oldPath);
        }
        $image = ''; // clear image field
    }

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../image/products/';

        /* ── AUTO NEXT NUMBER LOGIC ── */
        $existingFiles = glob($uploadDir . 'product_*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $maxNum = 0;
        foreach ($existingFiles as $f) {
            if (preg_match('/product_(\d+)\./i', basename($f), $m)) {
                $maxNum = max($maxNum, intval($m[1]));
            }
        }
        $nextNum = $maxNum + 1;
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $newName = 'product_' . $nextNum . '.' . $ext;
        $destPath = $uploadDir . $newName;
        $imageDB = '../image/products/' . $newName;

        /* Delete old image from disk first (replace) */
        if (!$deleteImage && !empty($product->image)) {
            $oldPathFull = $product->image;
            if (file_exists($oldPathFull))
                unlink($oldPathFull);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
            $image = $imageDB;
        } else {
            $msg = 'Image upload failed. Please check folder permissions for ../image/products/';
            $msgType = 'error';
        }
    }

    if ($msg === '') {
        /* ── BUILD UPDATE QUERY DYNAMICALLY WITH STATUS ── */
        $setClauses = "name=:name, category_id=:category_id, brand_id=:brand_id,
                       price=:price, stock=:stock, status=:status, description=:description, image=:image";
        foreach ($specFields as $col => $label) {
            $setClauses .= ", `$col`=:$col";
        }

        $updateSql = "UPDATE products SET $setClauses WHERE product_id=:id";
        $upStmt = $dbh->prepare($updateSql);

        $upStmt->bindParam(':name', $name, PDO::PARAM_STR);
        $upStmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $upStmt->bindParam(':brand_id', $brand_id, PDO::PARAM_INT);
        $upStmt->bindParam(':price', $price, PDO::PARAM_STR);
        $upStmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $upStmt->bindParam(':status', $status, PDO::PARAM_STR); 
        $upStmt->bindParam(':description', $description, PDO::PARAM_STR);
        $upStmt->bindParam(':image', $image, PDO::PARAM_STR);
        $upStmt->bindParam(':id', $id, PDO::PARAM_INT);

        foreach ($specFields as $col => $label) {
            $upStmt->bindParam(":$col", $specValues[$col], PDO::PARAM_STR);
        }

        if ($upStmt->execute()) {
            $msg = 'Product updated successfully.';
            $msgType = 'success';
            /* Re-fetch fresh data */
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_OBJ);
        } else {
            $msg = 'Update failed. Please try again.';
            $msgType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — Admin</title>
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
            background: #f5f5f5;
            display: flex;
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
            margin-bottom: 24px;
        }

        .topbar h1 {
            font-size: 1.5rem;
            color: #111;
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            background: #f5f5f5;
            color: #555;
            text-decoration: none;
            border: 1px solid #e0e0e0;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #e0e0e0;
            color: #222;
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 22px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #d4af37;
            color: #000;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-save:hover {
            background: #000;
            color: #d4af37;
        }

        /* ── ALERT ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* ── LAYOUT: 2 columns ── */
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px;
            align-items: start;
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header i {
            color: #d4af37;
            font-size: 0.9rem;
        }

        .card-header h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #111;
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        /* ── FORM ELEMENTS ── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        label span.req {
            color: #dc3545;
            margin-left: 2px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            color: #333;
            background: #fafafa;
            transition: border-color 0.2s;
            display: block;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #d4af37;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.08);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* ── INPUT PREFIX (PRICE CONTAINER) ── */
        .input-prefix {
            display: flex;
            align-items: center;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            background: #fafafa;
            width: 100%;
        }

        .input-prefix span {
            padding: 10px 12px;
            background: #f5f5f5;
            color: #888;
            font-size: 0.88rem;
            border-right: 1px solid #e0e0e0;
            white-space: nowrap;
        }

        .input-prefix input {
            border: none;
            border-radius: 0;
            flex: 1;
            min-width: 0;
            background: transparent;
        }

        .input-prefix input:focus {
            box-shadow: none;
        }

        .input-prefix:focus-within {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.08);
        }

        /* ── GRID ROW ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* ── STATUS FIELD STYLING ── */
        .status-options {
            display: flex;
            gap: 15px;
            width: 100%;
            margin-top: 6px;
            margin-bottom: 8px;
        }

        .status-option {
            flex: 1;
        }

        .status-option input[type="radio"] {
            display: none; 
        }

        .status-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 500;
            color: #666;
            transition: all 0.2s ease;
            user-select: none;
            text-transform: none; 
            letter-spacing: normal;
        }

        .status-option label i {
            font-size: 0.95rem;
        }

        .status-option label:hover {
            border-color: #bbb;
            background: #fafafa;
        }

        .status-option input[type="radio"]#status_active:checked + .label-active {
            background-color: #e8f5e9;
            border-color: #28a745;
            color: #1b5e20;
        }

        .status-option input[type="radio"]#status_inactive:checked + .label-inactive {
            background-color: #f8c1ccf9;
            border-color: #be4040;
            color: #343a40;
        }

        .form-hint {
            font-size: 0.8rem;
            color: #888;
            margin-top: 4px;
            margin-bottom: 12px;
        }

        .inactive-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 0.82rem;
            margin-top: 10px;
            align-items: flex-start;
            gap: 10px;
        }

        .inactive-warning i {
            font-size: 1rem;
            color: #e0a800;
            margin-top: 2px;
        }

        /* ── SPEC TABS ── */
        .spec-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }

        .spec-tab {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #e0e0e0;
            background: #fff;
            color: #888;
        }

        .spec-tab:hover {
            border-color: #d4af37;
            color: #d4af37;
        }

        .spec-tab.active {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
            font-weight: 600;
        }

        .spec-section {
            display: none;
        }

        .spec-section.active {
            display: block;
        }

        .spec-hint {
            font-size: 0.72rem;
            color: #bbb;
            margin-top: 4px;
        }

        /* ── IMAGE SECTION ── */
        .img-preview-wrap {
            position: relative;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: #fafafa;
            text-align: center;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .img-preview-wrap img {
            max-width: 100%;
            max-height: 220px;
            object-fit: contain;
            display: block;
        }

        .img-placeholder {
            color: #ccc;
        }

        .img-placeholder i {
            font-size: 2.5rem;
            margin-bottom: 8px;
            display: block;
        }

        .img-placeholder p {
            font-size: 0.78rem;
        }

        .delete-img-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: #fff5f5;
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .delete-img-wrap label {
            color: #dc3545;
            font-size: 0.78rem;
            text-transform: none;
            letter-spacing: 0;
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .delete-img-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #dc3545;
        }

        .upload-note {
            background: #fffbeb;
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 0.75rem;
            color: #8a6900;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .upload-note i {
            color: #d4af37;
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* ── STOCK BADGE ── */
        .stock-indicator {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            margin-left: auto;
        }

        .stock-ok {
            background: #d4edda;
            color: #155724;
        }

        .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }

        .stock-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .stock-label label {
            margin: 0;
        }

        /* ── BOTTOM SAVE BAR ── */
        .save-bar {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid #f0f0f0;
            padding: 14px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
            z-index: 10;
            margin-top: 4px;
            border-radius: 0 0 6px 6px;
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
        <a href="sales_report.php">📊 Sales Report</a>
        <a href="admin.php">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <div>
                <h1><i class="fa fa-pen-to-square" style="color:#d4af37;margin-right:8px;"></i>Edit Product</h1>
                <div style="font-size:0.75rem;color:#aaa;margin-top:2px;">
                    ID #<?php echo $product->product_id; ?> &nbsp;·&nbsp;
                    <?php echo htmlspecialchars($product->name); ?>
                </div>
            </div>
            <div class="topbar-right">
                <a href="products.php" class="btn-back">
                    <i class="fa fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>">
                <i class="fa fa-<?php echo $msgType === 'success' ? 'check-circle' : 'triangle-exclamation'; ?>"></i>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="editForm">

            <div class="form-layout">

                <div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-circle-info"></i>
                            <h3>Basic Information</h3>
                        </div>

                        <div class="card-body">
                            <div class="form-group">
                                <label>Product Name <span class="req">*</span></label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($product->name); ?>"
                                    required placeholder="e.g. MSI Cyborg 15">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Category <span class="req">*</span></label>
                                    <select name="category_id" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat->category_id; ?>" <?php echo $product->category_id == $cat->category_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat->category_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Brand <span class="req">*</span></label>
                                    <select name="brand_id" required>
                                        <?php foreach ($brands as $b): ?>
                                            <option value="<?php echo $b->brand_id; ?>" <?php echo $product->brand_id == $b->brand_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($b->brand_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Price (RM) <span class="req">*</span></label>
                                    <div class="input-prefix">
                                        <span>RM</span>
                                        <input type="number" name="price" step="0.01" min="0"
                                            value="<?php echo $product->price; ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="stock-label">
                                        <label>Stock Quantity <span class="req">*</span></label>
                                        <?php
                                        $s = intval($product->stock);
                                        if ($s <= 0)
                                            echo '<span class="stock-indicator stock-out">Out of Stock</span>';
                                        elseif ($s < 5)
                                            echo '<span class="stock-indicator stock-low">Low Stock</span>';
                                        else
                                            echo '<span class="stock-indicator stock-ok">In Stock</span>';
                                        ?>
                                    </div>
                                    <div class="input-container">
                                        <input type="number" name="stock" min="0" value="<?php echo $product->stock; ?>"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status <span class="req">*</span></label>
                                <div class="status-options">
                                    <div class="status-option">
                                        <input type="radio" id="status_active" name="status" value="active"
                                            <?php echo (!isset($product->status) || $product->status === 'active') ? 'checked' : ''; ?>>
                                        <label for="status_active" class="label-active">
                                            <i class="fa fa-circle-check"></i> Active
                                        </label>
                                    </div>
                                    <div class="status-option">
                                        <input type="radio" id="status_inactive" name="status" value="inactive"
                                            <?php echo (isset($product->status) && $product->status === 'inactive') ? 'checked' : ''; ?>>
                                        <label for="status_inactive" class="label-inactive">
                                            <i class="fa fa-circle-xmark"></i> Inactive
                                        </label>
                                    </div>
                                </div>
                                <div class="form-hint">Inactive products are hidden from the storefront.</div>
                                
                                <div class="inactive-warning" id="inactiveWarn" style="display: <?php echo (isset($product->status) && $product->status === 'inactive') ? 'flex' : 'none'; ?>;">
                                    <i class="fa fa-triangle-exclamation"></i>
                                    <div>
                                        <strong>Note:</strong> This product currently has <strong><?php echo intval($product->stock); ?></strong> unit<?php echo intval($product->stock) != 1 ? 's' : ''; ?> remaining in stock.
                                        Setting it to Inactive will completely hide it from the storefront workspace.
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="5"
                                    placeholder="Describe this product…"><?php echo htmlspecialchars($product->description); ?></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-microchip"></i>
                            <h3>Technical Specifications</h3>
                        </div>
                        <div class="card-body">

                            <div class="spec-tabs">
                                <?php
                                $tabGroups = [
                                    'core' => ['label' => 'Core Components', 'icon' => 'fa-microchip', 'cols' => ['cpu', 'gpu', 'ram', 'storage']],
                                    'display' => ['label' => 'Display & OS', 'icon' => 'fa-desktop', 'cols' => ['display_screen', 'operating_system']],
                                    'build' => ['label' => 'PC Build', 'icon' => 'fa-screwdriver-wrench', 'cols' => ['motherboard', 'power_supply', 'cooler', 'pc_case']],
                                    'periph' => ['label' => 'Peripherals', 'icon' => 'fa-keyboard', 'cols' => ['monitor', 'keyboard', 'mouse']],
                                ];
                                $first = true;
                                foreach ($tabGroups as $tabKey => $tab):
                                    ?>
                                    <div class="spec-tab <?php echo $first ? 'active' : ''; ?>"
                                        onclick="switchTab('<?php echo $tabKey; ?>')">
                                        <i class="fa <?php echo $tab['icon']; ?>"
                                            style="margin-right:4px;font-size:0.7rem;"></i>
                                        <?php echo $tab['label']; ?>
                                    </div>
                                    <?php $first = false; endforeach; ?>
                            </div>

                            <?php
                            $first = true;
                            foreach ($tabGroups as $tabKey => $tab):
                                ?>
                                <div class="spec-section <?php echo $first ? 'active' : ''; ?>"
                                    id="tab-<?php echo $tabKey; ?>">
                                    <?php foreach ($tab['cols'] as $col):
                                        $label = $specFields[$col];
                                        $val = htmlspecialchars($product->$col ?? '');
                                        ?>
                                        <div class="form-group">
                                            <label><?php echo $label; ?></label>
                                            <input type="text" name="<?php echo $col; ?>" value="<?php echo $val; ?>"
                                                placeholder="e.g. <?php echo $col === 'cpu' ? 'Intel Core i7-13620H' : ($col === 'ram' ? '16GB DDR5' : ($col === 'gpu' ? 'NVIDIA RTX 4060 8GB' : '')); ?>">
                                            <?php if ($val === ''): ?>
                                                <div class="spec-hint">Leave blank if not applicable for this product.</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php $first = false; endforeach; ?>

                        </div>
                    </div>

                </div>

                <div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-image"></i>
                            <h3>Product Image</h3>
                        </div>
                        <div class="card-body">

                            <div class="img-preview-wrap">
                                <?php if (!empty($product->image)): ?>
                                    <img src="<?php echo htmlspecialchars($product->image); ?>" id="imgPreview"
                                        alt="Product image"
                                        onerror="this.style.display='none';document.getElementById('imgPlaceholder').style.display='block'">
                                    <div class="img-placeholder" id="imgPlaceholder" style="display:none;">
                                        <i class="fa fa-image"></i>
                                        <p>Image not found</p>
                                    </div>
                                <?php else: ?>
                                    <div class="img-placeholder" id="imgPlaceholder">
                                        <i class="fa fa-image"></i>
                                        <p>No image uploaded</p>
                                    </div>
                                    <img id="imgPreview" style="display:none;">
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($product->image)): ?>
                                <div
                                    style="font-size:0.72rem;color:#aaa;margin-bottom:12px;word-break:break-all;padding:6px 10px;background:#fafafa;border-radius:4px;border:1px solid #f0f0f0;">
                                    <i class="fa fa-folder-open" style="color:#d4af37;margin-right:5px;"></i>
                                    <?php echo htmlspecialchars(basename($product->image)); ?>
                                </div>

                                <div class="delete-img-wrap">
                                    <input type="checkbox" name="delete_image" id="deleteImage" value="1"
                                        onchange="toggleDeleteImg(this)">
                                    <label for="deleteImage">
                                        <i class="fa fa-trash"></i>
                                        Delete current image
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Upload New Image</label>
                                <input type="file" name="image" id="newImageInput"
                                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                    onchange="previewNewImage(this)">
                            </div>

                            <div class="upload-note">
                                <i class="fa fa-circle-info"></i>
                                <div>
                                    New images are auto-named in sequence
                                    (e.g. <strong>product_59.jpg</strong>).
                                    File saved to <code
                                        style="background:#fff3cd;padding:1px 4px;border-radius:2px;">../image/products/</code>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-circle-info"></i>
                            <h3>Product Meta</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            $metaItems = [
                                ['fa fa-hashtag', 'Product ID', '#' . $product->product_id],
                                ['fa fa-calendar-days', 'Created', date('d M Y, g:i A', strtotime($product->created_at))],
                            ];
                            foreach ($metaItems as [$icon, $lbl, $val]):
                                ?>
                                <div
                                    style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f5f5f5;">
                                    <span style="font-size:0.78rem;color:#aaa;display:flex;align-items:center;gap:7px;">
                                        <i class="fa <?php echo $icon; ?>"
                                            style="color:#d4af37;width:14px;text-align:center;"></i>
                                        <?php echo $lbl; ?>
                                    </span>
                                    <span style="font-size:0.82rem;color:#555;font-weight:500;"><?php echo $val; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" name="update_product" class="btn-save"
                                style="width:100%;justify-content:center;padding:13px;">
                                <i class="fa fa-floppy-disk"></i> Save Changes
                            </button>
                            <a href="products.php" class="btn-back"
                                style="width:100%;justify-content:center;margin-top:10px;display:flex;">
                                <i class="fa fa-xmark"></i> Discard & Go Back
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </form>

    </div>

    <script>
        /* ── SPEC TABS ── */
        function switchTab(key) {
            document.querySelectorAll('.spec-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.spec-section').forEach(s => s.classList.remove('active'));

            event.currentTarget.classList.add('active');
            document.getElementById('tab-' + key).classList.add('active');
        }

        /* ── IMAGE PREVIEW ── */
        function previewNewImage(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById('imgPreview');
                const placeholder = document.getElementById('imgPlaceholder');

                preview.src = e.target.result;
                preview.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        /* ── DELETE IMAGE CHECKBOX ── */
        function toggleDeleteImg(checkbox) {
            const newInput = document.getElementById('newImageInput');
            const preview = document.getElementById('imgPreview');
            const placeholder = document.getElementById('imgPlaceholder');

            if (checkbox.checked) {
                if (preview) preview.style.opacity = '0.3';
                newInput.disabled = true;
                newInput.value = '';
                newInput.closest('.form-group').style.opacity = '0.4';
            } else {
                if (preview) preview.style.opacity = '1';
                newInput.disabled = false;
                newInput.closest('.form-group').style.opacity = '1';
            }
        }

        /* ── STATUS TOGGLE ENGINE ── */
        document.addEventListener("DOMContentLoaded", function() {
            const activeRadio = document.getElementById('status_active');
            const inactiveRadio = document.getElementById('status_inactive');
            const warningBox = document.getElementById('inactiveWarn');

            function toggleWarningPanel() {
                if (inactiveRadio.checked) {
                    warningBox.style.display = 'flex';
                } else {
                    warningBox.style.display = 'none';
                }
            }

            activeRadio.addEventListener('change', toggleWarningPanel);
            inactiveRadio.addEventListener('change', toggleWarningPanel);
        });

        /* ── CONFIRM BEFORE LEAVE ── */
        let formChanged = false;
        document.getElementById('editForm').addEventListener('change', () => { formChanged = true; });
        window.addEventListener('beforeunload', function (e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        document.querySelector('button[name="update_product"]').addEventListener('click', () => { formChanged = false; });
        document.querySelector('a.btn-back[href="products.php"]').addEventListener('click', () => { formChanged = false; });
    </script>

</body>

</html>