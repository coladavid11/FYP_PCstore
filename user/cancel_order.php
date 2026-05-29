<?php
/*
 * cancel_order.php
 * POST: order_id
 * Response: JSON
 *
 * Rules:
 *  - User must be logged in
 *  - Order must belong to the user
 *  - Only 'processing' orders can be cancelled
 *  - Restores stock for each cancelled item
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

/* ── FETCH ORDER (must belong to this user) ── */
$stmt = $dbh->prepare("
    SELECT order_id, order_status, order_number
    FROM tblorders
    WHERE order_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found.']);
    exit();
}

/* ── ONLY 'processing' CAN BE CANCELLED ── */
if (strtolower($order['order_status']) !== 'processing') {
    echo json_encode([
        'status'  => 'error',
        'message' => "This order cannot be cancelled. Current status: {$order['order_status']}."
    ]);
    exit();
}

/* ── TRANSACTION: cancel order + restore stock ── */
try {
    $dbh->beginTransaction();

    /* 1. UPDATE order status to cancelled */
    $update = $dbh->prepare("
        UPDATE tblorders
        SET order_status = 'cancelled',
            payment_status = 'refunding',
            updated_at   = ?
        WHERE order_id = ?
    ");
    $update->execute([date('Y-m-d H:i:s'), $order_id]);

    /* 2. RESTORE STOCK for each order item */
    $items = $dbh->prepare("
        SELECT product_id, quantity
        FROM tblorder_item
        WHERE order_id = ?
    ");
    $items->execute([$order_id]);
    $orderItems = $items->fetchAll(PDO::FETCH_ASSOC);

    $restoreStmt = $dbh->prepare("
        UPDATE products
        SET stock = stock + ?
        WHERE product_id = ?
    ");

    foreach ($orderItems as $item) {
        $restoreStmt->execute([$item['quantity'], $item['product_id']]);
    }

    $dbh->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => "Order {$order['order_number']} cancelled. Stock has been restored."
    ]);

} catch (Exception $e) {
    $dbh->rollBack();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to cancel order. Please try again.'
    ]);
}
?>