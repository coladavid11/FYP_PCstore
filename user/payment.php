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

$cart = $_SESSION['cart'] ?? [];

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
<style>
/* ── RESET & BASE ───────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --gold:    #f5c518;
  --gold-dk: #c9a20e;
  --red:     #c0392b;
  --bg:      #0a0a0f;
  --surface: #111118;
  --card:    #16161f;
  --border:  rgba(245,197,24,.18);
  --text:    #e8e8f0;
  --muted:   #6b6b82;
  --success: #27ae60;
  --error:   #e74c3c;
  --mono:    'Share Tech Mono', monospace;
  --display: 'Bebas Neue', sans-serif;
  --body:    'Barlow', sans-serif;
  --glow:    0 0 24px rgba(245,197,24,.25);
}

html { font-size: 16px; }

body {
  font-family: var(--body);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  background-image:
    radial-gradient(ellipse 80% 50% at 50% -10%, rgba(192,57,43,.18) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 80% 80%,  rgba(245,197,24,.07) 0%, transparent 60%);
}

/* ── NOISE OVERLAY ──────────────────────────────── */
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
}

/* ── NAVBAR ─────────────────────────────────────── */
.navbar {
  position: sticky; top: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 3rem;
  height: 64px;
  background: rgba(10,10,15,.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
}

.nav-logo {
  font-family: var(--display);
  font-size: 1.6rem;
  color: var(--gold);
  letter-spacing: .05em;
  display: flex; align-items: center; gap: .5rem;
  text-decoration: none;
}
.nav-logo span { color: var(--text); }

.nav-links { display: flex; gap: 2rem; list-style: none; }
.nav-links a {
  font-size: .85rem; font-weight: 500; letter-spacing: .08em;
  text-transform: uppercase; color: var(--muted);
  text-decoration: none; transition: color .2s;
}
.nav-links a:hover { color: var(--gold); }

.nav-badge {
  font-size: .75rem; font-weight: 700; letter-spacing: .1em;
  padding: .3rem .9rem;
  border: 1.5px solid var(--gold);
  color: var(--gold); background: transparent;
  text-transform: uppercase; cursor: pointer;
  transition: background .2s, color .2s;
}
.nav-badge:hover { background: var(--gold); color: #000; }

/* ── BREADCRUMB ─────────────────────────────────── */
.breadcrumb {
  padding: .9rem 3rem;
  font-size: .78rem; color: var(--muted);
  letter-spacing: .06em; text-transform: uppercase;
  border-bottom: 1px solid rgba(255,255,255,.04);
}
.breadcrumb span { color: var(--gold); }

/* ── MAIN LAYOUT ────────────────────────────────── */
.main {
  position: relative; z-index: 1;
  max-width: 1200px; margin: 0 auto;
  padding: 3rem 2rem 5rem;
  display: grid;
  grid-template-columns: 1fr 420px;
  gap: 2.5rem;
  align-items: start;
}

/* ── SECTION HEADING ────────────────────────────── */
.section-heading {
  font-family: var(--display);
  font-size: 2rem; letter-spacing: .06em;
  color: var(--text);
  margin-bottom: 1.5rem;
  display: flex; align-items: center; gap: .75rem;
}
.section-heading::before {
  content: '';
  display: inline-block; width: 4px; height: 1.8rem;
  background: var(--gold);
  border-radius: 2px;
}

/* ── PANELS ─────────────────────────────────────── */
.panel {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 2rem;
  position: relative;
  overflow: hidden;
}
.panel::after {
  content: '';
  position: absolute; top: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--red), var(--gold));
}

/* ── CREDIT CARD VISUAL ─────────────────────────── */
.card-visual {
  width: 100%; max-width: 360px; height: 210px;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  border-radius: 16px;
  border: 1px solid rgba(245,197,24,.2);
  padding: 1.5rem;
  margin: 0 auto 2rem;
  position: relative;
  box-shadow: 0 20px 60px rgba(0,0,0,.5), var(--glow);
  display: flex; flex-direction: column; justify-content: space-between;
  transition: transform .3s;
}
.card-visual:hover { transform: translateY(-4px) rotateX(3deg); }

