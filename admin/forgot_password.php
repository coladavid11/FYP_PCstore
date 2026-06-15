<?php
session_start();
include('includes/config.php');

$error = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($email) || empty($phone)) {
        $error = "Both email and phone number are required.";
    } else {
        $sql = "SELECT * FROM admin 
                WHERE email = :email AND phone = :phone 
                LIMIT 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':phone', $phone, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() > 0) {
            $_SESSION['reset_email'] = $email;
            header("Location: reset_password.php");
            exit();
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
    <!-- Importing Poppins to match your application font family -->
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
            /* Matches the clean white/gray canvas background from your screenshots */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Centered form card layout cloned directly from image_003e7a.png */
        .reset-card {
            background: #fff;
            width: 100%;
            max-width: 450px;
            padding: 40px 35px;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        /* Matches your exact "Admin Panel" header typography style */
        .reset-card h2 {
            font-size: 2.2rem;
            color: #111;
            font-weight: 500;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
            /* Used to replicate the distinct serif look in your title */
        }

        .reset-subtitle {
            font-size: 0.82rem;
            color: #777;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* Alert Styling matching your admin message formats */
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

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #444;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Input styling with the exact soft blue tint seen in image_003e7a.png */
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #bcccf0;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #333;
            background: #eef3ff;
            /* Matching your login page field tint */
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #d4af37;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }

        /* Primary Button matching your exact Gold Hex and full width style */
        .btn-continue {
            width: 100%;
            padding: 14px;
            background: #d4af37;
            /* Gold tone from your theme */
            color: #000;
            border: none;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            margin-top: 5px;
        }

        .btn-continue:hover {
            background: #bd9a2b;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
        }

        /* Text Link tracking back to Login screen design */
        .back-to-login {
            display: inline-block;
            margin-top: 25px;
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
        <p class="reset-subtitle">Enter your registered admin details below to proceed with resetting your password
            profile.</p>

        <!-- Error Banner Placement -->
        <?php if ($error) { ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php } ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="email">Admin Email Address</label>
                <input type="email" id="email" name="email" placeholder="example@gmail.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Registered Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="e.g. +60123456789"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
            </div>

            <button type="submit" name="submit" class="btn-continue">
                Verify Identity
            </button>

        </form>

        <a href="admin_login.php" class="back-to-login">
            <i class="fa-solid fa-arrow-left" style="font-size:0.75rem; margin-right:4px;"></i> Back to Login Panel
        </a>
    </div>

</body>

</html>