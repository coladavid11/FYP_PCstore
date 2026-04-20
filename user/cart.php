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
<style>
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #0f0f0f;
    color: #fff;
}

/* ── NAVBAR ─────────────────────────────────── */
.navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 3rem;
    height: 70px;
    background: rgba(15,15,15,.97);
    border-bottom: 1px solid #2a2a2a;
    backdrop-filter: blur(12px);
}

.nav-logo {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #d4af37;
    letter-spacing: 2px;
    text-decoration: none;
    text-transform: uppercase;
}

.nav-logo span {
    color: #fff;
}

.nav-links {
    display: flex;
    gap: 2.5rem;
    list-style: none;
}

.nav-links a {
    font-size: .78rem;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #888;
    text-decoration: none;
    transition: color .3s;
}

.nav-links a:hover,
.nav-links a.active {
    color: #d4af37;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cart-count {
    background: #d4af37;
    color: #000;
    font-size: .72rem;
    font-weight: 700;
    padding: .2rem .55rem;
    border-radius: 50%;
    min-width: 22px;
    text-align: center;
}

.btn-cta {
    background: linear-gradient(45deg, #d4af37, #c5a028);
    color: #000;
    padding: 10px 24px;
    font-weight: 700;
    border-radius: 2px;
    text-decoration: none;
    transition: .3s;
    border: none;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: .78rem;
    cursor: pointer;
}

.btn-cta:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212,175,55,.3);
}

/* ── BREADCRUMB ─────────────────────────────── */
.breadcrumb {
    padding: .8rem 3rem;
    font-size: .73rem;
    color: #555;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-bottom: 1px solid #1a1a1a;
}

.breadcrumb span {
    color: #d4af37;
}

/* ── HERO STRIP ──────────────────────────────── */
.page-hero {
    background: linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.88)),
        url('https://images.unsplash.com/photo-1553440569-bcc63803a83d?q=80&w=2070&auto=format&fit=crop');
    background-size: cover;
    background-position: center;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    text-align: center;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    text-shadow: 0 10px 30px rgba(0,0,0,.8);
    margin-bottom: 8px;
    animation: fadeInUp 1.2s cubic-bezier(.2,1,.2,1);
}

.hero-subtitle {
    font-size: .95rem;
    color: #d4af37;
    letter-spacing: 3px;
    text-transform: uppercase;
    font-weight: 500;
    animation: fadeInUp 1.2s cubic-bezier(.2,1,.2,1);
}

.accent-line {
    width: 60px;
    height: 2px;
    background: #d4af37;
    margin: 14px auto 0;
    border: none;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ── MAIN LAYOUT ─────────────────────────────── */
.main {
    max-width: 1100px;
    margin: 0 auto;
    padding: 3rem 2rem 5rem;
}

/* ── SECTION TITLE ───────────────────────────── */
.section-title {
    font-family: 'Playfair Display', serif;
    color: #fff;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: .75rem;
}

.section-title::before {
    content: '';
    display: inline-block;
    width: 3px;
    height: 1.6rem;
    background: #d4af37;
}

/* ── PANEL ───────────────────────────────────── */
.panel {
    background: #181818;
    border: 1px solid #2a2a2a;
    border-radius: 0;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    padding: 1.75rem;
    position: relative;
    overflow: hidden;
}

.panel::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #c5a028, #d4af37);
}

/* ── CART ITEMS ──────────────────────────────── */
.cart-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #555;
}

.cart-empty i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #2a2a2a;
    display: block;
}

.cart-empty p {
    font-size: .85rem;
}

.cart-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #1e1e1e;
}

.cart-item:last-child {
    border-bottom: none;
}

.ci-name {
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.5;
}

.ci-unit {
    font-size: .72rem;
    color: #555;
    margin-top: .2rem;
}

.qty-control {
    display: flex;
    align-items: center;
    gap: .4rem;
}

.qty-btn {
    width: 28px;
    height: 28px;
    background: #111;
    border: 1px solid #2a2a2a;
    color: #fff;
    border-radius: 2px;
    cursor: pointer;
    font-size: .95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color .2s, background .2s;
}

.qty-btn:hover {
    border-color: #d4af37;
    background: rgba(212,175,55,.1);
    color: #d4af37;
}

.qty-val {
    font-size: .9rem;
    width: 28px;
    text-align: center;
    font-weight: 600;
}

.ci-total {
    font-size: .95rem;
    font-weight: 700;
    color: #d4af37;
    text-align: right;
    min-width: 90px;
}

.remove-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: #444;
    font-size: 1rem;
    padding: .2rem .3rem;
    transition: color .2s;
    margin-left: .25rem;
}

.remove-btn:hover {
    color: #e74c3c;
}

/* ── ORDER SUMMARY ───────────────────────────── */
.summary-wrapper {
    margin-top: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    font-size: .85rem;
    color: #888;
    padding: .35rem 0;
}

.summary-row.total {
    font-family: 'Playfair Display', serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    padding-top: .75rem;
    margin-top: .25rem;
    border-top: 1px solid #2a2a2a;
}

.summary-row.total .val {
    color: #d4af37;
}

.checkout-btn {
    width: 100%;
    padding: 1rem;
    margin-top: 1.2rem;
    font-family: 'Poppins', sans-serif;
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    background: linear-gradient(45deg, #d4af37, #c5a028);
    color: #000;
    border: none;
    cursor: pointer;
    border-radius: 2px;
    text-align: center;
    text-decoration: none;
    display: block;
    transition: all .3s;
}

.checkout-btn:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(212,175,55,.3);
}

.checkout-btn.disabled {
    opacity: .4;
    pointer-events: none;
}

.clear-btn {
    width: 100%;
    padding: .65rem;
    margin-top: .75rem;
    font-size: .73rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: transparent;
    border: 1px solid #2a2a2a;
    color: #555;
    cursor: pointer;
    border-radius: 2px;
    font-family: 'Poppins', sans-serif;
    transition: all .3s;
}

.clear-btn:hover {
    border-color: #e74c3c;
    color: #e74c3c;
}

.stat-card {
    background: #181818;
    border: 1px solid #2a2a2a;
    border-radius: 0;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    transition: all .4s ease;
    padding: 1rem;
    margin-top: 1rem;
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: #444;
}

.text-soft {
    color: #888;
    font-size: .82rem;
    letter-spacing: .2px;
}

/* ── RESPONSIVE ──────────────────────────────── */
@media (max-width: 900px) {
    .navbar {
        padding: 0 1.2rem;
    }

    .nav-links {
        display: none;
    }

    .breadcrumb {
        padding: .75rem 1.2rem;
    }

    .main {
        padding: 1.5rem 1rem 3rem;
    }

    .cart-item {
        grid-template-columns: 1fr;
        align-items: start;
    }

    .qty-control {
        justify-content: flex-start;
    }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="nav-logo">🖥 <span>My PC</span> Store</a>
    <ul class="nav-links">
        <li><a href="index">Home</a></li>
        <li><a href="#">Find Computers</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="contact.php">Contact Us</a></li>
    </ul>
    <div class="nav-right">
        <?php if ($count > 0): ?>
        <span class="cart-count"><?= $count ?></span>
        <?php endif; ?>
        <a href="login.php" class="btn-cta">Login</a>
    </div>
</nav>

<!-- BREADCRUMB -->
<div class="breadcrumb">Home &nbsp;/&nbsp; <span>Shopping Cart</span></div>

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