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

$msg = "";
$error = "";

if (isset($_POST['submit'])) {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    }
    // 2. Password Length Check (>= 8 characters)
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // 3. Email Duplicate Check
        $check = $dbh->prepare("SELECT admin_id FROM admin WHERE email = :email");
        $check->bindParam(':email', $email, PDO::PARAM_STR);
        $check->execute();

        if ($check->rowCount() > 0) {
            $error = "This email is already registered.";
        } else {
            // Password Hashing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // FIXED: Changed :hashed_password to :password to match bindParam precisely
            $sql = "INSERT INTO admin (fullname, email, phone, password, role, status) 
                    VALUES (:fullname, :email, :phone, :password, :role, 1)";

            $query = $dbh->prepare($sql);
            $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->bindParam(':phone', $phone, PDO::PARAM_STR);
            $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $query->bindParam(':role', $role, PDO::PARAM_STR);

            if ($query->execute()) {
                $msg = "Admin account created successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Admin | My PC Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* =========================
            GENERAL RESET & BASIS
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
           SIDEBAR LAYOUT
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
            MAIN LAYOUT CONTAINER
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

        .Back {
            text-decoration: none;
            color: #d4af37;
            font-weight: 500;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.95rem;
        }

        .Back:hover {
            opacity: 0.8;
        }

        /* =========================
            FORM BOX STRUCTURING
         ========================= */
        .form-box {
            background: #fff;
            padding: 35px;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        .form-box h3 {
            margin-bottom: 25px;
            font-size: 1.25rem;
            color: #111;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 22px;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #ccac3d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #fff;
            color: #333;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ccac3d;
            box-shadow: 0 0 0 3px rgba(204, 172, 61, 0.1);
        }

        /* =========================
           ACTION SUBMIT BUTTON
        ========================= */
        .btn-save {
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 13px 28px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: #d4af37;
            color: #000;
        }

        /* =========================
           NOTIFICATION ALERT BANNERS
        ========================= */
        .success {
            background-color: #e2f5ea;
            color: #0b5931;
            border: 1px solid #c3ebd4;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error {
            background-color: #fdf2f2;
            color: #9b1c1c;
            border: 1px solid #fbd5d5;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
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
        <a href="admins.php" class="sidebar-active">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Add Admin</h1>
            <a href="admins.php" class="Back"><i class="fa-solid fa-arrow-left"></i>Back</a>
        </div>

        <div class="form-box">
            <h3>Create New Admin Account</h3>

            <?php if ($msg) { ?>
                <div class="success">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php } ?>

            <?php if ($error) { ?>
                <div class="error">
                    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <form method="POST">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="e.g., John Doe" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="e.g., admin@domain.com" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="e.g., +60123456789" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="At least 8 characters" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat your password" required>
                </div>

                <div class="form-group">
                    <label>Account Role Setting</label>
                    <select name="role">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn-save">
                    <i class="fa-solid fa-floppy-disk"></i> Add Admin Account
                </button>

            </form>
        </div>

    </div>

</body>

</html>