<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* ── DELETE BRAND ── */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $check = $dbh->prepare("SELECT COUNT(*) FROM products WHERE brand_id = :id");
    $check->bindParam(':id', $id, PDO::PARAM_INT);
    $check->execute();
    $usedCount = $check->fetchColumn();

    if ($usedCount > 0) {
        /* Redirect back with error flag — Swal will fire on page load */
        header("Location: brands.php?error=in_use&count=$usedCount&" . http_build_query([
            'search' => $_GET['search'] ?? '',
            'sort'   => $_GET['sort']   ?? 'latest',
        ]));
        exit;
    }

    $del = $dbh->prepare("DELETE FROM tblbrand WHERE brand_id = :id");
    $del->bindParam(':id', $id, PDO::PARAM_INT);
    $del->execute();
    header("Location: brands.php?deleted=1");
    exit;
}

/* ── FILTER & SORT INPUTS ── */
$search  = trim($_GET['search'] ?? '');
$sort    = $_GET['sort'] ?? 'latest';

$sortMap = [
    'az'     => 'b.brand_name ASC',
    'za'     => 'b.brand_name DESC',
    'latest' => 'b.brand_id DESC',
    'oldest' => 'b.brand_id ASC',
];
$orderBy = $sortMap[$sort] ?? 'b.brand_id DESC';

/* ── FETCH BRANDS WITH PRODUCT COUNT ── */
$sql    = "SELECT b.*, COUNT(p.product_id) AS product_count
           FROM tblbrand b
           LEFT JOIN products p ON p.brand_id = b.brand_id
           WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql     .= " AND b.brand_name LIKE ?";
    $params[] = "%$search%";
}

$sql .= " GROUP BY b.brand_id ORDER BY $orderBy";

