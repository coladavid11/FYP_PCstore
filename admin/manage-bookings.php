<?php
session_start();
error_reporting(0);
include('../includes/config.php');

// Security Check
if(strlen($_SESSION['alogin'])==0)
{   
    header('location:index.php');
}
else{
    // ==========================================
    // 1. Handle: Cancel Booking
    // ==========================================
    if(isset($_REQUEST['eid']))
    {
        $eid = intval($_GET['eid']);
        $status = 2; // 2 = Cancelled

        // Check Payment Status
        $sqlCheck = "SELECT payment_status FROM tblbooking WHERE id=:eid";
        $queryCheck = $dbh->prepare($sqlCheck);
        $queryCheck->bindParam(':eid', $eid, PDO::PARAM_STR);
        $queryCheck->execute();
        $resultCheck = $queryCheck->fetch(PDO::FETCH_OBJ);

        $new_payment_status = $resultCheck->payment_status; 

        // If Paid (1), set to Refund Processing (2)
        if($resultCheck->payment_status == 1) {
            $new_payment_status = 2; 
            $msg = "Booking Cancelled. Payment marked as REFUND PROCESSING.";
        } else {
            $msg = "Booking Successfully Cancelled";
        }

        $sql = "UPDATE tblbooking SET Status=:status, payment_status=:pstatus WHERE id=:eid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->bindParam(':pstatus', $new_payment_status, PDO::PARAM_STR);
        $query->bindParam(':eid', $eid, PDO::PARAM_STR);
        $query->execute();
    }

    // ==========================================
    // 2. Handle: Confirm Booking
    // ==========================================
    if(isset($_REQUEST['aeid']))
    {
        $aeid = intval($_GET['aeid']);
        $status = 1; // 1 = Confirmed

        $sql = "UPDATE tblbooking SET Status=:status WHERE id=:aeid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->bindParam(':aeid', $aeid, PDO::PARAM_STR);
        $query->execute();
        $msg = "Booking Successfully Confirmed";
    }

    // ==========================================
    // 3. Handle: Complete Refund
    // ==========================================
    if(isset($_REQUEST['refid']))
    {
        $refid = intval($_GET['refid']);
        $sql = "UPDATE tblbooking SET payment_status=3 WHERE id=:refid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':refid', $refid, PDO::PARAM_STR);
        $query->execute();
        $msg = "Refund Marked as COMPLETED!";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">

    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding-top: 80px; }
        .page-header { border-left: 5px solid #2c3e50; padding-left: 15px; margin-bottom: 30px; }
        .page-header h2 { font-weight: 800; color: #2c3e50; margin: 0; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header-custom { background-color: #2c3e50; color: white; padding: 15px 20px; font-weight: 600; }
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; color: #7f8c8d; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        .table-custom td { vertical-align: middle; color: #2c3e50; font-size: 0.9rem; }
        
        .badge-status { padding: 6px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        
        .bg-unpaid { background-color: #ffeeba; color: #856404; }
        .bg-paid { background-color: #d1e7dd; color: #0f5132; }
        .bg-processing { background-color: #ffdf7e; color: #856404; border:1px solid #ffeeba; }
        .bg-refunded { background-color: #343a40; color: #fff; }

        .bg-pending { background-color: #fff3cd; color: #856404; }
        .bg-confirmed { background-color: #d1e7dd; color: #0f5132; }
        .bg-cancelled { background-color: #f8d7da; color: #842029; }

        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: 0.3s; border: none; margin-right: 5px; text-decoration: none; cursor: pointer; }
        .btn-confirm { background-color: #e7f1ff; color: #0d6efd; }
        .btn-cancel { background-color: #ffeaea; color: #e74c3c; }
        
        .btn-refund { width: auto; padding: 4px 10px; background-color: #343a40; color: #fff; font-size: 0.75rem; font-weight: bold; border-radius: 4px; text-decoration: none; display: inline-block; transition: 0.2s; }
        .btn-refund:hover { background-color: #000; color: #fff; }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container-fluid px-4">
        <div class="page-header">
            <h2>Manage Bookings</h2>
        </div>

        <?php if($msg){ ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: '<?php echo htmlentities($msg); ?>',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            </script>
        <?php } ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fa fa-calendar-check me-2"></i> Booking Info
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Vehicle</th>
                                <th>Dates</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT tblusers.FullName,tblbrands.BrandName,tblvehicles.VehiclesTitle,tblbooking.FromDate,tblbooking.ToDate,tblbooking.message,tblbooking.VehicleId as vid,tblbooking.Status,tblbooking.PostingDate,tblbooking.id,tblbooking.payment_status, DATEDIFF(tblbooking.ToDate,tblbooking.FromDate) as totaldays, tblvehicles.PricePerDay  
                                    FROM tblbooking 
                                    JOIN tblvehicles ON tblvehicles.id=tblbooking.VehicleId 
                                    JOIN tblusers ON tblusers.EmailId=tblbooking.userEmail 
                                    JOIN tblbrands ON tblvehicles.VehiclesBrand=tblbrands.id  
                                    ORDER BY tblbooking.id DESC";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { 
                                    $days = ($result->totaldays == 0) ? 1 : $result->totaldays;
                                    $total = $days * $result->PricePerDay;
                            ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td><?php echo htmlentities($result->FullName);?></td>
                                    <td><a href="../vehical-details.php?vhid=<?php echo htmlentities($result->vid);?>" target="_blank" class="fw-bold text-dark"><?php echo htmlentities($result->BrandName);?> <?php echo htmlentities($result->VehiclesTitle);?></a></td>
                                    <td>
                                        <small class="d-block text-muted">From: <?php echo htmlentities($result->FromDate);?></small>
                                        <small class="d-block text-muted">To: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo htmlentities($result->ToDate);?></small>
                                    </td>
                                    <td>RM <?php echo number_format($total,2);?></td>
                                    
                                    <td>
                                        <?php if($result->payment_status == 0) { ?>
                                            <span class="badge badge-status bg-unpaid">Unpaid</span>
                                        <?php } else if($result->payment_status == 1) { ?>
                                            <span class="badge badge-status bg-paid">Paid</span>
                                        <?php } else if($result->payment_status == 2) { ?>
                                            <span class="badge badge-status bg-processing">Processing Refund</span>
                                        <?php } else if($result->payment_status == 3) { ?>
                                            <span class="badge badge-status bg-refunded">Refunded</span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <?php 
                                        if($result->Status==0){ ?>
                                            <span class="badge badge-status bg-pending">Pending</span>
                                        <?php } else if($result->Status==1) { ?>
                                            <span class="badge badge-status bg-confirmed">Confirmed</span>
                                        <?php } else { ?>
                                            <span class="badge badge-status bg-cancelled">Cancelled</span>
                                        <?php } ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if($result->Status == 0) { ?>
                                            <a href="javascript:void(0);" onclick="confirmAction('confirm', 'manage-bookings.php?aeid=<?php echo htmlentities($result->id);?>')" class="btn-action btn-confirm" title="Confirm"><i class="fa fa-check"></i></a>
                                            <a href="javascript:void(0);" onclick="confirmAction('cancel', 'manage-bookings.php?eid=<?php echo htmlentities($result->id);?>')" class="btn-action btn-cancel" title="Cancel"><i class="fa fa-times"></i></a>
                                        
                                        <?php } else if($result->Status == 1) { ?>
                                            <a href="javascript:void(0);" onclick="confirmAction('cancel', 'manage-bookings.php?eid=<?php echo htmlentities($result->id);?>')" class="btn-action btn-cancel" title="Cancel"><i class="fa fa-times"></i></a>
                                        
                                        <?php } else { ?>
                                            <?php } ?>

                                        <?php if($result->payment_status == 2) { ?>
                                            <div class="mt-1">
                                                <a href="javascript:void(0);" 
                                                   onclick="confirmAction('refund', 'manage-bookings.php?refid=<?php echo htmlentities($result->id);?>')" 
                                                   class="btn-refund">
                                                   Complete Refund
                                                </a>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php $cnt=$cnt+1; }} ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmAction(type, url) {
            let title = '', text = '', btnColor = '', btnText = '';

            if (type === 'cancel') {
                title = 'Cancel Booking?';
                text = 'If Paid, status will change to Refund Processing.';
                btnColor = '#d33';
                btnText = 'Yes, Cancel it!';
            } else if (type === 'confirm') {
                title = 'Confirm Booking?';
                text = 'Approve this booking?';
                btnColor = '#3085d6';
                btnText = 'Yes, Confirm!';
            } else if (type === 'refund') {
                title = 'Mark Refund Complete?';
                text = 'Confirm that you have manually returned the money.';
                btnColor = '#343a40';
                btnText = 'Yes, Refunded!';
            }

            Swal.fire({
                title: title, text: text, icon: 'warning',
                showCancelButton: true, confirmButtonColor: btnColor,
                cancelButtonColor: '#6c757d', confirmButtonText: btnText
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        }
    </script>
</body>
</html>
<?php } ?>