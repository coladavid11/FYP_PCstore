<?php
session_start();
header('Content-Type: application/json');

include('includes/config.php'); // contains $dbh

if(!isset($_SESSION['user_id'])){
    echo json_encode(['status'=>'login_required','message'=>'Login first']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$qty = $_POST['qty'];

/* GET PRODUCT */
$stmt = $dbh->prepare("SELECT * FROM products WHERE product_id=?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product){
    echo json_encode(['status'=>'error','message'=>'Product not found']);
    exit();
}

$subtotal = $product['price'] * $qty;

/* INSERT CART */
$stmt = $dbh->prepare("
INSERT INTO tblcart
(user_id, product_id, product_name, product_image, product_price, quantity, subtotal, created_at, updated_at, status)
VALUES (?,?,?,?,?,?,?,?,?,?)
");

$success = $stmt->execute([
    $user_id,
    $product_id,
    $product['name'],
    $product['image'],
    $product['price'],
    $qty,
    $subtotal,
    date("Y-m-d H:i:s"),
    date("Y-m-d H:i:s"),
    'active'
]);

if($success){
    echo json_encode(['status'=>'success','message'=>'Added to cart']);
}else{
    echo json_encode(['status'=>'error','message'=>'Insert failed']);
}
?>