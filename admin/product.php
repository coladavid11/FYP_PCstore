<?php
session_start();
include('includes/config.php');

if(!isset($_SESSION['admin_login'])){
    header("Location: admin_login.php");
    exit;
}

/* =========================
   FETCH PRODUCTS
========================= */

$sql = "SELECT products.*, categories.category_name
        FROM products
        LEFT JOIN categories
        ON products.category_id = categories.category_id
        ORDER BY product_id DESC";

$query = $dbh->prepare($sql);
$query->execute();

$products = $query->fetchAll(PDO::FETCH_OBJ);

//delete product
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    //GET IMAGE
    $sql = "SELECT image FROM products WHERE product_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();
    $productDelete = $query->fetch(PDO::FETCH_OBJ);

    //DELETE IMAGE
    if($productDelete){
        $imagePath = "../uploads/" . $productDelete->image;
        if(file_exists($imagePath)){
            unlink($imagePath);
        }
    }

    //DELETE DATABASE
    $sql = "DELETE FROM products WHERE product_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();

    //REFRESH PAGE
    header("Location: product.php");
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
   RESET
========================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/* =========================
   BODY
========================= */

body{
    font-family:'Poppins', sans-serif;
    background:#f5f5f5;
}

/* =========================
   CONTAINER
========================= */

.container{
    width:95%;
    max-width:1300px;

    margin:40px auto;

    background:#fff;

    padding:30px;

    border-radius:5px;

    box-shadow:0 5px 20px rgba(0,0,0,0.08);

    border-left:5px solid #ccac3d;
}

/* =========================
   TOP BAR
========================= */

.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:30px;
}

.title{
    font-size:2rem;
    font-weight:600;

    color:#111;
}

/* =========================
   BUTTONS
========================= */

.btn-group{
    display:flex;
    gap:10px;
}

.add-btn,
.back-btn,
.edit-btn,
.delete-btn{
    text-decoration:none;

    padding:10px 16px;

    border-radius:4px;

    font-size:0.9rem;
    font-weight:500;

    transition:0.3s;
}

.add-btn{
    background:#ccac3d;
    color:#fff;
}

.add-btn:hover{
    background:#000;
}

.back-btn{
    background:#eee;
    color:#333;
}

.back-btn:hover{
    background:#000;
    color:#fff;
}

.edit-btn{
    background:#007bff;
    color:#fff;
}

.edit-btn:hover{
    background:#0056b3;
}

.delete-btn{
    background:#dc3545;
    color:#fff;
}

.delete-btn:hover{
    background:#a71d2a;
}

/* =========================
   TABLE
========================= */

.table-box{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#111;
    color:#ccac3d;

    padding:15px;

    text-align:left;

    font-size:0.9rem;
}

table td{
    padding:15px;

    border-bottom:1px solid #eee;

    color:#555;

    vertical-align:middle;
}

/* =========================
   IMAGE
========================= */

.product-img{
    width:70px;
    height:70px;

    object-fit:cover;

    border-radius:5px;

    border:1px solid #ddd;
}

/* =========================
   EMPTY
========================= */

.empty{
    text-align:center;

    padding:40px;

    color:#888;
}

</style>
</head>

<body>

<div class="container">

    <!-- TOP BAR -->
    <div class="top-bar">

        <h1 class="title">
            Products Management
        </h1>

        <div class="btn-group">

            <a href="dashboard.php" class="back-btn">
                Dashboard
            </a>

            <a href="add_products.php" class="add-btn">
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

                <th>Price</th>

                <th>Stock</th>

                <th>Created</th>

                <th>Action</th>

            </tr>

            <?php if($products){ ?>

                <?php foreach($products as $product){ ?>

                    <tr>

                        <!-- ID -->
                        <td>

                            <?php echo $product->product_id; ?>

                        </td>

                        <!-- IMAGE -->
                        <td>

                            <img src="../uploads/<?php echo $product->image; ?>"
                                 class="product-img">

                        </td>

                        <!-- NAME -->
                        <td>

                            <?php echo htmlspecialchars($product->name); ?>

                        </td>

                        <!-- CATEGORY -->
                        <td>

                            <?php echo htmlspecialchars($product->category_name); ?>

                        </td>

                        <!-- PRICE -->
                        <td>

                            RM <?php echo number_format($product->price,2); ?>

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

                            <div class="btn-group">

                                <a href="edit_product.php?id=<?php echo $product->product_id; ?>"
                                   class="edit-btn">

                                   Edit

                                </a>

                                <a href="product.php?delete=<?php echo $product->product_id; ?>"
                                   class="delete-btn"

                                   onclick="return confirm('Delete this product?')">

                                   Delete

                                </a>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>

                    <td colspan="8">

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