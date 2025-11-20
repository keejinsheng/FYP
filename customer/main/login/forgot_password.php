<?php
require_once '../../../config/database.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../phpmailer/src/Exception.php';
require_once '../../phpmailer/src/PHPMailer.php';
require_once '../../phpmailer/src/SMTP.php';

$error_message = '';
$success_message = '';
$step = isset($_POST['step']) ? (int)$_POST['step'] : (isset($_SESSION['otp_email']) ? 2 : 1);
$email = '';

// Ensure required columns exist on `user` table (idempotent)
function ensureOtpColumns(PDO $pdo) {
    // reset_token
    $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'reset_token'");
    $q->execute();
    if ((int)$q->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `user` ADD COLUMN `reset_token` VARCHAR(100) NULL");
    }
    // token_expiry
    $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'token_expiry'");
    $q->execute();
    if ((int)$q->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `user` ADD COLUMN `token_expiry` DATETIME NULL");
    }
}

// Step 1: Send OTP to email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $email = sanitize($_POST['email'] ?? '');
    if (empty($email)) {
        $error_message = 'Please enter your email address';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT user_id, email, first_name FROM user WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate 6-digit OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Store OTP in database (using reset_token field temporarily)
                ensureOtpColumns($pdo);
                $stmt = $pdo->prepare("UPDATE user SET reset_token = ?, token_expiry = ? WHERE user_id = ?");
                $stmt->execute([$otp, $otp_expiry, $user['user_id']]);
                
                // Send OTP via email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'spicefusion0711@gmail.com';
                    $mail->Password   = 'pcfebgfgseufvukz';
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port       = 465;
                    
                    $mail->setFrom('spicefusion0711@gmail.com', 'Spice Fusion');
                    $mail->addAddress($email, $user['first_name']);
                    
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP - Spice Fusion';
                    $mail->Body    = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: #FF4B2B; color: white; padding: 20px; text-align: center; }
                                .content { padding: 20px; background: #f9f9f9; }
                                .otp-box { background: #fff; border: 2px solid #FF4B2B; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                                .otp-code { font-size: 32px; font-weight: bold; color: #FF4B2B; letter-spacing: 8px; }
                                .footer { padding: 20px; text-align: center; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>Spice Fusion</h1>
                                    <h2>Password Reset OTP</h2>
                                </div>
                                <div class='content'>
                                    <p>Hello <strong>" . htmlspecialchars($user['first_name']) . "</strong>,</p>
                                    <p>You requested to reset your password. Please use the following OTP code:</p>
                                    <div class='otp-box'>
                                        <div class='otp-code'>" . $otp . "</div>
                                    </div>
                                    <p><strong>This OTP will expire in 10 minutes.</strong></p>
                                    <p>If you didn't request this, please ignore this email and your password will remain unchanged.</p>
                                </div>
                                <div class='footer'>
                                    <p>Best regards,<br><strong>Spice Fusion Team</strong></p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    $mail->AltBody = "Hello " . $user['first_name'] . ",\n\nYour password reset OTP is: " . $otp . "\n\nThis OTP will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nSpice Fusion Team";
                    
                    $mail->send();
                    $_SESSION['otp_email'] = $email;
                    $success_message = 'OTP has been sent to your email address. Please check your inbox.';
                    $step = 2;
                } catch (Exception $e) {
                    $error_message = 'Failed to send OTP. Please try again. Error: ' . $mail->ErrorInfo;
                }
            } else {
                // For security, don't reveal if email exists
                $success_message = 'If the email is registered, an OTP has been sent.';
                $step = 2; // Still show step 2 to prevent email enumeration
                $_SESSION['otp_email'] = $email;
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// Step 2: Verify OTP and reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $email = $_SESSION['otp_email'] ?? sanitize($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email)) {
        $error_message = 'Email session expired. Please start over.';
        $step = 1;
        unset($_SESSION['otp_email']);
    } elseif (empty($otp)) {
        $error_message = 'Please enter the OTP code';
    } elseif (empty($new_password)) {
        $error_message = 'Please enter a new password';
    } elseif (empty($confirm_password)) {
        $error_message = 'Please confirm your new password';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password must be at least 6 characters long';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT user_id, reset_token, token_expiry FROM user WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error_message = 'Invalid email address';
                $step = 1;
                unset($_SESSION['otp_email']);
            } elseif (empty($user['reset_token']) || $user['reset_token'] !== $otp) {
                $error_message = 'Invalid OTP code. Please try again.';
            } elseif (strtotime($user['token_expiry']) < time()) {
                $error_message = 'OTP has expired. Please request a new one.';
                $step = 1;
                unset($_SESSION['otp_email']);
                // Clear expired OTP
                $stmt = $pdo->prepare("UPDATE user SET reset_token = NULL, token_expiry = NULL WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
            } else {
                // OTP verified, reset password
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE user_id = ?");
                $stmt->execute([$password_hash, $user['user_id']]);
                
                unset($_SESSION['otp_email']);
                $success_message = 'Password reset successfully! You can now login with your new password.';
                // Redirect to login after 3 seconds
                header("Refresh: 3; url=login.php");
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// If step 2 but no email in session, go back to step 1
if ($step === 2 && empty($_SESSION['otp_email'])) {
    $step = 1;
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
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
            --gradient-primary: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            --shadow-soft: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 8px 16px rgba(0, 0, 0, 0.2);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-dark);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-strong);
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-gray);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-error {
            background: rgba(255, 75, 43, 0.1);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid #4CAF50;
            color: #4CAF50;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--background-dark);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .otp-input {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: bold;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Forgot Password</h1>
                <p><?php echo $step === 1 ? 'Enter your email to receive an OTP' : 'Enter the OTP sent to your email'; ?></p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message && $step !== 2): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <form method="POST" action="">
                    <input type="hidden" name="step" value="1">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <button type="submit" class="login-btn">Send OTP</button>
                </form>
            <?php elseif ($step === 2): ?>
                <form method="POST" action="">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['otp_email'] ?? ''); ?>">
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="otp">Enter OTP Code</label>
                        <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" class="otp-input" placeholder="000000">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="login-btn">Reset Password</button>
                </form>
            <?php endif; ?>

            <div class="back-to-login">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>

