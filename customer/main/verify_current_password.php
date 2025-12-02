<?php
require_once '../../config/database.php';
header("Content-Type: application/json; charset=utf-8");

// 确保session已启动（database.php应该已经启动了，但为了安全起见再检查一次）
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$password = $_POST['password'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    // 检查 password_hash 字段，如果没有则检查 password 字段（向后兼容）
    $stmt = $pdo->prepare("SELECT password_hash, password FROM user WHERE user_id=?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    // 优先使用 password_hash，如果没有则使用 password
    $stored_password = $result['password_hash'] ?? $result['password'] ?? null;
    
    if (!$stored_password) {
        echo json_encode(['status' => 'error', 'message' => 'Password field not found']);
        exit;
    }
    
    // 验证密码
    $match = password_verify($password, $stored_password);
    echo json_encode(['status' => 'success', 'match' => $match]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

