<?php 
session_start();
include('includes/config.php');
error_reporting(0);

if(strlen($_SESSION['login'])==0)
{   
    header('location:index.php');
}
else
{
    $bkid = intval($_GET['bkid']);
    $useremail = $_SESSION['login'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #0f0f0f; color: #fff; }
        
        .page-header {
            background-color: #000;
            padding: 100px 0 50px;
            text-align: center;
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.7), rgba(15,15,15,1)), url('admin/img/vehicleimages/background.jpg');
            background-size: cover;
        }
        .page-header h1 { font-family: 'Playfair Display', serif; color: #d4af37; font-size: 3rem; }

        .booking-card {
            background: #181818;
            border: 1px solid #333;
            border-radius: 0;
            padding: 30px;
            margin-top: 30px;
        }
        .section-title { font-family: 'Playfair Display', serif; color: #fff; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        .info-label { color: #888; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; }
        .info-value { color: #fff; font-weight: 500; text-align: right; }
        .info-value.gold { color: #d4af37; font-weight: 700; font-size: 1.1rem; }

        .btn-pay {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000; border: none; width: 100%; padding: 15px; font-weight: bold; text-transform: uppercase; margin-top: 20px;
        }
        .btn-pay:hover { background: #fff; color: #000; }

        /* Status Badges */
        .status-badge { padding: 5px 10px; font-size: 0.8rem; border-radius: 2px; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #333; color: #f1c40f; border: 1px solid #f1c40f; }
        .status-confirmed { background: #0f5132; color: #fff; border: 1px solid #0f5132; }
        .status-cancelled { background: #842029; color: #fff; border: 1px solid #842029; }

        .pay-unpaid { color: #e74c3c; font-weight: bold; }
        .pay-paid { color: #27ae60; font-weight: bold; }
        .pay-processing { color: #f1c40f; font-weight: bold; }
        .pay-refunded { color: #2ecc71; font-weight: bold; text-decoration: line-through; } 
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="page-header">
        <h1>Booking Details</h1>
        <p class="text-white opacity-75">Track your ride status</p>
    </div>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <a href="my-booking.php" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa fa-arrow-left"></i> Back to My Bookings</a>

                <?php 
                $sql = "SELECT tblbooking.*,tblvehicles.VehiclesTitle,tblvehicles.PricePerDay,tblbrands.BrandName,tblvehicles.Vimage1 
                        FROM tblbooking 
                        JOIN tblvehicles ON tblbooking.VehicleId=tblvehicles.id 
                        JOIN tblbrands ON tblvehicles.VehiclesBrand=tblbrands.id 
                        WHERE tblbooking.id=:bkid AND tblbooking.userEmail=:useremail";
                $query = $dbh->prepare($sql);
                $query->bindParam(':bkid', $bkid, PDO::PARAM_STR);
                $query->bindParam(':useremail', $useremail, PDO::PARAM_STR);
                $query->execute();
                $results=$query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $result) { 
                        // Calculate total days
                        $d1 = new DateTime($result->FromDate);
                        $d2 = new DateTime($result->ToDate);
                        $interval = $d1->diff($d2);
                        $days = $interval->days;
                        if($days == 0) $days = 1;
                        $total = $days * $result->PricePerDay;
                ?>

                <div class="row">
                    <div class="col-md-5">
                        <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" class="img-fluid border border-secondary" alt="Car">
                        <h3 class="mt-3 text-white"><?php echo htmlentities($result->BrandName);?> <?php echo htmlentities($result->VehiclesTitle);?></h3>
                    </div>

                    <div class="col-md-7">
                        <div class="booking-card mt-0">
                            <h4 class="section-title">Booking Info</h4>
                            
                            <div class="info-row">
                                <span class="info-label">Booking No.</span>
                                <span class="info-value">#<?php echo htmlentities($result->id);?></span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <?php 
                                    if($result->Status==0){ 
                                        echo '<span class="status-badge status-pending">Pending Approval</span>';
                                    } else if($result->Status==1) {
                                        echo '<span class="status-badge status-confirmed">Confirmed</span>';
                                    } else {
                                        echo '<span class="status-badge status-cancelled">Cancelled</span>';
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Payment Status</span>
                                <span class="info-value">
                                    <?php 
                                    if($result->payment_status==0){ 
                                        echo '<span class="pay-unpaid">UNPAID</span>';
                                    } else if($result->payment_status==1) {
                                        echo '<span class="pay-paid">PAID <i class="fa fa-check-circle"></i></span>';
                                    } else if($result->payment_status==2) {
                                        echo '<span class="pay-processing">REFUND PROCESSING...</span>';
                                    } else if($result->payment_status==3) {
                                        echo '<span class="pay-refunded">REFUNDED (COMPLETED)</span>';
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="info-row mt-4">
                                <span class="info-label">From Date</span>
                                <span class="info-value"><?php echo htmlentities($result->FromDate);?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">To Date</span>
                                <span class="info-value"><?php echo htmlentities($result->ToDate);?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Duration</span>
                                <span class="info-value"><?php echo htmlentities($days);?> Days</span>
                            </div>

                            <div class="info-row" style="border-bottom: none; margin-top: 20px;">
                                <span class="info-label">Estimated Total</span>
                                <span class="info-value gold">RM <?php echo number_format($total, 2);?></span>
                            </div>

                            <?php if($result->Status != 2 && $result->payment_status == 0) { ?>
                                <a href="payment.php?bkid=<?php echo htmlentities($result->id);?>" class="btn btn-pay">Proceed to Payment</a>
                            <?php } ?>

                            <?php if($result->Status == 2 && $result->payment_status == 2) { ?>
                                <div class="alert alert-warning mt-3 mb-0" role="alert">
                                    <i class="fa fa-clock"></i> <strong>Refund in Progress:</strong> Your booking was cancelled. We are processing your refund.
                                </div>
                            <?php } ?>

                            <?php if($result->Status == 2 && $result->payment_status == 3) { ?>
                                <div class="alert mt-3 mb-0" role="alert" 
                                     style="background: #333; border: 1px solid #444; color: #d4af37;">
                                    <i class="fa fa-check-circle" style="color: #2ecc71;"></i> 
                                    <strong>Refund Completed:</strong> The amount has been returned to your account.
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>

                <?php }} ?>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php');?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>