<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$sql = "SELECT u.*, s.state_name FROM tbluser u LEFT JOIN tblstate s ON u.state_id = s.state_id ORDER BY user_id DESC";
$query = $dbh->prepare($sql);
$query->execute();

$users = $query->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management | My PC Store</title>
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
        }

        /* Table Scroll Wrapper */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            min-width: 1100px;
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

        .action-btn:hover {
            text-decoration: underline;
        }

        /* Status Badge Styling */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.2);
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
        <a href="users.php" class="sidebar-active">👥 Users</a>
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="sales_report.php">📊 Sales Report</a>
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Users Management</h1>
            <div class="topbar-links">
                <a href="dashboard.php" class="Back"><i class="fa fa-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="table-box">
            <h3>Users List</h3>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 14%;">Full Name</th>
                            <th style="width: 15%;">Email</th>
                            <th style="width: 10%;">Phone</th>
                            <th style="width: 24%;">Address</th>
                            <th style="width: 7%;">Gender</th>
                            <th style="width: 9%;">Status</th>
                            <th style="width: 9%;">Registered</th>
                            <th style="width: 7%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0) { ?>
                            <?php foreach ($users as $user) { ?>
                                <tr>
                                    <td><?= $user->user_id ?></td>
                                    <td><?= htmlspecialchars($user->fullname) ?></td>
                                    <td><?= htmlspecialchars($user->gmail) ?></td>
                                    <td><?= htmlspecialchars($user->phone_num) ?></td>
                                    <td>
                                        <?= htmlspecialchars($user->addr_line1) ?>
                                        <?php if (!empty($user->addr_line2)): ?>
                                            <br><?= htmlspecialchars($user->addr_line2) ?>
                                        <?php endif; ?>
                                        <br>
                                        <span style="color:#888; font-size:0.85rem;">
                                            <?= htmlspecialchars($user->postcode) ?> <?= htmlspecialchars($user->city) ?>,
                                            <?= htmlspecialchars($user->state_name ?? '-') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user->gender) ?></td>
                                    <td>
                                        <?php if (isset($user->status) && strtolower($user->status) === 'active'): ?>
                                            <span class="status-badge status-active"><i class="fa fa-circle"
                                                    style="font-size:0.45rem;"></i> Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive"><i class="fa fa-circle"
                                                    style="font-size:0.45rem;"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user->reg_date)) ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?= $user->user_id ?>" class="action-btn edit">
                                            <i class="fa fa-pen-to-square"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: #888; padding: 25px;">No users available.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>