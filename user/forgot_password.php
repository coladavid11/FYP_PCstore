<?php
/*
 * forgot_password.php
 * Step 1: Enter email → AJAX sends OTP → form POST validates email in DB → go to step 2
 * Step 2: Enter OTP  → AJAX verifies OTP → form POST confirms verified → go to step 3
 * Step 3: Enter new password → form POST updates DB
 *
 * KEY RULE: This file NEVER touches $_SESSION['otp'] or $_SESSION['otp_expiry'].
 *           Those are owned exclusively by send_otp.php and verify_otp.php.
 */
session_start();
include('includes/config.php');
error_reporting(0);

// ── "Use a different email" — wipe reset state and restart ────
if (isset($_GET['reset'])) {
    unset(
        $_SESSION['fp_step'],
        $_SESSION['reset_email'],
        $_SESSION['reset_user_id'],
        $_SESSION['otp'],
        $_SESSION['otp_expiry'],
        $_SESSION['otp_verified'],
        $_SESSION['otp_attempts'],
        $_SESSION['otp_resend_count'],
        $_SESSION['otp_first_request_time']
    );
    header('Location: forgot_password.php');
    exit;
}

// ── Already logged in ─────────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errMsg     = '';
$successMsg = '';

// ═════════════════════════════════════════════════════════════
// STEP 1 POST — validate email in DB, advance to step 2
// (OTP is already sent at this point via AJAX before form submit)
// ═════════════════════════════════════════════════════════════
if (isset($_POST['send_otp'])) {

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $errMsg = "Please enter your email address.";

    } else {
        $stmt = $dbh->prepare("SELECT user_id FROM tbluser WHERE gmail = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            $errMsg = "No account found with this email address.";
            // OTP was already sent but email not in DB — wipe the OTP
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_verified'],
                  $_SESSION['otp_attempts'], $_SESSION['otp_resend_count'],
                  $_SESSION['otp_first_request_time']);
        } else {
            // Store identity — do NOT touch otp/otp_expiry here
            $_SESSION['reset_email']   = $email;
            $_SESSION['reset_user_id'] = $user->user_id;
            $_SESSION['fp_step']       = 2;
        }
    }
}

// ═════════════════════════════════════════════════════════════
// STEP 2 POST — confirm otp_verified flag set by verify_otp.php
// ═════════════════════════════════════════════════════════════
if (isset($_POST['confirm_otp'])) {
    if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
        $_SESSION['fp_step'] = 3;
    } else {
        $errMsg = "OTP not verified. Please enter and verify your OTP.";
        $_SESSION['fp_step'] = 2;
    }
}

// ═════════════════════════════════════════════════════════════
// STEP 3 POST — update password
// ═════════════════════════════════════════════════════════════
if (isset($_POST['reset_password'])) {

    if (empty($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        $errMsg = "Unauthorized. Please complete OTP verification.";
        $_SESSION['fp_step'] = 2;

    } else {
        $newPass     = $_POST['new_password']     ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($newPass) || empty($confirmPass)) {
            $errMsg = "Please fill in both password fields.";
            $_SESSION['fp_step'] = 3;

        } elseif (strlen($newPass) < 6) {
            $errMsg = "Password must be at least 6 characters.";
            $_SESSION['fp_step'] = 3;

        } elseif ($newPass !== $confirmPass) {
            $errMsg = "Passwords do not match.";
            $_SESSION['fp_step'] = 3;

        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt   = $dbh->prepare("UPDATE tbluser SET password = :pw WHERE user_id = :id");
            $stmt->bindParam(':pw', $hashed,                          PDO::PARAM_STR);
            $stmt->bindParam(':id', $_SESSION['reset_user_id'],       PDO::PARAM_INT);
            $stmt->execute();

            // Full cleanup
            unset(
                $_SESSION['fp_step'], $_SESSION['reset_email'], $_SESSION['reset_user_id'],
                $_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_verified'],
                $_SESSION['otp_attempts'], $_SESSION['otp_resend_count'],
                $_SESSION['otp_first_request_time']
            );

            $successMsg = "reset_done";
        }
    }
}

