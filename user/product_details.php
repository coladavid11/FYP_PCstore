<?php
session_start();
include('includes/config.php');

$product_id = $_GET['id'] ?? 0;

/* ================= FETCH PRODUCT ================= */
$stmt = $dbh->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found");
}

/* ================= IMAGES (UP TO 10) ================= 
$images = [];
for ($i = 1; $i <= 10; $i++) {
    $key = "image$i";
    if (!empty($product[$key])) {
        $images[] = $product[$key];
    }
}*/

/* ================= RELATED PRODUCTS ================= */
$stmt2 = $dbh->prepare("
    SELECT * FROM products
    WHERE category_id = ?
    AND product_id != ?
    LIMIT 4
");
$stmt2->execute([$product['category_id'], $product_id]);
$related = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($product['name']); ?> - My PC Store</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- External CSS -->
<link rel="stylesheet" href="newstyle.css">

<style>
body{
    background:#0f0f0f;
    color:#fff;
    font-family:'Poppins',sans-serif;
}

.dark-card{
    background:#121212;
    border:1px solid #2a2a2a;
    border-radius:10px;
}

.text-soft{color:#aaa;}

.thumb-img{
    width:70px;height:70px;
    object-fit:cover;
    cursor:pointer;
    border:2px solid #333;
    border-radius:6px;
}
</style>

</head>

<body>

<?php include('includes/header.php'); ?>

<div class="container py-5">

<div class="row g-4">

<!-- ================= LEFT IMAGE ================= -->
<div class="col-lg-6">

    <div class="dark-card p-3">

        <img id="mainImage"
             src="<?php echo $product['image']; ?>"
             class="img-fluid rounded"
             style="width:100%;height:420px;object-fit:cover;">

    </div>
</div>

<!-- ================= RIGHT INFO ================= -->
<div class="col-lg-6">

<div class="dark-card p-4">

    <h2><?php echo $product['name']; ?></h2>

    <p class="text-soft"><?php echo $product['category_id']; ?></p>

    <h3 style="color:#d4af37;">
        RM <span id="price"><?php echo $product['price']; ?></span>
    </h3>

    <!-- STOCK -->
    <div class="mt-2">
        <small class="text-soft">Stock</small>
        <div class="progress" style="height:6px;">
            <div class="progress-bar bg-warning"
                 style="width:<?php echo min(100,$product['stock']); ?>%"></div>
        </div>
    </div>

    <p class="mt-3"><?php echo $product['description']; ?></p>

    <!-- QTY -->
    <div class="d-flex align-items-center gap-3 mt-3">

        <button class="btn btn-dark" onclick="qtyMinus()">-</button>

        <input id="qty" value="1"
               class="form-control text-center"
               style="width:70px;">

        <button class="btn btn-dark" onclick="qtyPlus()">+</button>

    </div>

    <!-- SUBTOTAL -->
    <h5 class="mt-3 text-warning">
        Subtotal: RM <span id="subtotal"><?php echo $product['price']; ?></span>
    </h5>

    <!-- ADD CART -->
    <button class="btn btn-warning w-100 mt-3"
            onclick="addToCart(<?php echo $product['product_id']; ?>)">
        Add to Cart
    </button>

</div>
</div>

</div>

<!-- ================= TABS ================= -->
<div class="dark-card p-4 mt-4">

<ul class="nav nav-tabs">

<li class="nav-item">
<a class="nav-link active" data-bs-toggle="tab" href="#desc">Description</a>
</li>

<li class="nav-item">
<a class="nav-link" data-bs-toggle="tab" href="#spec">Specs</a>
</li>

<li class="nav-item">
<a class="nav-link" data-bs-toggle="tab" href="#review">Reviews</a>
</li>

</ul>

<div class="tab-content mt-3">

<div class="tab-pane fade show active" id="desc">
<?php echo $product['description']; ?>
</div>

<div class="tab-pane fade" id="spec">
<p class="text-soft">Specifications coming soon</p>
</div>

<div class="tab-pane fade" id="review">
<p class="text-soft">Reviews system later</p>
</div>

</div>

</div>

<!-- ================= RELATED ================= -->
<h4 class="mt-5">Related Products</h4>

<div class="row g-3">

<?php foreach($related as $r): ?>

<div class="col-md-3">

<div class="dark-card p-2">

<img src="<?php echo $r['image1']; ?>"
     style="width:100%;height:120px;object-fit:cover;">

<h6 class="mt-2"><?php echo $r['name']; ?></h6>

<p class="text-warning">RM <?php echo $r['price']; ?></p>

<a href="product_details.php?id=<?php echo $r['product_id']; ?>"
   class="btn btn-outline-warning btn-sm w-100">
   View
</a>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<?php include('includes/footer.php'); ?>

<!-- ================= JS ================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

function changeImage(el){
    document.getElementById('mainImage').src = el.src;
}

function qtyPlus(){
    let q = document.getElementById('qty');
    q.value++;
    updateSubtotal();
}

function qtyMinus(){
    let q = document.getElementById('qty');
    if(q.value>1) q.value--;
    updateSubtotal();
}

function updateSubtotal(){
    let price = parseFloat(document.getElementById('price').innerText);
    let qty = parseInt(document.getElementById('qty').value);
    document.getElementById('subtotal').innerText = (price*qty).toFixed(2);
}

function addToCart(pid){

    let qty = document.getElementById('qty').value;

    fetch('add_to_cart.php', {
        method:'POST',
        headers:{
            'Content-Type':'application/x-www-form-urlencoded'
        },
        body:'product_id=' + pid + '&qty=' + qty
    })

    .then(res => res.json())

    .then(data => {

        // SUCCESS
        if(data.status == 'success'){

            Swal.fire({
                icon:'success',
                title:'Added to Cart',
                text:data.message,
                background:'#1a1a1a',
                color:'#fff',
                confirmButtonColor:'#d4af37'
            });

        }

        // LOGIN REQUIRED
        else if(data.status == 'login_required'){

            Swal.fire({
                icon:'warning',
                title:'Login Required',
                text:data.message,
                background:'#1a1a1a',
                color:'#fff',
                confirmButtonColor:'#d4af37'
            }).then(() => {

                window.location.href = 'login.php';

            });

        }

        // OTHER ERROR
        else{

            Swal.fire({
                icon:'error',
                title:'Error',
                text:data.message
            });

        }

    });

}

</script>

</body>
</html>