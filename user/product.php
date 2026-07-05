<?php
session_start();
include('includes/config.php');

// GET filters
$category = $_GET['category'] ?? 'all';
$brand = $_GET['brand'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$search = trim($_GET['search'] ?? '');

/* ── CATEGORY LIST ── */
$catStmt = $dbh->prepare("SELECT * FROM categories WHERE status = 1 ORDER BY category_name ASC");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── BRAND LIST ── */
$brandStmt = $dbh->prepare("SELECT * FROM tblbrand WHERE status = 'Active' ORDER BY brand_name ASC");
$brandStmt->execute();
$brands = $brandStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── SELECTED LABELS (for dropdown display) ── */
$selectedCatName = 'All Categories';
$selectedBrandName = 'All Brands';

foreach ($categories as $cat) {
    if ($cat['category_id'] == $category) {
        $selectedCatName = htmlspecialchars($cat['category_name']);
        break;
    }
}
foreach ($brands as $b) {
    if ($b['brand_id'] == $brand) {
        $selectedBrandName = htmlspecialchars($b['brand_name']);
        break;
    }
}

/* ── BASE QUERY ── */
$sql = "SELECT p.*, c.category_name, b.brand_name
           FROM products p
           LEFT JOIN categories c ON p.category_id = c.category_id
           LEFT JOIN tblbrand   b ON p.brand_id    = b.brand_id
           WHERE LOWER(p.status) = 'active'
             AND (b.brand_id IS NULL OR b.status = 'Active')
             AND (c.category_id IS NULL OR LOWER(c.status) = 'active')";
$params = [];

if ($category !== 'all') {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}
if ($brand !== 'all') {
    $sql .= " AND p.brand_id = ?";
    $params[] = $brand;
}
if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR b.brand_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sortMap = [
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'newest' => 'p.created_at DESC',
    'name_az' => 'p.name ASC',
];
$orderBy = $sortMap[$sort] ?? 'p.created_at DESC';
$sql .= " ORDER BY $orderBy";

$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── HELPER: build URL keeping other params ── */
function filterUrl(array $overrides): string
{
    $base = array_merge([
        'category' => $_GET['category'] ?? 'all',
        'brand' => $_GET['brand'] ?? 'all',
        'sort' => $_GET['sort'] ?? 'newest',
        'search' => $_GET['search'] ?? '',
    ], $overrides);
    $qs = http_build_query(array_filter($base, fn($v) => $v !== ''));
    return 'product.php' . ($qs ? "?$qs" : '');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — My PC Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="newstyle.css">

    <style>
        /* ── PRODUCT CARD ── */
        .product-card {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            transition: transform 0.28s ease, border-color 0.28s ease, box-shadow 0.28s ease;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .product-card:hover {
            transform: translateY(-6px);
            border-color: #d4af37;
            box-shadow: 0 14px 32px rgba(212, 175, 55, 0.18);
            color: inherit;
            text-decoration: none;
        }

        .product-img-wrap {
            position: relative;
            width: 100%;
            height: 190px;
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
            margin-bottom: 10px;
            line-height: 1.35;
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
            margin-top: 12px;
        }

        /* ── FILTER BAR ── */
        .filter-bar {
            background: #121212;
            border: 1px solid #2a2a2a;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 28px;
        }

        /* ── CUSTOM DROPDOWN ── */
        .filter-dropdown .dropdown-toggle {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            color: #ccc;
            border-radius: 8px;
            padding: 9px 16px;
            font-size: 0.88rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            min-width: 160px;
            transition: border-color 0.2s;
        }

        .filter-dropdown .dropdown-toggle:hover,
        .filter-dropdown .dropdown-toggle:focus {
            border-color: #d4af37;
            color: #fff;
            box-shadow: none;
        }

        .filter-dropdown .dropdown-toggle.active-filter {
            border-color: #d4af37;
            color: #d4af37;
        }

        .filter-dropdown .dropdown-toggle::after {
            margin-left: auto;
        }

        .filter-dropdown .dropdown-menu {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 6px;
            min-width: 210px;
            max-height: 320px;
            overflow-y: auto;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
        }

        .filter-dropdown .dropdown-menu::-webkit-scrollbar {
            width: 4px;
        }

        .filter-dropdown .dropdown-menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .filter-dropdown .dropdown-menu::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 2px;
        }

        .filter-dropdown .dropdown-item {
            color: #ccc;
            border-radius: 6px;
            padding: 9px 14px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }

        .filter-dropdown .dropdown-item:hover {
            background: #2a2a2a;
            color: #fff;
        }

        .filter-dropdown .dropdown-item.selected {
            background: rgba(212, 175, 55, 0.12);
            color: #d4af37;
            font-weight: 600;
        }

        .filter-dropdown .dropdown-item .item-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #444;
            flex-shrink: 0;
        }

        .filter-dropdown .dropdown-item.selected .item-dot {
            background: #d4af37;
        }

        /* search box in filter bar */
        .filter-search {
            background: #1a1a1a;
            border: 1px solid #2a2a2a;
            color: #fff;
            border-radius: 8px;
            padding: 9px 16px;
            font-size: 0.88rem;
            flex: 1;
            min-width: 160px;
            transition: border-color 0.2s;
        }

        .filter-search:focus {
            outline: none;
            border-color: #d4af37;
            background: #1a1a1a;
            color: #fff;
        }

        .filter-search::placeholder {
            color: #555;
        }

        /* sort pills */
        .sort-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .sort-pill {
            padding: 7px 14px;
            border-radius: 20px;
            border: 1px solid #2a2a2a;
            background: transparent;
            color: #888;
            font-size: 0.78rem;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .sort-pill:hover {
            border-color: #555;
            color: #ccc;
        }

        .sort-pill.active {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
            font-weight: 700;
        }

        /* active filter badges */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .filter-badge {
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.35);
            color: #d4af37;
            border-radius: 20px;
            padding: 4px 12px 4px 10px;
            font-size: 0.78rem;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .filter-badge:hover {
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
        }

        .filter-badge i {
            font-size: 0.65rem;
        }

        /* results count */
        .result-count {
            font-size: 0.82rem;
            color: #555;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* empty state */
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

        /* page hero */
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
    </style>
</head>

<body>

    <?php include('includes/header.php'); ?>

    <!-- PAGE HERO -->
    <section class="page-hero">
        <div class="container">
            <h1>Browse Products</h1>
            <p>Build your ultimate PC setup</p>
            <div class="accent-line mx-auto mt-3"></div>
        </div>
    </section>

    <div class="container pb-5">

        <!-- ════════════════ FILTER BAR ════════════════ -->
        <form method="GET" action="product.php" id="filterForm">
            <div class="filter-bar">

                <div class="d-flex flex-wrap align-items-center gap-3">

                    <!-- 🔍 Search -->
                    <div class="d-flex align-items-center gap-2" style="flex:1; min-width:200px;">
                        <i class="fa fa-search" style="color:#555;"></i>
                        <input type="text" name="search" class="filter-search" placeholder="Search products or brands…"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <!-- 📦 Category Dropdown -->
                    <div class="dropdown filter-dropdown">
                        <button class="btn dropdown-toggle <?php echo $category !== 'all' ? 'active-filter' : ''; ?>"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-layer-group" style="color:#d4af37;font-size:0.85rem;"></i>
                            <?php echo $selectedCatName; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo $category === 'all' ? 'selected' : ''; ?>"
                                    href="<?php echo filterUrl(['category' => 'all']); ?>">
                                    <span class="item-dot"></span> All Categories
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider" style="border-color:#2a2a2a; margin:4px 0;">
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>"
                                        href="<?php echo filterUrl(['category' => $cat['category_id']]); ?>">
                                        <span class="item-dot"></span>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- 🏷️ Brand Dropdown -->
                    <div class="dropdown filter-dropdown">
                        <button class="btn dropdown-toggle <?php echo $brand !== 'all' ? 'active-filter' : ''; ?>"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-tag" style="color:#d4af37;font-size:0.85rem;"></i>
                            <?php echo $selectedBrandName; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo $brand === 'all' ? 'selected' : ''; ?>"
                                    href="<?php echo filterUrl(['brand' => 'all']); ?>">
                                    <span class="item-dot"></span> All Brands
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider" style="border-color:#2a2a2a; margin:4px 0;">
                            </li>
                            <?php foreach ($brands as $b): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $brand == $b['brand_id'] ? 'selected' : ''; ?>"
                                        href="<?php echo filterUrl(['brand' => $b['brand_id']]); ?>">
                                        <span class="item-dot"></span>
                                        <?php echo htmlspecialchars($b['brand_name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div><!-- end row 1 -->

                <!-- ── Sort + results row ── -->
                <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-2">

                    <div class="sort-pills">
                        <?php
                        $sorts = [
                            'newest' => '<i class="fa fa-clock me-1"></i>Newest',
                            'price_low' => '<i class="fa fa-arrow-up me-1"></i>Price',
                            'price_high' => '<i class="fa fa-arrow-down me-1"></i>Price',
                            'name_az' => '<i class="fa fa-font me-1"></i>A–Z',
                        ];
                        foreach ($sorts as $key => $label): ?>
                            <a href="<?php echo filterUrl(['sort' => $key]); ?>"
                                class="sort-pill <?php echo $sort === $key ? 'active' : ''; ?>">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <span class="result-count">
                        <?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?> found
                    </span>

                </div>

                <!-- ── Active filter badges ── -->
                <?php if ($category !== 'all' || $brand !== 'all' || $search !== ''): ?>
                    <div class="active-filters">
                        <?php if ($category !== 'all'): ?>
                            <a href="<?php echo filterUrl(['category' => 'all']); ?>" class="filter-badge">
                                <i class="fa fa-times"></i> <?php echo $selectedCatName; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($brand !== 'all'): ?>
                            <a href="<?php echo filterUrl(['brand' => 'all']); ?>" class="filter-badge">
                                <i class="fa fa-times"></i> <?php echo $selectedBrandName; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($search !== ''): ?>
                            <a href="<?php echo filterUrl(['search' => '']); ?>" class="filter-badge">
                                <i class="fa fa-times"></i> "<?php echo htmlspecialchars($search); ?>"
                            </a>
                        <?php endif; ?>
                        <a href="product.php" class="filter-badge"
                            style="border-color:#555; color:#888; background:transparent;">
                            <i class="fa fa-rotate-left"></i> Clear all
                        </a>
                    </div>
                <?php endif; ?>

            </div><!-- end filter-bar -->
        </form>

        <!-- ════════════════ PRODUCT GRID ════════════════ -->
        <?php if (count($products) > 0): ?>

            <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                <?php foreach ($products as $p): ?>

                    <div class="col">
                        <a href="product_details.php?id=<?php echo $p['product_id']; ?>" class="product-card h-100">

                            <div class="product-img-wrap">
                                <img src="<?php echo htmlspecialchars($p['image']); ?>"
                                    alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy"
                                    onerror="this.src='../image/products/placeholder.jpg'">
                            </div>

                            <div class="product-body">
                                <div class="product-meta">
                                    <?php echo htmlspecialchars($p['category_name'] ?? '—'); ?>
                                    &nbsp;·&nbsp;
                                    <?php echo htmlspecialchars($p['brand_name'] ?? '—'); ?>
                                </div>

                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>

                                <div class="d-flex align-items-center justify-content-between product-footer">
                                    <span class="product-price">
                                        RM <?php echo number_format($p['price'], 2); ?>
                                    </span>

                                    <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                                        <span class="badge" style="background:#2a2a2a; color:#777; font-size:0.7rem;">
                                            Out of Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="badge"
                                            style="background:rgba(40,167,69,0.15); color:#28a745; font-size:0.7rem; border:1px solid rgba(40,167,69,0.25);">
                                            In Stock
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-2">
                                    <span class="btn btn-sm w-100"
                                        style="background:linear-gradient(45deg,#d4af37,#c5a028);color:#000;font-weight:700;font-size:0.8rem;letter-spacing:0.8px;border-radius:6px;">
                                        View Product
                                    </span>
                                </div>
                            </div>

                        </a>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <!-- EMPTY STATE -->
            <div class="empty-state">
                <i class="fa fa-box-open"></i>
                <h5>No products found</h5>
                <p style="font-size:0.85rem;">Try adjusting your filters or search term.</p>
                <a href="product.php" class="btn-cta mt-3" style="padding:10px 28px;font-size:0.85rem;">
                    Clear Filters
                </a>
            </div>

        <?php endif; ?>

    </div><!-- end container -->

    <?php include('includes/footer.php'); ?>

    <script>
        // Auto-submit search on Enter (dropdowns already link directly)
        document.querySelector('.filter-search')?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    </script>

</body>

</html>