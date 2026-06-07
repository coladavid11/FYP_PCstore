<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$id = intval($_GET['id']);

$sql = "SELECT tblorders.*, tbluser.fullname FROM tblorders LEFT JOIN tbluser ON tblorders.user_id = tbluser.user_id WHERE tblorders.order_id = :id";
$query = $dbh->prepare($sql);
$query->bindParam(':id', $id, PDO::PARAM_INT);
$query->execute();

$orders = $query->fetch(PDO::FETCH_OBJ);

if (isset($_POST['update'])) {
    $status = $_POST['status'];

    $sql = "UPDATE tblorders SET order_status = :status WHERE order_id = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':status', $status, PDO::PARAM_STR);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    $query->execute();

    header("Location: orders.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update</title>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            padding: 30px;
            min-height: 100vh;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-bottom: 20px;
        }

        p {
            margin: 10px 0;
        }

        label {
            font-weight: 600;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 10px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }

        .update-btn,
        .back-btn {
            flex: 1;

            text-align: center;

            text-decoration: none;

            padding: 12px;

            border: none;

            border-radius: 4px;

            font-weight: 600;

            transition: 0.3s;
        }

        .update-btn {
            background: #ccac3d;
            color: #fff;
        }

        .update-btn:hover {
            background: #000;
        }

        .back-btn {
            background: #eee;
            color: #333;
        }

        .back-btn:hover {
            background: #000;
            color: #fff;
        }

        .success {
            background: #d4edda;
            color: #155724;

            padding: 12px;

            border-radius: 4px;

            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="container">

        <h2>Edit Order</h2>

        <p><strong>Order ID:</strong> <?= $orders->order_id ?></p>

        <p><strong>Customer:</strong> <?= htmlspecialchars($orders->fullname) ?></p>

        <p><strong>Total:</strong> RM <?= number_format($orders->total_amount, 2) ?></p>

        <p><strong>Date:</strong> <?= $orders->created_at ?></p>

        <form method="POST">

            <label>Status</label>

            <select name="status">

                <option value="processing" <?= ($orders->order_status == "processing") ? "selected" : "" ?>>
                    processing
                </option>

                <option value="packed" <?= ($orders->order_status == "packed") ? "selected" : "" ?>>
                    packed
                </option>

                <option value="shipped" <?= ($orders->order_status == "shipped") ? "selected" : "" ?>>
                    shipped
                </option>

                <option value="delivered" <?= ($orders->order_status == "delivered") ? "selected" : "" ?>>
                    delivered
                </option>

                <option value="cancelled" <?= ($orders->order_status == "cancelled") ? "selected" : "" ?>>
                    cancelled
                </option>

            </select>

            <div class="btn-group">

                <button type="submit" name="update" class="update-btn">

                    Update Order

                </button>

                <a href="orders.php" class="back-btn">

                    Back

                </a>

            </div>

        </form>

    </div>
</body>

</html>