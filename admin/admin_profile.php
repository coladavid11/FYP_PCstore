<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("location:admin_login.php");
    exit();
}

$email = $_SESSION['admin_login'];

$sql = "SELECT * FROM admin WHERE email = :email";
$query = $dbh->prepare($sql);
$query->bindParam(':email', $email, PDO::PARAM_STR);
$query->execute();

$result = $query->fetch(PDO::FETCH_OBJ);

if (!$result) {
    die("Admin not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | My PC Store</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
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
            transition: .3s;
        }

        .sidebar a:hover {
            background: #d4af37;
            color: #000;
        }

        /* =========================
           MAIN
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

            background: #fff;
            padding: 15px 25px;

            border-radius: 4px;

            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);

            margin-bottom: 25px;
        }

        .topbar h1 {
            font-size: 1.8rem;
            color: #111;
        }

        .back-btn {
            color: #d4af37;
            text-decoration: none;
            font-weight: 500;
        }

        /* =========================
           PROFILE CARD
        ========================= */

        .profile-card {
            background: #fff;
            border-radius: 4px;

            box-shadow: 0 5px 15px rgba(0, 0, 0, .05);

            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            color: #111;
            font-size: 1.2rem;
        }

        .card-body {
            padding: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 16px 0;

            border-bottom: 1px solid #f1f1f1;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #777;
            font-weight: 500;
        }

        .info-value {
            color: #111;
            font-weight: 600;
        }

        /* =========================
           BADGES
        ========================= */

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-super {
            background: #fff3cd;
            color: #856404;
        }

        .badge-admin {
            background: #e2e3e5;
            color: #383d41;
        }

        /* =========================
           FOOTER
        ========================= */

        .card-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
        }

        .edit-btn {
            display: inline-block;

            background: #000;
            color: #d4af37;

            border: 1px solid #d4af37;

            padding: 10px 20px;

            text-decoration: none;

            border-radius: 4px;

            transition: .3s;
        }

        .edit-btn:hover {
            background: #d4af37;
            color: #000;
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
        <a href="sales_report.php">📊 Sales Report</a>
        <a href="admins.php">⚙ Admin</a>

    </div>

    <div class="main">

        <div class="topbar">

            <h1>Admin Profile</h1>

            <a href="dashboard.php" class="back-btn">
                ← Back
            </a>

        </div>

        <div class="profile-card">

            <div class="card-header">
                <h2>Profile Information</h2>
            </div>

            <div class="card-body">

                <div class="info-row">
                    <span class="info-label">Admin ID</span>
                    <span class="info-value">
                        #<?= $result->admin_id ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value">
                        <?= htmlspecialchars($result->fullname) ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Email Address</span>
                    <span class="info-value">
                        <?= htmlspecialchars($result->email) ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Phone Number</span>
                    <span class="info-value">
                        <?= htmlspecialchars($result->phone ?: '-') ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Role</span>

                    <?php if ($result->role == 'superadmin') { ?>
                        <span class="badge badge-super">
                            Super Admin
                        </span>
                    <?php } else { ?>
                        <span class="badge badge-admin">
                            Admin
                        </span>
                    <?php } ?>

                </div>

                <div class="info-row">
                    <span class="info-label">Status</span>

                    <?php if ($result->status == 1) { ?>
                        <span class="badge badge-active">
                            Active
                        </span>
                    <?php } else { ?>
                        <span class="badge badge-inactive">
                            Inactive
                        </span>
                    <?php } ?>

                </div>

                <div class="info-row">
                    <span class="info-label">Created At</span>
                    <span class="info-value">
                        <?= date('d M Y', strtotime($result->created_at)) ?>
                    </span>
                </div>

            </div>

            <div class="card-footer">

                <a href="edit_profile.php" class="edit-btn">
                    Edit Profile
                </a>

            </div>

        </div>

    </div>

</body>

</html>