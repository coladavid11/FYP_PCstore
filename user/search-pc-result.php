<?php 
session_start();
include('includes/config.php');
error_reporting(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results | Premium Fleet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f0f0f; /* Ultra Dark Background */
            color: #fff;
        }

        /* --- HEADER SECTION --- */
        .page-header {
            background-color: #000;
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(15,15,15,1)), url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            color: #fff;
            text-shadow: 0 5px 20px rgba(0,0,0,0.9);
            margin-bottom: 10px;
        }
        .page-header span {
            color: #d4af37;
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* --- BACK BUTTON STYLE --- */
        .btn-back-link {
            color: #888;
            text-decoration: none;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            margin-bottom: 30px;
            border: 1px solid #333;
            padding: 10px 20px;
            border-radius: 50px;
            background: #111;
        }
        .btn-back-link:hover {
            color: #000;
            background: #d4af37;
            border-color: #d4af37;
            transform: translateX(-5px);
        }
        .btn-back-link i { margin-right: 10px; }

        /* --- CAR CARD STYLING --- */
        .car-card {
            background: #181818;
            border: 1px solid #2a2a2a;
            border-radius: 0; 
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.2, 1, 0.2, 1);
            margin-bottom: 30px;
            position: relative;
        }
        .car-card:hover {
            transform: translateY(-10px);
            border-color: #444;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .img-wrapper {
            position: relative;
            height: 260px;
            overflow: hidden;
        }
        .img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s;
            filter: brightness(0.9);
        }
        .car-card:hover .img-wrapper img {
            transform: scale(1.08);
            filter: brightness(1.1);
        }

        .price-tag-floating {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #d4af37;
            color: #000;
            padding: 8px 20px;
            font-weight: 700;
            font-size: 1rem;
        }

        .card-info { padding: 30px; }

        .car-name {
            color: #fff;
            font-size: 1.5rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 5px;
        }
        
        .brand-label {
            color: #666;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: block;
            margin-bottom: 25px;
        }

        .specs-row {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #333;
            padding-top: 20px;
            color: #aaa;
            font-size: 0.85rem;
        }
        .specs-item i { color: #d4af37; margin-right: 8px; }

        .btn-view-arrow {
            display: inline-block;
            margin-top: 25px;
            color: #fff;
            text-decoration: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: 0.3s;
            border-bottom: 1px solid transparent;
        }
        .btn-view-arrow:hover {
            color: #d4af37;
            border-bottom: 1px solid #d4af37;
            padding-bottom: 3px;
        }
        .btn-view-arrow i { font-size: 0.7rem; margin-left: 5px; }

        /* No Result Styling */
        .no-result-box {
            text-align: center;
            padding: 80px 20px;
            border: 1px solid #2a2a2a;
            background: #111;
            margin-top: 20px;
        }
        .btn-gold-outline {
            border: 1px solid #d4af37;
            color: #d4af37;
            background: transparent;
            padding: 10px 30px;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 0.9rem;
            transition: 0.3s;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-gold-outline:hover {
            background: #d4af37;
            color: #000;
        }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <div class="page-header">
        <div>
            <h1>Search Results</h1>
            <span>Find your perfect match</span>
        </div>
    </div>

    <section class="pb-5">
        <div class="container">
            
            <div class="row">
                <div class="col-12">
                    <a href="car-listing.php" class="btn-back-link">
                        <i class="fa fa-arrow-left"></i> Back to Inventory
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <?php 
                // --- SEARCH LOGIC ---
                // Receive inputs from car-listing.php
                $brand = $_POST['brand'];
                $fueltype = $_POST['fueltype'];
                // Logic Fix Included (VehicleType)
                $vehicletype = $_POST['vehicletype'];

                // Start building the query
                $sql = "SELECT tblvehicles.*, tblbrands.BrandName, tblbrands.id as bid FROM tblvehicles JOIN tblbrands ON tblbrands.id = tblvehicles.VehiclesBrand";
                
                $conditions = [];
                $params = [];

                // 1. Filter by Brand (Ignore if 'All Brands' or empty)
                if ($brand != "All Brands" && $brand != "") {
                    $conditions[] = "tblvehicles.VehiclesBrand = :brand";
                    $params[':brand'] = $brand;
                }

                // 2. Filter by Fuel Type (Ignore if 'All Types' or empty)
                if ($fueltype != "All Types" && $fueltype != "All Fuel Types" && $fueltype != "") {
                    $conditions[] = "tblvehicles.FuelType = :fueltype";
                    $params[':fueltype'] = $fueltype;
                }

                // 3. Filter by Vehicle Type (Ignore if 'All Types')
                if ($vehicletype != "All Types" && $vehicletype != "") {
                    $conditions[] = "tblvehicles.VehicleType = :vehicletype";
                    $params[':vehicletype'] = $vehicletype;
                }

                // Combine conditions with AND
                if (count($conditions) > 0) {
                    $sql .= " WHERE " . implode(' AND ', $conditions);
                }

                // Execute Query
                $query = $dbh->prepare($sql);
                $query->execute($params);
                $results = $query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0)
                {
                    foreach($results as $result)
                    {  
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="car-card">
                        <div class="img-wrapper">
                            <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" alt="Car" onerror="this.src='https://placehold.co/600x400/222/fff?text=No+Image'">
                            <div class="price-tag-floating">RM <?php echo htmlentities($result->PricePerDay);?> / Day</div>
                        </div>
                        
                        <div class="card-info">
                            <h4 class="car-name"><?php echo htmlentities($result->VehiclesTitle);?></h4>
                            <span class="brand-label">
                                <?php echo htmlentities($result->BrandName);?> 
                                <?php if($result->VehicleType) { echo " | " . htmlentities($result->VehicleType); } ?>
                            </span>
                            
                            <div class="specs-row">
                                <div class="specs-item"><i class="fa fa-calendar"></i> <?php echo htmlentities($result->ModelYear);?></div>
                                <div class="specs-item"><i class="fa fa-gas-pump"></i> <?php echo htmlentities($result->FuelType);?></div>
                                <div class="specs-item"><i class="fa fa-cogs"></i> <?php echo htmlentities($result->Transmission);?></div>
                            </div>

                            <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id);?>" class="btn-view-arrow">
                                View Details <i class="fa fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    } 
                } else { 
                ?>
                    <div class="col-12">
                        <div class="no-result-box">
                            <i class="fa fa-search fa-3x mb-3 text-muted"></i>
                            <h3 class="text-white">No Vehicles Found</h3>
                            <p class="text-muted">We couldn't find any cars matching your specific criteria.</p>
                            <a href="car-listing.php" class="btn-gold-outline">View All Vehicles</a>
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
    </section>

    <?php include('includes/footer.php');?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>