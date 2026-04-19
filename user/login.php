<?php
session_start();
include('includes/config.php');
error_reporting(0);

// If already logged in, redirect to store home
if (isset($_SESSION['login']) && strlen($_SESSION['login']) > 0) {
    header('Location: index.php');
    exit;
}

$errMsg = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['gmail'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errMsg = "Please enter both email and password.";
    } else {
        // Querying using your custom table 'tbluser' and column 'gmail'
        $sql = "SELECT gmail, password, fullname FROM tbluser WHERE gmail = :email LIMIT 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetch(PDO::FETCH_OBJ);

        if ($query->rowCount() > 0) {
            // Verify the hashed password
            if (password_verify($password, $results->password)) {
                $_SESSION['login'] = $results->gmail;
                $_SESSION['fname'] = $results->fullname;
                
                echo "<script>window.location.href='index.php'</script>";
            } else {
                $errMsg = "Invalid password. Please try again.";
            }
        } else {
            $errMsg = "Account not found. Please register first.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - My PC Store</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #0f0f0f; /* Dark tech theme */
      color: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .login-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-card {
      background: #181818;
      border: 1px solid #2a2a2a;
      width: 100%;
      max-width: 450px;
      padding: 40px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    }

    .store-logo {
      text-align: center;
      margin-bottom: 30px;
    }

    .store-logo i {
      font-size: 3rem;
      color: #d4af37;
      margin-bottom: 10px;
    }

    .store-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.8rem;
      color: #fff;
      margin: 0;
    }

    .form-label {
      color: #aaa;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .form-control {
      background: #121212;
      border: 1px solid #2a2a2a;
      color: #fff;
      border-radius: 0;
      padding: 12px;
    }

    .form-control:focus {
      background: #121212;
      border-color: #d4af37;
      color: #fff;
      box-shadow: none;
    }

    .btn-login {
      background: linear-gradient(45deg, #d4af37, #c5a028);
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 2px;
      width: 100%;
      padding: 12px;
      text-transform: uppercase;
      margin-top: 20px;
      transition: 0.3s;
    }

    .btn-login:hover {
      background: #fff;
      transform: translateY(-2px);
    }

    .error-msg {
      background: rgba(220,53,69,0.1);
      border: 1px solid #dc3545;
      color: #ffb3bc;
      padding: 10px;
      font-size: 0.9rem;
      margin-bottom: 20px;
      text-align: center;
    }

    .reg-link {
      color: #d4af37;
      text-decoration: none;
    }

    .reg-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="login-wrap">
  <div class="login-card">
    <div class="store-logo">
      <i class="fa fa-laptop-code"></i>
      <h1 class="store-title">My PC Store</h1>
      <p style="color:#666; font-size: 0.9rem;">Premium Hardware & Custom Builds</p>
    </div>

    <?php if($errMsg !== '') { ?>
      <div class="error-msg">
        <i class="fa fa-exclamation-circle"></i> <?php echo htmlentities($errMsg); ?>
      </div>
    <?php } ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Gmail Address</label>
        <input type="email" name="gmail" class="form-control" placeholder="name@gmail.com" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" name="login" class="btn-login">Sign In</button>
    </form>

    <div class="text-center mt-4" style="color:#888; font-size:0.9rem;">
      Don't have an account? <br>
      <a href="register.php" class="reg-link">Register as a New Member</a>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html>