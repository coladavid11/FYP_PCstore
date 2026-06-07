<?php
session_start();
header('Content-Type: application/json');

include('includes/config.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'login_required',
        'message' => 'Please login first'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

/* CHECK EXIST */
$stmt = $dbh->prepare("
    SELECT * FROM tblwishlist 
    WHERE user_id=? AND product_id=?
");
$stmt->execute([$user_id, $product_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {

    // TOGGLE STATUS
    if ($row['status'] == 'active') {

        $update = $dbh->prepare("
            UPDATE tblwishlist 
            SET status='removed'
            WHERE wishlist_id=?
        ");
        $update->execute([$row['wishlist_id']]);

        echo json_encode([
            'status' => 'removed',
            'message' => 'Removed from wishlist'
        ]);

    } else {

        $update = $dbh->prepare("
            UPDATE tblwishlist 
            SET status='active'
            WHERE wishlist_id=?
        ");
        $update->execute([$row['wishlist_id']]);

        echo json_encode([
            'status' => 'added',
            'message' => 'Added to wishlist'
        ]);
    }

} else {

    // FIRST TIME INSERT
    $insert = $dbh->prepare("
        INSERT INTO tblwishlist 
        (user_id, product_id, status, created_at)
        VALUES (?, ?, 'active', NOW())
    ");

    $insert->execute([$user_id, $product_id]);

    echo json_encode([
        'status' => 'added',
        'message' => 'Added to wishlist'
    ]);
}
?>