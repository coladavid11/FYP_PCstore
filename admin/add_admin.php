<?php
session_start();
include('includes/config.php');

// Include PHPMailer classes manually via the nested master src directory
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'includes/PHPMailer/PHPMailer-master/src/Exception.php';
require 'includes/PHPMailer/PHPMailer-master/src/PHPMailer.php';
require 'includes/PHPMailer/PHPMailer-master/src/SMTP.php';

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}
if ($_SESSION['admin_role'] != 'superadmin') {
    die("Access Denied");
}

$msg = "";
$error = "";

if (isset($_POST['submit'])) {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    // 1. Email Duplicate Check
    $check = $dbh->prepare("SELECT admin_id FROM admin WHERE email = :email");
    $check->bindParam(':email', $email, PDO::PARAM_STR);
    $check->execute();

    if ($check->rowCount() > 0) {
        $error = "This email is already registered.";
    } else {
        // 2. Generate a secure random password (12 characters long with mixed cases/numbers)
        $bytes = random_bytes(6);
        $random_password = bin2hex($bytes);

        // Hash the generated random password for safe database storage
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO admin (fullname, email, phone, password, role, status) 
                VALUES (:fullname, :email, :phone, :password, :role, 1)";

        $query = $dbh->prepare($sql);
        $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':phone', $phone, PDO::PARAM_STR);
        $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $query->bindParam(':role', $role, PDO::PARAM_STR);

        if ($query->execute()) {

            // 3. Dispatch Notification Email containing credentials to the newly registered admin
            $mail = new PHPMailer(true);

            try {
                // --- SMTP CONFIGURATION ---
                // Replace these values with your actual SMTP credentials (e.g., Gmail, Mailtrap, Hostinger)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';             // Set your SMTP server provider
                $mail->SMTPAuth = true;
                $mail->Username = 'coladavid0203@gmail.com';       // Your enterprise email address
                $mail->Password = 'supx ydta rxkt inuh';          // Your App Password configuration token
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // --- EMAIL METADATA ---
                $mail->setFrom('coladavid0203@gmail.com', 'My PC Store');
                $mail->addAddress($email, $fullname);

                // --- EMAIL CONTENT STRUCTURE ---
                $mail->isHTML(true);
                $mail->Subject = 'Your New Admin Account Credentials — My PC Store';

                // HTML Email Design Layout matching professional structures
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                        <h2 style='color: #ccac3d; border-bottom: 2px solid #f5f5f5; padding-bottom: 10px;'>Welcome to the Management Team!</h2>
                        <p>Hello <strong>" . htmlspecialchars($fullname) . "</strong>,</p>
                        <p>An administrative workspace profile has been provisioned for you at <strong>My PC Store</strong>.</p>
                        <p>Below are your secure login credentials:</p>
                        <table style='background-color: #f8fafc; padding: 15px; border-radius: 6px; width: 100%; margin: 15px 0;'>
                            <tr>
                                <td style='font-weight: bold; color: #444; width: 120px;'>Portal Link:</td>
                                <td><a href='http://" . $_SERVER['HTTP_HOST'] . "/admin_login.php' style='color: #ccac3d; text-decoration: none;'>Admin Login Panel</a></td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; color: #444;'>Username/Email:</td>
                                <td>" . htmlspecialchars($email) . "</td>
                            </tr>
                            <tr>
                                <td style='font-weight: bold; color: #444;'>Temporary Password:</td>
                                <td style='font-family: monospace; font-size: 1.1rem; background: #eef3ff; padding: 2px 6px; border-radius: 4px; color: #333; letter-spacing: 0.5px;'><strong>" . $random_password . "</strong></td>
                            </tr>
                        </table>
                        <p style='color: #dc3545; font-size: 0.85rem;'>⚠️ <strong>Security Action Required:</strong> For access optimization and data preservation safety, please navigate directly to your account dashboard to change this temporary credential on your very first initialization login.</p>
                        <hr style='border: none; border-top: 1px solid #f5f5f5; margin: 20px 0;'>
                        <p style='font-size: 0.8rem; color: #777;'>This system notification is automated. Please do not reply directly to this mail string.</p>
                    </div>
                ";

                $mail->send();
                $msg = "Admin account created successfully! The random password has been dispatched directly to " . htmlspecialchars($email);

            } catch (Exception $e) {
                // Account was saved to database safely, but SMTP processing raised problems
                $msg = "Admin account created successfully in the system database. However, the system encountered issues delivering the registration credentials email notification. Error details: {$mail->ErrorInfo}";
            }

        } else {
            $error = "Something went wrong creating the account. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Admin | My PC Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            background: #f5f5f5;
        }

        .sidebar {
            width: 220px;
            height: 100vh;
            background: #000;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar h2 {
            color: #d4af37;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2rem;
        }

        .sidebar a {
            display: block;
            color: #adadad;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #d4af37;
            color: #000;
        }

        .sidebar a.sidebar-active {
            background: #d4af37;
            color: #000;
        }

        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: #fff;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .topbar h1 {
            font-size: 1.8rem;
            color: #111;
            font-weight: 600;
        }

        .Back {
            text-decoration: none;
            color: #d4af37;
            font-weight: 500;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.95rem;
        }

        .Back:hover {
            opacity: 0.8;
        }

        .form-box {
            background: #fff;
            padding: 35px;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
        }

        .form-box h3 {
            margin-bottom: 25px;
            font-size: 1.25rem;
            color: #111;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 22px;
            width: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #ccac3d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #fff;
            color: #333;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ccac3d;
            box-shadow: 0 0 0 3px rgba(204, 172, 61, 0.1);
        }

        .btn-save {
            background: #000;
            color: #d4af37;
            border: 1px solid #d4af37;
            padding: 13px 28px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: #d4af37;
            color: #000;
        }

        .success {
            background-color: #e2f5ea;
            color: #0b5931;
            border: 1px solid #c3ebd4;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
        }

        .error {
            background-color: #fdf2f2;
            color: #9b1c1c;
            border: 1px solid #fbd5d5;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-note {
            background-color: #eef3ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 22px;
            line-height: 1.4;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="shipping_rates.php">🚚 Shipping Rates</a>
        <a href="sales_report.php">📊 Sales Report</a>
        <a href="admins.php" class="sidebar-active">⚙ Admin</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Add Admin</h1>
            <a href="admins.php" class="Back"><i class="fa-solid fa-arrow-left"></i>Back</a>
        </div>

        <div class="form-box">
            <h3>Create New Admin Account</h3>

            <?php if ($msg) { ?>
                <div class="success">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.2rem; flex-shrink: 0;"></i>
                    <div><?= htmlspecialchars($msg) ?></div>
                </div>
            <?php } ?>

            <?php if ($error) { ?>
                <div class="error">
                    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php } ?>

            <div class="info-note">
                <i class="fa-solid fa-circle-info"></i> <strong>Note:</strong> Password assignment fields have been
                securely automated. The system architecture generates a random password string upon creation and
                automatically delivers it to the target user account email address.
            </div>

            <form method="POST">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="Name" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="Phone" required>
                </div>

                <div class="form-group">
                    <label>Account Role Setting</label>
                    <select name="role">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn-save">
                    <i class="fa-solid fa-user-plus"></i> Register & Send Password
                </button>

            </form>
        </div>

    </div>

</body>

</html>