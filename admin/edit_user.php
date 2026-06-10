<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$msg = "";
$error = "";

// Check if User ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    header("Location: users.php");
    exit;
}

/* ====================================
    UPDATE USER STATUS PROCESSOR
==================================== */
if (isset($_POST['update_status'])) {
    $status = $_POST['status'];

    $sql = "UPDATE tbluser SET status = :status WHERE user_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':status', $status, PDO::PARAM_STR);
    $query->bindParam(':id', $id, PDO::PARAM_INT);

    if ($query->execute()) {
        $msg = "User status updated successfully!";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

// Fetch the existing details of the target user
$fetchSql = "SELECT * FROM tbluser WHERE user_id = :id";
$fetchQuery = $dbh->prepare($fetchSql);
$fetchQuery->bindParam(':id', $id, PDO::PARAM_INT);
$fetchQuery->execute();
$user = $fetchQuery->fetch(PDO::FETCH_OBJ);

if (!$user) {
    header("Location: users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Status | Admin Suite</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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

        /* ── SIDEBAR ── */
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

        .sidebar a:hover, .sidebar a.sidebar-active {
            background: #d4af37;
            color: #000;
        }

        /* ── MAIN CONTENT ── */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        /* ── TOPBAR ── */
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

        .Back {
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            color: #d4af37;
            font-size: 0.95rem;
        }

        /* ── BOX CONTAINER ── */
        .form-box {
            background: #fff;
            padding: 35px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            font-size: 0.9rem;
        }

        .info-value {
            padding: 10px 12px;
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 4px;
            color: #666;
            font-size: 0.95rem;
        }

        .status-select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
            color: #444;
            background: #fff;
            cursor: pointer;
            outline: none;
            transition: 0.2s;
        }

        .status-select:focus {
            border-color: #d4af37;
        }

        .btn-submit {
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 12px 24px;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #d4af37;
            color: #000;
        }

        /* Alert Banners */
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">
        <div class="topbar">
            <h1>Change User Account Status</h1>
            <a href="users.php" class="Back"><i class="fa fa-arrow-left"></i> Back to Users</a>
        </div>

        <div class="form-box">
            <?php if ($msg) { ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $msg ?></div>
            <?php } if ($error) { ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> <?= $error ?></div>
            <?php } ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="info-value"><?= htmlspecialchars($user->fullname) ?></div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="info-value"><?= htmlspecialchars($user->gmail) ?></div>
                </div>

                <div class="form-group">
                    <label>Account Status Selection</label>
                    <select name="status" class="status-select">
                        <option value="Active" <?= (isset($user->status) && strtolower($user->status) === 'active') ? 'selected' : '' ?>>🟢 Active</option>
                        <option value="Inactive" <?= (isset($user->status) && strtolower($user->status) === 'inactive') ? 'selected' : '' ?>>🔴 Inactive</option>
                    </select>
                </div>

                <button type="submit" name="update_status" class="btn-submit">
                    <i class="fa fa-save"></i> Save Status Change
                </button>
            </form>
        </div>
    </div>

</body>
</html>