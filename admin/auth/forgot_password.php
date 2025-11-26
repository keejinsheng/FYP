<?php
require_once '../../config/database.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../customer/phpmailer/src/Exception.php';
require_once '../../customer/phpmailer/src/PHPMailer.php';
require_once '../../customer/phpmailer/src/SMTP.php';

$error_message = '';
$success_message = '';
$step = isset($_POST['step']) ? (int)$_POST['step'] : (isset($_SESSION['admin_otp_email']) ? 2 : 1);
$email = '';

function ensureAdminOtpColumns(PDO $pdo) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_user' AND COLUMN_NAME = 'reset_token'");
    $q->execute();
    if ((int)$q->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `admin_user` ADD COLUMN `reset_token` VARCHAR(100) NULL");
    }
    $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_user' AND COLUMN_NAME = 'token_expiry'");
    $q->execute();
    if ((int)$q->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `admin_user` ADD COLUMN `token_expiry` DATETIME NULL");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $email = sanitize($_POST['email'] ?? '');
    if (empty($email)) {
        $error_message = 'Please enter your email address';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT admin_id, email, first_name FROM admin_user WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin) {
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                ensureAdminOtpColumns($pdo);
                $stmt = $pdo->prepare("UPDATE admin_user SET reset_token = ?, token_expiry = ? WHERE admin_id = ?");
                $stmt->execute([$otp, $otp_expiry, $admin['admin_id']]);

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
                    $mail->addAddress($email, $admin['first_name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Admin Password Reset OTP - Spice Fusion';
                    $mail->Body = "
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
                                    <h1>Spice Fusion Admin</h1>
                                    <h2>Password Reset OTP</h2>
                                </div>
                                <div class='content'>
                                    <p>Hello <strong>" . htmlspecialchars($admin['first_name']) . "</strong>,</p>
                                    <p>Please use the following OTP to reset your admin password:</p>
                                    <div class='otp-box'>
                                        <div class='otp-code'>" . $otp . "</div>
                                    </div>
                                    <p><strong>This OTP will expire in 10 minutes.</strong></p>
                                    <p>If you didn't request this, please ignore this email.</p>
                                </div>
                                <div class='footer'>
                                    <p>Best regards,<br><strong>Spice Fusion Team</strong></p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    $mail->AltBody = "Hello " . $admin['first_name'] . ",\n\nYour admin password reset OTP is: " . $otp . "\n\nThis OTP will expire in 10 minutes.";
                    $mail->send();

                    $_SESSION['admin_otp_email'] = $email;
                    $success_message = 'OTP has been sent to your email address.';
                    $step = 2;
                } catch (Exception $e) {
                    $error_message = 'Failed to send OTP. Please try again later.';
                }
            } else {
                $success_message = 'If the email is registered, an OTP has been sent.';
                $step = 2;
                $_SESSION['admin_otp_email'] = $email;
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $email = $_SESSION['admin_otp_email'] ?? sanitize($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email)) {
        $error_message = 'Email session expired. Please start over.';
        $step = 1;
        unset($_SESSION['admin_otp_email']);
    } elseif (empty($otp)) {
        $error_message = 'Please enter the OTP code';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please enter and confirm the new password';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password must be at least 6 characters long';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT admin_id, reset_token, token_expiry FROM admin_user WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin) {
                $error_message = 'Invalid email address';
                $step = 1;
                unset($_SESSION['admin_otp_email']);
            } elseif (empty($admin['reset_token']) || $admin['reset_token'] !== $otp) {
                $error_message = 'Invalid OTP code.';
            } elseif (strtotime($admin['token_expiry']) < time()) {
                $error_message = 'OTP has expired. Please request a new one.';
                $step = 1;
                unset($_SESSION['admin_otp_email']);
                $stmt = $pdo->prepare("UPDATE admin_user SET reset_token = NULL, token_expiry = NULL WHERE admin_id = ?");
                $stmt->execute([$admin['admin_id']]);
            } else {
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE admin_user SET password_hash = ?, reset_token = NULL, token_expiry = NULL WHERE admin_id = ?");
                $stmt->execute([$password_hash, $admin['admin_id']]);

                unset($_SESSION['admin_otp_email']);
                $success_message = 'Password reset successfully! Redirecting to login...';
                header("Refresh: 3; url=login.php");
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

if ($step === 2 && empty($_SESSION['admin_otp_email'])) {
    $step = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Forgot Password - Spice Fusion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        :root {
            --primary-color: #FF4B2B;
            --secondary-color: #FF416C;
            --background-dark: #1a1a1a;
            --text-light: #ffffff;
            --text-gray: #a0a0a0;
            --card-bg: #2a2a2a;
            --gradient-primary: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
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
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-strong);
            width: 100%;
            max-width: 420px;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .header p {
            color: var(--text-gray);
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .alert-error {
            background: rgba(255, 75, 43, 0.1);
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #28a745;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }
        input {
            width: 100%;
            padding: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--text-gray);
            background: var(--background-dark);
            color: var(--text-light);
            font-size: 1rem;
        }
        .otp-input {
            text-align: center;
            letter-spacing: 0.4rem;
            font-size: 1.4rem;
            font-weight: bold;
        }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 50px;
            background: var(--gradient-primary);
            color: var(--text-light);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Admin Forgot Password</h1>
            <p><?php echo $step === 1 ? 'Enter your admin email to receive an OTP' : 'Enter the OTP sent to your email'; ?></p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="step" value="1">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <button type="submit" class="submit-btn">Send OTP</button>
            </form>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['admin_otp_email'] ?? ''); ?>">

                <div class="form-group">
                    <label for="otp">OTP Code</label>
                    <input type="text" id="otp" name="otp" maxlength="6" required class="otp-input" pattern="[0-9]{6}" placeholder="000000">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>

