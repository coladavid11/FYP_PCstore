<?php
session_start();
include('includes/config.php');
error_reporting(0);

$isLoggedIn = isset($_SESSION['login']);
$user_id    = $_SESSION['user_id'] ?? null;

$sets = [
    [
        'id'       => 1,
        'name'     => 'Office Set',
        'tagline'  => 'Perfect for everyday use & light gaming',
        'price'    => 2499.00,
        'image'    => '../image/products/set_1.jpg',
        'badge'    => 'Budget',
        'for'      => 'Students & casual users who need a reliable daily driver for browsing, office work, and light gaming.',
        'specs'    => [
    'CPU'         => 'Intel Core i5-14400',
    'GPU'         => 'Intel UHD Graphics 730',
    'RAM'         => 'Kingston Fury Beast 16GB DDR5',
    'Storage'     => 'Kingston NV2 1TB PCIe 4.0 NVMe SSD',
    'Motherboard' => 'MSI PRO B760M-A WIFI DDR5',
    'Power Supply'=> 'Corsair CX650 650W 80+ Bronze',
    'Cooler'      => 'Intel Laminar RM1 Stock Cooler',
    'Case'        => 'Montech XR',

    'Monitor'     => 'MSI G274F 27" Full HD IPS Monitor',
    'Keyboard'    => 'Logitech K120 Wired Keyboard',
    'Mouse'       => 'Logitech B100 Optical Mouse',
        ],
    ],
    [
        'id'       => 2,
        'name'     => 'Mainstream Gamer',
        'tagline'  => 'Smooth 1080p gaming on all popular titles',
        'price'    => 4599.00,
        'image'    => '../image/products/set_2.jpg',
        'badge'    => 'Popular',
        'for'      => 'Gamers who want buttery-smooth 1080p performance on titles like CS2, Valorant, and FC 26.',
        'specs'    => [
            'CPU'         => 'Intel Core i5-14400F',
            'GPU'         => 'RTX 4060 8GB',
            'RAM'         => 'Kingston Fury Beast 16GB DDR5',
            'Storage'     => 'Kingston NV2 1TB SSD',
            'Motherboard' => 'MSI PRO B760M-A WIFI DDR5',
            'Power Supply'=> 'Corsair CX650 650W',
            'Cooler'      => 'DeepCool AK400',
            'Case'        => 'Montech XR',
            'Monitor'     => 'MSI G274F 27" 180Hz',
            'Keyboard'    => 'Logitech K120',
            'Mouse'       => 'Logitech G102',
        ],
    ],
    [
        'id'       => 3,
        'name'     => 'High Performance',
        'tagline'  => 'Dominate at 1440p with high framerates',
        'price'    => 6999.00,
        'image'    => '../image/products/set_3.jpg',
        'badge'    => 'Best Value',
        'for'      => 'Competitive and enthusiast gamers who demand high FPS at 1440p on demanding titles.',
        'specs'    => [
            'CPU'         => 'Intel Core i5-14600KF',
            'GPU'         => 'RTX 4070 12GB',
            'RAM'         => 'G.Skill Trident Z5 32GB DDR5',
            'Storage'     => 'Samsung 980 Pro 1TB NVMe',
            'Motherboard' => 'MSI MAG B760 TOMAHAWK WIFI',
            'Power Supply'=> 'Corsair RM750x 750W',
            'Cooler'      => 'DeepCool AK620',
            'Case'        => 'Lian Li Lancool 216',
            'Monitor'     => 'LG 27GP850-B 27" 165Hz QHD',
            'Keyboard'    => 'Keychron K2 Mechanical',
            'Mouse'       => 'Logitech G304',
        ],
    ],
    [
        'id'       => 4,
        'name'     => 'Content Creator',
        'tagline'  => 'Built for streaming, editing & multitasking',
        'price'    => 8499.00,
        'image'    => '../image/products/set_4.jpg',
        'badge'    => 'Creator',
        'for'      => 'Content creators, streamers, and video editors who need powerful multi-core performance and fast storage.',
        'specs'    => [
            'CPU'         => 'AMD Ryzen 7 7700X',
            'GPU'         => 'RTX 4070 Super 12GB',
            'RAM'         => 'Corsair Vengeance 32GB DDR5',
            'Storage'     => 'WD Black SN850X 2TB NVMe',
            'Motherboard' => 'ASUS TUF Gaming X670E-Plus WIFI',
            'Power Supply'=> 'be quiet! Straight Power 850W',
            'Cooler'      => 'Noctua NH-D15',
            'Case'        => 'Fractal Design North',
            'Monitor'     => 'LG 32UN880 32" 4K UltraWide',
            'Keyboard'    => 'Logitech MX Keys',
            'Mouse'       => 'Logitech MX Master 3S',
        ],
    ],
    [
        'id'       => 5,
        'name'     => 'Ultimate Gaming',
        'tagline'  => 'Uncompromised 4K gaming beast',
        'price'    => 12999.00,
        'image'    => '../image/products/set_5.jpg',
        'badge'    => 'Top Tier',
        'for'      => 'Hardcore gamers and enthusiasts who want the best 4K gaming experience with no compromises.',
        'specs'    => [
            'CPU'         => 'Intel Core i7-14700KF',
            'GPU'         => 'RTX 4080 Super 16GB',
            'RAM'         => 'G.Skill Trident Z5 RGB 64GB DDR5',
            'Storage'     => 'Samsung 990 Pro 2TB NVMe',
            'Motherboard' => 'ASUS ROG Strix Z790-E WIFI',
            'Power Supply'=> 'Corsair HX1000 1000W Platinum',
            'Cooler'      => 'NZXT Kraken 360 AIO',
            'Case'        => 'Lian Li O11 Dynamic EVO',
            'Monitor'     => 'ASUS ROG Swift PG32UQX 32" 4K 144Hz',
            'Keyboard'    => 'Corsair K100 RGB Mechanical',
            'Mouse'       => 'Logitech G Pro X Superlight 2',
        ],
    ],
    [
        'id'       => 6,
        'name'     => 'Workstation Pro',
        'tagline'  => 'Professional-grade power for serious work',
        'price'    => 18999.00,
        'image'    => '../image/products/set_6.jpg',
        'badge'    => 'Pro',
        'for'      => '3D artists, architects, and professionals running heavy workloads like rendering, simulation, and AI tasks.',
        'specs'    => [
            'CPU'         => 'Intel Core i9-14900K',
            'GPU'         => 'RTX 4090 24GB',
            'RAM'         => 'Corsair Dominator Platinum 128GB DDR5',
            'Storage'     => 'Samsung 990 Pro 4TB NVMe',
            'Motherboard' => 'ASUS ProArt Z790-CREATOR WIFI',
            'Power Supply'=> 'Seasonic PRIME TX-1300 1300W Titanium',
            'Cooler'      => 'Custom 360mm AIO',
            'Case'        => 'Fractal Design Torrent XL',
            'Monitor'     => 'ASUS ProArt PA32UCG 32" 4K 120Hz',
            'Keyboard'    => 'Das Keyboard 6 Professional',
            'Mouse'       => 'Logitech MX Master 3S',
        ],
    ],
];

