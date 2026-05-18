<?php
session_start();
include('includes/config.php');
$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

$orders = [];

if ($isLoggedIn) {

    $stmt = $dbh->prepare("
        SELECT * FROM tblorders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- INCLUDE YOUR CSS -->
<link rel="stylesheet" href="newstyle.css">

<body>
<?php include('includes/header.php'); ?>

<div class="container py-5">

<h2 class="mb-4" style="color:#fff;">My Orders</h2>

<?php if(!$isLoggedIn): ?>

<div class="dark-card p-5 text-center">
    <h4>Please login to view orders</h4>
    <a href="login.php" class="btn-cta mt-3">Login</a>
</div>

<?php elseif(empty($orders)): ?>

<div class="dark-card p-5 text-center">
    <h4>No orders found</h4>
</div>

<?php else: ?>

<?php foreach($orders as $order): ?>

<div class="dark-card p-4 mb-3">

    <div class="d-flex justify-content-between">
        <h5 style="color:#d4af37;">
            <?php echo $order['order_number']; ?>
        </h5>

        <span class="badge bg-warning text-dark">
            <?php echo $order['order_status']; ?>
        </span>
    </div>

    <p class="text-soft mb-1">
        Date: <?php echo $order['created_at']; ?>
    </p>

    <p class="mb-2">
        Total: <strong style="color:#d4af37;">
            RM <?php echo number_format($order['grand_total'],2); ?>
        </strong>
    </p>

    <a href="order-details.php?id=<?php echo $order['order_id']; ?>"
       class="btn-cta">
        View Details
    </a>

    <?php if($order['order_status'] == 'processing'): ?>
        <button class="btn btn-danger btn-sm ms-2"
            onclick="alert('Cancel request sent to admin for approval')">
            Cancel Order
        </button>
    <?php endif; ?>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html>