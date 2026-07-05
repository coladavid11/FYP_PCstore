<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id    = $_SESSION['user_id'] ?? null;

// Discount tiers 
function getDiscount(float $total): array
{
    if ($total >= 6000) return ['pct' => 10, 'label' => '10% off'];
    if ($total >= 4000) return ['pct' => 5,  'label' => '5% off'];
    return ['pct' => 0, 'label' => 'No discount'];
}

// Required parts (9 mandatory) 
$REQUIRED_KEYS = ['CPU','Motherboard','RAM','Storage','GPU','Power Supply','PC Case','Case Fan','Cooler'];

//  Part definitions
$parts = [
    'CPU'         => ['cat_id' => 6,  'icon' => 'fa-microchip',       'label' => 'Processor (CPU)',  'required' => true],
    'Motherboard' => ['cat_id' => 1,  'icon' => 'fa-server',          'label' => 'Motherboard',      'required' => true],
    'RAM'         => ['cat_id' => 7,  'icon' => 'fa-memory',          'label' => 'RAM',              'required' => true],
    'Storage'     => ['cat_id' => 8,  'icon' => 'fa-hard-drive',      'label' => 'Storage',          'required' => true],
    'GPU'         => ['cat_id' => 3,  'icon' => 'fa-display',         'label' => 'Graphics Card',    'required' => true],
    'Power Supply'=> ['cat_id' => 9,  'icon' => 'fa-bolt',            'label' => 'Power Supply',     'required' => true],
    'PC Case'     => ['cat_id' => 11, 'icon' => 'fa-box',             'label' => 'PC Case',          'required' => true],
    'Case Fan'    => ['cat_id' => 12, 'icon' => 'fa-fan',             'label' => 'Case Fan',         'required' => true],
    'Cooler'      => ['cat_id' => 10, 'icon' => 'fa-wind',            'label' => 'CPU Cooler',       'required' => true],
    'Monitor'     => ['cat_id' => 13, 'icon' => 'fa-desktop',         'label' => 'Monitor',          'required' => false],
    'Keyboard'    => ['cat_id' => 14, 'icon' => 'fa-keyboard',        'label' => 'Keyboard',         'required' => false],
    'Mouse'       => ['cat_id' => 15, 'icon' => 'fa-computer-mouse',  'label' => 'Mouse',            'required' => false],
];

