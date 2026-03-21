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
    // --- Delete User Logic ---
    if(isset($_GET['del']))
    {
        $id=$_GET['del'];
        $sql = "DELETE FROM tblusers WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query -> bindParam(':id',$id, PDO::PARAM_STR);
        $query -> execute();
        $msg="User Record deleted successfully";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Registered Users</title>
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
        .table-custom td { vertical-align: middle; color: #2c3e50; font-size: 0.95rem; }
        
        /* Delete Button Style */
        .btn-delete { 
            background-color: #f8d7da; 
            color: #721c24; 
            width: 35px; 
            height: 35px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 6px; 
            transition: 0.3s; 
            border: none;
            text-decoration: none;
        }
        .btn-delete:hover { 
            background-color: #dc3545; 
            color: white; 
            transform: scale(1.1); 
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        .badge-id { background-color: #e9ecef; color: #495057; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 2px; }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container-fluid px-4">
        
        <div class="page-header">
            <h2>Registered Users</h2>
        </div>

        <?php if($msg){ ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?php echo htmlentities($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fa fa-users me-2"></i> User Details
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Name</th>
                                <th width="20%">Identity / License</th> <th width="20%">Email / Contact</th>
                                <th width="20%">Address</th>
                                <th width="10%">Reg Date</th>
                                <th width="10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * from tblusers ORDER BY RegDate DESC";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle p-2 me-2 text-secondary"><i class="fa fa-user"></i></div>
                                            <strong><?php echo htmlentities($result->FullName);?></strong>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if($result->IcNo) { ?>
                                            <div class="small"><strong>IC:</strong> <?php echo htmlentities($result->IcNo);?></div>
                                        <?php } else { ?>
                                            <span class="text-muted small">- No IC -</span><br>
                                        <?php } ?>

                                        <?php if($result->LicenseNo) { ?>
                                            <div class="small text-muted"><strong>Lic:</strong> <?php echo htmlentities($result->LicenseNo);?></div>
                                            <div class="small text-danger" style="font-size: 0.8rem;">
                                                Exp: <?php echo htmlentities($result->LicenseExpDate);?>
                                            </div>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <div class="text-primary small mb-1"><i class="fa fa-envelope me-1"></i> <?php echo htmlentities($result->EmailId);?></div>
                                        <div class="text-muted small"><i class="fa fa-phone me-1"></i> <?php echo htmlentities($result->ContactNo);?></div>
                                    </td>
                                    <td>
                                        <small class="d-block text-truncate" style="max-width: 200px;">
                                            <?php echo htmlentities($result->Address);?>
                                        </small>
                                        <small class="text-muted"><?php echo htmlentities($result->City);?></small>
                                    </td>
                                    <td><small class="text-muted"><?php echo htmlentities($result->RegDate);?></small></td>
                                    
                                    <td class="text-center">
                                        <a href="reg-users.php?del=<?php echo htmlentities($result->id);?>" 
                                           onclick="return confirmDelete(event, this.href)"
                                           class="btn-delete" title="Delete User">
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
            e.preventDefault(); // Stop the link from working immediately

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this user? This process cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Red for danger
                cancelButtonColor: '#6c757d', // Grey for cancel
                confirmButtonText: 'Yes, delete user!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Proceed to PHP delete URL
                    window.location.href = url;
                }
            });
        }
    </script>
</body>
</html>
<?php } ?>