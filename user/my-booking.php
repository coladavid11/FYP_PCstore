<?php
session_start();
require_once __DIR__ . '/includes/config.php';
error_reporting(0);

if (empty($_SESSION['login'])) {
  header("Location: index.php");
  exit();
}

$useremail = $_SESSION['login'];

$sql = "SELECT 
          b.id AS bookingId,
          b.FromDate,
          b.ToDate,
          b.message,
          b.Status,
          b.PostingDate,
          v.id AS vehicleId,
          v.VehiclesTitle,
          v.PricePerDay,
          v.FuelType,
          v.ModelYear,
          v.SeatingCapacity,
          v.Vimage1,
          br.BrandName
        FROM tblbooking b
        JOIN tblvehicles v ON v.id = b.VehicleId
        JOIN tblbrands br ON br.id = v.VehiclesBrand
        WHERE b.userEmail = :useremail
        ORDER BY b.PostingDate DESC";

$q = $dbh->prepare($sql);
$q->bindParam(':useremail', $useremail, PDO::PARAM_STR);
$q->execute();
$bookings = $q->fetchAll(PDO::FETCH_OBJ);

function statusText($s) {
  $s = (int)$s; 
  if ($s === 1) return "Confirmed";
  if ($s === 2) return "Cancelled";
  return "Pending";
}
function statusClass($s) {
  $s = (int)$s;
  if ($s === 1) return "badge-confirmed";
  if ($s === 2) return "badge-cancelled";
  return "badge-pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Bookings - Buat Kerja Betul2 Car Rental</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #0f0f0f;
      color: #fff;
    }

    .page-hero {
      background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.9)),
        url('https://images.unsplash.com/photo-1550355291-bbee04a92027?q=80&w=2070&auto=format&fit=crop');
      background-size: cover;
      background-position: center;
      height: 45vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      position: relative;
    }
    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: 3.2rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      text-shadow: 0 10px 30px rgba(0,0,0,0.8);
      margin-bottom: 10px;
    }
    .hero-subtitle {
      font-size: 0.95rem;
      color: #d4af37;
      letter-spacing: 3px;
      text-transform: uppercase;
      font-weight: 500;
      margin: 0;
      opacity: 0.95;
    }
    .section-divider {
      width: 60px;
      height: 2px;
      background: #d4af37;
      margin: 16px auto 0;
      border: none;
    }

    .booking-card {
      background: #181818;
      border: 1px solid #2a2a2a;
      border-radius: 0;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      transition: all 0.4s ease;
      overflow: hidden;
      height: 100%;
    }
    .booking-card:hover {
      transform: translateY(-10px);
      border-color: #444;
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }

    .img-wrapper {
      position: relative;
      height: 210px;
      overflow: hidden;
      background: #111;
    }
    .car-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: 0.6s;
      filter: brightness(0.9);
    }
    .booking-card:hover .car-img {
      transform: scale(1.08);
      filter: brightness(1.08);
    }

    .card-body {
      padding: 22px;
    }

    .card-title {
      font-family: 'Playfair Display', serif;
      color: #fff;
      font-size: 1.35rem;
      margin-bottom: 6px;
      line-height: 1.2;
    }
    .subtext {
      color: #aaa;
      font-size: 0.88rem;
      margin-bottom: 12px;
    }

    .price {
      color: #d4af37;
      font-weight: 700;
      font-size: 1.05rem;
      display: inline-block;
      margin-bottom: 12px;
    }

    .meta {
      border-top: 1px solid #2a2a2a;
      padding-top: 12px;
      margin-top: 10px;
      font-size: 0.85rem;
      color: #aaa;
      display: flex;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }
    .meta i { color: #d4af37; margin-right: 6px; }

    .badge-pending {
      background: rgba(212, 175, 55, 0.12);
      border: 1px solid rgba(212, 175, 55, 0.35);
      color: #d4af37;
      padding: 6px 10px;
      font-size: 0.8rem;
      border-radius: 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .badge-confirmed {
      background: rgba(39, 174, 96, 0.12);
      border: 1px solid rgba(39, 174, 96, 0.35);
      color: #27ae60;
      padding: 6px 10px;
      font-size: 0.8rem;
      border-radius: 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .badge-cancelled {
      background: rgba(231, 76, 60, 0.12);
      border: 1px solid rgba(231, 76, 60, 0.35);
      color: #e74c3c;
      padding: 6px 10px;
      font-size: 0.8rem;
      border-radius: 0;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .btn-outline-gold {
      border: 1px solid #555;
      color: #fff;
      width: 100%;
      padding: 10px;
      margin-top: 16px;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 1px;
      transition: 0.3s;
      text-decoration: none;
      display: block;
      text-align: center;
      border-radius: 0;
      background: transparent;
    }
    .btn-outline-gold:hover {
      background: #d4af37;
      border-color: #d4af37;
      color: #000;
    }

    .empty-box {
      background: #181818;
      border: 1px solid #2a2a2a;
      padding: 28px;
      text-align: center;
    }
    .empty-box i {
      font-size: 2rem;
      color: #d4af37;
      margin-bottom: 12px;
      display: block;
    }
    .empty-box p { color: #aaa; margin: 0; }
  </style>
</head>

<body>
<?php include(__DIR__ . '/includes/header.php'); ?>

<section class="page-hero">
  <div class="container">
    <h1 class="hero-title">My Bookings</h1>
    <p class="hero-subtitle">Track your rental requests & status</p>
    <hr class="section-divider">
  </div>
</section>

<section class="py-5">
  <div class="container">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <div style="color:#aaa; letter-spacing:1px; text-transform:uppercase; font-size:0.85rem;">
          Logged in as
        </div>
        <div style="font-weight:600;"><?php echo htmlentities($useremail); ?></div>
      </div>

      <a href="car-listing.php" class="btn-outline-gold" style="max-width:260px;">
        <i class="fa fa-car"></i> Browse Cars
      </a>
    </div>

    <?php if(!$bookings || count($bookings) === 0) { ?>
      <div class="empty-box">
        <i class="fa fa-calendar-xmark"></i>
        <h4 style="font-family:'Playfair Display', serif; margin-bottom:8px;">No bookings yet</h4>
        <p>When you book a vehicle, it will appear here.</p>
        <div class="mt-3" style="max-width:240px; margin:0 auto;">
          <a href="car-listing.php" class="btn-outline-gold">Find a Car</a>
        </div>
      </div>
    <?php } else { ?>

      <div class="row g-4">
        <?php foreach($bookings as $b) { ?>
          <div class="col-md-6 col-lg-4">
            <div class="booking-card">
              <div class="img-wrapper">
                <img
                  src="admin/img/vehicleimages/<?php echo htmlentities($b->Vimage1); ?>"
                  class="car-img"
                  alt="Vehicle"
                  onerror="this.src='https://placehold.co/900x600/222/fff?text=No+Image'"
                >
              </div>

              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <div class="card-title">
                      <?php echo htmlentities($b->BrandName . " " . $b->VehiclesTitle); ?>
                    </div>
                    <div class="subtext">
                      Booking ID: <span style="color:#fff;"><?php echo htmlentities($b->bookingId); ?></span>
                    </div>
                  </div>

                  <span class="<?php echo htmlentities(statusClass($b->Status)); ?>">
                    <?php echo htmlentities(statusText($b->Status)); ?>
                  </span>
                </div>

                <div class="price">
                  RM <?php echo htmlentities($b->PricePerDay); ?> / Day
                </div>

                <div class="meta">
                  <span><i class="fa fa-calendar"></i> <?php echo htmlentities($b->FromDate); ?> → <?php echo htmlentities($b->ToDate); ?></span>
                  <span><i class="fa fa-clock"></i> <?php echo htmlentities($b->PostingDate); ?></span>
                </div>

                <div class="meta">
                  <span><i class="fa fa-gas-pump"></i> <?php echo htmlentities($b->FuelType); ?></span>
                  <span><i class="fa fa-chair"></i> <?php echo htmlentities($b->SeatingCapacity); ?></span>
                  <span><i class="fa fa-calendar-days"></i> <?php echo htmlentities($b->ModelYear); ?></span>
                </div>

                <a class="btn-outline-gold" href="booking-details.php?bkid=<?php echo urlencode($b->bookingId); ?>">
                  View Details
                </a>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>

    <?php } ?>

  </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
