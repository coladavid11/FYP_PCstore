<?php

session_start();
header('Content-Type: application/json');
include('includes/config.php');

/* ── AUTH ── */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? 0);
$order_id = intval($_POST['order_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

/* ── INPUT VALIDATION ── */
if ($product_id <= 0 || $order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product or order.']);
    exit();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5.']);
    exit();
}

// limit review length to 2000 characters
if (strlen($review) > 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Review is too long (max 2000 characters).']);
    exit();
}

/* ── VERIFY ORDER: belongs to user AND is delivered ── */
$orderStmt = $dbh->prepare("
    SELECT order_id, order_status
    FROM tblorders
    WHERE order_id = ? AND user_id = ?
    LIMIT 1
");
$orderStmt->execute([$order_id, $user_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
    exit();
}

if (strtolower($order['order_status']) !== 'delivered') {
    echo json_encode([
        'status' => 'error',
        'message' => 'You can only review products from delivered orders.'
    ]);
    exit();
}

/* ── VERIFY PRODUCT EXISTS IN THIS ORDER ── */
$itemStmt = $dbh->prepare("
    SELECT order_item_id FROM tblorder_item
    WHERE order_id = ? AND product_id = ?
    LIMIT 1
");
$itemStmt->execute([$order_id, $product_id]);
if (!$itemStmt->fetch()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'This product was not part of the order.'
    ]);
    exit();
}

/* ── CHECK FOR DUPLICATE REVIEW ── */
$dupStmt = $dbh->prepare("
    SELECT review_id FROM tblreviews
    WHERE user_id = ? AND product_id = ? AND order_id = ?
    LIMIT 1
");
$dupStmt->execute([$user_id, $product_id, $order_id]);
if ($dupStmt->fetch()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You have already reviewed this product for this order.'
    ]);
    exit();
}

/* ── INSERT REVIEW ── */
$now = date('Y-m-d H:i:s');

$insert = $dbh->prepare("
    INSERT INTO tblreviews
        (product_id, order_id, user_id, rating, review_text, created_at, updated_at)
    VALUES
        (?, ?, ?, ?, ?, ?, ?)
");

$success = $insert->execute([
    $product_id,
    $order_id,
    $user_id,
    $rating,
    htmlspecialchars($review),
    $now,
    $now
]);

if ($success) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you! Your review has been submitted.'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save your review. Please try again.'
    ]);
}
?>