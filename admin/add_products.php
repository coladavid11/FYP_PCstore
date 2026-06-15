<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$msg = '';
$error = '';

/* ── FETCH CATEGORIES & BRANDS ── */
$categories = $dbh->query("SELECT * FROM categories WHERE status=1 ORDER BY category_name ASC")->fetchAll(PDO::FETCH_OBJ);
$brands = $dbh->query("SELECT * FROM tblbrand ORDER BY brand_name ASC")->fetchAll(PDO::FETCH_OBJ);

/* ── SPEC FIELDS (matches DB columns) ── */
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

/* ── ADD PRODUCT HANDLER ── */
if (isset($_POST['add_product'])) {

    /* Basic fields */
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $brand_id = intval($_POST['brand_id'] ?? 0);

    /* Spec fields */
    $specValues = [];
    foreach ($specFields as $col => $label) {
        $specValues[$col] = trim($_POST[$col] ?? '');
    }

    /* ── CHECK FOR DUPLICATE PRODUCT NAME ── */
    $check_sql = "SELECT COUNT(*) FROM products WHERE name = :pname";
    $check_query = $dbh->prepare($check_sql);
    $check_query->bindParam(':pname', $name, PDO::PARAM_STR);
    $check_query->execute();
    $product_exists = $check_query->fetchColumn();

    /* ── VALIDATION ── */
    if ($name === '') {
        $error = 'Product name is required.';
    } elseif ($product_exists > 0) {
        // Blocks insertion and retains form data matching your exact custom validation string
        $error = 'This product name is already exist';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative.';
    } elseif ($stock < 0) {
        $error = 'Stock cannot be negative.';
    } elseif ($category_id <= 0) {
        $error = 'Please select a category.';
    } elseif ($brand_id <= 0) {
        $error = 'Please select a brand.';
    } elseif (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a product image.';
    } else {

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG, JPEG, PNG, GIF and WEBP files are allowed.';
        } else {

            $uploadDir = '../image/products/';

            /* ── AUTO NEXT NUMBER ── */
            $existing = glob($uploadDir . 'product_*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            $maxNum = 0;
            foreach ($existing as $f) {
                if (preg_match('/product_(\d+)\./i', basename($f), $m)) {
                    $maxNum = max($maxNum, intval($m[1]));
                }
            }
            $nextNum = $maxNum + 1;
            $newName = 'product_' . $nextNum . '.' . $ext;
            $destPath = $uploadDir . $newName;
            $imageDB = '../image/products/' . $newName;

            /* Create folder if missing */
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $error = 'Image upload failed. Check folder permissions for ../image/products/';
            } else {

                /* ── BUILD INSERT QUERY ── */
                $specCols = implode(', ', array_map(fn($c) => "`$c`", array_keys($specFields)));
                $specPlaceholders = implode(', ', array_map(fn($c) => ":$c", array_keys($specFields)));

                $sql = "INSERT INTO products
                            (name, description, price, stock, category_id, brand_id, image, $specCols)
                        VALUES
                            (:name, :description, :price, :stock, :category_id, :brand_id, :image, $specPlaceholders)";

                $query = $dbh->prepare($sql);
                $query->bindParam(':name', $name, PDO::PARAM_STR);
                $query->bindParam(':description', $description, PDO::PARAM_STR);
                $query->bindParam(':price', $price, PDO::PARAM_STR);
                $query->bindParam(':stock', $stock, PDO::PARAM_INT);
                $query->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $query->bindParam(':brand_id', $brand_id, PDO::PARAM_INT);
                $query->bindParam(':image', $imageDB, PDO::PARAM_STR);

                foreach ($specFields as $col => $label) {
                    $query->bindParam(":$col", $specValues[$col], PDO::PARAM_STR);
                }

                if ($query->execute()) {
                    $newProductId = $dbh->lastInsertId();
                    $msg = "Product added successfully! Saved as <strong>$newName</strong> (Product ID: #$newProductId)";
                    /* Reset POST values after success */
                    $_POST = [];
                } else {
                    $error = 'Database insert failed. Please try again.';
                    /* Clean up uploaded file if DB failed */
                    if (file_exists($destPath))
                        unlink($destPath);
                }
            }
        }
    }
}

