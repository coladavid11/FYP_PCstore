<?php
session_start();
include('includes/config.php');

if(!isset($_SESSION['admin_login'])) {
    header("location:admin_login.php"); 
    exit();
}

$email = $_SESSION['admin_login'];

$sql = "SELECT * FROM admin WHERE email = :email";
$query = $dbh->prepare($sql);
$query->bindParam(':email', $email, PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_OBJ);

if(!$result) {
    echo "Admin details not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Profile | My PC Store</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

/* =========================
   RESET
========================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/* =========================
   BODY
========================= */

body{
    font-family:'Poppins', sans-serif;
    background:#f5f5f5;

    height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;
}

/* =========================
   PROFILE CONTAINER
========================= */

.profile-container{
    position:relative;

    background:#fff;

    width:100%;
    max-width:550px;

    padding:40px;

    border-radius:5px;

    border-left:5px solid #ccac3d;

    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

/* =========================
   TITLE
========================= */

.profile-title{
    font-size:2rem;
    font-weight:600;

    color:#111;

    margin-bottom:30px;
}

/* =========================
   PROFILE INFO
========================= */

.profile-info p{
    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:15px 0;

    border-bottom:1px solid #eee;
}

.profile-info strong{
    color:#ccac3d;
    font-size:0.95rem;
}

.profile-info span{
    color:#555;
}

/* =========================
   EDIT BUTTON
========================= */

.btn-box{
    margin-top:30px;
}

.edit-btn{
    display:block;

    width:100%;

    text-align:center;

    background:#ccac3d;
    color:#fff;

    padding:13px;

    text-decoration:none;
    font-weight:600;

    border-radius:4px;

    transition:0.3s;
}

.edit-btn:hover{
    background:#000;
}

/* =========================
   CLOSE BUTTON
========================= */

.close-btn{
    position:absolute;

    top:20px;
    right:20px;

    width:42px;
    height:42px;

    border-radius:50%;

    background:#fff;
    color:#777;

    display:flex;
    justify-content:center;
    align-items:center;

    text-decoration:none;

    font-size:1.5rem;
    font-weight:bold;

    box-shadow:0 4px 12px rgba(0,0,0,0.1);

    transition:0.3s;
}

.close-btn:hover{
    background:#f3f3f3;
    color:#000;

    transform:scale(1.05);
}

</style>
</head>

<body>

<div class="profile-container">

    <!-- CLOSE BUTTON -->
    <a href="dashboard.php" class="close-btn">
        &times;
    </a>

    <!-- TITLE -->
    <h1 class="profile-title">
        Admin Profile
    </h1>

    <!-- PROFILE INFO -->
    <div class="profile-info">

        <p>
            <strong>Name</strong>
            <span>
                <?php echo htmlspecialchars($result->fullname); ?>
            </span>
        </p>

        <p>
            <strong>Email</strong>
            <span>
                <?php echo htmlspecialchars($result->email); ?>
            </span>
        </p>

        <p>
            <strong>Role</strong>
            <span>
                <?php echo htmlspecialchars($result->role); ?>
            </span>
        </p>

    </div>

    <!-- EDIT BUTTON -->
    <div class="btn-box">

        <a href="edit_profile.php" class="edit-btn">
            Edit Profile
        </a>

    </div>

</div>

</body>
</html>
`