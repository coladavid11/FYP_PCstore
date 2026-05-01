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

<!-- External CSS file -->
<link rel="stylesheet" href="newstyle.css">
</head>

<body>
<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="index-hero d-flex align-items-center text-center"
style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.9)), url('https://storage-asset.msi.com/event/2022/cnd/i-want-it-all/images/reason-img-02.jpg')">
    <div class="container">
        <h1 class="hero-title">Build Your Dream PC</h1>
        <p class="hero-subtitle">Premium Hardware • High Performance</p>
        <div class="mt-4">
            <a href="full-set.php" class="btn-cta">Shop Now</a>
            <a href="#" class="btn-cta-outline">View Builds</a>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="py-5">
    <div class="container text-center">
        <h2 class="section-title mb-3">Why Choose Us</h2>
        <p class="text-soft mb-5">Simple. Fast. Reliable PC solutions.</p>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-card p-4">
                    <i class="fa-solid fa-microchip icon-badge mb-3"></i>
                    <h5>Latest Components</h5>
                    <p class="text-soft">We provide the newest hardware in the market.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-card p-4">
                    <i class="fa-solid fa-bolt icon-badge mb-3"></i>
                    <h5>High Performance</h5>
                    <p class="text-soft">Optimized builds for gaming & productivity.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-card p-4">
                    <i class="fa-solid fa-headset icon-badge mb-3"></i>
                    <h5>Support</h5>
                    <p class="text-soft">Fast and reliable customer service.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PRODUCTS (SIMPLE SHOWCASE) -->
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="section-title text-dark mb-4">Featured Builds</h2>
        <p class="text-soft mb-5">Popular setups chosen by customers</p>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-card p-3">
                    <img src="https://www.pcworld.com/wp-content/uploads/2025/10/pcw07_GamingSetup_RGBeci.jpg?quality=50&strip=all" class="img-fluid mb-3">
                    <h5>Gaming Beast</h5>
                    <p class="text-soft">RTX Series • High FPS Gaming</p>
                    <a href="#" class="btn-cta mt-2">View</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-card p-3">
                    <img src="https://images.unsplash.com/photo-1593642634315-48f5414c3ad9" class="img-fluid mb-3">
                    <h5>Workstation Pro</h5>
                    <p class="text-soft">Editing • Rendering • Productivity</p>
                    <a href="#" class="btn-cta mt-2">View</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-card p-3">
                    <img src="https://images.unsplash.com/photo-1587831990711-23ca6441447b" class="img-fluid mb-3">
                    <h5>Budget Build</h5>
                    <p class="text-soft">Affordable • Reliable • Everyday Use</p>
                    <a href="#" class="btn-cta mt-2">View</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>