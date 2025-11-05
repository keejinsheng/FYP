<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email address.";
        header("Location: forgot_password.php");
        exit;
    }

    try {
        $pdo = getDBConnection();
        
        // 检查邮箱是否存在
        $stmt = $pdo->prepare("SELECT user_id, email, first_name FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // 生成重置令牌
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1小时后过期
            
            // 保存令牌到数据库
            $update_stmt = $pdo->prepare("UPDATE user SET reset_token = ?, token_expiry = ? WHERE user_id = ?");
            $update_stmt->execute([$reset_token, $token_expiry, $user['user_id']]);
            
            // 创建重置链接
            $reset_link = "http://localhost/FYP/customer/main/auth/reset_password.php?token=" . $reset_token;
            
            // 发送邮件（这里需要配置邮件发送）
            $subject = "Password Reset Request - Spice Fusion";
            $message = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Hello " . $user['first_name'] . ",</p>
                    <p>You requested to reset your password. Click the link below to reset your password:</p>
                    <p><a href='" . $reset_link . "' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <br>
                    <p>Best regards,<br>Spice Fusion Team</p>
                </body>
                </html>
            ";
            
            // 邮件头
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Spice Fusion <noreply@spicefusion.com>" . "\r\n";
            
            // 发送邮件（在生产环境中使用）
            if (mail($email, $subject, $message, $headers)) {
                $_SESSION['status'] = "Password reset link has been sent to your email!";
            } else {
                // 如果邮件发送失败，显示重置链接（用于测试）
                $_SESSION['status'] = "Password reset link: <a href='$reset_link'>Click here</a> (For testing - copy this link)";
            }
            
        } else {
            $_SESSION['error'] = "No account found with that email address.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "System error. Please try again later.";
    }
    
    header("Location: forgot_password.php");
    exit;
} else {
    header("Location: forgot_password.php");
    exit;
}
?>