$query  = $dbh->prepare($sql);
$query->execute($params);
$brands = $query->fetchAll(PDO::FETCH_OBJ);
$total  = count($brands);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Brands Management — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
body { display:flex; background:#f5f5f5; }

/* ── SIDEBAR ── */
.sidebar { width:220px; height:100vh; background:#000; padding:20px; position:fixed; left:0; top:0; overflow-y:auto; }
.sidebar h2 { color:#d4af37; margin-bottom:30px; text-align:center; font-size:2rem; }
.sidebar a { display:block; color:#adadad; text-decoration:none; padding:12px; margin:10px 0; border-radius:5px; transition:0.3s; }
.sidebar a:hover { background:#d4af37; color:#000; }
.sidebar a.sidebar-active { background:#d4af37; color:#000; }

/* ── MAIN ── */
.main { margin-left:220px; width:calc(100% - 220px); padding:30px; }

/* ── TOPBAR ── */
.topbar {
    display:flex; justify-content:space-between; align-items:center;
    background:#fff; padding:15px 25px; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px;
}
.topbar h1 { font-size:1.6rem; color:#111; font-weight:600; }
.topbar-right { display:flex; gap:10px; align-items:center; }
.btn-back { color:#d4af37; text-decoration:none; font-size:0.88rem; font-weight:500; }
.btn-back:hover { opacity:0.75; }
.btn-add {
    display:inline-flex; align-items:center; gap:6px;
    background:#000; color:#d4af37; text-decoration:none;
    padding:9px 18px; border-radius:4px; font-weight:500;
    font-size:0.85rem; border:1px solid #d4af37; transition:0.25s;
}
.btn-add:hover { background:#d4af37; color:#000; }

/* ── FILTER BAR ── */
.filter-bar {
    background:#fff; padding:16px 20px; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:18px;
    display:flex; gap:12px; flex-wrap:wrap; align-items:center;
}

/* Search */
.search-wrap { position:relative; flex:1; min-width:200px; }
.search-wrap i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#bbb; font-size:0.82rem; pointer-events:none; }
.search-input {
    width:100%; padding:9px 12px 9px 32px;
    border:1px solid #e0e0e0; border-radius:4px;
    font-size:0.85rem; color:#333; font-family:'Poppins',sans-serif; transition:border-color 0.2s;
}
.search-input:focus { outline:none; border-color:#d4af37; box-shadow:0 0 0 2px rgba(212,175,55,0.1); }
.search-input::placeholder { color:#bbb; }

/* Sort select */
.sort-select {
    padding:9px 12px; border:1px solid #e0e0e0; border-radius:4px;
    font-size:0.85rem; color:#555; background:#fff;
    font-family:'Poppins',sans-serif; cursor:pointer;
    transition:border-color 0.2s; min-width:160px;
}
.sort-select:focus { outline:none; border-color:#d4af37; }

.btn-search {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 18px; border-radius:4px; border:none;
    background:#000; color:#d4af37; font-size:0.85rem;
    font-weight:500; cursor:pointer; font-family:'Poppins',sans-serif; transition:0.2s;
}
.btn-search:hover { background:#d4af37; color:#000; }
.btn-reset {
    padding:9px 14px; border-radius:4px; border:1px solid #e0e0e0;
    background:#fff; color:#888; font-size:0.85rem; text-decoration:none;
    font-family:'Poppins',sans-serif; transition:0.2s; white-space:nowrap;
    display:inline-flex; align-items:center; gap:5px;
}
.btn-reset:hover { border-color:#aaa; color:#555; }

/* ── TABLE BOX ── */
.table-box { background:#fff; padding:24px 28px; box-shadow:0 2px 8px rgba(0,0,0,0.05); border-radius:4px; }

.table-meta {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:16px; flex-wrap:wrap; gap:8px;
}
.result-info { font-size:0.8rem; color:#aaa; }
.result-info strong { color:#333; }

table { width:100%; border-collapse:collapse; }
table th, table td { padding:13px 12px; border-bottom:1px solid #f0f0f0; text-align:left; vertical-align:middle; }
table th { color:#d4af37; font-weight:600; background:#fafafa; text-transform:uppercase; font-size:0.78rem; letter-spacing:0.5px; }
table td { color:#444; font-size:0.88rem; }
table tr:hover td { background:#fcfcfc; }

.brand-name { font-weight:600; color:#222; }
.brand-id   { color:#bbb; font-size:0.78rem; }

/* Product count badge */
.count-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:600;
}
.count-active { background:#fffbeb; color:#b89a2e; border:1px solid rgba(212,175,55,0.3); }
.count-zero   { background:#f5f5f5; color:#bbb; border:1px solid #e0e0e0; }

.date-text { font-size:0.78rem; color:#aaa; }

.action-btn { text-decoration:none; font-weight:500; font-size:0.82rem; display:inline-flex; align-items:center; gap:4px; transition:0.2s; }
.action-btn.edit   { color:#ccac3d; }
.action-btn.delete { color:#ff4d4d; }
.action-btn:hover  { text-decoration:underline; }
.action-btn.delete-disabled { color:#ccc; cursor:not-allowed; pointer-events:none; }
.divider { color:#ddd; margin:0 5px; }

/* Sort indicator arrows */
.sort-pill {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; font-size:0.72rem;
    font-weight:500; border:1px solid #e0e0e0; color:#888;
    text-decoration:none; transition:0.15s;
}
.sort-pill.active { background:#d4af37; border-color:#d4af37; color:#000; font-weight:700; }
.sort-pill:hover  { border-color:#aaa; color:#555; }

/* Status badge */
.status-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 9px; border-radius:20px; font-size:0.72rem; font-weight:600;
}
.status-active   { background:#d4edda; color:#155724; border:1px solid rgba(40,167,69,0.2); }
.status-inactive { background:#f5f5f5; color:#888;    border:1px solid #e0e0e0; }

/* Empty */
.empty-state { text-align:center; padding:50px 20px; color:#bbb; }
.empty-state i { font-size:2.2rem; display:block; margin-bottom:12px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>Admin</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php">📦 Products</a>
    <a href="categories.php">📂 Categories</a>
    <a href="brands.php" class="sidebar-active">🏷️ Brands</a>
    <a href="orders.php">🛒 Orders</a>
    <a href="users.php">👥 Users</a>
    <a href="admin.php">⚙ Admin</a>
</div>

<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <h1><i class="fa fa-tag" style="color:#d4af37;margin-right:8px;font-size:1.3rem;"></i>Brands</h1>
            <div style="font-size:0.72rem;color:#aaa;margin-top:2px;">Manage all product brands</div>
        </div>
        <div class="topbar-right">
            <a href="dashboard.php" class="btn-back"><i class="fa fa-arrow-left" style="margin-right:4px;"></i>Back</a>
            <a href="add_brands.php" class="btn-add"><i class="fa fa-plus"></i> Add Brand</a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" action="brands.php" id="filterForm">
    <div class="filter-bar">
        <div class="search-wrap">
            <i class="fa fa-search"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Search brand name…"
                   value="<?php echo htmlspecialchars($search); ?>"
                   autocomplete="off">
        </div>

        <select name="sort" class="sort-select" onchange="this.form.submit()">
            <option value="latest" <?php echo $sort==='latest'?'selected':''; ?>>Latest Added</option>
            <option value="oldest" <?php echo $sort==='oldest'?'selected':''; ?>>Oldest Added</option>
            <option value="az"     <?php echo $sort==='az'    ?'selected':''; ?>>Name A → Z</option>
            <option value="za"     <?php echo $sort==='za'    ?'selected':''; ?>>Name Z → A</option>
        </select>

        <button type="submit" class="btn-search">
            <i class="fa fa-search"></i> Search
        </button>
        <a href="brands.php" class="btn-reset">
            <i class="fa fa-rotate-left"></i> Reset
        </a>
    </div>
    </form>

    <!-- TABLE BOX -->
    <div class="table-box">

        <div class="table-meta">
            <div class="result-info">
                Showing <strong><?php echo $total; ?></strong> brand<?php echo $total !== 1 ? 's' : ''; ?>
                <?php if ($search !== ''): ?>
                &nbsp;for <strong style="color:#d4af37;">"<?php echo htmlspecialchars($search); ?>"</strong>
                <?php endif; ?>
            </div>

            <!-- Sort quick pills -->
            <div style="display:flex;gap:6px;">
                <?php
                $pills = ['latest'=>'Latest','oldest'=>'Oldest','az'=>'A→Z','za'=>'Z→A'];
                foreach($pills as $k=>$lbl):
                    $url = '?sort='.$k.($search!==''?'&search='.urlencode($search):'');
                ?>
                <a href="<?php echo $url; ?>" class="sort-pill <?php echo $sort===$k?'active':''; ?>"><?php echo $lbl; ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:8%;">ID</th>
                    <th style="width:32%;">Brand Name</th>
                    <th style="width:13%;">Status</th>
                    <th style="width:13%;">Products</th>
                    <th style="width:13%;">Created</th>
                    <th style="width:16%;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($brands)): ?>
                <?php foreach($brands as $brand): ?>
                <tr>
                    <td class="brand-id">#<?php echo $brand->brand_id; ?></td>

                    <td>
                        <span class="brand-name"><?php echo htmlspecialchars($brand->brand_name); ?></span>
                    </td>

                    <td>
                        <?php if ($brand->status == 'active'): ?>
                        <span class="status-badge status-active"><i class="fa fa-circle" style="font-size:0.45rem;"></i> Active</span>
                        <?php else: ?>
                        <span class="status-badge status-inactive"><i class="fa fa-circle" style="font-size:0.45rem;"></i> Inactive</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($brand->product_count > 0): ?>
                        <span class="count-badge count-active">
                            <i class="fa fa-box" style="font-size:0.65rem;"></i>
                            <?php echo $brand->product_count; ?> product<?php echo $brand->product_count != 1 ? 's' : ''; ?>
                        </span>
                        <?php else: ?>
                        <span class="count-badge count-zero">
                            <i class="fa fa-minus" style="font-size:0.6rem;"></i> None
                        </span>
                        <?php endif; ?>
                    </td>

                    <td><span class="date-text"><?php echo date('d M Y', strtotime($brand->created_at)); ?></span></td>

                    <td>
                        <a href="edit_brands.php?id=<?php echo $brand->brand_id; ?>" class="action-btn edit">
                            <i class="fa fa-pen-to-square"></i> Edit
                        </a>
                        <span class="divider">|</span>

                        <?php if ($brand->product_count > 0): ?>
                        <!-- DISABLED — has products -->
                        <a href="javascript:void(0);"
                           class="action-btn delete"
                           onclick="warnInUse('<?php echo htmlspecialchars(addslashes($brand->brand_name)); ?>', <?php echo $brand->product_count; ?>, 'brand')">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                        <?php else: ?>
                        <!-- SAFE to delete -->
                        <a href="javascript:void(0);"
                           class="action-btn delete"
                           onclick="confirmDelete('brands.php?delete=<?php echo $brand->brand_id; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>', '<?php echo htmlspecialchars(addslashes($brand->brand_name)); ?>')">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa fa-tag"></i>
                            <p>No brands found<?php echo $search !== '' ? ' for "'.htmlspecialchars($search).'"' : ''; ?>.</p>
                            <?php if($search !== ''): ?>
                            <a href="brands.php" style="color:#d4af37;font-size:0.82rem;">Clear search</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div><!-- table-box -->

</div><!-- main -->

<script>
/* ── CANNOT DELETE: brand in use ── */
function warnInUse(name, count, type) {
    Swal.fire({
        icon: 'warning',
        title: 'Cannot Delete',
        html: `<b style="color:#d4af37;">${name}</b> is currently used by
               <b>${count}</b> product${count !== 1 ? 's' : ''}.<br><br>
               <span style="color:#888;font-size:0.88rem;">
               Remove or reassign those products before deleting this ${type}.
               </span>`,
        confirmButtonText: 'Got it',
        confirmButtonColor: '#d4af37',
        background: '#fff',
        customClass: { confirmButton: 'swal-btn' }
    });
}

/* ── CONFIRM DELETE ── */
function confirmDelete(url, name) {
    Swal.fire({
        icon: 'question',
        title: 'Delete Brand?',
        html: `Delete <b style="color:#d4af37;">${name}</b>? This cannot be undone.`,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-trash"></i> Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        background: '#fff',
    }).then(result => {
        if (result.isConfirmed) window.location.href = url;
    });
}

/* ── SUCCESS TOAST ── */
<?php if (isset($_GET['deleted'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Brand Deleted',
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    background: '#fff',
});
<?php endif; ?>

/* ── SEARCH: submit on Enter ── */
document.querySelector('.search-input')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('filterForm').submit(); }
});
</script>

</body>
</html>