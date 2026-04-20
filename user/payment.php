<?php
$payment_success = false;
$payment_error = '';
$demo_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_name    = trim($_POST['card_name'] ?? '');
    $card_number  = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry       = trim($_POST['expiry'] ?? '');
    $cvv          = trim($_POST['cvv'] ?? '');
    $amount       = floatval($_POST['amount'] ?? 0);

    
    if (empty($card_name)) {
        $payment_error = 'Please enter the cardholder name.';
    } elseif (!preg_match('/^\d{16}$/', $card_number)) {
        $payment_error = 'Card number must be 16 digits.';
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $payment_error = 'Expiry must be in MM/YY format.';
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $payment_error = 'CVV must be 3 or 4 digits.';
    } elseif ($amount <= 0) {
        $payment_error = 'Invalid payment amount.';
    } else {
        // Simulate processing
        $payment_success = true;
        $demo_message    = 'DEMO MODE — No real charge was made.';
    }
}

// Cart items (demo)
session_start();

if (empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        ['name' => 'ASUS ROG Strix G16 Gaming Laptop', 'qty' => 1, 'price' => 5498.00],
        ['name' => 'Corsair K95 RGB Mechanical Keyboard', 'qty' => 1, 'price' => 699.00],
        ['name' => 'Logitech G Pro X Superlight Mouse',  'qty' => 1, 'price' => 399.00],
    ];
}

$cart     = $_SESSION['cart'];
$subtotal = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $cart));
$tax      = $subtotal * 0.06;
$total    = $subtotal + $tax;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>PC Store — Secure Payment</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="#" class="nav-logo">🖥&nbsp;<span>MY PC</span> STORE</a>
  <ul class="nav-links">
    <li><a href="#">Home</a></li>
    <li><a href="#">Find Computers</a></li>
    <li><a href="#">About Us</a></li>
    <li><a href="#">Contact Us</a></li>
  </ul>
  <button class="nav-badge">Login / Register</button>
</nav>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  Home &nbsp;/&nbsp; Cart &nbsp;/&nbsp; <span>Checkout</span>
</div>

