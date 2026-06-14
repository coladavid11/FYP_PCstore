<?php
session_start();
include('includes/config.php');
error_reporting(0);

// If already logged in, go home
if (isset($_SESSION['login']) && strlen($_SESSION['login']) > 0) {
    header('Location: index.php');
    exit;
}

$errMsg          = '';
$registerSuccess = false;
$successName     = '';

// Fetch states for dropdown
$stateStmt = $dbh->query("SELECT state_id, state_name FROM tblstate ORDER BY state_id");
$states    = $stateStmt->fetchAll(PDO::FETCH_ASSOC);

// Preserve POST values on error
$post = [
    'fullname'  => '',
    'gmail'     => '',
    'gender'    => '',
    'phone_num' => '',
    'addr1'     => '',
    'addr2'     => '',
    'postcode'  => '',
    'city'      => '',
    'state_id'  => '',
];

if (isset($_POST['register'])) {

    $post['fullname']  = trim($_POST['fullname']   ?? '');
    $post['gmail']     = trim($_POST['gmail']       ?? '');
    $post['gender']    = trim($_POST['gender']      ?? '');
    $post['phone_num'] = trim($_POST['phone_num']   ?? '');
    $post['addr1']     = trim($_POST['addr_line1']  ?? '');
    $post['addr2']     = trim($_POST['addr_line2']  ?? '');
    $post['postcode']  = trim($_POST['postcode']    ?? '');
    $post['city']      = trim($_POST['city']        ?? '');
    $post['state_id']  = (int)($_POST['state_id']   ?? 0);

    $password = trim($_POST['password']         ?? '');
    $cpass    = trim($_POST['confirm_password'] ?? '');

    // ── Validation ────────────────────────────────────────────
    if (
        $post['fullname'] === '' || $post['gmail'] === '' ||
        $post['gender']   === '' || $post['phone_num'] === '' ||
        $post['addr1']    === '' || $post['postcode'] === '' ||
        $post['city']     === '' || $post['state_id'] === 0  ||
        $password         === '' || $cpass === ''
    ) {
        $errMsg = "Please fill in all required fields.";

    } elseif (!filter_var($post['gmail'], FILTER_VALIDATE_EMAIL)) {
        $errMsg = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {
        $errMsg = "Password must be at least 8 characters long.";

    } elseif (preg_match('/\s/', $password)) {
        $errMsg = "Password cannot contain spaces.";

    } elseif ($password !== $cpass) {
        $errMsg = "Password and Confirm Password do not match.";

    } elseif (!preg_match('/^01[0-9]-?[0-9]{7,8}$/', $post['phone_num'])) {
        $errMsg = "Invalid phone number format. (e.g. 012-3456789)";

    } elseif (!preg_match('/^[0-9]{5}$/', $post['postcode'])) {
        $errMsg = "Postcode must be exactly 5 digits.";

    } else {
        // Check duplicate email
        $chk = $dbh->prepare("SELECT user_id FROM tbluser WHERE gmail = :email LIMIT 1");
        $chk->bindParam(':email', $post['gmail'], PDO::PARAM_STR);
        $chk->execute();

        if ($chk->rowCount() > 0) {
            $errMsg = "This email is already registered.";

        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO tbluser
                        (fullname, gmail, password, gender, phone_num,
                         addr_line1, addr_line2, postcode, city, state_id)
                    VALUES
                        (:fullname, :email, :password, :gender, :phone,
                         :addr1, :addr2, :postcode, :city, :state_id)";

            $q = $dbh->prepare($sql);
            $q->bindParam(':fullname',  $post['fullname'],  PDO::PARAM_STR);
            $q->bindParam(':email',     $post['gmail'],     PDO::PARAM_STR);
            $q->bindParam(':password',  $hashed,            PDO::PARAM_STR);
            $q->bindParam(':gender',    $post['gender'],    PDO::PARAM_STR);
            $q->bindParam(':phone',     $post['phone_num'], PDO::PARAM_STR);
            $q->bindParam(':addr1',     $post['addr1'],     PDO::PARAM_STR);
            $q->bindParam(':addr2',     $post['addr2'],     PDO::PARAM_STR);
            $q->bindParam(':postcode',  $post['postcode'],  PDO::PARAM_STR);
            $q->bindParam(':city',      $post['city'],      PDO::PARAM_STR);
            $q->bindParam(':state_id',  $post['state_id'],  PDO::PARAM_INT);
            $q->execute();

            if ($dbh->lastInsertId()) {
                $registerSuccess = true;
                $successName     = $post['fullname'];
            } else {
                $errMsg = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - My PC Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Poppins', sans-serif; background: #0f0f0f; color: #fff; }

        .page-wrap { min-height: 80vh; display: flex; align-items: center; padding: 40px 0; }

        .register-card {
            background: #181818;
            border: 1px solid #2a2a2a;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .register-header { padding: 28px 28px 0 28px; text-align: center; }

        .register-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            margin-bottom: 8px;
        }

        .section-divider {
            width: 60px; height: 2px;
            background: #d4af37;
            margin: 0 auto 22px auto; border: none;
        }

        .register-body { padding: 0 28px 28px 28px; }

        .form-label {
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        .section-label {
            color: #d4af37;
            font-size: 0.88rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            background: #121212;
            border: 1px solid #2a2a2a;
            color: #fff;
            border-radius: 0;
            padding: 12px;
        }
        .form-control:focus, .form-select:focus {
            background: #121212;
            color: #fff;
            border-color: #d4af37;
            box-shadow: none;
        }
        .form-control::placeholder { color: #555; }

        /* Keep select options readable */
        .form-select option { background: #1a1a1a; color: #fff; }

        .btn-gold {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000; font-weight: bold; border: none;
            text-transform: uppercase; width: 100%; padding: 12px;
            transition: 0.3s;
        }
        .btn-gold:hover { background: #fff; color: #000; transform: translateY(-2px); }

        .error-box {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ffb3bc; padding: 12px; margin-bottom: 16px;
        }

        .input-group-text {
            background: #121212; border: 1px solid #2a2a2a;
            color: #d4af37; border-radius: 0;
        }

        .divider-line {
            border-color: #2a2a2a;
            margin: 20px 0 16px 0;
        }

        .optional-badge {
            font-size: 0.72rem;
            color: #888;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            margin-left: 4px;
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="page-wrap">
<div class="container">
<div class="row justify-content-center">
<div class="col-md-11 col-lg-8">
<div class="register-card">

    <div class="register-header">
        <h1 class="register-title">Register</h1>
        <p style="color:#888;">Join us today</p>
        <hr class="section-divider">
    </div>

    <div class="register-body">

        <?php if ($errMsg !== ''): ?>
            <div class="error-box">
                <i class="fa fa-triangle-exclamation me-1"></i><?php echo htmlentities($errMsg); ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">

            <!-- ── Account Info ──────────────────────────────── -->
            <div class="section-label">
                <i class="fa fa-user"></i> Account Information
            </div>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">User Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" class="form-control" name="fullname"
                               placeholder="John Doe"
                               value="<?php echo htmlentities($post['fullname']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="email" class="form-control" name="gmail"
                               placeholder="example@gmail.com"
                               value="<?php echo htmlentities($post['gmail']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-venus-mars"></i></span>
                        <select class="form-select" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male"   <?php echo $post['gender'] === 'Male'   ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $post['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other"  <?php echo $post['gender'] === 'Other'  ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-phone"></i></span>
                        <input type="text" class="form-control" name="phone_num"
                               id="phone_num" placeholder="012-3456789" maxlength="12"
                               value="<?php echo htmlentities($post['phone_num']); ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                        <input type="password" class="form-control" name="password"
                               id="password" minlength="8"
                               placeholder="Min. 8 characters, no spaces" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                        <input type="password" class="form-control" name="confirm_password"
                               id="confirm_password" placeholder="Re-enter password" required>
                    </div>
                    <div id="passMatchMsg" style="font-size:0.78rem;margin-top:4px;"></div>
                </div>

            </div>

            <!-- ── Delivery Address ──────────────────────────── -->
            <hr class="divider-line">

            <div class="section-label">
                <i class="fa fa-location-dot"></i> Delivery Address
            </div>

            <div class="row g-3">

                <!-- Address Line 1 -->
                <div class="col-12">
                    <label class="form-label">
                        Address Line 1 <span class="text-danger">*</span>
                        <span class="optional-badge">(Street, House / Unit No.)</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-road"></i></span>
                        <input type="text" class="form-control" name="addr_line1"
                               placeholder="e.g. No. 12, Jalan Harmoni 3"
                               value="<?php echo htmlentities($post['addr1']); ?>" required>
                    </div>
                </div>

                <!-- Address Line 2 -->
                <div class="col-12">
                    <label class="form-label">
                        Address Line 2
                        <span class="optional-badge">(Optional — Taman, Apartment, Floor)</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-building"></i></span>
                        <input type="text" class="form-control" name="addr_line2"
                               placeholder="e.g. Taman Lagenda Putra"
                               value="<?php echo htmlentities($post['addr2']); ?>">
                    </div>
                </div>

                <!-- Postcode + City -->
                <div class="col-md-4">
                    <label class="form-label">Postcode <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-hashtag"></i></span>
                        <input type="text" class="form-control" name="postcode"
                               id="postcode" maxlength="5" placeholder="e.g. 81000"
                               value="<?php echo htmlentities($post['postcode']); ?>" required>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-city"></i></span>
                        <input type="text" class="form-control" name="city"
                               placeholder="e.g. Kulai"
                               value="<?php echo htmlentities($post['city']); ?>" required>
                    </div>
                </div>

                <!-- State -->
                <div class="col-12">
                    <label class="form-label">State <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-map"></i></span>
                        <select class="form-select" name="state_id" required>
                            <option value="">— Select State —</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s['state_id']; ?>"
                                    <?php echo ($s['state_id'] == $post['state_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlentities($s['state_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>

            <!-- ── Submit ─────────────────────────────────────── -->
            <div class="col-12 mt-4">
                <button type="submit" name="register" class="btn-gold">
                    <i class="fa fa-user-plus me-2"></i>Create Account
                </button>
            </div>

            <div class="text-center mt-3" style="color:#aaa;">
                Already have an account?
                <a style="color:#d4af37;text-decoration:none;" href="login.php">Login here</a>
            </div>

        </form>
    </div><!-- /register-body -->
</div><!-- /register-card -->
</div>
</div>
</div>
</div>

<script>
// ── Phone auto-format ─────────────────────────────────────────
document.getElementById('phone_num').addEventListener('input', function (e) {
    let v = e.target.value.replace(/\D/g, '');
    if (v.length > 3) v = v.slice(0, 3) + '-' + v.slice(3);
    e.target.value = v;
});

// ── Postcode: digits only ─────────────────────────────────────
document.getElementById('postcode').addEventListener('input', function (e) {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 5);
});

// ── Password match indicator ──────────────────────────────────
document.getElementById('confirm_password').addEventListener('input', function () {
    const pw  = document.getElementById('password').value;
    const msg = document.getElementById('passMatchMsg');
    if (!this.value) { msg.textContent = ''; return; }
    if (pw === this.value) {
        msg.style.color   = '#1fd719';
        msg.textContent   = '✓ Passwords match';
    } else {
        msg.style.color   = '#ff4d4d';
        msg.textContent   = '✗ Passwords do not match';
    }
});
</script>

<?php if ($registerSuccess): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Registration Successful!',
        text: 'Welcome <?php echo addslashes($successName); ?>! Please login.',
        timer: 2500,
        showConfirmButton: false,
        background: '#1a1a1a',
        color: '#fff',
        iconColor: '#1fd719'
    }).then(() => { window.location.href = 'login.php'; });
</script>
<?php endif; ?>

<?php include('includes/footer.php'); ?>
</body>
</html>