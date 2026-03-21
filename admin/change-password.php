<?php
session_start();
error_reporting(0);
include('../includes/config.php');

// Security Check: Redirect to login if session is invalid
if(strlen($_SESSION['alogin'])==0)
{   
    header('location:index.php');
}
else{
    // --- Change Password Logic ---
    if(isset($_POST['submit']))
    {
        $username = $_SESSION['alogin'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // 1. Fetch current password hash from database
        $sql = "SELECT Password FROM admin WHERE UserName=:username";
        $query = $dbh->prepare($sql);
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);

        // 2. Verify Current Password
        if($query->rowCount() > 0)
        {
            // Verify if the entered current password matches the hash in DB
            if(password_verify($current_password, $result->Password))
            {
                // 3. Check if New Password matches Confirm Password
                if($new_password == $confirm_password)
                {
                    // 4. Hash the NEW password
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // 5. Update Database
                    $sql_update = "UPDATE admin SET Password=:newpassword WHERE UserName=:username";
                    $chngpwd1 = $dbh->prepare($sql_update);
                    $chngpwd1->bindParam(':newpassword', $new_hashed_password, PDO::PARAM_STR);
                    $chngpwd1->bindParam(':username', $username, PDO::PARAM_STR);
                    $chngpwd1->execute();
                    
                    $msg = "Your password has been changed successfully!";
                }
                else
                {
                    $error = "New Password and Confirm Password do not match.";
                }
            }
            else
            {
                $error = "Your current password is incorrect.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; }
        
        .form-card {
            background: white; 
            border-radius: 12px; 
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
            max-width: 500px;
            margin: 0 auto;
            border-top: 4px solid #f1c40f; /* Yellow Top Border */
        }
        
        h2 { font-weight: 700; color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 15px;}
    </style>
    
    <script type="text/javascript">
        // Simple JS check for password match before submitting
        function valid()
        {
            if(document.chngpwd.new_password.value != document.chngpwd.confirm_password.value)
            {
                alert("New Password and Confirm Password Field do not match!");
                document.chngpwd.confirm_password.focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container">
        
        <div class="row">
            <div class="col-md-12">
                
                <div class="form-card mt-4">
                    <h2><i class="fa fa-key"></i> Change Password</h2>

                    <?php if(isset($msg)){ ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fa fa-check-circle"></i> <?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>

                    <?php if(isset($error)){ ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>

                    <form name="chngpwd" method="post" onSubmit="return valid();">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="submit" class="btn btn-dark btn-lg">Update Password</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>