.card-chip {
  width: 40px; height: 30px;
  background: linear-gradient(135deg, #d4a843, #f5c518, #c9a20e);
  border-radius: 4px;
  display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr;
  gap: 2px; padding: 4px;
}
.card-chip div { background: rgba(0,0,0,.2); border-radius: 1px; }

.card-logo {
  font-family: var(--display);
  font-size: 1.2rem; color: var(--gold); letter-spacing: .1em;
  align-self: flex-end;
}

.card-number-display {
  font-family: var(--mono);
  font-size: 1.1rem; letter-spacing: .2em;
  color: rgba(255,255,255,.9);
  text-align: center;
}

.card-meta {
  display: flex; justify-content: space-between; align-items: flex-end;
}
.card-meta-label {
  font-size: .6rem; color: var(--muted); letter-spacing: .12em;
  text-transform: uppercase; margin-bottom: .2rem;
}
.card-meta-val {
  font-family: var(--mono); font-size: .9rem;
  color: rgba(255,255,255,.9);
}

/* ── FORM ───────────────────────────────────────── */
.form-grid { display: grid; gap: 1.2rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }

.field { display: flex; flex-direction: column; gap: .45rem; }

label {
  font-size: .72rem; font-weight: 700; letter-spacing: .12em;
  text-transform: uppercase; color: var(--muted);
}

input[type="text"], input[type="tel"] {
  font-family: var(--mono);
  font-size: .95rem;
  padding: .75rem 1rem;
  background: var(--surface);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 3px;
  color: var(--text);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  letter-spacing: .05em;
}
input[type="text"]:focus,
input[type="tel"]:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(245,197,24,.1);
}
input::placeholder { color: var(--muted); font-size: .85rem; }

/* ── CARD TYPE ICONS ────────────────────────────── */
.card-types {
  display: flex; gap: .5rem; align-items: center;
  padding: .6rem .75rem;
  background: var(--surface);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 3px;
}
.card-type-badge {
  font-size: .65rem; font-weight: 700; letter-spacing: .08em;
  padding: .25rem .6rem; border-radius: 3px;
  text-transform: uppercase;
}
.badge-visa   { background: #1a1f71; color: #fff; }
.badge-mc     { background: #eb001b; color: #fff; }
.badge-amex   { background: #007bc1; color: #fff; }
.card-types-label { font-size: .75rem; color: var(--muted); margin-left: auto; }

/* ── SECURE ROW ─────────────────────────────────── */
.secure-row {
  display: flex; align-items: center; gap: .5rem;
  font-size: .75rem; color: var(--muted); letter-spacing: .06em;
  margin-top: .25rem;
}
.lock-icon { color: var(--gold); font-size: 1rem; }

/* ── PAY BUTTON ─────────────────────────────────── */
.pay-btn {
  width: 100%; padding: 1rem;
  font-family: var(--display);
  font-size: 1.4rem; letter-spacing: .12em;
  background: linear-gradient(90deg, var(--red), #e74c3c);
  color: #fff; border: none; cursor: pointer;
  border-radius: 3px;
  position: relative; overflow: hidden;
  transition: transform .15s, box-shadow .2s;
  margin-top: .5rem;
}
.pay-btn::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.1), transparent);
  transform: translateX(-100%);
  transition: transform .5s;
}
.pay-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(192,57,43,.4); }
.pay-btn:hover::before { transform: translateX(100%); }
.pay-btn:active { transform: translateY(0); }

/* ── ORDER SUMMARY ──────────────────────────────── */
.order-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: .9rem 0;
  border-bottom: 1px solid rgba(255,255,255,.05);
}
.order-item:last-child { border-bottom: none; }

