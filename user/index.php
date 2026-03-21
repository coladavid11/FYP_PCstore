<?php 
session_start();
include('includes/config.php');
error_reporting(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Buat Kerja Betul2 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f0f0f; 
            color: #fff;
        }

        /* --- HERO SECTION --- */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 90vh; 
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .hero-title { 
            font-family: 'Playfair Display', serif; 
            font-size: 4.5rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 2px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.8);
            margin-bottom: 15px;
            animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
        }
        
        .hero-subtitle { 
            font-size: 1.2rem; 
            margin-bottom: 40px; 
            color: #d4af37; 
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 500;
            animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
        }

        .btn-hero { 
            background: linear-gradient(45deg, #d4af37, #c5a028); /* 金色渐变按钮 */
            color: #000; 
            padding: 15px 40px; 
            font-weight: bold; 
            border-radius: 2px; 
            text-decoration: none; 
            transition: 0.3s; 
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: fadeInUp 1.2s cubic-bezier(0.2, 1, 0.2, 1);
        }
        .btn-hero:hover { 
            background: #fff; 
            color: #000; 
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3);
        }

        /* --- SECTION HEADERS --- */
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-header h2 {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 2.5rem;
        }
        .section-header p {
            color: #888;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .section-divider {
            width: 60px; 
            height: 2px; 
            background: #d4af37; 
            margin: 20px auto; 
            border: none;
        }

        /* --- CAR CARD STYLING (MATCHING LISTING PAGE) --- */
        .bg-custom-dark {
            background-color: #0f0f0f;
        }

        .car-card { 
            background: #181818;
            border: 1px solid #2a2a2a; 
            border-radius: 0; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            transition: all 0.4s ease; 
            overflow: hidden; 
        }
        .car-card:hover { 
            transform: translateY(-10px); 
            border-color: #444;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
        }
        
        .img-wrapper {
            position: relative;
            height: 240px;
            overflow: hidden;
        }
        .car-img-top { 
            height: 100%; 
            object-fit: cover; 
            width: 100%; 
            transition: 0.6s;
            filter: brightness(0.9);
        }
        .car-card:hover .car-img-top { transform: scale(1.08); filter: brightness(1.1); }
        
        .card-body { padding: 25px; }
        
        .card-title {
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 1.4rem;
            margin-bottom: 5px;
        }

        .car-price { 
            color: #d4af37; 
            font-weight: 700; 
            font-size: 1.1rem; 
            margin-bottom: 15px;
            display: block;
        }
        
        .car-meta { 
            border-top: 1px solid #333;
            padding-top: 15px;
            font-size: 0.85rem; 
            color: #aaa; 
            display: flex;
            justify-content: space-between;
        }
        .car-meta i { color: #d4af37; margin-right: 5px; }

        .btn-outline-gold {
            border: 1px solid #555;
            color: #fff;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            transition: 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-outline-gold:hover {
            background: #d4af37;
            border-color: #d4af37;
            color: #000;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <?php include('includes/header.php');?>

    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Find Your Best Drive</h1>
            <p class="hero-subtitle">Premium Cars. Unlimited Miles. Unforgettable Moments.</p>
            <br>
            <a href="car-listing.php" class="btn-hero">Browse Collection</a>
        </div>
    </section>

    <section class="py-5 bg-custom-dark">
        <div class="container">
            <div class="section-header">
                <h2>New Arrivals</h2>
                <hr class="section-divider">
                <p>Experience our latest premium additions</p>
            </div>

            <div class="row g-4">
                
                <?php 
                $sql = "SELECT tblvehicles.VehiclesTitle,tblbrands.BrandName,tblvehicles.PricePerDay,tblvehicles.FuelType,tblvehicles.ModelYear,tblvehicles.id,tblvehicles.SeatingCapacity,tblvehicles.Vimage1 from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand order by tblvehicles.id desc limit 3";
                $query = $dbh -> prepare($sql);
                $query->execute();
                $results=$query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0)
                {
                    foreach($results as $result)
                    {  
                ?>
                    <div class="col-md-4">
                        <div class="card car-card h-100">
                            <div class="img-wrapper">
                                <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" class="car-img-top" alt="Car Image" onerror="this.src='https://placehold.co/600x400/222/fff?text=No+Image'">
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlentities($result->BrandName);?> <?php echo htmlentities($result->VehiclesTitle);?></h5>
                                <span class="car-price">RM <?php echo htmlentities($result->PricePerDay);?> / Day</span>
                                
                                <div class="car-meta">
                                    <span><i class="fa fa-gas-pump"></i> <?php echo htmlentities($result->FuelType);?></span>
                                    <span><i class="fa fa-calendar"></i> <?php echo htmlentities($result->ModelYear);?></span>
                                    <span><i class="fa fa-chair"></i> <?php echo htmlentities($result->SeatingCapacity);?></span>
                                </div>
                                
                                <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id);?>" class="btn-outline-gold">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php }} else { ?>
                    <div class="col-12 text-center text-white">
                        <p class="text-muted">No vehicles available at the moment.</p>
                    </div>
                <?php } ?>

            </div>
        </div>
    </section>

    <?php include('includes/footer.php');?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>