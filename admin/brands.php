<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$sql = "SELECT * FROM tblbrand ORDER BY brand_id DESC";
$query = $dbh->prepare($sql);
$query->execute();

$brands = $query->fetchAll(PDO::FETCH_OBJ);

/* =========================
   DELETE CATEGORY
========================= */

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // CHECK PRODUCT USING CATEGORY

    $checkSql = "SELECT * FROM products WHERE brand_id = :id";
    $checkQuery = $dbh->prepare($checkSql);
    $checkQuery->bindParam(':id', $id, PDO::PARAM_INT);
    $checkQuery->execute();

    if ($checkQuery->rowCount() > 0) {
        echo "<script>alert('Cannot delete brand. Products are using this brand.');</script>";
    } else {
        $sql = "DELETE FROM tblbrand WHERE brand_id = :id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        header("Location: brands.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands Management | My PC Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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

        /* Highlight Active Menu Item */
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

        /* Custom Button for Adding Brands */
        .btn-add {
            display: inline-block;
            background: #000;
            color: #d4af37;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 25px;
            border: 1px solid #d4af37;
            transition: 0.3s;
        }

        .btn-add:hover {
            background: #d4af37;
            color: #000;
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
            /* Subtle hover highlight for rows */
        }

        /* Inline Action Link Adjustments */
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
    <a href="brands.php" class="active">Brands</a>
</div>

    <div class="main">

        <div class="topbar">
            <h1>Brands</h1>
            <div class="topbar-links">
                <a href="dashboard.php" class="Back"><i class="fa fa-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="table-box">

            <h3>Brands List</h3>

            <a href="add_brands.php" class="btn-add">+ Add Brand</a>

            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">ID</th>
                        <th style="width: 60%;">Brand Name</th>
                        <th style="width: 25%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($brands) > 0) { ?>
                        <?php foreach ($brands as $brand) { ?>
                            <tr>
                                <td><?= $brand->brand_id ?></td>
                                <td><?= htmlspecialchars($brand->brand_name) ?></td>
                                <td>
                                    <a href="edit_brands.php?id=<?= $brand->brand_id ?>" class="action-btn edit"><i
                                            class="fa fa-pen-to-square"></i>Edit</a>
                                    <span class="divider">|</span>
                                    <a href="brands.php?delete=<?php echo $brand->brand_id; ?>" class="action-btn delete"
                                        onclick="return confirm('Are you sure you want to delete this brand?')">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #888; padding: 25px;">No brands available.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>

    </div>

</body>

</html>