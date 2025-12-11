<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$bank_name = sanitize($_POST['bank_name'] ?? '');
$cardholder_name = sanitize($_POST['cardholder_name'] ?? '');
$card_number = sanitize($_POST['card_number'] ?? '');
$expiry_date = sanitize($_POST['expiry_date'] ?? '');
$cvv = sanitize($_POST['cvv'] ?? '');

// Validation
if (empty($bank_name) || empty($cardholder_name) || empty($card_number) || empty($expiry_date) || empty($cvv)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all required bank information']);
    exit();
}

// Clean card number
$card_number_clean = preg_replace('/\s+/', '', $card_number);

// Verify bank information against dummy_bank table
$is_verified = verifyBankInfo($bank_name, $cardholder_name, $card_number_clean, $expiry_date, $cvv);

if ($is_verified) {
    echo json_encode([
        'success' => true,
        'message' => 'Bank information verified successfully',
        'verified' => true
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid bank information. Please check your bank details and try again.',
        'verified' => false
    ]);
}
?>

