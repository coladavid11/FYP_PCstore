<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SESSION['admin_role'] != 'superadmin') {
    $_SESSION['error'] = "Only Super Admin can access Admin Management.";
    header("Location: dashboard.php");
    exit;
}

// Fixed logic to retrieve both active and inactive administrators so they render correctly in management
$sql = "SELECT * FROM admin ORDER BY admin_id DESC";
$query = $dbh->prepare($sql);
$query->execute();

$admins = $query->fetchAll(PDO::FETCH_OBJ);

/* =========================
   DELETE / DEACTIVATE ADMIN
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
            left: 0;
            top: 0;
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
            MAIN CONTENT AREA
         ========================= */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        /* =========================
            TOPBAR CONTAINER
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
            gap: 25px;
            align-items: center;
        }

        .Back {
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            color: #d4af37;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .Back:hover {
            opacity: 0.8;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        /* =========================
            TABLE DATA CONTAINER
         ========================= */
        .table-box {
            background: #fff;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            width: 100%;
        }

        .table-box h3 {
            margin-bottom: 25px;
            color: #111;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Table Architecture & Rebalanced Spacing Layout */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th,
        table td {
            padding: 16px 14px;
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

        /* =========================
           STATUS PILL BADGES
        ========================= */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Active Badge Layout (Green) */
        .badge-status.active {
            background-color: #e2f5ea;
            color: #0b5931;
            border: 1px solid #c3ebd4;
        }

        .badge-status.active::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #0b5931;
            border-radius: 50%;
        }

        /* Inactive Badge Layout (Gray) */
        .badge-status.inactive {
            background-color: #f2f2f2;
            color: #616161;
            border: 1px solid #e0e0e0;
        }

        .badge-status.inactive::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #757575;
            border-radius: 50%;
        }

        /* =========================
           ACTION BUTTON LINKS
        ========================= */
        .action-btn {
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="admins.php" class="sidebar-active">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Admin Management</h1>
            <div class="topbar-links">
                <a href="dashboard.php" class="Back"><i class="fa-solid fa-arrow-left"></i>Back</a>
                <a href="add_admin.php" class="btn-add"><i class="fa fa-plus"></i>Add Admin</a>
            </div>
        </div>

        <div class="table-box">

            <h3>Admin List</h3>

            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 20%;">Full Name</th>
                        <th style="width: 25%;">Email</th>
                        <th style="width: 15%;">Role</th>
                        <th style="width: 14%;">Status</th>
                        <th style="width: 10%;">Registered</th>
                        <th style="width: 8%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($admins) > 0) { ?>
                        <?php foreach ($admins as $admin) { ?>
                            <tr>
                                <td>#<?= $admin->admin_id ?></td>
                                <td><?= htmlspecialchars($admin->fullname) ?></td>
                                <td><?= htmlspecialchars($admin->email) ?></td>
                                <td><?= htmlspecialchars($admin->role) ?></td>
                                <td>
                                    <?php if ($admin->status == 1) { ?>
                                        <span class="badge-status active">Active</span>
                                    <?php } else { ?>
                                        <span class="badge-status inactive">Inactive</span>
                                    <?php } ?>
                                </td>
                                <td><?= date('d M Y', strtotime($admin->created_at)) ?></td>
                                <td>
                                    <a href="edit_admin.php?id=<?= $admin->admin_id ?>" class="action-btn edit">
                                        <i class="fa fa-edit"></i>Edit
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #888; padding: 35px;">No admins available.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>

    </div>

</body>

</html>