$step = $_SESSION['fp_step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - My PC Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #0f0f0f;
            color: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .login-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }
        .login-card {
            background: #181818;
            border: 1px solid #2a2a2a;
            max-width: 450px;
            width: 100%;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            margin-top: 60px;
        }
        .store-logo { text-align: center; margin-bottom: 24px; }
        .store-logo i { font-size: 2.6rem; color: #d4af37; }
        .store-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; }
        .page-subtitle { text-align: center; color: #aaa; font-size: 0.85rem; margin-bottom: 28px; }

        /* Steps */
        .steps { display: flex; justify-content: center; align-items: center; margin-bottom: 32px; }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            background: #2a2a2a; border: 2px solid #333;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 600; color: #666;
            transition: all .3s; flex-shrink: 0;
        }
        .step-dot.active { border-color: #d4af37; background: #d4af37; color: #000; }
        .step-dot.done   { border-color: #d4af37; background: #1a1a1a; color: #d4af37; }
        .step-line { flex: 1; height: 2px; background: #333; max-width: 60px; }
        .step-line.done { background: #d4af37; }

        /* Inputs */
        .form-control { background: #121212; border: 1px solid #2a2a2a; color: #fff; }
        .form-control:focus { border-color: #d4af37; box-shadow: none; background: #121212; color: #fff; }
        .form-label { color: #ccc; font-size: 0.85rem; margin-bottom: 6px; }

        /* Button */
        .btn-gold {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000; width: 100%; padding: 12px; border: none;
            font-weight: 600; font-family: 'Poppins', sans-serif;
            font-size: 0.95rem; cursor: pointer; transition: opacity .2s; margin-top: 20px;
        }
        .btn-gold:hover { opacity: .88; }
        .btn-gold:disabled { opacity: .5; cursor: not-allowed; }

        /* Error */
        .error-msg {
            background: rgba(220,53,69,0.1); border: 1px solid #dc3545;
            color: #ffb3bc; padding: 10px; margin-bottom: 20px;
            text-align: center; font-size: 0.88rem;
        }

        /* OTP boxes */
        .otp-row { display: flex; gap: 10px; justify-content: center; margin-bottom: 8px; }
        .otp-box {
            width: 56px; height: 56px; text-align: center;
            font-size: 1.4rem; font-weight: 700;
            background: #121212; border: 1px solid #2a2a2a;
            color: #d4af37; caret-color: #d4af37;
            outline: none;
        }
        .otp-box:focus { border-color: #d4af37; }

        /* Resend */
        .resend-row { text-align: center; margin-top: 12px; font-size: 0.83rem; color: #888; }
        .resend-row .resend-btn { color: #d4af37; cursor: pointer; }
        .resend-row .resend-btn.disabled { color: #555; cursor: default; pointer-events: none; }

        /* Email badge */
        .email-badge {
            background: #111; border: 1px solid #333; color: #d4af37;
            padding: 8px 14px; font-size: 0.82rem;
            text-align: center; margin-bottom: 20px; word-break: break-all;
        }

        /* Strength */
        .strength-bar   { height: 4px; border-radius: 2px; margin-top: 6px; transition: all .3s; }
        .strength-label { font-size: 0.75rem; margin-top: 4px; }

        /* Back link */
        .back-link { text-align: center; margin-top: 18px; font-size: 0.82rem; color: #666; }
        .back-link a { color: #d4af37; text-decoration: none; }

        /* Password toggle */
        .pass-wrap { position: relative; }
        .pass-wrap .toggle-eye {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            color: #666; cursor: pointer; font-size: 0.9rem;
        }
        .pass-wrap .toggle-eye:hover { color: #d4af37; }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="login-wrap">
<div class="login-card">

    <div class="store-logo">
        <i class="fa fa-laptop-code"></i>
        <h1 class="store-title">My PC Store</h1>
    </div>

    <!-- Step indicator -->
    <div class="steps">
        <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">
            <?= $step > 1 ? '<i class="fa fa-check" style="font-size:.65rem"></i>' : '1' ?>
        </div>
        <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
        <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">
            <?= $step > 2 ? '<i class="fa fa-check" style="font-size:.65rem"></i>' : '2' ?>
        </div>
        <div class="step-line <?= $step > 2 ? 'done' : '' ?>"></div>
        <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
    </div>

    <?php if ($errMsg !== ''): ?>
        <div class="error-msg">
            <i class="fa fa-circle-exclamation me-1"></i><?= htmlspecialchars($errMsg) ?>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         STEP 1 — Enter email
         ══════════════════════════════════════ -->
    <?php if ($step === 1): ?>

        <p class="page-subtitle">Enter your registered email address and we'll send you a verification code.</p>

        <form method="post" id="formStep1">
            <input type="hidden" name="send_otp" value="1">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" id="emailInput" class="form-control mb-1"
                   placeholder="yourname@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <button type="button" class="btn-gold" id="btnSendOtp">
                <i class="fa fa-paper-plane me-2"></i>Send OTP
            </button>
        </form>

        <div class="back-link">
            <a href="login.php"><i class="fa fa-arrow-left me-1"></i>Back to Login</a>
        </div>

    <!-- ══════════════════════════════════════
         STEP 2 — Verify OTP
         ══════════════════════════════════════ -->
    <?php elseif ($step === 2): ?>

        <p class="page-subtitle">A 4-digit OTP has been sent to:</p>
        <div class="email-badge">
            <i class="fa fa-envelope me-1"></i><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?>
        </div>

        <form method="post" id="formStep2">
            <input type="hidden" name="confirm_otp" value="1">
            <div class="otp-row">
                <input class="otp-box" maxlength="1" inputmode="numeric" id="o1">
                <input class="otp-box" maxlength="1" inputmode="numeric" id="o2">
                <input class="otp-box" maxlength="1" inputmode="numeric" id="o3">
                <input class="otp-box" maxlength="1" inputmode="numeric" id="o4">
            </div>
            <button type="button" class="btn-gold" id="btnVerifyOtp">
                <i class="fa fa-shield-halved me-2"></i>Verify OTP
            </button>
        </form>

        <div class="resend-row">
            OTP expires in <span id="countdown" style="color:#d4af37;">05:00</span>
            &nbsp;|&nbsp;
            <span class="resend-btn disabled" id="resendBtn" onclick="resendOtp()">Resend OTP</span>
        </div>
        <div class="back-link">
            <a href="javascript:void(0)" onclick="confirmChangeEmail()">
                <i class="fa fa-arrow-left me-1"></i>Use a different email
            </a>
        </div>

    <!-- ══════════════════════════════════════
         STEP 3 — New password
         ══════════════════════════════════════ -->
    <?php elseif ($step === 3): ?>

        <p class="page-subtitle">OTP verified! Set your new password below.</p>

        <form method="post" id="formStep3">
            <label class="form-label">New Password</label>
            <div class="pass-wrap mb-1">
                <input type="password" name="new_password" id="newPass"
                       class="form-control" placeholder="Min. 6 characters" required>
                <i class="fa fa-eye toggle-eye" onclick="togglePass('newPass', this)"></i>
            </div>
            <div class="strength-bar" id="strengthBar"></div>
            <div class="strength-label" id="strengthLabel"></div>

            <label class="form-label mt-3">Confirm New Password</label>
            <div class="pass-wrap">
                <input type="password" name="confirm_password" id="confirmPass"
                       class="form-control" placeholder="Re-enter password" required>
                <i class="fa fa-eye toggle-eye" onclick="togglePass('confirmPass', this)"></i>
            </div>
            <div id="matchMsg" style="font-size:.75rem;margin-top:4px;"></div>

            <button type="submit" name="reset_password" class="btn-gold">
                <i class="fa fa-key me-2"></i>Reset Password
            </button>
        </form>

    <?php endif; ?>

</div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const swalBase = { background: '#1a1a1a', color: '#fff', confirmButtonColor: '#d4af37' };

// ══════════════════════════════════════════════════════════════
// STEP 1 — AJAX send OTP, then submit form
// ══════════════════════════════════════════════════════════════
const btnSendOtp = document.getElementById('btnSendOtp');
if (btnSendOtp) {
    btnSendOtp.addEventListener('click', function () {
        const email = document.getElementById('emailInput').value.trim();

        if (!email) {
            Swal.fire({ ...swalBase, title: 'Required', text: 'Please enter your email address.', icon: 'warning', iconColor: '#d4af37' });
            return;
        }

        btnSendOtp.disabled = true;
        btnSendOtp.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Sending OTP...';

        fetch('send_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // OTP is now stored in session by send_otp.php
                // Submit the form so PHP can validate email in DB and go to step 2
                document.getElementById('formStep1').submit();
            } else {
                Swal.fire({ ...swalBase, title: 'Failed to Send OTP', text: data.message, icon: 'error', iconColor: '#ff4d4d' });
                btnSendOtp.disabled = false;
                btnSendOtp.innerHTML = '<i class="fa fa-paper-plane me-2"></i>Send OTP';
            }
        })
        .catch(() => {
            Swal.fire({ ...swalBase, title: 'Network Error', text: 'Could not reach the server. Please try again.', icon: 'error' });
            btnSendOtp.disabled = false;
            btnSendOtp.innerHTML = '<i class="fa fa-paper-plane me-2"></i>Send OTP';
        });
    });
}

// ══════════════════════════════════════════════════════════════
// STEP 2 — OTP boxes behaviour
// ══════════════════════════════════════════════════════════════
const otpBoxes = document.querySelectorAll('.otp-box');

otpBoxes.forEach((box, i) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '');
        if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
    });
    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && i > 0) otpBoxes[i - 1].focus();
    });
    box.addEventListener('paste', e => {
        e.preventDefault();
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 4);
        digits.split('').forEach((d, j) => { if (otpBoxes[j]) otpBoxes[j].value = d; });
        otpBoxes[Math.min(digits.length, otpBoxes.length - 1)].focus();
    });
});

// Verify OTP button
const btnVerify = document.getElementById('btnVerifyOtp');
if (btnVerify) {
    btnVerify.addEventListener('click', function () {
        const otp = [...otpBoxes].map(b => b.value).join('');

        if (otp.length < 4) {
            Swal.fire({ ...swalBase, title: 'Incomplete', text: 'Please enter all 4 digits.', icon: 'warning', iconColor: '#d4af37' });
            return;
        }

        btnVerify.disabled = true;
        btnVerify.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Verifying...';

        fetch('verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'otp=' + encodeURIComponent(otp)
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    ...swalBase,
                    title: 'Verified!',
                    text: 'Proceeding to reset your password.',
                    icon: 'success', timer: 1400,
                    showConfirmButton: false, iconColor: '#1fd719'
                }).then(() => {
                    // otp_verified is now true in session (set by verify_otp.php)
                    // Submit form to let PHP advance to step 3
                    document.getElementById('formStep2').submit();
                });
            } else {
                Swal.fire({ ...swalBase, title: 'Failed', text: data.message, icon: 'error', iconColor: '#ff4d4d' });
                btnVerify.disabled = false;
                btnVerify.innerHTML = '<i class="fa fa-shield-halved me-2"></i>Verify OTP';
                otpBoxes.forEach(b => b.value = '');
                otpBoxes[0].focus();
            }
        })
        .catch(() => {
            Swal.fire({ ...swalBase, title: 'Error', text: 'Network error. Please try again.', icon: 'error' });
            btnVerify.disabled = false;
            btnVerify.innerHTML = '<i class="fa fa-shield-halved me-2"></i>Verify OTP';
        });
    });
}

