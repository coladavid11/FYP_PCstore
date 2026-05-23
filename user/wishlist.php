<?php
session_start();
include('includes/config.php');

$isLoggedIn = isset($_SESSION['login']);
$user_id = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || !$user_id) {
    header("Location: login.php");
    exit;
}

/* =========================
   REMOVE WISHLIST (UPDATE STATUS)
========================= */
if (isset($_POST['remove_wishlist'])) {

    $wid = $_POST['wishlist_id'];

    $stmt = $dbh->prepare("
        UPDATE tblwishlist 
        SET status = 'removed'
        WHERE wishlist_id = ? AND user_id = ?
    ");
    $stmt->execute([$wid, $user_id]);

    echo json_encode([
        "status" => "success",
        "message" => "Removed from wishlist"
    ]);
    exit;
}

/* =========================
   MOVE TO CART
========================= */
if (isset($_POST['move_to_cart'])) {

    $pid = $_POST['product_id'];

    // get product
    $stmt = $dbh->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($p) {

        // check cart
        $check = $dbh->prepare("SELECT * FROM tblcart WHERE user_id=? AND product_id=?");
        $check->execute([$user_id, $pid]);

        if ($check->rowCount() > 0) {

            $upd = $dbh->prepare("
                UPDATE tblcart
                SET quantity = quantity + 1,
                    subtotal = subtotal + ?
                WHERE user_id=? AND product_id=?
            ");
            $upd->execute([$p['price'], $user_id, $pid]);

        } else {

            $ins = $dbh->prepare("
                INSERT INTO tblcart
                (user_id, product_id, product_name, product_image, product_price, quantity, subtotal, created_at, updated_at, status)
                VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW(), 'active')
            ");

            $ins->execute([
                $user_id,
                $p['product_id'],
                $p['name'],
                $p['image'],
                $p['price'],
                $p['price']
            ]);
        }

        // IMPORTANT: sync wishlist status
        $updateWish = $dbh->prepare("
            UPDATE tblwishlist 
            SET status='removed'
            WHERE user_id=? AND product_id=?
        ");
        $updateWish->execute([$user_id, $pid]);

        echo json_encode([
            "status" => "success",
            "message" => "Moved to cart"
        ]);
    }

    exit;
}

/* =========================
   FETCH WISHLIST (ONLY ACTIVE)
========================= */
$stmt = $dbh->prepare("
    SELECT w.*, p.name, p.price, p.image, p.product_id
    FROM tblwishlist w
    JOIN products p ON p.product_id = w.product_id
    WHERE w.user_id = ?
    AND w.status = 'active'
    ORDER BY w.created_at DESC
");

$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Wishlist - My PC Store</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="newstyle.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.dark-card{
    background:#121212;
    border:1px solid #2a2a2a;
    border-radius:12px;
    padding:20px;
}

.product-img{
    width:100%;
    height:140px;
    object-fit:cover;
    border-radius:10px;
}

.price{
    color:#d4af37;
    font-weight:bold;
}

.btn-gold{
    background:#d4af37;
    color:#000;
    border:none;
}
.btn-dark2{
    background:#1e1e1e;
    color:#fff;
    border:1px solid #333;
}
</style>
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="container py-5">

<h2 class="mb-4 text-warning">
    <i class="fa fa-heart"></i> My Wishlist
</h2>

<?php if(count($items) == 0): ?>

    <div class="dark-card text-center py-5">
        <i class="fa fa-heart-broken fa-3x text-secondary mb-3"></i>
        <h4>No wishlist items</h4>
        <a href="index.php" class="btn btn-gold mt-3">Start Shopping</a>
    </div>

<?php else: ?>

<div class="row g-4">

<?php foreach($items as $row): ?>

<div class="col-md-4">

<div class="dark-card">

    <img src="<?php echo $row['image']; ?>" class="product-img mb-3">

    <h5><?php echo $row['name']; ?></h5>

    <div class="price mb-2">
        RM <?php echo number_format($row['price'],2); ?>
    </div>

    <div class="d-flex gap-2">

        <a href="product_details.php?id=<?php echo $row['product_id']; ?>"
           class="btn btn-dark2 w-100">
            View
        </a>

        <button class="btn btn-gold w-100"
                onclick="moveToCart(<?php echo $row['product_id']; ?>)">
            Cart
        </button>

    </div>

    <button class="btn btn-outline-danger w-100 mt-2"
            onclick="removeWishlist(<?php echo $row['wishlist_id']; ?>)">
        Remove
    </button>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>

<script>

/* REMOVE WISHLIST */
function removeWishlist(id){

    Swal.fire({
        title:'Remove item?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#d4af37'
    }).then((res)=>{

        if(res.isConfirmed){

            fetch('wishlist.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'remove_wishlist=1&wishlist_id='+id
            })
            .then(res=>res.json())
            .then(data=>{
                Swal.fire(data.message).then(()=>location.reload());
            });

        }

    });

}

/* MOVE TO CART */
function moveToCart(pid){

    fetch('wishlist.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'move_to_cart=1&product_id='+pid
    })
    .then(res=>res.json())
    .then(data=>{
        Swal.fire({
            icon:'success',
            title:data.message
        }).then(()=>location.reload());
    });

}

</script>

<?php include('includes/footer.php'); ?>

</body>
</html>