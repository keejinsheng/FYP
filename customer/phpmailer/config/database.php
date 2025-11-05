<?php
session_start();
// 修改为正确的路径
require_once '../config/database.php';

$page_title = "Forgot Password";

// 处理邮件发送
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    try {
        $pdo = getDBConnection();
        
        // 检查邮箱是否存在
        $stmt = $pdo->prepare("SELECT user_id, email, first_name FROM user WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // 生成重置令牌
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // 保存令牌到数据库
            $update_stmt = $pdo->prepare("UPDATE user SET reset_token = ?, token_expiry = ? WHERE user_id = ?");
            $update_stmt->execute([$reset_token, $token_expiry, $user['user_id']]);
            
            // 使用正确的路径生成重置链接
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            // 正确的重置链接
            $reset_link = "$protocol://$host/FYP/customer/main/reset_password.php?token=$reset_token";
            
            // 记录并显示重置链接
            sendResetEmail($email, $reset_link);
            
            $_SESSION['status'] = "Password reset link has been generated!";
            $_SESSION['user_email'] = $email;
            
        } else {
            // 出于安全考虑，不提示邮箱是否存在
            $_SESSION['status'] = "If the email is registered, a reset link has been sent.";
        }
        
        header("Location: forgot_password.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "System error. Please try again.";
        header("Location: forgot_password.php");
        exit;
    }
}
?>