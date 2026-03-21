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
    // --- Update Brand Logic ---
    if(isset($_POST['submit']))
    {
        $brand = $_POST['brand'];
        $id = $_GET['id']; // Get ID from URL
        
        $sql = "UPDATE tblbrands SET BrandName=:brand, UpdationDate=CURRENT_TIMESTAMP WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':brand',$brand,PDO::PARAM_STR);
        $query->bindParam(':id',$id,PDO::PARAM_STR);
        $query->execute();
        
        $msg="Brand Updated Successfully";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Edit Brand</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding-top: 100px; }
        .form-card {
            background: white; border-radius: 12px; padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin: 0 auto; max-width: 600px;
            border-top: 4px solid #f1c40f;
        }
        h2 { font-weight: 700; color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
        .btn-custom { background-color: #2c3e50; color: white; border: none; padding: 10px 25px; transition: 0.3s; }
        .btn-custom:hover { background-color: #f1c40f; color: #fff; }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                
                <div class="form-card">
                    <h2><i class="fa fa-edit"></i> Edit Brand</h2>

                    <?php if(isset($msg)){ ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fa fa-check-circle"></i> <?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>

                    <?php 
                    // Fetch existing data
                    $id = intval($_GET['id']);
                    $sql = "SELECT * from tblbrands where id=:id";
                    $query = $dbh -> prepare($sql);
                    $query->bindParam(':id',$id, PDO::PARAM_STR);
                    $query->execute();
                    $results=$query->fetchAll(PDO::FETCH_OBJ);
                    
                    if($query->rowCount() > 0)
                    {
                        foreach($results as $result)
                        { ?>
                            <form method="post">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Brand Name</label>
                                    <input type="text" name="brand" class="form-control form-control-lg" value="<?php echo htmlentities($result->BrandName);?>" required>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="manage-brands.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button class="btn btn-custom" name="submit" type="submit">Update Brand</button>
                                </div>
                            </form>
                        <?php }} ?>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>