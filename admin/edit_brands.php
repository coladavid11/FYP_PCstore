<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

/* ── VALIDATE ID ── */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: brands.php");
    exit;
}

/* ── FETCH BRAND ── */
$fetch = $dbh->prepare("SELECT * FROM tblbrand WHERE brand_id = :id");
$fetch->bindParam(':id', $id, PDO::PARAM_INT);
$fetch->execute();
$brand = $fetch->fetch(PDO::FETCH_OBJ);

if (!$brand) {
    header("Location: brands.php?error=not_found");
    exit;
}

/* ── FETCH PRODUCT COUNT ── */
$countQ = $dbh->prepare("SELECT COUNT(*) FROM products WHERE brand_id = :id");
$countQ->bindParam(':id', $id, PDO::PARAM_INT);
$countQ->execute();
$productCount = $countQ->fetchColumn();

$errors  = [];
$success = false;
$dupName = false;

/* ── HANDLE POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName   = trim($_POST['brand_name'] ?? '');
    $newStatus = $_POST['status'] ?? 'active';

    /* Basic validation */
    if ($newName === '') {
        $errors[] = 'Brand name cannot be empty.';
    } elseif (strlen($newName) > 100) {
        $errors[] = 'Brand name must be 100 characters or fewer.';
    }

    if (!in_array($newStatus, ['active', 'inactive'])) {
        $errors[] = 'Invalid status value.';
    }

    /* Duplicate name check (case-insensitive, exclude self) */
    if (empty($errors)) {
        $dup = $dbh->prepare(
            "SELECT brand_id FROM tblbrand
             WHERE LOWER(brand_name) = LOWER(:name) AND brand_id != :id"
        );
        $dup->bindParam(':name', $newName);
        $dup->bindParam(':id',   $id, PDO::PARAM_INT);
        $dup->execute();
        if ($dup->fetch()) {
            $dupName = true;
            $errors[] = "A brand named \"$newName\" already exists.";
        }
    }

    /* Save */
    if (empty($errors)) {
        $upd = $dbh->prepare(
            "UPDATE tblbrand SET brand_name = :name, status = :status WHERE brand_id = :id"
        );
        $upd->bindParam(':name',   $newName);
        $upd->bindParam(':status', $newStatus);
        $upd->bindParam(':id',     $id, PDO::PARAM_INT);
        $upd->execute();
        $success = true;
        $brand->brand_name = $newName;
        $brand->status     = $newStatus;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Brand — Admin</title>
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
    box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:24px;
}
.topbar h1 { font-size:1.6rem; color:#111; font-weight:600; }
.topbar-sub { font-size:0.72rem; color:#aaa; margin-top:2px; }
.topbar-right { display:flex; gap:10px; align-items:center; }
.btn-back {
    display:inline-flex; align-items:center; gap:6px;
    color:#d4af37; text-decoration:none; font-size:0.88rem; font-weight:500;
    padding:8px 14px; border:1px solid #d4af37; border-radius:4px; transition:0.2s;
}
.btn-back:hover { background:#d4af37; color:#000; }

/* ── LAYOUT COLUMNS ── */
.edit-layout { display:flex; gap:20px; align-items:flex-start; }
.col-main  { flex:1; min-width:0; }
.col-aside { width:260px; flex-shrink:0; }

/* ── FORM CARD ── */
.form-card {
    background:#fff; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden;
}
.form-card-header {
    padding:18px 28px; border-bottom:1px solid #f0f0f0;
    display:flex; align-items:center; gap:10px;
}
.form-card-header i { color:#d4af37; font-size:1rem; }
.form-card-header h2 { font-size:1rem; font-weight:600; color:#111; }
.form-card-header .badge-id {
    margin-left:auto; font-size:0.72rem; color:#aaa;
    background:#f5f5f5; border:1px solid #e8e8e8;
    padding:3px 10px; border-radius:20px;
}
.form-body { padding:28px; }

/* ── FORM ELEMENTS ── */
.form-group { margin-bottom:22px; }
.form-label {
    display:block; font-size:0.82rem; font-weight:600;
    color:#444; margin-bottom:7px; letter-spacing:0.3px;
}
.form-label span.req { color:#e74c3c; margin-left:2px; }
.form-control {
    width:100%; padding:10px 14px;
    border:1px solid #e0e0e0; border-radius:4px;
    font-size:0.88rem; color:#333; font-family:'Poppins',sans-serif;
    transition:border-color 0.2s, box-shadow 0.2s;
    background:#fff;
}
.form-control:focus {
    outline:none; border-color:#d4af37;
    box-shadow:0 0 0 3px rgba(212,175,55,0.12);
}
.form-control.is-error { border-color:#e74c3c; box-shadow:0 0 0 3px rgba(231,76,60,0.1); }
.field-error { font-size:0.77rem; color:#e74c3c; margin-top:5px; display:flex; align-items:center; gap:4px; }

/* Status toggle */
.status-options { display:flex; gap:12px; }
.status-option { flex:1; }
.status-option input[type="radio"] { display:none; }
.status-option label {
    display:flex; align-items:center; justify-content:center; gap:8px;
    padding:10px 14px; border:2px solid #e0e0e0; border-radius:4px;
    cursor:pointer; font-size:0.85rem; font-weight:500; color:#888;
    transition:all 0.2s; user-select:none;
}
.status-option input[type="radio"]:checked + label.label-active {
    border-color:#28a745; background:#d4edda; color:#155724;
}
.status-option input[type="radio"]:checked + label.label-inactive {
    border-color:#aaa; background:#f5f5f5; color:#555;
}
.status-option label:hover { border-color:#bbb; }

/* ── HINT ── */
.form-hint { font-size:0.76rem; color:#aaa; margin-top:5px; }

/* Inactive warning */
.inactive-warning {
    display:none; margin-top:14px; padding:10px 14px;
    background:#fff8e1; border:1px solid #ffe082; border-radius:4px;
    font-size:0.78rem; color:#7a6000; line-height:1.5;
}
.inactive-warning i { margin-right:4px; }

/* ── FOOTER ── */
.form-footer {
    padding:18px 28px; border-top:1px solid #f0f0f0;
    display:flex; gap:10px; align-items:center; background:#fafafa;
}
.btn-save {
    display:inline-flex; align-items:center; gap:7px;
    background:#000; color:#d4af37; border:1px solid #d4af37;
    padding:10px 24px; border-radius:4px; font-size:0.88rem;
    font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif;
    transition:0.2s;
}
.btn-save:hover { background:#d4af37; color:#000; }
.btn-cancel {
    display:inline-flex; align-items:center; gap:6px;
    background:#fff; color:#888; border:1px solid #e0e0e0;
    padding:10px 20px; border-radius:4px; font-size:0.88rem;
    font-weight:500; cursor:pointer; font-family:'Poppins',sans-serif;
    text-decoration:none; transition:0.2s;
}
.btn-cancel:hover { border-color:#aaa; color:#555; }

/* ── SIDE CARDS ── */
.side-card {
    background:#fff; border-radius:4px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    overflow:hidden; margin-bottom:16px;
}
.side-card-header {
    padding:12px 18px; border-bottom:1px solid #f0f0f0;
    display:flex; align-items:center; gap:8px;
}
.side-card-header i { color:#d4af37; font-size:0.85rem; }
.side-card-header h3 { font-size:0.8rem; font-weight:600; color:#555; text-transform:uppercase; letter-spacing:0.4px; }
.side-card-body { padding:16px 18px; }

/* Meta rows */
.meta-row { display:flex; gap:8px; align-items:flex-start; font-size:0.82rem; color:#888; margin-bottom:10px; }
.meta-row:last-child { margin-bottom:0; }
.meta-row i { color:#d4af37; width:14px; text-align:center; font-size:0.75rem; margin-top:2px; flex-shrink:0; }
.meta-row strong { color:#444; }

/* Count badge (sidebar) */
.count-badge-lg {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 14px; border-radius:20px; font-size:0.8rem; font-weight:600;
    margin-top:4px;
}
.count-active { background:#fffbeb; color:#b89a2e; border:1px solid rgba(212,175,55,0.3); }
.count-zero   { background:#f5f5f5; color:#bbb; border:1px solid #e0e0e0; }

/* Status badge (sidebar preview) */
.status-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 9px; border-radius:20px; font-size:0.72rem; font-weight:600;
}
.status-active   { background:#d4edda; color:#155724; border:1px solid rgba(40,167,69,0.2); }
.status-inactive { background:#f5f5f5; color:#888; border:1px solid #e0e0e0; }
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
    <a href="shipping_rates.php">🚚 Shipping Rates</a>
    <a href="sales_report.php">📊 Sales Report</a>
    <a href="admin.php">⚙ Admin</a>
</div>

<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <h1><i class="fa fa-pen-to-square" style="color:#d4af37;margin-right:8px;font-size:1.2rem;"></i>Edit Brand</h1>
            <div class="topbar-sub">Update brand name and status</div>
        </div>
        <div class="topbar-right">
            <a href="brands.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Brands</a>
        </div>
    </div>

    <div class="edit-layout">

        <!-- MAIN FORM -->
        <div class="col-main">
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa fa-tag"></i>
                <h2>Brand Details</h2>
                <span class="badge-id">ID #<?php echo $brand->brand_id; ?></span>
            </div>

            <form method="POST" action="edit_brands.php?id=<?php echo $id; ?>" id="editForm" novalidate>
            <div class="form-body">

                <!-- Brand Name -->
                <div class="form-group">
                    <label class="form-label" for="brand_name">
                        Brand Name <span class="req">*</span>
                    </label>
                    <input
                        type="text"
                        id="brand_name"
                        name="brand_name"
                        class="form-control <?php echo $dupName || (!empty($errors) && trim($_POST['brand_name'] ?? '') === '') ? 'is-error' : ''; ?>"
                        value="<?php echo htmlspecialchars($brand->brand_name); ?>"
                        maxlength="100"
                        autocomplete="off"
                        placeholder="e.g. ASUS, MSI, Logitech"
                    >
                    <div class="form-hint">Maximum 100 characters. Name must be unique.</div>
                    <?php if ($dupName): ?>
                    <div class="field-error"><i class="fa fa-circle-exclamation"></i> A brand with this name already exists.</div>
                    <?php elseif (!empty($errors) && trim($_POST['brand_name'] ?? '') === ''): ?>
                    <div class="field-error"><i class="fa fa-circle-exclamation"></i> Brand name cannot be empty.</div>
                    <?php endif; ?>
                </div>

                <!-- Status -->
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <div class="status-options">
                        <div class="status-option">
                            <input type="radio" id="status_active" name="status" value="active"
                                <?php echo $brand->status === 'active' ? 'checked' : ''; ?>>
                            <label for="status_active" class="label-active">
                                <i class="fa fa-circle-check"></i> Active
                            </label>
                        </div>
                        <div class="status-option">
                            <input type="radio" id="status_inactive" name="status" value="inactive"
                                <?php echo $brand->status === 'inactive' ? 'checked' : ''; ?>>
                            <label for="status_inactive" class="label-inactive">
                                <i class="fa fa-circle-xmark"></i> Inactive
                            </label>
                        </div>
                    </div>
                    <div class="form-hint">Inactive brands are hidden from the storefront.</div>
                    <div class="inactive-warning" id="inactiveWarn">
                        <i class="fa fa-triangle-exclamation"></i>
                        <strong>Note:</strong> This brand has <strong><?php echo $productCount; ?></strong>
                        product<?php echo $productCount != 1 ? 's' : ''; ?> linked to it.
                        Setting it to Inactive will hide those products from the storefront.
                    </div>
                </div>

            </div><!-- form-body -->

            <div class="form-footer">
                <button type="submit" class="btn-save">
                    <i class="fa fa-floppy-disk"></i> Save Changes
                </button>
                <a href="brands.php" class="btn-cancel">
                    <i class="fa fa-xmark"></i> Cancel
                </a>
            </div>
            </form>
        </div><!-- form-card -->
        </div><!-- col-main -->

        <!-- ASIDE -->
        <div class="col-aside">

            <!-- Record Info -->
            <div class="side-card">
                <div class="side-card-header">
                    <i class="fa fa-circle-info"></i>
                    <h3>Record Info</h3>
                </div>
                <div class="side-card-body">
                    <div class="meta-row">
                        <i class="fa fa-hashtag"></i>
                        <span>ID: <strong>#<?php echo $brand->brand_id; ?></strong></span>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-calendar"></i>
                        <div>
                            <div style="margin-bottom:2px;">Created</div>
                            <strong><?php echo date('d M Y', strtotime($brand->created_at)); ?></strong>
                        </div>
                    </div>
                    <div class="meta-row">
                        <i class="fa fa-signal"></i>
                        <div>
                            <div style="margin-bottom:4px;">Current Status</div>
                            <?php if ($brand->status === 'active'): ?>
                            <span class="status-badge status-active"><i class="fa fa-circle" style="font-size:0.45rem;"></i> Active</span>
                            <?php else: ?>
                            <span class="status-badge status-inactive"><i class="fa fa-circle" style="font-size:0.45rem;"></i> Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked Products -->
            <div class="side-card">
                <div class="side-card-header">
                    <i class="fa fa-box"></i>
                    <h3>Linked Products</h3>
                </div>
                <div class="side-card-body">
                    <div class="meta-row">
                        <i class="fa fa-layer-group"></i>
                        <div>
                            <div style="margin-bottom:6px;font-size:0.8rem;">Total products using this brand</div>
                            <?php if ($productCount > 0): ?>
                            <span class="count-badge-lg count-active">
                                <i class="fa fa-box" style="font-size:0.65rem;"></i>
                                <?php echo $productCount; ?> product<?php echo $productCount != 1 ? 's' : ''; ?>
                            </span>
                            <?php else: ?>
                            <span class="count-badge-lg count-zero">
                                <i class="fa fa-minus" style="font-size:0.6rem;"></i> No products
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($productCount > 0): ?>
                    <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f0f0f0;">
                        <a href="products.php?brand=<?php echo $id; ?>" style="font-size:0.8rem;color:#d4af37;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                            <i class="fa fa-arrow-up-right-from-square" style="font-size:0.72rem;"></i>
                            View linked products
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- col-aside -->
    </div><!-- edit-layout -->

</div><!-- main -->

<script>
/* ── INACTIVE WARNING ── */
const inactiveWarn = document.getElementById('inactiveWarn');
<?php if ($productCount > 0): ?>
function toggleWarn() {
    const inactive = document.getElementById('status_inactive').checked;
    inactiveWarn.style.display = inactive ? 'block' : 'none';
}
document.querySelectorAll('input[name="status"]').forEach(r => r.addEventListener('change', toggleWarn));
toggleWarn();
<?php endif; ?>

/* ── SUCCESS ── */
<?php if ($success): ?>
Swal.fire({
    icon: 'success',
    title: 'Brand Updated!',
    html: `<b style="color:#d4af37;"><?php echo htmlspecialchars(addslashes($brand->brand_name)); ?></b> has been saved successfully.`,
    confirmButtonText: '<i class="fa fa-check"></i> OK',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>

/* ── DUPLICATE NAME ── */
<?php if ($dupName): ?>
Swal.fire({
    icon: 'error',
    title: 'Duplicate Brand Name',
    html: `A brand named <b style="color:#d4af37;"><?php echo htmlspecialchars(addslashes($_POST['brand_name'])); ?></b> already exists.<br>
           <span style="font-size:0.88rem;color:#888;">Please choose a different name.</span>`,
    confirmButtonText: 'Try Again',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>

/* ── OTHER ERRORS ── */
<?php if (!empty($errors) && !$dupName && !$success): ?>
Swal.fire({
    icon: 'warning',
    title: 'Validation Error',
    html: `<?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>`,
    confirmButtonText: 'Fix It',
    confirmButtonColor: '#d4af37',
    background: '#fff',
});
<?php endif; ?>

/* ── UNSAVED CHANGES WARNING ── */
let formChanged = false;
const form = document.getElementById('editForm');
form.querySelectorAll('input').forEach(el => {
    el.addEventListener('change', () => formChanged = true);
    el.addEventListener('input',  () => formChanged = true);
});
window.addEventListener('beforeunload', e => {
    if (formChanged <?php echo $success ? '&& false' : ''; ?>) {
        e.preventDefault();
        e.returnValue = '';
    }
});
form.addEventListener('submit', () => formChanged = false);
</script>

</body>
</html>