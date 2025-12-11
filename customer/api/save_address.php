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

$address_line1 = sanitize($_POST['address_line1'] ?? '');
$address_line2 = sanitize($_POST['address_line2'] ?? '');
$city = sanitize($_POST['city'] ?? '');
$state = sanitize($_POST['state'] ?? '');
$postal_code = sanitize($_POST['postal_code'] ?? '');
$is_default = isset($_POST['is_default']) ? 1 : 0;

// Validation
if (empty($address_line1) || empty($city) || empty($state) || empty($postal_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    // If this is set as default, remove default from other addresses
    if ($is_default) {
        $stmt = $pdo->prepare("UPDATE delivery_address SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // If this is the first address, set it as default
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM delivery_address WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $address_count = $stmt->fetchColumn();
    
    if ($address_count == 0) {
        $is_default = 1;
    }
    
    // Insert new address
    $stmt = $pdo->prepare("
        INSERT INTO delivery_address (user_id, address_line1, address_line2, city, state, postal_code, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $address_line1, $address_line2, $city, $state, $postal_code, $is_default]);
    $address_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    // Fetch the saved address to return
    $stmt = $pdo->prepare("SELECT * FROM delivery_address WHERE address_id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);
    $saved_address = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Address saved successfully',
        'address' => $saved_address
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving address. Please try again.']);
}
?>

