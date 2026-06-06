<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* =========================
   FETCH PRODUCTS
========================= */

$sql = "SELECT products.*, categories.category_name, brand_name
        FROM products
        LEFT JOIN categories
        ON products.category_id = categories.category_id
        LEFT JOIN tblbrand
        ON products.brand_id = tblbrand.brand_id
        ORDER BY products.product_id DESC";

$query = $dbh->prepare($sql);
$query->execute();

$products = $query->fetchAll(PDO::FETCH_OBJ);

//delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    //GET IMAGE
    $sql = "SELECT image FROM products WHERE product_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $productDelete = $query->fetch(PDO::FETCH_OBJ);

    //DELETE IMAGE
    if ($productDelete) {
        $imagePath = "img/" . $productDelete->image;
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    //DELETE DATABASE
    $sql = "DELETE FROM products WHERE product_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();

    //REFRESH PAGE
    header("Location: products.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* =========================
           GENERAL RESET
        ========================= */
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

        /* =========================
           SIDEBAR
        ========================= */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #000;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar h2 {
            color: #d4af37;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
        }

        .sidebar a {
            display: block;
            color: #adadad;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
            font-size: 0.95rem;
        }

        .sidebar a:hover {
            background: #d4af37;
            color: #000;
        }

        .sidebar a.active {
            background: #d4af37;
            color: #000;
            font-weight: 500;
        }

        /* =========================
           MAIN CONTENT
        ========================= */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        /* =========================
           TOPBAR
        ========================= */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
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

        .Back {
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            color: #d4af37;
            font-size: 0.95rem;
        }

        .Back:hover {
            opacity: 0.7;
        }

        /* =========================
           TABLE BOX CONTAINER
        ========================= */
        .table-box {
            background: #fff;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        .table-box h3 {
            margin-bottom: 20px;
            color: #111;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-add {
            display: inline-block;
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

        .action-btn {
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .action-btn.edit {
            color: #ccac3d;
        }

        .action-btn.delete {
            color: #ff4d4d;
        }

        .action-btn:hover {
            text-decoration: underline;
        }

        .divider {
            color: #ccc;
            margin: 0 5px;
        }

        /* Table Architecture */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th,
        table td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: middle;
        }

        table th {
            color: #d4af37;
            font-weight: 600;
            background-color: #fafafa;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        table td {
            color: #444;
            font-size: 0.95rem;
        }

        table tr:hover td {
            background-color: #fcfcfc;
        }

        /* Product Image Grid View Element */
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #eee;
            display: block;
        }

        /* Action Text Anchors Matching Category Layout */
        .action-btn {
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .action-btn.edit {
            color: #ccac3d;
        }

        .action-btn.delete {
            color: #ff4d4d;
        }

        .action-btn:hover {
            text-decoration: underline;
        }

        .divider {
            color: #ccc;
            margin: 0 5px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Admin</h2>
        <a href="products.php" class="active">Products</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Products</h1>

            <div class="topbar-links">
                <a href="dashboard.php" class="Back">Back</a>

                <a href="add_products.php" class="btn-add">
                    + Add Product
                </a>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-box">

            <table>

                <tr>

                    <th>ID</th>

                    <th>Image</th>

                    <th>Product Name</th>

                    <th>Category</th>

                    <th>Brand</th>

                    <th>Price</th>

                    <th>Stock</th>

                    <th>Created</th>

                    <th style="width: 15%;">Action</th>

                </tr>

                <?php if ($products) { ?>

                    <?php foreach ($products as $product) { ?>

                        <tr>

                            <!-- ID -->
                            <td>

                                <?php echo $product->product_id; ?>

                            </td>

                            <!-- IMAGE -->
                            <td>

                                <img src="img/<?php echo $product->image; ?>" class="product-img">

                            </td>

                            <!-- NAME -->
                            <td>

                                <?php echo htmlspecialchars($product->name); ?>

                            </td>

                            <!-- CATEGORY -->
                            <td>

                                <?php echo htmlspecialchars($product->category_name); ?>

                            </td>

                            <!-- BRAND -->
                            <td>

                                <?php echo htmlspecialchars($product->brand_name); ?>

                            </td>

                            <!-- PRICE -->
                            <td>

                                RM <?php echo number_format($product->price, 2); ?>

                            </td>

                            <!-- STOCK -->
                            <td>

                                <?php echo $product->stock; ?>

                            </td>

                            <!-- CREATED -->
                            <td>

                                <?php echo $product->created_at; ?>

                            </td>

                            <!-- ACTION -->
                            <td>

                                <a href="edit_products.php?id=<?php echo $product->product_id; ?>" class="action-btn edit">
                                    Edit
                                </a>

                                <span class="divider">|</span>

                                <a href="products.php?delete=<?php echo $product->product_id; ?>" class="action-btn delete"
                                    onclick="return confirm('Delete this product?')">
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="9">

                            <div class="empty">

                                No products found.

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

</body>

</html>