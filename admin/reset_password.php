<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

$error = "";
$success = "";

if (isset($_POST['reset'])) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password != $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Fetch the current hashed password from the database
        $check_sql = "SELECT password FROM admin WHERE email = :email LIMIT 1";
        $check_query = $dbh->prepare($check_sql);
        $check_query->bindParam(':email', $email, PDO::PARAM_STR);
        $check_query->execute();
        $admin_row = $check_query->fetch(PDO::FETCH_ASSOC);

        if ($admin_row) {
            $current_hashed_password = $admin_row['password'];

            // Compare the typed password with the old database password
            if (password_verify($password, $current_hashed_password)) {
                $error = "Your new password cannot be the same as your old password. Please choose a different one.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "UPDATE admin
                        SET password = :password
                        WHERE email = :email";

                $query = $dbh->prepare($sql);
                $query->bindParam(':password', $hash, PDO::PARAM_STR);
                $query->bindParam(':email', $email, PDO::PARAM_STR);
                $query->execute();

                unset($_SESSION['reset_email']);
                $success = "Password has been reset successfully.";
            }
        } else {
            $error = "Account error. Please start the process over.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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
            max-width: 450px;
            padding: 40px 35px;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .reset-card h2 {
            font-size: 2.2rem;
            color: #111;
            font-weight: 500;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
        }

        .reset-subtitle {
            font-size: 0.82rem;
            color: #777;
            margin-bottom: 25px;
            line-height: 1.5;
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

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #444;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* FIXED: Applied style to both [type="password"] AND [type="text"] fields */
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 14px 14px;
            border: 1px solid #bcccf0;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #333;
            background: #eef3ff;
            /* Your signature light blue tint */
            transition: all 0.2s ease;
        }

        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #d4af37;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }

        .show-pass-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
            user-select: none;
        }

        .show-pass-wrapper input {
            cursor: pointer;
            width: 16px;
            height: 16px;
            accent-color: #d4af37;
        }

        .btn-reset {
            width: 100%;
            padding: 14px;
            background: #d4af37;
            color: #000;
            border: none;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-reset:hover {
            background: #bd9a2b;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
        }

        .btn-login-redirect {
            display: block;
            width: 100%;
            padding: 14px;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: 0.2s ease;
        }

        .btn-login-redirect:hover {
            background: #333;
        }
    </style>
</head>

<body>

    <div class="reset-card">
        <h2>Update Password</h2>

        <?php if (empty($success)) { ?>
            <p class="reset-subtitle">Please enter your new administrative credential properties below to secure your
                profile access.</p>
        <?php } ?>

        <?php if ($error) { ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php } ?>

        <?php if ($success) { ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
            <a href="admin_login.php" class="btn-login-redirect">
                Proceed to Login Panel <i class="fa-solid fa-arrow-right" style="margin-left:6px; font-size:0.8rem;"></i>
            </a>
        <?php } else { ?>

            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 8 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm">Confirm New Password</label>
                    <input type="password" id="confirm" name="confirm" placeholder="Repeat new password" required>
                </div>

                <label class="show-pass-wrapper">
                    <input type="checkbox" id="togglePassword"> Show Passwords
                </label>

                <button type="submit" name="reset" class="btn-reset">
                    Reset Password
                </button>
            </form>

        <?php } ?>
    </div>

    <script>
        const toggleCheck = document.getElementById('togglePassword');
        if (toggleCheck) {
            toggleCheck.addEventListener('change', function () {
                const passField = document.getElementById('password');
                const confirmField = document.getElementById('confirm');
                const targetType = this.checked ? 'text' : 'password';
                passField.setAttribute('type', targetType);
                confirmField.setAttribute('type', targetType);
            });
        }
    </script>

</body>

</html>