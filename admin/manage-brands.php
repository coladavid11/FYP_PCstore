<?php
session_start();
error_reporting(0);
include('../includes/config.php');

if(strlen($_SESSION['alogin'])==0)
{   
    header('location:index.php');
}
else{
    // --- Delete Brand Logic ---
    if(isset($_GET['del']))
    {
        $id=$_GET['del'];
        $sql = "delete from tblbrands WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query -> bindParam(':id',$id, PDO::PARAM_STR);
        $query -> execute();
        $msg="Brand Deleted Successfully";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Brands</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">

    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding-top: 80px; }
        .page-header { border-left: 5px solid #2c3e50; padding-left: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { font-weight: 800; color: #2c3e50; margin: 0; }
        
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 30px; }
        .card-header-custom { background-color: #2c3e50; color: white; padding: 15px 20px; font-weight: 600; }
        
        .table-custom th { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; color: #7f8c8d; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        .table-custom td { vertical-align: middle; color: #2c3e50; font-size: 0.95rem; }
        
        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: 0.3s; border: none; margin-right: 5px; text-decoration: none; }
        .btn-edit { background-color: #e7f1ff; color: #0d6efd; }
        .btn-delete { background-color: #ffeaea; color: #e74c3c; }
        .btn-delete:hover { background-color: #e74c3c; color: white; transform: translateY(-2px); }
        
        .btn-create { background-color: #2c3e50; color: white; border-radius: 6px; padding: 8px 20px; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-create:hover { background-color: #34495e; color: #f1c40f; }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>

    <div class="container-fluid px-4">
        <div class="page-header">
            <h2>Manage Brands</h2>
            <a href="create-brand.php" class="btn-create"><i class="fa fa-plus"></i> Create New</a>
        </div>

        <?php if($msg){ ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?></div><?php } ?>

        <div class="card card-custom">
            <div class="card-header-custom"><i class="fa fa-table"></i> Brand Details</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Brand Name</th>
                                <th>Creation Date</th>
                                <th>Last Update</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * from tblbrands";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            $cnt=1;
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { ?>
                                <tr>
                                    <td><?php echo htmlentities($cnt);?></td>
                                    <td><strong><?php echo htmlentities($result->BrandName);?></strong></td>
                                    <td><?php echo htmlentities($result->CreationDate);?></td>
                                    <td><?php echo htmlentities($result->UpdationDate);?></td>
                                    <td>
                                        <a href="edit-brand.php?id=<?php echo htmlentities($result->id);?>" class="btn-action btn-edit"><i class="fa fa-edit"></i></a>
                                        <a href="manage-brands.php?del=<?php echo htmlentities($result->id);?>" onclick="return confirmDelete(event, this.href)" class="btn-action btn-delete"><i class="fa fa-trash"></i></a>
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
        function confirmDelete(e, url) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this brand?",
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