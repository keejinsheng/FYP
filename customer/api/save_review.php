<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = sanitize($_POST['comment'] ?? '');

// Validation
if ($order_id <= 0 || $product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order or product ID']);
    exit();
}

// Rating must be between 0 and 5 (0 means not rated, 1-5 means rated)
if ($rating < 0 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5 stars']);
    exit();
}

// If rating is 0, don't save the review
if ($rating === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a rating (1-5 stars)']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Verify that the order belongs to the user and contains this product
    $stmt = $pdo->prepare("
        SELECT o.order_id, oi.product_id 
        FROM `order` o
        JOIN order_item oi ON o.order_id = oi.order_id
        WHERE o.order_id = ? AND o.user_id = ? AND oi.product_id = ?
    ");
    $stmt->execute([$order_id, $user_id, $product_id]);
    $order_product = $stmt->fetch();
    
    if (!$order_product) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Order or product not found']);
        exit();
    }
    
    // Check if review already exists for this order and product
    $stmt = $pdo->prepare("
        SELECT review_id FROM review 
        WHERE order_id = ? AND product_id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $product_id, $user_id]);
    $existing_review = $stmt->fetch();
    
    if ($existing_review) {
        // Update existing review
        $stmt = $pdo->prepare("
            UPDATE review 
            SET rating = ?, comment = ?, created_at = CURRENT_TIMESTAMP
            WHERE review_id = ?
        ");
        $stmt->execute([$rating, $comment, $existing_review['review_id']]);
        $review_id = $existing_review['review_id'];
        $action = 'updated';
    } else {
        // Insert new review
        $stmt = $pdo->prepare("
            INSERT INTO review (user_id, product_id, order_id, rating, comment, is_verified_purchase, is_approved)
            VALUES (?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([$user_id, $product_id, $order_id, $rating, $comment]);
        $review_id = $pdo->lastInsertId();
        $action = 'saved';
    }
    
    // Fetch the saved review to return
    $stmt = $pdo->prepare("SELECT * FROM review WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $saved_review = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Review ' . $action . ' successfully',
        'review' => $saved_review
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving review. Please try again.']);
}
?>