// Fetch products per category (also skip products whose category has been deactivated) 
function getPartsByCategory($dbh, $category_id)
{
    $stmt = $dbh->prepare("
        SELECT p.product_id, p.name, p.price, p.image, p.stock
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        WHERE p.category_id = ? AND p.stock > 0 AND p.status = 'Active'
          AND (c.category_id IS NULL OR LOWER(c.status) = 'active')
        ORDER BY p.price ASC
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$partProducts = [];
foreach ($parts as $key => $info) {
    $partProducts[$key] = getPartsByCategory($dbh, $info['cat_id']);
}

// Handle Proceed to Checkout (PC Build → direct payment) 
$addResult = '';
if ($isLoggedIn && isset($_POST['add_build_to_cart'])) {

    $selectedIds     = json_decode($_POST['selected_product_ids'] ?? '[]', true);
    $buildSubtotal   = floatval($_POST['build_subtotal']  ?? 0);
    $discountPct     = intval($_POST['discount_pct']      ?? 0);
    $discountAmt     = floatval($_POST['discount_amt']    ?? 0);
    $finalPrice      = floatval($_POST['final_price']     ?? 0);
    $missingParts    = json_decode($_POST['missing_parts']  ?? '[]', true);
    $assemblyService = isset($_POST['assembly_service']) && $_POST['assembly_service'] === '1';
    $assemblyFee     = $assemblyService ? 0.00 : -25.00; // Free if assembled, -25 if not

    if (!empty($missingParts)) {
        $addResult   = 'missing';
        $missingList = implode(', ', $missingParts);
    } else {
        try {
            $dbh->beginTransaction();

            // 1. Save build record (status = 'draft' — saved but not submitted for payment)
            $assemblyFeeAdj = $assemblyService ? 0.00 : -25.00;
            $adjustedFinal  = max(0, $finalPrice + $assemblyFeeAdj);

            $bStmt = $dbh->prepare("
                INSERT INTO tbl_pc_build
                    (user_id, subtotal, discount_pct, discount_amt, final_price,
                     assembly_service, assembly_fee, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')
            ");
            $bStmt->execute([
                $user_id, $buildSubtotal, $discountPct, $discountAmt, $adjustedFinal,
                $assemblyService ? 1 : 0,
                $assemblyService ? 0.00 : -25.00,
            ]);
            $build_id = $dbh->lastInsertId();

            // Discount multiplier for per-item final price
            $multiplier = 1 - ($discountPct / 100);
            $addedCount = 0;

            foreach ($selectedIds as $entry) {
                $pid     = intval($entry['id']);
                $partKey = $entry['key'];
                if ($pid <= 0) continue;

                // Fetch product
                $pStmt = $dbh->prepare("SELECT * FROM products WHERE product_id = ? AND stock > 0");
                $pStmt->execute([$pid]);
                $prod = $pStmt->fetch(PDO::FETCH_ASSOC);
                if (!$prod) continue;

                $unitPrice = floatval($prod['price']);
                $itemFinal = round($unitPrice * $multiplier, 2);

                // 2. Save build item (no tblcart — goes direct to payment)
                $biStmt = $dbh->prepare("
                    INSERT INTO tbl_pc_build_item
                        (build_id, part_key, product_id, product_name, unit_price, final_price)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $biStmt->execute([$build_id, $partKey, $pid, $prod['name'], $unitPrice, $itemFinal]);
                $addedCount++;
            }

            $dbh->commit();

            if ($addedCount > 0) {
                // Redirect directly to payment with build context
                header('Location: payment.php?source=pcbuild&build_id=' . $build_id);
                exit();
            } else {
                $addResult = 'empty';
            }

        } catch (Exception $e) {
            $dbh->rollBack();
            $addResult = 'error';
            $addError  = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Builder — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* LAYOUT */
        .builder-wrap {
            display: flex;
            gap: 28px;
            align-items: flex-start;
        }
        .builder-parts   { flex: 1; min-width: 0; }
        .builder-summary { width: 340px; flex-shrink: 0; position: sticky; top: 100px; }

        @media (max-width: 991px) {
            .builder-wrap    { flex-direction: column; }
            .builder-summary { width: 100%; position: static; }
        }

        /* PART ROW */
        .part-row {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: border-color .25s;
        }
        .part-row.selected   { border-color: #d4af37; }
        .part-row.required-missing { border-color: #dc354588; }

        .part-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            cursor: pointer;
            user-select: none;
            transition: background .2s;
        }
        .part-header:hover { background: #1a1a1a; }

        .part-icon {
            width: 38px; height: 38px;
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #d4af37;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .part-label {
            flex: 1;
            font-size: 0.83rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #aaa;
        }

        .required-badge {
            font-size: 0.62rem;
            background: rgba(212,175,55,0.15);
            color: #d4af37;
            border: 1px solid #d4af3744;
            border-radius: 4px;
            padding: 2px 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .optional-badge {
            font-size: 0.62rem;
            background: rgba(100,100,100,0.12);
            color: #555;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            padding: 2px 6px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .part-selected-name {
            font-size: 0.78rem;
            color: #555;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .part-selected-name.chosen { color: #d4af37; }

        .part-selected-price {
            font-size: 0.78rem;
            color: #d4af37;
            font-weight: 700;
            white-space: nowrap;
            margin-left: 6px;
        }

        .part-chevron {
            color: #555;
            font-size: 0.75rem;
            transition: transform .25s;
            margin-left: 6px;
            flex-shrink: 0;
        }
        .part-row.open .part-chevron { transform: rotate(180deg); }

        /* DROPDOWN PANEL */
        .part-panel {
            display: none;
            border-top: 1px solid #1e1e1e;
            padding: 14px;
            background: #0f0f0f;
        }
        .part-row.open .part-panel { display: block; }

        .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .part-option {
            background: #181818;
            border: 1px solid #2a2a2a;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 6px;
        }
        .part-option:hover { border-color: #555; background: #1e1e1e; }
        .part-option.active {
            border-color: #d4af37;
            background: rgba(212,175,55,0.07);
        }
        .part-option img {
            width: 100%; height: 86px;
            object-fit: cover;
            border-radius: 6px;
            background: #1a1a1a;
        }
        .part-option .none-icon {
            width: 100%; height: 86px;
            display: flex; align-items: center; justify-content: center;
            background: #1a1a1a;
            border-radius: 6px;
            color: #333; font-size: 1.6rem;
        }
        .part-option .opt-name {
            font-size: 0.73rem;
            color: #ccc;
            line-height: 1.3;
            font-weight: 500;
        }
        .part-option .opt-price {
            font-size: 0.76rem;
            color: #d4af37;
            font-weight: 700;
        }
        .part-option .opt-price.none { color: #444; font-weight: 400; }

        /* SUMMARY PANEL */
        .summary-card {
            background: #111;
            border: 1px solid #222;
            border-radius: 16px;
            overflow: hidden;
        }

        .summary-head {
            padding: 18px 20px 14px;
            border-bottom: 1px solid #1c1c1c;
        }

        .summary-head-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            color: #fff;
            margin-bottom: 2px;
        }

        .summary-head-sub {
            font-size: 0.72rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Required parts progress bar */
        .req-progress-wrap {
            padding: 12px 20px;
            border-bottom: 1px solid #1c1c1c;
        }

        .req-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #555;
            margin-bottom: 6px;
        }

        .req-progress-bar-bg {
            height: 4px;
            background: #1e1e1e;
            border-radius: 2px;
            overflow: hidden;
        }

        .req-progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #d4af37, #f0cc55);
            border-radius: 2px;
            transition: width .4s ease;
        }

        .req-parts-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 10px 20px 14px;
            border-bottom: 1px solid #1c1c1c;
        }

        .req-chip {
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid #2a2a2a;
            color: #555;
            background: #0f0f0f;
            white-space: nowrap;
            transition: all .2s;
        }
        .req-chip.done {
            border-color: #d4af3766;
            color: #d4af37;
            background: rgba(212,175,55,0.06);
        }

        /* Items list */
        .summary-items {
            padding: 10px 20px;
            border-bottom: 1px solid #1c1c1c;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 260px;
            overflow-y: auto;
        }
        .summary-items::-webkit-scrollbar { width: 3px; }
        .summary-items::-webkit-scrollbar-thumb { background: #2a2a2a; }

        .sum-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .sum-item-left { display: flex; flex-direction: column; gap: 1px; flex: 1; min-width: 0; }
        .sum-item-key  { font-size: 0.65rem; color: #555; text-transform: uppercase; letter-spacing: .5px; }
        .sum-item-name {
            font-size: 0.76rem; color: #aaa;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sum-item-price { font-size: 0.78rem; font-weight: 700; color: #d4af37; white-space: nowrap; }
        .sum-item-empty .sum-item-name { color: #333; font-style: italic; }

        /* Discount tier badges */
        .discount-tiers {
            display: flex;
            gap: 6px;
            padding: 12px 20px;
            border-bottom: 1px solid #1c1c1c;
        }
        .tier-badge {
            flex: 1;
            text-align: center;
            padding: 6px 4px;
            border-radius: 8px;
            font-size: 0.68rem;
            border: 1px solid #2a2a2a;
            background: #0f0f0f;
            color: #444;
            transition: all .3s;
        }
        .tier-badge .tier-pct   { font-size: 0.95rem; font-weight: 800; display: block; margin-bottom: 2px; }
        .tier-badge .tier-range { font-size: 0.6rem; }
        .tier-badge.tier-active {
            border-color: #d4af37;
            background: rgba(212,175,55,0.1);
            color: #d4af37;
        }
        .tier-badge.tier-next   { border-color: #3a3a2a; color: #666; }

        /* Totals */
        .summary-totals { padding: 14px 20px 6px; }

        .tot-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            color: #666;
            margin-bottom: 8px;
        }
        .tot-row.discount-row { color: #4caf50; }
        .tot-row.discount-row span:last-child { font-weight: 600; }

        .tot-divider { height: 1px; background: #1c1c1c; margin: 10px 0; }

        .tot-grand {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .tot-grand-label { font-size: 0.9rem; font-weight: 700; color: #fff; }
        .tot-grand-amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: #d4af37;
            font-family: 'Playfair Display', serif;
        }
        .tot-grand-original {
            font-size: 0.78rem;
            color: #555;
            text-decoration: line-through;
            text-align: right;
            margin-top: -4px;
        }

        /* CTA */
        .summary-cta { padding: 0 16px 16px; }

        .btn-build {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d4af37, #b8942a);
            color: #000;
            font-weight: 700;
            font-size: 0.9rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
            text-decoration: none;
        }
        .btn-build:hover:not(:disabled) { opacity: .88; transform: translateY(-1px); color: #000; }
        .btn-build:disabled { opacity: .3; cursor: not-allowed; }

        .missing-hint {
            font-size: 0.7rem;
            color: #dc3545;
            text-align: center;
            margin-top: 8px;
            min-height: 16px;
        }

        /* ASSEMBLY TOGGLE */
        .assembly-toggle {
            margin: 0 16px 14px;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            overflow: hidden;
            transition: border-color .25s;
        }
        .assembly-toggle.active { border-color: #d4af3766; }

        .assembly-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            background: #0f0f0f;
            transition: background .2s;
            user-select: none;
        }
        .assembly-label:hover { background: #141414; }

        .assembly-checkbox-wrap {
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Custom gold checkbox */
        .assembly-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 20px; height: 20px;
            border: 2px solid #3a3a3a;
            border-radius: 5px;
            background: #1a1a1a;
            cursor: pointer;
            position: relative;
            transition: all .2s;
            display: block;
        }
        .assembly-checkbox:checked {
            background: #d4af37;
            border-color: #d4af37;
        }
        .assembly-checkbox:checked::after {
            content: '';
            position: absolute;
            left: 5px; top: 2px;
            width: 6px; height: 10px;
            border: 2px solid #000;
            border-top: none; border-left: none;
            transform: rotate(45deg);
        }

        .assembly-text { flex: 1; }
        .assembly-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .assembly-free-badge {
            font-size: 0.62rem;
            background: rgba(76,175,80,0.15);
            color: #4caf50;
            border: 1px solid #4caf5044;
            border-radius: 4px;
            padding: 2px 7px;
            font-weight: 700;
            letter-spacing: .5px;
        }
        .assembly-desc {
            font-size: 0.75rem;
            color: #666;
            line-height: 1.5;
        }
        .assembly-desc strong { color: #d4af37; }

        .assembly-fee-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            background: #0a0a0a;
            border-top: 1px solid #1a1a1a;
            font-size: 0.78rem;
        }
        .assembly-fee-label { color: #666; }
        .assembly-fee-value { font-weight: 700; }
        .assembly-fee-value.free  { color: #4caf50; }
        .assembly-fee-value.deduct { color: #ff6b6b; }

        /* PAGE HERO */
        .page-hero {
            padding: 60px 0 40px;
            text-align: center;
        }
        .page-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            color: #fff;
            margin-bottom: 8px;
        }
        .page-hero p {
            color: #666;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        /* Section divider between required and optional */
        .parts-section-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #444;
            padding: 10px 0 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .parts-section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #1e1e1e;
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<section class="page-hero">
    <div class="container">
        <h1>PC Builder</h1>
        <p>Customize your dream machine</p>
        <div class="accent-line mx-auto mt-3"></div>
    </div>
</section>

<div class="container pb-5">
<div class="builder-wrap">

    <!-- LEFT: PART SELECTOR -->
    <div class="builder-parts">

        <div class="parts-section-label">Required Components</div>

        <?php foreach ($parts as $key => $info):
            if (!$info['required']) continue;
            $products = $partProducts[$key];
        ?>
        <div class="part-row" id="row-<?php echo $key; ?>">
            <div class="part-header" data-key="<?php echo $key; ?>">
                <div class="part-icon"><i class="fa <?php echo $info['icon']; ?>"></i></div>
                <span class="part-label"><?php echo $info['label']; ?></span>
                <span class="required-badge">Required</span>
                <span class="part-selected-name" id="sel-name-<?php echo $key; ?>">— Not selected</span>
                <span class="part-selected-price" id="sel-price-<?php echo $key; ?>" style="display:none;"></span>
                <i class="fa fa-chevron-down part-chevron"></i>
            </div>
            <div class="part-panel">
                <div class="parts-grid">
                    <?php foreach ($products as $p): ?>
                    <div class="part-option"
                         id="opt-<?php echo $key; ?>-<?php echo $p['product_id']; ?>"
                         data-key="<?php echo $key; ?>"
                         data-id="<?php echo $p['product_id']; ?>"
                         data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>"
                         data-price="<?php echo $p['price']; ?>">
                        <img src="<?php echo htmlspecialchars($p['image']); ?>"
                             alt="<?php echo htmlspecialchars($p['name']); ?>"
                             onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="opt-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="opt-price">RM <?php echo number_format($p['price'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <div style="color:#555;font-size:0.8rem;padding:10px;grid-column:1/-1;">
                        No products available.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="parts-section-label" style="margin-top:8px;">Optional Add-ons</div>

        <?php foreach ($parts as $key => $info):
            if ($info['required']) continue;
            $products = $partProducts[$key];
        ?>
        <div class="part-row" id="row-<?php echo $key; ?>">
            <div class="part-header" data-key="<?php echo $key; ?>">
                <div class="part-icon"><i class="fa <?php echo $info['icon']; ?>"></i></div>
                <span class="part-label"><?php echo $info['label']; ?></span>
                <span class="optional-badge">Optional</span>
                <span class="part-selected-name" id="sel-name-<?php echo $key; ?>">— None</span>
                <span class="part-selected-price" id="sel-price-<?php echo $key; ?>" style="display:none;"></span>
                <i class="fa fa-chevron-down part-chevron"></i>
            </div>
            <div class="part-panel">
                <div class="parts-grid">
                    <!-- None option for optional parts -->
                    <div class="part-option active"
                         data-key="<?php echo $key; ?>" data-id="0" data-name="None" data-price="0">
                        <div class="none-icon"><i class="fa fa-ban"></i></div>
                        <div class="opt-name">None</div>
                        <div class="opt-price none">RM 0.00</div>
                    </div>
                    <?php foreach ($products as $p): ?>
                    <div class="part-option"
                         id="opt-<?php echo $key; ?>-<?php echo $p['product_id']; ?>"
                         data-key="<?php echo $key; ?>"
                         data-id="<?php echo $p['product_id']; ?>"
                         data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>"
                         data-price="<?php echo $p['price']; ?>">
                        <img src="<?php echo htmlspecialchars($p['image']); ?>"
                             alt="<?php echo htmlspecialchars($p['name']); ?>"
                             onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="opt-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="opt-price">RM <?php echo number_format($p['price'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div><!-- /builder-parts -->

    <!-- RIGHT: SUMMARY PANEL -->
    <div class="builder-summary">
    <div class="summary-card">

        <!-- Head -->
        <div class="summary-head">
            <div class="summary-head-title">Build Summary</div>
            <div class="summary-head-sub">Configure your perfect PC</div>
        </div>

        <!-- Required parts progress -->
        <div class="req-progress-wrap">
            <div class="req-progress-label">
                <span>Required components</span>
                <span id="req-count">0 / <?php echo count($REQUIRED_KEYS); ?></span>
            </div>
            <div class="req-progress-bar-bg">
                <div class="req-progress-bar-fill" id="req-bar" style="width:0%"></div>
            </div>
        </div>

        <!-- Required parts chips -->
        <div class="req-parts-chips">
            <?php foreach ($REQUIRED_KEYS as $rk): ?>
            <span class="req-chip" id="chip-<?php echo $rk; ?>">
                <?php echo $parts[$rk]['label']; ?>
            </span>
            <?php endforeach; ?>
        </div>

        <!-- Item list -->
        <div class="summary-items" id="summaryItems">
            <?php foreach ($parts as $key => $info): ?>
            <div class="sum-item sum-item-empty" id="sum-row-<?php echo $key; ?>">
                <div class="sum-item-left">
                    <span class="sum-item-key"><?php echo $info['label']; ?></span>
                    <span class="sum-item-name" id="sum-name-<?php echo $key; ?>">
                        <?php echo $info['required'] ? 'Not selected' : 'None'; ?>
                    </span>
                </div>
                <span class="sum-item-price" id="sum-price-<?php echo $key; ?>" style="display:none;"></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Discount tier badges -->
        <div class="discount-tiers">
            <div class="tier-badge" id="tier-0">
                <span class="tier-pct">0%</span>
                <span class="tier-range">Below RM 4,000</span>
            </div>
            <div class="tier-badge" id="tier-5">
                <span class="tier-pct">5% off</span>
                <span class="tier-range">RM 4,000 – 5,999</span>
            </div>
            <div class="tier-badge" id="tier-10">
                <span class="tier-pct">10% off</span>
                <span class="tier-range">RM 6,000+</span>
            </div>
        </div>

        <!-- Totals -->
        <div class="summary-totals">
            <div class="tot-row">
                <span>Subtotal</span>
                <span id="tot-subtotal">RM 0.00</span>
            </div>
            <div class="tot-row discount-row" id="discount-row" style="display:none;">
                <span id="discount-label">Discount (0%)</span>
                <span id="tot-discount">− RM 0.00</span>
            </div>
            <div class="tot-row" id="assembly-fee-tot-row" style="display:none;">
                <span style="color:#ff6b6b;"><i class="fa fa-minus-circle me-1"></i>No Assembly</span>
                <span style="color:#ff6b6b;font-weight:600;">− RM 25.00</span>
            </div>
            <div class="tot-divider"></div>
            <div class="tot-grand">
                <div>
                    <div class="tot-grand-label">Total</div>
                </div>
                <div>
                    <div class="tot-grand-amount" id="tot-final">RM 0.00</div>
                    <div class="tot-grand-original" id="tot-original" style="display:none;"></div>
                </div>
            </div>
        </div>

        <!-- Assembly Service Toggle -->
        <div class="assembly-toggle active" id="assemblyToggleWrap">
            <label class="assembly-label" for="assemblyChk">
                <div class="assembly-checkbox-wrap">
                    <input type="checkbox" id="assemblyChk" checked
                           onchange="toggleAssembly(this.checked)">
                </div>
                <div class="assembly-text">
                    <div class="assembly-title">
                        <i class="fa fa-screwdriver-wrench" style="color:#d4af37;"></i>
                        Assembly Service
                        <span class="assembly-free-badge">FREE</span>
                    </div>
                    <div class="assembly-desc">
                        My PC Store will <strong>build &amp; test</strong> your PC
                        and deliver it fully assembled to your address.<br>
                        <span style="color:#555;">Uncheck if you prefer separate, unassembled parts.</span>
                    </div>
                </div>
            </label>
            <div class="assembly-fee-row">
                <span class="assembly-fee-label" id="assemblyFeeLabel">
                    <i class="fa fa-circle-check me-1" style="color:#4caf50;"></i>
                    Assembled &amp; delivered to your door
                </span>
                <span class="assembly-fee-value free" id="assemblyFeeValue">FREE</span>
            </div>
        </div>

        <!-- CTA -->
        <div class="summary-cta">
            <form method="POST" id="buildForm">
                <input type="hidden" name="add_build_to_cart"    value="1">
                <input type="hidden" name="selected_product_ids" id="selectedIdsInput" value="[]">
                <input type="hidden" name="build_subtotal"        id="inputSubtotal"   value="0">
                <input type="hidden" name="discount_pct"          id="inputDiscPct"    value="0">
                <input type="hidden" name="discount_amt"          id="inputDiscAmt"    value="0">
                <input type="hidden" name="final_price"           id="inputFinal"      value="0">
                <input type="hidden" name="missing_parts"         id="inputMissing"    value="[]">
                <input type="hidden" name="assembly_service"      id="inputAssembly"   value="1">

                <?php if (!$isLoggedIn): ?>
                    <a href="login.php" class="btn-build">Login to Proceed to Checkout</a>
                <?php else: ?>
                    <button type="button" class="btn-build" id="addBuildBtn"
                            onclick="submitBuild()" disabled>
                        <i class="fa fa-lock me-2"></i> Proceed to Checkout
                    </button>
                <?php endif; ?>
            </form>
            <div class="missing-hint" id="missingHint"></div>
        </div>

    </div>
    </div><!-- /builder-summary -->

</div>
</div>

<?php include('includes/footer.php'); ?>

<script>
const REQUIRED_KEYS = <?php echo json_encode($REQUIRED_KEYS); ?>;
const ALL_KEYS      = <?php echo json_encode(array_keys($parts)); ?>;
const PART_LABELS   = <?php echo json_encode(array_combine(array_keys($parts), array_column($parts, 'label'))); ?>;

// State: key → {id, name, price}
const build = {};

function fmt(val) {
    return 'RM ' + parseFloat(val).toLocaleString('en-MY', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}

function getDiscount(total) {
    if (total >= 6000) return { pct: 10, label: '10%' };
    if (total >= 4000) return { pct: 5,  label: '5%'  };
    return { pct: 0, label: '0%' };
}

// Select a part 
function selectPart(key, id, name, price) {
    document.querySelectorAll(`.part-option[data-key="${key}"]`)
        .forEach(el => el.classList.remove('active'));

    const optEl = id == 0
        ? document.querySelector(`.part-option[data-key="${key}"][data-id="0"]`)
        : document.getElementById(`opt-${key}-${id}`);
    if (optEl) optEl.classList.add('active');

    const row = document.getElementById(`row-${key}`);

    if (id == 0) {
        delete build[key];
        document.getElementById(`sel-name-${key}`).textContent  = REQUIRED_KEYS.includes(key) ? '— Not selected' : '— None';
        document.getElementById(`sel-name-${key}`).classList.remove('chosen');
        document.getElementById(`sel-price-${key}`).style.display = 'none';
        row.classList.remove('selected');
    } else {
        build[key] = { id: parseInt(id), name, price: parseFloat(price) };
        const nameEl = document.getElementById(`sel-name-${key}`);
        nameEl.textContent = name;
        nameEl.classList.add('chosen');
        document.getElementById(`sel-price-${key}`).textContent    = fmt(price);
        document.getElementById(`sel-price-${key}`).style.display  = '';
        row.classList.add('selected');
        row.classList.remove('open');
    }

    updateSummary();
}

// Update all summary UI 
function updateSummary() {
    let subtotal = 0;
    let reqDone  = 0;

    ALL_KEYS.forEach(key => {
        const row    = document.getElementById(`sum-row-${key}`);
        const nameEl  = document.getElementById(`sum-name-${key}`);
        const priceEl = document.getElementById(`sum-price-${key}`);

        if (build[key]) {
            nameEl.textContent    = build[key].name;
            priceEl.textContent   = fmt(build[key].price);
            priceEl.style.display = '';
            row.classList.remove('sum-item-empty');
            subtotal += build[key].price;
        } else {
            nameEl.textContent    = REQUIRED_KEYS.includes(key) ? 'Not selected' : 'None';
            priceEl.style.display = 'none';
            row.classList.add('sum-item-empty');
        }

        if (REQUIRED_KEYS.includes(key) && build[key]) {
            reqDone++;
            document.getElementById(`chip-${key}`)?.classList.add('done');
        } else if (REQUIRED_KEYS.includes(key)) {
            document.getElementById(`chip-${key}`)?.classList.remove('done');
        }
    });

    const total = REQUIRED_KEYS.length;
    document.getElementById('req-count').textContent = `${reqDone} / ${total}`;
    document.getElementById('req-bar').style.width   = `${(reqDone / total) * 100}%`;

    const disc       = getDiscount(subtotal);
    const discAmt    = parseFloat((subtotal * disc.pct / 100).toFixed(2));
    const finalPrice = parseFloat((subtotal - discAmt).toFixed(2));

    ['0','5','10'].forEach(t => document.getElementById(`tier-${t}`).classList.remove('tier-active','tier-next'));
    if (disc.pct === 0 && subtotal > 0)   { document.getElementById('tier-0').classList.add('tier-active'); document.getElementById('tier-5').classList.add('tier-next'); }
    else if (disc.pct === 5)  { document.getElementById('tier-5').classList.add('tier-active'); document.getElementById('tier-10').classList.add('tier-next'); }
    else if (disc.pct === 10) { document.getElementById('tier-10').classList.add('tier-active'); }
    else                      { document.getElementById('tier-0').classList.add('tier-active'); }

    document.getElementById('tot-subtotal').textContent = fmt(subtotal);

    if (disc.pct > 0) {
        document.getElementById('discount-row').style.display  = '';
        document.getElementById('discount-label').textContent  = `Discount (${disc.label})`;
        document.getElementById('tot-discount').textContent    = `− ${fmt(discAmt)}`;
        document.getElementById('tot-original').style.display  = '';
        document.getElementById('tot-original').textContent    = fmt(subtotal);
    } else {
        document.getElementById('discount-row').style.display  = 'none';
        document.getElementById('tot-original').style.display  = 'none';
    }

    const assemblyDeduct = assemblySelected ? 0 : 25;
    const displayedTotal = Math.max(0, finalPrice - assemblyDeduct);
    document.getElementById('tot-final').textContent = fmt(displayedTotal);

    document.getElementById('inputSubtotal').value = subtotal.toFixed(2);
    document.getElementById('inputDiscPct').value  = disc.pct;
    document.getElementById('inputDiscAmt').value  = discAmt.toFixed(2);
    document.getElementById('inputFinal').value    = finalPrice.toFixed(2);

    const missing = REQUIRED_KEYS.filter(k => !build[k]);
    document.getElementById('inputMissing').value = JSON.stringify(
        missing.map(k => PART_LABELS[k] || k)
    );

    const hint = document.getElementById('missingHint');
    if (missing.length > 0 && subtotal > 0) {
        hint.textContent = `Missing: ${missing.join(', ')}`;
    } else {
        hint.textContent = '';
    }

    ALL_KEYS.forEach(key => {
        const row = document.getElementById(`row-${key}`);
        if (REQUIRED_KEYS.includes(key) && !build[key] && subtotal > 0) {
            row.classList.add('required-missing');
        } else {
            row.classList.remove('required-missing');
        }
    });

    const btn = document.getElementById('addBuildBtn');
    if (btn) btn.disabled = (missing.length > 0 || subtotal === 0);

    const ids = Object.entries(build).map(([k, v]) => ({ key: k, id: v.id }));
    document.getElementById('selectedIdsInput').value = JSON.stringify(ids);
}

// Assembly service toggle 
let assemblySelected = true;

function toggleAssembly(checked) {
    assemblySelected = checked;
    const wrap     = document.getElementById('assemblyToggleWrap');
    const label    = document.getElementById('assemblyFeeLabel');
    const value    = document.getElementById('assemblyFeeValue');
    const feeRow   = document.getElementById('assembly-fee-tot-row');
    const hiddenIn = document.getElementById('inputAssembly');

    wrap.classList.toggle('active', checked);
    hiddenIn.value = checked ? '1' : '0';

    if (checked) {
        label.innerHTML  = '<i class="fa fa-circle-check me-1" style="color:#4caf50;"></i>Assembled &amp; delivered to your door';
        value.textContent = 'FREE';
        value.className   = 'assembly-fee-value free';
        feeRow.style.display = 'none';
    } else {
        label.innerHTML  = '<i class="fa fa-box-open me-1" style="color:#ff6b6b;"></i>Separate unassembled parts delivery';
        value.textContent = '− RM 25.00';
        value.className   = 'assembly-fee-value deduct';
        feeRow.style.display = '';
    }
    updateSummary();
}

// Submit build → proceed to checkout 
function submitBuild() {
    const missing = REQUIRED_KEYS.filter(k => !build[k]);
    if (missing.length > 0) {
        Swal.fire({
            icon: 'warning', title: 'Incomplete Build',
            html: `Please select all required components:<br><small style="color:#d4af37;">${missing.join(', ')}</small>`,
            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
        });
        return;
    }

    const subtotal  = parseFloat(document.getElementById('inputSubtotal').value);
    const discPct   = parseInt(document.getElementById('inputDiscPct').value);
    const discAmt   = parseFloat(document.getElementById('inputDiscAmt').value);
    const final     = parseFloat(document.getElementById('inputFinal').value);
    const partCount = Object.keys(build).length;

    const assemblyDeductConf = assemblySelected ? 0 : 25;
    const displayedFinal     = Math.max(0, final - assemblyDeductConf);

    let discHtml = discPct > 0
        ? `<div style="color:#4caf50;font-size:.85rem;margin-top:4px;">
               <i class="fa fa-tag me-1"></i>${discPct}% discount — you save ${fmt(discAmt)}
           </div>`
        : '';

    const assemblyHtml = assemblySelected
        ? `<div style="color:#4caf50;font-size:.82rem;margin-top:4px;">
               <i class="fa fa-screwdriver-wrench me-1"></i>Assembly & delivery included — <strong>FREE</strong>
           </div>`
        : `<div style="color:#ff6b6b;font-size:.82rem;margin-top:4px;">
               <i class="fa fa-box-open me-1"></i>Separate unassembled parts — <strong>− RM 25.00</strong>
           </div>`;

    Swal.fire({
        icon: 'question',
        title: 'Proceed to Checkout?',
        html: `<div style="text-align:left;">
                   <div style="color:#aaa;font-size:.85rem;margin-bottom:8px;">${partCount} part(s) selected</div>
                   <div style="display:flex;justify-content:space-between;color:#888;font-size:.82rem;margin-bottom:4px;">
                       <span>Parts Subtotal</span><span>${fmt(subtotal)}</span>
                   </div>
                   ${discHtml}
                   ${assemblyHtml}
                   <div style="display:flex;justify-content:space-between;font-weight:700;color:#d4af37;font-size:1rem;margin-top:10px;border-top:1px solid #2a2a2a;padding-top:10px;">
                       <span>Total (excl. shipping)</span><span>${fmt(displayedFinal)}</span>
                   </div>
               </div>`,
        background: '#1a1a1a', color: '#fff',
        confirmButtonColor: '#d4af37', cancelButtonColor: '#333',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-arrow-right me-1"></i> Yes, Checkout',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) document.getElementById('buildForm').submit();
    });
}

function fmt(val) {
    return 'RM ' + parseFloat(val).toLocaleString('en-MY', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}

// Event delegation 
document.addEventListener('click', function (e) {
    const opt = e.target.closest('.part-option');
    if (opt) {
        selectPart(opt.dataset.key, opt.dataset.id, opt.dataset.name, opt.dataset.price);
        return;
    }
    const header = e.target.closest('.part-header');
    if (header) {
        const row = document.getElementById('row-' + header.dataset.key);
        if (row) row.classList.toggle('open');
    }
});

// Init
updateSummary();
</script>

<?php if ($addResult === 'missing'): ?>
<script>
Swal.fire({
    icon: 'warning', title: 'Incomplete Build',
    html: 'Missing required components: <strong style="color:#d4af37;"><?php echo htmlspecialchars($missingList ?? ''); ?></strong>',
    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
});
</script>
<?php elseif ($addResult === 'empty'): ?>
<script>
Swal.fire({ icon: 'error', title: 'Nothing Added', text: 'No valid parts were found.',
    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37' });
</script>
<?php elseif ($addResult === 'error'): ?>
<script>
Swal.fire({ icon: 'error', title: 'Error', text: '<?php echo addslashes($addError ?? ''); ?>',
    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37' });
</script>
<?php endif; ?>

</body>
</html>