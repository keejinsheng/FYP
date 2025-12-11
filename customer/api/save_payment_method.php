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

$payment_method = sanitize($_POST['payment_method'] ?? '');
$cardholder_name = sanitize($_POST['cardholder_name'] ?? '');
$card_number = sanitize($_POST['card_number'] ?? '');
$expiry_date = sanitize($_POST['expiry_date'] ?? '');
$cvv = sanitize($_POST['cvv'] ?? '');
$bank_name = sanitize($_POST['bank_name'] ?? '');

// Validation
if (empty($payment_method) || ($payment_method !== 'Credit Card' && $payment_method !== 'Online Banking')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit();
}

if (empty($cardholder_name) || empty($card_number) || empty($expiry_date) || empty($cvv)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required card information']);
    exit();
}

if ($payment_method === 'Online Banking' && empty($bank_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter your bank name']);
    exit();
}

// Validate cardholder name
if (!preg_match('/^[a-zA-Z\s\'-]+$/', $cardholder_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cardholder name must contain only letters and spaces']);
    exit();
}

// Validate card number
$card_number_clean = preg_replace('/\s+/', '', $card_number);
if (!preg_match('/^\d+$/', $card_number_clean)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Card number must contain only numbers']);
    exit();
}

if (strlen($card_number_clean) < 13 || strlen($card_number_clean) > 19) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Card number must be between 13 and 19 digits']);
    exit();
}

// Validate expiry date
if (!preg_match('/^\d{2}\/\d{2}$/', $expiry_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Expiry date must be in MM/YY format']);
    exit();
}

// Validate CVV
if (!preg_match('/^\d+$/', $cvv) || strlen($cvv) < 3 || strlen($cvv) > 4) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'CVV must be 3 or 4 digits']);
    exit();
}

// Validate bank name for Online Banking
if ($payment_method === 'Online Banking' && !preg_match('/^[a-zA-Z\s\'-]+$/', $bank_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bank name must contain only letters and spaces']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Check if saved_payment_methods table exists, if not create it
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS saved_payment_methods (
            payment_method_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            payment_method_type ENUM('Credit Card', 'Online Banking') NOT NULL,
            cardholder_name VARCHAR(100) NOT NULL,
            card_number_encrypted VARCHAR(255) NOT NULL,
            card_last_four VARCHAR(4) NOT NULL,
            expiry_date VARCHAR(5) NOT NULL,
            bank_name VARCHAR(100) DEFAULT NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    
    $pdo->beginTransaction();
    
    // Encrypt card number (simple encryption - in production, use proper encryption)
    $card_last_four = substr($card_number_clean, -4);
    $card_number_encrypted = base64_encode($card_number_clean); // Simple encoding - use proper encryption in production
    
    // If this is set as default, remove default from other payment methods
    // For now, we'll set the first saved payment method as default
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_payment_methods WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $payment_count = $stmt->fetchColumn();
    
    $is_default = ($payment_count == 0) ? 1 : 0;
    
    // Insert new payment method
    $stmt = $pdo->prepare("
        INSERT INTO saved_payment_methods (user_id, payment_method_type, cardholder_name, card_number_encrypted, card_last_four, expiry_date, bank_name, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, 
        $payment_method, 
        $cardholder_name, 
        $card_number_encrypted, 
        $card_last_four, 
        $expiry_date, 
        $bank_name ?: null, 
        $is_default
    ]);
    $payment_method_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    // Fetch the saved payment method to return (without sensitive data)
    $stmt = $pdo->prepare("
        SELECT payment_method_id, payment_method_type, cardholder_name, card_last_four, expiry_date, bank_name, is_default
        FROM saved_payment_methods 
        WHERE payment_method_id = ? AND user_id = ?
    ");
    $stmt->execute([$payment_method_id, $user_id]);
    $saved_payment = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment method saved successfully',
        'payment_method' => $saved_payment
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving payment method. Please try again.']);
}
?>

