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

        $mail->Username = 'coladavid11@gmail.com'; // 🔁 换成你的
        $mail->Password = 'pveq yudq bjac kyig';   // 🔁 换成你的App Password

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('coladavid11@gmail.com', 'FYP System');
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