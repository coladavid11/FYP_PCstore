<?php 
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])){
    header("Location: admin_login.php");
    exit;
}

//CHECK PRODUCT
if(!isset($_GET['id'])){
    header("Location: products.php");
    exit;
}

$id = intval($_GET['id']);

//FETCH CATEGORIES
$categorySql = "SELECT * FROM categories ORDER BY category_name ASC";
$categoryQuery = $dbh->prepare($categorySql);
$categoryQuery->execute();
$categories = $categoryQuery->fetchAll(PDO::FETCH_OBJ);

//FETCH PRODUCT
$sql = "SELECT * FROM products WHERE product_id = :id";
$query = $dbh->prepare($sql);
$query->bindParam(':id', $id, PDO::PARAM_INT);
$query->execute();
$product = $query->fetch(PDO::FETCH_OBJ);

if(!$product){
    header("Location: products.php");
    exit;
}

//UPDATE PRODUCT
$msg= "";
if(isset($_POST['update_product'])){
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    //DEFAULT OLD IMAGE
    $image = $product->image;

    //CHECK NEW IMAGE
    if(!empty($_FILES['image']['name'])){
        $newImage = $_FILES['image']['name'];

        //DELETE OLD IMAGE
        $oldImagePath = "img/" . $product->image;

        if(file_exists($oldImagePath)){
            unlink($oldImagePath);
        }

        //UPLOAD NEW IMAGE
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $newImage);
        $image = $newImage;
    }

    //UPDATE QUERY
    $update = "UPDATE products SET
                name = :name,
                category_id = :category_id,
                price = :price,
                stock = :stock,
                description = :description,
                image = :image
                WHERE product_id = :id";

    $updateQuery = $dbh->prepare($update);
    $updateQuery->bindParam(':name', $name,PDO::PARAM_STR);
    $updateQuery->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $updateQuery->bindParam(':price', $price, PDO::PARAM_STR);
    $updateQuery->bindParam(':stock', $stock, PDO::PARAM_INT);
    $updateQuery->bindParam(':description', $description, PDO::PARAM_STR);
    $updateQuery->bindParam(':image', $image, PDO::PARAM_STR);
    $updateQuery->bindParam(':id', $id, PDO::PARAM_INT);

    if($updateQuery->execute()){
        $msg = "Product updated successfully.";

        //REFRESH PRODUCT DATA
        $query->execute();
        $product = $query->fetch(PDO::FETCH_OBJ);
    }
    else{
        $msg = "Something went wrong.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>

    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }

    body{
        font-family:'Poppins', sans-serif;
        background:#f5f5f5;
    }

    /* CONTAINER */

    .container{
        width:100%;
        max-width:700px;

        margin:50px auto;

        background:#fff;

        padding:40px;

        border-radius:5px;

        box-shadow:0 5px 20px rgba(0,0,0,0.08);

        border-left:5px solid #ccac3d;
    }

    /* TOP BAR */

    .top-bar{
        display:flex;
        justify-content:space-between;
        align-items:center;

        margin-bottom:25px;
    }

    /* TITLE */

    .title{
        font-size:2rem;
        font-weight:600;

        color:#111;
    }

    /* FORM */

    .form-group{
        margin-bottom:20px;
    }

    label{
        display:block;

        margin-bottom:8px;

        color:#ccac3d;

        font-size:0.9rem;
        font-weight:600;
    }

    input,
    textarea,
    select{
        width:100%;

        padding:12px;

        border:1px solid #ddd;

        border-radius:4px;

        font-family:'Poppins', sans-serif;

        background:#fafafa;
    }

    input:focus,
    textarea:focus,
    select:focus{
        outline:none;

        border-color:#ccac3d;

        background:#fff;
    }

    textarea{
        resize:none;
    }

    /* CURRENT IMAGE */

    .current-image{
        width:120px;
        height:120px;

        object-fit:cover;

        border-radius:5px;

        border:1px solid #ddd;

        margin-top:10px;
    }

    /* BUTTON */

    .btn-group{
        display:flex;
        gap:10px;

        margin-top:20px;
    }

    .update-btn,
    .back-btn{
        flex:1;

        text-align:center;

        text-decoration:none;

        padding:14px;

        border:none;

        border-radius:4px;

        font-size:1rem;
        font-weight:600;

        transition:0.3s;

        cursor:pointer;
    }

    .update-btn{
        background:#ccac3d;
        color:#fff;
    }

    .update-btn:hover{
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

    /* ALERT */

    .success{
        background:#d4edda;
        color:#155724;

        padding:12px;

        margin-bottom:20px;

        border-radius:4px;
    }

    .error{
        background:#f8d7da;
        color:#721c24;

        padding:12px;

        margin-bottom:20px;

        border-radius:4px;
    }

    </style>
    
</head>
<body>
    <div class="container">
        <h1 class="title">
            Edit Product
        </h1>

        <?php if($msg){ ?>
    <div class="success">
        <?php echo $msg; ?>
    </div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data">

    <!-- PRODUCT NAME -->
    <div class="form-group">

        <label>Product Name</label>

        <input type="text"
               name="name"
               value="<?php echo htmlspecialchars($product->name); ?>"
               required>

    </div>

    <!-- DESCRIPTION -->
    <div class="form-group">

        <label>Description</label>

        <textarea name="description"
                  rows="5"><?php echo htmlspecialchars($product->description); ?></textarea>

    </div>

    <!-- PRICE -->
    <div class="form-group">

        <label>Price(RM)</label>

        <input type="number"
               step="0.01"
               min="0"
               name="price"
               value="<?php echo $product->price; ?>"
               required>

    </div>

       <!-- STOCK -->
    <div class="form-group">

        <label>Stock Quantity</label>

        <input type="number"
               min="0"
               name="stock"
               value="<?php echo $product->stock; ?>"
               required>

    </div>

    <!-- CATEGORY -->
    <div class="form-group">

        <label>Category</label>

        <select name="category_id" required>

            <?php foreach($categories as $category){ ?>

                <option value="<?php echo $category->category_id; ?>"

                    <?php
                    if($product->category_id == $category->category_id){
                        echo "selected";
                    }
                    ?>

                >

                    <?php echo htmlspecialchars($category->category_name); ?>

                </option>

            <?php } ?>

        </select>

    </div>

    <!-- CURRENT IMAGE -->
    <div class="form-group">

        <label>Current Image</label>

        <img src="img/<?php echo $product->image; ?>"
             class="current-image">

    </div>

    <!-- NEW IMAGE -->
    <div class="form-group">

        <label>Upload New Image</label>

        <input type="file" name="image">

    </div>

    <!-- BUTTON -->
    <div class="btn-group">

        <button type="submit"
                name="update_product"
                class="update-btn">

            Update Product

        </button>

        <a href="products.php"
           class="back-btn">

            Back

        </a>

    </div>

</form>
</div>

</body>
</html>