// ══════════════════════════════════════════════════════════════
// STEP 2 — Countdown + resend
// ══════════════════════════════════════════════════════════════
<?php if ($step === 2): ?>
let timeLeft = 300;
const countdownEl = document.getElementById('countdown');
const resendEl    = document.getElementById('resendBtn');

const countdownTimer = setInterval(() => {
    timeLeft--;
    if (timeLeft <= 0) {
        clearInterval(countdownTimer);
        countdownEl.textContent      = '00:00';
        countdownEl.style.color      = '#ff4d4d';
        resendEl.classList.remove('disabled');
    } else {
        const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
        const s = String(timeLeft % 60).padStart(2, '0');
        countdownEl.textContent = `${m}:${s}`;
    }
}, 1000);

otpBoxes[0] && otpBoxes[0].focus();

function resendOtp() {
    const email = '<?= addslashes($_SESSION['reset_email'] ?? '') ?>';

    fetch('send_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ ...swalBase, title: 'OTP Sent!', text: 'A new OTP has been sent to your email.', icon: 'success', timer: 1800, showConfirmButton: false, iconColor: '#1fd719' });
            // Reset countdown
            timeLeft = 300;
            countdownEl.style.color = '#d4af37';
            resendEl.classList.add('disabled');
        } else {
            Swal.fire({ ...swalBase, title: 'Failed', text: data.message, icon: 'error', iconColor: '#ff4d4d' });
        }
    });
}
<?php endif; ?>

