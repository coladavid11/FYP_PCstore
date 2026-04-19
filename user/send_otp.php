<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userEmail = $_POST['email'];

    // 生成OTP
    $otp = rand(100000, 999999);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'coladavid0203@gmail.com'; // The working email address, Jiun Le personal email adrees.
        $mail->Password = 'supx ydta rxkt inuh';   // App password generated for the above email address, not the actual email password.

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('coladavid0203@gmail.com', 'FYP System');
        $mail->addAddress($userEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP is: <b>$otp</b>";

        $mail->send();

        echo "✅ OTP sent to $userEmail <br>";
        echo "👉 (Testing purpose OTP: $otp)";

    } catch (Exception $e) {
        echo "❌ Error: {$mail->ErrorInfo}";
    }
}
?>