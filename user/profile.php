<?php
session_start();
require_once __DIR__ . '/includes/config.php';
error_reporting(0);

// Security Check
if (empty($_SESSION['login'])) {
  header("Location: index.php");
  exit();
}

$useremail = $_SESSION['login']; // Current logged-in email
$status = "";

// 1. Fetch Latest User Data
$sql = "SELECT FullName, EmailId, Password, ContactNo, Address, City, RegDate, UpdationDate, IcNo, LicenseNo, LicenseExpDate
        FROM tblusers
        WHERE EmailId = :email
        LIMIT 1";
$q = $dbh->prepare($sql);
$q->bindParam(':email', $useremail, PDO::PARAM_STR);
$q->execute();
$u = $q->fetch(PDO::FETCH_OBJ);

if (!$u) {
  header("Location: logout.php");
  exit();
}

// 2. Handle Update Submission
if (isset($_POST['update_profile'])) {
  $fullname  = trim($_POST['fullname'] ?? '');
  $contactno = trim($_POST['contactno'] ?? '');
  $address   = trim($_POST['address'] ?? '');
  $city      = trim($_POST['city'] ?? '');
  
  // New: Get the submitted email
  $new_email = trim($_POST['email'] ?? '');

  // Get IC/License inputs
  $input_ic = trim($_POST['icno'] ?? '');
  $input_lic = trim($_POST['licenseno'] ?? '');
  $input_exp = trim($_POST['licenseexp'] ?? '');

  // Password inputs
  $current_password = $_POST['current_password'] ?? '';
  $newpass = $_POST['newpass'] ?? '';
  $confpass = $_POST['confpass'] ?? '';

  // Basic Validation
  if ($fullname === "" || $contactno === "" || $address === "" || $city === "" || $new_email === "") {
    $status = "invalid";
  } else if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    $status = "invalid_email";
  }

  // Check if new email is already taken (excluding self)
  if ($status === "" && $new_email !== $useremail) {
      $sqlCheck = "SELECT EmailId FROM tblusers WHERE EmailId = :newemail AND EmailId != :oldemail";
      $qc = $dbh->prepare($sqlCheck);
      $qc->bindParam(':newemail', $new_email);
      $qc->bindParam(':oldemail', $useremail);
      $qc->execute();
      if ($qc->rowCount() > 0) {
          $status = "email_taken";
      }
  }

  // --- Identity Locking Logic ---
  $final_ic = empty($u->IcNo) ? $input_ic : $u->IcNo;
  $final_lic = empty($u->LicenseNo) ? $input_lic : $u->LicenseNo;
  $final_exp = (empty($u->LicenseExpDate) || $u->LicenseExpDate == '0000-00-00') ? $input_exp : $u->LicenseExpDate;

  // Simple Format Validation (Only for first-time entry)
  if ($status === "" && empty($u->IcNo) && !empty($final_ic)) {
      if (!preg_match('/^[0-9]{12}$/', $final_ic)) {
          $status = "invalid_ic";
      }
  }

  // Password Logic
  $wantsChangePassword = ($current_password !== "" || $newpass !== "" || $confpass !== "");
  if ($status === "" && $wantsChangePassword) {
    if ($current_password === "") {
      $status = "current_required";
    } else if (!password_verify($current_password, $u->Password)) {
      $status = "current_wrong";
    } else if ($newpass !== $confpass) {
      $status = "pw_mismatch";
    } else if (strlen($newpass) < 6) {
      $status = "pw_short";
    }
  }

  // 3. Execute Update
  if ($status === "") {
    
    // Start Transaction
    $dbh->beginTransaction();

    try {
        // Base SQL
        $sqlBase = "UPDATE tblusers
                     SET FullName = :fullname,
                         EmailId   = :newemail, 
                         ContactNo = :contactno,
                         Address   = :address,
                         City      = :city,
                         IcNo      = :icno,
                         LicenseNo = :licenseno,
                         LicenseExpDate = :licenseexp,
                         UpdationDate = NOW()";

        if ($wantsChangePassword && $newpass !== "") {
          $hash = password_hash($newpass, PASSWORD_BCRYPT);
          $sqlUpd = $sqlBase . ", Password = :pass WHERE EmailId = :oldemail";
        } else {
          $sqlUpd = $sqlBase . " WHERE EmailId = :oldemail";
        }

        $upd = $dbh->prepare($sqlUpd);
        
        $upd->bindParam(':fullname', $fullname);
        $upd->bindParam(':newemail', $new_email);
        $upd->bindParam(':contactno', $contactno);
        $upd->bindParam(':address', $address);
        $upd->bindParam(':city', $city);
        $upd->bindParam(':icno', $final_ic);
        $upd->bindParam(':licenseno', $final_lic);
        $upd->bindParam(':licenseexp', $final_exp);
        $upd->bindParam(':oldemail', $useremail); 

        if ($wantsChangePassword && $newpass !== "") {
            $upd->bindParam(':pass', $hash);
        }
        $upd->execute();

        // 🔥 If Email changed, update linked tables
        if ($new_email !== $useremail) {
            $updBk = $dbh->prepare("UPDATE tblbooking SET userEmail = :new WHERE userEmail = :old");
            $updBk->execute([':new' => $new_email, ':old' => $useremail]);

            $updRv = $dbh->prepare("UPDATE tblreviews SET userEmail = :new WHERE userEmail = :old");
            $updRv->execute([':new' => $new_email, ':old' => $useremail]);

            $_SESSION['login'] = $new_email;
            $useremail = $new_email; 
        }

        $dbh->commit();
        $_SESSION['fname'] = $fullname;
        $status = "success";

    } catch (Exception $e) {
        $dbh->rollBack();
        $status = "error";
    }

    // Refresh Data
    $q = $dbh->prepare("SELECT FullName, EmailId, Password, ContactNo, Address, City, IcNo, LicenseNo, LicenseExpDate FROM tblusers WHERE EmailId = :email");
    $q->bindParam(':email', $useremail);
    $q->execute();
    $u = $q->fetch(PDO::FETCH_OBJ);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile Settings</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body { font-family:'Poppins',sans-serif; background:#0f0f0f; color:#fff; }

    .page-hero{
      background: linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.9)),
      url('https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?q=80&w=2070&auto=format&fit=crop');
      background-size:cover; background-position:center;
      height:35vh; display:flex; align-items:center; justify-content:center;
      text-align:center;
    }
    .hero-title{ font-family:'Playfair Display',serif; font-size:2.6rem; font-weight:700; text-transform:uppercase; }
    .hero-subtitle{ font-size:.9rem; color:#d4af37; letter-spacing:2px; }

    .dark-card{ background:#181818; border:1px solid #2a2a2a; border-radius:0; padding:25px; }

    .label{ color:#aaa; text-transform:uppercase; letter-spacing:1px; font-size:.82rem; margin-bottom:8px; }

    .form-control{
      background:#121212; border:1px solid #2a2a2a; color:#fff; border-radius:0;
      padding:12px 14px;
    }
    .form-control:focus{
      background:#121212; border-color:#d4af37;
      box-shadow:0 0 0 .2rem rgba(212,175,55,.15);
      color:#fff;
    }

    /*  Readonly Style */
    .readonly-box {
      background:#222 !important; 
      color:#999 !important;      
      border-color: #333 !important;
      cursor:not-allowed;
      pointer-events: none;       
    }

    .btn-gold{
      background: linear-gradient(45deg, #d4af37, #c5a028);
      border:none; border-radius:0;
      color:#000; font-weight:700; padding:12px 14px;
      width:100%; text-transform:uppercase; letter-spacing:1px;
    }
    .btn-gold:hover{
      background:#fff;
      transform: translateY(-2px);
      box-shadow:0 10px 20px rgba(212,175,55,.3);
    }

    .line{ border-top:1px solid #2a2a2a; margin:18px 0; }
    
    .section-title {
        color: #d4af37; font-size: 1.1rem; margin-top: 20px; margin-bottom: 15px; 
        border-bottom: 1px dashed #333; padding-bottom: 5px;
    }
    
    /* Fixed Lock Icon Alignment */
    .lock-icon {
        position: absolute; 
        right: 15px; 
        top: 50%; /* Vertically Center */
        transform: translateY(-50%); /* Correct Offset */
        color: #666;
        pointer-events: none;
    }
  </style>
</head>
<body>

<?php include(__DIR__ . '/includes/header.php'); ?>

<section class="page-hero">
  <div>
    <div class="hero-title">Profile Settings</div>
    <div class="hero-subtitle">Manage your account</div>
  </div>
</section>

<section class="py-5">
  <div class="container" style="max-width:900px;">

    <div class="dark-card mb-4">
      <form method="post">

        <div class="section-title">Personal Information</div>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="label">Full Name</div>
            <input class="form-control" name="fullname" value="<?php echo htmlentities($u->FullName); ?>" required>
          </div>

          <div class="col-md-6">
            <div class="label">Contact No</div>
            <input class="form-control" name="contactno" value="<?php echo htmlentities($u->ContactNo); ?>" required>
          </div>
          
          <div class="col-md-6">
            <div class="label">Email Address</div>
            <input type="email" class="form-control" name="email" value="<?php echo htmlentities($u->EmailId); ?>" required>
          </div>
        </div>

        <div class="section-title">Identity & License (Malaysia)</div>
        <div class="row g-3">
            
            <div class="col-md-12">
                <div class="label">MyKad No. (IC)</div>
                <div style="position:relative;">
                    <input type="text" name="icno" 
                           value="<?php echo htmlentities($u->IcNo); ?>" 
                           class="form-control <?php echo (!empty($u->IcNo)) ? 'readonly-box' : ''; ?>" 
                           placeholder="Enter your 12-digit IC"
                           <?php echo (!empty($u->IcNo)) ? 'readonly' : ''; ?> >
                    
                    <?php if(!empty($u->IcNo)) { ?>
                        <i class="fa fa-lock lock-icon" title="Identity Verified"></i>
                    <?php } ?>
                </div>
                <?php if(empty($u->IcNo)) { ?>
                    <div class="small text-warning mt-1"><i class="fa fa-info-circle"></i> Once saved, this cannot be changed.</div>
                <?php } ?>
            </div>

            <div class="col-md-6">
                <div class="label">Driving License No.</div>
                <div style="position:relative;">
                    <input type="text" name="licenseno" 
                           value="<?php echo htmlentities($u->LicenseNo); ?>" 
                           class="form-control <?php echo (!empty($u->LicenseNo)) ? 'readonly-box' : ''; ?>" 
                           placeholder="License No"
                           <?php echo (!empty($u->LicenseNo)) ? 'readonly' : ''; ?> >
                    
                    <?php if(!empty($u->LicenseNo)) { ?>
                        <i class="fa fa-lock lock-icon"></i>
                    <?php } ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="label">License Expiry Date</div>
                <div style="position:relative;">
                    <input type="date" name="licenseexp" 
                           value="<?php echo htmlentities($u->LicenseExpDate); ?>" 
                           class="form-control <?php echo (!empty($u->LicenseExpDate) && $u->LicenseExpDate != '0000-00-00') ? 'readonly-box' : ''; ?>" 
                           min="<?php echo date('Y-m-d'); ?>"
                           <?php echo (!empty($u->LicenseExpDate) && $u->LicenseExpDate != '0000-00-00') ? 'readonly' : ''; ?> >
                    
                    <?php if(!empty($u->LicenseExpDate) && $u->LicenseExpDate != '0000-00-00') { ?>
                        <i class="fa fa-lock lock-icon"></i>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="section-title">Address</div>
        <div class="row g-3">
          <div class="col-12">
            <div class="label">Address</div>
            <input class="form-control" name="address" value="<?php echo htmlentities($u->Address); ?>" required>
          </div>

          <div class="col-md-6">
            <div class="label">City</div>
            <input class="form-control" name="city" value="<?php echo htmlentities($u->City); ?>" required>
          </div>
        </div>

        <div class="line"></div>

        <h5 style="font-family:'Playfair Display'; font-size: 1.1rem; color: #fff;">Change Password (Optional)</h5>

        <div class="row g-3 mt-1">
          <div class="col-12">
            <div class="label">Current Password</div>
            <input type="password" name="current_password" class="form-control" placeholder="Enter your current password">
          </div>
          <div class="col-md-6">
            <div class="label">New Password</div>
            <input type="password" name="newpass" class="form-control" placeholder="Min 6 characters">
          </div>
          <div class="col-md-6">
            <div class="label">Confirm Password</div>
            <input type="password" name="confpass" class="form-control" placeholder="Re-enter new password">
          </div>
        </div>

        <button class="btn-gold mt-4" name="update_profile">
          <i class="fa fa-save"></i> Save Changes
        </button>

      </form>
    </div>

  </div>
</section>

<?php include(__DIR__ . '/includes/footer.php'); ?>

<script>
<?php if ($status === "success") { ?>
Swal.fire({ icon:'success', title:'Updated!', text:'Profile updated successfully.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "invalid") { ?>
Swal.fire({ icon:'error', title:'Incomplete', text:'Please fill in all required fields.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "email_taken") { ?>
Swal.fire({ icon:'error', title:'Email Taken', text:'This email address is already in use by another account.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "invalid_ic") { ?>
Swal.fire({ icon:'error', title:'Invalid IC', text:'Please enter a valid 12-digit MyKad number.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "current_required") { ?>
Swal.fire({ icon:'error', title:'Password Required', text:'Enter your current password to save changes.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "current_wrong") { ?>
Swal.fire({ icon:'error', title:'Wrong Password', text:'The current password you entered is incorrect.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "pw_mismatch") { ?>
Swal.fire({ icon:'error', title:'Password Mismatch', text:'New password and confirm password do not match.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "pw_short") { ?>
Swal.fire({ icon:'error', title:'Password Too Short', text:'Password must be at least 6 characters.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } elseif ($status === "error") { ?>
Swal.fire({ icon:'error', title:'Update Failed', text:'Something went wrong. Try again.', confirmButtonColor:'#d4af37', background:'#181818', color:'#fff' });
<?php } ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>