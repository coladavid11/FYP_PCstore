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
    // --- 1. Toggle Visibility Logic (Show/Hide) ---
    if(isset($_GET['aeid']))
    {
        $id = intval($_GET['aeid']);
        $status = intval($_GET['status']); // 1=Currently Visible, 0=Currently Hidden
        
        $newStatus = ($status == 1) ? 0 : 1;
        $msg = ($newStatus == 1) ? "Review is now Visible!" : "Review is now Hidden!";

        $sql = "UPDATE tblreviews SET status=:status WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':status', $newStatus, PDO::PARAM_INT);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();
    }

    // --- 2. Delete Review Logic ---
    if(isset($_GET['del']))
    {
        $id = intval($_GET['del']);
        $sql = "DELETE FROM tblreviews WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();
        $msg = "Review deleted successfully";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Reviews</title>
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
        
        .badge-status { padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .bg-active { background-color: #d1e7dd; color: #0f5132; }
        .bg-inactive { background-color: #f8d7da; color: #842029; }

        .star-rating { color: #f1c40f; font-size: 0.85rem; }
        
        .btn-action { 
            width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; 
            border-radius: 6px; transition: 0.3s; border: none; margin-right: 5px; text-decoration: none; 
        }
        .btn-toggle { background-color: #e7f1ff; color: #0d6efd; }
        .btn-toggle:hover { background-color: #0d6efd; color: white; transform: translateY(-2px); }
        
        .btn-delete { background-color: #ffeaea; color: #e74c3c; }
        .btn-delete:hover { background-color: #e74c3c; color: white; transform: translateY(-2px); }
        
        .comment-box { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container-fluid px-4">
        
        <div class="page-header">
            <h2>Manage Reviews</h2>
        </div>

        <?php if($msg){ ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?php echo htmlentities($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fa fa-comments me-2"></i> Customer Feedback
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Vehicle</th>
                                <th width="15%">User Email</th>
                                <th width="10%">Rating</th>
                                <th width="30%">Comment</th>
                                <th width="10%">Status</th>
                                <th width="10%">Date</th>
                                <th width="10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Join Query: Fetch Reviews + Vehicle Name
                            $sql = "SELECT tblreviews.*, tblvehicles.VehiclesTitle 
                                    FROM tblreviews 
                                    JOIN tblvehicles ON tblreviews.VehicleId = tblvehicles.id 
                                    ORDER BY tblreviews.created_at DESC";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td><strong><?php echo htmlentities($result->VehiclesTitle);?></strong></td>
                                    <td><?php echo htmlentities($result->userEmail);?></td>
                                    <td>
                                        <div class="star-rating">
                                            <?php 
                                            for($i=1; $i<=5; $i++) {
                                                echo ($i <= $result->rating) ? '<i class="fa fa-star"></i>' : '<i class="far fa-star text-secondary"></i>';
                                            }
                                            ?>
                                            <span class="text-dark ms-1 small">(<?php echo htmlentities($result->rating);?>)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="comment-box" title="<?php echo htmlentities($result->comment);?>">
                                            <?php echo htmlentities($result->comment);?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($result->status == 1) { ?>
                                            <span class="badge badge-status bg-active">Visible</span>
                                        <?php } else { ?>
                                            <span class="badge badge-status bg-inactive">Hidden</span>
                                        <?php } ?>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlentities($result->created_at);?></td>
                                    
                                    <td class="text-center">
                                        <a href="manage-reviews.php?aeid=<?php echo htmlentities($result->id);?>&status=<?php echo htmlentities($result->status);?>" 
                                           class="btn-action btn-toggle" 
                                           title="<?php echo ($result->status==1)?'Hide Review':'Show Review';?>">
                                            <i class="fa <?php echo ($result->status==1)?'fa-eye-slash':'fa-eye';?>"></i>
                                        </a>

                                        <a href="manage-reviews.php?del=<?php echo htmlentities($result->id);?>" 
                                           onclick="return confirmDelete(event, this.href)"
                                           class="btn-action btn-delete" title="Delete Review">
                                            <i class="fa fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php $cnt=$cnt+1; }} else { ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No reviews found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmDelete(e, url) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Delete Review?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    </script>
</body>
</html>
<?php } ?>