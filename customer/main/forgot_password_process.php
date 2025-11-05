<?php
session_start();

// 导入PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 根据你的文件结构调整路径
require_once '../../phpmailer/src/Exception.php';
require_once '../../phpmailer/src/PHPMailer.php';
require_once '../../phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $_SESSION['error'] = "Please enter your email address.";
        header("Location: forgot_password.php");
        exit;
    }

    try {
        // 直接创建数据库连接
        $host = 'localhost';
        $dbname = 'spicefusion'; // 替换为你的数据库名
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查邮箱是否存在
        $stmt = $pdo->prepare("SELECT user_id, email, first_name FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // 生成重置令牌
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // 保存令牌到数据库
            $update_stmt = $pdo->prepare("UPDATE user SET reset_token = ?, token_expiry = ? WHERE user_id = ?");
            $update_stmt->execute([$reset_token, $token_expiry, $user['user_id']]);
            
            // 创建重置链接
            $reset_link = "http://localhost/FYP/customer/main/auth/reset_password.php?token=" . $reset_token;
            
            // 使用PHPMailer发送邮件
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'limxingyi2626@gmail.com';
                $mail->Password   = 'binojjwnutvwzzot';
                $mail->SMTPSecure = 'ssl';
                $mail->Port       = 465;

                //Recipients
                $mail->setFrom('limxingyi2626@gmail.com', 'Spice Fusion');
                $mail->addAddress($email, $user['first_name']);
                
                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request - Spice Fusion';
                $mail->Body    = "
                    <html>
                    <head>
                        <title>Password Reset</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                            .content { padding: 20px; background: #f9f9f9; }
                            .button { background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; }
                            .footer { padding: 20px; text-align: center; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Spice Fusion</h1>
                                <h2>Password Reset Request</h2>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($user['first_name']) . "</strong>,</p>
                                <p>You requested to reset your password. Click the button below to reset your password:</p>
                                <p style='text-align: center;'>
                                    <a href='" . $reset_link . "' class='button'>Reset Password</a>
                                </p>
                                <p>Or copy and paste this link in your browser:</p>
                                <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 4px;'>" . $reset_link . "</p>
                                <p><strong>This link will expire in 1 hour.</strong></p>
                                <p>If you didn't request this, please ignore this email and your password will remain unchanged.</p>
                            </div>
                            <div class='footer'>
                                <p>Best regards,<br><strong>Spice Fusion Team</strong></p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                
                // 可选的纯文本版本
                $mail->AltBody = "Hello " . $user['first_name'] . ",\n\nYou requested to reset your password. Please use the following link to reset your password:\n\n" . $reset_link . "\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nSpice Fusion Team";

                $mail->send();
                $_SESSION['status'] = "Password reset link has been sent to your email!";
                
            } catch (Exception $e) {
                // 如果邮件发送失败，显示重置链接用于测试
                error_log("Mailer Error: " . $mail->ErrorInfo);
                $_SESSION['status'] = "Email sent failed, but here's your reset link for testing: <br><a href='$reset_link' style='color: #007bff;'>Click here to reset password</a><br><small>This is shown because email sending failed.</small>";
            }
            
        } else {
            $_SESSION['error'] = "No account found with that email address.";
        }
        
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $_SESSION['error'] = "System error. Please try again later.";
    }
    
    header("Location: forgot_password.php");
    exit;
} else {
    header("Location: forgot_password.php");
    exit;
}