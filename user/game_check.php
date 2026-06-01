<?php
session_start();
include('includes/config.php');
error_reporting(0);

$games = [
    [
        'name'    => 'Counter-Strike 2',
        'image'   => '../image/products/game_1.jpg',
        'cpu'     => 'Intel i5-10400F / Ryzen 5 3600',
        'ram'     => '16GB',
        'gpu'     => 'GTX 1660 Super',
        'storage' => '50GB SSD',
        'badge'   => 'Competitive',
        'badge_color' => '#d4af37',
    ],
    [
        'name'    => 'Valorant',
        'image'   => '../image/products/game_2.jpg',
        'cpu'     => 'Intel i3-12100F / Ryzen 5 5500',
        'ram'     => '16GB',
        'gpu'     => 'GTX 1650',
        'storage' => '30GB SSD',
        'badge'   => 'Tactical FPS',
        'badge_color' => '#d4af37',
    ],
    [
        'name'    => 'Black Myth: Wukong',
        'image'   => '../image/products/game_3.jpg',
        'cpu'     => 'Intel i5-14400F / Ryzen 7 5700X',
        'ram'     => '16GB',
        'gpu'     => 'RTX 4060 8GB',
        'storage' => '130GB SSD',
        'badge'   => 'Action RPG',
        'badge_color' => '#d4af37',
    ],
    [
        'name'    => 'EA Sports FC 26',
        'image'   => '../image/products/game_4.jpg',
        'cpu'     => 'Intel i5-12400F / Ryzen 5 5600',
        'ram'     => '16GB',
        'gpu'     => 'RTX 3050 8GB',
        'storage' => '100GB SSD',
        'badge'   => 'Sports',
        'badge_color' => '#d4af37',
    ],
    [
        'name'    => 'Grand Theft Auto VI',
        'image'   => '../image/products/game_5.png',
        'cpu'     => 'Intel i7-14700F / Ryzen 7 7700',
        'ram'     => '32GB',
        'gpu'     => 'RTX 4070 Super',
        'storage' => '150GB SSD',
        'badge'   => 'Open World',
        'badge_color' => '#d4af37',
    ],
    [
        'name'    => 'Forza Horizon 5',
        'image'   => '../image/products/game_6.png',
        'cpu'     => 'Intel i5-14400F / Ryzen 7 7700',
        'ram'     => '16GB',
        'gpu'     => 'RTX 4060 8GB',
        'storage' => '150GB SSD',
        'badge'   => 'Multiplayer Racing',
        'badge_color' => '#d4af37',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Game Check — My PC Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="newstyle.css">

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

/* ── GAME CARD ── */
.game-card {
    background: #121212;
    border: 1px solid #2a2a2a;
    border-radius: 14px;
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}
.game-card:hover {
    transform: translateY(-8px);
    border-color: #d4af37;
    box-shadow: 0 20px 50px rgba(212,175,55,0.15);
}

/* ── GAME BANNER ── */
.game-banner {
    position: relative;
    width: 100%;
    height: 180px;
    overflow: hidden;
}
.game-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
    filter: brightness(0.85);
}
.game-card:hover .game-banner img {
    transform: scale(1.06);
    filter: brightness(1);
}
.game-banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(18,18,18,0.95) 0%, transparent 60%);
}
.game-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #000;
}
.game-title-overlay {
    position: absolute;
    bottom: 12px;
    left: 14px;
    right: 14px;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
    line-height: 1.3;
}

/* ── SPEC TABLE ── */
.spec-body {
    padding: 16px;
}
.spec-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 9px 0;
    border-bottom: 1px solid #1e1e1e;
}
.spec-row:last-child { border-bottom: none; }
.spec-icon {
    width: 30px; height: 30px;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    color: #d4af37;
    font-size: 0.75rem;
    flex-shrink: 0;
    margin-top: 1px;
}
.spec-info { flex: 1; min-width: 0; }
.spec-label {
    font-size: 0.68rem;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 2px;
}
.spec-value {
    font-size: 0.82rem;
    color: #ddd;
    font-weight: 500;
    line-height: 1.3;
    word-break: break-word;
}

/* ── SHOP BUTTON ── */
.btn-shop {
    display: block;
    margin: 0 16px 16px;
    padding: 9px;
    background: linear-gradient(45deg, #d4af37, #c5a028);
    color: #000;
    font-weight: 700;
    font-size: 0.8rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-align: center;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s;
}
.btn-shop:hover {
    background: #fff;
    color: #000;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212,175,55,0.3);
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

/* ── SECTION LABEL ── */
.btn-cta i {
    color: #000 !important;
}

    font-size: 0.75rem;
    color: #d4af37;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 6px;
}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="page-hero">
    <div class="container">
        <p class="section-eyebrow">Performance Guide</p>
        <h1>Game Check</h1>
        <p>Minimum specs to run today's top titles smoothly</p>
        <div class="accent-line mx-auto mt-3"></div>
    </div>
</section>

<div class="container pb-5">

    <!-- Info Banner -->
    <div class="info-banner">
        <i class="fa fa-circle-info"></i>
        <p>
            The specs below are <strong>minimum recommended requirements</strong> for smooth gameplay at 1080p medium settings.
            For high/ultra settings or higher resolutions, consider upgrading your components.
        </p>
    </div>

    <!-- Game Cards Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

    <?php foreach ($games as $game): ?>
    <div class="col">
        <div class="game-card h-100">

            <!-- Banner -->
            <div class="game-banner">
                <img src="<?php echo $game['image']; ?>"
                     alt="<?php echo htmlspecialchars($game['name']); ?>"
                     onerror="this.src='assets/images/placeholder.jpg'">
                <div class="game-banner-overlay"></div>
                <span class="game-badge" style="background:<?php echo $game['badge_color']; ?>;">
                    <?php echo $game['badge']; ?>
                </span>
                <div class="game-title-overlay">
                    <?php echo htmlspecialchars($game['name']); ?>
                </div>
            </div>

            <!-- Specs -->
            <div class="spec-body">

                <div class="spec-row">
                    <div class="spec-icon"><i class="fa fa-microchip"></i></div>
                    <div class="spec-info">
                        <div class="spec-label">Processor (CPU)</div>
                        <div class="spec-value"><?php echo htmlspecialchars($game['cpu']); ?></div>
                    </div>
                </div>

                <div class="spec-row">
                    <div class="spec-icon"><i class="fa fa-memory"></i></div>
                    <div class="spec-info">
                        <div class="spec-label">Memory (RAM)</div>
                        <div class="spec-value"><?php echo htmlspecialchars($game['ram']); ?></div>
                    </div>
                </div>

                <div class="spec-row">
                    <div class="spec-icon"><i class="fa fa-display"></i></div>
                    <div class="spec-info">
                        <div class="spec-label">Graphics Card (GPU)</div>
                        <div class="spec-value"><?php echo htmlspecialchars($game['gpu']); ?></div>
                    </div>
                </div>

                <div class="spec-row">
                    <div class="spec-icon"><i class="fa fa-hard-drive"></i></div>
                    <div class="spec-info">
                        <div class="spec-label">Storage</div>
                        <div class="spec-value"><?php echo htmlspecialchars($game['storage']); ?></div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <?php endforeach; ?>

    </div>

    <!-- Bottom CTA -->
    <div class="text-center mt-5 pt-3">
        <p class="text-soft mb-3">Want to build a PC for any of these games?</p>
        <a href="pcbuild.php" class="btn-cta me-2">PC Builder</a>
        <a href="product.php" class="btn-cta">Browse All Products</a>
    </div>

</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>