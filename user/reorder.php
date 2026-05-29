<?php
/*
 * reorder.php
 * POST: order_id
 * Response: JSON
 *
 * Rules:
 *  - User must be logged in
 *  - Order must belong to the user
 *  - For each item: check stock, then insert/update tblcart
 *  - Skips out-of-stock items, reports how many were added
 */

session_start();
header('Content-Type: application/json');
include('includes/config.php');

/* ── AUTH ── */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first.']);
    exit();
}

$user_id  = $_SESSION['user_id'];
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order.']);
    exit();
}

/* ── VERIFY ORDER BELONGS TO USER ── */
$stmt = $dbh->prepare("
    SELECT order_id FROM tblorders
    WHERE order_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
    exit();
}

/* ── FETCH ORDER ITEMS ── */
$iStmt = $dbh->prepare("
    SELECT oi.product_id, oi.product_name, oi.product_price, oi.quantity,
           p.image, p.stock
    FROM tblorder_item oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$iStmt->execute([$order_id]);
$items = $iStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'No items found in this order.']);
    exit();
}

/* ── PREPARE CART QUERIES ── */
$checkCart = $dbh->prepare("
    SELECT cart_id, quantity FROM tblcart
    WHERE user_id = ? AND product_id = ? AND status = 'active'
    LIMIT 1
");

$insertCart = $dbh->prepare("
    INSERT INTO tblcart
        (user_id, product_id, product_name, product_image, product_price, quantity, subtotal, created_at, updated_at, status)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
");

$updateCart = $dbh->prepare("
    UPDATE tblcart
    SET quantity   = ?,
        subtotal   = ?,
        updated_at = ?
    WHERE cart_id  = ?
");

/* ── LOOP THROUGH ITEMS ── */
$added   = 0;
$skipped = [];
$now     = date('Y-m-d H:i:s');

foreach ($items as $item) {
    $pid      = intval($item['product_id']);
    $reqQty   = intval($item['quantity']);
    $stock    = intval($item['stock'] ?? 0);
    $price    = floatval($item['product_price']);

    /* Skip if product no longer exists or out of stock */
    if ($pid <= 0 || $stock <= 0) {
        $skipped[] = $item['product_name'];
        continue;
    }

    /* Cap quantity to available stock */
    $addQty = min($reqQty, $stock);

    /* Check if already in cart */
    $checkCart->execute([$user_id, $pid]);
    $existing = $checkCart->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        /* UPDATE existing cart row — but cap at stock */
        $newQty = min($existing['quantity'] + $addQty, $stock);
        $updateCart->execute([
            $newQty,
            $price * $newQty,
            $now,
            $existing['cart_id']
        ]);
    } else {
        /* INSERT new cart row */
        $insertCart->execute([
            $user_id,
            $pid,
            $item['product_name'],
            $item['image'] ?? '',
            $price,
            $addQty,
            $price * $addQty,
            $now,
            $now
        ]);
    }

    $added++;
}

/* ── BUILD RESPONSE ── */
if ($added === 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'All items are currently out of stock and could not be added to cart.'
    ]);
    exit();
}

$msg = "{$added} item(s) added to your cart.";
if (!empty($skipped)) {
    $msg .= ' Skipped (out of stock): ' . implode(', ', $skipped) . '.';
}

echo json_encode([
    'status'       => 'success',
    'message'      => $msg,
    'added'        => $added,
    'skipped'      => $skipped,
]);
?>