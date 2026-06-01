<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('includes/config.php');

// check login
$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

// =======================
// AJAX: UPDATE QUANTITY
// =======================
if ($isLoggedIn && isset($_POST['ajax_update_qty'])) {

    header('Content-Type: application/json');

    $cart_id = intval($_POST['cart_id']);
    $qty     = intval($_POST['qty']);

    if ($qty <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Quantity must be at least 1.']);
        exit();
    }

    // Check stock
    $stmtCheck = $dbh->prepare("
        SELECT c.product_id, c.product_price, p.stock
        FROM tblcart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.cart_id = ? AND c.user_id = ?
    ");
    $stmtCheck->execute([$cart_id, $user_id]);
    $info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
        exit();
    }

    if ($qty > $info['stock']) {
        echo json_encode([
            'status'  => 'stock_error',
            'message' => 'Only ' . $info['stock'] . ' item(s) available in stock.',
            'max'     => $info['stock']
        ]);
        exit();
    }

    // Update DB
    $stmt = $dbh->prepare("
        UPDATE tblcart 
        SET quantity = ?,
            subtotal = product_price * ?
        WHERE cart_id = ? AND user_id = ?
    ");
    $stmt->execute([$qty, $qty, $cart_id, $user_id]);

    // Recalculate totals
    $stmtTotal = $dbh->prepare("
        SELECT SUM(product_price * quantity) as total
        FROM tblcart
        WHERE user_id = ? AND status = 'active'
    ");
    $stmtTotal->execute([$user_id]);
    $row = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $newTotal   = floatval($row['total']);
    $shipping   = 15.00;
    $grandTotal = $newTotal + $shipping;

    echo json_encode([
        'status'        => 'success',
        'item_subtotal' => number_format($info['product_price'] * $qty, 2),
        'subtotal'      => number_format($newTotal, 2),
        'grand_total'   => number_format($grandTotal, 2),
    ]);
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

// Fetch stock for each cart item
$stockMap = [];
if ($isLoggedIn && !empty($cartItems)) {
    foreach ($cartItems as $item) {
        $s = $dbh->prepare("SELECT stock FROM products WHERE product_id = ?");
        $s->execute([$item['product_id']]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        $stockMap[$item['cart_id']] = $row ? intval($row['stock']) : 0;
    }
}

// =======================
// FETCH ORDER HISTORY
// =======================
$orderHistory = [];
if ($isLoggedIn) {
    $oStmt = $dbh->prepare("
        SELECT o.*, 
               COUNT(oi.order_id) as item_count
        FROM tblorders o
        LEFT JOIN tblorder_item oi ON o.order_id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
    ");
    $oStmt->execute([$user_id]);
    $orderHistory = $oStmt->fetchAll(PDO::FETCH_ASSOC);
}

function orderStatusConfig(string $status): array {
    return match(strtolower($status)) {
        'processing' => ['label'=>'Processing', 'color'=>'#ffc107', 'bg'=>'rgba(255,193,7,0.1)',   'icon'=>'fa-clock'],
        'packed'     => ['label'=>'Packed',     'color'=>'#17a2b8', 'bg'=>'rgba(23,162,184,0.1)',  'icon'=>'fa-box'],
        'shipped'    => ['label'=>'Shipped',    'color'=>'#007bff', 'bg'=>'rgba(0,123,255,0.1)',   'icon'=>'fa-truck'],
        'completed'  => ['label'=>'Completed',  'color'=>'#28a745', 'bg'=>'rgba(40,167,69,0.1)',   'icon'=>'fa-check-circle'],
        'cancelled'  => ['label'=>'Cancelled',  'color'=>'#dc3545', 'bg'=>'rgba(220,53,69,0.1)',   'icon'=>'fa-times-circle'],
        default      => ['label'=>ucfirst($status),'color'=>'#aaa', 'bg'=>'rgba(170,170,170,0.1)', 'icon'=>'fa-circle'],
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart - PC Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="newstyle.css">

<style>
/* ── Shopee-style qty controls ── */
.qty-control {
    display: flex;
    align-items: center;
    border: 1px solid #3a3a3a;
    border-radius: 6px;
    overflow: hidden;
    width: fit-content;
}
.qty-btn {
    background: #2a2a2a;
    border: none;
    color: #d4af37;
    width: 34px;
    height: 34px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.qty-btn:hover:not(:disabled) { background: #d4af37; color: #1a1a1a; }
.qty-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.qty-display {
    width: 46px;
    text-align: center;
    background: #1a1a1a;
    color: #fff;
    border: none;
    border-left: 1px solid #3a3a3a;
    border-right: 1px solid #3a3a3a;
    font-size: 14px;
    height: 34px;
    outline: none;
}
.qty-display::-webkit-outer-spin-button,
.qty-display::-webkit-inner-spin-button { -webkit-appearance: none; }
.qty-display[type=number] { -moz-appearance: textfield; }
.stock-warning {
    font-size: 11px;
    color: #ff6b6b;
    margin-top: 4px;
    display: none;
}

/* ── ORDER HISTORY ── */
.history-section {
    margin-top: 40px;
}
.history-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    color: #fff;
    margin-bottom: 6px;
}
.history-subtitle {
    font-size: 0.78rem;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 20px;
}
.order-row {
    background: #121212;
    border: 1px solid #1e1e1e;
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 10px;
    transition: border-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    text-decoration: none;
}
.order-row:hover { border-color: #d4af37; }
.order-num {
    font-size: 0.88rem;
    font-weight: 700;
    color: #d4af37;
    margin-bottom: 2px;
}
.order-date {
    font-size: 0.75rem;
    color: #555;
}
.order-items-count {
    font-size: 0.78rem;
    color: #666;
}
.order-total {
    font-size: 1rem;
    font-weight: 700;
    color: #d4af37;
}
.order-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
    border: 1px solid;
}
.view-all-btn {
    display: block;
    text-align: center;
    padding: 10px;
    background: transparent;
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    color: #555;
    font-size: 0.8rem;
    text-decoration: none;
    margin-top: 8px;
    transition: all 0.2s;
}
.view-all-btn:hover { border-color: #d4af37; color: #d4af37; }
</style>
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="container py-5">

<div class="row g-4">

<!-- ===================== -->
<!-- LEFT: CART ITEMS      -->
<!-- ===================== -->
<div class="col-lg-8">

<?php if(!$isLoggedIn): ?>

    <div class="dark-card p-5 text-center">
        <i class="fa fa-user-lock fa-3x mb-3" style="color:#d4af37;"></i>
        <h3>Login Required</h3>
        <p class="text-soft">Please login to view your shopping cart.</p>
        <a href="login.php" class="btn-cta mt-3">Login Now</a>
    </div>

<?php else: ?>

    <?php if(empty($cartItems)): ?>

        <div class="dark-card p-5 text-center">
            <i class="fa fa-shopping-cart fa-3x mb-3" style="color:#d4af37;"></i>
            <h3>Your Cart is Empty</h3>
            <p class="text-soft">Start adding premium PC products now.</p>
            <a href="product.php" class="btn-cta">Continue Shopping</a>
        </div>

    <?php else: ?>

        <?php foreach($cartItems as $item):
            $maxStock = $stockMap[$item['cart_id']] ?? 99;
        ?>

        <div class="info-card mb-3 p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                <!-- IMAGE + NAME -->
                <div class="d-flex align-items-center gap-3" style="min-width:250px;">
                    <img src="<?php echo $item['product_image']; ?>"
                         style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
                    <div>
                        <h6 class="mb-1"><?php echo $item['product_name']; ?></h6>
                        <small class="text-soft">
                            RM <?php echo number_format($item['product_price'],2); ?> each
                        </small>
                    </div>
                </div>

                <!-- QTY CONTROL -->
                <div>
                    <div class="qty-control"
                         data-cart-id="<?php echo $item['cart_id']; ?>"
                         data-price="<?php echo $item['product_price']; ?>"
                         data-max="<?php echo $maxStock; ?>">
                        <button class="qty-btn btn-minus"
                                <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                            &minus;
                        </button>
                        <input type="number"
                               class="qty-display"
                               value="<?php echo $item['quantity']; ?>"
                               min="1"
                               max="<?php echo $maxStock; ?>">
                        <button class="qty-btn btn-plus"
                                <?php echo $item['quantity'] >= $maxStock ? 'disabled' : ''; ?>>
                            &plus;
                        </button>
                    </div>
                    <div class="stock-warning" id="warn-<?php echo $item['cart_id']; ?>">
                        Max stock: <?php echo $maxStock; ?>
                    </div>
                </div>

                <!-- SUBTOTAL + DELETE -->
                <div class="d-flex align-items-center gap-3">
                    <strong class="item-subtotal" style="color:#d4af37;">
                        RM <?php echo number_format($item['product_price'] * $item['quantity'], 2); ?>
                    </strong>
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

<!-- ===================== -->
<!-- ORDER HISTORY SECTION -->
<!-- ===================== -->
<?php if ($isLoggedIn): ?>
<div class="history-section">

    <div class="history-title">Order History</div>
    <div class="history-subtitle">Your recent purchases</div>

    <?php if (empty($orderHistory)): ?>
        <div class="dark-card p-4 text-center">
            <i class="fa fa-box-open fa-2x mb-2" style="color:#2a2a2a;"></i>
            <p class="text-soft mb-0">No orders yet.</p>
        </div>
    <?php else: ?>

        <?php foreach($orderHistory as $order):
            $cfg = orderStatusConfig($order['order_status']);
        ?>
        <a href="myorder_detail.php?id=<?php echo $order['order_id']; ?>" class="order-row">

            <!-- Left: order number + date -->
            <div>
                <div class="order-num"><?php echo htmlspecialchars($order['order_number']); ?></div>
                <div class="order-date">
                    <i class="fa fa-calendar me-1"></i>
                    <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                </div>
            </div>

            <!-- Middle: item count -->
            <div class="order-items-count">
                <i class="fa fa-box me-1"></i>
                <?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?>
            </div>

            <!-- Status pill -->
            <span class="order-status-pill"
                  style="color:<?php echo $cfg['color']; ?>; background:<?php echo $cfg['bg']; ?>; border-color:<?php echo $cfg['color']; ?>55;">
                <i class="fa <?php echo $cfg['icon']; ?>"></i>
                <?php echo $cfg['label']; ?>
            </span>

            <!-- Total -->
            <div class="order-total">
                RM <?php echo number_format($order['grand_total'], 2); ?>
            </div>

            <!-- Arrow -->
            <i class="fa fa-chevron-right" style="color:#333; font-size:0.8rem;"></i>

        </a>
        <?php endforeach; ?>

        <a href="myorder.php" class="view-all-btn">
            View All Orders <i class="fa fa-arrow-right ms-1"></i>
        </a>

    <?php endif; ?>

</div>
<?php endif; ?>

</div>

<!-- ===================== -->
<!-- RIGHT: ORDER SUMMARY  -->
<!-- ===================== -->
<div class="col-lg-4">
<?php
$shipping    = 15.00;
$service_fee = $total * 0.03;
$grand_total = $total + $shipping + $service_fee;
?>
<div class="stat-card p-4 sticky-top" style="top:100px;">

    <h4>Order Summary</h4>
    <hr style="border-color:#2a2a2a;">

    <div class="d-flex justify-content-between mb-2">
        <span class="text-soft">Subtotal</span>
        <span id="summary-subtotal">RM <?php echo number_format($total,2); ?></span>
    </div>
    <div class="d-flex justify-content-between mb-2">
        <span class="text-soft">SST (3%)</span>
        <span>RM <?php echo number_format($service_fee,2); ?></span>
    </div>
    <div class="d-flex justify-content-between mb-3">
        <span class="text-soft">Shipping</span>
        <span>RM <?php echo number_format($shipping,2); ?></span>
    </div>

    <hr style="border-color:#2a2a2a;">

    <div class="d-flex justify-content-between mb-3">
        <strong>Total</strong>
        <strong id="summary-total" style="color:#d4af37;">
            RM <?php echo number_format($grand_total,2); ?>
        </strong>
    </div>

    <?php if(!$isLoggedIn): ?>
        <a href="login.php" class="btn-gold">Login to Checkout</a>
    <?php elseif(empty($cartItems)): ?>
        <button class="btn-gold" onclick="alert('Your cart is empty!')">Proceed Checkout</button>
    <?php elseif($total < 0): ?>
        <button class="btn-gold" onclick="alert('Error: Invalid cart total!')">Proceed Checkout</button>
    <?php else: ?>
        <a href="payment.php" class="btn-gold">Proceed Checkout</a>
    <?php endif; ?>

</div>
</div>

</div>
</div>

<?php include('includes/footer.php'); ?>

<script>
document.querySelectorAll('.qty-control').forEach(function(control) {

    const cartId    = control.dataset.cartId;
    const price     = parseFloat(control.dataset.price);
    const maxStock  = parseInt(control.dataset.max);

    const minusBtn     = control.querySelector('.btn-minus');
    const plusBtn      = control.querySelector('.btn-plus');
    const qtyInput     = control.querySelector('.qty-display');
    const itemSubtotal = control.closest('.info-card').querySelector('.item-subtotal');
    const warning      = document.getElementById('warn-' + cartId);

    let debounceTimer = null;

    function updateButtons(qty) {
        minusBtn.disabled = qty <= 1;
        plusBtn.disabled  = qty >= maxStock;
        warning.style.display = qty >= maxStock ? 'block' : 'none';
    }

    function sendUpdate(qty) {
        const formData = new FormData();
        formData.append('ajax_update_qty', '1');
        formData.append('cart_id', cartId);
        formData.append('qty', qty);

        fetch('cart.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                itemSubtotal.textContent = 'RM ' + data.item_subtotal;
                document.getElementById('summary-subtotal').textContent = 'RM ' + data.subtotal;
                document.getElementById('summary-total').textContent    = 'RM ' + data.grand_total;
            } else if (data.status === 'stock_error') {
                qtyInput.value = data.max;
                updateButtons(data.max);
                warning.style.display = 'block';
                warning.textContent   = 'Only ' + data.max + ' item(s) in stock!';
                sendUpdate(data.max);
            }
        });
    }

    minusBtn.addEventListener('click', function() {
        let qty = parseInt(qtyInput.value);
        if (qty > 1) { qty--; qtyInput.value = qty; updateButtons(qty); sendUpdate(qty); }
    });

    plusBtn.addEventListener('click', function() {
        let qty = parseInt(qtyInput.value);
        if (qty < maxStock) { qty++; qtyInput.value = qty; updateButtons(qty); sendUpdate(qty); }
    });

    qtyInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            let qty = parseInt(qtyInput.value);
            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > maxStock) qty = maxStock;
            qtyInput.value = qty;
            updateButtons(qty);
            sendUpdate(qty);
        }, 600);
    });

    updateButtons(parseInt(qtyInput.value));
});
</script>

</body>
</html>