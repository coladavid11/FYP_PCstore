<?php
session_start();
include('includes/config.php');

/* ── FETCH ALL ACTIVE GAMES FROM DB ── */
$stmt = $dbh->prepare("SELECT * FROM tblgamecheck WHERE status = 1 ORDER BY game_id ASC");
$stmt->execute();
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Check — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        /* ── HERO ── */
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

        .page-hero .eyebrow {
            font-size: 0.75rem;
            color: #d4af37;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .page-hero p {
            color: #666;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 0.82rem;
        }

        /* ── INFO BANNER ── */
        .info-banner {
            background: #141414;
            border: 1px solid #2a2a2a;
            border-left: 3px solid #d4af37;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 36px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .info-banner i {
            color: #d4af37;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .info-banner p {
            margin: 0;
            color: #888;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .info-banner strong {
            color: #d4af37;
        }

        /* ── GAME CARD ── */
        .game-card {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 14px;
            overflow: hidden;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }

        .game-card:hover {
            transform: translateY(-8px);
            border-color: #d4af37;
            box-shadow: 0 20px 50px rgba(212, 175, 55, 0.15);
            color: inherit;
            text-decoration: none;
        }

        /* ── BANNER IMAGE ── */
        .game-banner {
            position: relative;
            width: 100%;
            height: 185px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .game-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease, filter 0.3s;
            filter: brightness(0.82);
        }

        .game-card:hover .game-banner img {
            transform: scale(1.06);
            filter: brightness(1);
        }

        .game-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(18, 18, 18, 0.95) 0%, transparent 55%);
        }

        .game-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.68rem;
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
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
            line-height: 1.3;
        }

        /* ── SPEC BODY ── */
        .spec-body {
            padding: 16px;
            flex: 1;
        }

        .spec-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #1e1e1e;
        }

        .spec-row:last-child {
            border-bottom: none;
        }

        .spec-icon {
            width: 30px;
            height: 30px;
            background: #1a1a1a;
            border: 1px solid #222;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d4af37;
            font-size: 0.72rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .spec-label {
            font-size: 0.67rem;
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

        /* ── VIEW BUTTON ── */
        .btn-view {
            display: block;
            margin: 0 16px 16px;
            padding: 9px;
            border-radius: 8px;
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: #fff;
            color: #000;
        }

        /* ── GENRE TAG ── */
        .genre-tag {
            font-size: 0.7rem;
            color: #555;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <?php include('includes/header.php'); ?>

    <!-- HERO -->
    <section class="page-hero">
        <div class="container">
            <div class="eyebrow">Performance Guide</div>
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
                Specs shown are <strong>minimum recommended requirements</strong> for smooth gameplay at 1080p medium
                settings.
                Click any game to see full specs, recommended requirements, and matching products from our store.
            </p>
        </div>

        <!-- Game Cards Grid -->
        <?php if (empty($games)): ?>
            <div class="text-center py-5" style="color:#555;">
                <i class="fa fa-gamepad fa-3x mb-3" style="color:#2a2a2a;"></i>
                <h5>No games found.</h5>
                <p>Check back soon or add games via the admin panel.</p>
            </div>
        <?php else: ?>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">

                <?php foreach ($games as $game): ?>
                    <div class="col">

                        <a href="game_check_detail.php?id=<?php echo $game['game_id']; ?>" class="game-card">

                            <!-- Banner -->
                            <div class="game-banner">
                                <img src="<?php echo htmlspecialchars($game['image']); ?>"
                                    alt="<?php echo htmlspecialchars($game['name']); ?>"
                                    onerror="this.src='assets/images/placeholder.jpg'">
                                <div class="game-banner-overlay"></div>
                                <span class="game-badge"
                                    style="background:<?php echo htmlspecialchars($game['badge_color']); ?>;">
                                    <?php echo htmlspecialchars($game['badge']); ?>
                                </span>
                                <div class="game-title-overlay">
                                    <?php echo htmlspecialchars($game['name']); ?>
                                </div>
                            </div>

                            <!-- Minimum Specs (quick view) -->
                            <div class="spec-body">

                                <p class="genre-tag mb-3">
                                    <i
                                        class="fa fa-tag me-1"></i><?php echo htmlspecialchars($game['genre'] ?? $game['badge']); ?>
                                </p>

                                <div class="spec-row">
                                    <div class="spec-icon"><i class="fa fa-microchip"></i></div>
                                    <div>
                                        <div class="spec-label">CPU (Min)</div>
                                        <div class="spec-value"><?php echo htmlspecialchars($game['min_cpu']); ?></div>
                                    </div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-icon"><i class="fa fa-memory"></i></div>
                                    <div>
                                        <div class="spec-label">RAM (Min)</div>
                                        <div class="spec-value"><?php echo htmlspecialchars($game['min_ram']); ?></div>
                                    </div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-icon"><i class="fa fa-display"></i></div>
                                    <div>
                                        <div class="spec-label">GPU (Min)</div>
                                        <div class="spec-value"><?php echo htmlspecialchars($game['min_gpu']); ?></div>
                                    </div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-icon"><i class="fa fa-hard-drive"></i></div>
                                    <div>
                                        <div class="spec-label">Storage (Min)</div>
                                        <div class="spec-value"><?php echo htmlspecialchars($game['min_storage']); ?></div>
                                    </div>
                                </div>

                            </div><!-- spec-body -->

                            <span class="btn-view">
                                <i class="fa fa-arrow-right me-1"></i> View Full Specs & Products
                            </span>

                        </a>
                    </div>
                <?php endforeach; ?>

            </div><!-- row -->
        <?php endif; ?>

        <!-- Bottom CTA -->
        <div class="text-center mt-5 pt-3">
            <p style="color:#555; font-size:0.88rem; margin-bottom:16px;">
                Not sure which build suits your games?
            </p>
            <a href="product.php" class="btn-cta me-2">Browse All Products</a>
        </div>

    </div>

    <?php include('includes/footer.php'); ?>

</body>

</html>