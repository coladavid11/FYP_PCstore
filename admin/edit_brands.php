<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

// CHECK ID
if (!isset($_GET['id'])) {
    header("Location: brands.php");
    exit;
}

$id = intval($_GET['id']);

// FETCH BRAND
$sql = "SELECT * FROM tblbrand WHERE brand_id = :id";

$query = $dbh->prepare($sql);

$query->bindParam(':id', $id, PDO::PARAM_INT);

$query->execute();

$brand = $query->fetch(PDO::FETCH_OBJ);

if (!$brand) {
    header("Location: brands.php");
    exit;
}

$msg = "";

// UPDATE BRAND
if (isset($_POST['update'])) {

    $name = trim($_POST['brand_name']);

    $updateSql = "UPDATE tblbrand
                  SET brand_name = :name
                  WHERE brand_id = :id";

    $updateQuery = $dbh->prepare($updateSql);

    $updateQuery->bindParam(':name', $name, PDO::PARAM_STR);
    $updateQuery->bindParam(':id', $id, PDO::PARAM_INT);

    if ($updateQuery->execute()) {

        $msg = "Brand updated successfully.";

        // REFRESH DATA
        $query->execute();
        $brand = $query->fetch(PDO::FETCH_OBJ);

    } else {

        $msg = "Something went wrong.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Brand</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f5f5;
        }

        .container {
            width: 100%;
            max-width: 600px;

            margin: 50px auto;

            background: #fff;

            padding: 40px;

            border-radius: 5px;

            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);

            border-left: 5px solid #ccac3d;
        }

        .title {
            font-size: 2rem;
            font-weight: 600;

            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;

            margin-bottom: 8px;

            color: #ccac3d;

            font-weight: 600;
        }

        input {
            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 4px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .update-btn,
        .back-btn {
            flex: 1;

            text-align: center;

            text-decoration: none;

            padding: 12px;

            border: none;

            border-radius: 4px;

            font-weight: 600;

            transition: 0.3s;
        }

        .update-btn {
            background: #ccac3d;
            color: #fff;
        }

        .update-btn:hover {
            background: #000;
        }

        .back-btn {
            background: #eee;
            color: #333;
        }

        .back-btn:hover {
            background: #000;
            color: #fff;
        }

        .success {
            background: #d4edda;
            color: #155724;

            padding: 12px;

            border-radius: 4px;

            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1 class="title">
            Edit Brand
        </h1>

        <?php if ($msg) { ?>

            <div class="success">
                <?php echo $msg; ?>
            </div>

        <?php } ?>

        <form method="POST">

            <div class="form-group">

                <label>Brand Name</label>

                <input type="text" name="brand_name" value="<?php echo htmlspecialchars($brand->brand_name); ?>"
                    required>

            </div>

            <div class="btn-group">

                <button type="submit" name="update" class="update-btn">

                    Update Brand

                </button>

                <a href="brands.php" class="back-btn">

                    Back

                </a>

            </div>

        </form>

    </div>

</body>

</html>