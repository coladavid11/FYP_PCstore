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
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $cpass    = trim($_POST['confirm_password'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    
    // 🔥 New Malaysian Fields
    $icno        = trim($_POST['icno'] ?? '');
    $licenseno   = trim($_POST['licenseno'] ?? '');
    $licenseexp  = trim($_POST['licenseexp'] ?? '');
    
    $address  = trim($_POST['address'] ?? '');
    $city     = trim($_POST['city'] ?? '');

    // Basic validation
    if ($fullname === '' || $email === '' || $password === '' || $cpass === '' || $contact === '' || $address === '' || $city === '' || $icno === '' || $licenseno === '' || $licenseexp === '') {
        $errMsg = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errMsg = "Please enter a valid email address.";
    } elseif ($password !== $cpass) {
        $errMsg = "Password and Confirm Password do not match.";
    } elseif (!preg_match('/^[0-9]{9,11}$/', $contact)) {
        $errMsg = "Please enter a valid phone number (digits only).";
    } elseif (!preg_match('/^[0-9]{12}$/', $icno)) {
        // 🔥 Malaysia IC Validation (12 Digits)
        $errMsg = "Invalid MyKad (IC) Number. Please enter 12 digits without dashes (-).";
    } elseif ($licenseexp < date('Y-m-d')) {
        // 🔥 License Expiry Check
        $errMsg = "Your Driving License has expired. You cannot register.";
    } else {

        // Check if email already exists
        $checkSql = "SELECT id FROM tblusers WHERE EmailId = :email LIMIT 1";
        $checkQuery = $dbh->prepare($checkSql);
        $checkQuery->bindParam(':email', $email, PDO::PARAM_STR);
        $checkQuery->execute();

        if ($checkQuery->rowCount() > 0) {
            $errMsg = "This email is already registered.";
        } else {

            // Hash password (bcrypt)
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user with IC and License info
            $sql = "INSERT INTO tblusers(FullName, EmailId, Password, ContactNo, IcNo, LicenseNo, LicenseExpDate, Address, City)
                    VALUES(:fullname, :email, :password, :contact, :icno, :licenseno, :licenseexp, :address, :city)";
            $query = $dbh->prepare($sql);
            $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $query->bindParam(':contact', $contact, PDO::PARAM_STR);
            // Binding new fields
            $query->bindParam(':icno', $icno, PDO::PARAM_STR);
            $query->bindParam(':licenseno', $licenseno, PDO::PARAM_STR);
            $query->bindParam(':licenseexp', $licenseexp, PDO::PARAM_STR);
            
            $query->bindParam(':address', $address, PDO::PARAM_STR);
            $query->bindParam(':city', $city, PDO::PARAM_STR);

            $query->execute();
            $lastInsertId = $dbh->lastInsertId();

            if ($lastInsertId) {
                // Trigger SweetAlert AFTER page loads
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
  <title>Register - Buat Kerja Betul2 Car Rental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body{
      font-family:'Poppins',sans-serif;
      background:#0f0f0f;
      color:#fff;
    }

    .page-wrap{
      min-height: 75vh;
      display:flex;
      align-items:center;
      padding: 40px 0;
    }

    .register-card{
      background:#181818;
      border:1px solid #2a2a2a;
      border-radius:0;
      box-shadow:0 10px 30px rgba(0,0,0,0.3);
      overflow:hidden;
    }

    .register-header{
      padding: 28px 28px 0 28px;
      text-align:center;
    }

    .register-title{
      font-family:'Playfair Display',serif;
      font-size:2.2rem;
      margin-bottom:8px;
    }

    .register-subtitle{
      color:#888;
      font-size:0.9rem;
      letter-spacing:1px;
      text-transform:uppercase;
      margin-bottom: 18px;
    }

    .section-divider{
      width:60px;
      height:2px;
      background:#d4af37;
      margin: 0 auto 22px auto;
      border:none;
    }

    .register-body{
      padding: 0 28px 28px 28px;
    }

    .form-label{
      color:#aaa;
      text-transform:uppercase;
      letter-spacing:1px;
      font-size:0.85rem;
      margin-bottom:8px;
    }

    .form-control{
      background:#121212;
      border:1px solid #2a2a2a;
      color:#fff;
      border-radius:0;
      padding:12px 14px;
    }

    .form-control:focus{
      background:#121212;
      color:#fff;
      border-color:#d4af37;
      box-shadow:0 0 0 0.2rem rgba(212,175,55,0.15);
    }
    
    /* Auto-fill fix for dark theme */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active{
        -webkit-box-shadow: 0 0 0 30px #121212 inset !important;
        -webkit-text-fill-color: white !important;
    }

    .btn-gold{
      background: linear-gradient(45deg, #d4af37, #c5a028);
      color:#000;
      padding:12px 26px;
      font-weight:bold;
      border-radius:2px;
      border:none;
      text-transform:uppercase;
      letter-spacing:1px;
      transition:0.3s;
      width:100%;
    }
    .btn-gold:hover{
      background:#fff;
      color:#000;
      transform: translateY(-2px);
      box-shadow:0 10px 20px rgba(212,175,55,0.3);
    }

    .link-gold{
      color:#d4af37;
      text-decoration:none;
    }
    .link-gold:hover{
      color:#fff;
      text-decoration:underline;
    }

    .error-box{
      background: rgba(220,53,69,0.12);
      border:1px solid rgba(220,53,69,0.35);
      color:#ffb3bc;
      padding:12px 14px;
      margin-bottom:16px;
      font-size:0.95rem;
    }

    .input-group-text{
      background:#121212;
      border:1px solid #2a2a2a;
      color:#d4af37;
      border-radius:0;
    }
    
    .section-title-small {
        color: #d4af37;
        font-size: 1rem;
        margin-top: 15px;
        margin-bottom: 10px;
        border-bottom: 1px solid #333;
        padding-bottom: 5px;
        font-weight: 500;
    }
  </style>
</head>

<body>
<?php include('includes/header.php'); ?>

<div class="page-wrap">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-11 col-lg-8 col-xl-7">
        <div class="register-card">
          <div class="register-header">
            <h1 class="register-title">Register</h1>
            <p class="register-subtitle">Create an account to start booking cars</p>
            <hr class="section-divider">
          </div>

          <div class="register-body">
            <?php if($errMsg !== '') { ?>
              <div class="error-box">
                <i class="fa fa-triangle-exclamation"></i> <?php echo htmlentities($errMsg); ?>
              </div>
            <?php } ?>

            <form method="post" autocomplete="off">
              <div class="row g-3">
                
                <div class="col-12"><div class="section-title-small">Personal Information</div></div>
                
                <div class="col-md-6">
                  <label class="form-label">Full Name</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" class="form-control" name="fullname" placeholder="As per MyKad" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Contact Number</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="contact" placeholder="e.g. 0123456789" required>
                  </div>
                </div>

                <div class="col-12"><div class="section-title-small">Identity & License (Malaysia)</div></div>

                <div class="col-md-12">
                  <label class="form-label">MyKad No. (IC)</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                    <input type="text" class="form-control" name="icno" placeholder="e.g. 981225015566 (12 digits, no dash)" maxlength="12" required>
                  </div>
                  <div style="font-size:0.75rem; color:#666; margin-top:3px;">* For verification purposes only.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Driving License No.</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-car"></i></span>
                    <input type="text" class="form-control" name="licenseno" placeholder="License Number" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">License Expiry Date</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                    <input type="date" class="form-control" name="licenseexp" min="<?php echo date('Y-m-d'); ?>" required>
                  </div>
                </div>

                <div class="col-12"><div class="section-title-small">Security & Address</div></div>

                <div class="col-md-6">
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="Create password" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Repeat password" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">City</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-city"></i></span>
                    <input type="text" class="form-control" name="city" placeholder="e.g. Melaka" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Address</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-location-dot"></i></span>
                    <input type="text" class="form-control" name="address" placeholder="Your address" required>
                  </div>
                </div>

                <div class="col-12 mt-4">
                  <button type="submit" name="register" class="btn-gold">Create Account</button>
                </div>
              </div>

              <div class="text-center mt-3" style="color:#aaa;">
                Already have an account?
                <a class="link-gold" href="login.php">Login here</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>

<?php if($registerSuccess) { ?>
<script>
  Swal.fire({
      icon: 'success',
      title: 'Account Created',
      text: 'Hi <?php echo addslashes($successName); ?>, registration successful. Please login.',
      timer: 2000,
      showConfirmButton: false,
      heightAuto: false
  }).then(() => {
      window.location.href = 'login.php';
  });
</script>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>