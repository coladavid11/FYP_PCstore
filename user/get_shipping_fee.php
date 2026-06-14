<?php
/*
 * get_shipping_fee.php
 * AJAX endpoint — POST with state_id, returns JSON {fee, fee_display}
 */
session_start();
header('Content-Type: application/json');
include('includes/config.php');

$state_id = intval($_POST['state_id'] ?? 0);

if ($state_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid state.']);
    exit;
}

$stmt = $dbh->prepare("
    SELECT sr.fee, s.state_name
    FROM tbl_shipping_rate sr
    JOIN tblstate s ON s.state_id = sr.state_id
    WHERE sr.state_id = ?
");
$stmt->execute([$state_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'State not found.']);
    exit;
}

echo json_encode([
    'status'      => 'success',
    'fee'         => floatval($row['fee']),
    'fee_display' => 'RM ' . number_format($row['fee'], 2),
    'state_name'  => $row['state_name'],
]);