<?php
session_start();
include('includes/config.php');
error_reporting(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Support - My PC Store</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <!-- External CSS -->
  <link rel="stylesheet" href="newstyle.css">
</head>

<body>

<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="hero d-flex align-items-center text-center"
style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.9)),
url('../image/products/contact-us-hero.jpg')">

  <div class="container">
    <h1 class="hero-title">Contact Support</h1>
    <p class="hero-subtitle">
      Need help with your PC build, order, or product?  
      <span style="color:#f1c40f; font-weight:700;">We’re here to assist you</span>
    </p>
    <hr class="section-divider">
  </div>
</section>

<!-- CONTENT -->
<section class="py-5">
  <div class="container">

    <!-- CONTACT CARDS -->
    <div class="row g-4 mb-4">

      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fa fa-location-dot"></i></div>
          <div class="card-label">Location</div>
          <p class="card-value mb-0">Melaka, Malaysia</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fa fa-phone"></i></div>
          <div class="card-label">Hotline</div>
          <p class="card-value mb-0">
            <a class="link-gold" href="tel:+601123366716">011-23366716</a>
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fab fa-whatsapp"></i></div>
          <div class="card-label">WhatsApp</div>
          <p class="card-value mb-0">
            <a class="link-gold" href="https://wa.me/601123366716" target="_blank">
              Chat Support
            </a>
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fa fa-envelope"></i></div>
          <div class="card-label">Email</div>
          <p class="card-value mb-0">
            <a class="link-gold" href="mailto:LIU.JIUN.LE@student.mmu.edu.my">
              support@mypcstore.com
            </a>
          </p>
        </div>
      </div>

    </div>

    <!-- INFO SECTION -->
    <div class="row g-4">

      <!-- LEFT -->
      <div class="col-lg-7">
        <div class="dark-card p-4 p-md-5 h-100">

          <h3 class="mb-3" style="font-family:'Playfair Display', serif;">
            Support Hours
          </h3>

          <p style="color:#888;">
            Our technical and customer support team is available during the following hours.
          </p>

          <div class="d-flex justify-content-between border-bottom"
               style="border-color:#2a2a2a !important; padding:14px 0;">
            <span style="color:#aaa; text-transform:uppercase;">Weekday</span>
            <span style="font-weight:600;">9:00 AM – 6:00 PM</span>
          </div>

          <div class="d-flex justify-content-between"
               style="padding:14px 0;">
            <span style="color:#aaa; text-transform:uppercase;">Weekend</span>
            <span style="font-weight:600;">10:00 AM – 3:00 PM</span>
          </div>

          <hr style="border-color:#2a2a2a; margin:25px 0;">

          <p class="mb-2" style="color:#aaa; text-transform:uppercase;">
            Preferred Contact Method
          </p>

          <p class="mb-2">
            <i class="fab fa-whatsapp" style="color:#d4af37;"></i>
            <a class="link-gold" href="https://wa.me/601123366716">
              WhatsApp Support
            </a>
          </p>

          <p class="mb-2">
            <i class="fa fa-phone" style="color:#d4af37;"></i>
            <a class="link-gold" href="tel:+601123366716">
              Call Hotline
            </a>
          </p>

          <p class="mb-0">
            <i class="fa fa-envelope" style="color:#d4af37;"></i>
            <a class="link-gold" href="mailto:support@mypcstore.com">
              support@mypcstore.com
            </a>
          </p>

        </div>
      </div>

      <!-- RIGHT -->
      <div class="col-lg-5">
        <div class="dark-card p-4 p-md-5 h-100">

          <h3 class="mb-3" style="font-family:'Playfair Display', serif;">
            Quick Support
          </h3>

          <ul class="list-unstyled quick-list mb-0">

            <li><i class="fa fa-bolt"></i> Fast response within working hours</li>
            <li><i class="fa fa-face-smile"></i> Friendly technical support</li>
            <li><i class="fa fa-cart-shopping"></i> Order & checkout assistance</li>
            <li><i class="fa fa-screwdriver-wrench"></i> PC build guidance</li>
            <li><i class="fa fa-gamepad"></i> Gaming setup advice</li>

          </ul>

        </div>
      </div>

    </div>

    <!-- MAP -->
    <div class="mt-5">

      <div class="text-center mb-4">
        <h2 class="section-title text-white">Our Location</h2>
        <p class="text-soft">My PC Store HQ - Melaka</p>
        <hr class="section-divider">
      </div>

      <iframe
        class="map-frame"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps?q=MMU%20Melaka&output=embed">
      </iframe>

    </div>

  </div>
</section>

<?php include('includes/footer.php'); ?>

</body>
</html>