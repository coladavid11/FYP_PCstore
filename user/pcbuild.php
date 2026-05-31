<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id    = $_SESSION['user_id'] ?? null;

// ── Fetch products per category ──
function getPartsByCategory($dbh, $category_id) {
    $stmt = $dbh->prepare("
        SELECT p.product_id, p.name, p.price, p.image, p.stock,
               c.category_name, c.category_image
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.category_id = ? AND p.stock > 0
        ORDER BY p.price ASC
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Category IDs from your DB (based on screenshot)
$parts = [
    'CPU'           => ['cat_id' => 6,  'icon' => 'fa-microchip',        'label' => 'Processor (CPU)'],
    'Motherboard'   => ['cat_id' => 1,  'icon' => 'fa-server',           'label' => 'Motherboard'],
    'RAM'           => ['cat_id' => 7,  'icon' => 'fa-memory',           'label' => 'RAM'],
    'Storage'       => ['cat_id' => 8,  'icon' => 'fa-hard-drive',       'label' => 'Storage'],
    'GPU'           => ['cat_id' => 3,  'icon' => 'fa-display',          'label' => 'Graphics Card'],
    'Monitor'       => ['cat_id' => 13, 'icon' => 'fa-desktop',          'label' => 'Monitor'],
    'PSU'           => ['cat_id' => 9,  'icon' => 'fa-bolt',             'label' => 'Power Supply'],
    'Case'          => ['cat_id' => 11, 'icon' => 'fa-box',              'label' => 'PC Case'],
    'Keyboard'      => ['cat_id' => 14, 'icon' => 'fa-keyboard',         'label' => 'Keyboard'],
    'Mouse'         => ['cat_id' => 15, 'icon' => 'fa-computer-mouse',   'label' => 'Mouse'],
    'CaseFan'       => ['cat_id' => 12, 'icon' => 'fa-fan',              'label' => 'Case Fan'],
    'Cooler'        => ['cat_id' => 10, 'icon' => 'fa-wind',             'label' => 'Cooler'],
];

// Fetch all products for each part
$partProducts = [];
foreach ($parts as $key => $info) {
    $partProducts[$key] = getPartsByCategory($dbh, $info['cat_id']);
}

// ── HANDLE ADD ALL TO CART ──
$addResult = '';
if ($isLoggedIn && isset($_POST['add_build_to_cart'])) {
    $selectedIds = json_decode($_POST['selected_product_ids'] ?? '[]', true);
    $addedCount  = 0;

    foreach ($selectedIds as $pid) {
        $pid = intval($pid);
        if ($pid <= 0) continue;

        // Fetch product
        $pStmt = $dbh->prepare("SELECT * FROM products WHERE product_id = ? AND stock > 0");
        $pStmt->execute([$pid]);
        $prod = $pStmt->fetch(PDO::FETCH_ASSOC);
        if (!$prod) continue;

        // Check existing cart
        $cStmt = $dbh->prepare("SELECT cart_id, quantity FROM tblcart WHERE user_id = ? AND product_id = ? AND status = 'active'");
        $cStmt->execute([$user_id, $pid]);
        $existing = $cStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQty = $existing['quantity'] + 1;
            if ($newQty <= $prod['stock']) {
                $dbh->prepare("UPDATE tblcart SET quantity=?, subtotal=product_price*? WHERE cart_id=?")
                    ->execute([$newQty, $newQty, $existing['cart_id']]);
            }
        } else {
            $dbh->prepare("INSERT INTO tblcart (user_id,product_id,product_name,product_image,product_price,quantity,subtotal,created_at,updated_at,status) VALUES (?,?,?,?,?,1,?,NOW(),NOW(),'active')")
                ->execute([$user_id, $pid, $prod['name'], $prod['image'], $prod['price'], $prod['price']]);
        }
        $addedCount++;
    }
    $addResult = $addedCount > 0 ? 'success' : 'empty';
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
/* ─── LAYOUT ─── */
.builder-wrap {
    display: flex;
    gap: 28px;
    align-items: flex-start;
}
.builder-parts { flex: 1; min-width: 0; }
.builder-summary {
    width: 320px;
    flex-shrink: 0;
    position: sticky;
    top: 100px;
}

/* ─── PART ROW ─── */
.part-row {
    background: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    margin-bottom: 12px;
    overflow: hidden;
    transition: border-color 0.25s;
}
.part-row.selected {
    border-color: #d4af37;
}
.part-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
}
.part-header:hover { background: #1a1a1a; }

.part-icon {
    width: 40px; height: 40px;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #d4af37;
    font-size: 1rem;
    flex-shrink: 0;
}
.part-label {
    flex: 1;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #aaa;
}
.part-selected-name {
    font-size: 0.82rem;
    color: #d4af37;
    font-weight: 500;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.part-selected-price {
    font-size: 0.82rem;
    color: #d4af37;
    font-weight: 700;
    margin-left: 10px;
    white-space: nowrap;
}
.part-chevron {
    color: #555;
    font-size: 0.8rem;
    transition: transform 0.25s;
    margin-left: 10px;
}
.part-row.open .part-chevron { transform: rotate(180deg); }

/* ─── DROPDOWN PANEL ─── */
.part-panel {
    display: none;
    border-top: 1px solid #2a2a2a;
    padding: 14px;
    background: #0f0f0f;
}
.part-row.open .part-panel { display: block; }

.parts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
}

/* None option */
.part-option {
    background: #181818;
    border: 1px solid #2a2a2a;
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 6px;
}
.part-option:hover {
    border-color: #555;
    background: #1e1e1e;
}
.part-option.active {
    border-color: #d4af37;
    background: rgba(212,175,55,0.07);
}
.part-option img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    border-radius: 6px;
    background: #1a1a1a;
}
.part-option .none-icon {
    width: 100%;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1a1a1a;
    border-radius: 6px;
    color: #444;
    font-size: 1.8rem;
}
.part-option .opt-name {
    font-size: 0.75rem;
    color: #ccc;
    line-height: 1.3;
    font-weight: 500;
}
.part-option .opt-price {
    font-size: 0.78rem;
    color: #d4af37;
    font-weight: 700;
}
.part-option .opt-price.none { color: #555; font-weight: 400; }

/* ─── SUMMARY PANEL ─── */
.summary-card {
    background: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 14px;
    padding: 22px 20px;
}
.summary-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: #fff;
    margin-bottom: 16px;
}
.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid #1e1e1e;
    gap: 8px;
}
.summary-item:last-of-type { border-bottom: none; }
.summary-part-label {
    font-size: 0.72rem;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    white-space: nowrap;
}
.summary-part-name {
    font-size: 0.78rem;
    color: #aaa;
    flex: 1;
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 130px;
    margin-left: 6px;
}
.summary-part-price {
    font-size: 0.82rem;
    color: #d4af37;
    font-weight: 700;
    white-space: nowrap;
    margin-left: 8px;
}
.summary-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid #2a2a2a;
}
.summary-total-label { font-size: 0.9rem; font-weight: 600; color: #fff; }
.summary-total-price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #d4af37;
    font-family: 'Playfair Display', serif;
}
.parts-count {
    font-size: 0.75rem;
    color: #555;
    margin-top: 4px;
}

