<?php
session_start();
include('includes/config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// If already logged in as admin, go to dashboard
if (isset($_SESSION['admin_login']) && strlen($_SESSION['admin_login']) > 0) {
    header('Location: dashboard.php');
    exit;
}

$errMsg = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errMsg = "Please enter both email and password.";
    } else {

        $sql = "SELECT email, password, fullname, role, status 
                FROM admin 
                WHERE email = :email LIMIT 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $admin = $query->fetch(PDO::FETCH_OBJ);

        if ($query->rowCount() > 0) {

            if (password_verify($password, $admin->password)) {

                // 🔒 check status
                if ($admin->status == 0) {
                    $errMsg = "Account is blocked.";
                } else {

                    $_SESSION['admin_login'] = $admin->email;
                    $_SESSION['admin_name'] = $admin->fullname;
                    $_SESSION['admin_role'] = $admin->role;

                    // 🔥 redirect
                    header("Location: dashboard.php");
                    exit;
                }

            } else {
                $errMsg = "Invalid password.";
            }

        } else {
            $errMsg = "Admin account not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins&family=Playfair+Display&display=swap" rel="stylesheet">

    <style>
        /* 1. Base Setup */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            /* Deep black background */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000000;
        }

        /* 2. The Login Card */
        .login-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background-color: #ffffff;
            /* Dark grey card */
            width: 100%;
            max-width: 400px;
            padding: 50px 40px;
            border-radius: 4px;
            box-shadow: 0 15px 50px rgba(71, 68, 68, 0.9);
            text-align: center;
        }

        /* 3. Logo and Title */
        .store-header {
            margin-bottom: 35px;
        }

        .logo-icon {
            font-size: 3rem;
            color: #ccac3d;
            margin-bottom: 10px;
            display: block;
        }

        .store-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: #000000;
            font-weight: 700;
        }

        /* 4. Form Inputs */
        .mb-3 {
            margin-bottom: 15px;
        }

        .form-label {
            display: none;
            /* Hidden to match the placeholder-only style in your image */
        }

        .form-control {
            width: 100%;
            background-color: #ffffff;
            /* Slightly darker than the card */
            border: 1px solid #333;
            padding: 15px;
            color: #000000;
            font-size: 1rem;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #ccac3d;
            /* Gold border on click */
        }

        .form-control::placeholder {
            color: #555;
        }

        /* 5. Show Password Fix (The "Too Black" Text Fix) */
        .show-password-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            margin-top: 15px;
        }

        .show-password-wrapper label {
            color: #ffffff;
            /* FIX: Changed from black to white for visibility */
            font-size: 0.85rem;
            cursor: pointer;
        }

        .show-password-wrapper input[type="checkbox"] {
            cursor: pointer;
            accent-color: #ccac3d;
            /* Golden checkbox color */
        }

        /* 6. Login Button */
        .btn-login {
            width: 100%;
            background-color: #ccac3d;
            /* Specific gold from image_009a1d.png */
            color: #000;
            padding: 15px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 25px;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .btn-login:hover {
            background-color: #e3e3e3;
            transform: translateY(-2px);
        }

        /* 7. Error Messages */
        .error-msg {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b6b;
            padding: 12px;
            font-size: 0.85rem;
            border: 1px solid #dc3545;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>

</head>

<body>

    <div class="login-wrap">
        <div class="login-card">

            <!-- Logo -->
            <div>
                <h2 class="store-title">Admin Panel</h2>
            </div>

            <!-- Error Message -->
            <?php if (!empty($errMsg)) { ?>
                <div class="error-msg"><?php echo $errMsg; ?></div>
            <?php } ?>

            <!-- Login Form -->
            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter admin email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>

                <div style="margin-top:10px; font-size:0.9rem">
                    <input type="checkbox" onclick="togglePassword()"> Show Password
                </div>

                <button type="submit" name="login" class="btn-login">
                    Login
                </button>

            </form>

        </div>
    </div>
    <script>
        function togglePassword() {
            const pwd = document.getElementsByName("password")[0];
            pwd.type = pwd.type === "password" ? "text" : "password";
        }
    </script>
</body>

</html>