// ── ADD TO CART ──
$addResult  = '';
$addMessage = '';

if ($isLoggedIn && isset($_POST['add_set_to_cart'])) {
    $set_id = intval($_POST['set_id']);
    $set    = null;
    foreach ($sets as $s) {
        if ($s['id'] == $set_id) { $set = $s; break; }
    }

    if ($set) {
        // Check if already in cart
        $check = $dbh->prepare("SELECT cart_id, quantity FROM tblcart WHERE user_id = ? AND product_name = ? AND status = 'active'");
        $check->execute([$user_id, $set['name'] . ' PC Set']);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQty = $existing['quantity'] + 1;
            $dbh->prepare("UPDATE tblcart SET quantity = ?, subtotal = product_price * ? WHERE cart_id = ?")
                ->execute([$newQty, $newQty, $existing['cart_id']]);
        } else {
            $dbh->prepare("INSERT INTO tblcart (user_id, product_id, product_name, product_image, product_price, quantity, subtotal, created_at, updated_at, status) VALUES (?, 0, ?, ?, ?, 1, ?, NOW(), NOW(), 'active')")
                ->execute([$user_id, $set['name'] . ' PC Set', $set['image'], $set['price'], $set['price']]);
        }
        $addResult  = 'success';
        $addMessage = $set['name'] . ' PC Set added to cart!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Full PC Sets — My PC Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="newstyle.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ── PAGE HERO ── */
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
.page-hero p {
    color: #666;
    letter-spacing: 2px;
    text-transform: uppercase;
    font-size: 0.85rem;
}
.section-eyebrow {
    font-size: 0.75rem;
    color: #d4af37;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

/* ── INFO BANNER ── */
.info-banner {
    background: linear-gradient(135deg, #1a1a1a 0%, #121212 100%);
    border: 1px solid #2a2a2a;
    border-left: 3px solid #d4af37;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.info-banner i { color: #d4af37; font-size: 1.2rem; flex-shrink: 0; }
.info-banner p { margin: 0; color: #888; font-size: 0.85rem; line-height: 1.5; }
.info-banner strong { color: #d4af37; }

/* ── SET CARD ── */
.set-card {
    background: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 14px;
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.set-card:hover {
    transform: translateY(-8px);
    border-color: #d4af37;
    box-shadow: 0 20px 50px rgba(212,175,55,0.15);
}

/* ── SET BANNER ── */
.set-banner {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
}
.set-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
    filter: brightness(0.85);
}
.set-card:hover .set-banner img {
    transform: scale(1.06);
    filter: brightness(1);
}
.set-banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(18,18,18,0.95) 0%, transparent 55%);
}
.set-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: #d4af37;
    color: #000;
}
.set-price-overlay {
    position: absolute;
    bottom: 12px;
    right: 14px;
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #d4af37;
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
}
.set-name-overlay {
    position: absolute;
    bottom: 12px;
    left: 14px;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
}

/* ── CARD BODY ── */
.set-body {
    padding: 18px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.set-tagline {
    font-size: 0.8rem;
    color: #d4af37;
    font-weight: 500;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

/* ── FOR WHOM ── */
.set-for {
    background: #1a1a1a;
    border-left: 2px solid #d4af37;
    border-radius: 0 6px 6px 0;
    padding: 10px 12px;
    margin-bottom: 14px;
}
.set-for-label {
    font-size: 0.65rem;
    color: #d4af37;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
}
.set-for-text {
    font-size: 0.78rem;
    color: #888;
    line-height: 1.4;
}

/* ── SPECS TABLE ── */
.specs-toggle {
    font-size: 0.75rem;
    color: #555;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 10px;
    user-select: none;
    transition: color 0.2s;
}
.specs-toggle:hover { color: #d4af37; }
.specs-toggle i { transition: transform 0.25s; font-size: 0.65rem; }
.specs-toggle.open i { transform: rotate(180deg); }

.specs-list {
    display: none;
    margin-bottom: 14px;
}
.specs-list.show { display: block; }

.spec-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 7px 0;
    border-bottom: 1px solid #1a1a1a;
}
.spec-row:last-child { border-bottom: none; }
.spec-icon {
    width: 26px; height: 26px;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    color: #d4af37;
    font-size: 0.65rem;
    flex-shrink: 0;
}
.spec-label {
    font-size: 0.68rem;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    width: 80px;
    flex-shrink: 0;
    padding-top: 2px;
}
.spec-value {
    font-size: 0.78rem;
    color: #ccc;
    flex: 1;
    line-height: 1.3;
}

/* ── ADD TO CART BTN ── */
.btn-add-cart {
    display: block;
    width: 100%;
    padding: 11px;
    background: linear-gradient(45deg, #d4af37, #c5a028);
    color: #000;
    font-weight: 700;
    font-size: 0.82rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: auto;
}
.btn-add-cart:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212,175,55,0.3);
}
.btn-add-cart:disabled {
    background: #2a2a2a;
    color: #555;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ── SPEC ICONS MAP ── */
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="page-hero">
    <div class="container">
        <p class="section-eyebrow">Ready to Ship</p>
        <h1>Full PC Sets</h1>
        <p>Complete setups — just plug in and play</p>
        <div class="accent-line mx-auto mt-3"></div>
    </div>
</section>

<div class="container pb-5">

    <!-- Info Banner -->
    <div class="info-banner">
        <i class="fa fa-circle-info"></i>
        <p>
            All sets include <strong>monitor, keyboard and mouse</strong>. Prices are inclusive of all components listed.
        </p>
    </div>

    <!-- Sets Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

    <?php foreach ($sets as $set):
        $specIconMap = [
            'CPU'         => 'fa-microchip',
            'GPU'         => 'fa-display',
            'RAM'         => 'fa-memory',
            'Storage'     => 'fa-hard-drive',
            'Motherboard' => 'fa-server',
            'PSU'         => 'fa-bolt',
            'Cooler'      => 'fa-wind',
            'Case'        => 'fa-box',
            'Monitor'     => 'fa-desktop',
            'Keyboard'    => 'fa-keyboard',
            'Mouse'       => 'fa-computer-mouse',
        ];
    ?>
    <div class="col">
        <div class="set-card">

            <!-- Banner -->
            <div class="set-banner">
                <img src="<?php echo $set['image']; ?>"
                     alt="<?php echo htmlspecialchars($set['name']); ?>"
                     onerror="this.src='assets/images/placeholder.jpg'">
                <div class="set-banner-overlay"></div>
                <span class="set-badge"><?php echo $set['badge']; ?></span>
                <div class="set-name-overlay"><?php echo htmlspecialchars($set['name']); ?></div>
                <div class="set-price-overlay">RM <?php echo number_format($set['price'], 2); ?></div>
            </div>

            <!-- Body -->
            <div class="set-body">

                <div class="set-tagline">
                    <i class="fa fa-star me-1" style="font-size:0.65rem;"></i>
                    <?php echo htmlspecialchars($set['tagline']); ?>
                </div>

                <!-- For Whom -->
                <div class="set-for">
                    <div class="set-for-label"><i class="fa fa-user me-1"></i> Best For</div>
                    <div class="set-for-text"><?php echo htmlspecialchars($set['for']); ?></div>
                </div>

                <!-- Specs Toggle -->
                <div class="specs-toggle" onclick="toggleSpecs(this, 'specs-<?php echo $set['id']; ?>')">
                    <i class="fa fa-chevron-down"></i>
                    <span>View Full Specifications</span>
                </div>

                <div class="specs-list" id="specs-<?php echo $set['id']; ?>">
                    <?php foreach ($set['specs'] as $label => $value):
                        $icon = $specIconMap[$label] ?? 'fa-circle';
                    ?>
                    <div class="spec-row">
                        <div class="spec-icon"><i class="fa <?php echo $icon; ?>"></i></div>
                        <div class="spec-label"><?php echo $label; ?></div>
                        <div class="spec-value"><?php echo htmlspecialchars($value); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add to Cart -->
                <?php if ($isLoggedIn): ?>
                <form method="POST">
                    <input type="hidden" name="set_id" value="<?php echo $set['id']; ?>">
                    <button type="submit" name="add_set_to_cart" class="btn-add-cart">
                        <i class="fa fa-cart-plus me-2"></i>Add to Cart — RM <?php echo number_format($set['price'], 2); ?>
                    </button>
                </form>
                <?php else: ?>
                <a href="login.php" class="btn-add-cart" style="text-align:center; text-decoration:none; display:block;">
                    <i class="fa fa-lock me-2"></i>Login to Add to Cart
                </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <?php endforeach; ?>

    </div>

    <!-- Bottom CTA -->
    <div class="text-center mt-5 pt-3">
        <p class="text-soft mb-3">Prefer to pick your own components?</p>
        <a href="pcbuild.php" class="btn-cta me-2">PC Builder</a>
        <a href="product.php" class="btn-cta">Browse All Products</a>
    </div>

</div>

<?php include('includes/footer.php'); ?>

<script>
function toggleSpecs(el, id) {
    const list = document.getElementById(id);
    list.classList.toggle('show');
    el.classList.toggle('open');
    el.querySelector('span').textContent =
        list.classList.contains('show') ? 'Hide Specifications' : 'View Full Specifications';
}
</script>

<?php if ($addResult === 'success'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Added to Cart!',
    text: '<?php echo addslashes($addMessage); ?>',
    background: '#1a1a1a',
    color: '#fff',
    confirmButtonColor: '#d4af37',
    timer: 2000,
    showConfirmButton: false,
    timerProgressBar: true
});
</script>
<?php elseif ($addResult === 'error'): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'Something went wrong. Please try again.',
    background: '#1a1a1a',
    color: '#fff',
    confirmButtonColor: '#d4af37'
});
</script>
<?php endif; ?>

</body>
</html>