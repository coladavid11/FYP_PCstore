<?php
session_start();
include('../includes/config.php');

// Initialize variables
$error_msg = ""; 
$login_success = false;

// --- Login Logic ---
if(isset($_POST['login']))
{
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Check if username exists
    $sql = "SELECT UserName, Password FROM admin WHERE UserName=:username";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetch(PDO::FETCH_OBJ);

    // 2. If username is found
    if($query->rowCount() > 0)
    {
        // 3. Verify Password
        if(password_verify($password, $results->Password)) 
        {
            // ✅ Success
            $_SESSION['alogin'] = $_POST['username'];
            $login_success = true; 
        } 
        else 
        {
            // ❌ Error: Incorrect Password
            $error_msg = "Incorrect Password. Please try again.";
        }
    } 
    else 
    {
        // ❌ Error: Invalid Username
        $error_msg = "Invalid Username. Account does not exist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | Car Rental Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            /* Background Image */
            background: linear-gradient(rgba(10, 10, 15, 0.8), rgba(10, 10, 15, 0.9)), url('https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            
            /* 🔧 FIX: Prevent scrollbars and jumping */
            overflow: hidden; 
            margin: 0;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Top Gold Accent Bar */
        .login-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #f1c40f; 
        }

        .brand-logo { font-size: 3rem; color: #2c3e50; margin-bottom: 10px; }
        .login-title { color: #333; font-weight: 700; margin-bottom: 30px; font-size: 1.6rem; letter-spacing: -0.5px; }

        .input-group-text { background: #f0f2f5; border-right: none; color: #6c757d; border-color: #e9ecef; }
        .form-control { border-left: none; background: #f0f2f5; padding: 12px; font-size: 0.95rem; border-color: #e9ecef; color: #495057; }
        .form-control:focus { box-shadow: none; border-color: #f1c40f; background: #fff; }
        .input-group:focus-within .input-group-text { background: #fff; color: #f1c40f; border-color: #f1c40f; }

        .btn-login { background-color: #2c3e50; color: white; font-weight: 600; padding: 12px; border-radius: 8px; border: none; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; }
        .btn-login:hover { background-color: #f1c40f; color: #2c3e50; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(241, 196, 15, 0.4); }

        .back-link { display: block; margin-top: 25px; color: #999; text-decoration: none; font-size: 0.85rem; transition: 0.3s; }
        .back-link:hover { color: #f1c40f; }
        
        .forgot-link { color: #6c757d; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .forgot-link:hover { color: #f1c40f; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-logo"><i class="fa fa-car"></i></div>
        <h2 class="login-title">Admin Portal</h2>
        
        <form method="post">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa fa-user"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            
            <div class="input-group mb-4">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            
            <div class="d-grid">
                <button type="submit" name="login" class="btn btn-login">Login Access</button>
            </div>

            <div class="text-center mt-3">
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>
        </form>
        
        <a href="../index.php" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Homepage
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if($login_success) { ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Welcome Back!',
            text: 'Login successful. Redirecting to dashboard...',
            timer: 2000,
            showConfirmButton: false,
            heightAuto: false // 🔧 FIX: Prevents screen from jumping
        }).then(() => {
            window.location = 'dashboard.php';
        });
    </script>
    <?php } ?>

    <?php if($error_msg != "") { ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: '<?php echo $error_msg; ?>',
            confirmButtonColor: '#2c3e50',
            confirmButtonText: 'Try Again',
            heightAuto: false // 🔧 FIX: Prevents screen from jumping
        });
    </script>
    <?php } ?>

</body>
</html>