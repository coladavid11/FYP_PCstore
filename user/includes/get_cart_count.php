<?php
/*
 * get_cart_count.php
 * Lightweight AJAX endpoint — returns current active cart item count.
 * Called by updateCartBadge(null) in header.php when a hard re-fetch is needed.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
include('includes/config.php');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'success', 'count' => 0]);
    exit;
}

$stmt = $dbh->prepare("SELECT COALESCE(SUM(quantity), 0) FROM tblcart WHERE user_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$count = (int) $stmt->fetchColumn();

echo json_encode(['status' => 'success', 'count' => $count]);