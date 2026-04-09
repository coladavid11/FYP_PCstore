<?php
session_start();
include('includes/config.php');
error_reporting(0);

// If already logged in, go home
if (isset($_SESSION['login']) && strlen($_SESSION['login']) > 0) {
    header('Location: index.php');
    exit;
}

$errMsg = '';
$registerSuccess = false;
$successName = '';

if (isset($_POST['register'])) {

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['gmail'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $cpass    = trim($_POST['confirm_password'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $phone    = trim($_POST['phone_num'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    // Basic validation
    if ($fullname === '' || $email === '' || $password === '' || $cpass === '' || $gender === '' || $phone === '' || $address === '') {
        $errMsg = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errMsg = "Please enter a valid email address.";
    } 
    // --- Password Rules: Min 8 chars, No spaces ---
    elseif (strlen($password) < 8) {
        $errMsg = "Password must be at least 8 characters long.";
    } elseif (preg_match('/\s/', $password)) {
        $errMsg = "Password cannot contain spaces.";
    } 
    // --- End Password Rules ---
    elseif ($password !== $cpass) {
        $errMsg = "Password and Confirm Password do not match.";
    } 
    // --- Phone Number Rule: Max 15 digits ---
    elseif (!preg_match('/^[0-9]{1,15}$/', $phone)) {
        $errMsg = "Phone number must be digits only and maximum 15 characters.";
    } 
    else {
        // Check if email already exists
        $checkSql = "SELECT user_id FROM tbluser WHERE gmail = :email LIMIT 1";
        $checkQuery = $dbh->prepare($checkSql);
        $checkQuery->bindParam(':email', $email, PDO::PARAM_STR);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            $errMsg = "This email is already registered.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert into tbluser
            $sql = "INSERT INTO tbluser(fullname, gmail, password, gender, phone_num, address)
                    VALUES(:fullname, :email, :password, :gender, :phone, :address)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $query->bindParam(':gender', $gender, PDO::PARAM_STR);
            $query->bindParam(':phone', $phone, PDO::PARAM_STR);
            $query->bindParam(':address', $address, PDO::PARAM_STR);

            $query->execute();
            $lastInsertId = $dbh->lastInsertId();

            if ($lastInsertId) {
                $registerSuccess = true;
                $successName = $fullname;
            } else {
                $errMsg = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - User Account</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    body{ font-family:'Poppins',sans-serif; background:#0f0f0f; color:#fff; }
    .page-wrap{ min-height: 80vh; display:flex; align-items:center; padding: 40px 0; }
    .register-card{ background:#181818; border:1px solid #2a2a2a; box-shadow:0 10px 30px rgba(0,0,0,0.3); }
    .register-header{ padding: 28px 28px 0 28px; text-align:center; }
    .register-title{ font-family:'Playfair Display',serif; font-size:2.2rem; margin-bottom:8px; }
    .section-divider{ width:60px; height:2px; background:#d4af37; margin: 0 auto 22px auto; border:none; }
    .register-body{ padding: 0 28px 28px 28px; }
    .form-label{ color:#aaa; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem; }
    .form-control, .form-select{ background:#121212; border:1px solid #2a2a2a; color:#fff; border-radius:0; padding:12px; }
    .form-control:focus, .form-select:focus{ background:#121212; color:#fff; border-color:#d4af37; box-shadow: none; }
    .btn-gold{ background: linear-gradient(45deg, #d4af37, #c5a028); color:#000; font-weight:bold; border:none; text-transform:uppercase; width:100%; padding:12px; transition:0.3s; }
    .btn-gold:hover{ background:#fff; color:#000; transform: translateY(-2px); }
    .error-box{ background: rgba(220,53,69,0.12); border:1px solid rgba(220,53,69,0.35); color:#ffb3bc; padding:12px; margin-bottom:16px; }
    .input-group-text{ background:#121212; border:1px solid #2a2a2a; color:#d4af37; border-radius:0; }
  </style>
</head>

<body>
<?php include('includes/header.php'); ?>

<div class="page-wrap">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-11 col-lg-8">
        <div class="register-card">
          <div class="register-header">
            <h1 class="register-title">Register</h1>
            <p style="color:#888;">Join us today</p>
            <hr class="section-divider">
          </div>

          <div class="register-body">
            <?php if($errMsg !== '') { ?>
              <div class="error-box"><i class="fa fa-triangle-exclamation"></i> <?php echo htmlentities($errMsg); ?></div>
            <?php } ?>

            <form method="post" autocomplete="off">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Full Name</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" class="form-control" name="fullname" placeholder="John Doe" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="form-control" name="gmail" placeholder="example@gmail.com" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Gender</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-venus-mars"></i></span>
                    <select class="form-select" name="gender" required>
                      <option value="">Select Gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
  <label class="form-label">Phone Number</label>
  <div class="input-group">
    <span class="input-group-text"><i class="fa fa-phone"></i></span>
    <input type="text" 
           class="form-control" 
           name="phone_num" 
           placeholder="0123456789" 
           maxlength="15" 
           pattern="\d*" 
           title="Please enter only digits, maximum 15 characters"
           required>
  </div>
</div>

                <div class="col-md-6">
  <label class="form-label">Password</label>
  <div class="input-group">
    <span class="input-group-text"><i class="fa fa-lock"></i></span>
    <input type="password" 
           class="form-control" 
           name="password" 
           minlength="8" 
           pattern="^\S{8,}$" 
           title="At least 8 characters with no spaces"
           required>
  </div>
</div>

                <div class="col-md-6">
                  <label class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control" name="confirm_password" required>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label">Address</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-location-dot"></i></span>
                    <input type="text" class="form-control" name="address" placeholder="Full Home Address" required>
                  </div>
                </div>

                <div class="col-12 mt-4">
                  <button type="submit" name="register" class="btn-gold">Create Account</button>
                </div>
              </div>

              <div class="text-center mt-3" style="color:#aaa;">
                Already have an account? <a style="color:#d4af37; text-decoration:none;" href="login.php">Login here</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if($registerSuccess) { ?>
<script>
  Swal.fire({
      icon: 'success',
      title: 'Registration Successful',
      text: 'Welcome <?php echo addslashes($successName); ?>! Please login.',
      timer: 2500,
      showConfirmButton: false
  }).then(() => {
      window.location.href = 'login.php';
  });
</script>
<?php } ?>

</body>
</html>