<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('includes/config.php');

// check login
$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;


if ($isLoggedIn && isset($_POST['ajax_update_qty'])) {

    header('Content-Type: application/json');

    $cart_id = intval($_POST['cart_id']);
    $qty = intval($_POST['qty']);

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
            'status' => 'stock_error',
            'message' => 'Only ' . $info['stock'] . ' item(s) available in stock.',
            'max' => $info['stock']
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
    $newTotal = floatval($row['total']);
    // Shipping is calculated on payment page based on state
    // Return subtotal only; grand total shown on payment page
    $newShipping = null;
    if (isset($_SESSION['user_id'])) {
        $shpStmt2 = $dbh->prepare("SELECT sr.fee FROM tbluser u LEFT JOIN tbl_shipping_rate sr ON sr.state_id = u.state_id WHERE u.user_id = ?");
        $shpStmt2->execute([$_SESSION['user_id']]);
        $shpRow2 = $shpStmt2->fetch(PDO::FETCH_ASSOC);
        $newShipping = ($shpRow2 && $shpRow2['fee']) ? floatval($shpRow2['fee']) : null;
    }
    $newGrand = $newShipping !== null ? $newTotal + $newShipping : $newTotal;

    // Recompute original subtotal and build discount after qty change
    $ajaxOriginal = 0.00;
    $ajaxCartItems = $dbh->prepare("SELECT c.product_id, c.product_price, c.quantity, p.price as db_price FROM tblcart c JOIN products p ON p.product_id = c.product_id WHERE c.user_id = ? AND c.status = 'active'");
    $ajaxCartItems->execute([$user_id]);
    $ajaxRows = $ajaxCartItems->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ajaxRows as $r) {
        $ajaxOriginal += floatval($r['db_price']) * intval($r['quantity']);
    }
    $ajaxBuildDisc = max(0, $ajaxOriginal - $newTotal);
    $ajaxAssembly  = 0.00;
    $ajaxAStmt = $dbh->prepare("SELECT assembly_fee FROM tbl_pc_build WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $ajaxAStmt->execute([$user_id]);
    $ajaxARow = $ajaxAStmt->fetch(PDO::FETCH_ASSOC);
    if ($ajaxARow) $ajaxAssembly = floatval($ajaxARow['assembly_fee']);

    echo json_encode([
        'status'           => 'success',
        'item_subtotal'    => number_format($info['product_price'] * $qty, 2),
        'original_subtotal'=> number_format($ajaxOriginal, 2),
        'subtotal'         => number_format($newTotal, 2),
        'build_discount'   => number_format($ajaxBuildDisc, 2),
        'assembly_fee'     => number_format($ajaxAssembly, 2),
        'estimated_total'  => number_format(max(0, $newTotal + $ajaxAssembly), 2),
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

// ── Sync cart prices with current product prices (skip PC Build items) ──
if ($isLoggedIn && !empty($cartItems)) {
    foreach ($cartItems as $item) {
        $pSync = $dbh->prepare("SELECT price FROM products WHERE product_id = ?");
        $pSync->execute([$item['product_id']]);
        $pRow = $pSync->fetch(PDO::FETCH_ASSOC);
        if ($pRow) {
            $dbPrice   = floatval($pRow['price']);
            $cartPrice = floatval($item['product_price']);

            if ($cartPrice !== $dbPrice) {
                // Check if this cart item came from a PC build (price locked at discount)
                $buildCheck = $dbh->prepare("
                    SELECT COUNT(*) FROM tbl_pc_build_item bi
                    JOIN tbl_pc_build b ON b.build_id = bi.build_id
                    WHERE bi.product_id = ? AND b.user_id = ? AND b.status = 'ordered'
                ");
                $buildCheck->execute([$item['product_id'], $user_id]);
                $fromBuild = $buildCheck->fetchColumn() > 0;

                if (!$fromBuild) {
                    // Normal cart item — sync to latest DB price
                    $dbh->prepare("
                        UPDATE tblcart
                        SET product_price = ?,
                            subtotal      = ? * quantity
                        WHERE cart_id = ?
                    ")->execute([$dbPrice, $dbPrice, $item['cart_id']]);
                }
                // PC Build item — leave price unchanged (discount locked at order time)
            }
        }
    }

    // Re-fetch cart after sync
    $stmt = $dbh->prepare("
        SELECT * FROM tblcart
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['product_price'] * $item['quantity'];
    }
}

// ── Detect PC Build discounts and Assembly discount in cart ──
// We compare product_price in cart vs actual product price in DB.
// If product_price < DB price → it was added via PC Builder (discounted).
// Assembly fee: tbl_pc_build.assembly_fee for the latest draft/ordered build by this user.
$originalSubtotal  = 0.00;  // sum of full DB prices × qty
$buildDiscountAmt  = 0.00;  // total saved from PC Build tier discount
$assemblyFeeAmt    = 0.00;  // 0 (assembled) or -25 (not assembled)

if ($isLoggedIn && !empty($cartItems)) {
    foreach ($cartItems as $item) {
        // Fetch DB price
        $pStmt = $dbh->prepare("SELECT price FROM products WHERE product_id = ?");
        $pStmt->execute([$item['product_id']]);
        $pRow  = $pStmt->fetch(PDO::FETCH_ASSOC);
        $dbPrice = $pRow ? floatval($pRow['price']) : floatval($item['product_price']);
        $originalSubtotal += $dbPrice * $item['quantity'];
    }
    $buildDiscountAmt = $originalSubtotal - $total; // positive = saving
    if ($buildDiscountAmt < 0.005) $buildDiscountAmt = 0.00; // float noise guard

    // Fetch latest PC build assembly fee for this user
    $aStmt = $dbh->prepare("
        SELECT assembly_fee FROM tbl_pc_build
        WHERE user_id = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $aStmt->execute([$user_id]);
    $aRow = $aStmt->fetch(PDO::FETCH_ASSOC);
    if ($aRow) {
        $assemblyFeeAmt = floatval($aRow['assembly_fee']); // 0.00 or -25.00
    }
}
$cartEstimatedTotal = $total + $assemblyFeeAmt; // before shipping

// Fetch user's state shipping fee for cart preview
$cartShippingFee = null;
$cartStateName   = '';
if ($isLoggedIn && $user_id) {
    $shpStmt = $dbh->prepare("
        SELECT sr.fee, s.state_name
        FROM tbluser u
        LEFT JOIN tbl_shipping_rate sr ON sr.state_id = u.state_id
        LEFT JOIN tblstate s ON s.state_id = u.state_id
        WHERE u.user_id = ?
    ");
    $shpStmt->execute([$user_id]);
    $shpRow = $shpStmt->fetch(PDO::FETCH_ASSOC);
    if ($shpRow && $shpRow['fee']) {
        $cartShippingFee = floatval($shpRow['fee']);
        $cartStateName   = $shpRow['state_name'];
    }
}

// Fetch stock and product status for each cart item
$stockMap  = [];
$statusMap = []; // cart_id → true (active) / false (inactive)
if ($isLoggedIn && !empty($cartItems)) {
    foreach ($cartItems as $item) {
        $s = $dbh->prepare("SELECT stock, status FROM products WHERE product_id = ?");
        $s->execute([$item['product_id']]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        $stockMap[$item['cart_id']]  = $row ? intval($row['stock']) : 0;
        $statusMap[$item['cart_id']] = $row ? strtolower($row['status']) === 'active' : false;
    }
}

// Check if any item is unavailable (for disabling checkout button)
$hasUnavailable = in_array(false, $statusMap, true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cart - PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap"
        rel="stylesheet">
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

        .qty-btn:hover:not(:disabled) {
            background: #d4af37;
            color: #1a1a1a;
        }

        .qty-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

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
        .qty-display::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }

        .qty-display[type=number] {
            -moz-appearance: textfield;
        }

        .stock-warning {
            font-size: 11px;
            color: #ff6b6b;
            margin-top: 4px;
            display: none;
        }

        /* ── Unavailable item styling ── */
        .item-unavailable {
            opacity: 0.55;
            pointer-events: none;
        }

        .unavailable-badge {
            display: inline-block;
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.35);
            color: #dc3545;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            pointer-events: none;
        }

        .item-unavailable .qty-control { pointer-events: none; }

        /* ══ ORDER SUMMARY PANEL ══════════════════════════════════ */
        .summary-panel {
            background: #111;
            border: 1px solid #222;
            border-radius: 16px;
            overflow: hidden;
        }

        .sp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 16px;
            border-bottom: 1px solid #1c1c1c;
        }

        .sp-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.2px;
        }

        .sp-count {
            font-size: 0.75rem;
            color: #555;
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 20px;
            padding: 3px 10px;
        }

        /* Item list */
        .sp-items {
            padding: 12px 20px;
            border-bottom: 1px solid #1c1c1c;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 280px;
            overflow-y: auto;
        }

        .sp-items::-webkit-scrollbar { width: 4px; }
        .sp-items::-webkit-scrollbar-track { background: transparent; }
        .sp-items::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 4px; }

        .sp-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sp-item-img-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .sp-item-img-wrap img {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #1e1e1e;
            display: block;
        }

        .sp-item-qty {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #d4af37;
            color: #000;
            font-size: 0.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .sp-item-name {
            flex: 1;
            font-size: 0.78rem;
            color: #ccc;
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .sp-item-price {
            font-size: 0.8rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Totals */
        .sp-totals {
            padding: 16px 20px 4px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sp-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.83rem;
            color: #888;
        }

        .sp-shipping-note {
            font-size: 0.75rem;
            color: #444;
            font-style: italic;
        }

        .sp-divider {
            height: 1px;
            background: #1c1c1c;
            margin: 14px 20px;
        }

        /* Grand total */
        .sp-grand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px 20px;
        }

        .sp-grand-label {
            font-size: 0.88rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }

        .sp-grand-sub {
            font-size: 0.68rem;
            color: #444;
        }

        .sp-grand-amount {
            font-size: 1.25rem;
            font-weight: 800;
            color: #d4af37;
            letter-spacing: -0.3px;
        }

        /* Checkout button */
        .sp-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 16px 12px;
            padding: 14px 20px;
            background: linear-gradient(135deg, #d4af37 0%, #b8942a 100%);
            color: #000;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .2s, transform .15s;
        }

        .sp-btn:hover {
            opacity: .9;
            transform: translateY(-1px);
            color: #000;
        }

        .sp-btn-disabled {
            background: #1e1e1e;
            color: #444;
            cursor: not-allowed;
        }

        .sp-btn-disabled:hover {
            transform: none;
            opacity: 1;
        }

        /* Trust signals */
        .sp-trust {
            display: flex;
            justify-content: space-around;
            padding: 12px 16px 16px;
            border-top: 1px solid #1a1a1a;
        }

        .sp-trust span {
            font-size: 0.68rem;
            color: #444;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sp-trust i {
            color: #555;
            font-size: 0.72rem;
        }
        /* Breakdown rows */
        .sp-row-label { color: #888; font-size: 0.82rem; }
        .sp-row-value { font-size: 0.82rem; color: #ccc; text-align:right; }
        .sp-row-value.green   { color: #4caf50; font-weight: 600; }
        .sp-row-value.red     { color: #ff6b6b; font-weight: 600; }
        .sp-row-value.muted   { color: #555; font-style: italic; font-size:0.74rem; }
        .sp-row-value.orig    { color: #555; text-decoration: line-through; font-size:0.76rem; }
        .sp-separator { height:1px; background:#1c1c1c; margin:10px 20px; }
        .sp-grand-row {
            display:flex; justify-content:space-between; align-items:center;
            padding: 12px 20px 4px;
        }
        .sp-grand-row-label { font-size:0.9rem; font-weight:700; color:#fff; }
        .sp-grand-row-amount {
            font-size:1.28rem; font-weight:800; color:#d4af37;
            font-family:'Playfair Display',serif;
        }
        .sp-grand-row-sub { font-size:0.68rem; color:#444; padding:0 20px 10px; text-align:right; }
        /* ══ END SUMMARY PANEL ════════════════════════════════════ */

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

                <?php if (!$isLoggedIn): ?>

                    <div class="dark-card p-5 text-center">
                        <i class="fa fa-user-lock fa-3x mb-3" style="color:#d4af37;"></i>
                        <h3>Login Required</h3>
                        <p class="text-soft">Please login to view your shopping cart.</p>
                        <a href="login.php" class="btn-cta mt-3">Login Now</a>
                    </div>

                <?php else: ?>

                    <?php if (empty($cartItems)): ?>

                        <div class="dark-card p-5 text-center">
                            <i class="fa fa-shopping-cart fa-3x mb-3" style="color:#d4af37;"></i>
                            <h3>Your Cart is Empty</h3>
                            <p class="text-soft">Start adding premium PC products now.</p>
                            <a href="product.php" class="btn-cta">Continue Shopping</a>
                        </div>

                    <?php else: ?>

                        <?php foreach ($cartItems as $item):
                            $maxStock   = $stockMap[$item['cart_id']] ?? 99;
                            $isActive   = $statusMap[$item['cart_id']] ?? true;
                            ?>

                            <div class="info-card mb-3 p3 <?php echo !$isActive ? 'item-unavailable' : ''; ?>">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                                    <!-- IMAGE + NAME -->
                                    <div class="d-flex align-items-center gap-3" style="min-width:250px;">
                                        <img src="<?php echo $item['product_image']; ?>"
                                            style="width:80px;height:80px;object-fit:cover;border-radius:8px;<?php echo !$isActive ? 'filter:grayscale(1);' : ''; ?>">
                                        <div>
                                            <h6 class="mb-1"><?php echo $item['product_name']; ?></h6>
                                            <small class="text-soft">
                                                RM <?php echo number_format($item['product_price'], 2); ?> each
                                            </small>
                                            <?php if (!$isActive): ?>
                                            <div class="unavailable-badge">
                                                <i class="fa fa-ban me-1"></i>Unavailable
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- QTY CONTROL -->
                                    <div>
                                        <div class="qty-control" data-cart-id="<?php echo $item['cart_id']; ?>"
                                            data-price="<?php echo $item['product_price']; ?>" data-max="<?php echo $maxStock; ?>">
                                            <button class="qty-btn btn-minus"
                                                <?php echo ($item['quantity'] <= 1 || !$isActive) ? 'disabled' : ''; ?>>
                                                &minus;
                                            </button>

                                            <input type="number" class="qty-display" value="<?php echo $item['quantity']; ?>"
                                                min="1" max="<?php echo $maxStock; ?>"
                                                <?php echo !$isActive ? 'disabled' : ''; ?>>
                                            <button class="qty-btn btn-plus"
                                                <?php echo ($item['quantity'] >= $maxStock || !$isActive) ? 'disabled' : ''; ?>>
                                                &plus;
                                            </button>
                                        </div>
                                        <div class="stock-warning" id="warn-<?php echo $item['cart_id']; ?>">
                                            Max stock: <?php echo $maxStock; ?>
                                        </div>
                                    </div>

                                    <!-- SUBTOTAL + DELETE -->
                                    <div class="d-flex align-items-center gap-3">
                                        <strong class="item-subtotal" style="color:<?php echo !$isActive ? '#555' : '#d4af37'; ?>;">
                                            RM <?php echo number_format($item['product_price'] * $item['quantity'], 2); ?>
                                        </strong>
                                        <a href="cart.php?remove=<?php echo $item['cart_id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           style="pointer-events:all;">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>

                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <!-- RIGHT: ORDER SUMMARY -->
            <div class="col-lg-4">
                <?php $itemCount = array_sum(array_column($cartItems, 'quantity')); ?>

                <div class="summary-panel sticky-top" style="top:100px;">

                    <!-- Header -->
                    <div class="sp-header">
                        <span class="sp-title">Order Summary</span>
                        <?php if (!empty($cartItems)): ?>
                        <span class="sp-count"><?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($cartItems)): ?>

                    <!-- Item list (compact) -->
                    <div class="sp-items">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="sp-item">
                            <div class="sp-item-img-wrap">
                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>"
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     style="<?php echo !($statusMap[$item['cart_id']] ?? true) ? 'filter:grayscale(1);opacity:0.5;' : ''; ?>">
                                <span class="sp-item-qty"><?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="sp-item-name" style="<?php echo !($statusMap[$item['cart_id']] ?? true) ? 'color:#555;' : ''; ?>">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                                <?php if (!($statusMap[$item['cart_id']] ?? true)): ?>
                                <span style="color:#dc3545;font-size:0.65rem;display:block;">Unavailable</span>
                                <?php endif; ?>
                            </div>
                            <div class="sp-item-price" style="<?php echo !($statusMap[$item['cart_id']] ?? true) ? 'color:#555;' : ''; ?>">
                                RM&nbsp;<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totals breakdown -->
                    <div class="sp-totals">

                        <!-- Original subtotal -->
                        <div class="sp-row">
                            <span class="sp-row-label">Original Subtotal</span>
                            <span class="sp-row-value <?php echo $buildDiscountAmt > 0 ? 'orig' : ''; ?>"
                                  id="sp-original-subtotal">
                                RM <?php echo number_format($originalSubtotal > 0 ? $originalSubtotal : $total, 2); ?>
                            </span>
                        </div>

                        <!-- PC Build Discount (only if > 0) -->
                        <?php if ($buildDiscountAmt > 0): ?>
                        <div class="sp-row" id="sp-build-disc-row">
                            <span class="sp-row-label">
                                <i class="fa fa-tag me-1" style="color:#4caf50;font-size:0.7rem;"></i>
                                PC Build Discount
                            </span>
                            <span class="sp-row-value green" id="sp-build-disc">
                                − RM <?php echo number_format($buildDiscountAmt, 2); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Discounted subtotal (after build disc) -->
                        <?php if ($buildDiscountAmt > 0): ?>
                        <div class="sp-row" style="border-top:1px solid #1c1c1c;padding-top:8px;margin-top:4px;">
                            <span class="sp-row-label">Subtotal after Discount</span>
                            <span class="sp-row-value" id="summary-subtotal">
                                RM <?php echo number_format($total, 2); ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <div class="sp-row" id="sp-subtotal-row" style="display:none;">
                            <span class="sp-row-label">Subtotal</span>
                            <span class="sp-row-value" id="summary-subtotal">
                                RM <?php echo number_format($total, 2); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Shipping note -->
                        <div class="sp-row">
                            <span class="sp-row-label">Shipping</span>
                            <span class="sp-row-value muted">Calculated at checkout</span>
                        </div>

                    </div>

                    <div class="sp-separator"></div>

                    <!-- Estimated Total -->
                    <div class="sp-grand-row">
                        <span class="sp-grand-row-label">Estimated Total</span>
                        <span class="sp-grand-row-amount" id="summary-total">
                            RM <?php echo number_format(max(0, $cartEstimatedTotal), 2); ?>
                        </span>
                    </div>
                    <div class="sp-grand-row-sub">+ Shipping calculated at checkout</div>

                    <?php endif; ?>

                    <!-- Checkout button -->
                    <?php if (!$isLoggedIn): ?>
                        <a href="login.php" class="sp-btn">Login to Checkout</a>
                    <?php elseif (empty($cartItems)): ?>
                        <button class="sp-btn sp-btn-disabled" disabled>Your cart is empty</button>
                    <?php elseif ($hasUnavailable): ?>
                        <button class="sp-btn sp-btn-disabled" disabled>
                            <i class="fa fa-ban me-2"></i>Remove unavailable items
                        </button>
                    <?php else: ?>
                        <a href="payment.php" class="sp-btn">
                            <i class="fa fa-lock me-2"></i>Proceed to Checkout
                        </a>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script>
        document.querySelectorAll('.qty-control').forEach(function (control) {

            const cartId = control.dataset.cartId;
            const price = parseFloat(control.dataset.price);
            const maxStock = parseInt(control.dataset.max);

            const minusBtn = control.querySelector('.btn-minus');
            const plusBtn = control.querySelector('.btn-plus');
            const qtyInput = control.querySelector('.qty-display');
            const itemSubtotal = control.closest('.info-card').querySelector('.item-subtotal');
            const warning = document.getElementById('warn-' + cartId);

            // Skip unavailable items
            if (qtyInput.disabled) return;

            let debounceTimer = null;

            function updateButtons(qty) {
                minusBtn.disabled = qty <= 1;
                plusBtn.disabled = qty >= maxStock;
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

                            // Original subtotal
                            const origEl = document.getElementById('sp-original-subtotal');
                            if (origEl) origEl.textContent = 'RM ' + data.original_subtotal;

                            // Subtotal after discount
                            const subEl = document.getElementById('summary-subtotal');
                            if (subEl) subEl.textContent = 'RM ' + data.subtotal;

                            // Build discount row
                            const discRow = document.getElementById('sp-build-disc-row');
                            const discVal = document.getElementById('sp-build-disc');
                            if (discRow && discVal) {
                                if (parseFloat(data.build_discount) > 0) {
                                    discRow.style.display = '';
                                    discVal.textContent = '− RM ' + data.build_discount;
                                } else {
                                    discRow.style.display = 'none';
                                }
                            }

                            // Assembly fee
                            const asmVal = document.getElementById('sp-assembly-val');
                            if (asmVal) {
                                const aFee = parseFloat(data.assembly_fee);
                                if (aFee < 0) {
                                    asmVal.textContent = '− RM ' + Math.abs(aFee).toFixed(2);
                                    asmVal.className = 'sp-row-value red';
                                } else {
                                    asmVal.textContent = 'FREE';
                                    asmVal.className = 'sp-row-value green';
                                }
                            }

                            // Estimated total
                            document.getElementById('summary-total').textContent = 'RM ' + data.estimated_total;
                        } else if (data.status === 'stock_error') {
                            qtyInput.value = data.max;
                            updateButtons(data.max);
                            warning.style.display = 'block';
                            warning.textContent = 'Only ' + data.max + ' item(s) in stock!';
                            sendUpdate(data.max);
                        }
                    });
            }

            minusBtn.addEventListener('click', function () {
                let qty = parseInt(qtyInput.value);
                if (qty > 1) { qty--; qtyInput.value = qty; updateButtons(qty); sendUpdate(qty); }
            });

            plusBtn.addEventListener('click', function () {
                let qty = parseInt(qtyInput.value);
                if (qty < maxStock) { qty++; qtyInput.value = qty; updateButtons(qty); sendUpdate(qty); }
            });

            qtyInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
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