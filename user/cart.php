<?php
session_start();
include('includes/config.php');

// check login
$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

// =======================
// UPDATE QUANTITY (PDO)
// =======================
if ($isLoggedIn && isset($_POST['update_qty'])) {

    $cart_id = $_POST['cart_id'];
    $qty = $_POST['qty'];

    if ($qty > 0) {
        $stmt = $dbh->prepare("
            UPDATE tblcart 
            SET quantity = ? 
            WHERE cart_id = ? AND user_id = ?
        ");
        $stmt->execute([$qty, $cart_id, $user_id]);
    }

    header("Location: cart.php");
    exit();
}

// =======================
// REMOVE ITEM (PDO)
// =======================
if ($isLoggedIn && isset($_GET['remove'])) {

    $cart_id = $_GET['remove'];

    $stmt = $dbh->prepare("
        UPDATE tblcart 
        SET status = 'removed' 
        WHERE cart_id = ? AND user_id = ?
    ");
    $stmt->execute([$cart_id, $user_id]);

    header("Location: cart.php");
    exit();
}

// =======================
// FETCH CART ITEMS
// =======================
$cartItems = [];
$total = 0;

if ($isLoggedIn) {

    $stmt = $dbh->prepare("
        SELECT * FROM tblcart 
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        $total += $item['product_price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart - PC Store</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Your CSS -->
<link rel="stylesheet" href="newstyle.css">
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="container py-5">

<div class="row g-4">

<!-- ===================== -->
<!-- LEFT: CART ITEMS -->
<!-- ===================== -->
<div class="col-lg-8">

<?php if(!$isLoggedIn): ?>

    <!-- NOT LOGGED IN -->
    <div class="dark-card p-5 text-center">

        <i class="fa fa-user-lock fa-3x mb-3" style="color:#d4af37;"></i>

        <h3>Login Required</h3>

        <p class="text-soft">
            Please login to view your shopping cart.
        </p>

        <a href="login.php" class="btn-cta mt-3">
            Login Now
        </a>

    </div>

<?php else: ?>

    <?php if(empty($cartItems)): ?>

        <!-- EMPTY CART -->
        <div class="dark-card p-5 text-center">

            <i class="fa fa-shopping-cart fa-3x mb-3" style="color:#d4af37;"></i>

            <h3>Your Cart is Empty</h3>

            <p class="text-soft">
                Start adding premium PC products now.
            </p>

            <a href="index.php" class="btn-cta">
                Continue Shopping
            </a>

        </div>

    <?php else: ?>

        <!-- CART ITEMS LOOP -->
        <?php foreach($cartItems as $item): ?>

        <div class="info-card mb-3 p-3">

            <div class="row align-items-center">

                <!-- IMAGE -->
                <div class="col-md-3">
                    <img src="<?php echo $item['product_image']; ?>"
                         class="img-fluid"
                         style="height:120px; object-fit:cover;">
                </div>

                <!-- NAME + PRICE -->
                <div class="col-md-4">
                    <h5><?php echo $item['product_name']; ?></h5>
                    <p class="text-soft mb-0">
                        RM <?php echo number_format($item['product_price'],2); ?>
                    </p>
                </div>

                <!-- QTY UPDATE -->
                <div class="col-md-3">

                    <form method="POST" class="d-flex align-items-center">

                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">

                        <input type="number"
                               name="qty"
                               value="<?php echo $item['quantity']; ?>"
                               min="1"
                               class="form-control form-control-sm">

                        <button type="submit"
                                name="update_qty"
                                class="btn btn-outline-warning btn-sm ms-2">
                            <i class="fa fa-sync"></i>
                        </button>

                    </form>

                </div>

                <!-- REMOVE -->
                <div class="col-md-2 text-end">

                    <a href="cart.php?remove=<?php echo $item['cart_id']; ?>"
                       class="btn btn-danger btn-sm">
                        <i class="fa fa-trash"></i>
                    </a>

                </div>

            </div>

        </div>

        <?php endforeach; ?>

    <?php endif; ?>

<?php endif; ?>

</div>

<!-- ===================== -->
<!-- RIGHT: SUMMARY -->
<!-- ===================== -->
<div class="col-lg-4">
<?php 
$shipping = 15.00;
$service_fee = 0.00;
$grand_total = $total + $shipping + $service_fee;
?>
<div class="stat-card p-4 sticky-top" style="top:100px;">

    <h4>Order Summary</h4>

    <hr style="border-color:#2a2a2a;">

<div class="d-flex justify-content-between mb-2">
    <span class="text-soft">Subtotal</span>
    <span>RM <?php echo number_format($total,2); ?></span>
</div>

<div class="d-flex justify-content-between mb-2">
    <span class="text-soft">Service Fee</span>
    <span>RM <?php echo number_format($service_fee,2); ?></span>
</div>

<div class="d-flex justify-content-between mb-3">
    <span class="text-soft">Shipping</span>
    <span>RM <?php echo number_format($shipping,2); ?></span>
</div>

<hr style="border-color:#2a2a2a;">

<div class="d-flex justify-content-between mb-3">
    <strong>Total</strong>
    <strong style="color:#d4af37;">
        RM <?php echo number_format($grand_total,2); ?>
    </strong>
</div>

    <?php if(!$isLoggedIn): ?>

    <a href="login.php" class="btn-gold">
        Login to Checkout
    </a>

<?php elseif(empty($cartItems)): ?>

    <button class="btn-gold" onclick="alert('Your cart is empty! Please add items before checkout.')">
        Proceed Checkout
    </button>

<?php elseif($total < 0): ?>

    <button class="btn-gold" onclick="alert('Error: Invalid cart total detected! Please refresh or contact support.')">
        Proceed Checkout
    </button>

<?php else: ?>

    <a href="payment.php" class="btn-gold">
        Proceed Checkout
    </a>

<?php endif; ?>

    

</div>

</div>

</div>
</div>
<?php include('includes/footer.php'); ?>
</body>
</html>