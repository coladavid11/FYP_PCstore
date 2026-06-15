<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);

// Fetches all columns including 'phone'
$sql = "SELECT * FROM admin WHERE admin_id = :id";
$query = $dbh->prepare($sql);
$query->bindParam(':id', $id, PDO::PARAM_INT);
$query->execute();

$admin = $query->fetch(PDO::FETCH_OBJ);

if (!$admin) {
    die("Admin not found");
}

/* =========================
   UPDATE ADMIN
========================= */

if (isset($_POST['update'])) {

    // Read-only fields (fullname, email, phone) are completely omitted here
    $role = $_POST['role'];
    $status = intval($_POST['status']);
    $password = trim($_POST['password']);

    // Update without password (Only updates role and status)
    if (empty($password)) {

        $sql = "UPDATE admin 
                SET role = :role, 
                    status = :status 
                WHERE admin_id = :id";

        $query = $dbh->prepare($sql);

        $query->bindParam(':role', $role);
        $query->bindParam(':status', $status);
        $query->bindParam(':id', $id);

        $query->execute();

    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE admin 
                SET password = :password, 
                    role = :role, 
                    status = :status 
                WHERE admin_id = :id";

        $query = $dbh->prepare($sql);

        $query->bindParam(':password', $hashedPassword);
        $query->bindParam(':role', $role);
        $query->bindParam(':status', $status);
        $query->bindParam(':id', $id);

        $query->execute();
    }

    echo "<script>
            alert('Admin updated successfully');
            window.location='admins.php';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Admin</title>

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

        .sidebar a.sidebar-active {
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

        .Back:hover {
            opacity: 0.7;
        }

        /* =========================
   FORM BOX
========================= */
        .form-box {
            background: #fff;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            max-width: 800px;
        }

        .form-box h3 {
            margin-bottom: 25px;
            color: #111;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* =========================
   FORM ELEMENTS
========================= */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
            background: #fff;
            transition: 0.3s;
        }

        /* Styling for read-only fields */
        .form-group input[readonly] {
            background-color: #f9f9f9;
            color: #777;
            cursor: not-allowed;
            border-color: #e4e4e4;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #d4af37;
            outline: none;
            box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
        }

        /* Remove focus glow effect for read-only fields */
        .form-group input[readonly]:focus {
            border-color: #e4e4e4;
            box-shadow: none;
        }

        /* =========================
   BUTTONS
========================= */
        .btn-submit {
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #d4af37;
            color: #000;
        }

        /* =========================
   ALERT MESSAGE
========================= */
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
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
            <h1>Edit Admin</h1>
            <div class="topbar-links">
                <a href="admins.php" class="Back">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="form-box">
            <h3>Edit Admin Information</h3>

            <form method="POST">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($admin->fullname) ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin->email) ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($admin->phone) ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="admin" <?= $admin->role == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="superadmin" <?= $admin->role == 'superadmin' ? 'selected' : '' ?>>Super Admin
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="1" <?= $admin->status == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $admin->status == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" name="update" class="btn-submit">Update Admin</button>

            </form>
        </div>

    </div>

</body>

</html>