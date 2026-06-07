<?php
session_start();
include('includes/config.php');
error_reporting(0);

$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

// ── Fetch Gaming PC products (category_id = 2) ──
//id 2 = gaming full set, id 17 = work station full set 
$stmt = $dbh->prepare("
    SELECT p.*, b.brand_name, c.category_name
    FROM products p
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.category_id = 2 OR p.category_id = 17
    ORDER BY p.price ASC
");
$stmt->execute();
$gamingPCs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── ADD TO CART ──
$addResult = '';
$addMessage = '';

if ($isLoggedIn && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);

    // Fetch product
    $pStmt = $dbh->prepare("SELECT * FROM products WHERE product_id = ? AND stock > 0");
    $pStmt->execute([$product_id]);
    $prod = $pStmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        // Check existing cart
        $check = $dbh->prepare("SELECT cart_id, quantity FROM tblcart WHERE user_id = ? AND product_id = ? AND status = 'active'");
        $check->execute([$user_id, $product_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQty = $existing['quantity'] + 1;
            if ($newQty <= $prod['stock']) {
                $dbh->prepare("UPDATE tblcart SET quantity = ?, subtotal = product_price * ? WHERE cart_id = ?")
                    ->execute([$newQty, $newQty, $existing['cart_id']]);
                $addResult = 'success';
                $addMessage = $prod['name'] . ' quantity updated in cart!';
            } else {
                $addResult = 'error';
                $addMessage = 'Not enough stock!';
            }
        } else {
            $dbh->prepare("INSERT INTO tblcart (user_id, product_id, product_name, product_image, product_price, quantity, subtotal, created_at, updated_at, status) VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW(), 'active')")
                ->execute([$user_id, $product_id, $prod['name'], $prod['image'], $prod['price'], $prod['price']]);
            $addResult = 'success';
            $addMessage = $prod['name'] . ' added to cart!';
        }
    } else {
        $addResult = 'error';
        $addMessage = 'Product not available or out of stock.';
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
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
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

        .info-banner i {
            color: #d4af37;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-banner p {
            margin: 0;
            color: #888;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .info-banner strong {
            color: #d4af37;
        }

        /* ── PRODUCT CARD (same as product.php) ── */
        .product-card {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            transition: transform 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-6px);
            border-color: #d4af37;
            box-shadow: 0 14px 32px rgba(212, 175, 55, 0.18);
        }

        .product-img-wrap {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #1a1a1a;
        }

        .product-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-img-wrap img {
            transform: scale(1.06);
        }

        .product-body {
            padding: 14px 16px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-meta {
            font-size: 0.75rem;
            color: #777;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .product-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #eee;
            margin-bottom: 6px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-desc {
            font-size: 0.78rem;
            color: #555;
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            color: #d4af37;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .product-footer {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ── ADD TO CART BTN ── */
        .btn-add-cart {
            display: block;
            width: 100%;
            padding: 10px;
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 12px;
            text-align: center;
            text-decoration: none;
        }

        .btn-add-cart:hover {
            background: #fff;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        }

        /* ── VIEW DETAIL LINK ── */
        .btn-view {
            display: block;
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid #2a2a2a;
            color: #888;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 6px;
            text-align: center;
            text-decoration: none;
        }

        .btn-view:hover {
            border-color: #d4af37;
            color: #d4af37;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #555;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #2a2a2a;
        }

        .empty-state h5 {
            color: #777;
            margin-bottom: 8px;
        }
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <!-- HERO -->
    <section class="page-hero">
        <div class="container">
            <p class="section-eyebrow">Ready to Ship</p>
            <h1>Full PC Sets</h1>
            <p>Complete Gaming PC setups — just plug in and play</p>
            <div class="accent-line mx-auto mt-3"></div>
        </div>
    </section>

    <div class="container pb-5">

        <!-- Info Banner -->
        <div class="info-banner">
            <i class="fa fa-circle-info"></i>
            <p>
                All sets are <strong>complete Gaming PC systems</strong>. Prices are inclusive of all components listed.
            </p>
        </div>

        <!-- Product Count -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div style="font-size:0.82rem; color:#555; text-transform:uppercase; letter-spacing:0.5px;">
                <?php echo count($gamingPCs); ?> set<?php echo count($gamingPCs) !== 1 ? 's' : ''; ?> available
            </div>
        </div>

        <!-- Product Grid -->
        <?php if (!empty($gamingPCs)): ?>

            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">

                <?php foreach ($gamingPCs as $p): ?>
                    <div class="col">
                        <div class="product-card">

                            <!-- Image -->
                            <a href="product_details.php?id=<?php echo $p['product_id']; ?>">
                                <div class="product-img-wrap">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>"
                                        alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"
                                        onerror="this.src='assets/images/placeholder.jpg'">
                                </div>
                            </a>

                            <!-- Body -->
                            <div class="product-body">

                                <div class="product-meta">
                                    <?php echo htmlspecialchars($p['category_name'] ?? 'Gaming PC'); ?>
                                    <?php if (!empty($p['brand_name'])): ?>
                                        &nbsp;·&nbsp; <?php echo htmlspecialchars($p['brand_name']); ?>
                                    <?php endif; ?>
                                </div>

                                <a href="product_details.php?id=<?php echo $p['product_id']; ?>" style="text-decoration:none;">
                                    <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                </a>

                                <?php if (!empty($p['description'])): ?>
                                    <div class="product-desc"><?php echo htmlspecialchars($p['description']); ?></div>
                                <?php endif; ?>

                                <div class="product-footer">
                                    <span class="product-price">RM <?php echo number_format($p['price'], 2); ?></span>

                                    <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                                        <span class="badge" style="background:#2a2a2a; color:#777; font-size:0.7rem;">Out of
                                            Stock</span>
                                    <?php else: ?>
                                        <span class="badge"
                                            style="background:rgba(40,167,69,0.15); color:#28a745; font-size:0.7rem; border:1px solid rgba(40,167,69,0.25);">In
                                            Stock</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Buttons -->
                                <?php if ($isLoggedIn): ?>
                                    <?php if (isset($p['stock']) && $p['stock'] > 0): ?>
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn-add-cart">
                                                <i class="fa fa-cart-plus me-1"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-add-cart" disabled
                                            style="background:#2a2a2a; color:#555; cursor:not-allowed;">
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn-add-cart">
                                        <i class="fa fa-lock me-1"></i> Login to Add
                                    </a>
                                <?php endif; ?>

                                <a href="product_details.php?id=<?php echo $p['product_id']; ?>" class="btn-view">
                                    View Details
                                </a>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <i class="fa fa-box-open"></i>
                <h5>No Gaming PCs available</h5>
                <p style="font-size:0.85rem;">Check back soon for new arrivals.</p>
                <a href="product.php" class="btn-cta mt-3" style="padding:10px 28px; font-size:0.85rem;">
                    Browse All Products
                </a>
            </div>

        <?php endif; ?>

        <!-- Bottom CTA -->
        <div class="text-center mt-5 pt-3">
            <p class="text-soft mb-3">Prefer to pick your own components?</p>
            <a href="pcbuild.php" class="btn-cta me-2">PC Builder</a>
            <a href="product.php" class="btn-cta">Browse All Products</a>
        </div>

    </div>

    <?php include('includes/footer.php'); ?>

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
                text: '<?php echo addslashes($addMessage); ?>',
                background: '#1a1a1a',
                color: '#fff',
                confirmButtonColor: '#d4af37'
            });
        </script>
    <?php endif; ?>

</body>

</html>