<?php
session_start();
require_once '../../config/database.php';

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
            
            // 生成重置链接
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            
            // 正确的重置链接
            $reset_link = "$protocol://$host/FYP/customer/main/reset_password.php?token=" . urlencode($reset_token);
            
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

// 邮件发送函数 - 在页面显示链接
function sendResetEmail($to_email, $reset_link) {
    // 记录到日志文件
    $log_message = "[" . date('Y-m-d H:i:s') . "] Password reset for: $to_email\n";
    $log_message .= "Reset link: $reset_link\n";
    $log_message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    $log_message .= "----------------------------------------\n";
    
    file_put_contents('password_reset_log.txt', $log_message, FILE_APPEND);
    
    // 在 session 中存储调试信息
    $_SESSION['debug_reset_link'] = $reset_link;
    $_SESSION['debug_email'] = $to_email;
    
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .auth-container {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .auth-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #FF4B2B;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(45deg, #FF4B2B, #FF416C);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #FF4B2B;
            text-decoration: none;
            font-weight: 500;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            word-break: break-all;
        }
        
        .debug-info h3 {
            margin-bottom: 0.5rem;
            color: #856404;
        }
        
        .reset-link {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
            word-break: break-all;
            font-family: monospace;
        }
        
        .copy-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        
        .copy-btn:hover {
            background: #545b62;
        }
        
        .test-link {
            display: inline-block;
            margin-top: 0.5rem;
            color: #007bff;
            text-decoration: none;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            margin-left: 10px;
        }
        
        .test-link:hover {
            background: #0056b3;
            text-decoration: none;
            color: white;
        }
        
        .link-actions {
            margin-top: 1rem;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Reset Your Password</h1>
            <p>Enter your email address to receive a reset link</p>
        </div>

        <?php if (isset($_SESSION['status'])): ?>
            <div class="message success">
                <?php echo $_SESSION['status']; ?>
            </div>
            <?php unset($_SESSION['status']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['debug_reset_link'])): ?>
            <div class="debug-info">
                <h3>🔧 Development Mode - Reset Link</h3>
                <p>Email: <strong><?php echo htmlspecialchars($_SESSION['debug_email'] ?? 'Unknown'); ?></strong></p>
                <p>Since email sending is not configured in development, please use this link to reset your password:</p>
                
                <div class="reset-link">
                    <?php echo htmlspecialchars($_SESSION['debug_reset_link']); ?>
                </div>
                
                <div class="link-actions">
                    <button class="copy-btn" onclick="copyResetLink()">Copy Link</button>
                    <a href="<?php echo $_SESSION['debug_reset_link']; ?>" class="test-link" target="_blank">
                        🔗 Test This Link
                    </a>
                </div>
                <span id="copy-message" style="margin-left: 10px; color: green; display: none;">✓ Copied!</span>
                
                <p style="margin-top: 1rem; font-size: 0.9rem;">
                    <strong>Note:</strong> In production, this link would be sent via email automatically.
                </p>
                
                <!-- 添加清除按钮 -->
                <div style="margin-top: 1rem; text-align: center;">
                    <button onclick="clearResetLink()" style="padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Clear This Link
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Enter your email address"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <button type="submit" class="submit-btn">Generate Reset Link</button>
        </form>

        <div class="back-link">
            <a href="login/">← Back to Login</a>
        </div>
    </div>

    <script>
        function copyResetLink() {
            const resetLink = document.querySelector('.reset-link').textContent;
            navigator.clipboard.writeText(resetLink).then(() => {
                const message = document.getElementById('copy-message');
                message.style.display = 'inline';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 2000);
            });
        }
        
        function clearResetLink() {
            // 通过刷新页面来清除session变量
            window.location.href = 'forgot_password.php?clear=1';
        }
        
        // 如果URL中有clear参数，显示清除消息
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('clear') === '1') {
            alert('Reset link cleared. You can generate a new one.');
        }
    </script>
</body>
</html>