<?php
session_start();

include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}


/* =========================
   DASHBOARD DATA
========================= */

// Total Products
$stmt = $dbh->query("SELECT COUNT(*) FROM products");
$totalProducts = $stmt->fetchColumn();

// Total Orders
$stmt = $dbh->query("SELECT COUNT(*) FROM tblorders");
$totalOrders = $stmt->fetchColumn();

// Total Users
$stmt = $dbh->query("SELECT COUNT(*) FROM tbluser");
$totalUsers = $stmt->fetchColumn();

// Total Revenue
$stmt = $dbh->query("SELECT SUM(grand_total) FROM tblorders WHERE order_status='delivered'");
$totalRevenue = $stmt->fetchColumn();

// Recent Orders
$stmt = $dbh->query("SELECT * FROM tblorders ORDER BY created_at DESC LIMIT 5");
$orders = $stmt->fetchAll();

/* =========================
   ORDER STATUS CHART DATA
========================= */

$pending = $dbh->query("
SELECT COUNT(*) 
FROM tblorders
WHERE order_status='pending'
")->fetchColumn();

$processing = $dbh->query("
SELECT COUNT(*) 
FROM tblorders
WHERE order_status='processing'
")->fetchColumn();

$shipped = $dbh->query("
SELECT COUNT(*) 
FROM tblorders
WHERE order_status='shipped'
")->fetchColumn();

$delivered = $dbh->query("
SELECT COUNT(*) 
FROM tblorders
WHERE order_status='delivered'
")->fetchColumn();

$cancelled = $dbh->query("
SELECT COUNT(*) 
FROM tblorders
WHERE order_status='cancelled'
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
   MAIN
========================= */

        .main {
            margin-left: 220px;
            width: 100%;
            padding: 20px;
        }

        /* =========================
   TOPBAR
========================= */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .topbar h1 {
            font-size: 2rem;
            color: #111;
        }

        .topbar-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .profile,
        .logout {
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .profile {
            color: #d4af37;
        }

        .logout {
            color: red;
        }

        .profile:hover,
        .logout:hover {
            opacity: 0.7;
        }

        /* =========================
   CARDS
========================= */

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .cards a {
            text-decoration: none;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-left: 5px solid #d4af37;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }

        .card h3 {
            font-size: 1rem;
            color: #777;
        }

        .card p {
            font-size: 2rem;
            margin-top: 10px;
            color: #111;
            font-weight: 600;
        }

        /* =========================
   TABLE
========================= */

        .table-box {
            margin-top: 30px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .table-box h3 {
            margin-bottom: 20px;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        table th {
            color: #d4af37;
        }

        /* =========================
   STATUS
========================= */

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status.completed {
            background: #d4edda;
            color: #155724;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* =========================
   PIE CHART
========================= */

        .chart-box {
            margin-top: 30px;
            background: #fff;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-box h3 {
            margin-bottom: 20px;
            color: #111;
        }

        /* =========================
   PIE CHART CONTAINER
========================= */

        .chart-container {
            width: 100%;
            /* Allow it to use available width up to max */
            max-width: 580px;
            /* Increased from 520px to prevent long text clipping */
            height: 260px;
            /* Set a fixed height for the container */
            margin: 10px auto;
            position: relative;
        }
    </style>

</head>

<body>

    <?php if (isset($_SESSION['error'])) { ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: '<?php echo $_SESSION['error']; ?>',
                confirmButtonColor: '#d4af37'
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php } ?>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <h2>Admin</h2>

        <a href="dashboard.php" class="sidebar-active">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="admins.php">⚙ Admin</a>

    </div>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">

            <h1>Welcome, <?php echo $_SESSION['admin_name']; ?></h1>

            <div class="topbar-links">
                <a href="admin_profile.php" class="profile">Admin Profile</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>

        </div>

        <!-- CARDS -->
        <div class="cards">

            <a href="products.php">
                <div class="card">
                    <h3>Total Products</h3>
                    <p><?php echo $totalProducts; ?></p>
                </div>
            </a>

            <a href="orders.php">
                <div class="card">
                    <h3>Total Orders</h3>
                    <p><?php echo $totalOrders; ?></p>
                </div>
            </a>

            <a href="users.php">
                <div class="card">
                    <h3>Total Users</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
            </a>

            <a href="revenue.php">
                <div class="card">
                    <h3>Total Revenue</h3>
                    <p>
                        RM <?php echo number_format($totalRevenue ?? 0, 2); ?>
                    </p>
                </div>
            </a>

        </div>

        <!-- RECENT ORDERS -->
        <div class="table-box">

            <h3>Recent Orders</h3>

            <!-- ORDER STATUS CHART -->

            <div class="chart-box">

                <h3>Order Status Distribution</h3>

                <div class="chart-container">

                    <canvas id="orderChart"></canvas>

                </div>

            </div>

            <table>

                <tr>
                    <th>Order ID</th>
                    <th>User</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>

                <?php if (count($orders) > 0) { ?>

                    <?php foreach ($orders as $order) { ?>

                        <tr>

                            <td>
                                #<?php echo $order->order_id; ?>
                            </td>

                            <td>
                                <?php echo $order->user_id; ?>
                            </td>

                            <td>
                                RM <?php echo number_format($order->grand_total, 2); ?>
                            </td>

                            <td>
                                <span class="status <?php echo strtolower($order->order_status); ?>">
                                    <?php echo $order->order_status; ?>
                                </span>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="4" style="text-align:center;">
                            No recent orders found.
                        </td>
                    </tr>

                <?php } ?>

            </table>

        </div>

    </div>

</body>

<script>

    const ctx = document.getElementById('orderChart');

    new Chart(ctx, {

        type: 'pie',

        data: {

            labels: [
                'Pending',
                'Processing',
                'Shipped',
                'Delivered',
                'Cancelled'
            ],

            datasets: [{
                data: [
                    <?= $pending ?>,
                    <?= $processing ?>,
                    <?= $shipped ?>,
                    <?= $delivered ?>,
                    <?= $cancelled ?>
                ],

                backgroundColor: [
                    '#ffc107',
                    '#17a2b8',
                    '#007bff',
                    '#28a745',
                    '#dc3545'
                ],

                borderWidth: 1
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false, // Allows the chart canvas to stretch wide for text labels

            plugins: {
                legend: {
                    position: 'right',
                    align: 'center',
                    labels: {
                        boxWidth: 15,
                        padding: 12,       // Vertical spacing between items
                        usePointStyle: false,

                        generateLabels: function (chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                const dataset = data.datasets[0];
                                const total = dataset.data.reduce((sum, value) => sum + value, 0);

                                return data.labels.map((label, i) => {
                                    const value = dataset.data[i];
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;

                                    // Formats clearly as: Pending: 0.0% (0)
                                    return {
                                        text: `${label}: ${percentage}% (${value})`,
                                        fillStyle: dataset.backgroundColor[i],
                                        strokeStyle: dataset.borderColor ? dataset.borderColor[i] : '#fff',
                                        lineWidth: dataset.borderWidth,
                                        hidden: isNaN(dataset.data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                title: {
                    display: false
                }
            },
            layout: {
                padding: {
                    left: 10,
                    right: 30, // Generous padding on the right edge so text won't touch the wall
                    top: 10,
                    bottom: 10
                }
            }
        }
    });

</script>

</html>