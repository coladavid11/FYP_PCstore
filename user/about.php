<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About Us - My PC STORE</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="newstyle.css">

<style>
.hero-title {
    font-weight:700;
    color:#fff;
}

.hero-subtitle {
    color:#ddd;
    max-width:700px;
    margin:auto;
}

.section-title {
    color:white;
    font-weight:700;
}

.text-soft {
    color:#aaa;
}

.dark-card {
    background:#121212;
    border:1px solid #2a2a2a;
    border-radius:12px;
    padding:20px;
}

.icon-badge {
    width:40px;
    height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#1a1a1a;
    border:1px solid #333;
    border-radius:10px;
    color:#d4af37;
}

.stat-card {
    background:#121212;
    border:1px solid #2a2a2a;
    padding:25px;
    border-radius:12px;
}

.stat-number {
    font-size:2rem;
    font-weight:700;
    color:#d4af37;
}

.cta-btn {
    background:#d4af37;
    color:#000;
    padding:10px 20px;
    border:none;
    border-radius:8px;
    margin:5px;
    text-decoration:none;
}

.cta-outline {
    border:1px solid #d4af37;
    color:#d4af37;
    padding:10px 20px;
    border-radius:8px;
    margin:5px;
    text-decoration:none;
}
</style>
</head>

<body>
  <?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="hero d-flex align-items-center text-center"
style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.9)), url('../image/products/about-hero.jpg')">

    <div class="container">
        <h1 class="hero-title">MY PC STORE</h1>

        <p class="hero-subtitle">
            Build. Upgrade. Dominate.  
            <span style="color:#d4af37;font-weight:600;">
                Your Ultimate PC Performance Hub.
            </span>
        </p>

        <hr style="border-color:#333;">

        <p class="text-soft">
            Premium gaming PCs, components, and custom builds for creators and gamers.
        </p>

    </div>
</section>

<!-- WHO WE ARE -->
<section class="py-5 bg-dark text-white">

<div class="container">

<div class="row align-items-center g-4">

    <div class="col-lg-7">

        <h2 class="section-title">Who We Are</h2>

        <p class="text-soft">
            My PC STORE is a student-developed e-commerce platform focused on delivering
            high-performance PC components, gaming accessories, and custom builds.
            We aim to simplify the PC buying experience with a clean, fast, and modern system.
        </p>

        <div class="mt-4">

            <div class="d-flex gap-3 mb-3">
                <div class="icon-badge"><i class="fa fa-microchip"></i></div>
                <div>
                    <strong>Performance Focus</strong><br>
                    <small class="text-soft">Optimized hardware selection</small>
                </div>
            </div>

            <div class="d-flex gap-3 mb-3">
                <div class="icon-badge"><i class="fa fa-gamepad"></i></div>
                <div>
                    <strong>Gaming Ready</strong><br>
                    <small class="text-soft">Built for AAA performance</small>
                </div>
            </div>

            <div class="d-flex gap-3">
                <div class="icon-badge"><i class="fa fa-code"></i></div>
                <div>
                    <strong>Student Project System</strong><br>
                    <small class="text-soft">Built for FYP demonstration</small>
                </div>
            </div>

        </div>

    </div>

    <div class="col-lg-5">

        <div class="dark-card">

            <h5 class="text-warning mb-3">What We Sell</h5>

            <ul class="list-unstyled text-soft">

                <li>✔ Gaming PCs</li>
                <li>✔ GPUs (RTX / RX Series)</li>
                <li>✔ Monitors</li>
                <li>✔ Keyboards & Mice</li>
                <li>✔ Custom PC Builds</li>

            </ul>

        </div>

    </div>

</div>

</div>
</section>

<!-- WHY CHOOSE -->
<section class="py-5">

<div class="container text-center">

<h2 class="section-title text-white">Why Choose Us</h2>
<p class="text-soft">Designed for gamers, built for performance.</p>

<div class="row g-4 mt-4">

    <div class="col-md-4">
        <div class="stat-card">
            <i class="fa fa-bolt text-warning mb-2"></i>
            <h5>High Performance</h5>
            <p class="text-soft">Only selected quality components</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <i class="fa fa-shield text-warning mb-2"></i>
            <h5>Reliable System</h5>
            <p class="text-soft">Stable and tested configurations</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <i class="fa fa-user-gear text-warning mb-2"></i>
            <h5>Easy Customization</h5>
            <p class="text-soft">Build your own dream PC</p>
        </div>
    </div>

</div>

</div>
</section>

<!-- STATS -->
<section class="py-5 bg-dark text-white">

<div class="container text-center">

<h2 class="section-title">Quick Stats</h2>

<div class="row g-4 mt-3">

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" id="s1">0</div>
            <div class="text-soft">Products</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" id="s2">0</div>
            <div class="text-soft">Categories</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number" id="s3">0</div>
            <div class="text-soft">Users</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number">MY</div>
            <div class="text-soft">Location</div>
        </div>
    </div>

</div>

</div>
</section>

<!-- CTA -->
<section class="py-5 text-center">

<h2 class="text-white">Start Building Your PC Today</h2>
<p class="text-soft">Explore our latest components and custom builds.</p>

<a href="product.php" class="cta-btn">Shop Now</a>
<a href="contact.php" class="cta-outline">Contact Support</a>

</section>

<script>
// simple animated counter
function count(id, target){
    let el = document.getElementById(id);
    let i = 0;
    let interval = setInterval(()=>{
        i++;
        el.innerText = i;
        if(i >= target) clearInterval(interval);
    }, 30);
}

count("s1", 120);
count("s2", 12);
count("s3", 300);
</script>

<?php include('includes/footer.php'); ?>

</body>
</html>