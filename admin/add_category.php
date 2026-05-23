<?php
session_start();
include('includes/config.php');

if(!isset($_SESSION['admin_login'])){
    header("Location: admin_login.php");
    exit;
}

if(isset($_POST['submit'])){

    $name = trim($_POST['category_name']);

    $sql = "INSERT INTO categories(category_name) VALUES(:name)";

    $query = $dbh->prepare($sql);
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->execute();

    header("Location: categories.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category | My PC Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .topbar h1 {
            font-size: 1.8rem;
            color: #111;
            font-weight: 600;
        }

        /* =========================
           FORM CONTAINER (Reusing table-box layout)
        ========================= */
        .table-box {
            background: #fff;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-radius: 4px;
            max-width: 600px; /* Limits the form width so it doesn't stretch too wide */
        }

        /* Form Group Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #ccac3d;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        /* Form Input Styling */
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            background: #fdfdfd;
            border-radius: 4px;
            font-size: 0.95rem;
            color: #333;
            outline: none;
            transition: 0.3s;
        }

        input[type="text"]:focus {
            border-color: #d4af37;
            box-shadow: 0 0 5px rgba(212, 175, 55, 0.2);
        }

        /* Action Buttons Wrapper */
        .form-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        /* Custom Action Buttons */
        .btn-submit {
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #d4af37;
            color: #000;
        }

        .btn-cancel {
            display: inline-block;
            background: #eee;
            color: #333;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            transition: 0.3s;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #ddd;
            color: #111;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="categories.php" class="active">Categories</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Add New Category</h1>
    </div>

    <div class="table-box">

        <form method="POST">
            
            <div class="form-group">
                <label for="category_name">Category Name</label>
                <input type="text" 
                       id="category_name"
                       name="category_name" 
                       placeholder="e.g., Graphics Card, Processors" 
                       required>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">Add Category</button>
                <a href="categories.php" class="btn-cancel">Cancel</a>
            </div>

        </form>

    </div>

</div>

</body>
</html>