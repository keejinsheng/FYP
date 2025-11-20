<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();

// Get order_id from request
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

try {
    // Fetch order details with customer and payment info
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
               p.payment_method, p.payment_status, p.amount as payment_amount
        FROM `order` o
        JOIN user u ON o.user_id = u.user_id
        LEFT JOIN payment p ON o.order_id = p.order_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Fetch order items with product details
    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name, p.image
        FROM order_item oi
        JOIN product p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.item_id
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response
    $response = [
        'order' => $order,
        'items' => $order_items
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

