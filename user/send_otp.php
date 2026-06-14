<?php
/*
 * send_otp.php
 * Called via AJAX (POST) with: email=xxx
 * Generates OTP, stores in session, sends email.
 * Does NOT redirect — returns JSON only.
 */
session_start();

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// ── Rate limit ────────────────────────────────────────────────
if (!isset($_SESSION['otp_resend_count']))       $_SESSION['otp_resend_count']       = 0;
if (!isset($_SESSION['otp_first_request_time'])) $_SESSION['otp_first_request_time'] = time();

// Reset counter after 5 minutes
if (time() - $_SESSION['otp_first_request_time'] > 300) {
    $_SESSION['otp_resend_count']       = 0;
    $_SESSION['otp_first_request_time'] = time();
}

if ($_SESSION['otp_resend_count'] >= 3) {
    echo json_encode(['status' => 'error', 'message' => 'Too many OTP requests. Please wait 5 minutes.']);
    exit;
}

// ── Email ─────────────────────────────────────────────────────
$userEmail = trim($_POST['email'] ?? '');

if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

// ── Generate & store OTP BEFORE sending email ─────────────────
$otp = rand(1000, 9999);

$_SESSION['otp']          = $otp;
$_SESSION['otp_expiry']   = time() + 300; // 5 minutes
$_SESSION['otp_verified'] = false;
$_SESSION['otp_attempts'] = 0;            // reset attempts on new OTP
$_SESSION['otp_resend_count']++;

// ── Send email ────────────────────────────────────────────────
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'coladavid0203@gmail.com';
    $mail->Password   = 'supx ydta rxkt inuh';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('coladavid0203@gmail.com', 'MY PC STORE');
    $mail->addAddress($userEmail);
    $mail->isHTML(true);
    $mail->Subject = 'MY PC STORE - OTP Verification';
    $mail->Body    = "
    <div style='background:#111;padding:40px;font-family:Arial;color:#fff;'>
        <div style='max-width:600px;margin:auto;background:#1a1a1a;
                    border:1px solid #d4af37;padding:40px;border-radius:10px;'>
            <h1 style='color:#d4af37;text-align:center;'>MY PC STORE</h1>
            <p style='font-size:16px;'>Password Reset Verification</p>
            <div style='background:#000;padding:20px;text-align:center;
                        margin:30px 0;border-radius:8px;border:1px solid #333;'>
                <span style='font-size:40px;letter-spacing:10px;color:#d4af37;font-weight:bold;'>
                    $otp
                </span>
            </div>
            <p style='color:#aaa;'>This OTP will expire in 5 minutes.</p>
            <p style='color:#888;font-size:13px;'>
                If you did not request this, please ignore this email.
            </p>
        </div>
    </div>";

    $mail->send();

    echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully.']);

} catch (Exception $e) {
    // OTP was stored but email failed — clear it so user isn't stuck
    unset($_SESSION['otp'], $_SESSION['otp_expiry']);
    $_SESSION['otp_resend_count']--;

    echo json_encode(['status' => 'error', 'message' => 'Failed to send email: ' . $mail->ErrorInfo]);
}