<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();

// Get user_id from request
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

try {
    // Fetch customer details
    $stmt = $pdo->prepare("
        SELECT user_id, username, email, first_name, last_name, phone, 
               date_of_birth, gender, profile_image, is_active, created_at
        FROM user
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($customer);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

