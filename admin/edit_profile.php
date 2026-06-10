<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$email = $_SESSION['admin_login'];
$errors = [];
$success = false;

// Fetch Current Admin Data
$sql = "SELECT * FROM admin WHERE email = :email";
$query = $dbh->prepare($sql);
$query->bindParam(':email', $email, PDO::PARAM_STR);
$query->execute();
$admin = $query->fetch(PDO::FETCH_OBJ);

if (!$admin) {
    header("Location: admin_login.php");
    exit;
}

/* ── HANDLE POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /* Basic validation */
    if ($fullname === '') {
        $errors[] = 'Full name cannot be empty.';
    }
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone === '') {
        $errors[] = 'Phone number cannot be empty.';
    }

    /* Email uniqueness check if email is changing */
    if (empty($errors) && strtolower($new_email) !== strtolower($admin->email)) {
        $chkEmail = $dbh->prepare("SELECT admin_id FROM admin WHERE LOWER(email) = LOWER(:email)");
        $chkEmail->bindParam(':email', $new_email, PDO::PARAM_STR);
        $chkEmail->execute();
        if ($chkEmail->fetch()) {
            $errors[] = 'The email address is already in use by another administrator.';
        }
    }

    /* Password Processing Validation */
    $updating_password = !empty($current_password) || !empty($new_password) || !empty($confirm_password);

    if ($updating_password) {
        // Verify current password
        if (!password_verify($current_password, $admin->password)) {
            $errors[] = 'The current password you entered is incorrect.';
        }

        // Validate new password rules
        if (strlen($new_password) < 8) {
            $errors[] = 'The new password must be at least 8 characters long.';
        }
        if ($new_password === $current_password) {
            $errors[] = 'The new password cannot be the same as your old password.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'The confirmation password does not match your new password.';
        }
    }

    /* Save Profile Changes */
    if (empty($errors)) {
        if ($updating_password) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE admin SET fullname = :fullname, email = :email, phone = :phone, password = :password WHERE admin_id = :id";
            $upd = $dbh->prepare($sql);
            $upd->bindParam(':password', $password_hash, PDO::PARAM_STR);
        } else {
            $sql = "UPDATE admin SET fullname = :fullname, email = :email, phone = :phone WHERE admin_id = :id";
            $upd = $dbh->prepare($sql);
        }

        $upd->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $upd->bindParam(':email', $new_email, PDO::PARAM_STR);
        $upd->bindParam(':phone', $phone, PDO::PARAM_STR);
        $upd->bindParam(':id', $admin->admin_id, PDO::PARAM_INT);

        if ($upd->execute()) {
            $success = true;
            $_SESSION['admin_login'] = $new_email;

            // Refresh local object references
            $admin->fullname = $fullname;
            $admin->email = $new_email;
            $admin->phone = $phone;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            background: #f5f5f5;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #000;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #d4af37;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .sidebar a {
            display: block;
            color: #adadad;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #d4af37;
            color: #000;
        }

        .sidebar a.sidebar-active {
            background: #d4af37;
            color: #000;
        }

        /* ── MAIN ── */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .topbar h1 {
            font-size: 1.6rem;
            color: #111;
            font-weight: 600;
        }

        .topbar-sub {
            font-size: 0.72rem;
            color: #aaa;
            margin-top: 2px;
        }

        .topbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #d4af37;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 8px 14px;
            border: 1px solid #d4af37;
            border-radius: 4px;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #d4af37;
            color: #000;
        }

        /* ── LAYOUT COLUMNS ── */
        .edit-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .col-main {
            flex: 1;
            min-width: 0;
        }

        .col-aside {
            width: 260px;
            flex-shrink: 0;
        }

        /* ── FORM CARD ── */
        .form-card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .form-card-header {
            padding: 18px 28px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-header i {
            color: #d4af37;
            font-size: 1rem;
        }

        .form-card-header h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #111;
        }

        .form-card-header .badge-id {
            margin-left: auto;
            font-size: 0.72rem;
            color: #aaa;
            background: #f5f5f5;
            border: 1px solid #e8e8e8;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .form-body {
            padding: 28px;
        }

        /* ── FORM ELEMENTS ── */
        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }

        .form-label span.req {
            color: #e74c3c;
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.88rem;
            color: #333;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.12);
        }

        .form-hint {
            font-size: 0.76rem;
            color: #aaa;
            margin-top: 5px;
        }

        /* Email changes warning module */
        .email-warning {
            display: none;
            margin-top: 14px;
            padding: 10px 14px;
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 4px;
            font-size: 0.78rem;
            color: #7a6000;
            line-height: 1.5;
        }

        /* ── FOOTER ── */
        .form-footer {
            padding: 18px 28px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 10px;
            align-items: center;
            background: #fafafa;
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
        }

        .btn-save:hover {
            background: #d4af37;
            color: #000;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            color: #888;
            border: 1px solid #e0e0e0;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-cancel:hover {
            border-color: #aaa;
            color: #555;
        }

        /* ── SIDE CARDS ── */
        .side-card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 16px;
        }

        .side-card-header {
            padding: 12px 18px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .side-card-header i {
            color: #d4af37;
            font-size: 0.85rem;
        }

        .side-card-header h3 {
            font-size: 0.8rem;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .side-card-body {
            padding: 16px 18px;
        }

        .meta-row {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            font-size: 0.82rem;
            color: #888;
            margin-bottom: 10px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-row i {
            color: #d4af37;
            width: 14px;
            text-align: center;
            font-size: 0.75rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .meta-row strong {
            color: #444;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .status-inactive {
            background: #f5f5f5;
            color: #888;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="admin.php" class="sidebar-active">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <div>
                <h1><i class="fa fa-user-gear" style="color:#d4af37;margin-right:8px;font-size:1.2rem;"></i>My Profile
                </h1>
                <div class="topbar-sub">Update personal metrics and administrative credentials</div>
            </div>
            <div class="topbar-right">
                <a href="dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> To Dashboard</a>
            </div>
        </div>

        <form method="POST" id="profileForm" novalidate>
            <div class="edit-layout">

                <div class="col-main">

                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fa fa-id-card"></i>
                            <h2>Personal Information</h2>
                            <span class="badge-id">Admin #<?php echo $admin->admin_id; ?></span>
                        </div>
                        <div class="form-body">
                            <div class="form-group">
                                <label class="form-label" for="fullname">Full Name <span class="req">*</span></label>
                                <input type="text" id="fullname" name="fullname" class="form-control"
                                    value="<?php echo htmlspecialchars($admin->fullname); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email Address <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($admin->email); ?>"
                                    data-orig="<?php echo htmlspecialchars($admin->email); ?>" required>
                                <div class="email-warning" id="emailWarn">
                                    <i class="fa fa-triangle-exclamation"></i>
                                    <strong>Note:</strong> Modifying your email updates your primary administrator
                                    system login identifier.
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="phone">Phone Number <span class="req">*</span></label>
                                <input type="text" id="phone" name="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($admin->phone); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="form-card-header">
                            <i class="fa fa-key"></i>
                            <h2>Change Security Credentials</h2>
                        </div>
                        <div class="form-body">
                            <div class="form-hint" style="margin-bottom: 15px;">Leave password fields completely blank
                                if you do not want to update your current system password.</div>

                            <div class="form-group">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password"
                                    class="form-control" placeholder="••••••••">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                    placeholder="Minimum 8 characters">
                                <div class="form-hint">Must be at least 8 characters long and differ from the old one.
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                    class="form-control" placeholder="Retype new password">
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn-save">
                                <i class="fa fa-floppy-disk"></i> Save Profile
                            </button>
                            <a href="dashboard.php" class="btn-cancel">
                                <i class="fa fa-xmark"></i> Cancel
                            </a>
                        </div>
                    </div>

                </div>
                <div class="col-aside">
                    <div class="side-card">
                        <div class="side-card-header">
                            <i class="fa fa-circle-info"></i>
                            <h3>Session Context</h3>
                        </div>
                        <div class="side-card-body">
                            <div class="meta-row">
                                <i class="fa fa-user"></i>
                                <span>Role:
                                    <strong><?php echo htmlspecialchars(strtoupper($admin->role)); ?></strong></span>
                            </div>
                            <div class="meta-row">
                                <i class="fa fa-calendar-day"></i>
                                <div>
                                    <div style="margin-bottom:2px;">Created On</div>
                                    <strong><?php echo date('d M Y', strtotime($admin->created_at)); ?></strong>
                                </div>
                            </div>
                            <div class="meta-row">
                                <i class="fa fa-signal"></i>
                                <div>
                                    <div style="margin-bottom:4px;">Account Status</div>
                                    <?php if (intval($admin->status) === 1): ?>
                                        <span class="status-badge status-active"><i class="fa fa-circle"
                                                style="font-size:0.45rem;"></i> Enabled</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive"><i class="fa fa-circle"
                                                style="font-size:0.45rem;"></i> Disabled</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
    <script>
        /* ── INLINE DYNAMIC EMAIL NOTICE ── */
        const emailInput = document.getElementById('email');
        const emailWarn = document.getElementById('emailWarn');
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                if (this.value.trim().toLowerCase() !== this.getAttribute('data-orig').toLowerCase()) {
                    emailWarn.style.display = 'block';
                } else {
                    emailWarn.style.display = 'none';
                }
            });
        }

        /* ── ALERT SYSTEM HANDLERS (SWEETALERT2) ── */
        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Profile Updated!',
                html: 'Your administration records have been saved completely.',
                confirmButtonText: '<i class="fa fa-check"></i> Complete',
                confirmButtonColor: '#d4af37',
                background: '#fff'
            });
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                html: `<?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>`,
                confirmButtonText: 'Review Fields',
                confirmButtonColor: '#d4af37',
                background: '#fff'
            });
        <?php endif; ?>

        /* ── LEAVE PAGE PREVENTION ── */
        let formChanged = false;
        const form = document.getElementById('profileForm');
        form.querySelectorAll('input').forEach(el => {
            el.addEventListener('change', () => formChanged = true);
            el.addEventListener('input', () => formChanged = true);
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