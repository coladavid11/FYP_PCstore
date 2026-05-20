<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $userOtp = trim($_POST['otp'] ?? '');

    if (empty($userOtp)) {

        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter OTP.'
        ]);

        exit;
    }

    // OTP expired
    if (!isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {

        echo json_encode([
            'status' => 'error',
            'message' => 'OTP expired.'
        ]);

        exit;
    }

    // max attempts
    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }

    if ($_SESSION['otp_attempts'] >= 5) {

        echo json_encode([
            'status' => 'error',
            'message' => 'Too many wrong attempts.'
        ]);

        exit;
    }

    // verify
    if ($userOtp == $_SESSION['otp']) {

        $_SESSION['otp_verified'] = true;

        echo json_encode([
            'status' => 'success',
            'message' => 'OTP Verified Successfully.'
        ]);

    } else {

        $_SESSION['otp_attempts']++;

        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid OTP.'
        ]);
    }
}
?>