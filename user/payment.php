<?php
session_start();
include('includes/config.php');

//check login
$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

$cartItems = [];
$total = 0;
$success = false;
$error = '';



// FETCH CART
if ($isLoggedIn) {
    $stmt = $dbh->prepare("
        SELECT * FROM tblcart 
        WHERE user_id=? AND status='active'
    ");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        $total += $item['product_price'] * $item['quantity'];
    }

    $shipping = 15.00;
    $service_fee = 0.00;
    $grand_total = $total + $shipping + $service_fee;
}

// PROCESS PAYMENT (DEMO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {

    if (empty($cartItems)) {
        $error = "Cart is empty.";
    } else {

        try {
            $dbh->beginTransaction();

            $grand_total = $total + $shipping + $service_fee;

            // 1. GENERATE ORDER NUMBER
            $order_number = 'PC' . date('Ymd') . strtoupper(substr(uniqid(), -5));

            // 2. INSERT INTO tblorders
            $stmt = $dbh->prepare("
                INSERT INTO tblorders 
                (user_id, order_number, total_amount, shipping_fee, service_fee, grand_total, payment_status, order_status)
                VALUES (?, ?, ?, ?, ?, ?, 'paid', 'processing')
            ");

            $stmt->execute([
                $user_id,
                $order_number,
                $total,
                $shipping,
                $service_fee,
                $grand_total
            ]);

            $order_id = $dbh->lastInsertId();

            // 3. INSERT ORDER ITEMS
            foreach ($cartItems as $item) {

                $subtotal = $item['product_price'] * $item['quantity'];

                $stmt = $dbh->prepare("
                    INSERT INTO tblorder_items
                    (order_id, product_id, product_name, product_price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_price'],
                    $item['quantity'],
                    $subtotal
                ]);
            }

            // 4. UPDATE CART STATUS
            $stmt = $dbh->prepare("
                UPDATE tblcart 
                SET status = 'ordered'
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$user_id]);

            $dbh->commit();

            $success = true;

            // clear local cart view
            $cartItems = [];

        } catch (Exception $e) {
            $dbh->rollBack();
            $error = "Payment failed: " . $e->getMessage();
        }
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

<!-- LEFT SIDE -->
<div class="col-lg-8">

<?php if(!$isLoggedIn): ?>

    <div class="dark-card p-5 text-center">
        <i class="fa fa-lock fa-3x mb-3" style="color:#d4af37;"></i>
        <h3>Login Required</h3>
        <p class="text-soft">Please login to proceed payment.</p>
        <a href="login.php" class="btn-cta">Login</a>
    </div>

<?php elseif($success): ?>

    <div class="dark-card p-5 text-center">

        <i class="fa fa-check-circle fa-3x mb-3" style="color:#d4af37;"></i>

        <h3>Payment Successful</h3>

        <p class="text-soft">Your order has been placed.</p>

        <h4 style="color:#d4af37;">
            RM <?php echo number_format($grand_total,2); ?>
        </h4>

        <a href="index.php" class="btn-cta mt-3">
            Back to Store
        </a>

    </div>

<?php else: ?>

<?php if($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat-card p-4">

    <h4 class="mb-3">Secure Payment</h4>

    <form method="POST">

        <div class="mb-3">
    <label class="form-label">Card Number</label>
    <input type="text" id="cardNumber" class="form-control"
           placeholder="0000 0000 0000 0000"
           maxlength="19" required>
</div>

<div class="row">
    <div class="col">
        <label class="form-label">Expiry</label>
        <input type="text" id="expiry" class="form-control"
               placeholder="MM/YY"
               maxlength="5" required>
    </div>

    <div class="col">
        <label class="form-label">CVV</label>
        <input type="text" id="cvv" class="form-control"
               placeholder="123"
               maxlength="3" required>
    </div>
</div>

        <button type="submit" class="btn-gold mt-4 w-100">
            PAY RM <?php echo number_format($grand_total,2); ?>
        </button>

    </form>

</div>

<?php endif; ?>

</div>

<!-- RIGHT SIDE -->
<div class="col-lg-4">

<div class="stat-card p-4">

    <h4 class="mb-3">Order Summary</h4>

    <hr style="border-color:#2a2a2a;">

    <?php foreach($cartItems as $item): ?>
        <div class="d-flex justify-content-between mb-2">
            <span class="text-soft">
                <?php echo $item['product_name']; ?> x <?php echo $item['quantity']; ?>
            </span>
            <span>
                RM <?php echo number_format($item['product_price']*$item['quantity'],2); ?>
            </span>
        </div>
    <?php endforeach; ?>

    <?php 
    $shipping = 15.00;
    $service_fee = 0.00;
    $grand_total = $total + $shipping + $service_fee;
    ?>
    
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
    

</div>

</div>

</div>

</div>

<?php include('includes/footer.php'); ?>
<script>
  // ================= CARD NUMBER =================
const cardInput = document.getElementById('cardNumber');

cardInput.addEventListener('input', function () {
    let value = this.value.replace(/\D/g, ''); // remove letters

    if (value.length > 16) {
        alert("Card number cannot exceed 16 digits");
        value = value.substring(0, 16);
    }

    // format 4-4-4-4
    let formatted = value.replace(/(\d{4})/g, '$1 ').trim();
    this.value = formatted;
});


// ================= EXPIRY =================
const expiryInput = document.getElementById('expiry');

expiryInput.addEventListener('input', function () {
    let value = this.value.replace(/\D/g, ''); // only digits

    if (value.length > 4) {
        value = value.substring(0, 4);
    }

    if (value.length >= 3) {
        this.value = value.substring(0, 2) + '/' + value.substring(2);
    } else {
        this.value = value;
    }
});


// ================= CVV =================
const cvvInput = document.getElementById('cvv');

cvvInput.addEventListener('input', function () {
    let value = this.value.replace(/\D/g, '');

    if (value.length > 3) {
        alert("CVV must be 3 digits only");
        value = value.substring(0, 3);
    }

    this.value = value;
});


// ================= FINAL FORM CHECK =================
document.querySelector("form").addEventListener("submit", function (e) {

    let card = cardInput.value.replace(/\s/g, '');

    if (card.length !== 16) {
        alert("Card number must be exactly 16 digits");
        e.preventDefault();
        return;
    }

    if (expiryInput.value.length !== 5) {
        alert("Invalid expiry format (MM/YY)");
        e.preventDefault();
        return;
    }

    if (cvvInput.value.length !== 3) {
        alert("CVV must be 3 digits");
        e.preventDefault();
        return;
    }
});
</script>
</body>