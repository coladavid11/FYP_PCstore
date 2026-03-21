<?php 
session_start();
include('includes/config.php');
error_reporting(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Premium Fleet | Car Rental Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f0f0f; /* Ultra Dark Background */
            color: #fff;
        }

        /* --- 1. HERO BANNER --- */
        .hero-banner {
            background-color: #000;
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(15,15,15,1) 100%), url('https://images.unsplash.com/photo-1617788138017-80ad40651399?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 600px; 
            display: flex;
            align-items: flex-end; 
            justify-content: center;
            position: relative;
            padding-bottom: 150px;
        }

        .hero-content {
            text-align: center;
            z-index: 2;
            animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
        }

        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4.5rem;
            color: #fff;
            letter-spacing: -1px;
            text-shadow: 0 10px 40px rgba(0,0,0,0.9);
        }
        
        .hero-content p {
            font-size: 1.1rem;
            margin-bottom: 120px;
            color: #d4af37; /* Champagne Gold */
            letter-spacing: 4px;
            text-transform: uppercase;
            font-weight: 500;
            text-shadow: 0 5px 20px rgba(0,0,0,0.9);
            position: relative;
            display: inline-block;
        }
        .hero-content p::after {
            content: '';
            display: block;
            width: 40px;
            height: 2px;
            background: #d4af37;
            margin: 15px auto 0;
        }

        /* --- 2. SEARCH BAR --- */
        .search-container {
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }

        .search-box {
            background: rgba(35, 35, 35, 0.9);
            backdrop-filter: blur(15px);
            padding: 35px 30px;
            border-radius: 2px;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        }

        .form-label {
            color: #aaa;
            font-size: 0.75rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .form-select {
            background-color: transparent;
            border: none;
            border-bottom: 1px solid #555;
            border-radius: 0;
            color: #fff;
            padding-left: 0;
            font-size: 1rem;
            font-weight: 400;
            transition: 0.3s;
            cursor: pointer;
        }
        .form-select:focus {
            background-color: #1a1a1a;
            color: #fff;
            border-color: #d4af37;
            box-shadow: none;
        }
        .form-select option {
            background-color: #1a1a1a; 
            color: #fff;
        }

        .btn-gold {
            background: linear-gradient(45deg, #d4af37, #c5a028);
            color: #000;
            border: none;
            width: 100%;
            height: 50px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.4s ease;
            margin-top: 28px;
        }
        .btn-gold:hover {
            background: #fff;
            color: #000;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
        }

        /* --- 3. CAR CARD STYLING --- */
        .section-header {
            text-align: left;
            margin-bottom: 40px;
            margin-top: 20px;
            border-left: 3px solid #d4af37;
            padding-left: 20px;
        }
        .section-header h3 {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 2.2rem;
            margin: 0;
        }
        .section-header span {
            color: #666;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

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
        .btn-view-arrow i { font-size: 0.7rem; margin-left: 5px; transition: 0.3s; }
        .btn-view-arrow:hover i { margin-left: 10px; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <section class="hero-banner">
        <div class="hero-content">
            <h1>Find Your Perfect Ride</h1>
            <p>Elevate Your Journey</p>
        </div>
    </section>

    <div class="container search-container">
        <div class="search-box">
            <form action="search-car-result.php" method="post">
                <div class="row align-items-center g-3">
                    
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Select Brand</label>
                        <select class="form-select" name="brand">
                            <option selected>All Brands</option>
                            <?php 
                            $sql = "SELECT * from tblbrands";
                            $query = $dbh -> prepare($sql);
                            $query->execute();
                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                            if($query->rowCount() > 0) {
                                foreach($results as $result) { ?>  
                                <option value="<?php echo htmlentities($result->id);?>"><?php echo htmlentities($result->BrandName);?></option>
                            <?php }} ?>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Vehicle Type</label>
                        <select class="form-select" name="vehicletype">
                            <option value="All Types" selected>All Types</option>
                            <option value="Sedan">Sedan</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="SUV">SUV</option>
                            <option value="MPV">MPV</option>
                            <option value="Coupe">Coupe</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Fuel Type</label>
                        <select class="form-select" name="fueltype">
                            <option selected>All Fuel Types</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <button type="submit" class="btn btn-gold">Find Vehicle</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <section class="py-5">
        <div class="container">
            <div class="section-header">
                <h3>Our Fleet</h3>
                <span>Curated for Excellence</span>
            </div>

            <div class="row g-4">
                <?php 
                $sql = "SELECT tblvehicles.*,tblbrands.BrandName,tblbrands.id as bid from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand order by tblvehicles.id desc";
                $query = $dbh -> prepare($sql);
                $query->execute();
                $results=$query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $result) { ?>
                    
                    <div class="col-lg-4 col-md-6">
                        <div class="card car-card h-100">
                            <div class="img-wrapper">
                                <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" alt="Car Image" onerror="this.src='https://placehold.co/600x400/222/fff?text=No+Image'">
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
                                    <div class="specs-item"><i class="fa fa-chair"></i> <?php echo htmlentities($result->SeatingCapacity);?> Seats</div>
                                </div>

                                <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id);?>" class="btn-view-arrow">
                                    View Details <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                <?php }} else { ?>
                    <div class="col-12 text-center text-white py-5">
                        <div style="opacity: 0.5;">
                            <i class="fa fa-car fa-3x mb-3"></i>
                            <h4>Inventory Updating...</h4>
                            <p>We are currently updating our fleet. Please check back soon.</p>
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