// ══════════════════════════════════════════════════════════════
// "Use a different email"
// ══════════════════════════════════════════════════════════════
function confirmChangeEmail() {
    Swal.fire({
        ...swalBase,
        title: 'Change Email?',
        html: `<p style="color:#ccc;font-size:.88rem;margin-bottom:6px;">
                   Please use the email you <strong style="color:#d4af37;">registered</strong> with.
               </p>
               <p style="color:#888;font-size:.82rem;">Your current OTP session will be cleared.</p>`,
        icon: 'info', iconColor: '#d4af37',
        showCancelButton: true,
        confirmButtonText: 'Yes, change email',
        cancelButtonText:  'Cancel',
        cancelButtonColor: '#333',
        reverseButtons: true
    }).then(r => { if (r.isConfirmed) window.location.href = 'forgot_password.php?reset=1'; });
}

// ══════════════════════════════════════════════════════════════
// STEP 3 — Password strength + match
// ══════════════════════════════════════════════════════════════
const newPassEl     = document.getElementById('newPass');
const confirmPassEl = document.getElementById('confirmPass');

if (newPassEl) {
    newPassEl.addEventListener('input', () => {
        const val = newPassEl.value;
        const bar = document.getElementById('strengthBar');
        const lbl = document.getElementById('strengthLabel');
        let score = 0;
        if (val.length >= 6)           score++;
        if (val.length >= 10)          score++;
        if (/[A-Z]/.test(val))         score++;
        if (/[0-9]/.test(val))         score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const levels = [
            { pct:'20%',  color:'#ff4d4d', label:'Very Weak'   },
            { pct:'40%',  color:'#ff944d', label:'Weak'        },
            { pct:'60%',  color:'#ffd700', label:'Fair'        },
            { pct:'80%',  color:'#a3d977', label:'Strong'      },
            { pct:'100%', color:'#1fd719', label:'Very Strong' }
        ];
        if (!val) { bar.style.width = '0'; lbl.textContent = ''; return; }
        const lvl = levels[Math.max(0, score - 1)];
        bar.style.width = bar.style.background = lvl.color; // width set below
        bar.style.width      = lvl.pct;
        bar.style.background = lvl.color;
        lbl.style.color      = lvl.color;
        lbl.textContent      = lvl.label;
    });
}

if (confirmPassEl) {
    confirmPassEl.addEventListener('input', () => {
        const msg = document.getElementById('matchMsg');
        if (!confirmPassEl.value) { msg.textContent = ''; return; }
        if (newPassEl.value === confirmPassEl.value) {
            msg.style.color = '#1fd719'; msg.textContent = '✓ Passwords match';
        } else {
            msg.style.color = '#ff4d4d'; msg.textContent = '✗ Passwords do not match';
        }
    });
}

function togglePass(id, icon) {
    const el = document.getElementById(id);
    el.type = el.type === 'text' ? 'password' : 'text';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

// ══════════════════════════════════════════════════════════════
// Password reset success
// ══════════════════════════════════════════════════════════════
<?php if ($successMsg === 'reset_done'): ?>
Swal.fire({
    ...swalBase,
    title: 'Password Reset!',
    text: 'Your password has been updated successfully.',
    icon: 'success', iconColor: '#1fd719',
    confirmButtonText: 'Go to Login',
    allowOutsideClick: false
}).then(() => { window.location.href = 'login.php'; });
<?php endif; ?>
</script>
</body>
</html>