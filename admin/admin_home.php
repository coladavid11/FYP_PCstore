<?php
ob_start();
include ('includes/config.php');
session_start();
$admin_id= $_SESSION['admin_id'];
    $admin_name= $_SESSION['admin_name'];

    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];
    } else {
        header("refresh:0.1;url='admin_login.php'");
    }
    
    if (isset($_SESSION['admin_name'])) {
        $admin_name = $_SESSION['admin_name'];
    } else {
        header("refresh:0.1;url='admin_login.php'");
    }

?>
 
<!doctype html>
<html lang="en">
 
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Home Page</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <link href="assets/vendor/fonts/circular-std/style.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/libs/css/style.css">
    <link rel="stylesheet" href="assets/vendor/fonts/fontawesome/css/fontawesome-all.css">

</head>
<style>
    label 
	{
    float: left;
    width: 100px;
    padding-right: 24px;
	}
 
	input
	{
    float: left;
	}
    .custom-ecommerce-widget {
        margin: 0px auto 20 auto;
}
</style>
<body>
<div class="dashboard-main-wrapper">
        <div class="dashboard-header">
            <nav class="navbar navbar-expand-lg bg-white fixed-top">
                <div class="logo">
					<a href="admin_home.php">
					<img src="Picture/fypLogo.jpeg">
					</a>
                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse " id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-right-top">
                        <li class="nav-item dropdown nav-user">
                            <a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<img src="Picture/setting.png" alt="" class="user-avatar-md rounded-circle"></a>
                            <div class="dropdown-menu dropdown-menu-right nav-user-dropdown" aria-labelledby="navbarDropdownMenuLink2">
                                <a class="dropdown-item" href="logout.php"><i class="fas fa-power-off mr-2"></i>Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <div class="nav-left-sidebar sidebar-dark">
            <div class="menu-list">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav flex-column">
                            <li class="nav-divider">
                                Menu
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-16" aria-controls="submenu-5"><i class="fa fa-address-card"></i>Super Admin</a>
                                <div id="submenu-16" class="collapse submenu" style="">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="spadmin_login.php">Admin Management</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
							<li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-11" aria-controls="submenu-5"><i class="fa fa-fw fa-user-circle"></i></i>User</a>
                                <div id="submenu-11" class="collapse submenu" style="">																			
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="View_User.php">View User List</a>
                                        </li>  
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-13" aria-controls="submenu-5"><i class="fa fa-server"></i>Categories</a>
                                <div id="submenu-13" class="collapse submenu" style="">
                                    <ul class="nav flex-column">
										<li class="nav-item">
                                            <a class="nav-link" href="Add_Category.php">Add Category</a>
                                        </li>
										<li class="nav-item">
                                            <a class="nav-link" href="View_Category.php">View Category</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-14" aria-controls="submenu-5"><i class="fa fa-desktop"></i>Products</a>
                                <div id="submenu-14" class="collapse submenu" style="">
                                    <ul class="nav flex-column">
										<li class="nav-item">
                                            <a class="nav-link" href="admin(add).php">Add Product</a>
                                        </li>
										<li class="nav-item">
                                            <a class="nav-link" href="View_Product.php">View Product</a>
                                        </li>
										<li class="nav-item">
                                            <a class="nav-link" href="View_Product_Review.php">View Product Review</a>
                                        </li>  
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-12" aria-controls="submenu-5"><i class="fa fa-truck"></i>Order</a>
                                <div id="submenu-12" class="collapse submenu" style="">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="View_Order.php">View Order</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false" data-target="#submenu-15" aria-controls="submenu-5"><i class="fas fa-fw fa-table"></i>Report</a>
                                <div id="submenu-15" class="collapse submenu" style="">
                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="View_Report.html">View Report</a>
                                        </li>                
                                    </ul>
                                </div>
                            </li>					
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- end left sidebar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- wrapper  -->
        <!-- ============================================================== -->
        <div class="dashboard-wrapper">
            <div class="dashboard-ecommerce">
                <div class="container-fluid dashboard-content ">
                    <!-- ============================================================== -->
                    <!-- pageheader  -->
                    <!-- ============================================================== -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                            <div class="page-header">
                                <h2 class="pageheader-title">Guardian Gaming Admin Dashboard</h2>
                                <p class="pageheader-text">Nulla euismod urna eros, sit amet scelerisque torton lectus vel mauris facilisis faucibus at enim quis massa lobortis rutrum.</p>
                                <div class="page-breadcrumb">
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a class="breadcrumb-link">Dashboard</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Guardian Gaming Admin Dashboard</li>
                                        </ol>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ecommerce-widget">
                        <div class="row">      
                            <!-- ============================================================== -->
                            <!-- sales pie chart -->
                            <!-- ============================================================== -->
                            <div class="ecommerce-widget custom-ecommerce-widget">
                            <div class="row">
                                <!-- Include the pie chart from piechart.php -->
                                <?php include("piechart.php"); ?>
                            </div>
                        </div>
    
                        <div id="piechart" style="width: 0px; height: 560px;"></div>
							<!-- ============================================================== -->
                            <!-- sales  -->
                            <!-- ============================================================== -->
                            <?php
                            $result = mysqli_query($con,"SELECT * FROM orders");
                            $sales = 0;
                            while($row = mysqli_fetch_assoc($result))
                            {
                                $sales += $row['total'];
                            }
                            ?>						
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="card border-3 border-top border-top-primary">
                                    <div class="card-body">
                                        <h5 class="text-muted">Sales</h5>
                                        <div class="metric-value d-inline-block">
                                            <h1 class="mb-1"><?php echo "RM ".number_format($sales,2);?></h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
							  <!-- ============================================================== -->
                            <!-- new customer  -->
                            <!-- ============================================================== -->
							<?php
                            $result = mysqli_query($con,"SELECT count(*) as total_users FROM users;");
                            $row = mysqli_fetch_assoc($result);
                            $total_users = $row['total_users'];
                            ?>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="card border-3 border-top border-top-primary">
                                    <div class="card-body">
                                        <h5 class="text-muted">Total Users</h5>
                                        <div class="metric-value d-inline-block">
                                            <h1 class="mb-1"><?php echo $total_users;?></h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
							  <!-- ============================================================== -->
                            <!-- total product  -->
                            <!-- ============================================================== -->
							<?php
                            $result = mysqli_query($con,"SELECT count(*) as total_product FROM products;");
                            $row = mysqli_fetch_assoc($result);
                            $total_product = $row['total_product'];
                            ?>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="card border-3 border-top border-top-primary">
                                    <div class="card-body">
                                        <h5 class="text-muted">Total Product</h5>
                                        <div class="metric-value d-inline-block">
                                            <h1 class="mb-1"><?php echo $total_product;?></h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
							  <?php
                            $result = mysqli_query($con,"SELECT count(*) as total_order FROM orders;");
                            $row = mysqli_fetch_assoc($result);
                            $total_order = $row['total_order'];
                            ?>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                <div class="card border-3 border-top border-top-primary">
                                    <div class="card-body">
                                        <h5 class="text-muted">Total Order Deals</h5>
                                        <div class="metric-value d-inline-block">
                                            <h1 class="mb-1"><?php echo $total_order;?></h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-9 col-lg-12 col-md-6 col-sm-12 col-12">
                                <div class="card">
                                    <h5 class="card-header">Recent Orders</h5>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead class="bg-light">
                                                    <tr class="border-0">
                                                        <th class="border-0">#</th>
                                                        <th class="border-0">Order Number</th>
                                                        <th class="border-0">Total</th>
                                                        <th class="border-0">Made By</th>
                                                        <th class="border-0">Order Status</th> 
														<th class="border-0">Placed On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                            $result = mysqli_query($con,"SELECT * FROM orders o INNER JOIN users u ON o.user_id = u.user_id;");
                                            if(mysqli_num_rows($result)>0)
                                            {
                                                $count = 1;
                                                while($row = mysqli_fetch_assoc($result))
                                                {
                                                ?>
                                                    <tr>
                                                        <td><?php echo $count;?></td>
                                                        <td><?php echo $row['order_number'];?></td>
                                                        <td><?php echo "RM ".number_format($row['total'],2);?></td>
                                                        <td><?php echo $row['User_First_Name'];?></td>
                                                        <td>
                                                            <?php
                                                                if($row['order_status'] == "Order Received")
                                                                {
                                                                    ?>
                                                                    <span class="badge-dot badge-info mr-1"></span>
                                                                    <?php
                                                                }
                                                                else if($row['order_status'] == "In Transit")
                                                                {
                                                                    ?>
                                                                    <span class="badge-dot badge-brand mr-1"></span>
                                                                    <?php
                                                                }
                                                                else if($row['order_status'] == "Delivered")
                                                                {
                                                                    ?>
                                                                    <span class="badge-dot badge-success mr-1"></span>
                                                                    <?php
                                                                }
                                                                echo $row['order_status'];
                                                            ?>
                                                            
                                                        </td>
                                                        <td><?php echo $row['created_date'];?></td>
                                                        
                                                    </tr>
                                                <?php
                                                $count++;
                                                }
                                            }
                                            else
                                            {
                                                ?>
                                                <tr>
                                                    <td colspan="6" style="text-align: center;">There is no record(s) in this table</td>
                                                </tr>
                                                <?php
                                            }
                                            
                                        ?>
                                                        
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer" style="position: absolute; bottom:0;">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
                            <a>Copyright © Guardian Gaming, 2024</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Optional JavaScript -->
    <!-- jquery 3.3.1 -->
    <script src="assets/vendor/jquery/jquery-3.3.1.min.js"></script>
    <!-- bootstap bundle js -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <!-- slimscroll js -->
    <script src="assets/vendor/slimscroll/jquery.slimscroll.js"></script>
    <!-- main js -->
    <script src="assets/libs/js/main-js.js"></script>
    <!-- chart chartist js -->
    <script src="assets/vendor/charts/chartist-bundle/chartist.min.js"></script>
    <!-- sparkline js -->
    <script src="assets/vendor/charts/sparkline/jquery.sparkline.js"></script>
    <!-- morris js -->
    <script src="assets/vendor/charts/morris-bundle/raphael.min.js"></script>
    <script src="assets/vendor/charts/morris-bundle/morris.js"></script>
    <!-- chart c3 js -->
    <script src="assets/vendor/charts/c3charts/c3.min.js"></script>
    <script src="assets/vendor/charts/c3charts/d3-5.4.0.min.js"></script>
    <script src="assets/vendor/charts/c3charts/C3chartjs.js"></script>
    <script src="assets/libs/js/dashboard-ecommerce.js"></script>
    
</body>
 
</html>