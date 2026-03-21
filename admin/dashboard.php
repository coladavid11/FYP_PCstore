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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
            padding-top: 0; 
        }

        .main-content {
            margin-top: 30px;
        }

        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border-bottom: 5px solid #f1c40f; /* Yellow Accent */
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .bg-users { background-color: #e3f2fd; color: #2196f3; }
        .bg-vehicles { background-color: #fff3e0; color: #ff9800; }
        .bg-bookings { background-color: #e8f5e9; color: #4caf50; }

        .stat-number { font-size: 2.5rem; font-weight: 700; color: #2c3e50; line-height: 1; }
        .stat-label { color: #95a5a6; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

        .quick-action-btn {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: 0.3s;
            color: #2c3e50;
            text-decoration: none;
            display: block;
        }
        .quick-action-btn:hover { background: #2c3e50; color: #f1c40f; transform: translateY(-3px); }
        .qa-icon { font-size: 2rem; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="container main-content">
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <h1 class="fw-bold" style="color: #2c3e50;">Welcome back, Admin!</h1>
                    <p class="text-muted">Here's what's happening in your car rental system today.</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <?php 
                        $sql = "SELECT id from tblusers";
                        $query = $dbh -> prepare($sql);
                        $query->execute();
                        $users_cnt = $query->rowCount();
                        ?>
                        <div class="stat-number"><?php echo htmlentities($users_cnt);?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-icon bg-users"><i class="fa fa-users"></i></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <?php 
                        $sql2 = "SELECT id from tblvehicles";
                        $query2 = $dbh -> prepare($sql2);
                        $query2->execute();
                        $vehicles_cnt = $query2->rowCount();
                        ?>
                        <div class="stat-number"><?php echo htmlentities($vehicles_cnt);?></div>
                        <div class="stat-label">Total Vehicles</div>
                    </div>
                    <div class="stat-icon bg-vehicles"><i class="fa fa-car"></i></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <?php 
                        $sql3 = "SELECT id from tblbooking";
                        $query3 = $dbh -> prepare($sql3);
                        $query3->execute();
                        $bookings_cnt = $query3->rowCount();
                        ?>
                        <div class="stat-number"><?php echo htmlentities($bookings_cnt);?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-icon bg-bookings"><i class="fa fa-calendar-check"></i></div>
                </div>
            </div>

        </div>

        <h4 class="fw-bold mb-3" style="color: #2c3e50;"><i class="fa fa-bolt text-warning"></i> Quick Actions</h4>
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <a href="post-avehicle.php" class="quick-action-btn">
                    <span class="qa-icon"><i class="fa fa-plus-circle"></i></span>
                    Post Vehicle
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="manage-bookings.php" class="quick-action-btn">
                    <span class="qa-icon"><i class="fa fa-list-alt"></i></span>
                    Manage Bookings
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="create-brand.php" class="quick-action-btn">
                    <span class="qa-icon"><i class="fa fa-tag"></i></span>
                    Add Brand
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="reg-users.php" class="quick-action-btn">
                    <span class="qa-icon"><i class="fa fa-users-cog"></i></span>
                    View Users
                </a>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>