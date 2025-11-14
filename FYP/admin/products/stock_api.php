<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT product_id, stock_quantity FROM product');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>