<!-- MAIN -->
<div class="main">

  <!-- LEFT: PAYMENT FORM -->
  <div>
    <div class="section-heading">
      Secure Payment
      <span class="demo-badge">Demo</span>
    </div>

    <?php if ($payment_success): ?>
    <!-- SUCCESS STATE -->
    <div class="panel">
      <div class="success-screen">
        <div class="success-icon">✓</div>
        <div class="success-title">Payment Successful</div>
        <div class="success-ref">Reference: PC<?= strtoupper(substr(md5(time()), 0, 10)) ?></div>
        <div class="success-amount">RM <?= number_format($total, 2) ?></div>
        <div class="success-demo-note"><?= htmlspecialchars($demo_message) ?></div>
        <a href="?" style="display:inline-block;padding:.7rem 2rem;background:var(--gold);color:#000;font-family:var(--display);font-size:1.1rem;letter-spacing:.1em;text-decoration:none;border-radius:3px;margin-top:.5rem;">BACK TO STORE</a>
      </div>
    </div>

    <?php else: ?>

    <?php if ($payment_error): ?>
    <div class="alert alert-error">
      <span class="alert-icon">✕</span>
      <div><?= htmlspecialchars($payment_error) ?></div>
    </div>
    <?php endif; ?>

    <div class="panel">
      <!-- CARD VISUAL -->
      <div class="card-visual" id="cardPreview">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <div class="card-chip">
            <div></div><div></div><div></div><div></div>
          </div>
          <div class="card-logo">PC STORE</div>
        </div>
        <div class="card-number-display" id="previewNumber">•••• &nbsp;•••• &nbsp;•••• &nbsp;••••</div>
        <div class="card-meta">
          <div>
            <div class="card-meta-label">Card Holder</div>
            <div class="card-meta-val" id="previewName">YOUR NAME</div>
          </div>
          <div>
            <div class="card-meta-label">Expires</div>
            <div class="card-meta-val" id="previewExpiry">MM/YY</div>
          </div>
          <div style="display:flex;gap:-.5rem;">
            <div style="width:32px;height:32px;border-radius:50%;background:#eb001b;opacity:.9;"></div>
            <div style="width:32px;height:32px;border-radius:50%;background:#f79e1b;opacity:.9;margin-left:-10px;"></div>
          </div>
        </div>
      </div>

      <!-- ACCEPTED CARDS -->
      <div class="card-types" style="margin-bottom:1.5rem;">
        <span class="card-type-badge badge-visa">VISA</span>
        <span class="card-type-badge badge-mc">MC</span>
        <span class="card-type-badge badge-amex">AMEX</span>
        <span class="card-types-label">🔒 Secure checkout</span>
      </div>

      <!-- FORM -->
      <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="amount" value="<?= number_format($total, 2, '.', '') ?>" />

        <div class="form-grid">

          <!-- Amount -->
          <div class="amount-display">
            <span class="amount-label">Total to Pay</span>
            <span class="amount-val">RM <?= number_format($total, 2) ?></span>
          </div>

          <!-- Card Holder -->
          <div class="field">
            <label for="card_name">Cardholder Name</label>
            <input type="text" id="card_name" name="card_name"
                   placeholder="Name as on card"
                   value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>"
                   maxlength="26" required />
          </div>

          <!-- Card Number -->
          <div class="field">
            <label for="card_number">Card Number</label>
            <input type="tel" id="card_number" name="card_number"
                   placeholder="0000  0000  0000  0000"
                   maxlength="19" required />
          </div>

          <!-- Expiry + CVV -->
          <div class="form-row">
            <div class="field">
              <label for="expiry">Expiry Date</label>
              <input type="tel" id="expiry" name="expiry"
                     placeholder="MM/YY"
                     value="<?= htmlspecialchars($_POST['expiry'] ?? '') ?>"
                     maxlength="5" required />
            </div>
            <div class="field">
              <label for="cvv">CVV / CVC</label>
              <input type="tel" id="cvv" name="cvv"
                     placeholder="•••"
                     maxlength="4" required />
            </div>
          </div>

          <div class="secure-row">
            <span class="lock-icon">🔒</span>
            Your card details are encrypted and never stored.
          </div>

          <button type="submit" class="pay-btn">
            PAY &nbsp;RM <?= number_format($total, 2) ?>
          </button>

        </div>
      </form>
    </div>

    <?php endif; ?>
  </div>

  <!-- RIGHT: ORDER SUMMARY -->
  <div>
    <div class="section-heading">Order Summary</div>
    <div class="panel">
      <?php foreach ($cart as $item): ?>
      <div class="order-item">
        <div>
          <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="item-qty">Qty: <?= $item['qty'] ?></div>
        </div>
        <div class="item-price">RM <?= number_format($item['price'], 2) ?></div>
      </div>
      <?php endforeach; ?>

      <div class="summary-row" style="margin-top:.75rem;">
        <span>Subtotal</span>
        <span class="val" style="font-family:var(--mono);">RM <?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="summary-row">
        <span>SST (6%)</span>
        <span class="val" style="font-family:var(--mono);">RM <?= number_format($tax, 2) ?></span>
      </div>
      <div class="summary-row">
        <span>Shipping</span>
        <span style="color:var(--success);font-size:.8rem;font-weight:600;">FREE</span>
      </div>
      <div class="summary-row total">
        <span>TOTAL</span>
        <span class="val">RM <?= number_format($total, 2) ?></span>
      </div>

      <div style="margin-top:1.5rem;padding:1rem;background:rgba(245,197,24,.06);border:1px solid rgba(245,197,24,.15);border-radius:3px;font-size:.78rem;color:var(--muted);line-height:1.6;">
        🛡&nbsp; <strong style="color:var(--text);">Buyer Protection</strong><br/>
        Full refund if item not received or not as described.
      </div>
    </div>
  </div>

</div><!-- /main -->

<script>
// ── Live Card Preview ──────────────────────────────
const nameInput   = document.getElementById('card_name');
const numInput    = document.getElementById('card_number');
const expiryInput = document.getElementById('expiry');

const previewName   = document.getElementById('previewName');
const previewNumber = document.getElementById('previewNumber');
const previewExpiry = document.getElementById('previewExpiry');

nameInput?.addEventListener('input', () => {
  previewName.textContent = nameInput.value.toUpperCase() || 'YOUR NAME';
});

numInput?.addEventListener('input', () => {
  // Format with spaces every 4 digits
  let v = numInput.value.replace(/\D/g, '').substring(0, 16);
  let formatted = v.replace(/(.{4})/g, '$1 ').trim();
  numInput.value = formatted;

  let display = v.padEnd(16, '•');
  previewNumber.textContent =
    display.substring(0,4) + '  ' +
    display.substring(4,8) + '  ' +
    display.substring(8,12) + '  ' +
    display.substring(12,16);
});

expiryInput?.addEventListener('input', () => {
  let v = expiryInput.value.replace(/\D/g, '').substring(0,4);
  if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2);
  expiryInput.value = v;
  previewExpiry.textContent = v || 'MM/YY';
});
</script>
</body>
</html>