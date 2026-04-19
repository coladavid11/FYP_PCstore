<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// ===== Login Check =====
if (empty($_SESSION['login'])) {
  header("Location: index.php");
  exit();
}

$useremail = $_SESSION['login'];
$bkid = isset($_GET['bkid']) ? (int)$_GET['bkid'] : 0;

if ($bkid <= 0) {
  header("Location: my-booking.php");
  exit();
}

// ===== Fetch Booking =====
$sql = "SELECT b.*, v.VehiclesTitle, v.PricePerDay, v.Vimage1, br.BrandName
        FROM tblbooking b
        JOIN tblvehicles v ON v.id = b.VehicleId
        JOIN tblbrands br ON br.id = v.VehiclesBrand
        WHERE b.id = :bkid AND b.userEmail = :useremail LIMIT 1";

$q = $dbh->prepare($sql);
$q->execute([
  ':bkid' => $bkid,
  ':useremail' => $useremail
]);

$bk = $q->fetch(PDO::FETCH_OBJ);

if (!$bk) {
  header("Location: my-booking.php");
  exit();
}

// ===== Calculate Total =====
$from = new DateTime($bk->FromDate);
$to   = new DateTime($bk->ToDate);
$days = max(1, $from->diff($to)->days);
$total = $days * $bk->PricePerDay;

$err = "";
$paid_now = false;

// ===== Handle Payment =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pay_now'])) {

  if ((int)$bk->payment_status === 1) {
    $err = "This booking is already paid.";
  } else {

    $card_name = trim($_POST['card_name'] ?? '');
    $card_no   = preg_replace('/\D/', '', $_POST['card_no'] ?? '');
    $cvv       = preg_replace('/\D/', '', $_POST['cvv'] ?? '');

    if ($card_name === "" || $card_no === "" || $cvv === "") {
      $err = "Please fill all fields.";
    }
    elseif (!preg_match('/^\d{13,19}$/', $card_no)) {
      $err = "Invalid card number.";
    }
    elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
      $err = "Invalid CVV.";
    }
    else {

      // 防重复付款（关键）
      $upd = "UPDATE tblbooking 
              SET payment_status = 1 
              WHERE id = :bkid 
              AND userEmail = :useremail 
              AND payment_status = 0";

      $u = $dbh->prepare($upd);
      $u->execute([
        ':bkid' => $bkid,
        ':useremail' => $useremail
      ]);

      if ($u->rowCount() > 0) {
        $paid_now = true;
      } else {
        $err = "Payment already processed.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body { background:#0f0f0f; color:#fff; font-family:sans-serif; }
.container { max-width:900px; margin:auto; padding:20px; }
.card { background:#181818; padding:20px; margin-bottom:20px; }
input {
  width:100%; padding:10px; margin:8px 0;
  background:#222; border:1px solid #333; color:#fff;
}
button {
  width:100%; padding:12px;
  background:#d4af37; border:none; cursor:pointer;
}
.error { color:#ff6b6b; }
.success { color:#00e5b0; }
</style>
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container">

<h2>Payment</h2>

<!-- Booking Info -->
<div class="card">
  <h3><?php echo htmlentities($bk->BrandName . " " . $bk->VehiclesTitle); ?></h3>
  <p>Days: <?php echo $days; ?></p>
  <p>Total: <strong>RM <?php echo number_format($total,2); ?></strong></p>
</div>

<?php if ($err): ?>
<p class="error"><?php echo htmlentities($err); ?></p>
<?php endif; ?>

<?php if (!$paid_now): ?>
<form method="POST" onsubmit="return validateForm()">

  <input type="text" name="card_name" id="card_name" placeholder="Cardholder Name">

  <input type="text" name="card_no" id="card_no" placeholder="Card Number">

  <input type="text" name="exp" placeholder="MM/YY">

  <input type="text" name="cvv" id="cvv" placeholder="CVV">

  <button type="submit" name="pay_now">
    Pay RM <?php echo number_format($total,2); ?>
  </button>
</form>
<?php endif; ?>

<?php if ($paid_now): ?>
<p class="success">✅ Payment Successful!</p>

<script>
setTimeout(()=>{
  window.location.href = "booking-details.php?bkid=<?php echo $bkid;?>";
},2000);
</script>
<?php endif; ?>

</div>

<script>
function validateForm(){
  let name = document.getElementById('card_name').value;
  let number = document.getElementById('card_no').value;
  let cvv = document.getElementById('cvv').value;

  if(name==="" || number==="" || cvv===""){
    alert("Please fill all fields");
    return false;
  }
  return true;
}
</script>

</body>
</html>