.item-name { font-size: .9rem; font-weight: 500; }
.item-qty  { font-size: .75rem; color: var(--muted); margin-top: .15rem; }
.item-price {
  font-family: var(--mono); font-size: .95rem;
  color: var(--gold);
}

.order-divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: .75rem 0; }

.summary-row {
  display: flex; justify-content: space-between;
  font-size: .85rem; color: var(--muted);
  padding: .3rem 0;
}
.summary-row.total {
  font-family: var(--display);
  font-size: 1.5rem; letter-spacing: .06em;
  color: var(--text);
  padding-top: .75rem;
  margin-top: .25rem;
  border-top: 1px solid var(--border);
}
.summary-row.total .val { color: var(--gold); }

/* ── AMOUNT INPUT ───────────────────────────────── */
.amount-display {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 3px;
  padding: .9rem 1rem;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.2rem;
}
.amount-label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; }
.amount-val {
  font-family: var(--mono); font-size: 1.4rem; color: var(--gold); font-weight: 700;
}

/* ── ALERTS ─────────────────────────────────────── */
.alert {
  padding: 1rem 1.2rem; border-radius: 3px;
  font-size: .9rem; font-weight: 500;
  display: flex; align-items: flex-start; gap: .75rem;
  margin-bottom: 1.5rem;
  border-left: 3px solid;
}
.alert-success { background: rgba(39,174,96,.1); border-color: var(--success); color: #2ecc71; }
.alert-error   { background: rgba(231,76,60,.1);  border-color: var(--error);   color: #e74c3c; }
.alert-icon    { font-size: 1.1rem; margin-top: .05rem; }

.demo-badge {
  display: inline-block;
  font-size: .65rem; font-weight: 700; letter-spacing: .12em;
  text-transform: uppercase;
  padding: .2rem .6rem;
  background: rgba(245,197,24,.15);
  border: 1px solid rgba(245,197,24,.4);
  color: var(--gold);
  border-radius: 2px;
  margin-left: .5rem;
  vertical-align: middle;
}

/* ── SUCCESS SCREEN ─────────────────────────────── */
.success-screen {
  text-align: center; padding: 2rem 1rem;
}
.success-icon {
  width: 72px; height: 72px;
  background: rgba(39,174,96,.15);
  border: 2px solid var(--success);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; margin: 0 auto 1.5rem;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(39,174,96,.3); }
  50%      { box-shadow: 0 0 0 16px rgba(39,174,96,0); }
}

.success-title {
  font-family: var(--display);
  font-size: 2.2rem; letter-spacing: .06em;
  color: var(--success); margin-bottom: .5rem;
}
.success-ref {
  font-family: var(--mono); font-size: .85rem; color: var(--muted);
  margin-bottom: 1.5rem;
}
.success-amount {
  font-family: var(--display); font-size: 3rem;
  color: var(--gold); letter-spacing: .04em;
  margin-bottom: .5rem;
}
.success-demo-note {
  font-size: .8rem; color: var(--muted); margin-bottom: 1.5rem;
}

/* ── RESPONSIVE ─────────────────────────────────── */
@media (max-width: 860px) {
  .main { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .navbar { padding: 0 1.2rem; }
  .nav-links { display: none; }
  .breadcrumb { padding: .75rem 1.2rem; }
  .main { padding: 2rem 1rem 3rem; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">🖥&nbsp;<span>MY PC</span> STORE</a>
  <ul class="nav-links">
    <li><a href="index">Home</a></li>
    <li><a href="#">Find Computers</a></li>
    <li><a href="about.php">About Us</a></li>
    <li><a href="contact.php">Contact Us</a></li>
  </ul>
  <button class="nav-badge"><a href="login.php" class="btn-cta">Login</a></button>
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
        <a href="index.php" style="display:inline-block;padding:.7rem 2rem;background:var(--gold);color:#000;font-family:var(--display);font-size:1.1rem;letter-spacing:.1em;text-decoration:none;border-radius:3px;margin-top:.5rem;">BACK TO STORE</a>
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