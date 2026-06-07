<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$msg = "";
$error = "";

/* =========================
   FETCH CATEGORIES
========================= */

$sql = "SELECT * FROM categories WHERE status = 1";

$query = $dbh->prepare($sql);

$query->execute();

$categories = $query->fetchAll(PDO::FETCH_OBJ);

/* =========================
   FETCH BRANDS

========================= */

$sql = "SELECT * FROM tblbrand ORDER BY brand_id ASC";

$query = $dbh->prepare($sql);

$query->execute();

$brands = $query->fetchAll(PDO::FETCH_OBJ);

/* =========================
   ADD PRODUCT
========================= */

if (isset($_POST['add_product'])) {

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'];
    /* =========================
       IMAGE
    ========================= */

    $image = str_replace(' ', '_', $_FILES['image']['name']);

    $tmp_name = $_FILES['image']['tmp_name'];

    // IMAGE EXTENSION
    $imageExtension = strtolower(
        pathinfo($image, PATHINFO_EXTENSION)
    );

    // ALLOWED TYPES
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    // GENERATE UNIQUE IMAGE NAME
    $newImageName = time() . "_" . $image;

    // UPLOAD PATH
    $uploadPath = "img/" . $newImageName;

    /* =========================
       VALIDATION
    ========================= */

    if ($name == "" || $price === "" || $stock === "") {

        $error = "Please fill in all required fields.";

    } elseif ($price < 0) {

        $error = "Price cannot be negative.";

    } elseif ($stock < 0) {

        $error = "Stock cannot be negative.";

    } elseif (!in_array($imageExtension, $allowedExtensions)) {

        $error = "Only JPG, JPEG, PNG and WEBP files are allowed.";

    } else {

        /* =========================
           CREATE IMG FOLDER
        ========================= */

        if (!file_exists("img")) {

            mkdir("img", 0777, true);
        }

        /* =========================
           UPLOAD IMAGE
        ========================= */

        if (move_uploaded_file($tmp_name, $uploadPath)) {

            /* =========================
               INSERT PRODUCT
            ========================= */

            $sql = "INSERT INTO products
                    (name, description, price, stock, category_id, brand_id, image)
                    VALUES
                    (:name, :description, :price, :stock, :category_id, :brand_id, :image)";

            $query = $dbh->prepare($sql);

            $query->bindParam(':name', $name, PDO::PARAM_STR);

            $query->bindParam(':description', $description, PDO::PARAM_STR);

            $query->bindParam(':price', $price, PDO::PARAM_STR);

            $query->bindParam(':stock', $stock, PDO::PARAM_INT);

            $query->bindParam(':category_id', $category_id, PDO::PARAM_INT);

            $query->bindParam(':brand_id', $brand_id, PDO::PARAM_INT);

            $query->bindParam(':image', $newImageName, PDO::PARAM_STR);

            if ($query->execute()) {

                $msg = "Product added successfully!";

            } else {

                $error = "Database insert failed.";
            }

        } else {

            $error = "Failed to upload image.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* =========================
   RESET
========================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* =========================
   BODY
========================= */

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }

        /* =========================
   CONTAINER
========================= */

        .container {

            width: 100%;
            max-width: 700px;

            margin: 50px auto;

            background: #fff;

            padding: 40px;

            border-radius: 5px;

            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);

            border-left: 5px solid #ccac3d;
        }

        /* =========================
   TOP BAR
========================= */

        .top-bar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }

        /* =========================
   TITLE
========================= */

        .title {

            font-size: 2rem;

            font-weight: 600;

            color: #111;
        }

        /* =========================
   BACK BUTTON
========================= */

        .back-btn {

            text-decoration: none;

            background: #eee;

            color: #333;

            padding: 10px 15px;

            border-radius: 4px;

            transition: 0.3s;
        }

        .back-btn:hover {

            background: #000;

            color: #fff;
        }

        /* =========================
   FORM GROUP
========================= */

        .form-group {

            margin-bottom: 20px;
        }

        label {

            display: block;

            margin-bottom: 8px;

            color: #ccac3d;

            font-size: 0.9rem;

            font-weight: 600;
        }

        input,
        textarea,
        select {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 4px;

            font-family: 'Poppins', sans-serif;

            background: #fafafa;
        }

        input:focus,
        textarea:focus,
        select:focus {

            outline: none;

            border-color: #ccac3d;

            background: #fff;
        }

        textarea {

            resize: none;

            height: 120px;
        }

        /* =========================
   BUTTON
========================= */

        .btn {

            width: 100%;

            background: #ccac3d;

            color: #fff;

            border: none;

            padding: 14px;

            font-size: 1rem;

            font-weight: 600;

            border-radius: 4px;

            cursor: pointer;

            transition: 0.3s;
        }

        .btn:hover {

            background: #000;
        }

        /* =========================
   ALERTS
========================= */

        .success {

            background: #d4edda;

            color: #155724;

            padding: 12px;

            margin-bottom: 20px;

            border-radius: 4px;
        }

        .error {

            background: #f8d7da;

            color: #721c24;

            padding: 12px;

            margin-bottom: 20px;

            border-radius: 4px;
        }
    </style>

</head>

<body>

    <div class="container">

        <!-- TOP BAR -->
        <div class="top-bar">

            <h1 class="title">
                Add Product
            </h1>

            <a href="products.php" class="back-btn">
                Back
            </a>

        </div>

        <!-- SUCCESS -->
        <?php if ($msg) { ?>

            <div class="success">

                <?php echo $msg; ?>

            </div>

        <?php } ?>

        <!-- ERROR -->
        <?php if ($error) { ?>

            <div class="error">

                <?php echo $error; ?>

            </div>

        <?php } ?>

        <!-- FORM -->
        <form method="POST" enctype="multipart/form-data">

            <!-- PRODUCT NAME -->
            <div class="form-group">

                <label>
                    Product Name
                </label>

                <input type="text" name="name" placeholder="Enter product name" required>

            </div>

            <!-- DESCRIPTION -->
            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea name="description" placeholder="Enter product description"></textarea>

            </div>

            <!-- PRICE -->
            <div class="form-group">

                <label>
                    Price (RM)
                </label>

                <input type="number" step="0.01" min="0" name="price" placeholder="Enter price" required>

            </div>

            <!-- STOCK -->
            <div class="form-group">

                <label>
                    Stock Quantity
                </label>

                <input type="number" min="0" name="stock" placeholder="Enter stock quantity" required>

            </div>

            <!-- CATEGORY -->
            <div class="form-group">

                <label>
                    Category
                </label>

                <select name="category_id" required>

                    <option value="">
                        Select Category
                    </option>

                    <?php foreach ($categories as $category) { ?>

                        <option value="<?php echo $category->category_id; ?>">

                            <?php echo htmlspecialchars($category->category_name); ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <!-- BRAND -->
            <div class="form-group">

                <label>
                    Brand
                </label>

                <select name="brand_id" required>

                    <option value="">
                        Select Brand
                    </option>

                    <?php foreach ($brands as $brand) { ?>

                        <option value="<?php echo $brand->brand_id; ?>">

                            <?php echo htmlspecialchars($brand->brand_name); ?>

                        </option>

                    <?php } ?>

                </select>

            </div>

            <!-- IMAGE -->
            <div class="form-group">

                <label>
                    Product Image
                </label>

                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>

            </div>

            <!-- BUTTON -->
            <button type="submit" name="add_product" class="btn">

                Add Product

            </button>

        </form>

    </div>

</body>

</html>