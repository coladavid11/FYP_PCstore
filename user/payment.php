<?php
session_start();

// ✅ 修正这里（重点）
require_once __DIR__ . '/../includes/config.php';

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

// Fetch booking
$sql = "SELECT b.*, v.VehiclesTitle, v.PricePerDay, br.BrandName
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

// Calculate total
$from = new DateTime($bk->FromDate);
$to   = new DateTime($bk->ToDate);
$days = max(1, $from->diff($to)->days);
$total = $days * $bk->PricePerDay;

$paid_now = false;
$err = "";

// Handle payment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pay_now'])) {

  if ((int)$bk->payment_status === 1) {
    $err = "Already paid.";
  } else {

    if (empty($_POST['card_name']) || empty($_POST['card_no'])) {
      $err = "Please fill all fields.";
    } else {

      $upd = "UPDATE tblbooking 
              SET payment_status = 1 
              WHERE id = :bkid AND userEmail = :useremail";

      $u = $dbh->prepare($upd);
      $u->execute([
        ':bkid' => $bkid,
        ':useremail' => $useremail
      ]);

      $paid_now = true;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
  background:#0a0a0f;
  color:#fff;
  font-family:sans-serif;
}
.container{
  max-width:900px;
  margin:auto;
  padding:20px;
}
.card{
  background:#111;
  padding:20px;
  border-radius:10px;
}
input{
  width:100%;
  padding:10px;
  margin:8px 0;
  background:#222;
  border:1px solid #333;
  color:#fff;
}
button{
  width:100%;
  padding:12px;
  background:#00e5ff;
  border:none;
  cursor:pointer;
}
.success{
  color:#00ff99;
}
.error{
  color:red;
}
</style>
</head>

<body>

<?php 
// ✅ 如果你有 header.php 就用这个（已修正路径）
include __DIR__ . '/../includes/header.php'; 
?>

<div class="container">

<h2>Payment</h2>

<div class="card">
  <h3><?php echo htmlentities($bk->BrandName . " " . $bk->VehiclesTitle); ?></h3>
  <p>Days: <?php echo $days; ?></p>
  <p>Total: RM <?php echo number_format($total,2); ?></p>
</div>

<?php if ($err): ?>
<p class="error"><?php echo $err; ?></p>
<?php endif; ?>

<?php if (!$paid_now): ?>
<form method="POST" onsubmit="return validateForm()">

  <input type="text" name="card_name" id="card_name" placeholder="Card Name">
  <input type="text" name="card_no" id="card_no" placeholder="Card Number">
  <input type="text" name="exp" placeholder="MM/YY">
  <input type="text" name="cvv" placeholder="CVV">

  <button type="submit" name="pay_now">
    Pay RM <?php echo number_format($total,2); ?>
  </button>
</form>
<?php endif; ?>

<?php if ($paid_now): ?>
<p class="success">Payment Successful!</p>

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

  if(name==="" || number===""){
    alert("Fill all fields");
    return false;
  }
  return true;
}
</script>

</body>
</html>