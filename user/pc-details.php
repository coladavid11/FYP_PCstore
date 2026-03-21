<?php 
session_start();
require_once(__DIR__ . '/includes/config.php');
error_reporting(0);

$booking_status = ""; 
$review_status = ""; 

$vhid = intval($_GET['vhid']);

// ===========================
//  LOGIC 1: SUBMIT REVIEW
// ===========================
if (isset($_POST['submit_review'])) {
    if (empty($_SESSION['login'])) {
        $review_status = "not_logged_in";
    } else {
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? "");


        if ($rating < 1 || $rating > 5 || $comment === "") {
            $review_status = "invalid";
        } else {
        
            $sql = "INSERT INTO tblreviews (VehicleId, userEmail, rating, comment, status)
                    VALUES (:vhid, :email, :rating, :comment, 1)";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':vhid', $vhid, PDO::PARAM_INT);
            $stmt->bindParam(':email', $_SESSION['login'], PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $review_status = "success";
            } else {
                $review_status = "error";
            }
        }
    }
}

// ===========================
//  LOGIC 2: BOOKING
// ===========================
if(isset($_POST['submit']))
{
    $fromdate=$_POST['fromdate'];
    $todate=$_POST['todate']; 
    $message=$_POST['message'];
    $useremail=$_SESSION['login'];
    $status=0; // 0 = Pending
    
    // Check if user is logged in
    if(strlen($_SESSION['login'])==0)
    {   
        $booking_status = "not_logged_in";
    }
    else
    {
        $sql="INSERT INTO tblbooking(userEmail,VehicleId,FromDate,ToDate,message,Status) VALUES(:useremail,:vhid,:fromdate,:todate,:message,:status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':useremail',$useremail,PDO::PARAM_STR);
        $query->bindParam(':vhid',$vhid,PDO::PARAM_STR);
        $query->bindParam(':fromdate',$fromdate,PDO::PARAM_STR);
        $query->bindParam(':todate',$todate,PDO::PARAM_STR);
        $query->bindParam(':message',$message,PDO::PARAM_STR);
        $query->bindParam(':status',$status,PDO::PARAM_STR);
        $query->execute();
        $lastInsertId = $dbh->lastInsertId();
        if($lastInsertId)
        {
            $booking_status = "success";
        }
        else 
        {
            $booking_status = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Details | Premium Fleet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@4/dark.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #000000; /* Pure Black Background */
            color: #fff;
        }

        /* --- 1. HERO CAROUSEL SECTION --- */
        .detail-hero {
            height: 600px;
            position: relative;
            background-color: #000;
        }
        
        /* Carousel Styling */
        .carousel, .carousel-inner, .carousel-item {
            height: 100%;
        }
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.9;
        }

        /* Custom Gold Arrows */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            filter: invert(78%) sepia(26%) saturate(1008%) hue-rotate(359deg) brightness(92%) contrast(88%); /* Gold Color */
            width: 3rem;
            height: 3rem;
        }
        .carousel-control-prev, .carousel-control-next {
            width: 8%; /* Wider click area */
            opacity: 0.7;
            z-index: 10;
        }
        .carousel-control-prev:hover, .carousel-control-next:hover { opacity: 1; }

        /* Overlay Text */
        .hero-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, #000000, transparent);
            padding: 150px 0 50px 0;
            pointer-events: none; /* Allows clicking through to carousel controls */
            z-index: 5;
        }
        .car-title-large {
            font-family: 'Playfair Display', serif;
            font-size: 3.8rem;
            color: #fff;
            text-shadow: 0 5px 20px rgba(0,0,0,0.9);
            margin-bottom: 20px;
        }
        .car-brand-sub {
            color: #d4af37; /* Champagne Gold */
            font-size: 1.2rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0,0,0,0.9);
        }

        /* --- 2. MAIN CONTENT LAYOUT --- */
        .content-wrapper {
            margin-top: -60px; 
            position: relative;
            z-index: 10;
        }

        /* BACK BUTTON STYLE */
        .btn-back-link {
            color: #888;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid #333;
            padding: 8px 20px;
            border-radius: 50px;
            background: #111;
        }
        .btn-back-link:hover {
            color: #000;
            background: #d4af37;
            border-color: #d4af37;
            transform: translateX(-5px);
        }
        .btn-back-link i { margin-right: 8px; }

        /* Detail Card */
        .detail-card {
            background: #111; /* Dark Grey */
            border: 1px solid #222;
            padding: 35px;
            border-radius: 0; 
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
            color: #fff;
        }

        /* Specs Boxes */
        .specs-box {
            background: #1a1a1a;
            padding: 25px 15px;
            text-align: center;
            border: 1px solid #333;
            transition: 0.3s;
            height: 100%;
        }
        .specs-box:hover { border-color: #d4af37; background: #222; }
        .specs-box i { font-size: 1.8rem; color: #d4af37; margin-bottom: 15px; display: block; }
        .specs-box span { display: block; font-size: 0.8rem; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .specs-box strong { font-size: 1.1rem; color: #fff; font-weight: 600; }

        .desc-text { color: #ccc; line-height: 1.9; font-weight: 300; font-size: 1rem; }

        /* Accessories */
        .accessories-list { list-style: none; padding: 0; display: flex; flex-wrap: wrap; margin-top: 20px; }
        .accessories-list li { width: 50%; margin-bottom: 15px; color: #bbb; display: flex; align-items: center; font-size: 0.95rem; }
        .accessories-list li i { margin-right: 12px; font-size: 1.1rem; }
        .check-icon { color: #27ae60 !important; }
        .times-icon { color: #555 !important; opacity: 0.4; }

        /* Thumbnail Grid */
        .thumb-grid { display: flex; gap: 15px; margin-top: 30px; }
        .thumb-img { 
            width: 120px; 
            height: 80px; 
            object-fit: cover; 
            border: 1px solid #333; 
            cursor: zoom-in; 
            opacity: 0.6; 
            transition: 0.3s; 
        }
        .thumb-img:hover { 
            opacity: 1; 
            border-color: #d4af37; 
            transform: scale(1.05); 
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
        }

        /* Booking Sidebar */
        .booking-sidebar {
            background: #111;
            border: 1px solid #d4af37;
            padding: 35px;
            border-radius: 0;
            position: sticky;
            top: 100px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }
        
        .price-display {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #333;
            padding-bottom: 25px;
        }
        .main-price { font-size: 2.8rem; color: #d4af37; font-family: 'Playfair Display', serif; font-weight: 700; line-height: 1; }
        .price-label { color: #888; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 2px; margin-top: 10px; display: block; }

        .deposit-badge {
            background: rgba(212, 175, 55, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: #d4af37;
            padding: 12px;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }

        .form-control-dark {
            background: #222;
            border: 1px solid #333;
            color: #fff;
            padding: 15px;
            border-radius: 0;
        }
        .form-control-dark:focus { background: #000; color: #fff; border-color: #d4af37; box-shadow: none; }
        label { color: #888; font-size: 0.75rem; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }

        .btn-book-now {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            width: 100%;
            padding: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            margin-top: 20px;
            transition: 0.3s;
            border-radius: 0;
        }
        .btn-book-now:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3); background: #fff; }
        
        .login-link-btn {
            display: block;
            text-align: center;
            background: #333;
            color: #fff;
            padding: 15px;
            text-decoration: none;
            margin-top: 15px;
            text-transform: uppercase;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .login-link-btn:hover { background: #d4af37; color: #000; }

        /* 🔥 REVIEW SECTION STYLE */
        .review-item { background:#1a1a1a; border:1px solid #2a2a2a; padding:15px; margin-bottom:12px; }
        .review-header { display:flex; justify-content:space-between; flex-wrap:wrap; margin-bottom: 8px; }
        .review-user { font-weight:600; color:#d4af37; }
        .review-date { color:#888; font-size:0.8rem; }
        .review-stars { color:#f1c40f; margin-bottom: 5px; font-size: 0.9rem; }
        .review-comment { color:#ddd; font-size: 0.95rem; line-height: 1.6; }
        
        .btn-hero { background: #d4af37; color: #000; font-weight: bold; border: none; transition: 0.3s; }
        .btn-hero:hover { background: #fff; transform: translateY(-2px); }
        .btn-outline-gold { border: 1px solid #d4af37; color: #d4af37; padding: 10px 20px; text-decoration: none; display: inline-block; transition: 0.3s; text-align: center; width: 100%;}
        .btn-outline-gold:hover { background: #d4af37; color: #000; }

        /* Lightbox Modal */
        .image-modal {
            display: none;
            position: fixed; 
            z-index: 9999; 
            padding-top: 50px; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.95);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 1200px;
            max-height: 85vh;
            object-fit: contain;
            border: 1px solid #333;
            box-shadow: 0 0 50px rgba(0,0,0,1);
            animation: zoomIn 0.3s;
        }
        .close-btn {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #d4af37;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
        .close-btn:hover { color: #fff; }
        @keyframes zoomIn { from {transform:scale(0.8); opacity:0} to {transform:scale(1); opacity:1} }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <?php 
    // Fetch Vehicle Details
    $sql = "SELECT tblvehicles.*,tblbrands.BrandName,tblbrands.id as bid from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand where tblvehicles.id=:vhid";
    $query = $dbh -> prepare($sql);
    $query->bindParam(':vhid', $vhid, PDO::PARAM_STR);
    $query->execute();
    $results=$query->fetchAll(PDO::FETCH_OBJ);

    if($query->rowCount() > 0)
    {
        foreach($results as $result)
        {  
            $_SESSION['brndid']=$result->bid;  
    ?>  

    <div class="detail-hero">
        <div id="carCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" alt="Main View">
                </div>
                <?php if($result->Vimage2!="") { ?>
                <div class="carousel-item">
                    <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage2);?>" alt="Side View">
                </div>
                <?php } ?>
                <?php if($result->Vimage3!="") { ?>
                <div class="carousel-item">
                    <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage3);?>" alt="Interior View">
                </div>
                <?php } ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        <div class="hero-overlay">
            <div class="container">
                <div class="car-brand-sub"><?php echo htmlentities($result->BrandName);?></div>
                <h1 class="car-title-large"><?php echo htmlentities($result->VehiclesTitle);?></h1>
            </div>
        </div>
    </div>

    <div class="container content-wrapper">
        
        <div class="row">
            <div class="col-12">
                <a href="car-listing.php" class="btn-back-link">
                    <i class="fa fa-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>

        <div class="row">
            
            <div class="col-lg-8">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="specs-box">
                            <i class="fa fa-calendar"></i>
                            <span>Year Model</span>
                            <strong><?php echo htmlentities($result->ModelYear);?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="specs-box">
                            <i class="fa fa-gas-pump"></i>
                            <span>Fuel Type</span>
                            <strong><?php echo htmlentities($result->FuelType);?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="specs-box">
                            <i class="fa fa-chair"></i>
                            <span>Seats</span>
                            <strong><?php echo htmlentities($result->SeatingCapacity);?></strong>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="specs-box">
                            <i class="fa fa-cogs"></i>
                            <span>Transmission</span>
                            <strong><?php echo htmlentities($result->Transmission);?></strong>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <h3 class="section-heading">Vehicle Overview</h3>
                    <p class="desc-text"><?php echo htmlentities($result->VehiclesOverview);?></p>
                    
                    <div class="thumb-grid">
                        <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" class="thumb-img" onclick="openLightbox(this.src)">
                        <?php if($result->Vimage2!="") { ?>
                             <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage2);?>" class="thumb-img" onclick="openLightbox(this.src)">
                        <?php } ?>
                        <?php if($result->Vimage3!="") { ?>
                             <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage3);?>" class="thumb-img" onclick="openLightbox(this.src)">
                        <?php } ?>
                    </div>
                    <p class="small text-muted mt-3"><i class="fa fa-search-plus"></i> Click thumbnails to zoom in.</p>
                </div>

                <div class="detail-card">
                    <h3 class="section-heading">Features & Accessories</h3>
                    <ul class="accessories-list">
                        <li><i class="<?php echo ($result->AirConditioner==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Air Conditioner</li>
                        <li><i class="<?php echo ($result->PowerDoorLocks==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Power Door Locks</li>
                        <li><i class="<?php echo ($result->AntiLockBrakingSystem==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> ABS System</li>
                        <li><i class="<?php echo ($result->PowerSteering==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Power Steering</li>
                        <li><i class="<?php echo ($result->PowerWindows==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Power Windows</li>
                        <li><i class="<?php echo ($result->CDPlayer==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Bluetooth/Audio</li>
                        <li><i class="<?php echo ($result->LeatherSeats==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Leather Seats</li>
                        <li><i class="<?php echo ($result->CentralLocking==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Central Locking</li>
                        <li><i class="<?php echo ($result->CrashSensor==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Crash Sensor</li>
                        <li><i class="<?php echo ($result->DriverAirbag==1) ? 'fa fa-check-circle check-icon' : 'fa fa-times-circle times-icon';?>"></i> Driver Airbag</li>
                    </ul>
                </div>

                <div class="detail-card">
                    <h3 class="section-heading">Customer Reviews</h3>
                    
                    <?php
                    // ---- FETCH ALL REVIEWS ----
                    // Join with tblusers to get FullName if possible, otherwise just use Email
                    $rsql = "SELECT tblreviews.*, tblusers.FullName 
                             FROM tblreviews 
                             LEFT JOIN tblusers ON tblreviews.userEmail = tblusers.EmailId 
                             WHERE tblreviews.VehicleId=:vhid AND tblreviews.status=1 
                             ORDER BY tblreviews.created_at DESC";
                    $rq = $dbh->prepare($rsql);
                    $rq->bindParam(':vhid', $vhid, PDO::PARAM_INT);
                    $rq->execute();
                    $reviews = $rq->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!$reviews || count($reviews) === 0) { ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fa fa-comment-slash fa-2x mb-3"></i>
                            <p>No reviews yet. Be the first to comment!</p>
                        </div>
                    <?php } else { 
                        foreach ($reviews as $rv) { ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-user">
                                        <i class="fa fa-user-circle me-2"></i>
                                        <?php echo htmlentities($rv->FullName ? $rv->FullName : $rv->userEmail); ?>
                                    </div>
                                    <div class="review-date">
                                        <?php echo htmlentities($rv->created_at); ?>
                                    </div>
                                </div>

                                <div class="review-stars">
                                    <?php 
                                    for($i=1; $i<=5; $i++) {
                                        if($i <= $rv->rating) { echo '<i class="fa fa-star"></i>'; }
                                        else { echo '<i class="far fa-star text-muted"></i>'; }
                                    }
                                    ?>
                                </div>

                                <div class="review-comment">
                                    <?php echo nl2br(htmlentities($rv->comment)); ?>
                                </div>
                            </div>
                        <?php } 
                    } ?>

                    <hr style="border-color:#2a2a2a; margin:25px 0;">

                    <?php if (empty($_SESSION['login'])) { ?>
                        <a href="login.php?redirect=vehical-details.php?vhid=<?php echo $vhid; ?>" class="btn-outline-gold">
                            Login to write a review
                        </a>
                    <?php } else { ?>
                        <form method="post">
                            <div class="mb-3">
                                <label style="color:#aaa;">Rating (1–5)</label>
                                <select class="form-control form-control-dark" name="rating" required>
                                    <option value="">Select rating</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Okay</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Bad</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label style="color:#aaa;">Your Experience</label>
                                <textarea name="comment" rows="3" class="form-control form-control-dark" required placeholder="How was the car?"></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn-hero" style="padding:12px 28px; width:100%;">
                                Submit Review
                            </button>
                        </form>
                    <?php } ?>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="booking-sidebar">
                    
                    <div class="price-display">
                        <div class="main-price">RM <?php echo htmlentities($result->PricePerDay);?></div>
                        <span class="price-label">Daily Rate</span>
                    </div>

                    <div class="deposit-badge">
                        <i class="fa fa-shield-alt"></i> Security Deposit: 
                        <strong>RM <?php echo htmlentities($result->SecurityDeposit);?></strong>
                        <div style="font-size: 0.75rem; margin-top: 5px; opacity: 0.8;">(Refundable upon return)</div>
                    </div>

                    <?php if($result->PricePerWeek > 0) { ?>
                        <div class="d-flex justify-content-between text-secondary mb-2 small" style="border-bottom: 1px solid #333; padding-bottom: 8px;">
                            <span>Weekly Rate (7 Days):</span>
                            <span class="text-white">RM <?php echo htmlentities($result->PricePerWeek);?></span>
                        </div>
                    <?php } ?>
                    <?php if($result->PricePerMonth > 0) { ?>
                        <div class="d-flex justify-content-between text-secondary mb-4 small">
                            <span>Monthly Rate (30 Days):</span>
                            <span class="text-white">RM <?php echo htmlentities($result->PricePerMonth);?></span>
                        </div>
                    <?php } ?>

                    <h4 class="text-white mb-4 mt-4" style="font-family: 'Playfair Display'; text-align: center;">Book This Vehicle</h4>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label>From Date</label>
                            <input type="date" class="form-control form-control-dark" name="fromdate" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>To Date</label>
                            <input type="date" class="form-control form-control-dark" name="todate" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Message (Optional)</label>
                            <textarea rows="3" class="form-control form-control-dark" name="message" placeholder="Any special requests?"></textarea>
                        </div>
                        
                        <?php if($_SESSION['login']) { ?>
                            <button type="submit" class="btn btn-book-now" name="submit">Confirm Booking</button>
                        <?php } else { ?>
                            <?php $redirect = "vehical-details.php?vhid=" . urlencode($_GET['vhid'] ?? ''); ?>
                            <a href="login.php?redirect=<?php echo urlencode($redirect); ?>" class="login-link-btn">
                                Login to Book
                            </a>
                        <?php } ?>
                    </form>

                </div>
            </div>

        </div>
    </div>

    <div id="imgModal" class="image-modal" onclick="closeModal()">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="fullImage">
    </div>

    <?php }} ?>

    <?php include('includes/footer.php');?>
    <?php include('includes/login.php');?>
    <?php include('includes/registration.php');?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function openLightbox(src) {
            var modal = document.getElementById("imgModal");
            var modalImg = document.getElementById("fullImage");
            modal.style.display = "block";
            modalImg.src = src; 
        }

        function closeModal() {
            var modal = document.getElementById("imgModal");
            modal.style.display = "none";
        }

        // 3. Handle SweetAlert Popups for Booking
        <?php if($booking_status == "success") { ?>
            Swal.fire({
                title: 'Booking Confirmed!',
                text: 'We have received your booking request. Our team will contact you shortly.',
                icon: 'success',
                confirmButtonText: 'Great!',
                confirmButtonColor: '#d4af37', 
                background: '#1a1a1a', 
                color: '#fff' 
            });
        <?php } elseif($booking_status == "error") { ?>
            Swal.fire({
                title: 'Booking Failed',
                text: 'Something went wrong. Please try again later.',
                icon: 'error',
                confirmButtonColor: '#d33',
                background: '#1a1a1a',
                color: '#fff'
            });
        <?php } elseif($booking_status == "not_logged_in") { ?>
            Swal.fire({
                title: 'Login Required',
                text: 'Please login to book this vehicle.',
                icon: 'warning',
                confirmButtonText: 'Login Now',
                confirmButtonColor: '#d4af37',
                showCancelButton: true,
                cancelButtonColor: '#555',
                background: '#1a1a1a',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php?redirect=vehical-details.php?vhid=<?php echo $vhid; ?>';
                }
            });
        <?php } ?>

        // 4. Handle SweetAlert Popups for Reviews
        <?php if ($review_status === "success") { ?>
            Swal.fire({
              icon: 'success',
              title: 'Review Submitted',
              text: 'Thank you for your feedback!',
              confirmButtonColor: '#d4af37',
              background: '#1a1a1a',
              color: '#fff'
            }).then(() => {
                // Prevent form resubmission
                if ( window.history.replaceState ) {
                    window.history.replaceState( null, null, window.location.href );
                }
                window.location.href = window.location.href; // Reload
            });
        <?php } ?>

        <?php if ($review_status === "invalid") { ?>
            Swal.fire({
              icon: 'error',
              title: 'Invalid Input',
              text: 'Please give a rating and write a comment.',
              confirmButtonColor: '#d4af37',
              background: '#1a1a1a',
              color: '#fff'
            });
        <?php } ?>

        <?php if ($review_status === "error") { ?>
            Swal.fire({
              icon: 'error',
              title: 'Failed',
              text: 'Something went wrong. Try again.',
              confirmButtonColor: '#d4af37',
              background: '#1a1a1a',
              color: '#fff'
            });
        <?php } ?>
    </script>
</body>
</html>