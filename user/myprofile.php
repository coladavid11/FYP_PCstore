<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id    = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || !$user_id) {
    header('Location: login.php');
    exit;
}

/* ============================================================
   FETCH USER DATA
   ============================================================ */
$stmt = $dbh->prepare("SELECT * FROM tbluser WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die('User not found.');

// Member days
$memberDays = (new DateTime())->diff(new DateTime($user['reg_date']))->days;

/* ============================================================
   FETCH STATES
   ============================================================ */
$stateStmt = $dbh->query("SELECT state_id, state_name FROM tblstate ORDER BY state_id");
$states    = $stateStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   VARIABLES (populated from DB, overwritten on POST error)
   ============================================================ */
$successMsg = '';
$errorMsg   = '';

$fullname  = $user['fullname'];
$gmail     = $user['gmail'];
$gender    = $user['gender'];
$phone     = $user['phone_num'];
$addr1     = $user['addr_line1'] ?? '';
$addr2     = $user['addr_line2'] ?? '';
$postcode  = $user['postcode']   ?? '';
$city      = $user['city']       ?? '';
$state_id  = $user['state_id']   ?? '';

/* ============================================================
   UPDATE PROFILE
   ============================================================ */
if (isset($_POST['update_profile'])) {

    // — Read POST values —
    $fullname  = trim($_POST['fullname']   ?? '');
    $gender    = trim($_POST['gender']     ?? '');
    $phone     = trim($_POST['phone_num']  ?? '');
    $addr1     = trim($_POST['addr_line1'] ?? '');
    $addr2     = trim($_POST['addr_line2'] ?? ''); // optional
    $postcode  = trim($_POST['postcode']   ?? '');
    $city      = trim($_POST['city']       ?? '');
    $state_id  = (int)($_POST['state_id']  ?? 0);

    $current_password = trim($_POST['current_password'] ?? '');
    $new_password     = trim($_POST['new_password']     ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    /* — Validation — */
    if (empty($fullname) || empty($gender) || empty($phone) ||
        empty($addr1)    || empty($postcode) || empty($city) || empty($state_id)) {

        $errorMsg = "Please fill in all required fields.";

    } elseif (!preg_match('/^[0-9]{5}$/', $postcode)) {

        $errorMsg = "Postcode must be exactly 5 digits.";

    } elseif (!preg_match('/^01[0-9]-?[0-9]{7,8}$/', $phone)) {

        $errorMsg = "Invalid phone number format. (e.g. 012-3456789)";

    } elseif (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {

        // Password change path
        if (!empty($new_password) && empty($_SESSION['otp_verified'])) {
            $errorMsg = "Please verify OTP first before changing password.";

        } elseif (!password_verify($current_password, $user['password'])) {
            $errorMsg = "Current password is incorrect.";

        } elseif (strlen($new_password) < 8) {
            $errorMsg = "New password must be at least 8 characters.";

        } elseif (preg_match('/\s/', $new_password)) {
            $errorMsg = "Password cannot contain spaces.";

        } elseif ($current_password === $new_password) {
            $errorMsg = "New password cannot be the same as current password.";

        } elseif ($new_password !== $confirm_password) {
            $errorMsg = "Confirm password does not match.";
        }
    }

    /* — Update DB — */
    if (empty($errorMsg)) {
        try {
            if (empty($new_password)) {

                $sql = "UPDATE tbluser
                        SET fullname  = :fullname,
                            gender    = :gender,
                            phone_num = :phone,
                            addr_line1 = :addr1,
                            addr_line2 = :addr2,
                            postcode   = :postcode,
                            city       = :city,
                            state_id   = :state_id
                        WHERE user_id = :user_id";
                $q = $dbh->prepare($sql);
                $q->bindParam(':fullname',  $fullname,  PDO::PARAM_STR);
                $q->bindParam(':gender',    $gender,    PDO::PARAM_STR);
                $q->bindParam(':phone',     $phone,     PDO::PARAM_STR);
                $q->bindParam(':addr1',     $addr1,     PDO::PARAM_STR);
                $q->bindParam(':addr2',     $addr2,     PDO::PARAM_STR);
                $q->bindParam(':postcode',  $postcode,  PDO::PARAM_STR);
                $q->bindParam(':city',      $city,      PDO::PARAM_STR);
                $q->bindParam(':state_id',  $state_id,  PDO::PARAM_INT);
                $q->bindParam(':user_id',   $user_id,   PDO::PARAM_INT);
                $q->execute();

            } else {

                $hashed = password_hash($new_password, PASSWORD_BCRYPT);

                $sql = "UPDATE tbluser
                        SET fullname   = :fullname,
                            gender     = :gender,
                            phone_num  = :phone,
                            addr_line1 = :addr1,
                            addr_line2 = :addr2,
                            postcode   = :postcode,
                            city       = :city,
                            state_id   = :state_id,
                            password   = :password
                        WHERE user_id = :user_id";
                $q = $dbh->prepare($sql);
                $q->bindParam(':fullname',  $fullname,  PDO::PARAM_STR);
                $q->bindParam(':gender',    $gender,    PDO::PARAM_STR);
                $q->bindParam(':phone',     $phone,     PDO::PARAM_STR);
                $q->bindParam(':addr1',     $addr1,     PDO::PARAM_STR);
                $q->bindParam(':addr2',     $addr2,     PDO::PARAM_STR);
                $q->bindParam(':postcode',  $postcode,  PDO::PARAM_STR);
                $q->bindParam(':city',      $city,      PDO::PARAM_STR);
                $q->bindParam(':state_id',  $state_id,  PDO::PARAM_INT);
                $q->bindParam(':password',  $hashed,    PDO::PARAM_STR);
                $q->bindParam(':user_id',   $user_id,   PDO::PARAM_INT);
                $q->execute();

                // Clear OTP flag after password change
                unset($_SESSION['otp_verified']);
            }

            $successMsg = "Profile updated successfully.";

            // Refresh $user so form shows updated values
            $stmt->execute([$user_id]);
            $user     = $stmt->fetch(PDO::FETCH_ASSOC);
            $fullname = $user['fullname'];
            $gender   = $user['gender'];
            $phone    = $user['phone_num'];
            $addr1    = $user['addr_line1'];
            $addr2    = $user['addr_line2'];
            $postcode = $user['postcode'];
            $city     = $user['city'];
            $state_id = $user['state_id'];

        } catch (PDOException $e) {
            $errorMsg = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - My PC Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="newstyle.css">

    <style>
    .form-control:disabled {
        background: #121212 !important;
        color: #555 !important;
        border-color: #2a2a2a !important;
        opacity: 1;
    }
    </style>
    
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container py-5">
<div class="row g-4">

    <!-- ── LEFT PROFILE CARD ─────────────────────────────── -->
    <div class="col-lg-4">
        <div class="dark-card p-4 text-center">

            <div class="mb-4">
                <i class="fa-solid fa-circle-user" style="font-size:120px;color:#d4af37;"></i>
            </div>

            <h3 class="mb-2"><?php echo htmlentities($fullname); ?></h3>
            <p class="text-soft mb-1"><?php echo htmlentities($gmail); ?></p>

            <hr style="border-color:#2a2a2a;">

            <div class="text-start">
                <p><i class="fa fa-user text-warning me-2"></i>Customer Account</p>
                <p><i class="fa fa-calendar text-warning me-2"></i>Member for <?php echo $memberDays; ?> days</p>
            </div>

            <?php if (!empty($addr1)): ?>
            <hr style="border-color:#2a2a2a;">
            <div class="text-start">
                <p class="mb-1" style="color:#aaa;font-size:0.82rem;">
                    <i class="fa fa-location-dot text-warning me-2"></i>
                    <?php
                        // Find state name for display
                        $stateName = '';
                        foreach ($states as $s) {
                            if ($s['state_id'] == $state_id) { $stateName = $s['state_name']; break; }
                        }
                        $addrParts = array_filter([$addr1, $addr2, $postcode . ' ' . $city, $stateName]);
                        echo htmlentities(implode(', ', $addrParts));
                    ?>
                </p>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ── RIGHT FORM ────────────────────────────────────── -->
    <div class="col-lg-8">
    <div class="dark-card p-4">

        <h2 class="section-title mb-4" style="color:white;">My Profile</h2>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <form method="POST">

            <!-- ── Personal Info ── -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">User Name <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control"
                           value="<?php echo htmlentities($fullname); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control"
                           value="<?php echo htmlentities($gmail); ?>" disabled>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone_num" id="phone_num" class="form-control"
                           maxlength="12" placeholder="012-3456789"
                           value="<?php echo htmlentities($phone); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male"   <?php echo $gender === 'Male'   ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other"  <?php echo $gender === 'Other'  ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <hr style="border-color:#2a2a2a;">

            <!-- ── Delivery Address ── -->
            <h4 class="mb-3" style="color:#d4af37;">
                <i class="fa fa-location-dot me-2"></i>Delivery Address
            </h4>

            <!-- Address Line 1 -->
            <div class="mb-3">
                <label class="form-label">
                    Address Line 1 <span class="text-danger">*</span>
                    <small class="text-soft ms-1">(Street, House / Unit No.)</small>
                </label>
                <input type="text" name="addr_line1" class="form-control"
                       placeholder="e.g. No. 12, Jalan Harmoni 3"
                       value="<?php echo htmlentities($addr1); ?>" required>
            </div>

            <!-- Address Line 2 -->
            <div class="mb-3">
                <label class="form-label">
                    Address Line 2
                    <small class="text-soft ms-1">(Optional — Taman, Apartment, Floor)</small>
                </label>
                <input type="text" name="addr_line2" class="form-control"
                       placeholder="e.g. Taman Lagenda Putra"
                       value="<?php echo htmlentities($addr2); ?>">
            </div>

            <!-- Postcode + City -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Postcode <span class="text-danger">*</span></label>
                    <input type="text" name="postcode" id="postcode" class="form-control"
                           maxlength="5" placeholder="e.g. 81000"
                           value="<?php echo htmlentities($postcode); ?>" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control"
                           placeholder="e.g. Kulai"
                           value="<?php echo htmlentities($city); ?>" required>
                </div>
            </div>

            <!-- State -->
            <div class="mb-4">
                <label class="form-label">State <span class="text-danger">*</span></label>
                <select name="state_id" class="form-select" required>
                    <option value="">— Select State —</option>
                    <?php foreach ($states as $s): ?>
                        <option value="<?php echo $s['state_id']; ?>"
                            <?php echo ($s['state_id'] == $state_id) ? 'selected' : ''; ?>>
                            <?php echo htmlentities($s['state_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr style="border-color:#2a2a2a;">

            <!-- ── Change Password ── -->
            <h4 class="mb-3" style="color:#d4af37;">
                <i class="fa fa-lock me-2"></i>Change Password
            </h4>

            <div class="alert alert-dark border border-warning text-light mb-4" style="background:#151515;">
                <i class="fa fa-shield-halved text-warning me-2"></i>
                For security purposes, OTP verification is required before changing your password.
            </div>

            <!-- Current Password -->
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control"
                       placeholder="Enter current password">
            </div>

            <!-- OTP Card -->
            <div class="dark-card p-4 mb-3" style="background:#121212;border:1px solid #2a2a2a;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1 text-warning">OTP Verification</h5>
                        <small class="text-soft">Verify your registered email before password change</small>
                    </div>
                    <button type="button" class="btn btn-warning" id="sendOtpBtn">Get OTP</button>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Enter OTP</label>
                        <input type="text" id="otp_input" class="form-control"
                               maxlength="4" placeholder="4-digit OTP">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-success w-100" id="verifyOtpBtn">
                            Verify OTP
                        </button>
                    </div>
                </div>
            </div>

            <!-- New Password -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control"
                           placeholder="Minimum 8 characters"
                           pattern="^(?!\s).{8,}$"
                           title="At least 8 characters, no spaces" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password"
                           class="form-control" placeholder="Re-enter new password" disabled>
                </div>
            </div>

            <!-- Save -->
            <div class="mt-4">
                <button type="submit" name="update_profile" class="btn-cta">
                    <i class="fa fa-floppy-disk me-2"></i>Save Changes
                </button>
            </div>

        </form>

    </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

// ── SEND OTP ──────────────────────────────────────────────────
document.getElementById('sendOtpBtn').addEventListener('click', function () {
    const btn = this;
    btn.disabled  = true;
    btn.innerText = 'Sending...';

    fetch('send_otp.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'email=<?php echo urlencode($gmail); ?>&context=profile'
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success', title: 'OTP Sent', text: data.message,
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
            });
            // 30-second cooldown
            let sec = 30;
            const iv = setInterval(() => {
                btn.innerText = `Resend OTP (${sec}s)`;
                sec--;
                if (sec < 0) {
                    clearInterval(iv);
                    btn.disabled  = false;
                    btn.innerText = 'Get OTP';
                }
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error', title: 'Failed', text: data.message,
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
            });
            btn.disabled  = false;
            btn.innerText = 'Get OTP';
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error', title: 'Error', text: 'Network error.',
            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
        });
        btn.disabled  = false;
        btn.innerText = 'Get OTP';
    });
});

// ── VERIFY OTP ────────────────────────────────────────────────
document.getElementById('verifyOtpBtn').addEventListener('click', function () {
    const otp = document.getElementById('otp_input').value.trim();

    if (!/^\d{4}$/.test(otp)) {
        Swal.fire({
            icon: 'warning', title: 'Invalid OTP', text: 'OTP must be 4 digits.',
            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
        });
        return;
    }

    fetch('verify_otp.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'otp=' + otp
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success', title: 'Verified!', text: data.message,
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
            });
            // Unlock password fields
            document.getElementById('new_password').disabled     = false;
            document.getElementById('confirm_password').disabled = false;
        } else {
            Swal.fire({
                icon: 'error', title: 'Failed', text: data.message,
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37'
            });
        }
    });
});

</script>

<?php include('includes/footer.php'); ?>
</body>
</html>