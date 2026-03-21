<?php
session_start();
error_reporting(0);
include('../includes/config.php');

if(strlen($_SESSION['alogin'])==0)
{   
    header('location:index.php');
}
else{
    // --- Logic: Handle "Mark as Paid" Action ---
    if(isset($_REQUEST['eid']))
    {
        $eid=intval($_GET['eid']);
        $status=1; // 1 = Paid
        $sql = "UPDATE tblbooking SET payment_status=:status WHERE id=:eid";
        $query = $dbh->prepare($sql);
        $query -> bindParam(':status',$status, PDO::PARAM_STR);
        $query-> bindParam(':eid',$eid, PDO::PARAM_STR);
        $query -> execute();
        $msg="Payment Successfully Updated";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">

    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding-top: 80px; }
        
        /* Page Header Style */
        .page-header { border-left: 5px solid #27ae60; padding-left: 15px; margin-bottom: 30px; }
        .page-header h2 { font-weight: 800; color: #2c3e50; margin: 0; }
        
        /* Card & Table Styles */
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header-custom { background-color: #2c3e50; color: white; padding: 15px 20px; font-weight: 600; }
        
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; color: #7f8c8d; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        .table-custom td { vertical-align: middle; color: #2c3e50; font-size: 0.95rem; }
        
        /* Status Badges */
        .badge-unpaid { background-color: #f8d7da; color: #721c24; padding: 8px 12px; border-radius: 6px; font-weight: 600; }
        .badge-paid { background-color: #d4edda; color: #155724; padding: 8px 12px; border-radius: 6px; font-weight: 600; }
        
        /* 🔥 Button Style (Fixed layout issue) */
        .btn-mark-paid { 
            background-color: #198754; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 5px; 
            font-size: 0.85rem; 
            transition: 0.3s;
            white-space: nowrap; /* Prevents text wrapping */
            display: inline-block;
            text-decoration: none;
        }
        .btn-mark-paid:hover { background-color: #157347; color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(25, 135, 84, 0.2); }
        
        .days-badge { background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; color: #495057; }
        .total-price { font-weight: 700; color: #2c3e50; }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container-fluid px-4">
        
        <div class="page-header">
            <h2>Manage Payments</h2>
        </div>

        <?php if($msg){ ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?php echo htmlentities($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fa fa-money-bill-wave me-2"></i> Payments List
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Customer</th>
                                <th width="20%">Vehicle</th>
                                <th width="15%">Dates</th>
                                <th width="10%">Total Days</th>
                                <th width="10%">Price/Day</th>
                                <th width="10%">Est. Total</th>
                                <th width="8%">Status</th>
                                <th width="12%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // SQL Query: Join Booking + Users + Vehicles + Brands tables
                            $sql = "SELECT tblusers.FullName,tblvehicles.VehiclesTitle,tblvehicles.PricePerDay,tblbrands.BrandName,tblbooking.FromDate,tblbooking.ToDate,tblbooking.payment_status,tblbooking.id from tblbooking join tblvehicles on tblvehicles.id=tblbooking.VehicleId join tblusers on tblusers.EmailId=tblbooking.userEmail join tblbrands on tblvehicles.VehiclesBrand=tblbrands.id order by tblbooking.id desc";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { 
                                    // --- Auto Calculate Days & Price ---
                                    $fdate = strtotime($result->FromDate);
                                    $tdate = strtotime($result->ToDate);
                                    $datediff = $tdate - $fdate;
                                    $days = round($datediff / (60 * 60 * 24)); // Calculate days difference
                                    if($days == 0) { $days = 1; } // Minimum 1 day rental
                                    
                                    $total_price = $days * ($result->PricePerDay);
                            ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td><strong><?php echo htmlentities($result->FullName);?></strong></td>
                                    <td><?php echo htmlentities($result->BrandName);?> <?php echo htmlentities($result->VehiclesTitle);?></td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlentities($result->FromDate);?></small><br>
                                        <small class="text-muted"><?php echo htmlentities($result->ToDate);?></small>
                                    </td>
                                    <td><span class="days-badge"><?php echo htmlentities($days);?> Days</span></td>
                                    <td>RM <?php echo htmlentities($result->PricePerDay);?></td>
                                    <td class="total-price">RM <?php echo number_format($total_price, 2);?></td>
                                    
                                    <td>
                                        <?php if($result->payment_status == 1) { ?>
                                            <span class="badge-paid">Paid</span>
                                        <?php } else { ?>
                                            <span class="badge-unpaid">Unpaid</span>
                                        <?php } ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if($result->payment_status == 0) { ?>
                                            <a href="manage-payments.php?eid=<?php echo htmlentities($result->id);?>" 
                                               onclick="return confirmPayment(event, this.href)" 
                                               class="btn-mark-paid">
                                               <i class="fa fa-check"></i> Mark Paid
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-muted small"><i class="fa fa-check-circle text-success"></i> Completed</span>
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
        // SweetAlert2 Logic for Payment Confirmation Popup
        function confirmPayment(e, url) {
            e.preventDefault(); // Stop default link click

            Swal.fire({
                title: 'Confirm Payment?',
                text: 'Are you sure you have received the payment for this booking?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60', // Green for Yes
                cancelButtonColor: '#6c757d', // Grey for Cancel
                confirmButtonText: 'Yes, Mark as Paid!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to PHP update URL if confirmed
                    window.location.href = url;
                }
            });
        }
    </script>
</body>
</html>
<?php } ?>