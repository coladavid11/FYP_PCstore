<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch shipping records joined with state descriptions
$sql = "SELECT r.rate_id, r.state_id, r.fee, s.state_name 
        FROM tbl_shipping_rate r 
        INNER JOIN tblstate s ON r.state_id = s.state_id 
        ORDER BY r.rate_id ASC";
$query = $dbh->prepare($sql);
$query->execute();
$rates = $query->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shipping Rate Management</title>
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
        }

        .sidebar a {
            display: block;
            color: #adadad;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #d4af37;
            color: #000;
        }

        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .topbar h1 {
            font-size: 1.6rem;
            color: #111;
        }

        .btn-back {
            color: #d4af37;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .btn-back:hover {
            opacity: 0.75;
        }

        .card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #fafafa;
            color: #d4af37;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 14px;
            text-align: left;
            border-bottom: 2px solid #f0f0f0;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 0.88rem;
        }

        .btn-edit {
            color: #d4af37;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-edit:hover {
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
        <a href="shipping_rates.php" class="active">🚚 Shipping Rates</a>
        <a href="sales_report.php">📊 Sales Report</a>
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">
        <div class="topbar">
            <h1>Admin Management</h1>
            <a href="dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

        <div class="card">
            <h2 style="font-size: 1.1rem; margin-bottom: 15px; color: #111;">Shipping Rates Matrix</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Destination State / Region</th>
                        <th>Shipping Fee (RM)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rates):
                        foreach ($rates as $rate): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rate->rate_id); ?></td>
                                <td><strong><?php echo htmlspecialchars($rate->state_name); ?></strong></td>
                                <td>RM <?php echo number_format($rate->fee, 2); ?></td>
                                <td>
                                    <a href="edit_shipping_rate.php?id=<?php echo $rate->rate_id; ?>" class="btn-edit">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#999;">No shipping configurations initialized in
                                the system database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>