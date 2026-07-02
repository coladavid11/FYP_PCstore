<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id    = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || !$user_id) {
    header('Location: login.php');
    exit;
}

// ── Detect source: pcbuild or cart ───────────────────────────
$source    = $_GET['source']   ?? 'cart';
$build_id  = intval($_GET['build_id'] ?? 0);
$isPCBuild = ($source === 'pcbuild' && $build_id > 0);

// ── Fetch user profile (for default address) ──────────────────
$uStmt = $dbh->prepare("
    SELECT u.*, s.state_name, sr.fee AS shipping_fee
    FROM tbluser u
    LEFT JOIN tblstate s  ON s.state_id  = u.state_id
    LEFT JOIN tbl_shipping_rate sr ON sr.state_id = u.state_id
    WHERE u.user_id = ?
");
$uStmt->execute([$user_id]);
$userProfile = $uStmt->fetch(PDO::FETCH_ASSOC);

// ── Fetch all states + shipping fees for JS ───────────────────
$stateStmt = $dbh->query("
    SELECT s.state_id, s.state_name, COALESCE(sr.fee, 15) AS fee
    FROM tblstate s
    LEFT JOIN tbl_shipping_rate sr ON sr.state_id = s.state_id
    ORDER BY s.state_id
");
$states        = $stateStmt->fetchAll(PDO::FETCH_ASSOC);
$shippingRates = [];
foreach ($states as $s) {
    $shippingRates[$s['state_id']] = floatval($s['fee']);
}

// ── PC Build flow: fetch build + items ────────────────────────
$buildData        = null;
$buildItems       = [];
$cartItems        = [];
$subtotal         = 0;
$originalSubtotal = 0.00;
$buildDiscountAmt = 0.00;
$assemblyFeeAmt   = 0.00;
$assemblyIsService= true;

if ($isPCBuild) {
    // Fetch build record (must belong to this user)
    $bStmt = $dbh->prepare("
        SELECT * FROM tbl_pc_build
        WHERE build_id = ? AND user_id = ? AND status = 'draft'
        LIMIT 1
    ");
    $bStmt->execute([$build_id, $user_id]);
    $buildData = $bStmt->fetch(PDO::FETCH_ASSOC);

    if (!$buildData) {
        // Invalid or already paid build — redirect away
        header('Location: pcbuild.php');
        exit;
    }

    // Fetch build items
    $biStmt = $dbh->prepare("
        SELECT bi.*, p.image
        FROM tbl_pc_build_item bi
        LEFT JOIN products p ON p.product_id = bi.product_id
        WHERE bi.build_id = ?
    ");
    $biStmt->execute([$build_id]);
    $buildItems = $biStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals from build record
    $originalSubtotal  = floatval($buildData['subtotal']);
    $buildDiscountAmt  = floatval($buildData['discount_amt']);
    $subtotal          = $originalSubtotal - $buildDiscountAmt; // discounted parts total
    $assemblyFeeAmt    = floatval($buildData['assembly_fee']);   // 0.00 or -25.00
    $assemblyIsService = (bool)$buildData['assembly_service'];

} else {
    // ── Cart flow: original logic unchanged ───────────────────
    $stmt = $dbh->prepare("SELECT * FROM tblcart WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['product_price'] * $item['quantity'];
    }

    if (!empty($cartItems)) {
        foreach ($cartItems as $item) {
            $pStmt = $dbh->prepare("SELECT price FROM products WHERE product_id = ?");
            $pStmt->execute([$item['product_id']]);
            $pRow    = $pStmt->fetch(PDO::FETCH_ASSOC);
            $dbPrice = $pRow ? floatval($pRow['price']) : floatval($item['product_price']);
            $originalSubtotal += $dbPrice * $item['quantity'];
        }
        $buildDiscountAmt = max(0, $originalSubtotal - $subtotal);

        $aStmt = $dbh->prepare("SELECT assembly_service, assembly_fee FROM tbl_pc_build WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $aStmt->execute([$user_id]);
        $aRow = $aStmt->fetch(PDO::FETCH_ASSOC);
        if ($aRow) {
            $assemblyFeeAmt    = floatval($aRow['assembly_fee']);
            $assemblyIsService = (bool)$aRow['assembly_service'];
        }
    }
}

// Default shipping based on user's state
$defaultShipping = floatval($userProfile['shipping_fee'] ?? 15.00);

$success     = false;
$error       = '';
$grand_total = 0;

// ── Process payment ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $isEmpty = $isPCBuild ? empty($buildItems) : empty($cartItems);

    if ($isEmpty) {
        $error = $isPCBuild ? "No build items found." : "Your cart is empty.";

    } else {

        $useProfile = isset($_POST['use_profile_address']) && $_POST['use_profile_address'] === '1';
        $cardHolder = trim($_POST['card_holder_name'] ?? '');

        // ── Address: take from profile hidden fields or custom form fields ──
        if ($useProfile) {
            $receiverName  = trim($_POST['h_receiver_name']  ?? '');
            $receiverPhone = trim($_POST['h_receiver_phone'] ?? '');
            $addr1         = trim($_POST['h_addr_line1']     ?? '');
            $addr2         = trim($_POST['h_addr_line2']     ?? '');
            $postcode      = trim($_POST['h_postcode']       ?? '');
            $city          = trim($_POST['h_city']           ?? '');
            $state_id      = intval($_POST['h_state_id']    ?? 0);
        } else {
            $receiverName  = trim($_POST['receiver_name']  ?? '');
            $receiverPhone = trim($_POST['receiver_phone'] ?? '');
            $addr1         = trim($_POST['addr_line1']     ?? '');
            $addr2         = trim($_POST['addr_line2']     ?? '');
            $postcode      = trim($_POST['postcode']       ?? '');
            $city          = trim($_POST['city']           ?? '');
            $state_id      = intval($_POST['state_id']    ?? 0);
        }

        if (empty($cardHolder)) {
            $error = "Please enter the card holder name.";

        } elseif (empty($receiverName) || empty($receiverPhone) || empty($addr1) ||
                  empty($postcode)     || empty($city)           || $state_id <= 0) {
            $error = "Please fill in all delivery address fields.";

        } elseif (!preg_match('/^[0-9]{5}$/', $postcode)) {
            $error = "Postcode must be exactly 5 digits.";

        } else {
            $feeStmt = $dbh->prepare("SELECT fee FROM tbl_shipping_rate WHERE state_id = ?");
            $feeStmt->execute([$state_id]);
            $feeRow      = $feeStmt->fetch(PDO::FETCH_ASSOC);
            $shipping    = $feeRow ? floatval($feeRow['fee']) : 15.00;
            $service_fee = 0.00;
            $grand_total = $subtotal + $assemblyFeeAmt + $shipping + $service_fee;
            $grand_total = max(0, $grand_total);

            try {
                $dbh->beginTransaction();

                if ($isPCBuild) {
                    // ── PC Build checkout ─────────────────────
                    // 1. Stock check
                    $stockCheck = $dbh->prepare("SELECT product_id, stock FROM products WHERE product_id = ? FOR UPDATE");
                    foreach ($buildItems as $item) {
                        $stockCheck->execute([$item['product_id']]);
                        $stockRow = $stockCheck->fetch(PDO::FETCH_ASSOC);
                        if (!$stockRow || $stockRow['stock'] < 1) {
                            throw new Exception("'{$item['product_name']}' is out of stock.");
                        }
                    }

                    // 2. Insert order
                    $order_number = 'PC' . date('Ymd') . strtoupper(substr(uniqid(), -5));
                    $orderStmt = $dbh->prepare("
                        INSERT INTO tblorders
                            (user_id, order_number, total_amount, shipping_fee, service_fee,
                             grand_total, payment_method, card_holder_name, payment_status, order_status)
                        VALUES (?, ?, ?, ?, ?, ?, 'Demo Card', ?, 'paid', 'processing')
                    ");
                    $orderStmt->execute([
                        $user_id, $order_number, $subtotal, $shipping,
                        $service_fee, $grand_total, $cardHolder
                    ]);
                    $order_id = $dbh->lastInsertId();

                    // 3. Insert order items from build items
                    $itemStmt = $dbh->prepare("
                        INSERT INTO tblorder_item
                            (order_id, user_id, product_id, product_name, product_price, quantity, subtotal)
                        VALUES (?, ?, ?, ?, ?, 1, ?)
                    ");
                    foreach ($buildItems as $item) {
                        $itemStmt->execute([
                            $order_id, $user_id, $item['product_id'],
                            $item['product_name'], $item['final_price'], $item['final_price']
                        ]);
                    }

                    // 4. Insert delivery address
                    $addrStmt = $dbh->prepare("
                        INSERT INTO tbl_order_address
                            (order_id, receiver_name, phone, addr_line1, addr_line2,
                             postcode, city, state_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $addrStmt->execute([
                        $order_id, $receiverName, $receiverPhone,
                        $addr1, $addr2, $postcode, $city, $state_id
                    ]);

                    // 5. Mark build as ordered (paid)
                    $dbh->prepare("UPDATE tbl_pc_build SET status = 'ordered' WHERE build_id = ?")
                        ->execute([$build_id]);

                    // 6. Deduct stock
                    $stockStmt = $dbh->prepare("UPDATE products SET stock = stock - 1 WHERE product_id = ? AND stock >= 1");
                    foreach ($buildItems as $item) {
                        $stockStmt->execute([$item['product_id']]);
                        if ($stockStmt->rowCount() === 0) {
                            throw new Exception("{$item['product_name']} stock not enough.");
                        }
                    }

                } else {
                    // ── Cart checkout (original logic unchanged) ──
                    // 1. Stock check
                    $stockCheck = $dbh->prepare("SELECT product_id, stock FROM products WHERE product_id = ? FOR UPDATE");
                    foreach ($cartItems as $item) {
                        $stockCheck->execute([$item['product_id']]);
                        $stockRow = $stockCheck->fetch(PDO::FETCH_ASSOC);
                        if (!$stockRow || $stockRow['stock'] < $item['quantity']) {
                            throw new Exception(
                                "'{$item['product_name']}' is out of stock. " .
                                "Available: " . ($stockRow['stock'] ?? 0) .
                                ", Requested: " . $item['quantity']
                            );
                        }
                    }

                    // 2. Insert order
                    $order_number = 'PC' . date('Ymd') . strtoupper(substr(uniqid(), -5));
                    $orderStmt = $dbh->prepare("
                        INSERT INTO tblorders
                            (user_id, order_number, total_amount, shipping_fee, service_fee,
                             grand_total, payment_method, card_holder_name, payment_status, order_status)
                        VALUES (?, ?, ?, ?, ?, ?, 'Demo Card', ?, 'paid', 'processing')
                    ");
                    $orderStmt->execute([
                        $user_id, $order_number, $subtotal, $shipping,
                        $service_fee, $grand_total, $cardHolder
                    ]);
                    $order_id = $dbh->lastInsertId();

                    // 3. Insert order items
                    $itemStmt = $dbh->prepare("
                        INSERT INTO tblorder_item
                            (order_id, user_id, product_id, product_name, product_price, quantity, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    foreach ($cartItems as $item) {
                        $itemStmt->execute([
                            $order_id, $user_id, $item['product_id'],
                            $item['product_name'], $item['product_price'],
                            $item['quantity'], $item['product_price'] * $item['quantity']
                        ]);
                    }

                    // 4. Insert delivery address
                    $addrStmt = $dbh->prepare("
                        INSERT INTO tbl_order_address
                            (order_id, receiver_name, phone, addr_line1, addr_line2,
                             postcode, city, state_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $addrStmt->execute([
                        $order_id, $receiverName, $receiverPhone,
                        $addr1, $addr2, $postcode, $city, $state_id
                    ]);

                    // 5. Mark cart as ordered
                    $dbh->prepare("UPDATE tblcart SET status = 'ordered' WHERE user_id = ? AND status = 'active'")
                        ->execute([$user_id]);

                    // 6. Deduct stock
                    $stockStmt = $dbh->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
                    foreach ($cartItems as $item) {
                        $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                        if ($stockStmt->rowCount() === 0) {
                            throw new Exception("{$item['product_name']} stock not enough.");
                        }
                    }
                }

                $dbh->commit();
                $success    = true;
                $cartItems  = [];
                $buildItems = [];

            } catch (Exception $e) {
                $dbh->rollBack();
                $error = "Payment failed: " . $e->getMessage();
            }
        }
    }
}

// Items to display in summary panel
$displayItems = $isPCBuild ? $buildItems : $cartItems;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - My PC Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        .section-heading {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #d4af37;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            background: #121212;
            border: 1px solid #2a2a2a;
            color: #fff;
            border-radius: 4px;
        }
        .form-control:focus, .form-select:focus {
            background: #121212;
            color: #fff;
            border-color: #d4af37;
            box-shadow: none;
        }
        .form-control::placeholder { color: #555; }
        .form-select option { background: #1a1a1a; }
        .form-label { color: #aaa; font-size: 0.83rem; margin-bottom: 4px; }

        /* Address toggle */
        .addr-card {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .profile-addr-display {
            background: #0f0f0f;
            border: 1px solid #1e1e1e;
            border-radius: 6px;
            padding: 14px 16px;
            font-size: 0.85rem;
            color: #aaa;
            line-height: 1.7;
        }

        .profile-addr-display .addr-name {
            font-weight: 700;
            color: #fff;
            font-size: 0.92rem;
        }

        .profile-addr-display .addr-phone {
            color: #d4af37;
            font-size: 0.82rem;
        }

        /* Custom checkbox */
        .use-profile-check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #161616;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 16px;
            transition: border-color .2s;
        }
        .use-profile-check:hover { border-color: #d4af37; }
        .use-profile-check input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: #d4af37;
            cursor: pointer;
        }
        .use-profile-check label {
            cursor: pointer;
            font-size: 0.85rem;
            color: #ccc;
            margin: 0;
        }

        /* Card input */
        .card-field {
            background: #121212;
            border: 1px solid #2a2a2a;
            color: #fff;
            border-radius: 4px;
            padding: 10px 14px;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color .2s;
        }
        .card-field:focus { border-color: #d4af37; }
        .card-field::placeholder { color: #555; }

        /* Expiry invalid state */
        .card-field.is-invalid { border-color: #dc3545 !important; }
        .expiry-error {
            font-size: 0.75rem;
            color: #dc3545;
            margin-top: 4px;
            display: none;
        }

        /* Shipping info badge */
        .shipping-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #0d1a0d;
            border: 1px solid #1a3a1a;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.83rem;
            color: #7ecb7e;
            margin-top: 8px;
        }

        /* PC Build badge */
        .pcbuild-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(212,175,55,0.06);
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.82rem;
            color: #d4af37;
            margin-bottom: 16px;
        }

        /* Summary */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 9px 0;
            border-bottom: 1px solid #1a1a1a;
            font-size: 0.85rem;
            color: #888;
            gap: 8px;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row.grand {
            font-size: 1rem;
            font-weight: 700;
            color: #d4af37;
            padding-top: 14px;
        }
        .summary-row .sr-label { flex: 1; }
        .summary-row .sr-label small {
            display: block;
            font-size: 0.7rem;
            color: #555;
            margin-top: 1px;
        }
        .summary-row .sr-value { white-space: nowrap; text-align: right; }
        .summary-row .sr-value.green            { color: #4caf50; font-weight: 600; }
        .summary-row .sr-value.red              { color: #ff6b6b; font-weight: 600; }
        .summary-row .sr-value.gold             { color: #d4af37; font-weight: 600; }
        .summary-row .sr-value.muted            { color: #555; font-style: italic; font-size: 0.76rem; }
        .summary-row .sr-value.sr-strikethrough { text-decoration: line-through; color: #555; font-size: 0.78rem; }

        /* item list in payment summary */
        .pmt-items {
            max-height: 220px;
            overflow-y: auto;
            margin-bottom: 12px;
            padding-right: 2px;
        }
        .pmt-items::-webkit-scrollbar { width: 3px; }
        .pmt-items::-webkit-scrollbar-thumb { background: #2a2a2a; }
        .pmt-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 0;
            border-bottom: 1px solid #161616;
            font-size: 0.8rem;
        }
        .pmt-item:last-child { border-bottom: none; }
        .pmt-item img {
            width: 38px; height: 38px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #1e1e1e;
            flex-shrink: 0;
        }
        .pmt-item-name {
            flex: 1;
            color: #aaa;
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .pmt-item-qty {
            font-size: 0.7rem;
            color: #555;
            display: block;
        }
        .pmt-item-price {
            font-size: 0.82rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
        }

        .btn-pay {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            font-weight: 700;
            border: none;
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: opacity .2s;
            border-radius: 4px;
            margin-top: 16px;
        }
        .btn-pay:hover { opacity: .88; }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container py-5">

<?php if ($success): ?>
<!-- ══ SUCCESS ══════════════════════════════════════════════ -->
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="stat-card p-5 text-center">
    <i class="fa fa-check-circle fa-3x mb-3" style="color:#1fd719;"></i>
    <h3>Payment Successful!</h3>
    <p class="text-soft">Your order has been placed.</p>
    <h4 style="color:#d4af37;" class="mb-4">
        RM <?php echo number_format($grand_total, 2); ?>
    </h4>
    <div class="d-flex gap-3 justify-content-center">
        <a href="index.php"   class="btn-cta">Back to Store</a>
        <a href="myorder.php" class="btn-cta">My Orders</a>
    </div>
</div>
</div>
</div>

<?php else: ?>
<!-- ══ CHECKOUT FORM ════════════════════════════════════════ -->

<?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- LEFT: Delivery + Payment -->
    <div class="col-lg-7">

        <form method="POST" id="checkoutForm">
            <?php if ($isPCBuild): ?>
            <input type="hidden" name="source"   value="pcbuild">
            <input type="hidden" name="build_id" value="<?php echo $build_id; ?>">
            <?php endif; ?>

            <!-- ── Delivery Address ───────────────────────── -->
            <div class="addr-card">
                <div class="section-heading">
                    <i class="fa fa-location-dot"></i> Delivery Address
                </div>

                <?php if ($isPCBuild): ?>
                <div class="pcbuild-badge">
                    <i class="fa fa-screwdriver-wrench"></i>
                    PC Build Order — <?php echo count($buildItems); ?> part(s)
                    <?php if ($assemblyIsService): ?>
                    · <span style="color:#4caf50;">Assembled &amp; delivered</span>
                    <?php else: ?>
                    · <span style="color:#ff6b6b;">Unassembled parts</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Use Profile Address checkbox -->
                <label class="use-profile-check" for="useProfileChk">
                    <input type="checkbox" id="useProfileChk" name="use_profile_address"
                           value="1" checked onchange="toggleAddressForm(this.checked)">
                    <span>
                        <label for="useProfileChk" style="cursor:pointer;">
                            Use my profile address
                        </label>
                        <small class="d-block" style="color:#555;font-size:0.75rem;">
                            Uncheck to enter a different delivery address
                        </small>
                    </span>
                </label>

                <?php
                $profState = $userProfile['state_name'] ?? '';
                $profAddr  = array_filter([
                    $userProfile['addr_line1'] ?? '',
                    $userProfile['addr_line2'] ?? '',
                    ($userProfile['postcode'] ?? '') . ' ' . ($userProfile['city'] ?? ''),
                    $profState
                ]);
                $profAddrStr = implode(', ', $profAddr);
                ?>

                <!-- Profile address preview (shown when checkbox checked) -->
                <div id="profileAddrPreview">
                    <?php if ($profAddrStr): ?>
                    <div class="profile-addr-display">
                        <div class="addr-name"><?php echo htmlentities($userProfile['fullname']); ?></div>
                        <div class="addr-phone"><?php echo htmlentities($userProfile['phone_num']); ?></div>
                        <div><?php echo htmlentities($profAddrStr); ?></div>
                    </div>
                    <?php else: ?>
                    <div class="profile-addr-display" style="color:#666;">
                        <i class="fa fa-triangle-exclamation me-2" style="color:#d4af37;"></i>
                        No address saved in your profile.
                        <a href="myprofile.php" style="color:#d4af37;">Update profile</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Editable address form (hidden by default) -->
                <div id="customAddrForm" style="display:none;">
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Receiver Name <span class="text-danger">*</span></label>
                            <input type="text" name="receiver_name" id="receiverName"
                                   class="form-control" placeholder="Full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="receiver_phone" id="receiverPhone"
                                   class="form-control" placeholder="012-3456789" maxlength="12">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address Line 1 <span class="text-danger">*</span>
                                <small style="color:#555;">(Street, House No.)</small>
                            </label>
                            <input type="text" name="addr_line1" id="addrLine1Custom"
                                   class="form-control" placeholder="e.g. No. 12, Jalan Harmoni 3">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address Line 2
                                <small style="color:#555;">(Optional)</small>
                            </label>
                            <input type="text" name="addr_line2" id="addrLine2Custom"
                                   class="form-control" placeholder="e.g. Taman Lagenda Putra">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Postcode <span class="text-danger">*</span></label>
                            <input type="text" name="postcode" id="postcodeCustom"
                                   class="form-control" maxlength="5" placeholder="e.g. 81000">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" id="cityCustom"
                                   class="form-control" placeholder="e.g. Kulai">
                        </div>
                        <div class="col-12">
                            <label class="form-label">State <span class="text-danger">*</span></label>
                            <select name="state_id" id="stateSelectCustom"
                                    class="form-select" onchange="updateShipping(this.value)">
                                <option value="">— Select State —</option>
                                <?php foreach ($states as $s): ?>
                                    <option value="<?php echo $s['state_id']; ?>">
                                        <?php echo htmlentities($s['state_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="shipping-badge" id="shippingBadgeCustom" style="display:none;">
                                <i class="fa fa-truck"></i>
                                <span id="shippingTextCustom"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields for profile address (use h_ prefix to avoid name clash) -->
                <input type="hidden" name="h_receiver_name"  id="hiddenReceiverName"
                       value="<?php echo htmlentities($userProfile['fullname'] ?? ''); ?>">
                <input type="hidden" name="h_receiver_phone" id="hiddenReceiverPhone"
                       value="<?php echo htmlentities($userProfile['phone_num'] ?? ''); ?>">
                <input type="hidden" name="h_addr_line1"     id="hiddenAddr1"
                       value="<?php echo htmlentities($userProfile['addr_line1'] ?? ''); ?>">
                <input type="hidden" name="h_addr_line2"     id="hiddenAddr2"
                       value="<?php echo htmlentities($userProfile['addr_line2'] ?? ''); ?>">
                <input type="hidden" name="h_postcode"       id="hiddenPostcode"
                       value="<?php echo htmlentities($userProfile['postcode'] ?? ''); ?>">
                <input type="hidden" name="h_city"           id="hiddenCity"
                       value="<?php echo htmlentities($userProfile['city'] ?? ''); ?>">
                <input type="hidden" name="h_state_id"       id="hiddenStateId"
                       value="<?php echo intval($userProfile['state_id'] ?? 0); ?>">
                <input type="hidden" name="use_profile_address" id="hiddenUseProfile" value="1">

            </div><!-- /addr-card -->

            <!-- ── Payment ───────────────────────────────── -->
            <div class="addr-card">
                <div class="section-heading">
                    <i class="fa fa-credit-card"></i> Card Payment
                </div>

                <div class="mb-3">
                    <label class="form-label">Card Holder Name</label>
                    <input type="text" name="card_holder_name" id="cardHolderName" class="card-field"
                           placeholder="Name as shown on card" maxlength="100" autocomplete="cc-name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Card Number</label>
                    <input type="text" id="cardNumber" class="card-field"
                           placeholder="0000 0000 0000 0000" maxlength="19" required>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Expiry</label>
                        <input type="text" id="expiry" class="card-field"
                               placeholder="MM/YY" maxlength="5" required>
                        <div class="expiry-error" id="expiryError"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">CVV</label>
                        <input type="text" id="cvv" class="card-field"
                               placeholder="123" maxlength="3" required>
                    </div>
                </div>

                <button type="submit" class="btn-pay" id="btnPay">
                    <i class="fa fa-lock me-2"></i>Pay RM <span id="payBtnTotal">
                        <?php echo number_format(max(0, $subtotal + $assemblyFeeAmt + $defaultShipping), 2); ?>
                    </span>
                </button>
            </div>

        </form>
    </div>

    <!-- RIGHT: Order Summary -->
    <div class="col-lg-5">
        <div class="stat-card p-4 sticky-top" style="top:100px;">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo $isPCBuild ? 'PC Build Summary' : 'Order Summary'; ?></h5>
                <span style="font-size:0.72rem;color:#555;background:#1a1a1a;border:1px solid #2a2a2a;border-radius:20px;padding:3px 10px;">
                    <?php echo count($displayItems); ?> item<?php echo count($displayItems) !== 1 ? 's' : ''; ?>
                </span>
            </div>

            <!-- Item list -->
            <?php if (!empty($displayItems)): ?>
            <div class="pmt-items">
                <?php foreach ($displayItems as $item):
                    $imgSrc   = htmlspecialchars($item['image'] ?? $item['product_image'] ?? '');
                    $itemName = htmlspecialchars($item['product_name']);
                    $itemPrice = $isPCBuild ? floatval($item['final_price']) : floatval($item['product_price'] * $item['quantity']);
                    $itemQty   = $isPCBuild ? 1 : $item['quantity'];
                ?>
                <div class="pmt-item">
                    <img src="<?php echo $imgSrc; ?>"
                         alt="<?php echo $itemName; ?>"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="pmt-item-name">
                        <?php echo $itemName; ?>
                        <?php if (!$isPCBuild): ?>
                        <span class="pmt-item-qty">× <?php echo $itemQty; ?></span>
                        <?php else: ?>
                        <span class="pmt-item-qty"><?php echo htmlspecialchars($item['part_key'] ?? ''); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="pmt-item-price">RM <?php echo number_format($itemPrice, 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <hr style="border-color:#1c1c1c;margin:10px 0;">

            <!-- Original Subtotal -->
            <div class="summary-row">
                <span class="sr-label">Original Subtotal</span>
                <span class="sr-value <?php echo $buildDiscountAmt > 0 ? 'sr-strikethrough' : ''; ?>">
                    RM <?php echo number_format($originalSubtotal > 0 ? $originalSubtotal : $subtotal, 2); ?>
                </span>
            </div>

            <!-- PC Build Discount -->
            <?php if ($buildDiscountAmt > 0): ?>
            <div class="summary-row">
                <span class="sr-label">
                    <i class="fa fa-tag me-1" style="color:#4caf50;font-size:0.72rem;"></i>
                    PC Build Discount
                    <?php if ($isPCBuild && $buildData['discount_pct'] > 0): ?>
                    <small><?php echo $buildData['discount_pct']; ?>% off</small>
                    <?php endif; ?>
                </span>
                <span class="sr-value green">− RM <?php echo number_format($buildDiscountAmt, 2); ?></span>
            </div>
            <div class="summary-row" style="border-top:1px solid #161616;padding-top:8px;margin-top:2px;">
                <span class="sr-label">Subtotal after Discount</span>
                <span class="sr-value">RM <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php else: ?>
            <div class="summary-row">
                <span class="sr-label">Subtotal</span>
                <span class="sr-value">RM <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php endif; ?>

            <!-- Assembly Service -->
            <div class="summary-row">
                <span class="sr-label">
                    <i class="fa fa-screwdriver-wrench me-1"
                       style="color:<?php echo $assemblyFeeAmt < 0 ? '#ff6b6b' : '#4caf50'; ?>;font-size:0.72rem;"></i>
                    Assembly Service
                </span>
                <?php if ($assemblyFeeAmt < 0): ?>
                    <span class="sr-value red">− RM <?php echo number_format(abs($assemblyFeeAmt), 2); ?></span>
                <?php else: ?>
                    <span class="sr-value green">FREE</span>
                <?php endif; ?>
            </div>

            <!-- Shipping -->
            <div class="summary-row" id="shippingRow">
                <span class="sr-label">
                    Shipping
                    <small id="shippingStateName" style="display:block;font-size:0.7rem;color:#555;margin-top:1px;"></small>
                </span>
                <span class="sr-value gold" id="summaryShipping">RM <?php echo number_format($defaultShipping, 2); ?></span>
            </div>

            <hr style="border-color:#1c1c1c;margin:10px 0;">

            <!-- Grand Total -->
            <div class="summary-row grand">
                <span class="sr-label" style="color:#fff;">Grand Total</span>
                <span class="sr-value" id="summaryTotal"
                      style="color:#d4af37;font-size:1.15rem;font-weight:800;">
                    RM <?php echo number_format(max(0, $subtotal + $assemblyFeeAmt + $defaultShipping), 2); ?>
                </span>
            </div>

            <!-- Delivery address summary -->
            <div id="addrSummary" style="margin-top:16px;padding:12px 14px;background:#0f0f0f;border:1px solid #1a1a1a;border-radius:6px;font-size:0.8rem;color:#aaa;line-height:1.7;">
                <div style="font-size:0.7rem;color:#555;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
                    <i class="fa fa-location-dot me-1" style="color:#d4af37;"></i> Delivering To
                </div>
                <div id="addrSummaryText">
                    <?php if ($profAddrStr): ?>
                        <strong style="color:#fff;"><?php echo htmlentities($userProfile['fullname']); ?></strong><br>
                        <?php echo htmlentities($userProfile['phone_num']); ?><br>
                        <?php echo htmlentities($profAddrStr); ?>
                    <?php else: ?>
                        <span style="color:#666;">No address saved.</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>
<?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Constants from PHP ────────────────────────────────────────
const SHIPPING_RATES    = <?php echo json_encode($shippingRates); ?>;
const SUBTOTAL          = <?php echo floatval($subtotal); ?>;
const ORIGINAL_SUBTOTAL = <?php echo floatval($originalSubtotal); ?>;
const BUILD_DISCOUNT    = <?php echo floatval($buildDiscountAmt); ?>;
const ASSEMBLY_FEE      = <?php echo floatval($assemblyFeeAmt); ?>; // 0 or -25

const PROFILE = {
    name:     <?php echo json_encode($userProfile['fullname']   ?? ''); ?>,
    phone:    <?php echo json_encode($userProfile['phone_num']  ?? ''); ?>,
    addr1:    <?php echo json_encode($userProfile['addr_line1'] ?? ''); ?>,
    addr2:    <?php echo json_encode($userProfile['addr_line2'] ?? ''); ?>,
    postcode: <?php echo json_encode($userProfile['postcode']   ?? ''); ?>,
    city:     <?php echo json_encode($userProfile['city']       ?? ''); ?>,
    stateId:  <?php echo intval($userProfile['state_id'] ?? 0); ?>,
    stateName:<?php echo json_encode($userProfile['state_name'] ?? ''); ?>,
    fee:      <?php echo floatval($userProfile['shipping_fee']  ?? 15); ?>
};

let currentShipping = PROFILE.fee;

document.addEventListener('DOMContentLoaded', () => {
    updateSummary(PROFILE.fee, PROFILE.stateName);
});

// ── Toggle address form ───────────────────────────────────────
function toggleAddressForm(useProfile) {
    document.getElementById('profileAddrPreview').style.display = useProfile ? 'block' : 'none';
    document.getElementById('customAddrForm').style.display     = useProfile ? 'none'  : 'block';
    document.getElementById('hiddenUseProfile').value           = useProfile ? '1' : '0';

    if (useProfile) {
        // Restore hidden profile fields
        document.getElementById('hiddenReceiverName').value  = PROFILE.name;
        document.getElementById('hiddenReceiverPhone').value = PROFILE.phone;
        document.getElementById('hiddenAddr1').value         = PROFILE.addr1;
        document.getElementById('hiddenAddr2').value         = PROFILE.addr2;
        document.getElementById('hiddenPostcode').value      = PROFILE.postcode;
        document.getElementById('hiddenCity').value          = PROFILE.city;
        document.getElementById('hiddenStateId').value       = PROFILE.stateId;

        updateSummary(PROFILE.fee, PROFILE.stateName);
        updateAddrSummary(PROFILE.name, PROFILE.phone,
            [PROFILE.addr1, PROFILE.addr2, PROFILE.postcode + ' ' + PROFILE.city, PROFILE.stateName]);

    } else {
        // Clear hidden fields (custom form fields have different names, no clash)
        document.getElementById('hiddenReceiverName').value  = '';
        document.getElementById('hiddenReceiverPhone').value = '';
        document.getElementById('hiddenAddr1').value         = '';
        document.getElementById('hiddenAddr2').value         = '';
        document.getElementById('hiddenPostcode').value      = '';
        document.getElementById('hiddenCity').value          = '';
        document.getElementById('hiddenStateId').value       = '';

        updateSummary(0, '');
        document.getElementById('addrSummaryText').innerHTML =
            '<span style="color:#666;">Fill in delivery address above.</span>';
    }
}

// ── Update shipping when state changes (custom form) ─────────
function updateShipping(stateId) {
    stateId = parseInt(stateId);
    if (!stateId || !SHIPPING_RATES[stateId]) {
        document.getElementById('shippingBadgeCustom').style.display = 'none';
        updateSummary(0, '');
        return;
    }

    const fee  = SHIPPING_RATES[stateId];
    const name = document.getElementById('stateSelectCustom').options[
        document.getElementById('stateSelectCustom').selectedIndex
    ].text;

    document.getElementById('shippingBadgeCustom').style.display = 'flex';
    document.getElementById('shippingTextCustom').textContent =
        `Shipping to ${name}: RM ${fee.toFixed(2)}`;

    updateSummary(fee, name);

    const name2  = document.getElementById('receiverName').value || '—';
    const phone2 = document.getElementById('receiverPhone').value || '—';
    const a1     = document.getElementById('addrLine1Custom').value;
    const a2     = document.getElementById('addrLine2Custom').value;
    const pc     = document.getElementById('postcodeCustom').value;
    const city   = document.getElementById('cityCustom').value;
    updateAddrSummary(name2, phone2, [a1, a2, pc + ' ' + city, name]);
}

// ── Update summary panel numbers ──────────────────────────────
function updateSummary(fee, stateName) {
    currentShipping = fee;
    const grandTotal = Math.max(0, SUBTOTAL + ASSEMBLY_FEE + fee);

    document.getElementById('summaryShipping').textContent =
        fee > 0 ? 'RM ' + fee.toFixed(2) : '—';
    document.getElementById('summaryTotal').textContent =
        fee > 0 ? 'RM ' + grandTotal.toFixed(2) : '—';
    document.getElementById('payBtnTotal').textContent =
        fee > 0 ? grandTotal.toFixed(2) : '—';

    const stateEl = document.getElementById('shippingStateName');
    if (stateEl) stateEl.textContent = stateName || '';
}

// ── Update "Delivering To" summary ───────────────────────────
function updateAddrSummary(name, phone, addrParts) {
    const parts = addrParts.filter(p => p && p.trim());
    document.getElementById('addrSummaryText').innerHTML =
        `<strong style="color:#fff;">${escHtml(name)}</strong><br>
         ${escHtml(phone)}<br>
         ${parts.map(escHtml).join(', ')}`;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Postcode: digits only ─────────────────────────────────────
document.getElementById('postcodeCustom').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g,'').slice(0,5);
});

// ── Phone auto-format ─────────────────────────────────────────
document.getElementById('receiverPhone').addEventListener('input', function () {
    let v = this.value.replace(/\D/g,'');
    if (v.length > 3) v = v.slice(0,3) + '-' + v.slice(3);
    this.value = v;
});

// ── Card number format ────────────────────────────────────────
document.getElementById('cardNumber').addEventListener('input', function () {
    let v = this.value.replace(/\D/g,'').slice(0,16);
    this.value = v.replace(/(\d{4})/g,'$1 ').trim();
});

// ── Expiry format + real-time month validation ────────────────
document.getElementById('expiry').addEventListener('input', function () {
    let v = this.value.replace(/\D/g,'').slice(0,4);
    if (v.length >= 3) v = v.slice(0,2) + '/' + v.slice(2);
    this.value = v;

    const errEl = document.getElementById('expiryError');
    const month = parseInt(v.slice(0,2), 10);

    if (v.length >= 2) {
        if (month < 1 || month > 12) {
            this.classList.add('is-invalid');
            errEl.textContent = 'Invalid month (01–12)';
            errEl.style.display = 'block';
        } else {
            this.classList.remove('is-invalid');
            errEl.style.display = 'none';
        }
    } else {
        this.classList.remove('is-invalid');
        errEl.style.display = 'none';
    }
});

// ── CVV ───────────────────────────────────────────────────────
document.getElementById('cvv').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g,'').slice(0,3);
});

// ── Form submit validation ────────────────────────────────────
document.getElementById('checkoutForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const holderName = document.getElementById('cardHolderName').value.trim();
    const card       = document.getElementById('cardNumber').value.replace(/\s/g,'');

    if (!holderName) {
        Swal.fire({ title:'Required', text:'Please enter the card holder name.',
            icon:'warning', background:'#1a1a1a', color:'#fff', iconColor:'#d4af37', confirmButtonColor:'#d4af37' });
        return;
    }

    const expiry = document.getElementById('expiry').value;
    const cvv    = document.getElementById('cvv').value;

    if (card.length !== 16) {
        Swal.fire({ title:'Invalid Card', text:'Card number must be exactly 16 digits.',
            icon:'error', background:'#1a1a1a', color:'#fff', confirmButtonColor:'#d4af37' });
        return;
    }

    if (expiry.length !== 5) {
        Swal.fire({ title:'Invalid Expiry', text:'Use MM/YY format.',
            icon:'error', background:'#1a1a1a', color:'#fff', confirmButtonColor:'#d4af37' });
        return;
    }

    const [em, ey] = expiry.split('/').map(Number);
    const now = new Date();
    const nowYear = now.getFullYear(), nowMonth = now.getMonth() + 1;

    if (em < 1 || em > 12) {
        Swal.fire({ title:'Invalid Expiry', text:'Month must be between 01 and 12.',
            icon:'error', background:'#1a1a1a', color:'#fff', confirmButtonColor:'#d4af37' });
        return;
    }

    if ((2000+ey) < nowYear || ((2000+ey) === nowYear && em < nowMonth)) {
        Swal.fire({ title:'Card Expired', text:'Please use a valid card.',
            icon:'error', background:'#1a1a1a', color:'#fff', confirmButtonColor:'#d4af37' });
        return;
    }

    if (cvv.length !== 3) {
        Swal.fire({ title:'Invalid CVV', text:'CVV must be 3 digits.',
            icon:'error', background:'#1a1a1a', color:'#fff', confirmButtonColor:'#d4af37' });
        return;
    }

    const grandTotal = Math.max(0, SUBTOTAL + ASSEMBLY_FEE + currentShipping);

    Swal.fire({
        title: 'Confirm Payment',
        html: `Pay <strong style="color:#d4af37;">RM ${grandTotal.toFixed(2)}</strong>?`,
        icon: 'question',
        background: '#1a1a1a', color: '#fff',
        showCancelButton: true,
        confirmButtonText: 'Yes, Pay Now',
        cancelButtonText:  'Cancel',
        confirmButtonColor: '#d4af37',
        cancelButtonColor:  '#333',
    }).then(result => {
        if (result.isConfirmed) this.submit();
    });
});
</script>
</body>
</html>