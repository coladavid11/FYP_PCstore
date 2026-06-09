<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$msg = "";
$error = "";

if(isset($_POST['submit'])){

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Check email exists
    $check = $dbh->prepare("SELECT admin_id FROM admin WHERE email = :email");
    $check->bindParam(':email',$email,PDO::PARAM_STR);
    $check->execute();

    if($check->rowCount() > 0){

        $error = "Email already exists.";

    } else {

        $sql = "INSERT INTO admin
                (fullname,email,password,role,status)
                VALUES
                (:fullname,:email,:password,:role,1)";

        $query = $dbh->prepare($sql);

        $query->bindParam(':fullname',$fullname,PDO::PARAM_STR);
        $query->bindParam(':email',$email,PDO::PARAM_STR);
        $query->bindParam(':password',$password,PDO::PARAM_STR);
        $query->bindParam(':role',$role,PDO::PARAM_STR);

        if($query->execute()){
            $msg = "Admin added successfully.";
        } else {
            $error = "Failed to add admin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Admin | My PC Store</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    display:flex;
    background:#f5f5f5;
}

/* Sidebar */

.sidebar{
    width:220px;
    height:100vh;
    background:#000;
    padding:20px;
    position:fixed;
}

.sidebar h2{
    color:#d4af37;
    margin-bottom:30px;
    text-align:center;
}

.sidebar a{
    display:block;
    color:#adadad;
    text-decoration:none;
    padding:12px;
    margin:10px 0;
    border-radius:5px;
}

.sidebar a:hover{
    background:#d4af37;
    color:#000;
}

.sidebar-active{
    background:#d4af37;
    color:#000 !important;
}

/* Main */

.main{
    margin-left:220px;
    width:calc(100% - 220px);
    padding:30px;
}

/* Topbar */

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    background:#fff;
    padding:15px 25px;
    border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}

.topbar h1{
    font-size:1.8rem;
}

.Back{
    text-decoration:none;
    color:#d4af37;
    font-weight:500;
}

/* Form Box */

.form-box{
    background:#fff;
    padding:30px;
    border-radius:4px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

.form-box h3{
    margin-bottom:20px;
}

.form-group{
    margin-bottom:20px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    font-weight:500;
}

.form-group input,
.form-group select{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:4px;
}

.btn-save{
    background:#000;
    color:#d4af37;
    border:1px solid #d4af37;
    padding:12px 25px;
    cursor:pointer;
    border-radius:4px;
}

.btn-save:hover{
    background:#d4af37;
    color:#000;
}

.success{
    color:green;
    margin-bottom:15px;
}

.error{
    color:red;
    margin-bottom:15px;
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
        <h1>Add Admin</h1>
        <a href="admins.php" class="Back">← Back</a>
    </div>

    <div class="form-box">

        <h3>Create New Admin</h3>

        <?php if($msg){ ?>
            <div class="success"><?= $msg ?></div>
        <?php } ?>

        <?php if($error){ ?>
            <div class="error"><?= $error ?></div>
        <?php } ?>

        <form method="POST">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Role</label>

                <select name="role">

                    <option value="admin">
                        Admin
                    </option>

                    <option value="superadmin">
                        Super Admin
                    </option>

                </select>

            </div>

            <button type="submit"
                    name="submit"
                    class="btn-save">
                Add Admin
            </button>

        </form>

    </div>

</div>

</body>
</html>