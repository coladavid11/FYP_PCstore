<?php
session_start();
header('Content-Type: application/json');

include('includes/config.php'); // contains $dbh

/* ── AUTH CHECK ── */
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status'  => 'login_required',
        'message' => 'Please login to add items to your cart.'
    ]);
    exit();
}

$user_id    = $_SESSION['user_id'];
$product_id = intval($_POST['product_id'] ?? 0);
$qty        = intval($_POST['qty'] ?? 1);

/* ── BASIC INPUT VALIDATION ── */
if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit();
}

if ($qty <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Quantity must be at least 1.']);
    exit();
}

/* ── FETCH PRODUCT + STOCK (always from DB, never trust frontend) ── */
$stmt = $dbh->prepare("SELECT product_id, name, image, price, stock FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
    exit();
}

$available_stock = intval($product['stock']);

/* ── CHECK: existing cart qty for this user + product ── */
$cartStmt = $dbh->prepare("
    SELECT quantity FROM tblcart
    WHERE user_id = ? AND product_id = ? AND status = 'active'
    LIMIT 1
");
$cartStmt->execute([$user_id, $product_id]);
$existingCart = $cartStmt->fetch(PDO::FETCH_ASSOC);

$already_in_cart = $existingCart ? intval($existingCart['quantity']) : 0;
$total_requested = $already_in_cart + $qty;

/* ── STOCK VALIDATION (backend enforced) ── */
if ($available_stock <= 0) {
    echo json_encode([
        'status'          => 'out_of_stock',
        'message'         => 'Sorry, this product is currently out of stock.',
        'available_stock' => 0
    ]);
    exit();
}

if ($qty > $available_stock) {
    echo json_encode([
        'status'          => 'error',
        'message'         => "Only {$available_stock} item(s) available in stock.",
        'available_stock' => $available_stock
    ]);
    exit();
}

if ($total_requested > $available_stock) {
    $can_add = $available_stock - $already_in_cart;
    echo json_encode([
        'status'          => 'error',
        'message'         => "You already have {$already_in_cart} in your cart. You can only add {$can_add} more (stock: {$available_stock}).",
        'available_stock' => $available_stock,
        'already_in_cart' => $already_in_cart,
        'can_add_more'    => $can_add
    ]);
    exit();
}

/* ── INSERT OR UPDATE CART ── */
$subtotal = $product['price'] * $qty;

if ($existingCart) {
    /* UPDATE: add to existing row */
    $newQty      = $already_in_cart + $qty;
    $newSubtotal = $product['price'] * $newQty;

    $update = $dbh->prepare("
        UPDATE tblcart
        SET quantity   = ?,
            subtotal   = ?,
            updated_at = ?
        WHERE user_id = ? AND product_id = ? AND status = 'active'
    ");
    $success = $update->execute([
        $newQty,
        $newSubtotal,
        date("Y-m-d H:i:s"),
        $user_id,
        $product_id
    ]);

} else {
    /* INSERT: new cart row */
    $insert = $dbh->prepare("
        INSERT INTO tblcart
            (user_id, product_id, product_name, product_image, product_price, quantity, subtotal, created_at, updated_at, status)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $success = $insert->execute([
        $user_id,
        $product_id,
        $product['name'],
        $product['image'],
        $product['price'],
        $qty,
        $subtotal,
        date("Y-m-d H:i:s"),
        date("Y-m-d H:i:s")
    ]);
}

if ($success) {
    echo json_encode([
        'status'          => 'success',
        'message'         => "'{$product['name']}' added to your cart!",
        'available_stock' => $available_stock
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to update cart. Please try again.'
    ]);
}
?>