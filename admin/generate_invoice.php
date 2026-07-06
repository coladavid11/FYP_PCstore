<?php
session_start();
include('includes/config.php');

// 1. Authentication guard
if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

// 2. Validate Order ID input
$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    die("Error: Invalid Order ID.");
}

// 3. Fetch primary order details
$sql = "SELECT o.*, u.fullname, u.gmail 
        FROM tblorders o
        LEFT JOIN tbluser u ON u.user_id = o.user_id
        WHERE o.order_id = ? LIMIT 1";
$query = $dbh->prepare($sql);
$query->execute([$order_id]);
$order = $query->fetch(PDO::FETCH_OBJ);

if (!$order) {
    die("Error: Order not found.");
}

// 🔒 ADDED CRITERIA: Payment validation guard
// This stops execution if the payment status is not explicitly 'paid'
if (strtolower($order->payment_status) !== 'paid') {
    echo "<script>
        alert('Access Denied: Invoices can only be generated and printed for fully PAID orders.');
        window.close(); // Closes the newly opened invoice tab safely
    </script>";
    exit;
}

// 4. Fetch related line items for this specific order
$itemsSql = "SELECT product_name, quantity, product_price AS price FROM tblorder_item WHERE order_id = ?";
$itemsQuery = $dbh->prepare($itemsSql);
$itemsQuery->execute([$order_id]);
$items = $itemsQuery->fetchAll(PDO::FETCH_OBJ);

// 5. Initialize Dompdf Components (Using Standalone Release)
require_once 'includes/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// 6. Build HTML markup for your invoice
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - ' . htmlspecialchars($order->order_number) . '</title>
    <style>
        body { font-family: "Helvetica", sans-serif; color: #333; font-size: 13px; line-height: 1.4; }
        .invoice-box { max-width: 800px; margin: auto; padding: 10px; }
        .header { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
        .company-title { font-size: 24px; font-weight: bold; color: #000; }
        .invoice-title { font-size: 20px; text-align: right; color: #d4af37; font-weight: bold; }
        .details-table { width: 100%; margin-bottom: 40px; }
        .details-table td { vertical-align: top; width: 50%; }
        .item-table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 20px; }
        .item-table th { background: #f5f5f5; padding: 8px; border: 1px solid #e0e0e0; font-weight: bold; }
        .item-table td { padding: 8px; border: 1px solid #e0e0e0; }
        
        .summary-table { width: 40%; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
        .summary-table td { padding: 6px 8px; font-size: 13px; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .grand-total-row { font-size: 16px; color: #000; border-top: 2px solid #d4af37; }
        .footer-thanks { text-align: center; margin-top: 50px; font-weight: bold; color: #888; font-size: 14px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header">
            <tr>
                <td class="company-title">MY PC STORE</td>
                <td class="invoice-title">INVOICE</td>
            </tr>
        </table>

        <table class="details-table">
            <tr>
                <td>
                    <strong>Billed To:</strong><br>
                    ' . htmlspecialchars($order->fullname ?? 'Guest Customer') . '<br>
                    Email: ' . htmlspecialchars($order->gmail ?? 'N/A') . '<br>
                    Phone: ' . htmlspecialchars($order->phone ?? '—') . '
                </td>
                <td style="text-align: right;">
                    <strong>Invoice No:</strong> ' . htmlspecialchars($order->order_number) . '<br>
                    <strong>Date:</strong> ' . date('d M Y', strtotime($order->created_at)) . '<br>
                    <strong>Payment Method:</strong> ' . htmlspecialchars($order->payment_method ?? 'Card') . '<br>
                    <strong>Status:</strong> ' . strtoupper($order->order_status) . '
                </td>
            </tr>
        </table>

        <table class="item-table">
            <thead>
                <tr>
                    <th>Product Description</th>
                    <th style="text-align: center; width: 12%;">Qty</th>
                    <th style="text-align: right; width: 20%;">Price</th>
                    <th style="text-align: right; width: 22%;">Total</th>
                </tr>
            </thead>
            <tbody>';

if (!empty($items)) {
    foreach ($items as $item) {
        $itemTotal = $item->price * $item->quantity;
        $html .= '<tr>
                        <td>' . htmlspecialchars($item->product_name) . '</td>
                        <td style="text-align: center;">' . $item->quantity . '</td>
                        <td style="text-align: right;">RM ' . number_format($item->price, 2) . '</td>
                        <td style="text-align: right;">RM ' . number_format($itemTotal, 2) . '</td>
                    </tr>';
    }
} else {
    $html .= '<tr>
                    <td>Order Purchase Package (Ref: #' . htmlspecialchars($order->order_number) . ')</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">RM ' . number_format($order->grand_total, 2) . '</td>
                    <td style="text-align: right;">RM ' . number_format($order->grand_total, 2) . '</td>
                </tr>';
}

$html .= '  </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="bold">Subtotal:</td>
                <td class="text-right">RM ' . number_format(($order->subtotal ?? $order->grand_total), 2) . '</td>
            </tr>
            <tr>
                <td class="bold">Shipping:</td>
                <td class="text-right">RM ' . number_format(($order->shipping_fee ?? 0), 2) . '</td>
            </tr>
            <tr>
                <td class="bold">Service Fee:</td>
                <td class="text-right">RM ' . number_format(($order->service_fee ?? 0), 2) . '</td>
            </tr>
            <tr class="grand-total-row">
                <td class="bold" style="padding-top: 10px;">Grand Total:</td>
                <td class="text-right bold" style="padding-top: 10px;">RM ' . number_format($order->grand_total, 2) . '</td>
            </tr>
        </table>

        <div class="footer-thanks">
            Thank you for shopping with us!
        </div>
    </div>
</body>
</html>
';

// 7. Render the output matching configuration parameters
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Opens the PDF inline in a new window/tab
$dompdf->stream("Invoice-" . $order->order_number . ".pdf", array("Attachment" => false));
exit;
?>