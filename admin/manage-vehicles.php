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
    // Delete Logic
    if(isset($_REQUEST['del']))
    {
        $delid=intval($_GET['del']);
        $sql = "delete from tblvehicles WHERE id=:delid";
        $query = $dbh->prepare($sql);
        $query -> bindParam(':delid',$delid, PDO::PARAM_STR);
        $query -> execute();
        $msg="Vehicle record deleted successfully";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Vehicles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">

    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding-top: 80px; }
        
        .page-header { border-left: 5px solid #2c3e50; padding-left: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { font-weight: 800; color: #2c3e50; margin: 0; }
        
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header-custom { background-color: #2c3e50; color: white; padding: 15px 20px; font-weight: 600; }
        
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; color: #7f8c8d; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        .table-custom td { vertical-align: middle; color: #2c3e50; font-size: 0.95rem; }
        
        .badge-brand { background-color: #e9ecef; color: #495057; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        
        /* Status Badges for Road Tax */
        .badge-expired { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
        .badge-warning { background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
        .badge-ok { background-color: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }

        /* Action Buttons */
        .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: 0.3s; border: none; margin-right: 5px; text-decoration: none; }
        .btn-edit { background-color: #e7f1ff; color: #0d6efd; }
        .btn-edit:hover { background-color: #0d6efd; color: white; transform: translateY(-2px); }
        .btn-delete { background-color: #ffeaea; color: #e74c3c; }
        .btn-delete:hover { background-color: #e74c3c; color: white; transform: translateY(-2px); }
        .btn-post-new { background-color: #2c3e50; color: white; border-radius: 8px; padding: 10px 20px; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-post-new:hover { background-color: #34495e; color: #f1c40f; transform: translateY(-2px); }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container-fluid px-4">
        
        <div class="page-header">
            <div>
                <h2>Manage Vehicles</h2>
            </div>
            <a href="post-avehicle.php" class="btn-post-new"><i class="fa fa-plus-circle"></i> Post New Vehicle</a>
        </div>

        <?php if($msg){ ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?php echo htmlentities($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fa fa-car me-2"></i> Vehicle Details
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Vehicle Title</th>
                                <th width="15%">Brand</th>
                                <th width="15%">Price / Day</th>
                                <th width="15%">Road Tax Status</th> <th width="15%">Model Year</th>
                                <th width="15%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Select query including the new date column
                            $sql = "SELECT tblvehicles.VehiclesTitle,tblbrands.BrandName,tblvehicles.PricePerDay,tblvehicles.FuelType,tblvehicles.ModelYear,tblvehicles.RoadTaxExpDate,tblvehicles.id from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand ORDER BY tblvehicles.id DESC";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { 
                                    // 🔥 Road Tax Expiry Logic
                                    $expiry = new DateTime($result->RoadTaxExpDate);
                                    $today = new DateTime();
                                    $diff = $today->diff($expiry);
                                    $days_left = (int)$diff->format("%r%a"); // %r gives sign (- for expired)
                            ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td><strong><?php echo htmlentities($result->VehiclesTitle);?></strong></td>
                                    <td><span class="badge-brand"><?php echo htmlentities($result->BrandName);?></span></td>
                                    <td>RM <?php echo htmlentities($result->PricePerDay);?></td>
                                    
                                    <td>
                                        <?php if($result->RoadTaxExpDate) { ?>
                                            <?php if($days_left < 0) { ?>
                                                <span class="badge-expired">EXPIRED!</span>
                                            <?php } elseif($days_left < 30) { ?>
                                                <span class="badge-warning">Expiring in <?php echo $days_left; ?> days</span>
                                            <?php } else { ?>
                                                <span class="badge-ok">Valid (<?php echo $days_left; ?> days)</span>
                                            <?php } ?>
                                            <div class="small text-muted mt-1"><?php echo htmlentities($result->RoadTaxExpDate); ?></div>
                                        <?php } else { ?>
                                            <span class="text-muted small">- Not Set -</span>
                                        <?php } ?>
                                    </td>

                                    <td><?php echo htmlentities($result->ModelYear);?></td>
                                    
                                    <td class="text-center">
                                        <a href="edit-vehicle.php?id=<?php echo htmlentities($result->id);?>" class="btn-action btn-edit" title="Edit Vehicle">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <a href="manage-vehicles.php?del=<?php echo htmlentities($result->id);?>" 
                                           onclick="return confirmDelete(event, this.href)"
                                           class="btn-action btn-delete" title="Delete Vehicle">
                                            <i class="fa fa-trash-alt"></i>
                                        </a>
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
        // SweetAlert2 Delete Logic
        function confirmDelete(e, url) {
            e.preventDefault(); // Stop link execution

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this vehicle? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Red button
                cancelButtonColor: '#6c757d', // Grey button
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to PHP delete URL
                    window.location.href = url;
                }
            });
        }
    </script>
</body>
</html>
<?php } ?>