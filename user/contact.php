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

  <style>
    /* ===== SAME AS INDEX THEME ===== */
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #0f0f0f;
      color: #fff;
    }

    /* ===== HERO ===== */
    .page-hero {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)),
        url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?q=80&w=2070&auto=format&fit=crop');
      background-size: cover;
      background-position: center;
      height: 90vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      position: relative;
    }

    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: 4rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      text-shadow: 0 10px 30px rgba(0,0,0,0.8);
      margin-bottom: 15px;
      animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
    }

    .hero-subtitle {
      font-size: 1rem;
      color: #d4af37;
      letter-spacing: 3px;
      text-transform: uppercase;
      font-weight: 500;
      margin: 0;
      animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
    }

    .section-divider {
      width: 60px;
      height: 2px;
      background: #d4af37;
      margin: 20px auto;
      border: none;
    }

    /* ===== SECTION HEADERS ===== */
    .section-header {
      text-align: center;
      margin-bottom: 40px;
    }
    .section-header h2 {
      font-family: 'Playfair Display', serif;
      color: #fff;
      font-size: 2.5rem;
      margin-bottom: 8px;
    }
    .section-header p {
      color: #888;
      font-size: 0.9rem;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 0;
    }

    /* ===== CARDS (same vibe as index car-card) ===== */
    .dark-card {
      background: #181818;
      border: 1px solid #2a2a2a;
      border-radius: 0;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      transition: all 0.4s ease;
      height: 100%;
    }
    .dark-card:hover {
      transform: translateY(-10px);
      border-color: #444;
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }

    .card-icon {
      width: 46px;
      height: 46px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #2a2a2a;
      color: #d4af37;
      margin-bottom: 14px;
      font-size: 1.1rem;
    }

    .card-label {
      color: #aaa;
      font-size: 0.85rem;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .card-value {
      color: #fff;
      font-weight: 600;
      margin: 0;
      word-break: break-word;
    }

    /* ===== LINKS ===== */
    a.link-gold {
      color: #d4af37;
      text-decoration: none;
    }
    a.link-gold:hover {
      color: #fff;
      text-decoration: underline;
    }

    /* ===== FORM ===== */
    .form-control, .form-select {
      background: #121212;
      border: 1px solid #2a2a2a;
      color: #fff;
      border-radius: 0;
      padding: 12px 14px;
    }
    .form-control:focus, .form-select:focus {
      background: #121212;
      color: #fff;
      border-color: #d4af37;
      box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.15);
    }
    .form-label {
      color: #aaa;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 0.85rem;
      margin-bottom: 8px;
    }

    .btn-gold {
      background: linear-gradient(45deg, #d4af37, #c5a028);
      color: #000;
      padding: 12px 26px;
      font-weight: bold;
      border-radius: 2px;
      border: none;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: 0.3s;
      width: 100%;
    }
    .btn-gold:hover {
      background: #fff;
      color: #000;
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
    }

    /* ===== MAP ===== */
    .map-frame {
      width: 100%;
      height: 360px;
      border: 1px solid #2a2a2a;
      border-radius: 0;
      filter: grayscale(15%) brightness(0.9);
    }

    .quick-list li {
      color: #ddd;
      padding: 10px 0;
      border-bottom: 1px solid #2a2a2a;
      display: flex;
      gap: 10px;
      align-items: flex-start;
    }
    .quick-list i { color: #d4af37; margin-top: 3px; }
  
    @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
  </style>
  
</head>

<body>
<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="page-hero">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
