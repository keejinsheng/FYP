<?php
require_once '../../config/database.php';

// Check if admin is logged in
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();

// Get data from request
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Validate is_active value (should be 0 or 1)
$is_active = ($is_active == 1) ? 1 : 0;

try {
    // Update customer status
    $stmt = $pdo->prepare("UPDATE user SET is_active = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$is_active, $user_id]);

    if ($stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'is_active' => $is_active]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

