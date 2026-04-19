<?php 
session_start();
include('includes/config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Home - My PC Store</title>

<!-- ONLY LOAD ONCE (same as about.php) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #0f0f0f;
    color: #fff;
}

/* HERO ONLY */
.hero {
    height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;

    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)),
    url('https://www.pcworld.com/wp-content/uploads/2025/04/pcw08_Asus-Gaming-PC.jpg') center/cover;
}

.hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    font-weight: 700;
}

.hero p {
    color: #d4af37;
    letter-spacing: 2px;
}

.btn-gold {
    background: #d4af37;
    color: #000;
    padding: 12px 28px;
    text-decoration: none;
    font-weight: bold;
}
.btn-gold:hover {
    background: #fff;
}
</style>
</head>

<body>

<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="hero">
    <div>
        <h1>Build Your Dream PC</h1>
        <p>Premium Hardware • High Performance</p>
        <br>
        <a href="full-set.php" class="btn-gold">Shop Now</a>
    </div>
</section>

<!-- SIMPLE SECTION -->
<section class="py-5 text-center">
    <div class="container">
        <h2>Welcome to My PC Store</h2>
        <p class="text-secondary">Find the best PC parts for your build</p>
    </div>
</section>

<?php include('includes/footer.php'); ?>

</body>
</html>