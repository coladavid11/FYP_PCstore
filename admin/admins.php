<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SESSION['admin_role'] != 'superadmin') {
    die("Access Denied");
}

$sql = "SELECT * FROM admin WHERE status = 1 ORDER BY admin_id DESC";
$query = $dbh->prepare($sql);
$query->execute();

$admins = $query->fetchAll(PDO::FETCH_OBJ);

/* =========================
   DELETE ADMIN
========================= */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    if ($id == $_SESSION['admin_id']) {

        echo "<script>alert('You cannot deactivate your own account');</script>";

    } else {

        $check = $dbh->prepare("SELECT role FROM admin WHERE admin_id = :id");
        $check->bindParam(':id', $id, PDO::PARAM_INT);
        $check->execute();

        $target = $check->fetch(PDO::FETCH_OBJ);

        if ($target && $target->role == 'superadmin') {

            echo "<script>alert('Cannot deactivate another Super Admin');</script>";

        } else {

            $sql = "UPDATE admin
                    SET status = 0
                    WHERE admin_id = :id";

            $query = $dbh->prepare($sql);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            header("Location: admins.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admins Management | My PC Store</title>
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

        /* Custom Button for Adding Categories */
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

        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="admins.php" class="sidebar-active">⚙ Admin</a>

    </div>

    <div class="main">

        <div class="topbar">
            <h1>Admin Management</h1>
            <div class="topbar-links">
                <a href="dashboard.php" class="Back"><i class="fa fa-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="table-box">

            <h3>Admin List</h3>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">ID</th>
                        <th style="width: 15%;">Full Name</th>
                        <th style="width: 15%;">Email</th>
                        <th style="width: 15%;">Role</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Registered</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($admins) > 0) { ?>
                        <?php foreach ($admins as $admin) { ?>
                            <tr>
                                <td><?= $admin->admin_id ?></td>
                                <td><?= htmlspecialchars($admin->fullname) ?></td>
                                <td><?= htmlspecialchars($admin->email) ?></td>
                                <td><?= htmlspecialchars($admin->role) ?></td>
                                <td><?php if ($admin->status == 1) { ?>
                                        <span style="color:green;font-weight:bold;">
                                            Active
                                        </span>
                                    <?php } else { ?>
                                        <span style="color:red;font-weight:bold;">
                                            Inactive
                                        </span>
                                    <?php } ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($admin->created_at)) ?></td>
                                <td>
                                    <a href="admins.php?delete=<?= $admin->admin_id ?>" class="action-btn delete"
                                        onclick="return confirm('Are you sure you want to delete this admin?')">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #888; padding: 25px;">No admins available.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>

    </div>

</body>

</html>