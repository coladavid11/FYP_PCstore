<?php
session_start();
include('includes/config.php');

if (!isset($_SESSION['admin_login'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = false;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shipping_rates.php");
    exit;
}

$rate_id = intval($_GET['id']);

// FIXED: Cleaned query matching your exact database structure
$sql = "SELECT rate_id, state_id, fee FROM tbl_shipping_rate WHERE rate_id = :rate_id";
$query = $dbh->prepare($sql);
$query->bindParam(':rate_id', $rate_id, PDO::PARAM_INT);
$query->execute();
$rate = $query->fetch(PDO::FETCH_OBJ);

if (!$rate) {
    header("Location: shipping_rates.php");
    exit;
}

// Processing safe data update workflows 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_fee = trim($_POST['fee'] ?? '');

    if ($new_fee === '') {
        $errors[] = 'The shipping fee rate cannot be blank.';
    } elseif (!is_numeric($new_fee) || floatval($new_fee) < 0) {
        $errors[] = 'Please enter a valid positive numerical amount.';
    }

    if (empty($errors)) {
        $updateSql = "UPDATE tbl_shipping_rate SET fee = :fee WHERE rate_id = :rate_id";
        $updateQuery = $dbh->prepare($updateSql);
        $updateQuery->bindParam(':fee', $new_fee, PDO::PARAM_STR);
        $updateQuery->bindParam(':rate_id', $rate_id, PDO::PARAM_INT);

        if ($updateQuery->execute()) {
            $success = true;
            $rate->fee = $new_fee; 
        } else {
            $errors[] = 'Database subsystem update tracking operation broken.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Modify Shipping Target Fees</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { display: flex; background: #f5f5f5; }
        .sidebar { width: 220px; height: 100vh; background: #000; padding: 20px; position: fixed; }
        .sidebar h2 { color: #d4af37; margin-bottom: 30px; text-align: center; }
        .sidebar a { display: block; color: #adadad; text-decoration: none; padding: 12px; margin: 10px 0; border-radius: 5px; }
        .main { margin-left: 220px; width: calc(100% - 220px); padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 25px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 24px; }
        .topbar h1 { font-size: 1.6rem; color: #111; }
        .btn-back {
            color: #d4af37;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .btn-back:hover {
            opacity: 0.75;
        }
        .card { background: #fff; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 600px; overflow: hidden; }
        .card-header { padding: 18px 28px; border-bottom: 1px solid #f0f0f0; font-weight: 600; font-size: 1rem; color: #111; }
        .card-body { padding: 28px; }
        .form-group { margin-bottom: 22px; }
        .form-label { display: block; font-size: 0.82rem; font-weight: 600; color: #444; margin-bottom: 7px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 0.88rem; color: #333; }
        .form-control:focus { outline: none; border-color: #d4af37; box-shadow: 0 0 0 3px rgba(212,175,55,0.12); }
        .form-control:disabled { background: #fafafa; color: #777; cursor: not-allowed; }
        .card-footer { padding: 18px 28px; border-top: 1px solid #f0f0f0; background: #fafafa; display: flex; gap: 10px; }
        .btn-save { background: #000; color: #d4af37; border: 1px solid #d4af37; padding: 10px 24px; border-radius: 4px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { background: #d4af37; color: #000; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid rgba(40,167,69,0.2); padding: 15px 20px; border-radius: 4px; margin-bottom: 24px; font-size: 0.9rem; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid rgba(220,53,69,0.2); padding: 15px 20px; border-radius: 4px; margin-bottom: 24px; font-size: 0.9rem; list-style: none; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">📂 Categories</a>
        <a href="brands.php">🏷️ Brands</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="shipping_rates.php" class="sidebar-active">🚚 Shipping Rates</a>
        <a href="admins.php">⚙ Admin</a>
    </div>

    <div class="main">
        <div class="topbar">
            <h1>Modify Shipping Vector</h1>
            <a href="shipping_rates.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

        <?php if ($success): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> Shipping baseline matrix configuration processed successfully!</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <ul class="alert-error">
                <?php foreach ($errors as $error): ?>
                    <li><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Edit Logistics Parameters</div>
            <form method="POST">
                <div class="card-body">
                    <div class="form-group">
    <label class="form-label">Destination Territory / State ID</label>
    <input type="text" class="form-control" disabled value="Zone / State ID: <?php echo htmlspecialchars($rate->state_id); ?>">
</div>
                    
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Shipping Rate Charge Amount (RM)</label>
                        <input type="number" step="0.01" min="0" name="fee" class="form-control" value="<?php echo htmlspecialchars($rate->fee); ?>" required>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> Save Status Change</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>