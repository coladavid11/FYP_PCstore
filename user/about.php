<?php
session_start();
// Start session so the header can check login status (show Login/Register or user dropdown)
include('includes/config.php');
// Load database connection settings (PDO), even if this page does not query database
error_reporting(0);
// Hide PHP warnings/notices for cleaner output (not recommended for production debugging)
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us - Buat Kerja Betul2 Car Rental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap is used for layout (grid), spacing, and responsive design -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome is used for icons (car, check mark, support, etc.) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* Keep the same theme as index.php to make the website look consistent */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #0f0f0f;
        color: #fff;
    }

    /* Hero section with background image + dark overlay for readability */
    .page-hero {
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)),
            url('https://images.unsplash.com/photo-1553440569-bcc63803a83d?q=80&w=2070&auto=format&fit=crop');
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

    /* Main hero title uses premium font and animation to look modern */
    .hero-title {
        font-family: 'Playfair Display', serif;
        font-size: 4.5rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 0 10px 30px rgba(0,0,0,0.8);
        margin-bottom: 15px;
        animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
    }

    /* Subtitle uses gold color to match brand accent style */
    .hero-subtitle {
        font-size: 1.2rem;
        margin-bottom: 0;
        color: #d4af37;
        letter-spacing: 3px;
        text-transform: uppercase;
        font-weight: 500;
        animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
    }

    /* Gold accent line is used as a visual divider (same idea as index page) */
    .accent-line {
        width: 60px;
        height: 2px;
        background: #d4af37;
        margin: 20px auto 0;
        border: none;
    }

    /* Section titles use the same premium font to keep branding consistent */
    .section-title {
        font-family: 'Playfair Display', serif;
        color: #fff;
        font-size: 2.5rem;
        font-weight: 700;
    }

    /* Softer text color is used for readability on dark background */
    .text-soft {
        color: #888;
        font-size: 0.95rem;
        letter-spacing: 0.2px;
    }

    /* Override Bootstrap bg-light to stay in dark theme */
    .bg-light {
        background-color: #0f0f0f !important;
    }

    /* Card style follows index car-card: dark background, border, shadow, hover effect */
    .info-card,
    .stat-card,
    .team-card {
        background: #181818;
        border: 1px solid #2a2a2a;
        border-radius: 0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        transition: all 0.4s ease;
        height: 100%;
    }

    /* Hover effect makes the UI feel interactive and premium */
    .info-card:hover,
    .stat-card:hover,
    .team-card:hover {
        transform: translateY(-10px);
        border-color: #444;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }

    /* Icon badge uses gold color to match brand accent */
    .icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid #2a2a2a;
        color: #d4af37;
        font-size: 1.1rem;
    }

    /* Stats number uses gold to attract attention */
    .stat-number {
        font-size: 2rem;
        font-weight: 900;
        color: #d4af37;
        letter-spacing: 1px;
    }

    /* Stats label uses uppercase to look like a data card */
    .stat-label {
        color: #aaa;
        font-size: 0.85rem;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* Team image hover effect improves visual quality */
    .team-img {
        height: 500px;
        object-fit: cover;
        width: 100%;
        transition: 0.6s;
        filter: brightness(0.9);
    }
    .team-card:hover .team-img {
        transform: scale(1.05);
        filter: brightness(1.05);
    }

    /* CTA buttons follow index button style (gold gradient + sharp edges) */
    .btn-cta {
        background: linear-gradient(45deg, #d4af37, #c5a028);
        color: #000;
        padding: 15px 40px;
        font-weight: bold;
        border-radius: 2px;
        text-decoration: none;
        transition: 0.3s;
        border: none;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
    }
    .btn-cta:hover {
        background: #fff;
        color: #000;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
    }

    /* Outline button style matches the index outline-gold look */
    .btn-cta-outline {
        border: 1px solid #555;
        color: #fff;
        padding: 12px 30px;
        border-radius: 2px;
        text-decoration: none;
        transition: 0.3s;
        display: inline-block;
        margin-left: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.85rem;
    }
    .btn-cta-outline:hover {
        background: #d4af37;
        border-color: #d4af37;
        color: #000;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
    }

    /* Force selected icons to use gold color for branding consistency */
    .fa-check-circle,
    .fa-shield-halved,
    .fa-sack-dollar,
    .fa-calendar-check,
    .fa-screwdriver-wrench,
    .fa-headset {
        color: #d4af37 !important;
    }

    /* Code text stands out on dark background */
    code {
        color: #d4af37;
    }

    /* Simple animation for hero text */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body>

<?php include('includes/header.php'); ?>
<!-- Reuse the same header across all pages for consistent navigation -->

<section class="page-hero">
  <div class="container">
    <h1 class="hero-title">About Us</h1>
    <p class="hero-subtitle">
      Buat Kerja Betul2 Car Rental —
      <span style="color:#f1c40f; font-weight:800;">More Than a Rental. It’s an Experience.</span>
    </p>
    <hr class="accent-line">
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container">

    <!-- Use Bootstrap grid to make layout responsive on mobile and desktop -->
    <div class="row g-4 align-items-center">

      <!-- Left column: company introduction and key selling points -->
      <div class="col-lg-7">
        <h2 class="section-title mb-3">Who We Are</h2>
        <p class="text-soft mb-3">
          Buat Kerja Betul2 Car Rental is a simple and friendly car rental service based in Melaka.
          We focus on providing affordable prices, newer cars, and fast paperwork, so customers can rent without stress.
          Our goal is to make the rental process easy, safe, and smooth for everyone.
        </p>

        <!-- Small feature blocks to highlight top selling points -->
        <div class="d-flex gap-3 flex-wrap mt-4">

          <div class="d-flex align-items-center gap-2">
            <span class="icon-badge"><i class="fa fa-tags"></i></span>
            <div>
              <div class="fw-bold" style="color:#FFFFFF;">Cheap Price</div>
              <div class="text-soft" style="font-size:0.9rem;">Budget-friendly rates</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="icon-badge"><i class="fa fa-car-side"></i></span>
            <div>
              <div class="fw-bold" style="color:#FFFFFF;">Newer Cars</div>
              <div class="text-soft" style="font-size:0.9rem;">Clean & comfortable</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="icon-badge"><i class="fa fa-bolt"></i></span>
            <div>
              <div class="fw-bold" style="color:#FFFFFF;">Fast Process</div>
              <div class="text-soft" style="font-size:0.9rem;">Quick booking & paperwork</div>
            </div>
          </div>

        </div>
      </div>

      <!-- Right column: service list in a card -->
      <div class="col-lg-5">
        <div class="info-card p-4">
          <h5 class="fw-bold mb-3" style="color:#FFFFFF;">What We Offer</h5>

          <ul class="list-unstyled mb-0">

            <li class="mb-3 d-flex gap-2">
              <i class="fa fa-check-circle" style="margin-top:3px;"></i>
              <div>
                <div class="fw-bold">Daily Rental</div>
                <div class="text-soft" style="font-size:0.9rem;">Flexible daily plans for short trips</div>
              </div>
            </li>

            <li class="mb-3 d-flex gap-2">
              <i class="fa fa-check-circle" style="margin-top:3px;"></i>
              <div>
                <div class="fw-bold">Weekly / Monthly Rental</div>
                <div class="text-soft" style="font-size:0.9rem;">Better value for longer use</div>
              </div>
            </li>

            <li class="d-flex gap-2">
              <i class="fa fa-check-circle" style="margin-top:3px;"></i>
              <div>
                <div class="fw-bold">Corporate Rental</div>
                <div class="text-soft" style="font-size:0.9rem;">Simple support for business needs</div>
              </div>
            </li>

          </ul>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">

    <!-- Section header centered to guide user reading flow -->
    <div class="text-center mb-5">
      <h2 class="section-title">Why Choose Us</h2>
      <p class="text-soft">Trusted service with an easy booking experience.</p>
      <hr class="accent-line">
    </div>

    <!-- Feature cards use responsive grid -->
    <div class="row g-4">

      <div class="col-md-4">
        <div class="info-card p-4">
          <div class="icon-badge mb-3"><i class="fa fa-shield-halved"></i></div>
          <h5 class="fw-bold">Trusted</h5>
          <p class="text-soft mb-0">We aim to build trust with clear rental terms and responsible service.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="info-card p-4">
          <div class="icon-badge mb-3"><i class="fa fa-sack-dollar"></i></div>
          <h5 class="fw-bold">Affordable</h5>
          <p class="text-soft mb-0">Fair pricing and good value, suitable for students and working adults.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="info-card p-4">
          <div class="icon-badge mb-3"><i class="fa fa-calendar-check"></i></div>
          <h5 class="fw-bold">Easy Booking</h5>
          <p class="text-soft mb-0">Simple steps to book your car. No complicated process.</p>
        </div>
      </div>

      <div class="col-md-6">
        <div class="info-card p-4">
          <div class="icon-badge mb-3"><i class="fa fa-screwdriver-wrench"></i></div>
          <h5 class="fw-bold">Well-maintained Cars</h5>
          <p class="text-soft mb-0">We keep our cars clean and in good condition for a safer ride.</p>
        </div>
      </div>

      <div class="col-md-6">
        <div class="info-card p-4">
          <div class="icon-badge mb-3"><i class="fa fa-headset"></i></div>
          <h5 class="fw-bold">Friendly Support</h5>
          <p class="text-soft mb-0">If you need help, we reply and assist as fast as we can.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container">

    <div class="text-center mb-5">
      <h2 class="section-title">Quick Facts</h2>
      <p class="text-soft">A small start — but we are improving step by step.</p>
      <hr class="accent-line">
    </div>

    <!-- Stats are hardcoded here because this is a student project demo -->
    <div class="row g-4">
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-number">10+</div>
          <div class="stat-label">Customers Served</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-number">8</div>
          <div class="stat-label">Cars Available</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-number">0.1</div>
          <div class="stat-label">Years of Service</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card text-center">
          <div class="stat-number">Melaka</div>
          <div class="stat-label">Location</div>
        </div>
      </div>
    </div>

    <!-- Clarify that stats are for learning/demo purpose -->
    <p class="text-center text-soft mt-4 mb-0" style="font-size:0.95rem;">
      *These numbers are for project / learning purpose.
    </p>

  </div>
</section>

<section class="py-5">
  <div class="container">

    <div class="text-center mb-5">
      <h2 class="section-title">Our Team</h2>
      <p class="text-soft">We work together to deliver a better rental experience.</p>
      <hr class="accent-line">
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="team-card">

          <!-- Use onerror fallback so the page still looks okay if image is missing -->
          <img
            src="image/interviewpicture.png"
            class="team-img"
            onerror="this.src='https://placehold.co/1200x600?text=Team+Photo+Not+Found';"
            alt="Team Photo"
          >

          <div class="p-4">
            <h5 class="fw-bold mb-2" style="color:#FFFFFF;">Meet the People Behind the Service</h5>
            <p class="text-soft mb-0">
              We are a small team from Melaka. We focus on making the system easy to use,
              and we try our best to serve customers in a friendly and responsible way.
            </p>
          </div>
        </div>

        <p class="text-center text-soft mt-3 mb-0" style="font-size:0.9rem;">
          (Team image: <code>image/interviewpicture.png</code>)
        </p>

      </div>
    </div>

  </div>
</section>

<section class="py-5 bg-dark text-white">
  <div class="container text-center">

    <!-- CTA helps guide user to the next action -->
    <h2 class="fw-bold mb-2" style="text-transform:uppercase; letter-spacing:1px;">
      Ready to book your ride?
    </h2>

    <p class="mb-4" style="color:#ddd;">
      Browse our cars and choose the best one for your trip.
    </p>

    <a href="car-listing.php" class="btn-cta">
      Browse Cars <i class="fa fa-arrow-right"></i>
    </a>

    <a href="contact.php" class="btn-cta-outline">
      Contact Us <i class="fa fa-envelope"></i>
    </a>

  </div>
</section>

<?php include('includes/footer.php'); ?>
<!-- Reuse the same footer across all pages for consistency -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
