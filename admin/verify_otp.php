<?php
/*
 * verify_otp.php
 * Called via AJAX (POST) with: otp=xxxx
 * Checks session OTP and sets otp_verified = true on success.
 * Returns JSON only — no redirect.
 */
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$userOtp = trim($_POST['otp'] ?? '');

if (empty($userOtp)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter OTP.']);
    exit;
}

// ── Check OTP exists in session ───────────────────────────────
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
    echo json_encode(['status' => 'error', 'message' => 'No OTP found. Please request a new OTP.']);
    exit;
}

// ── Check expiry ──────────────────────────────────────────────
if (time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['otp'], $_SESSION['otp_expiry']);
    echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

// ── Check max attempts ────────────────────────────────────────
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

if ($_SESSION['otp_attempts'] >= 5) {
    echo json_encode(['status' => 'error', 'message' => 'Too many wrong attempts. Please request a new OTP.']);
    exit;
}

// ── Verify ────────────────────────────────────────────────────
if ((string)$userOtp === (string)$_SESSION['otp']) {

    $_SESSION['otp_verified'] = true;
    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_attempts']); // clean up

    echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully.']);

} else {

    $_SESSION['otp_attempts']++;
    $remaining = 5 - $_SESSION['otp_attempts'];

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid OTP. ' . $remaining . ' attempt(s) remaining.'
    ]);
}