<?php
session_start();
include('includes/config.php');
error_reporting(0);

// If already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errMsg = '';
$loginSuccess = false; // 🔥 flag

if (isset($_POST['login'])) {
    $email = trim($_POST['gmail'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errMsg = "Please enter both email and password.";
    } else {

        $sql = "SELECT user_id, gmail, password, fullname FROM tbluser WHERE gmail = :email LIMIT 1";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetch(PDO::FETCH_OBJ);

        if ($query->rowCount() > 0) {

            if (password_verify($password, $results->password)) {
                $_SESSION['login'] = $results->gmail;
                $_SESSION['fname'] = $results->fullname;
                $_SESSION['user_id'] = $results->user_id;

                $loginSuccess = true; // 🔥 只设 flag
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
    background: #0f0f0f;
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
}

.login-card {
    background: #181818;
    border: 1px solid #2a2a2a;
    max-width: 450px;
    width: 100%;
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
}

.store-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
}

.form-control {
    background: #121212;
    border: 1px solid #2a2a2a;
    color: #fff;
}

.form-control:focus {
    border-color: #d4af37;
    box-shadow: none;
}

.btn-login {
    background: linear-gradient(45deg, #d4af37, #c5a028);
    color: #000;
    width: 100%;
    padding: 12px;
    border: none;
    margin-top: 20px;
}

.error-msg {
    background: rgba(220,53,69,0.1);
    border: 1px solid #dc3545;
    color: #ffb3bc;
    padding: 10px;
    margin-bottom: 20px;
    text-align: center;
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
</div>

<?php if($errMsg !== '') { ?>
<div class="error-msg">
    <?php echo htmlentities($errMsg); ?>
</div>
<?php } ?>

<form method="post">
    <input type="email" name="gmail" class="form-control mb-3" placeholder="Email" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button type="submit" name="login" class="btn-login">Sign In</button>
</form>
<div>
    <p class="text-center mt-3">Don't have an account? <a href="register.php" style="color:#d4af37;">Register here</a></p>
    </div>
</div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- login successful animation -->
<?php if($loginSuccess) { ?>
<script>
Swal.fire({
    title: 'Welcome Back!',
    text: 'Login successful',
    icon: 'success',
    timer: 1500,
    showConfirmButton: false,
    background: '#1a1a1a',
    color: '#fff',
    iconColor: '#1fd719'
}).then(() => {
    window.location.href = 'index.php';
});
</script>
<?php } ?>

</body>
</html>