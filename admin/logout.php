<?php
session_start(); 

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        body {
            /* 必须和 Login 页面一样的背景 */
            background: linear-gradient(rgba(10, 10, 15, 0.8), rgba(10, 10, 15, 0.9)), url('https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            overflow: hidden; 
            margin: 0;
        }
    </style>
</head>
<body>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        Swal.fire({
            icon: 'success',
            title: 'Logged Out',
            text: 'You have been logged out successfully. See you soon!',
            timer: 1500,              
            showConfirmButton: false, 
            background: '#fff',
            heightAuto: false         
        }).then(() => {
            // 跳转回登录页
            window.location = 'index.php'; 
        });
    </script>

</body>
</html>