<?php
session_start();
include('includes/config.php');

// Security Guard: Kick out anyone who isn't authenticated through the login card
if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$msg = "";
$error = "";

if (isset($_POST['change_password'])) {
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];
    $admin_id = $_SESSION['admin_id'];

    if ($new_pass !== $conf_pass) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the chosen password safely
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

        // Update DB: Change password string and toggle first_login to 0
        $sql = "UPDATE admin SET password = :password, first_login = 0 WHERE admin_id = :admin_id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $query->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);

        if ($query->execute()) {
            header("Location: dashboard.php?setup=complete");
            exit;
        } else {
            $error = "Something went wrong updating credentials. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* =========================
           GENERAL RESET & BASIS
        ========================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* =========================
           CARD WRAPPER
        ========================= */
        .reset-card {
            background: #fff;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        /* =========================
           HEADER LOGO & TEXT
        ========================= */
        .icon-box {
            font-size: 3rem;
            color: #ccac3d;
            margin-bottom: 15px;
        }

        h2 {
            font-size: 1.8rem;
            color: #111;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        /* =========================
           FORM FIELDS & GROUPS
        ========================= */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            color: #ccac3d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #fff;
            color: #333;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ccac3d;
            box-shadow: 0 0 0 3px rgba(204, 172, 61, 0.1);
        }

        /* Options Utility Line */
        .options-row {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 8px;
            margin-top: -5px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            color: #444;
            text-align: left;
        }

        .options-row input[type="checkbox"] {
            cursor: pointer;
            accent-color: #ccac3d;
        }

        .options-row label {
            cursor: pointer;
            user-select: none;
        }

        /* =========================
           ACTION BUTTONS
        ========================= */
        .btn-submit {
            width: 100%;
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 14px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #d4af37;
            color: #000;
        }

        /* =========================
           ALERTS & NOTIFICATIONS
        ========================= */
        .error-msg {
            background-color: #fdf2f2;
            color: #9b1c1c;
            border: 1px solid #fbd5d5;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }
    </style>
</head>
<body>

    <div class="reset-card">
        <div class="icon-box">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h2>Secure Your Account</h2>
        <p class="subtitle">This is your first login session initialization. To complete security parameters, please establish a brand-new administrative account credential access password below.</p>

        <?php if (!empty($error)) { ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-xmark"></i> 
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php } ?>

        <form method="POST">

            <div class="form-group">
                <label>New Passphrase</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="new_password" class="form-control" placeholder="Minimum 8 characters" required>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Passphrase</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-check-double"></i>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat your new password" required>
                </div>
            </div>

            <div class="options-row">
                <input type="checkbox" id="toggleMask" onclick="togglePasswordVisibility()">
                <label invert for="toggleMask">Show Generated Passwords</label>
            </div>

            <button type="submit" name="change_password" class="btn-submit">
                <i class="fa-solid fa-key"></i> Initialize & Save Profile
            </button>

        </form>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passField = document.getElementsByName("new_password")[0];
            const confField = document.getElementsByName("confirm_password")[0];
            
            if (passField.type === "password") {
                passField.type = "text";
                confField.type = "text";
            } else {
                passField.type = "password";
                confField.type = "password";
            }
        }
    </script>
</body>
</html>