<?php
session_start();
include('includes/config.php');
error_reporting(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us - Buat Kerja Betul2 Car Rental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Fonts (match index) -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <!-- External CSS file -->
  <link rel="stylesheet" href="newstyle.css">
  
</head>

<body>
<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="hero d-flex align-items-center text-center"
style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.9)), url('https://storage-asset.msi.com/event/2022/cnd/i-want-it-all/images/reason-img-02.jpg')">
  <div class="container">
    <h1 class="hero-title">Contact Us</h1>
    <p class="hero-subtitle">We are here to help you with your car rental needs</p>
    <hr class="section-divider">
  </div>
</section>

<!-- CONTENT -->
<section class="py-5">
  <div class="container">
    <div class="section-header">
      <h2></h2>
      <p></p>
    </div>

    <!-- CONTACT INFO CARDS -->
    <div class="row g-4 mb-4">
      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fa fa-location-dot"></i></div>
          <div class="card-label">Address</div>
          <p class="card-value mb-0">Melaka, Malaysia</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fa fa-phone"></i></div>
          <div class="card-label">Phone</div>
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
            <a class="link-gold" href="https://wa.me/601123366716" target="_blank" rel="noopener">
              Chat with us
            </a>
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="dark-card p-4">
          <div class="card-icon"><i class="fa fa-envelope"></i></div>
          <div class="card-label">Email</div>
          <p class="card-value mb-0">
            <a class="link-gold" href="mailto:LIU.JIUN.LE@student.mmu.edu.my">LIU.JIUN.LE@student.mmu.edu.my</a>
          </p>
        </div>
      </div>
    </div>

    <!-- OPERATING HOURS + QUICK INFO -->
    <div class="row g-4">
      <!-- OPERATING HOURS (LEFT) -->
      <div class="col-lg-7">
        <div class="dark-card p-4 p-md-5 h-100">
          <h3 class="mb-3" style="font-family:'Playfair Display', serif;">
            Operating Hours
          </h3>

          <p style="color:#888;">
            You may contact us during our operating hours via phone or WhatsApp.
          </p>

          <div class="d-flex justify-content-between border-bottom"
              style="border-color:#2a2a2a !important; padding:14px 0;">
            <span style="color:#aaa; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem;">
              Weekday
            </span>
            <span style="font-weight:600;">
              8:00 AM – 5:00 PM
            </span>
          </div>

          <div class="d-flex justify-content-between"
              style="padding:14px 0;">
            <span style="color:#aaa; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem;">
              Weekend
            </span>
            <span style="font-weight:600;">
              8:00 AM – 2:00 PM
            </span>
          </div>

          <hr style="border-color:#2a2a2a; margin:25px 0;">

          <p class="mb-2" style="color:#aaa; text-transform:uppercase; letter-spacing:1px; font-size:0.85rem;">
            Preferred Contact
          </p>

          <p class="mb-2">
            <i class="fab fa-whatsapp" style="color:#d4af37;"></i>
            <a class="link-gold" href="https://wa.me/601123366716" target="_blank" rel="noopener">
              WhatsApp Us
            </a>
          </p>

          <p class="mb-2">
            <i class="fa fa-phone" style="color:#d4af37;"></i>
            <a class="link-gold" href="tel:+601123366716">
              011-23366716
            </a>
          </p>

          <p class="mb-0">
            <i class="fa fa-envelope" style="color:#d4af37;"></i>
            <a class="link-gold" href="mailto:LIU.JIUN.LE@student.mmu.edu.my">
              LIU.JIUN.LE@student.mmu.edu.my
            </a>
          </p>
        </div>
      </div>

      <!-- QUICK INFO (RIGHT) -->
      <div class="col-lg-5">
        <div class="dark-card p-4 p-md-5 h-100">
          <h3 class="mb-3" style="font-family:'Playfair Display', serif;">
            Quick Info
          </h3>

          <ul class="list-unstyled quick-list mb-0">
            <li><i class="fa fa-bolt"></i> Fast response</li>
            <li><i class="fa fa-face-smile"></i> Friendly support</li>
            <li><i class="fa fa-calendar-check"></i> Easy booking enquiry</li>
            <li><i class="fa fa-briefcase"></i> Business / corporate rental</li>
            <li><i class="fa fa-graduation-cap"></i> Student friendly</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- MAP -->
    <div class="mt-5">
      <div class="section-header">
        <h2>Our Location</h2>
        <p>MMU Melaka</p>
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
