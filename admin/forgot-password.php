<?php
session_start();
error_reporting(0);
include('../includes/config.php');

if(isset($_POST['update']))
{
    $username = $_POST['username'];
    $email = $_POST['email'];
    $newpassword = $_POST['newpassword'];
    $confirmpassword = $_POST['confirmpassword'];

    // 1. Check if New Password matches Confirm Password
    if($newpassword != $confirmpassword){
        $error = "New Password and Confirm Password do not match!";
    }
    else {
        // 2. Check if Username and Email match the database record
        $sql = "SELECT id FROM admin WHERE UserName=:username AND Email=:email";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        if($query->rowCount() > 0)
        {
            // 3. Validation Successful: Hash the new password and update database
            $hashed_password = password_hash($newpassword, PASSWORD_DEFAULT);
            
            $con = "UPDATE admin SET Password=:newpassword WHERE UserName=:username";
            $chngpwd1 = $dbh->prepare($con);
            $chngpwd1->bindParam(':newpassword', $hashed_password, PDO::PARAM_STR);
            $chngpwd1->bindParam(':username', $username, PDO::PARAM_STR);
            $chngpwd1->execute();
            
            $msg = "Your password has been reset successfully!";
            
            // Redirect to Login Page after 3 seconds
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 3000);</script>";
        }
        else
        {
            $error = "Invalid Username or Email id.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Password Recovery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            /* Consistent Background with Login Page */
            background: linear-gradient(rgba(10, 10, 15, 0.8), rgba(10, 10, 15, 0.9)), url('https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
            text-align: center;
            border-top: 5px solid #dc3545; /* Red accent for Emergency/Reset */
        }

        .login-title { color: #333; font-weight: 700; margin-bottom: 20px; }
        .btn-reset { background-color: #dc3545; color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: 600; transition: 0.3s; }
        .btn-reset:hover { background-color: #c82333; }
        .back-link { display: block; margin-top: 20px; color: #999; text-decoration: none; }
        .back-link:hover { color: #dc3545; }
    </style>
</head>
<body>

    <div class="login-card">
        
        <h3 class="login-title"><i class="fa fa-lock"></i> Password Recovery</h3>
        <p class="text-muted small mb-4">Enter your details to reset your password.</p>

        <?php if(isset($msg)){ ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?php echo $msg; ?>
                <br><small>Redirecting to login...</small>
            </div>
        <?php } ?>

        <?php if(isset($error)){ ?>
            <div class="alert alert-danger">
                <i class="fa fa-times-circle"></i> <?php echo $error; ?>
            </div>
        <?php } ?>

        <form method="post">
            
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa fa-user"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Registered Email" required>
            </div>

            <hr class="my-4">

            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fa fa-key"></i></span>
                <input type="password" name="newpassword" class="form-control" placeholder="New Password" required>
            </div>

            <div class="input-group mb-4">
                <span class="input-group-text"><i class="fa fa-check"></i></span>
                <input type="password" name="confirmpassword" class="form-control" placeholder="Confirm Password" required>
            </div>
            
            <button type="submit" name="update" class="btn-reset">RESET PASSWORD</button>
        </form>
        
        <a href="index.php" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to Login
        </a>
    </div>

</body>
</html>