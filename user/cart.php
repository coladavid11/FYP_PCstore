<?php
session_start();

$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    $id = intval($_POST['product_id']);
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => $i['id'] !== $id));
}

if ($action === 'update') {
    $id  = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);

    if ($qty <= 0) {
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'] ?? [], fn($i) => $i['id'] !== $id));
    } else {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $id) {
                $item['qty'] = $qty;
                break;
            }
        }
        unset($item);
    }
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
}

$cart     = $_SESSION['cart'] ?? [];
$subtotal = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $cart));
$tax      = $subtotal * 0.06;
$total    = $subtotal + $tax;
$count    = array_sum(array_column($cart, 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My PC Store — Shopping Cart</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="newstyle.css"/>

</head>
<body>
<?php include('includes/header.php'); ?>

<!-- HERO STRIP -->
<div class="page-hero">
    <h1 class="hero-title">Shopping Cart</h1>
    <p class="hero-subtitle">Review Your Selection</p>
    <hr class="accent-line"/>
</div>

<!-- MAIN -->
<div class="main">

    <!-- YOUR CART -->
    <div>
        <h2 class="section-title">Your Cart</h2>
        <div class="panel">
            <?php if (empty($cart)): ?>
            <div class="cart-empty">
                <i class="fa-solid fa-cart-shopping"></i>
                <p>Your cart is empty.<br><span class="text-soft">No items in cart.</span></p>
            </div>
            <?php else: ?>
                <?php foreach ($cart as $item): ?>
                <div class="cart-item">
                    <div>
                        <div class="ci-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="ci-unit">RM <?= number_format($item['price'], 2) ?> / unit</div>
                    </div>

                    <div class="qty-control">
                        <form method="POST" style="display:contents">
                            <input type="hidden" name="action" value="update"/>
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>"/>
                            <input type="hidden" name="qty" value="<?= $item['qty'] - 1 ?>"/>
                            <button type="submit" class="qty-btn">−</button>
                        </form>

                        <span class="qty-val"><?= $item['qty'] ?></span>

                        <form method="POST" style="display:contents">
                            <input type="hidden" name="action" value="update"/>
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>"/>
                            <input type="hidden" name="qty" value="<?= $item['qty'] + 1 ?>"/>
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </div>

                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem">
                        <div class="ci-total">RM <?= number_format($item['qty'] * $item['price'], 2) ?></div>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove"/>
                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>"/>
                            <button type="submit" class="remove-btn" title="Remove">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ORDER SUMMARY -->
    <div class="summary-wrapper">
        <h2 class="section-title">Order Summary</h2>
        <div class="panel">
            <div class="summary-row">
                <span>Items (<?= $count ?>)</span>
                <span>RM <?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>SST (6%)</span>
                <span>RM <?= number_format($tax, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span style="color:#27ae60;font-weight:600;font-size:.8rem">FREE</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span class="val">RM <?= number_format($total, 2) ?></span>
            </div>

            <a href="payment.php" class="checkout-btn <?= empty($cart) ? 'disabled' : '' ?>">
                <i class="fa-solid fa-lock"></i>&nbsp; Proceed to Payment
            </a>

            <?php if (!empty($cart)): ?>
            <form method="POST">
                <input type="hidden" name="action" value="clear"/>
                <button type="submit" class="clear-btn">
                    <i class="fa-solid fa-trash"></i>&nbsp; Clear Cart
                </button>
            </form>
            <?php endif; ?>

            <div class="stat-card">
                <div style="display:flex;gap:.6rem;align-items:center;justify-content:center;margin-bottom:.5rem">
                    <i class="fa-solid fa-shield-halved" style="color:#d4af37"></i>
                    <span style="font-size:.82rem;font-weight:600">Buyer Protection</span>
                </div>
                <p class="text-soft" style="text-align:center;font-size:.75rem">
                    Full refund if item not received or not as described.
                </p>
            </div>
        </div>
    </div>

</div>
</body>
</html>