/* ─── PAGE HERO ─── */
.page-hero {
    padding: 60px 0 40px;
    text-align: center;
}
.page-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.page-hero p { color: #666; letter-spacing: 2px; text-transform: uppercase; font-size: 0.85rem; }

/* ─── RESPONSIVE ─── */
@media (max-width: 991px) {
    .builder-wrap { flex-direction: column; }
    .builder-summary { width: 100%; position: static; }
}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<!-- PAGE HERO -->
<section class="page-hero">
    <div class="container">
        <h1>PC Builder</h1>
        <p>Customize your dream machine</p>
        <div class="accent-line mx-auto mt-3"></div>
    </div>
</section>

<div class="container pb-5">
<div class="builder-wrap">

    <!-- ══════════════════════════════ -->
    <!-- LEFT: PART SELECTOR           -->
    <!-- ══════════════════════════════ -->
    <div class="builder-parts">

        <?php foreach ($parts as $key => $info):
            $products = $partProducts[$key];
        ?>
        <div class="part-row" id="row-<?php echo $key; ?>">

            <!-- Header (click to expand) -->
            <div class="part-header" onclick="togglePanel('<?php echo $key; ?>')">
                <div class="part-icon">
                    <i class="fa <?php echo $info['icon']; ?>"></i>
                </div>
                <span class="part-label"><?php echo $info['label']; ?></span>
                <span class="part-selected-name" id="sel-name-<?php echo $key; ?>">— None selected</span>
                <span class="part-selected-price" id="sel-price-<?php echo $key; ?>" style="display:none;"></span>
                <i class="fa fa-chevron-down part-chevron"></i>
            </div>

            <!-- Dropdown panel -->
            <div class="part-panel">
                <div class="parts-grid">

                    <!-- NONE option -->
                    <div class="part-option active"
                         id="opt-<?php echo $key; ?>-none"
                         onclick="selectPart('<?php echo $key; ?>', 0, 'None', 0)">
                        <div class="none-icon"><i class="fa fa-ban"></i></div>
                        <div class="opt-name">None</div>
                        <div class="opt-price none">RM 0.00</div>
                    </div>

                    <?php if (empty($products)): ?>
                        <div style="color:#555; font-size:0.8rem; padding:10px; grid-column:1/-1;">
                            No products available in this category.
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <div class="part-option"
                             id="opt-<?php echo $key; ?>-<?php echo $p['product_id']; ?>"
                             onclick="selectPart('<?php echo $key; ?>', <?php echo $p['product_id']; ?>, <?php echo json_encode($p['name']); ?>, <?php echo $p['price']; ?>)">
                            <img src="<?php echo htmlspecialchars($p['image']); ?>"
                                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                                 onerror="this.src='assets/images/placeholder.jpg'">
                            <div class="opt-name"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="opt-price">RM <?php echo number_format($p['price'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>

        </div>
        <?php endforeach; ?>

    </div>

    <!-- ══════════════════════════════ -->
    <!-- RIGHT: SUMMARY PANEL          -->
    <!-- ══════════════════════════════ -->
    <div class="builder-summary">
        <div class="summary-card">

            <div class="summary-title">Build Summary</div>

            <?php foreach ($parts as $key => $info): ?>
            <div class="summary-item" id="summary-<?php echo $key; ?>">
                <span class="summary-part-label"><?php echo $info['label']; ?></span>
                <span class="summary-part-name" id="sum-name-<?php echo $key; ?>">—</span>
                <span class="summary-part-price" id="sum-price-<?php echo $key; ?>" style="display:none;"></span>
            </div>
            <?php endforeach; ?>

            <div class="summary-total-row">
                <div>
                    <div class="summary-total-label">Total</div>
                    <div class="parts-count" id="parts-count">0 parts selected</div>
                </div>
                <div class="summary-total-price" id="summary-grand-total">RM 0.00</div>
            </div>

            <!-- Add to Cart button -->
            <form method="POST" id="buildForm" class="mt-3">
                <input type="hidden" name="add_build_to_cart" value="1">
                <input type="hidden" name="selected_product_ids" id="selectedIdsInput" value="[]">

                <?php if (!$isLoggedIn): ?>
                    <a href="login.php" class="btn-gold d-block text-center text-decoration-none">
                        Login to Add to Cart
                    </a>
                <?php else: ?>
                    <button type="button" class="btn-gold" id="addBuildBtn" onclick="submitBuild()" disabled>
                        <i class="fa fa-cart-plus me-2"></i>Add Build to Cart
                    </button>
                <?php endif; ?>
            </form>

        </div>
    </div>

</div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── STATE ──
const build = {};      // key -> { id, name, price }
const partKeys = <?php echo json_encode(array_keys($parts)); ?>;

// ── TOGGLE PANEL ──
function togglePanel(key) {
    const row = document.getElementById('row-' + key);
    row.classList.toggle('open');
}

// ── SELECT PART ──
function selectPart(key, id, name, price) {

    // Deactivate all options in this group
    document.querySelectorAll('[id^="opt-' + key + '-"]').forEach(el => {
        el.classList.remove('active');
    });

    // Activate chosen
    const chosen = document.getElementById('opt-' + key + '-' + (id === 0 ? 'none' : id));
    if (chosen) chosen.classList.add('active');

    // Update state
    if (id === 0) {
        delete build[key];
    } else {
        build[key] = { id, name, price: parseFloat(price) };
    }

    // Update row header
    const selName  = document.getElementById('sel-name-' + key);
    const selPrice = document.getElementById('sel-price-' + key);
    const row      = document.getElementById('row-' + key);

    if (id === 0) {
        selName.textContent  = '— None selected';
        selName.style.color  = '#555';
        selPrice.style.display = 'none';
        row.classList.remove('selected');
    } else {
        selName.textContent  = name;
        selName.style.color  = '#d4af37';
        selPrice.textContent = 'RM ' + parseFloat(price).toLocaleString('en-MY', {minimumFractionDigits:2, maximumFractionDigits:2});
        selPrice.style.display = '';
        row.classList.add('selected');
        // Auto-close panel
        row.classList.remove('open');
    }

    // Update summary
    updateSummary();
}

// ── UPDATE SUMMARY ──
function updateSummary() {
    let total      = 0;
    let partsCount = 0;

    partKeys.forEach(function(key) {
        const sumName  = document.getElementById('sum-name-' + key);
        const sumPrice = document.getElementById('sum-price-' + key);

        if (build[key]) {
            sumName.textContent    = build[key].name;
            sumName.style.color    = '#ccc';
            sumPrice.textContent   = 'RM ' + build[key].price.toLocaleString('en-MY', {minimumFractionDigits:2, maximumFractionDigits:2});
            sumPrice.style.display = '';
            total      += build[key].price;
            partsCount++;
        } else {
            sumName.textContent    = '—';
            sumName.style.color    = '#444';
            sumPrice.style.display = 'none';
        }
    });

    document.getElementById('summary-grand-total').textContent =
        'RM ' + total.toLocaleString('en-MY', {minimumFractionDigits:2, maximumFractionDigits:2});

    document.getElementById('parts-count').textContent =
        partsCount + ' part' + (partsCount !== 1 ? 's' : '') + ' selected';

    // Enable/disable button
    const btn = document.getElementById('addBuildBtn');
    if (btn) btn.disabled = partsCount === 0;
}

// ── SUBMIT BUILD ──
function submitBuild() {
    const ids = Object.values(build).map(b => b.id);
    if (ids.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Parts Selected',
            text: 'Please select at least one part to add to cart.',
            background: '#1a1a1a',
            color: '#fff',
            confirmButtonColor: '#d4af37'
        });
        return;
    }

    document.getElementById('selectedIdsInput').value = JSON.stringify(ids);

    // Confirm
    Swal.fire({
        icon: 'question',
        title: 'Add Build to Cart?',
        html: ids.length + ' part(s) will be added to your cart.',
        background: '#1a1a1a',
        color: '#fff',
        confirmButtonColor: '#d4af37',
        cancelButtonColor: '#333',
        showCancelButton: true,
        confirmButtonText: 'Yes, Add All',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('buildForm').submit();
        }
    });
}
</script>

<?php if ($addResult === 'success'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Build Added to Cart!',
    text: 'All selected parts have been added to your cart.',
    background: '#1a1a1a',
    color: '#fff',
    confirmButtonColor: '#d4af37'
});
</script>
<?php elseif ($addResult === 'empty'): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Nothing Added',
    text: 'No valid parts were found. Please try again.',
    background: '#1a1a1a',
    color: '#fff',
    confirmButtonColor: '#d4af37'
});
</script>
<?php endif; ?>

</body>
</html>