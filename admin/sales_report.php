<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* ==========================================
   1. HANDLE DATE FILTERS (DEFAULT: ALL MONTHS)
   ========================================== */
$currentYear = date('Y');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0; // 0 means "All Months"

// Build dynamic SQL queries
$whereClauses = ["order_status='delivered'"];
$params = [];

if ($selectedYear > 0) {
    $whereClauses[] = "YEAR(created_at) = :year";
    $params[':year'] = $selectedYear;
}

if ($selectedMonth > 0) {
    $whereClauses[] = "MONTH(created_at) = :month";
    $params[':month'] = $selectedMonth;
}

$whereSql = "WHERE " . implode(" AND ", $whereClauses);

/* ==========================================
   2. EXPORT TO EXCEL (.xlsx) — REAL EXCEL TABLE + AUTOFILTER
   Exports EVERY order regardless of status, year or month.
   The header row has a built-in filter dropdown so the admin
   can filter/display any year, month or status directly inside
   Excel without needing to re-select filters on this page.
   ========================================== */
if (isset($_GET['export']) && $_GET['export'] == 'excel') {

    // Requires PhpSpreadsheet. If not installed yet, run this once in your project root:
    //   composer require phpoffice/phpspreadsheet
    // Adjust the path below if vendor/ is not one level above this file.
    require_once __DIR__ . '/../vendor/autoload.php';

    $exportStmt = $dbh->prepare("
        SELECT 
            o.order_id,
            o.order_number,
            u.fullname AS customer_name,
            u.gmail AS customer_email,
            o.created_at,
            o.payment_method,
            o.payment_status,
            o.order_status,
            o.total_amount,
            o.shipping_fee,
            o.service_fee,
            o.grand_total
        FROM tblorders o
        LEFT JOIN tbluser u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
    ");
    $exportStmt->execute();
    $orders = $exportStmt->fetchAll(PDO::FETCH_OBJ);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Sales Report');

    $headers = [
        'Order ID', 'Order Number', 'Customer Name', 'Customer Email',
        'Year', 'Month', 'Order Date',
        'Payment Method', 'Payment Status', 'Order Status',
        'Total Amount (RM)', 'Shipping Fee (RM)', 'Grand Total (RM)'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowNum = 2;
    $statusTotals = [];   // order_status => ['count' => x, 'revenue' => y]
    $yearTotals   = [];   // year => revenue
    $grandTotalSum = 0;

    foreach ($orders as $o) {
        $ts = strtotime($o->created_at);
        $year = (int) date('Y', $ts);
        $monthName = date('F', $ts);
        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts);

        $sheet->setCellValue("A{$rowNum}", $o->order_id);
        $sheet->setCellValue("B{$rowNum}", $o->order_number);
        $sheet->setCellValue("C{$rowNum}", $o->customer_name ?? 'N/A');
        $sheet->setCellValue("D{$rowNum}", $o->customer_email ?? 'N/A');
        $sheet->setCellValue("E{$rowNum}", $year);
        $sheet->setCellValue("F{$rowNum}", $monthName);
        $sheet->setCellValue("G{$rowNum}", $excelDate);
        $sheet->setCellValue("H{$rowNum}", $o->payment_method);
        $sheet->setCellValue("I{$rowNum}", ucfirst($o->payment_status));
        $sheet->setCellValue("J{$rowNum}", ucfirst($o->order_status));
        $sheet->setCellValue("K{$rowNum}", (float) $o->total_amount);
        $sheet->setCellValue("L{$rowNum}", (float) $o->shipping_fee);
        $sheet->setCellValue("M{$rowNum}", (float) $o->grand_total);

        // accumulate summary data
        $statusTotals[$o->order_status]['count']   = ($statusTotals[$o->order_status]['count'] ?? 0) + 1;
        $statusTotals[$o->order_status]['revenue'] = ($statusTotals[$o->order_status]['revenue'] ?? 0) + $o->grand_total;
        $yearTotals[$year] = ($yearTotals[$year] ?? 0) + $o->grand_total;
        $grandTotalSum += $o->grand_total;

        $rowNum++;
    }
    $lastDataRow = max($rowNum - 1, 1);
    $fullRange = "A1:M{$lastDataRow}";

    // ---- Header styling: black background, gold bold text (matches admin theme) ----
    $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setRGB('D4AF37');
    $sheet->getStyle('A1:M1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('000000');
    $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal('center')->setVertical('center');
    $sheet->getRowDimension(1)->setRowHeight(22);

    // ---- Number/date formats ----
    if ($lastDataRow >= 2) {
        $sheet->getStyle("G2:G{$lastDataRow}")->getNumberFormat()->setFormatCode('dd-mmm-yyyy');
        $sheet->getStyle("K2:M{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.00');
    }

    // ---- Light borders across the table ----
    $sheet->getStyle($fullRange)->getBorders()->getAllBorders()
        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
        ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCCCCCC'));

    // ---- Auto-size every column ----
    foreach (range('A', 'M') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // ---- Freeze header row + turn on the filter dropdowns for ALL columns ----
    // This is what lets the admin click the arrow on "Year" or "Month" and
    // tick/untick which years or months to display, without touching this page.
    $sheet->freezePane('A2');
    $sheet->setAutoFilter($fullRange);

    // ==========================================
    // SUMMARY SHEET — quick totals by status & by year
    // ==========================================
    $summarySheet = $spreadsheet->createSheet();
    $summarySheet->setTitle('Summary');

    $summarySheet->setCellValue('A1', 'Sales Summary — All Orders (Every Status, Every Year & Month)');
    $summarySheet->mergeCells('A1:C1');
    $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

    $summarySheet->setCellValue('A3', 'Order Status');
    $summarySheet->setCellValue('B3', 'Number of Orders');
    $summarySheet->setCellValue('C3', 'Total Revenue (RM)');
    $summarySheet->getStyle('A3:C3')->getFont()->setBold(true)->getColor()->setRGB('D4AF37');
    $summarySheet->getStyle('A3:C3')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('000000');

    $r = 4;
    foreach ($statusTotals as $status => $data) {
        $summarySheet->setCellValue("A{$r}", ucfirst($status));
        $summarySheet->setCellValue("B{$r}", $data['count']);
        $summarySheet->setCellValue("C{$r}", round($data['revenue'], 2));
        $r++;
    }
    $summarySheet->setCellValue("A{$r}", 'TOTAL');
    $summarySheet->setCellValue("B{$r}", count($orders));
    $summarySheet->setCellValue("C{$r}", round($grandTotalSum, 2));
    $summarySheet->getStyle("A{$r}:C{$r}")->getFont()->setBold(true);
    $r += 2;

    $summarySheet->setCellValue("A{$r}", 'Year');
    $summarySheet->setCellValue("B{$r}", 'Total Revenue (RM)');
    $summarySheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true)->getColor()->setRGB('D4AF37');
    $summarySheet->getStyle("A{$r}:B{$r}")->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('000000');
    $r++;
    ksort($yearTotals);
    foreach ($yearTotals as $yr => $rev) {
        $summarySheet->setCellValue("A{$r}", $yr);
        $summarySheet->setCellValue("B{$r}", round($rev, 2));
        $r++;
    }
    foreach (range('A', 'C') as $col) {
        $summarySheet->getColumnDimension($col)->setAutoSize(true);
    }

    // land on the main data sheet when the file is opened
    $spreadsheet->setActiveSheetIndex(0);

    $filename = "Sales_Report_ALL_" . date('Ymd_His') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/* ==========================================
   3. FETCH FILTERED SALES SUMMARY CARDS DATA
   ========================================== */

// Total Revenue
$stmt = $dbh->prepare("SELECT SUM(grand_total) FROM tblorders $whereSql");
$stmt->execute($params);
$totalRevenue = $stmt->fetchColumn();

// Total Orders
$orderWhere = [];

if ($selectedYear > 0) {
    $orderWhere[] = "YEAR(created_at)=:year";
}

if ($selectedMonth > 0) {
    $orderWhere[] = "MONTH(created_at)=:month";
}

$orderSql = "";

if(count($orderWhere)>0){
    $orderSql = "WHERE ".implode(" AND ",$orderWhere);
}

$stmt = $dbh->prepare("
SELECT COUNT(*)
FROM tblorders
$orderSql
");

$stmt->execute($params);

$totalOrders = $stmt->fetchColumn();

// Pending Orders
$stmt = $dbh->prepare("
SELECT COUNT(*)
FROM tblorders
WHERE order_status='pending'
");

$stmt->execute();

$pendingOrders = $stmt->fetchColumn();

// Delivered Orders
$stmt = $dbh->prepare("SELECT COUNT(*) FROM tblorders $whereSql");
$stmt->execute($params);
$deliveredOrders = $stmt->fetchColumn();

// Average Order Value
$stmt = $dbh->prepare("SELECT AVG(grand_total) FROM tblorders $whereSql");
$stmt->execute($params);
$avgOrder = $stmt->fetchColumn();

// Best Selling Month Query (Filters applied dynamically based on selected year/month context)
$bestMonthStmt = $dbh->prepare("
    SELECT
    DATE_FORMAT(created_at, '%b') AS month_name,
    SUM(grand_total) AS revenue
    FROM tblorders
    $whereSql
    GROUP BY MONTH(created_at)
    ORDER BY revenue DESC
    LIMIT 1
");
$bestMonthStmt->execute($params);
$bestMonthData = $bestMonthStmt->fetch(PDO::FETCH_OBJ);

$bestMonthName = $bestMonthData ? $bestMonthData->month_name : 'N/A';
$bestMonthRevenue = $bestMonthData ? $bestMonthData->revenue : 0;

/* ==========================================
   4. DYNAMIC CHART DATA TIMELINE LAYOUT
   ========================================== */
if ($selectedMonth > 0) {
    $chartStmt = $dbh->prepare("
        SELECT 
        DAY(created_at) as time_num,
        DATE_FORMAT(created_at, '%d %b') as time_label,
        SUM(grand_total) as revenue
        FROM tblorders
        $whereSql
        GROUP BY DAY(created_at)
        ORDER BY DAY(created_at) ASC
    ");
} else {
    $chartStmt = $dbh->prepare("
        SELECT
        MONTH(created_at) AS time_num,
        DATE_FORMAT(created_at,'%b') AS time_label,
        SUM(grand_total) AS revenue
        FROM tblorders
        $whereSql
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at) ASC
    ");
}

$chartStmt->execute($params);
$chartLabels = [];
$chartRevenue = [];

while ($row = $chartStmt->fetch(PDO::FETCH_ASSOC)) {
    $chartLabels[] = $row['time_label'];
    $chartRevenue[] = $row['revenue'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f5f5;
            display: flex;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #000;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #d4af37;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .sidebar a {
            display: block;
            color: #adadad;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #d4af37;
            color: #000;
        }

        .sidebar a.sidebar-active {
            background: #d4af37;
            color: #000;
        }

        /* ── MAIN WORKSPACE ── */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .topbar h1 {
            font-size: 1.5rem;
            color: #111;
            font-weight: 600;
        }

        /* ── FILTERS & ACTION CONTROLS ── */
        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: #fff;
            font-size: 0.88rem;
            color: #333;
            outline: none;
            cursor: pointer;
        }

        .filter-form select:focus {
            border-color: #d4af37;
        }

        .btn-filter {
            background: #000;
            color: #d4af37;
            border: 1px solid #000;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-filter:hover {
            background: #d4af37;
            color: #000;
            border-color: #d4af37;
        }

        .btn-export {
            background: #217346;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-export:hover {
            background: #154a2d;
        }

        /* ── CARDS AREA (Updated to 5 columns setup layout dynamically) ── */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .card {
            background: #fff;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            font-size: 0.8rem;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .card p {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111;
            line-height: 1.3;
        }

        .card span.sub-text {
            font-size: 0.82rem;
            color: #d4af37;
            font-weight: 600;
            margin-bottom: 2px;
            display: block;
        }

        .card::after {
            content: '';
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 4rem;
            color: rgba(212, 175, 55, 0.06);
            pointer-events: none;
        }

        .card:nth-child(1)::after {
            content: '\f155';
        }

        /* Revenue */
        .card:nth-child(2)::after {
            content: '\f07a';
        }

        /* Total Orders */
        .card:nth-child(3)::after {
            content: '\f058';
        }

        /* Delivered */
        .card:nth-child(4)::after {
            content: '\f201';
        }

        /* Avg Order */
        .card:nth-child(5)::after {
            content: '\f0a3';
        }

        /* Best selling medal icon */

        /* ── CHART PANEL AREA ── */
        .table-box {
            background: #fff;
            border-radius: 6px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .table-box h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #111;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-box h3::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 16px;
            background: #d4af37;
            border-radius: 2px;
        }

        #salesChart {
            max-height: 420px;
            width: 100% !important;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="sales_report.php" class="sidebar-active">📊 Sales Report</a>
        <a href="admins.php">⚙ Admins</a>
    </div>

    <div class="main">

        <div class="topbar">
            <div>
                <h1><i class="fa fa-chart-bar" style="color:#d4af37;margin-right:8px;"></i>Sales Summary Report</h1>
                <div style="font-size:0.75rem;color:#aaa;margin-top:2px;">
                    Active Scope:
                    <?php echo ($selectedMonth > 0 ? date('F', mktime(0, 0, 0, $selectedMonth, 1)) : 'All Months') . ' ' . $selectedYear; ?>
                </div>
            </div>

            <form method="GET" class="filter-form">
                <select name="month">
                    <option value="0" <?php echo $selectedMonth === 0 ? 'selected' : ''; ?>>All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $selectedMonth === $m ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select name="year">
                    <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $selectedYear === $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Filter</button>

                <a href="sales_report.php?export=excel"
                    class="btn-export" title="Exports every order (all years, all months, all statuses) — use Excel's filter to narrow it down">
                    <i class="fa fa-file-excel"></i> Export All Orders (Excel)
                </a>
            </form>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Total Revenue</h3>
                <p>RM <?php echo number_format($totalRevenue ?? 0, 2); ?></p>
            </div>

            <div class="card">
                <h3>Total Orders</h3>
                <p><?php echo number_format($totalOrders); ?></p>
            </div>

            <div class="card">
                <h3>Delivered Orders</h3>
                <p><?php echo number_format($deliveredOrders); ?></p>
            </div>

            <div class="card">
                <h3>Pending Orders</h3>
                <p><?php echo number_format($pendingOrders); ?></p>
            </div>

            <div class="card">
                <h3>Average Order</h3>
                <p>RM <?php echo number_format($avgOrder ?? 0, 2); ?></p>
            </div>

            <div class="card">
                <h3>Best Sales Month</h3>
                <span class="sub-text"><?php echo htmlspecialchars($bestMonthName); ?></span>
                <p>RM <?php echo number_format($bestMonthRevenue, 2); ?></p>
            </div>
        </div>

        <div class="table-box">
            <h3>Revenue Timeline Profile</h3>
            <canvas id="salesChart"></canvas>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('salesChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Revenue (RM)',
                        data: <?php echo json_encode($chartRevenue); ?>,
                        backgroundColor: 'rgba(212, 175, 55, 0.15)',
                        borderColor: 'rgba(212, 175, 55, 1)',
                        borderWidth: 2,
                        borderRadius: 4,
                        hoverBackgroundColor: 'rgba(0, 0, 0, 0.05)',
                        hoverBorderColor: '#000'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { font: { family: "'Poppins', sans-serif", size: 12 } }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: "'Poppins', sans-serif" } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                font: { family: "'Poppins', sans-serif" },
                                callback: function (value) { return 'RM ' + value.toLocaleString(); }
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>