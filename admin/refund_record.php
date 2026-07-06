<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    die("Error: Invalid Order ID.");
}

// Fetch primary order details coupled with a LEFT JOIN so it doesn't crash if empty
$sql = "SELECT o.order_number, o.created_at AS order_date, o.grand_total, u.fullname, u.gmail,
               r.refund_id, r.amount AS refund_amount, r.reason AS refund_reason, r.status AS refund_status, r.created_at AS refund_date
        FROM tblorders o
        LEFT JOIN refund r ON r.order_id = o.order_id
        LEFT JOIN tbluser u ON u.user_id = o.user_id
        WHERE o.order_id = ? LIMIT 1";

$query = $dbh->prepare($sql);
$query->execute([$order_id]);
$record = $query->fetch(PDO::FETCH_OBJ);

if (!$record) {
    die("Error: Order not found.");
}

// Fallback logic: If no refund row exists yet, use fallback values on the fly
if (empty($record->refund_id)) {
    $record->refund_id = 0; // Handled dynamically below
    $record->refund_amount = $record->grand_total; 
    $record->refund_reason = "System Reversal (No written log details)";
    $record->refund_status = "COMPLETED";
    $record->refund_date = $record->order_date;
}

// ── NEW DYNAMIC STRING FORMATTING LOGIC ──
// Takes the refund date (or fallback order date) and formats it into YYYYMMDD
$datePrefix = date('Ymd', strtotime($record->refund_date));

// Pads the numeric refund_id to 4 digits (e.g., 1 becomes 0001)
$paddedId = str_pad($record->refund_id, 4, '0', STR_PAD_LEFT);

// Combine everything to generate the final string sequence layout
$customRefundRef = "REF-" . $datePrefix . "-" . $paddedId;


// Initialize Dompdf Components from your local standalone bundle
require_once 'includes/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

// Build structured layout HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Refund Credit Note - ' . htmlspecialchars($record->order_number) . '</title>
    <style>
        body { font-family: "Helvetica", sans-serif; color: #333; font-size: 13px; line-height: 1.4; }
        .invoice-box { max-width: 800px; margin: auto; padding: 10px; }
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #e67e22; padding-bottom: 10px; }
        .company-title { font-size: 24px; font-weight: bold; color: #000; }
        .invoice-title { font-size: 20px; text-align: right; color: #e67e22; font-weight: bold; }
        .details-table { width: 100%; margin-bottom: 40px; }
        .details-table td { vertical-align: top; width: 50%; }
        .refund-table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 30px; }
        .refund-table th { background: #fcf8f2; padding: 10px; border: 1px solid #f3e5d8; font-weight: bold; color: #c0392b; }
        .refund-table td { padding: 12px 10px; border: 1px solid #e0e0e0; }
        .total-section { text-align: right; font-size: 16px; font-weight: bold; margin-top: 20px; color: #c0392b; }
        .badge { background: #fde8e8; color: #c0392b; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header">
            <tr>
                <td class="company-title">MY PC STORE</td>
                <td class="invoice-title">CREDIT NOTE (REFUND)</td>
            </tr>
        </table>

        <table class="details-table">
            <tr>
                <td>
                    <strong>Customer Account:</strong><br>
                    ' . htmlspecialchars($record->fullname ?? 'Guest Customer') . '<br>
                    Email: ' . htmlspecialchars($record->gmail ?? 'N/A') . '
                </td>
                <td style="text-align: right;">
                    <strong>Refund Ref ID:</strong> ' . $customRefundRef . '<br>
                    <strong>Original Order Reference:</strong> ' . htmlspecialchars($record->order_number) . '<br>
                    <strong>Order Date:</strong> ' . date('d M Y', strtotime($record->order_date)) . '<br>
                    <strong>Processed Date:</strong> ' . date('d M Y h:i A', strtotime($record->refund_date)) . '
                </td>
            </tr>
        </table>

        <table class="refund-table">
            <thead>
                <tr>
                    <th>Refund Activity Description</th>
                    <th style="text-align: right; width: 30%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Refund Details</strong><br>
                        <span style="color:#666; font-size:12px;">Reason: ' . htmlspecialchars($record->refund_reason ?? 'Customer Request Reversal') . '</span>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        <span class="badge">' . strtoupper($record->refund_status ?? 'COMPLETED') . '</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            Total Amount Returned: RM ' . number_format($record->refund_amount, 2) . '
        </div>
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Refund-Note-" . $record->order_number . ".pdf", array("Attachment" => false));
exit;
?>