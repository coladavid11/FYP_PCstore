<?php
session_start();
include('includes/config.php');
error_reporting(0);

// --- 1. Smart Redirect Logic ---
$redirect = 'index.php'; // Default fallback

// A. Check POST data (Highest priority - from form submission)
if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
    $redirect = $_POST['redirect'];
} 
// B. Check URL parameter (e.g. login.php?redirect=page.php)
elseif (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
} 
// C. Check HTTP Referer (Auto-detect previous page)
elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    // Prevent redirect loops (don't redirect back to login or register page)
    if (strpos($referer, 'login.php') === false && strpos($referer, 'register.php') === false) {
        $redirect = $referer;
    }
}

// Security: Basic check to prevent malicious external redirects (optional but recommended)
// This ensures we stick to relative paths or our own domain usually, but simplified here.
if (empty($redirect)) {
    $redirect = 'index.php';
}

// If already logged in, redirect immediately
if (isset($_SESSION['login']) && strlen($_SESSION['login']) > 0) {
    header('Location: ' . $redirect);
    exit;
}

$errMsg = '';
$loginSuccess = false;
$welcomeName = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errMsg = "Please enter your email and password.";
    } else {
        // Fetch user by email
        $sql = "SELECT FullName, EmailId, Password FROM tblusers WHERE EmailId = :email LIMIT 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();

        if ($query->rowCount() == 1) {
            $user = $query->fetch(PDO::FETCH_OBJ);

            // Verify hashed password
            if (password_verify($password, $user->Password)) {
                $_SESSION['login'] = $user->EmailId;
                $_SESSION['fname'] = $user->FullName;

                $loginSuccess = true;
                $welcomeName = $user->FullName;
            } else {
                $errMsg = "Invalid email or password.";
            }
        } else {
            $errMsg = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Buat Kerja Betul2 Car Rental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body{ font-family:'Poppins',sans-serif; background:#0f0f0f; color:#fff; }
    .page-wrap{ min-height: 70vh; display:flex; align-items:center; padding: 40px 0; }
    .login-card{ background:#181818; border:1px solid #2a2a2a; border-radius:0; box-shadow:0 10px 30px rgba(0,0,0,0.3); overflow:hidden; }
    .login-header{ padding: 28px 28px 0 28px; text-align:center; }
    .login-title{ font-family:'Playfair Display',serif; font-size:2.2rem; margin-bottom:8px; }
    .login-subtitle{ color:#888; font-size:0.9rem; letter-spacing:1px; text-transform:uppercase; margin-bottom: 18px; }
    .section-divider{ width:60px; height:2px; background:#d4af37; margin: 0 auto 22px auto; border:none; }
    .login-body{ padding: 0 28px 28px 28px; }
    .form-label{ color:#aaa; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem; margin-bottom:8px; }
    .form-control{ background:#121212; border:1px solid #2a2a2a; color:#fff; border-radius:0; padding:12px 14px; }
    .form-control:focus{ background:#121212; color:#fff; border-color:#d4af37; box-shadow:0 0 0 0.2rem rgba(212,175,55,0.15); }
    .btn-gold{ background: linear-gradient(45deg, #d4af37, #c5a028); color:#000; padding:12px 26px; font-weight:bold; border-radius:2px; border:none; text-transform:uppercase; letter-spacing:1px; transition:0.3s; width:100%; }
    .btn-gold:hover{ background:#fff; color:#000; transform: translateY(-2px); box-shadow:0 10px 20px rgba(212,175,55,0.3); }
    .link-gold{ color:#d4af37; text-decoration:none; }
    .link-gold:hover{ color:#fff; text-decoration:underline; }
    .error-box{ background: rgba(220,53,69,0.12); border:1px solid rgba(220,53,69,0.35); color:#ffb3bc; padding:12px 14px; margin-bottom:16px; font-size:0.95rem; }
    .input-group-text{ background:#121212; border:1px solid #2a2a2a; color:#d4af37; border-radius:0; }
  </style>
</head>

<body>
<?php include('includes/header.php'); ?>

<div class="page-wrap">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-6 col-xl-5">
        <div class="login-card">
          <div class="login-header">
            <h1 class="login-title">Login</h1>
            <p class="login-subtitle">Login to manage your booking</p>
            <hr class="section-divider">
          </div>

          <div class="login-body">
            <?php if($errMsg !== '') { ?>
              <div class="error-box">
                <i class="fa fa-triangle-exclamation"></i> <?php echo htmlentities($errMsg); ?>
              </div>
            <?php } ?>

            <form method="post" autocomplete="off">
              <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

              <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                  <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa fa-lock"></i></span>
                  <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>
              </div>

              <button type="submit" name="login" class="btn-gold">Login</button>

              <div class="text-center mt-3" style="color:#aaa;">
                Don't have an account?
                <a class="link-gold" href="register.php">Register a new account</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>

<?php if($loginSuccess) { ?>
<script>
  Swal.fire({
      icon: 'success',
      title: 'Login Successful',
      text: 'Welcome back, <?php echo addslashes($welcomeName); ?>!',
      timer: 1500,
      showConfirmButton: false,
      heightAuto: false,
      background: '#181818',
      color: '#fff',
      confirmButtonColor: '#d4af37'
  }).then(() => {
      // Redirect to the stored page
      window.location.href = '<?php echo addslashes($redirect); ?>';
  });
</script>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>