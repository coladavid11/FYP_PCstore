<?php
session_start();
include('includes/config.php');

$product_id = intval($_GET['id'] ?? 0);

/* ── FETCH PRODUCT ── */
$stmt = $dbh->prepare("
    SELECT p.*, c.category_name, b.brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN tblbrand   b ON p.brand_id    = b.brand_id
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}

$stock = intval($product['stock']);

/* ── WISHLIST CHECK ── */
$isWishlisted = false;
if (isset($_SESSION['user_id'])) {
    $wStmt = $dbh->prepare("SELECT 1 FROM tblwishlist WHERE user_id=? AND product_id=? AND status='active' LIMIT 1");
    $wStmt->execute([$_SESSION['user_id'], $product_id]);
    $isWishlisted = (bool) $wStmt->fetch();
}

/* ── RELATED PRODUCTS ── */
$stmt2 = $dbh->prepare("
    SELECT p.*, b.brand_name
    FROM products p
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    WHERE p.category_id = ? AND p.product_id != ?
    LIMIT 4
");
$stmt2->execute([$product['category_id'], $product_id]);
$related = $stmt2->fetchAll(PDO::FETCH_ASSOC);

/* ── FETCH REVIEWS ── */
$rStmt = $dbh->prepare("
    SELECT r.rating, r.review_text, r.created_at, u.fullname
    FROM tblreviews r
    LEFT JOIN tbluser u ON r.user_id = u.user_id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$rStmt->execute([$product_id]);
$reviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        body {
            background: #0f0f0f;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        /* ── IMAGE ── */
        .main-img-wrap {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .main-img-wrap:hover img {
            transform: scale(1.04);
        }

        /* ── INFO PANEL ── */
        .info-panel {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            padding: 28px;
        }

        .product-brand {
            color: #d4af37;
            font-size: 0.75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .product-id {
            color: #555;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 700;
            color: #d4af37;
            letter-spacing: 0.5px;
        }

        /* ── STOCK BAR ── */
        .stock-wrap {
            margin: 16px 0;
        }

        .stock-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            margin-bottom: 6px;
        }

        .stock-label span:first-child {
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .stock-count {
            font-weight: 600;
        }

        .stock-count.ok {
            color: #28a745;
        }

        .stock-count.low {
            color: #ffc107;
        }

        .stock-count.empty {
            color: #dc3545;
        }

        .progress {
            height: 5px;
            background: #222;
            border-radius: 3px;
        }

        .bar-ok {
            background: linear-gradient(90deg, #28a745, #5cb85c);
        }

        .bar-low {
            background: linear-gradient(90deg, #ffc107, #ffda6a);
        }

        .bar-empty {
            background: #dc3545;
        }

        /* ── QTY CONTROL ── */
        .qty-wrap {
            display: inline-flex;
            align-items: center;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            overflow: hidden;
        }

        .qty-wrap button {
            background: #1a1a1a;
            border: none;
            color: #fff;
            width: 40px;
            height: 40px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-wrap button:hover:not(:disabled) {
            background: #2a2a2a;
        }

        .qty-wrap button:disabled {
            color: #444;
            cursor: not-allowed;
        }

        #qty {
            width: 60px;
            height: 40px;
            background: #121212;
            border: none;
            color: #fff;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 600;
            -moz-appearance: textfield;
        }

        #qty::-webkit-inner-spin-button,
        #qty::-webkit-outer-spin-button {
            -webkit-appearance: none;
        }

        .subtotal-line {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }

        .subtotal-line span:first-child {
            color: #888;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .subtotal-line .sub-price {
            color: #d4af37;
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* ── STOCK WARNING INLINE ── */
        #stockWarning {
            font-size: 0.8rem;
            color: #ffc107;
            margin-top: 6px;
            display: none;
        }

        /* ── BUTTONS ── */
        .btn-add-cart {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            border: none;
            padding: 13px 20px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 8px;
            transition: all 0.25s;
            width: 100%;
        }

        .btn-add-cart:hover:not(:disabled) {
            background: #fff;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.25);
        }

        .btn-add-cart:disabled {
            background: #2a2a2a;
            color: #555;
            cursor: not-allowed;
        }

        .btn-wish {
            background: transparent;
            border: 1px solid;
            padding: 11px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: all 0.25s;
            width: 100%;
        }

        .btn-wish.not-wishlisted {
            border-color: #555;
            color: #aaa;
        }

        .btn-wish.not-wishlisted:hover {
            border-color: #dc3545;
            color: #dc3545;
            background: rgba(220, 53, 69, 0.05);
        }

        .btn-wish.wishlisted {
            border-color: #dc3545;
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .btn-wish.wishlisted:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        /* ── TABS ── */
        .tab-panel {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 32px;
        }

        .nav-tabs {
            border-bottom: 1px solid #2a2a2a;
            padding: 0 20px;
            background: #161616;
        }

        .nav-tabs .nav-link {
            color: #666;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 14px 20px;
            font-size: 0.85rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            transition: color 0.2s;
            border-radius: 0;
        }

        .nav-tabs .nav-link:hover {
            color: #aaa;
        }

        .nav-tabs .nav-link.active {
            color: #d4af37;
            border-bottom-color: #d4af37;
            background: transparent;
        }

        .tab-content {
            padding: 24px;
        }

        .tab-pane {
            color: #ccc;
            font-size: 0.92rem;
            line-height: 1.7;
        }

        /* ── SPEC TABLE ── */
        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }

        .spec-table tr {
            border-bottom: 1px solid #1e1e1e;
        }

        .spec-table tr:last-child {
            border-bottom: none;
        }

        .spec-table th {
            width: 35%;
            padding: 12px 16px;
            color: #d4af37;
            font-size: 0.82rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            background: #161616;
            font-weight: 600;
        }

        .spec-table td {
            padding: 12px 16px;
            color: #ddd;
            font-size: 0.9rem;
        }

        /* ── RELATED PRODUCTS ── */
        .related-card {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            color: #fff;
        }

        .related-card:hover {
            border-color: #d4af37;
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(212, 175, 55, 0.15);
            color: #fff;
            text-decoration: none;
        }

        .related-img {
            width: 100%;
            height: 130px;
            object-fit: cover;
        }

        .related-body {
            padding: 12px 14px;
        }

        .related-name {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .related-brand {
            font-size: 0.73rem;
            color: #666;
            margin-bottom: 8px;
        }

        .related-price {
            color: #d4af37;
            font-weight: 700;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <div class="container py-5">

        <!-- ════════ PRODUCT ROW ════════ -->
        <div class="row g-4 align-items-start">

            <!-- LEFT — IMAGE -->
            <div class="col-lg-5">
                <div class="main-img-wrap">
                    <img id="mainImage" src="<?php echo htmlspecialchars($product['image']); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        onerror="this.src='assets/images/placeholder.jpg'">
                </div>
            </div>

            <!-- RIGHT — INFO -->
            <div class="col-lg-7">
                <div class="info-panel">

                    <div class="product-brand"><?php echo htmlspecialchars($product['brand_name'] ?? ''); ?></div>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-id mb-3"># <?php echo $product['product_id']; ?> &nbsp;·&nbsp;
                        <?php echo htmlspecialchars($product['category_name'] ?? ''); ?></div>

                    <div class="price-tag">RM <span
                            id="basePrice"><?php echo number_format($product['price'], 2); ?></span></div>

                    <!-- STOCK BAR -->
                    <div class="stock-wrap">
                        <?php
                        $pct = min(100, ($stock / 50) * 100);
                        if ($stock <= 0) {
                            $cls = 'empty';
                            $barCls = 'bar-empty';
                            $label = 'Out of Stock';
                        } elseif ($stock < 5) {
                            $cls = 'low';
                            $barCls = 'bar-low';
                            $label = "Only {$stock} left!";
                        } else {
                            $cls = 'ok';
                            $barCls = 'bar-ok';
                            $label = "{$stock} in stock";
                        }
                        ?>
                        <div class="stock-label">
                            <span>Availability</span>
                            <span class="stock-count <?php echo $cls; ?>"><?php echo $label; ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?php echo $barCls; ?>" style="width:<?php echo max(3, $pct); ?>%">
                            </div>
                        </div>
                    </div>

                    <!-- DESCRIPTION (short preview) -->
                    <p class="text-soft" id="descText" style="font-size:0.9rem; line-height:1.65; margin-bottom:4px;">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                    <a href="javascript:void(0);" id="toggleDesc"
                        style="color:#d4af37; font-size:0.85rem; font-weight:600; display:none;">
                        Read more
                    </a>

                    <hr style="border-color:#1e1e1e; margin:20px 0;">

                    <!-- QTY CONTROL -->
                    <?php if ($stock > 0): ?>
                        <label
                            style="font-size:0.78rem;color:#888;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:10px;">
                            Quantity
                        </label>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="qty-wrap">
                                <button type="button" id="btnMinus" onclick="qtyMinus()" disabled>
                                    <i class="fa fa-minus" style="font-size:0.75rem;"></i>
                                </button>
                                <input type="number" id="qty" value="1" min="1" max="<?php echo $stock; ?>" readonly>
                                <button type="button" id="btnPlus" onclick="qtyPlus()" <?php echo $stock <= 1 ? 'disabled' : ''; ?>>
                                    <i class="fa fa-plus" style="font-size:0.75rem;"></i>
                                </button>
                            </div>
                            <span style="color:#555; font-size:0.8rem;">Max: <?php echo $stock; ?></span>
                        </div>
                        <div id="stockWarning">
                            <i class="fa fa-triangle-exclamation"></i>
                            Maximum available stock reached.
                        </div>
                    <?php else: ?>
                        <div class="alert"
                            style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.3);color:#dc3545;border-radius:8px;padding:12px 16px;font-size:0.88rem;">
                            <i class="fa fa-ban me-2"></i> This product is currently out of stock.
                        </div>
                    <?php endif; ?>

                    <!-- SUBTOTAL -->
                    <div class="subtotal-line">
                        <span>Subtotal</span>
                        <span class="sub-price">RM <span
                                id="subtotal"><?php echo number_format($product['price'], 2); ?></span></span>
                    </div>

                    <!-- BUTTONS -->
                    <div class="d-flex flex-column gap-2 mt-3">
                        <button class="btn-add-cart" id="btnAddCart"
                            onclick="addToCart(<?php echo $product['product_id']; ?>)" <?php echo $stock <= 0 ? 'disabled' : ''; ?>><i
                            class="fa fa-cart-plus me-2"></i>
                            <?php echo $stock <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>

                        <button id="wishBtn"
                            class="btn-wish <?php echo $isWishlisted ? 'wishlisted' : 'not-wishlisted'; ?>"
                            onclick="toggleWishlist(<?php echo $product['product_id']; ?>)">
                            <i id="wishIcon"
                                class="<?php echo $isWishlisted ? 'fa-solid' : 'fa-regular'; ?> fa-heart me-2"></i>
                            <span
                                id="wishText"><?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?></span>
                        </button>
                    </div>

                </div><!-- end info-panel -->
            </div>

        </div><!-- end row -->

    <!-- ════════ TABS ════════ -->
    <div class="tab-panel">
        <ul class="nav nav-tabs" id="productTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#desc">Description</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#spec">Specifications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#review">Reviews</a>
            </li>
        </ul>

            <div class="tab-content">

                <!-- DESCRIPTION TAB -->
                <div class="tab-pane fade show active" id="desc">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

            <!-- SPEC TAB — ✅ 只改了这里，加了 7 个新栏位 -->
            <div class="tab-pane fade" id="spec">
                <?php
                $specs = [
                    'CPU'              => $product['cpu']              ?? '',
                    'RAM'              => $product['ram']              ?? '',
                    'Storage'          => $product['storage']          ?? '',
                    'GPU'              => $product['gpu']              ?? '',
                    'Motherboard'      => $product['motherboard']      ?? '',
                    'Power Supply'     => $product['power_supply']     ?? '',
                    'Cooler'           => $product['cooler']           ?? '',
                    'Case'             => $product['pc_case']          ?? '',
                    'Monitor'          => $product['monitor']          ?? '',
                    'Keyboard'         => $product['keyboard']         ?? '',
                    'Mouse'            => $product['mouse']            ?? '',
                    'Display'          => $product['display_screen']   ?? '',
                    'Operating System' => $product['operating_system'] ?? '',
                ];
                $hasSpec = array_filter($specs);
                ?>
                <?php if ($hasSpec): ?>
                <table class="spec-table">
                    <tbody>
                    <?php foreach($specs as $key => $val): ?>
                        <?php if(!empty($val)): ?>
                        <tr>
                            <th><?php echo $key; ?></th>
                            <td><?php echo htmlspecialchars($val); ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color:#555;">No specifications listed for this product.</p>
                <?php endif; ?>
            </div>

            <!-- REVIEWS TAB -->
            <div class="tab-pane fade" id="review">
                <p style="color:#555;">Reviews coming soon.</p>
            </div>

            </div>
        </div>

        <!-- ════════ RELATED PRODUCTS ════════ -->
        <?php if (count($related) > 0): ?>
            <div class="mt-5">
                <h4 class="mb-1" style="font-family:'Playfair Display',serif;">Related Products</h4>
                <div class="accent-line mb-4" style="margin:10px 0 24px;"></div>

                <div class="row row-cols-2 row-cols-sm-2 row-cols-md-4 g-3">
                    <?php foreach ($related as $r): ?>
                        <div class="col">
                            <a href="product_details.php?id=<?php echo $r['product_id']; ?>" class="related-card">
                                <img src="<?php echo htmlspecialchars($r['image']); ?>" class="related-img"
                                    alt="<?php echo htmlspecialchars($r['name']); ?>"
                                    onerror="this.src='assets/images/placeholder.jpg'">
                                <div class="related-body">
                                    <div class="related-brand"><?php echo htmlspecialchars($r['brand_name'] ?? ''); ?></div>
                                    <div class="related-name"><?php echo htmlspecialchars($r['name']); ?></div>
                                    <div class="related-price">RM <?php echo number_format($r['price'], 2); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- end container -->

    <?php include('includes/footer.php'); ?>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /* ─────────────────────────────────────────
           CONSTANTS from PHP
        ───────────────────────────────────────── */
        const STOCK = <?php echo $stock; ?>;
        const PRICE = <?php echo floatval($product['price']); ?>;

        /* ─────────────────────────────────────────
           QTY CONTROL (frontend stock cap)
        ───────────────────────────────────────── */
        function qtyPlus() {
            const input = document.getElementById('qty');
            const warning = document.getElementById('stockWarning');
            let val = parseInt(input.value) || 1;

            if (val < STOCK) {
                val++;
                input.value = val;
            }

            document.getElementById('btnMinus').disabled = (val <= 1);
            document.getElementById('btnPlus').disabled = (val >= STOCK);

            if (val >= STOCK) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }

            updateSubtotal();
        }

        function qtyMinus() {
            const input = document.getElementById('qty');
            const warning = document.getElementById('stockWarning');
            let val = parseInt(input.value) || 1;

            if (val > 1) {
                val--;
                input.value = val;
            }

            document.getElementById('btnMinus').disabled = (val <= 1);
            document.getElementById('btnPlus').disabled = (val >= STOCK);
            warning.style.display = 'none';

            updateSubtotal();
        }

        function updateSubtotal() {
            const qty = parseInt(document.getElementById('qty').value) || 1;
            document.getElementById('subtotal').textContent = (PRICE * qty).toFixed(2);
        }

        /* ─────────────────────────────────────────
           ADD TO CART
        ───────────────────────────────────────── */
        function addToCart(pid) {
            const qty = parseInt(document.getElementById('qty').value) || 1;
            const btn = document.getElementById('btnAddCart');

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Adding…';

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${pid}&qty=${qty}`
            })
                .then(res => res.json())
                .then(data => {

                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-cart-plus me-2"></i> Add to Cart';

                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Added to Cart!',
                            text: data.message,
                            background: '#1a1a1a',
                            color: '#fff',
                            confirmButtonColor: '#d4af37',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });

                    } else if (data.status === 'login_required') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Login Required',
                            text: data.message,
                            background: '#1a1a1a',
                            color: '#fff',
                            confirmButtonColor: '#d4af37'
                        }).then(() => {
                            window.location.href = 'login.php';
                        });

                    } else if (data.status === 'out_of_stock') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Out of Stock',
                            text: data.message,
                            background: '#1a1a1a',
                            color: '#fff',
                            confirmButtonColor: '#d4af37'
                        });

                    } else {
                        let bodyText = data.message;
                        if (data.available_stock !== undefined) {
                            bodyText = data.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Enough Stock',
                            text: bodyText,
                            background: '#1a1a1a',
                            color: '#fff',
                            confirmButtonColor: '#d4af37'
                        });
                    }

                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-cart-plus me-2"></i> Add to Cart';
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Could not reach the server. Please try again.',
                        background: '#1a1a1a',
                        color: '#fff',
                        confirmButtonColor: '#d4af37'
                    });
                });
        }

        /* ─────────────────────────────────────────
           WISHLIST TOGGLE
        ───────────────────────────────────────── */
        function toggleWishlist(pid) {
            fetch('wishlist_toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + pid
            })
                .then(res => res.json())
                .then(data => {
                    const btn = document.getElementById('wishBtn');
                    const icon = document.getElementById('wishIcon');
                    const text = document.getElementById('wishText');

                    if (data.status === 'added') {
                        btn.className = 'btn-wish wishlisted';
                        icon.className = 'fa-solid fa-heart me-2';
                        text.textContent = 'Remove from Wishlist';
                        Swal.fire({
                            icon: 'success', title: 'Wishlist Updated',
                            text: 'Added to your wishlist.',
                            background: '#1a1a1a', color: '#fff',
                            confirmButtonColor: '#d4af37',
                            timer: 1800, showConfirmButton: false, timerProgressBar: true
                        });

                    } else if (data.status === 'removed') {
                        btn.className = 'btn-wish not-wishlisted';
                        icon.className = 'fa-regular fa-heart me-2';
                        text.textContent = 'Add to Wishlist';
                        Swal.fire({
                            icon: 'info', title: 'Removed from Wishlist',
                            background: '#1a1a1a', color: '#fff',
                            confirmButtonColor: '#d4af37',
                            timer: 1800, showConfirmButton: false, timerProgressBar: true
                        });

                    } else if (data.status === 'login_required') {
                        Swal.fire({
                            icon: 'warning', title: 'Login Required',
                            text: data.message,
                            background: '#1a1a1a', color: '#fff',
                            confirmButtonColor: '#d4af37'
                        }).then(() => { window.location.href = 'login.php'; });
                    }
                });
        }

        /* ─────────────────────────────────────────
           DESCRIPTION READ MORE / LESS
        ───────────────────────────────────────── */
        document.addEventListener("DOMContentLoaded", function () {
            const desc = document.getElementById("descText");
            const btn = document.getElementById("toggleDesc");
            const LIMIT = 160;

            if (!desc) return;
            const full = desc.innerText;
            const short = full.substring(0, LIMIT) + "…";

            if (full.length > LIMIT) {
                desc.innerText = short;
                btn.style.display = 'inline';
                let expanded = false;

                btn.addEventListener("click", function () {
                    expanded = !expanded;
                    desc.innerText = expanded ? full : short;
                    btn.textContent = expanded ? 'Show less' : 'Read more';
                });
            }
        });
    </script>

</body>

</html>