/* ── HELPER: get old POST value or default ── */
function old(string $key, string $default = ''): string
{
    return htmlspecialchars($_POST[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product — Admin</title>
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
            font-family: 'Poppins', sans-serif;
        }

        .btn-save:hover {
            background: #000;
            color: #d4af37;
        }

        /* ── ALERT ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            font-weight: 500;
            line-height: 1.5;
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

        .alert i {
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* ── LAYOUT ── */
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
            background: #fafafa;
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

        /* border highlight for invalid fields */
        input.field-error,
        select.field-error {
            border-color: #dc3545 !important;
            background: #fff8f8;
        }

        .input-prefix {
            display: flex;
            align-items: center;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            background: #fafafa;
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
            background: transparent;
        }

        .input-prefix input:focus {
            box-shadow: none;
        }

        .input-prefix:focus-within {
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.08);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
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

        /* ── IMAGE UPLOAD ZONE ── */
        .img-drop-zone {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafafa;
            margin-bottom: 14px;
            position: relative;
        }

        .img-drop-zone:hover,
        .img-drop-zone.drag-over {
            border-color: #d4af37;
            background: #fffdf5;
        }

        .img-drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .img-drop-zone i {
            font-size: 2rem;
            color: #ccc;
            display: block;
            margin-bottom: 8px;
        }

        .img-drop-zone p {
            font-size: 0.82rem;
            color: #aaa;
            margin: 0;
        }

        .img-drop-zone .drop-sub {
            font-size: 0.72rem;
            color: #ccc;
            margin-top: 4px;
        }

        /* Preview appears here after file select */
        .img-preview-wrap {
            display: none;
            border: 2px dashed #d4af37;
            border-radius: 8px;
            overflow: hidden;
            background: #fafafa;
            text-align: center;
            margin-bottom: 14px;
            position: relative;
        }

        .img-preview-wrap img {
            max-width: 100%;
            max-height: 220px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .img-preview-name {
            padding: 8px 12px;
            background: #fffdf5;
            font-size: 0.72rem;
            color: #888;
            border-top: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .img-preview-name i {
            color: #d4af37;
        }

        .btn-remove-img {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(220, 53, 69, 0.85);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            cursor: pointer;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .btn-remove-img:hover {
            background: #dc3545;
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

        /* ── PROGRESS INDICATOR ── */
        .form-steps {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 24px;
            background: #fff;
            border-radius: 6px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
            gap: 8px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: #bbb;
        }

        .step-item.done {
            color: #28a745;
        }

        .step-item.active {
            color: #d4af37;
            font-weight: 600;
        }

        .step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            border: 2px solid #e0e0e0;
            color: #bbb;
            background: #fff;
        }

        .step-item.done .step-num {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        .step-item.active .step-num {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
        }

        .step-sep {
            width: 30px;
            height: 2px;
            background: #e0e0e0;
            flex-shrink: 0;
        }

        .step-sep.done {
            background: #28a745;
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
        <a href="admin.php">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <div>
                <h1><i class="fa fa-plus-circle" style="color:#d4af37;margin-right:8px;"></i>Add New Product</h1>
                <div style="font-size:0.75rem;color:#aaa;margin-top:2px;">Fill in all required fields and upload a
                    product image.</div>
            </div>
            <a href="products.php" class="btn-back">
                <i class="fa fa-arrow-left"></i> Back to Products
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                <div><?php echo $msg; ?> &nbsp;<a href="products.php" style="color:#155724;font-weight:600;">View all
                        products →</a></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="addForm" novalidate>

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
                                <input type="text" name="name" value="<?php echo old('name'); ?>"
                                    placeholder="e.g. MSI Cyborg 15 Gaming Laptop" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Category <span class="req">*</span></label>
                                    <select name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat->category_id; ?>"
                                                <?php echo (($_POST['category_id'] ?? '') == $cat->category_id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat->category_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Brand <span class="req">*</span></label>
                                    <select name="brand_id" required>
                                        <option value="">Select Brand</option>
                                        <?php foreach ($brands as $b): ?>
                                            <option value="<?php echo $b->brand_id; ?>"
                                                <?php echo (($_POST['brand_id'] ?? '') == $b->brand_id) ? 'selected' : ''; ?>>
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
                                            value="<?php echo old('price'); ?>" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Stock Quantity <span class="req">*</span></label>
                                    <input type="number" name="stock" min="0" value="<?php echo old('stock'); ?>"
                                        placeholder="0" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="5"
                                    placeholder="Describe this product — features, specs overview, condition…"><?php echo old('description'); ?></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-microchip"></i>
                            <h3>Technical Specifications <span
                                    style="color:#bbb;font-weight:400;font-size:0.8rem;">(Optional)</span></h3>
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
                            $specPlaceholders = [
                                'cpu' => 'e.g. Intel Core i7-13620H',
                                'gpu' => 'e.g. NVIDIA GeForce RTX 4060 8GB',
                                'ram' => 'e.g. 16GB DDR5 5200MHz',
                                'storage' => 'e.g. 1TB NVMe PCIe 4.0 SSD',
                                'display_screen' => 'e.g. 15.6" FHD 144Hz IPS',
                                'operating_system' => 'e.g. Windows 11 Home',
                                'motherboard' => 'e.g. ASUS TUF Gaming B760-PLUS',
                                'power_supply' => 'e.g. ASUS ROG Strix 1000W Gold',
                                'cooler' => 'e.g. DeepCool LS720 360mm AIO',
                                'pc_case' => 'e.g. Lian Li Lancool 216 RGB',
                                'monitor' => 'e.g. LG 27" 4K 144Hz',
                                'keyboard' => 'e.g. Logitech G Pro X TKL',
                                'mouse' => 'e.g. Razer DeathAdder V3',
                            ];
                            $first = true;
                            foreach ($tabGroups as $tabKey => $tab):
                                ?>
                                <div class="spec-section <?php echo $first ? 'active' : ''; ?>"
                                    id="tab-<?php echo $tabKey; ?>">
                                    <?php foreach ($tab['cols'] as $col): ?>
                                        <div class="form-group">
                                            <label><?php echo $specFields[$col]; ?></label>
                                            <input type="text" name="<?php echo $col; ?>" value="<?php echo old($col); ?>"
                                                placeholder="<?php echo $specPlaceholders[$col] ?? ''; ?>">
                                            <div class="spec-hint">Leave blank if not applicable for this product type.</div>
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
                            <h3>Product Image <span class="req" style="font-size:0.8rem;">*</span></h3>
                        </div>
                        <div class="card-body">

                            <div class="img-preview-wrap" id="previewWrap">
                                <img id="imgPreview" src="" alt="Preview">
                                <button type="button" class="btn-remove-img" onclick="removeImage()" title="Remove">
                                    <i class="fa fa-times"></i>
                                </button>
                                <div class="img-preview-name">
                                    <i class="fa fa-file-image"></i>
                                    <span id="previewName"></span>
                                </div>
                            </div>

                            <div class="img-drop-zone" id="dropZone">
                                <input type="file" name="image" id="imageInput"
                                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                    onchange="previewImage(this)" required>
                                <i class="fa fa-cloud-arrow-up"></i>
                                <p>Click to upload or drag & drop</p>
                                <div class="drop-sub">JPG, JPEG, PNG, GIF, WEBP accepted</div>
                            </div>

                            <div class="upload-note">
                                <i class="fa fa-circle-info"></i>
                                <div>
                                    Image will be auto-named in sequence
                                    (e.g. <strong>product_59.jpg</strong>)
                                    and saved to
                                    <code
                                        style="background:#fff3cd;padding:1px 4px;border-radius:2px;">../image/products/</code>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-list-check"></i>
                            <h3>Before You Submit</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            $checks = [
                                ['Product name filled in', 'name'],
                                ['Category selected', 'category_id'],
                                ['Brand selected', 'brand_id'],
                                ['Price entered', 'price'],
                                ['Stock quantity set', 'stock'],
                                ['Image uploaded', null],
                            ];
                            foreach ($checks as [$label, $field]):
                                $done = $field ? !empty($_POST[$field]) : false;
                                ?>
                                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5;"
                                    id="check-<?php echo $field ?? 'image'; ?>">
                                    <div
                                        style="width:20px;height:20px;border-radius:50%;border:2px solid <?php echo $done ? '#28a745' : '#e0e0e0'; ?>;
                             background:<?php echo $done ? '#28a745' : 'transparent'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <?php if ($done): ?>
                                            <i class="fa fa-check" style="color:#fff;font-size:0.6rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span style="font-size:0.82rem;color:<?php echo $done ? '#28a745' : '#aaa'; ?>;">
                                        <?php echo $label; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" name="add_product" class="btn-save"
                                style="width:100%;justify-content:center;padding:14px;font-size:0.95rem;">
                                <i class="fa fa-plus-circle"></i> Add Product
                            </button>
                            <a href="products.php" class="btn-back"
                                style="width:100%;justify-content:center;margin-top:10px;display:flex;">
                                <i class="fa fa-xmark"></i> Cancel
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
        function previewImage(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('imgPreview').src = e.target.result;
                document.getElementById('previewName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                document.getElementById('previewWrap').style.display = 'block';
                document.getElementById('dropZone').style.display = 'none';
                markCheck('image', true);
            };
            reader.readAsDataURL(file);
        }

        function removeImage() {
            document.getElementById('imageInput').value = '';
            document.getElementById('imgPreview').src = '';
            document.getElementById('previewWrap').style.display = 'none';
            document.getElementById('dropZone').style.display = 'block';
            markCheck('image', false);
        }

        /* ── DRAG & DROP ── */
        const dropZone = document.getElementById('dropZone');
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', e => { dropZone.classList.remove('drag-over'); });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const dt = e.dataTransfer;
            if (dt.files.length) {
                document.getElementById('imageInput').files = dt.files;
                previewImage(document.getElementById('imageInput'));
            }
        });

        /* ── LIVE CHECKLIST ── */
        function markCheck(field, done) {
            const row = document.getElementById('check-' + field);
            if (!row) return;
            const dot = row.querySelector('div');
            const text = row.querySelector('span');
            dot.style.borderColor = done ? '#28a745' : '#e0e0e0';
            dot.style.background = done ? '#28a745' : 'transparent';
            dot.innerHTML = done ? '<i class="fa fa-check" style="color:#fff;font-size:0.6rem;"></i>' : '';
            text.style.color = done ? '#28a745' : '#aaa';
        }

        /* Watch required fields for checklist */
        ['name', 'category_id', 'brand_id', 'price', 'stock'].forEach(field => {
            const el = document.querySelector('[name="' + field + '"]');
            if (el) {
                el.addEventListener('input', () => markCheck(field, el.value.trim() !== ''));
                el.addEventListener('change', () => markCheck(field, el.value.trim() !== ''));
            }
        });

        /* ── CLIENT-SIDE VALIDATION ── */
        document.getElementById('addForm').addEventListener('submit', function (e) {
            let valid = true;
            const required = [
                { name: 'name', msg: 'Product name is required.' },
                { name: 'category_id', msg: 'Please select a category.' },
                { name: 'brand_id', msg: 'Please select a brand.' },
                { name: 'price', msg: 'Price is required.' },
                { name: 'stock', msg: 'Stock quantity is required.' },
            ];

            required.forEach(r => {
                const el = this.querySelector('[name="' + r.name + '"]');
                if (el && el.value.trim() === '') {
                    el.classList.add('field-error');
                    el.addEventListener('input', () => el.classList.remove('field-error'), { once: true });
                    valid = false;
                }
            });

            const imgInput = document.getElementById('imageInput');
            if (!imgInput.files || !imgInput.files.length) {
                document.getElementById('dropZone').style.borderColor = '#dc3545';
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>

</body>

</html>