<?php
session_start();
include('includes/config.php');

$game_id = intval($_GET['id'] ?? 0);
if ($game_id <= 0) {
    header('Location: game_check.php');
    exit();
}

/* ── FETCH GAME ── */
$stmt = $dbh->prepare("SELECT * FROM tblgamecheck WHERE game_id = ? AND status = 1 LIMIT 1");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$game) {
    header('Location: game_check.php');
    exit();
}

/* ── PREV / NEXT GAME FOR NAVIGATION ── */
$prevStmt = $dbh->prepare("SELECT game_id, name FROM tblgamecheck WHERE game_id < ? AND status=1 ORDER BY game_id DESC LIMIT 1");
$prevStmt->execute([$game_id]);
$prevGame = $prevStmt->fetch(PDO::FETCH_ASSOC);

$nextStmt = $dbh->prepare("SELECT game_id, name FROM tblgamecheck WHERE game_id > ? AND status=1 ORDER BY game_id ASC LIMIT 1");
$nextStmt->execute([$game_id]);
$nextGame = $nextStmt->fetch(PDO::FETCH_ASSOC);

/* ── RECOMMENDED PRODUCTS BY CATEGORY ──
   rec_category_ids is comma-separated e.g. "4,6,7,3"
   We fetch up to 3 products per category group:
     Gaming Laptop (4), Gaming PC Set (2) → "Complete Build" section
     GPU (3), CPU (6), RAM (7)            → "Upgrade Components" section
*/
$recProducts = [];

