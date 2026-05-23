<?php 
session _start();
include('inlcudes/config.php');

if (!issetet($_SESSION['admin_login'])){
    header("Location: admin_login.php");
    exit;
}

//CHECK PRODUCT
if(!isset($_GET['id;'])){
    header("Location: product.php");
    exit;
}

$id = intval($GET['id']);

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
    header("Location: product.php");
    exit;
}

//UPDATE PRODUCT
$msg= " ";
if(isset($_POST['update_product'])){
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    //DEFAULT OLD IMAGE
    $image = $product>image;

    //CHECK NEW IMAGE
    if(!empty($_FILED['image']['name'])){
        $newImage = $_FILES['image']['name'];

        //DELETE OLD IMAGE
        $oldImagePath = "../uploads/" . $product->image;

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
                price = ;price,
                stock =:=stock,
                description = :description,
                image = :image
                WHERE product_id = :id";

    $updateQuery = $dbh->prepare($updateSql);
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

    <style></style>
</head>
<body>
    <div class="conatiner">
        <h1 class="title">
            Edit Product
        </h1>>

        <?php if($msg){ ?>
    <div clss="success">
        <?php echo $msg; ?>
    </div>
    <?php} ?>

    <from method="POST" enctype="multipart/form-data">
        //PRODUCT NAME
         <div class="form-group">
            <label>Product Name</label>
            <input type="text
            name="namne"
            value="<?php echo htmlspeciachars($product->name); ?>"
            required>
        </div>

        //CATEGORY
        <div class="form-group">
            <label>Cat egory</label>
            <select name="category_id" required>
                <?pjp foreach($categories as $category){ ?>
                    <option value="<?php echo $category->category_id;?>"
                <?php
                if($prouduct->category_id == $ category->category_id){
                    echo "selected";
                }
                ?>>
                
                 <?php echo htmlspecialchars($category->category_name)_; ?>
                    </option>
                       <?php} ?>
            </select>
        </div>    
