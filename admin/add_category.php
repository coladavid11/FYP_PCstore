<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$error_msg = ""; // Initialize error message variable

if (isset($_POST['submit'])) {

    $name = trim($_POST['category_name']);

    // Check if the category name already exists
    $check_sql = "SELECT COUNT(*) FROM categories WHERE category_name = :name";
    $check_query = $dbh->prepare($check_sql);
    $check_query->bindParam(':name', $name, PDO::PARAM_STR);
    $check_query->execute();

    $category_exists = $check_query->fetchColumn();

    if ($category_exists > 0) {
        // Set exact message string
        $error_msg = "This name is already exist";
    } else {
        // Proceed to insert if it's unique
        $sql = "INSERT INTO categories(category_name) VALUES(:name)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->execute();

        header("Location: categories.php");
        exit;
    }
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
           FORM CONTAINER
        ========================= */
        .table-box {
            background: #fff;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            max-width: 600px;
        }

        /* --- NEW ERROR BANNER STYLE (Matches the requested format) --- */
        .alert-error-banner {
            background-color: #fce8e6;
            /* Soft light red background */
            color: #c5221f;
            /* Sharp dark red font */
            border: 1px solid #fad2cf;
            /* Delicate matching border */
            padding: 14px 20px;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Round background wrapper for the icon inside the banner */
        .alert-error-banner .error-icon {
            background-color: #c5221f;
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
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
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php" class="sidebar-active">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Add New Category</h1>
            <div class="topbar-links">
                <a href="categories.php" class="Back">Back</a>
            </div>
        </div>

        <div class="table-box">

            <?php if (!empty($error_msg)): ?>
                <div class="alert-error-banner">
                    <span class="error-icon">✕</span> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label for="category_name">Category Name</label>
                    <input type="text" id="category_name" name="category_name"
                        placeholder="e.g., Graphics Card, Processors"
                        value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>"
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