<?php
session_start();
error_reporting(0);
include('../includes/config.php');

// Security Check
if(strlen($_SESSION['alogin'])==0)
{   
    header('location:index.php');
}
else
{ 
    $id=intval($_GET['id']);

    if(isset($_POST['submit']))
    {
        $vehicletitle = $_POST['vehicletitle'];
        $brand = $_POST['brandname'];
        $vehicleoverview = $_POST['vehicalorcview'];
        $priceperday = $_POST['priceperday'];
        $priceperweek = !empty($_POST['priceperweek']) ? $_POST['priceperweek'] : 0;
        $pricepermonth = !empty($_POST['pricepermonth']) ? $_POST['pricepermonth'] : 0;
        $securitydeposit = !empty($_POST['securitydeposit']) ? $_POST['securitydeposit'] : 0;
        
        $vehicletype = $_POST['vehicletype'];
        $fueltype = $_POST['fueltype'];
        $transmission = $_POST['transmission'];
        $modelyear = $_POST['modelyear'];
        $seatingcapacity = $_POST['seatingcapacity'];

        // MALAYSIA DOCS ---
        $roadtaxexp = $_POST['roadtaxexp'];
        $insurancepolicy = $_POST['insurancepolicy'];
        
        $airconditioner = isset($_POST['airconditioner']) ? 1 : 0;
        $powerdoorlocks = isset($_POST['powerdoorlocks']) ? 1 : 0;
        $antilockbrakingsys = isset($_POST['antilockbrakingsys']) ? 1 : 0;
        $brakeassist = isset($_POST['brakeassist']) ? 1 : 0;
        $powersteering = isset($_POST['powersteering']) ? 1 : 0;
        $driverairbag = isset($_POST['driverairbag']) ? 1 : 0;
        $passengerairbag = isset($_POST['passengerairbag']) ? 1 : 0;
        $powerwindow = isset($_POST['powerwindow']) ? 1 : 0;
        $cdplayer = isset($_POST['cdplayer']) ? 1 : 0;
        $centrallocking = isset($_POST['centrallocking']) ? 1 : 0;
        $crashcensor = isset($_POST['crashcensor']) ? 1 : 0;
        $leatherseats = isset($_POST['leatherseats']) ? 1 : 0;

        // SQL Update
        $sql="UPDATE tblvehicles SET VehiclesTitle=:vehicletitle,VehiclesBrand=:brand,VehiclesOverview=:vehicleoverview,PricePerDay=:priceperday,PricePerWeek=:priceperweek,PricePerMonth=:pricepermonth,SecurityDeposit=:securitydeposit,FuelType=:fueltype,Transmission=:transmission,VehicleType=:vehicletype,ModelYear=:modelyear,SeatingCapacity=:seatingcapacity,RoadTaxExpDate=:roadtaxexp,InsurancePolicyNo=:insurancepolicy,AirConditioner=:airconditioner,PowerDoorLocks=:powerdoorlocks,AntiLockBrakingSystem=:antilockbrakingsys,BrakeAssist=:brakeassist,PowerSteering=:powersteering,DriverAirbag=:driverairbag,PassengerAirbag=:passengerairbag,PowerWindows=:powerwindow,CDPlayer=:cdplayer,CentralLocking=:centrallocking,CrashSensor=:crashcensor,LeatherSeats=:leatherseats where id=:id";
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':vehicletitle',$vehicletitle,PDO::PARAM_STR);
        $query->bindParam(':brand',$brand,PDO::PARAM_STR);
        $query->bindParam(':vehicleoverview',$vehicleoverview,PDO::PARAM_STR);
        $query->bindParam(':priceperday',$priceperday,PDO::PARAM_STR);
        $query->bindParam(':priceperweek',$priceperweek,PDO::PARAM_STR);
        $query->bindParam(':pricepermonth',$pricepermonth,PDO::PARAM_STR);
        $query->bindParam(':securitydeposit',$securitydeposit,PDO::PARAM_STR);
        $query->bindParam(':fueltype',$fueltype,PDO::PARAM_STR);
        $query->bindParam(':transmission',$transmission,PDO::PARAM_STR);
        $query->bindParam(':vehicletype',$vehicletype,PDO::PARAM_STR);
        $query->bindParam(':modelyear',$modelyear,PDO::PARAM_STR);
        $query->bindParam(':seatingcapacity',$seatingcapacity,PDO::PARAM_STR);
        
        // Bind New Malaysia Fields
        $query->bindParam(':roadtaxexp',$roadtaxexp,PDO::PARAM_STR);
        $query->bindParam(':insurancepolicy',$insurancepolicy,PDO::PARAM_STR);

        $query->bindParam(':airconditioner',$airconditioner,PDO::PARAM_STR);
        $query->bindParam(':powerdoorlocks',$powerdoorlocks,PDO::PARAM_STR);
        $query->bindParam(':antilockbrakingsys',$antilockbrakingsys,PDO::PARAM_STR);
        $query->bindParam(':brakeassist',$brakeassist,PDO::PARAM_STR);
        $query->bindParam(':powersteering',$powersteering,PDO::PARAM_STR);
        $query->bindParam(':driverairbag',$driverairbag,PDO::PARAM_STR);
        $query->bindParam(':passengerairbag',$passengerairbag,PDO::PARAM_STR);
        $query->bindParam(':powerwindow',$powerwindow,PDO::PARAM_STR);
        $query->bindParam(':cdplayer',$cdplayer,PDO::PARAM_STR);
        $query->bindParam(':centrallocking',$centrallocking,PDO::PARAM_STR);
        $query->bindParam(':crashcensor',$crashcensor,PDO::PARAM_STR);
        $query->bindParam(':leatherseats',$leatherseats,PDO::PARAM_STR);
        $query->bindParam(':id',$id,PDO::PARAM_STR);
        
        $query->execute();
        $msg="Data updated successfully";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Edit Vehicle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f5f7fa; font-family: 'Segoe UI', sans-serif; padding-top: 100px; }
        .page-header { border-left: 5px solid #3498db; padding-left: 15px; margin-bottom: 30px; }
        .page-header h2 { font-weight: 800; color: #2c3e50; margin: 0; }
        .card-custom { background: white; border-radius: 12px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden; }
        .card-header-custom { background-color: #34495e; color: white; padding: 15px 20px; font-weight: 600; display: flex; align-items: center; }
        .card-header-custom i { margin-right: 10px; color: #3498db; }
        .card-body-custom { padding: 30px; }
        .form-label { font-weight: 600; color: #34495e; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select { border-radius: 8px; padding: 12px; border: 1px solid #e0e0e0; background-color: #fcfcfc; }
        .form-control:focus, .form-select:focus { border-color: #3498db; box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.2); }
        .current-img-box { text-align: center; margin-bottom: 15px; border: 1px solid #eee; padding: 10px; border-radius: 8px; }
        .current-img-box img { max-height: 150px; border-radius: 5px; }
        .price-input { border: 2px solid #27ae60 !important; color: #27ae60; font-weight: bold; }
        .accessory-item { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 15px; display: flex; align-items: center; margin-bottom: 10px; }
        .accessory-item:hover { border-color: #3498db; }
        .accessory-icon { width: 30px; height: 30px; background: #ecf0f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; color: #2c3e50; }
        .form-check-input:checked + label .accessory-icon { background: #3498db; color: white; }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>

    <div class="container">
        <div class="page-header">
            <h2>Edit Vehicle Details</h2>
        </div>

        <?php if($msg){?><div class="alert alert-success shadow-sm"><i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?> </div><?php } ?>

        <?php 
        $sql = "SELECT tblvehicles.*,tblbrands.BrandName,tblbrands.id as bid from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand where tblvehicles.id=:id";
        $query = $dbh -> prepare($sql);
        $query->bindParam(':id',$id, PDO::PARAM_STR);
        $query->execute();
        $results=$query->fetchAll(PDO::FETCH_OBJ);
        if($query->rowCount() > 0) {
            foreach($results as $result) { ?>

        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom">
                        <div class="card-header-custom"><i class="fa fa-info-circle"></i> Basic Info</div>
                        <div class="card-body-custom">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label">Vehicle Title</label>
                                    <input type="text" name="vehicletitle" class="form-control" value="<?php echo htmlentities($result->VehiclesTitle);?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Brand</label>
                                    <select name="brandname" class="form-select" required>
                                        <option value="<?php echo htmlentities($result->bid);?>"><?php echo htmlentities($result->BrandName);?></option>
                                        <?php 
                                        $ret="select id,BrandName from tblbrands";
                                        $query= $dbh -> prepare($ret);
                                        $query->execute();
                                        $results_brand=$query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($results_brand as $row) {
                                                if($row->BrandName != $result->BrandName) { ?>
                                                <option value="<?php echo htmlentities($row->id);?>"><?php echo htmlentities($row->BrandName);?></option>
                                        <?php }}} ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Vehicle Type</label>
                                    <select name="vehicletype" class="form-select" required>
                                        <option value="<?php echo htmlentities($result->VehicleType);?>"><?php echo htmlentities($result->VehicleType);?> (Current)</option>
                                        <option value="Sedan">Sedan</option>
                                        <option value="Hatchback">Hatchback</option>
                                        <option value="SUV">SUV</option>
                                        <option value="MPV">MPV</option>
                                        <option value="Coupe">Coupe</option>
                                        <option value="Luxury">Luxury</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Fuel Type</label>
                                    <select name="fueltype" class="form-select" required>
                                        <option value="<?php echo htmlentities($result->FuelType);?>"><?php echo htmlentities($result->FuelType);?> (Current)</option>
                                        <option value="Petrol">Petrol</option>
                                        <option value="Diesel">Diesel</option>
                                        <option value="Hybrid">Hybrid</option>
                                        <option value="Electric">Electric</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Transmission</label>
                                    <select name="transmission" class="form-select" required>
                                        <option value="<?php echo htmlentities($result->Transmission);?>"><?php echo htmlentities($result->Transmission);?> (Current)</option>
                                        <option value="Auto">Auto</option>
                                        <option value="Manual">Manual</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Model Year</label>
                                    <input type="number" name="modelyear" class="form-control" value="<?php echo htmlentities($result->ModelYear);?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Seating Capacity</label>
                                    <input type="number" name="seatingcapacity" class="form-control" value="<?php echo htmlentities($result->SeatingCapacity);?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Overview</label>
                                    <textarea name="vehicalorcview" class="form-control" rows="4" required><?php echo htmlentities($result->VehiclesOverview);?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-header-custom"><i class="fa fa-cogs"></i> Accessories</div>
                        <div class="card-body-custom">
                            <div class="row g-2">
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="airconditioner" id="ac" <?php if($result->AirConditioner==1) echo "checked";?>><label class="form-check-label ms-2" for="ac">Air Conditioner</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="powerdoorlocks" id="pdl" <?php if($result->PowerDoorLocks==1) echo "checked";?>><label class="form-check-label ms-2" for="pdl">Power Door Locks</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="antilockbrakingsys" id="abs" <?php if($result->AntiLockBrakingSystem==1) echo "checked";?>><label class="form-check-label ms-2" for="abs">ABS System</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="brakeassist" id="ba" <?php if($result->BrakeAssist==1) echo "checked";?>><label class="form-check-label ms-2" for="ba">Brake Assist</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="powersteering" id="ps" <?php if($result->PowerSteering==1) echo "checked";?>><label class="form-check-label ms-2" for="ps">Power Steering</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="driverairbag" id="da" <?php if($result->DriverAirbag==1) echo "checked";?>><label class="form-check-label ms-2" for="da">Driver Airbag</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="passengerairbag" id="pa" <?php if($result->PassengerAirbag==1) echo "checked";?>><label class="form-check-label ms-2" for="pa">Passenger Airbag</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="powerwindow" id="pw" <?php if($result->PowerWindows==1) echo "checked";?>><label class="form-check-label ms-2" for="pw">Power Windows</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="cdplayer" id="cd" <?php if($result->CDPlayer==1) echo "checked";?>><label class="form-check-label ms-2" for="cd">CD Player</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="centrallocking" id="cl" <?php if($result->CentralLocking==1) echo "checked";?>><label class="form-check-label ms-2" for="cl">Central Locking</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="crashcensor" id="cc" <?php if($result->CrashSensor==1) echo "checked";?>><label class="form-check-label ms-2" for="cc">Crash Sensor</label></div></div></div>
                                <div class="col-md-4"><div class="accessory-item"><div class="form-check"><input class="form-check-input" type="checkbox" name="leatherseats" id="ls" <?php if($result->LeatherSeats==1) echo "checked";?>><label class="form-check-label ms-2" for="ls">Leather Seats</label></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    
                    <div class="card-custom">
                        <div class="card-header-custom" style="background: #34495e;">
                            <i class="fa fa-file-contract text-white"></i> Legal & Documentation
                        </div>
                        <div class="card-body-custom">
                            <div class="mb-3">
                                <label class="form-label text-danger">Road Tax Expiry Date</label>
                                <input type="date" name="roadtaxexp" class="form-control" value="<?php echo htmlentities($result->RoadTaxExpDate);?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Insurance Policy No.</label>
                                <input type="text" name="insurancepolicy" class="form-control" value="<?php echo htmlentities($result->InsurancePolicyNo);?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-header-custom" style="background: #27ae60;"><i class="fa fa-money-bill-wave text-white"></i> Pricing</div>
                        <div class="card-body-custom">
                            <div class="mb-3">
                                <label class="form-label">Price Per Day (RM)</label>
                                <input type="text" name="priceperday" id="price_day" class="form-control price-input" value="<?php echo htmlentities($result->PricePerDay);?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price Per Week</label>
                                <input type="text" name="priceperweek" id="price_week" class="form-control" value="<?php echo htmlentities($result->PricePerWeek);?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price Per Month</label>
                                <input type="text" name="pricepermonth" id="price_month" class="form-control" value="<?php echo htmlentities($result->PricePerMonth);?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-danger">Security Deposit</label>
                                <input type="text" name="securitydeposit" class="form-control" value="<?php echo htmlentities($result->SecurityDeposit);?>">
                            </div>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-header-custom"><i class="fa fa-image"></i> Current Images</div>
                        <div class="card-body-custom">
                            <div class="mb-3">
                                <label class="form-label">Image 1</label>
                                <div class="current-img-box"><img src="img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" class="img-fluid"></div>
                            </div>
                            <?php if($result->Vimage2!="") { ?>
                            <div class="mb-3">
                                <label class="form-label">Image 2</label>
                                <div class="current-img-box"><img src="img/vehicleimages/<?php echo htmlentities($result->Vimage2);?>" class="img-fluid"></div>
                            </div>
                            <?php } ?>
                            <?php if($result->Vimage3!="") { ?>
                            <div class="mb-3">
                                <label class="form-label">Image 3</label>
                                <div class="current-img-box"><img src="img/vehicleimages/<?php echo htmlentities($result->Vimage3);?>" class="img-fluid"></div>
                            </div>
                            <?php } ?>
                            <div class="alert alert-warning small">To change images, please delete and re-post or use the image update module.</div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg shadow"><i class="fa fa-save"></i> Save Changes</button>
                    </div>
                </div>
            </div>
        </form>
        <?php }} ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const dailyInput = document.getElementById('price_day');
        const weeklyInput = document.getElementById('price_week');
        const monthlyInput = document.getElementById('price_month');

        dailyInput.addEventListener('input', function() {
            const dailyPrice = parseFloat(this.value);
            if (!isNaN(dailyPrice)) {
                // Auto calculate: Week = Day*7*0.85, Month = Day*30*0.65
                weeklyInput.value = Math.round(dailyPrice * 7 * 0.85);
                monthlyInput.value = Math.round(dailyPrice * 30 * 0.65);
            } else {
                weeklyInput.value = '';
                monthlyInput.value = '';
            }
        });
    </script>
</body>
</html>
<?php } ?>