if (!empty($game['rec_category_ids'])) {
    $catIds = array_map('intval', explode(',', $game['rec_category_ids']));
    $catIds = array_filter($catIds); // remove zeros

    if (!empty($catIds)) {
        $placeholders = implode(',', array_fill(0, count($catIds), '?'));

        /* Fetch up to 4 products per category, sorted by newest */
        $prodStmt = $dbh->prepare("
            SELECT p.*, c.category_name, b.brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN tblbrand   b ON p.brand_id    = b.brand_id
            WHERE p.category_id IN ($placeholders)
              AND p.stock > 0
            ORDER BY p.category_id ASC, p.created_at DESC
        ");
        $prodStmt->execute($catIds);
        $allProds = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

        /* Group by category, keep max 3 per category */
        $grouped = [];
        foreach ($allProds as $p) {
            $cname = $p['category_name'];
            if (!isset($grouped[$cname]))
                $grouped[$cname] = [];
            if (count($grouped[$cname]) < 3)
                $grouped[$cname][] = $p;
        }
        $recProducts = $grouped;
    }
}

/* ── HELPER: demand level label based on GPU tier ── */
function demandLevel(string $gpu): array
{
    $g = strtolower($gpu);
    if (str_contains($g, '4080') || str_contains($g, '4090') || str_contains($g, '7900')) {
        return ['label' => 'Demanding', 'color' => '#dc3545', 'icon' => 'fa-fire'];
    } elseif (str_contains($g, '4070') || str_contains($g, '4060') || str_contains($g, '6800')) {
        return ['label' => 'Moderate', 'color' => '#ffc107', 'icon' => 'fa-bolt'];
    } else {
        return ['label' => 'Entry-Level', 'color' => '#28a745', 'icon' => 'fa-leaf'];
    }
}
$demand = demandLevel($game['min_gpu']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['name']); ?> PC Requirements — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        body {
            background: #0f0f0f;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        /* ── BREADCRUMB ── */
        .breadcrumb-dark {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .breadcrumb-dark a {
            color: #555;
            text-decoration: none;
            font-size: 0.78rem;
            transition: color 0.2s;
        }

        .breadcrumb-dark a:hover {
            color: #d4af37;
        }

        .breadcrumb-dark .sep {
            color: #222;
            font-size: 0.6rem;
        }

        .breadcrumb-dark .cur {
            color: #888;
            font-size: 0.78rem;
        }

        /* ── HERO BANNER ── */
        .game-hero {
            position: relative;
            width: 100%;
            height: 380px;
            overflow: hidden;
            border-radius: 16px;
            margin-bottom: 36px;
        }

        .game-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.55);
        }

        .game-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 15, 15, 1) 0%, rgba(15, 15, 15, 0.3) 60%, transparent 100%);
        }

        .game-hero-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 28px 32px;
        }

        .game-hero-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 10px;
        }

        .game-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .game-hero-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .hero-meta-item {
            font-size: 0.78rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hero-meta-item i {
            color: #d4af37;
            font-size: 0.7rem;
        }

        /* ── PANEL ── */
        .panel {
            background: #121212;
            border: 1px solid #1e1e1e;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .panel-title {
            font-size: 0.7rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title i {
            color: #d4af37;
        }

        /* ── SPEC COMPARISON TABLE ── */
        .spec-compare {
            display: grid;
            grid-template-columns: 120px 1fr 1fr;
            gap: 0;
            border-radius: 10px;
            overflow: hidden;
        }

        .sc-header {
            padding: 10px 14px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            text-align: center;
        }

        .sc-header.min-col {
            background: #1a1a1a;
            color: #888;
        }

        .sc-header.rec-col {
            background: rgba(212, 175, 55, 0.12);
            color: #d4af37;
            border-left: 1px solid rgba(212, 175, 55, 0.2);
        }

        .sc-header.label-col {
            background: #161616;
            color: #555;
            text-align: left;
        }

        .sc-row {
            display: contents;
        }

        .sc-cell {
            padding: 12px 14px;
            border-top: 1px solid #1a1a1a;
            font-size: 0.83rem;
            vertical-align: middle;
        }

        .sc-cell.label-col {
            background: #161616;
            color: #555;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sc-cell.label-col i {
            color: #d4af37;
            width: 14px;
            text-align: center;
        }

        .sc-cell.min-col {
            background: #1a1a1a;
            color: #ccc;
        }

        .sc-cell.rec-col {
            background: rgba(212, 175, 55, 0.06);
            color: #e8c84a;
            font-weight: 600;
            border-left: 1px solid rgba(212, 175, 55, 0.15);
        }

        /* ── DEMAND BADGE ── */
        .demand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid;
        }

        /* ── DESCRIPTION ── */
        .game-desc {
            font-size: 0.9rem;
            color: #999;
            line-height: 1.75;
        }

        /* ── PRODUCT CARD ── */
        .prod-card {
            background: #161616;
            border: 1px solid #1e1e1e;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.25s;
            text-decoration: none;
            color: #fff;
            display: block;
            height: 100%;
        }

        .prod-card:hover {
            border-color: #d4af37;
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(212, 175, 55, 0.15);
            color: #fff;
            text-decoration: none;
        }

        .prod-img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            transition: transform 0.35s;
        }

        .prod-card:hover .prod-img {
            transform: scale(1.05);
        }

        .prod-body {
            padding: 12px 14px 14px;
        }

        .prod-cat {
            font-size: 0.68rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 3px;
        }

        .prod-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #ddd;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .prod-price {
            color: #d4af37;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .btn-prod {
            display: block;
            margin-top: 10px;
            padding: 7px;
            border-radius: 6px;
            text-align: center;
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: all 0.2s;
        }

        .btn-prod:hover {
            background: #fff;
            color: #000;
        }

        /* ── SECTION TITLE ── */
        .section-eyebrow {
            font-size: 0.7rem;
            color: #d4af37;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .section-h {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: #fff;
            margin-bottom: 4px;
        }

        .section-sub {
            font-size: 0.8rem;
            color: #555;
            margin-bottom: 24px;
        }

        /* ── NAV ARROWS ── */
        .game-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            gap: 16px;
        }

        .nav-arrow {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 10px;
            border: 1px solid #2a2a2a;
            background: #121212;
            color: #aaa;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
            max-width: 260px;
        }

        .nav-arrow:hover {
            border-color: #d4af37;
            color: #d4af37;
        }

        .nav-arrow .arrow-label {
            font-size: 0.65rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-arrow .arrow-name {
            font-weight: 600;
            color: #ddd;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-arrow.disabled {
            opacity: 0.3;
            pointer-events: none;
        }

        /* ── INFO STRIP ── */
        .info-strip {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .info-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #141414;
            border: 1px solid #1e1e1e;
            border-radius: 8px;
            padding: 10px 14px;
            flex: 1;
            min-width: 130px;
        }

        .info-chip i {
            color: #d4af37;
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        .info-chip .ic-lbl {
            font-size: 0.65rem;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .info-chip .ic-val {
            font-size: 0.85rem;
            color: #ccc;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <div class="container py-5">

        <!-- BREADCRUMB -->
        <div class="breadcrumb-dark">
            <a href="index.php">Home</a>
            <span class="sep"><i class="fa fa-chevron-right"></i></span>
            <a href="game_check.php">Game Check</a>
            <span class="sep"><i class="fa fa-chevron-right"></i></span>
            <span class="cur"><?php echo htmlspecialchars($game['name']); ?></span>
        </div>

        <!-- HERO BANNER -->
        <div class="game-hero">
            <img src="<?php echo htmlspecialchars($game['image']); ?>"
                alt="<?php echo htmlspecialchars($game['name']); ?>" onerror="this.src='assets/images/placeholder.jpg'">
            <div class="game-hero-overlay"></div>
            <div class="game-hero-content">
                <span class="game-hero-badge" style="background:<?php echo htmlspecialchars($game['badge_color']); ?>;">
                    <?php echo htmlspecialchars($game['badge']); ?>
                </span>
                <div class="game-hero-title"><?php echo htmlspecialchars($game['name']); ?></div>
                <div class="game-hero-meta">
                    <?php if ($game['developer']): ?>
                        <span class="hero-meta-item"><i class="fa fa-building"></i>
                            <?php echo htmlspecialchars($game['developer']); ?></span>
                    <?php endif; ?>
                    <?php if ($game['release_year']): ?>
                        <span class="hero-meta-item"><i class="fa fa-calendar"></i>
                            <?php echo $game['release_year']; ?></span>
                    <?php endif; ?>
                    <?php if ($game['genre']): ?>
                        <span class="hero-meta-item"><i class="fa fa-tag"></i>
                            <?php echo htmlspecialchars($game['genre']); ?></span>
                    <?php endif; ?>
                    <span class="demand-badge"
                        style="color:<?php echo $demand['color']; ?>; background:<?php echo $demand['color']; ?>1a; border-color:<?php echo $demand['color']; ?>44;">
                        <i class="fa <?php echo $demand['icon']; ?>"></i> <?php echo $demand['label']; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- LEFT: Main Content -->
            <div class="col-lg-8">

                <!-- DESCRIPTION -->
                <?php if (!empty($game['description'])): ?>
                    <div class="panel">
                        <div class="panel-title"><i class="fa fa-align-left"></i> About this Game</div>
                        <p class="game-desc mb-0"><?php echo nl2br(htmlspecialchars($game['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- SPEC COMPARISON: Min vs Recommended -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-sliders"></i> PC Requirements</div>

                    <div class="spec-compare">
                        <!-- Header Row -->
                        <div class="sc-header label-col"></div>
                        <div class="sc-header min-col"><i class="fa fa-arrow-down me-1"></i> Minimum</div>
                        <div class="sc-header rec-col"><i class="fa fa-star me-1"></i> Recommended</div>

                        <!-- CPU -->
                        <div class="sc-cell label-col"><i class="fa fa-microchip"></i> CPU</div>
                        <div class="sc-cell min-col"><?php echo htmlspecialchars($game['min_cpu']); ?></div>
                        <div class="sc-cell rec-col">
                            <?php echo htmlspecialchars($game['rec_cpu'] ?? $game['min_cpu']); ?></div>

                        <!-- RAM -->
                        <div class="sc-cell label-col"><i class="fa fa-memory"></i> RAM</div>
                        <div class="sc-cell min-col"><?php echo htmlspecialchars($game['min_ram']); ?></div>
                        <div class="sc-cell rec-col">
                            <?php echo htmlspecialchars($game['rec_ram'] ?? $game['min_ram']); ?></div>

                        <!-- GPU -->
                        <div class="sc-cell label-col"><i class="fa fa-display"></i> GPU</div>
                        <div class="sc-cell min-col"><?php echo htmlspecialchars($game['min_gpu']); ?></div>
                        <div class="sc-cell rec-col">
                            <?php echo htmlspecialchars($game['rec_gpu'] ?? $game['min_gpu']); ?></div>

                        <!-- Storage -->
                        <div class="sc-cell label-col"><i class="fa fa-hard-drive"></i> Storage</div>
                        <div class="sc-cell min-col"><?php echo htmlspecialchars($game['min_storage']); ?></div>
                        <div class="sc-cell rec-col">
                            <?php echo htmlspecialchars($game['rec_storage'] ?? $game['min_storage']); ?></div>
                    </div>

                    <!-- Note -->
                    <p style="font-size:0.75rem;color:#444;margin-top:14px;margin-bottom:0;">
                        <i class="fa fa-circle-info me-1" style="color:#555;"></i>
                        Minimum = playable at 1080p medium settings. Recommended = smooth 1080p high/ultra or 1440p
                        medium.
                    </p>
                </div>

            </div><!-- end left col -->

            <!-- RIGHT: Quick Info Sidebar -->
            <div class="col-lg-4">

                <!-- QUICK INFO CHIPS -->
                <div class="panel">
                    <div class="panel-title"><i class="fa fa-circle-info"></i> Quick Info</div>
                    <div class="d-flex flex-column gap-2">
                        <?php if ($game['developer']): ?>
                            <div class="info-chip">
                                <i class="fa fa-building"></i>
                                <div>
                                    <div class="ic-lbl">Developer</div>
                                    <div class="ic-val"><?php echo htmlspecialchars($game['developer']); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($game['release_year']): ?>
                            <div class="info-chip">
                                <i class="fa fa-calendar-days"></i>
                                <div>
                                    <div class="ic-lbl">Release Year</div>
                                    <div class="ic-val"><?php echo $game['release_year']; ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($game['genre']): ?>
                            <div class="info-chip">
                                <i class="fa fa-gamepad"></i>
                                <div>
                                    <div class="ic-lbl">Genre</div>
                                    <div class="ic-val"><?php echo htmlspecialchars($game['genre']); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="info-chip">
                            <i class="fa <?php echo $demand['icon']; ?>"
                                style="color:<?php echo $demand['color']; ?>;"></i>
                            <div>
                                <div class="ic-lbl">Hardware Demand</div>
                                <div class="ic-val" style="color:<?php echo $demand['color']; ?>;">
                                    <?php echo $demand['label']; ?></div>
                            </div>
                        </div>
                        <div class="info-chip">
                            <i class="fa fa-hard-drive"></i>
                            <div>
                                <div class="ic-lbl">Min Storage Needed</div>
                                <div class="ic-val"><?php echo htmlspecialchars($game['min_storage']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MIN SPECS SUMMARY -->
                <div class="panel" style="background:#0d0d0d;">
                    <div class="panel-title"><i class="fa fa-list-check"></i> Minimum at a Glance</div>
                    <?php
                    $minSpecs = [
                        ['fa fa-microchip', 'CPU', $game['min_cpu']],
                        ['fa fa-memory', 'RAM', $game['min_ram']],
                        ['fa fa-display', 'GPU', $game['min_gpu']],
                        ['fa fa-hard-drive', 'Storage', $game['min_storage']],
                    ];
                    foreach ($minSpecs as [$icon, $lbl, $val]): ?>
                        <div
                            style="display:flex;gap:10px;align-items:flex-start;padding:9px 0;border-bottom:1px solid #151515;">
                            <div
                                style="width:28px;height:28px;border-radius:6px;background:#141414;border:1px solid #1e1e1e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="<?php echo $icon; ?>" style="color:#d4af37;font-size:0.7rem;"></i>
                            </div>
                            <div>
                                <div
                                    style="font-size:0.65rem;color:#444;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:1px;">
                                    <?php echo $lbl; ?></div>
                                <div style="font-size:0.8rem;color:#bbb;font-weight:500;">
                                    <?php echo htmlspecialchars($val); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="border-bottom:none;"></div>
                </div>

            </div><!-- end right col -->

        </div><!-- end row -->

        <!-- ════════════════════════════════════════════
         RECOMMENDED PRODUCTS SECTION
    ════════════════════════════════════════════ -->
        <?php if (!empty($recProducts)): ?>
            <div class="mt-5">
                <div class="section-eyebrow">From Our Store</div>
                <div class="section-h">Recommended Products</div>
                <p class="section-sub">Products from our store that meet or exceed the requirements for <strong
                        style="color:#d4af37;"><?php echo htmlspecialchars($game['name']); ?></strong></p>

                <?php foreach ($recProducts as $categoryName => $products): ?>
                    <div class="mb-4">
                        <!-- Category Header -->
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                            <div style="width:3px;height:22px;background:#d4af37;border-radius:2px;flex-shrink:0;"></div>
                            <h5 style="margin:0;font-size:1rem;color:#ddd;font-weight:600;">
                                <?php echo htmlspecialchars($categoryName); ?></h5>
                            <div style="flex:1;height:1px;background:#1a1a1a;"></div>
                            <a href="product.php?category=<?php echo $products[0]['category_id'] ?? ''; ?>"
                                style="font-size:0.72rem;color:#555;text-decoration:none;white-space:nowrap;letter-spacing:0.5px;text-transform:uppercase;"
                                onmouseover="this.style.color='#d4af37'" onmouseout="this.style.color='#555'">
                                View All <i class="fa fa-arrow-right" style="font-size:0.6rem;"></i>
                            </a>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                            <?php foreach ($products as $p): ?>
                                <div class="col">
                                    <a href="product_details.php?id=<?php echo $p['product_id']; ?>" class="prod-card">
                                        <div style="overflow:hidden; border-radius:10px 10px 0 0;">
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" class="prod-img"
                                                alt="<?php echo htmlspecialchars($p['name']); ?>"
                                                onerror="this.src='assets/images/placeholder.jpg'">
                                        </div>
                                        <div class="prod-body">
                                            <div class="prod-cat"><?php echo htmlspecialchars($p['brand_name'] ?? ''); ?></div>
                                            <div class="prod-name"><?php echo htmlspecialchars($p['name']); ?></div>

                                            <?php
                                            /* Show 1-2 relevant specs depending on category */
                                            $specLines = [];
                                            if (!empty($p['cpu']))
                                                $specLines[] = ['fa fa-microchip', 'CPU', $p['cpu']];
                                            if (!empty($p['gpu']))
                                                $specLines[] = ['fa fa-display', 'GPU', $p['gpu']];
                                            if (!empty($p['ram']))
                                                $specLines[] = ['fa fa-memory', 'RAM', $p['ram']];
                                            if (!empty($p['storage']))
                                                $specLines[] = ['fa fa-hard-drive', 'SSD', $p['storage']];
                                            $showSpecs = array_slice($specLines, 0, 2);
                                            ?>
                                            <?php foreach ($showSpecs as [$icon, $lbl, $val]): ?>
                                                <div style="display:flex;gap:6px;align-items:baseline;margin-bottom:3px;">
                                                    <i class="<?php echo $icon; ?>"
                                                        style="color:#555;font-size:0.65rem;width:12px;text-align:center;flex-shrink:0;"></i>
                                                    <span style="font-size:0.72rem;color:#555;"><?php echo $lbl; ?>:</span>
                                                    <span
                                                        style="font-size:0.72rem;color:#888;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($val); ?></span>
                                                </div>
                                            <?php endforeach; ?>

                                            <div class="d-flex align-items-center justify-content-between mt-2">
                                                <span class="prod-price">RM <?php echo number_format($p['price'], 2); ?></span>
                                                <?php if ($p['stock'] > 0): ?>
                                                    <span style="font-size:0.68rem;color:#28a745;">In Stock</span>
                                                <?php else: ?>
                                                    <span style="font-size:0.68rem;color:#dc3545;">Out of Stock</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-prod">View Product</div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

                <div class="text-center mt-4">
                    <a href="product.php" class="btn-cta">
                        <i class="fa fa-th me-2"></i> Browse All Products
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ════ GAME NAVIGATION ════ -->
        <div class="game-nav">
            <?php if ($prevGame): ?>
                <a href="game_check_detail.php?id=<?php echo $prevGame['game_id']; ?>" class="nav-arrow">
                    <i class="fa fa-chevron-left" style="color:#d4af37;flex-shrink:0;"></i>
                    <div style="overflow:hidden;">
                        <div class="arrow-label">Previous Game</div>
                        <div class="arrow-name"><?php echo htmlspecialchars($prevGame['name']); ?></div>
                    </div>
                </a>
            <?php else: ?>
                <div class="nav-arrow disabled"><i class="fa fa-chevron-left"></i>
                    <div>
                        <div class="arrow-label">Previous</div>
                        <div class="arrow-name">—</div>
                    </div>
                </div>
            <?php endif; ?>

            <a href="game_check.php"
                style="font-size:0.78rem;color:#555;text-decoration:none;white-space:nowrap;padding:8px 16px;border-radius:8px;border:1px solid #1e1e1e;"
                onmouseover="this.style.color='#d4af37'" onmouseout="this.style.color='#555'">
                <i class="fa fa-grid-2 me-1"></i> All Games
            </a>

            <?php if ($nextGame): ?>
                <a href="game_check_detail.php?id=<?php echo $nextGame['game_id']; ?>" class="nav-arrow"
                    style="flex-direction:row-reverse;text-align:right;">
                    <i class="fa fa-chevron-right" style="color:#d4af37;flex-shrink:0;"></i>
                    <div style="overflow:hidden;">
                        <div class="arrow-label">Next Game</div>
                        <div class="arrow-name"><?php echo htmlspecialchars($nextGame['name']); ?></div>
                    </div>
                </a>
            <?php else: ?>
                <div class="nav-arrow disabled" style="flex-direction:row-reverse;"><i class="fa fa-chevron-right"></i>
                    <div>
                        <div class="arrow-label">Next</div>
                        <div class="arrow-name">—</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- container -->

    <?php include('includes/footer.php'); ?>

</body>

</html>