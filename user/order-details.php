<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

$order_id = $_GET['id'] ?? 0;

// GET ORDER
$stmt = $dbh->prepare("
    SELECT * FROM tblorders
    WHERE order_id = ? AND user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// GET ITEMS
$stmt = $dbh->prepare("
    SELECT * FROM tblorder_items
    WHERE order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- INCLUDE YOUR CSS -->
<link rel="stylesheet" href="newstyle.css">
</head>

<body>

<div class="container py-5">
<?php include('includes/header.php'); ?>

<?php if(!$order): ?>

<div class="dark-card p-5 text-center">
    <h4>Order not found</h4>
</div>

<?php else: ?>

<div class="row g-4">

<!-- LEFT -->
<div class="col-lg-8">

<div class="dark-card p-4">

<h4 style="color:#d4af37;">
    Order #<?php echo $order['order_number']; ?>
</h4>

<hr style="border-color:#2a2a2a;">

<?php foreach($items as $item): ?>

<div class="d-flex justify-content-between mb-2">

    <div>
        <strong><?php echo $item['product_name']; ?></strong><br>
        <span class="text-soft">
            Qty: <?php echo $item['quantity']; ?>
        </span>
    </div>

    <div>
        RM <?php echo number_format($item['subtotal'],2); ?>
    </div>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- RIGHT -->
<div class="col-lg-4">

<div class="dark-card p-4">

<h5>Order Summary</h5>

<hr style="border-color:#2a2a2a;">

<p>Status:
    <span style="color:#d4af37;">
        <?php echo $order['order_status']; ?>
    </span>
</p>

<p>Payment: Paid</p>

<p>Shipping:
    RM <?php echo number_format($order['shipping_fee'],2); ?>
</p>

<hr>

<h5 style="color:#d4af37;">
    Total RM <?php echo number_format($order['grand_total'],2); ?>
</h5>

<br>

<button class="btn-cta" onclick="window.print()">
    Download Invoice
</button>

<a href="product-details.php?id=<?php echo $item['product_id']; ?>" 
   class="btn btn-warning btn-sm mt-2 w-100">
    Buy Again
</a>

</div>

</div>

</div>

<?php endif; ?>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html> 