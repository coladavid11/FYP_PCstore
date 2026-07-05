<?php
session_start();
include('includes/config.php');

$error = "";
$success_identity = false;
$verified_email = "";

// Handle Step 1: Initial Identity Verification via POST form submission
if (isset($_POST['verify_identity'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($email) || empty($phone)) {
        $error = "Both email and phone number are required.";
    } else {
        $sql = "SELECT * FROM admin WHERE email = :email AND phone = :phone LIMIT 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':phone', $phone, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() > 0) {
            $_SESSION['reset_email'] = $email;
            $verified_email = $email;
            $success_identity = true; // Signals the frontend to unveil the OTP section
        } else {
            $error = "The email or phone number provided is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .reset-card {
            background: #fff;
            width: 100%;
            max-width: 480px;
            padding: 40px 35px;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .reset-card h2 {
            font-size: 2.2rem;
            color: #111;
            font-weight: 500;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
            text-align: center;
        }

        .reset-subtitle {
            font-size: 0.82rem;
            color: #777;
            margin-bottom: 25px;
            line-height: 1.5;
            text-align: center;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-hint {
            font-size: 0.8rem;
            color: #666;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: #444;
            font-size: 0.8rem;
            font-weight: 500;
        }

        input[type="email"],
        input[type="tel"],
        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #bcccf0;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #333;
            background: #eef3ff;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #d4af37;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }

        .btn-save, .btn-verify-custom {
            background: #000;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.2s ease;
            width: 100%;
            padding: 12px;
        }

        .btn-save:hover, .btn-verify-custom:hover {
            background: #222;
        }

        .btn-warning-custom {
            background: #d4af37;
            color: #000;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 6px 14px;
            cursor: pointer;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .btn-warning-custom:hover {
            background: #bd9a2b;
        }

        .otp-inner-container {
            border-top: 1px dashed #e0e0e0;
            margin-top: 20px;
            padding-top: 20px;
        }

        .back-to-login-container {
            text-align: center;
            margin-top: 25px;
        }

        .back-to-login {
            display: inline-block;
            font-size: 0.85rem;
            color: #d4af37;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        .back-to-login:hover {
            color: #000;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="reset-card">
        <h2>Identity Verification</h2>
        <p class="reset-subtitle">Enter your registered admin details below to proceed with resetting your password profile.</p>

        <?php if ($error) { ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php } ?>

        <div class="form-body">

            <form method="POST" action="" id="identityForm">
                <div id="identityVerificationStep">
                    <div class="form-hint" style="margin-bottom: 15px;">
                        To ensure your administrative security, you must first verify your identity metrics using registered records.
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Admin Email Address</label>
                        <input type="email" id="email" name="email" placeholder="example@gmail.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" <?php echo $success_identity ? 'disabled' : ''; ?> required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="phone">Registered Phone Number</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="tel" id="phone" name="phone" placeholder="e.g. +60123456789"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" <?php echo $success_identity ? 'disabled' : ''; ?> required>
                            
                            <?php if (!$success_identity) { ?>
                                <button type="submit" name="verify_identity" id="btnVerifyPassword" class="btn-save" style="white-space: nowrap; padding: 10px 20px; width: auto;">
                                    Next
                                </button>
                            <?php } ?>
                        </div>
                        
                        <div id="verifyMessage" style="font-size: 0.8rem; margin-top: 8px; font-weight: 500; 
                            <?php echo $success_identity ? 'color: #28a745;' : 'display: none;'; ?>">
                            <?php if ($success_identity) { echo "Identity Verified! Ready for security token validation."; } ?>
                        </div>
                    </div>
                </div>
            </form>

            <div id="otpVerificationSection" class="otp-inner-container" style="<?php echo $success_identity ? 'display: block;' : 'display: none;'; ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h4 style="font-size: 0.9rem; font-weight: 600; color: #111; margin-bottom: 2px;">OTP Verification</h4>
                        <span class="form-hint" style="margin: 0;">Verify your registered email before password change</span>
                    </div>
                    <button type="button" class="btn-warning-custom" id="sendOtpBtn">Get OTP</button>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label class="form-label" for="otp_input">Enter OTP</label>
                        <input type="text" id="otp_input" maxlength="4" placeholder="4-digit OTP" 
                            style="text-align: center; font-weight: bold; font-size: 1.1rem; letter-spacing: 5px;">
                    </div>
                    <div style="width: 140px;">
                        <button type="button" class="btn-verify-custom" id="verifyOtpBtn">Verify OTP</button>
                    </div>
                </div>
            </div>

        </div>

        <div class="back-to-login-container">
            <a href="admin_login.php" class="back-to-login">
                <i class="fa-solid fa-arrow-left" style="font-size:0.75rem; margin-right:4px;"></i> Back to Login Panel
            </a>
        </div>
    </div>

    <script>
        // Only run action engines initialization context if Identity Verification succeeded server-side
        <?php if ($success_identity) { ?>
        
        // ── SEND OTP ACTION ENGINE ──────────────────────────────────────
        document.getElementById('sendOtpBtn').addEventListener('click', function () {
            const btn = this;
            btn.disabled = true;
            btn.innerText = 'Sending...';

            fetch('send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent('<?php echo $verified_email; ?>') + '&context=profile'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: 'OTP Sent', text: data.message,
                        background: '#ffffff', color: '#212529', confirmButtonColor: '#000000'
                    });
                    
                    // 30-second security cooldown countdown block
                    let sec = 30;
                    const iv = setInterval(() => {
                        btn.innerText = `Resend (${sec}s)`;
                        sec--;
                        if (sec < 0) {
                            clearInterval(iv);
                            btn.disabled = false;
                            btn.innerText = 'Get OTP';
                        }
                    }, 1000);
                } else {
                    Swal.fire({
                        icon: 'error', title: 'Failed', text: data.message,
                        background: '#ffffff', color: '#212529', confirmButtonColor: '#000000'
                    });
                    btn.disabled = false;
                    btn.innerText = 'Get OTP';
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error', title: 'Error', text: 'Network connection dropped.',
                    background: '#ffffff', color: '#212529', confirmButtonColor: '#000000'
                });
                btn.disabled = false;
                btn.innerText = 'Get OTP';
            });
        });

        // ── VERIFY OTP ACTION ENGINE ──────────────────────────────────
        document.getElementById('verifyOtpBtn').addEventListener('click', function () {
            const otpValue = document.getElementById('otp_input').value.trim();

            if (!/^\d{4}$/.test(otpValue)) {
                Swal.fire({
                    icon: 'warning', title: 'Invalid OTP', text: 'OTP must be exactly 4 digits.',
                    background: '#ffffff', color: '#212529', confirmButtonColor: '#000000'
                });
                return;
            }

            fetch('verify_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'otp=' + encodeURIComponent(otpValue)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success', title: 'Verified!', text: data.message,
                        background: '#ffffff', color: '#212529', confirmButtonColor: '#000000',
                        timer: 1500, showConfirmButton: false
                    });
                    
                    // Freeze action tokens and redirect safely to password configuration view
                    document.getElementById('otp_input').disabled = true;
                    this.disabled = true;
                    document.getElementById('sendOtpBtn').disabled = true;

                    setTimeout(() => {
                        window.location.href = 'reset_password.php';
                    }, 1600);
                } else {
                    Swal.fire({
                        icon: 'error', title: 'Failed', text: data.message,
                        background: '#ffffff', color: '#212529', confirmButtonColor: '#000000'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error', title: 'Error', text: 'Network connection processing dropped.',
                    background: '#ffffff', color: '#212529', confirmButtonColor: '#000000'
                });
            });
        });
        
        <?php } ?>
    </script>
</body>
</html>