<?php
require_once '../../../config/database.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$error_message = '';
$success_message = '';
$forgot_error_message = '';
$forgot_success_message = '';
$show_security_question = false;
$reset_email = '';
$security_question = '';

// Clear reset success session if not in forgot password POST.
if (!isset($_POST['forgot_password_step2'])) {
    unset($_SESSION['reset_success']);
}

// Always clear forgot password state on GET (not POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $forgot_error_message = '';
    $forgot_success_message = '';
    $show_security_question = false;
    $reset_email = '';
    $security_question = '';
}

// Before modal rendering, ensure reset_success is only set after a real POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['reset_success'])) {
    unset($_SESSION['reset_success']);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password']) && !isset($_POST['step'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, first_name, last_name FROM user WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                redirect('../index/index.php');
            } else {
                $error_message = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}

// Always clear forgot password state on GET (not POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $forgot_error_message = "";
    $forgot_success_message = "";
    $show_security_question = false;
    $reset_email = "";
    $security_question = "";
}

// Before modal rendering, ensure reset_success is only set after a real POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['reset_success'])) {
    unset($_SESSION['reset_success']);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password']) && !isset($_POST['step'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, first_name, last_name FROM user WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                redirect('../index/index.php');
            } else {
                $error_message = 'Invalid email or password';
            }
        } catch (Exception $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}

// --- 忘记密码逻辑（从这里开始替换）---
if ((isset($_POST['forgot_password_step1']) && ($_POST['step'] ?? '1') === '1') || (isset($_POST['forgot_password_step2']) && ($_POST['step'] ?? '') === '2')) {
    if (isset($_POST['forgot_password_step1']) && ($_POST['step'] ?? '1') === '1') {
        $email = sanitize($_POST['forgot_email'] ?? '');
        if (empty($email)) {
            $forgot_error_message = 'Please enter your email address';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT u.user_id, u.security_question_id, sq.question, u.email FROM user u LEFT JOIN security_questions sq ON u.security_question_id = sq.id WHERE u.email = ? AND u.is_active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user) {
                    if ($user['security_question_id']) {
                        $show_security_question = true;
                        $reset_email = $email;
                        $security_question = $user['question'];
                        $forgot_success_message = 'Security question found. Please answer it to reset your password.';
                    } else {
                        // 如果没有设置安全问题，提供替代方案
                        $forgot_error_message = 'No security question set for this account. Please contact customer support or try the email reset method.';
                    }
                } else {
                    $forgot_error_message = 'Email address not found or account is inactive.';
                }
            } catch (Exception $e) {
                $forgot_error_message = 'Failed to process request. Please try again. Error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['forgot_password_step2']) && ($_POST['step'] ?? '') === '2') {
        $pdo = getDBConnection();
        $email = sanitize($_POST['reset_email'] ?? '');
        $answer = trim($_POST['security_answer'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // 更详细的验证
        if (empty($answer)) {
            $forgot_error_message = 'Please provide the answer to your security question';
        } elseif (empty($new_password)) {
            $forgot_error_message = 'Please enter a new password';
        } elseif (empty($confirm_password)) {
            $forgot_error_message = 'Please confirm your new password';
        } elseif ($new_password !== $confirm_password) {
            $forgot_error_message = 'Passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $forgot_error_message = 'Password must be at least 6 characters long';
        } else {
            // 验证安全问题答案
            $stmt = $pdo->prepare("SELECT security_answer_hash FROM user WHERE email = ?");
            $stmt->execute([$email]);
            $answer_hash = $stmt->fetchColumn();
            
            if ($answer_hash && password_verify($answer, $answer_hash)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET password_hash = ? WHERE email = ?");
                $result = $stmt->execute([$password_hash, $email]);
                
                if ($result) {
                    $_SESSION['reset_success'] = true;
                    $show_security_question = false;
                    // 清除所有忘记密码相关的session
                    unset($_SESSION['forgot_email']);
                    unset($_SESSION['security_question']);
                } else {
                    $forgot_error_message = 'Password reset failed. Please try again.';
                    $show_security_question = true;
                    $reset_email = $email;
                    // 重新获取安全问题
                    $stmt = $pdo->prepare("SELECT sq.question FROM user u LEFT JOIN security_questions sq ON u.security_question_id = sq.id WHERE u.email = ?");
                    $stmt->execute([$email]);
                    $security_question = $stmt->fetchColumn();
                }
            } else {
                $forgot_error_message = 'Incorrect security answer. Please try again.';
                $show_security_question = true;
                $reset_email = $email;
                $stmt = $pdo->prepare("SELECT sq.question FROM user u LEFT JOIN security_questions sq ON u.security_question_id = sq.id WHERE u.email = ?");
                $stmt->execute([$email]);
                $security_question = $stmt->fetchColumn();
            }
        }
    }
}
// --- 忘记密码逻辑结束 ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Spice Fusion</title>
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

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-gray);
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .forgot-password:hover {
            text-decoration: underline;
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

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-gray);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* Forgot Password Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 400px;
            position: relative;
        }

        .modal-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .modal-header h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .close-modal:hover {
            color: var(--text-light);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }

        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
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
                <h1>Welcome Back</h1>
                <p>Sign in to your Spice Fusion account</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" required style="padding-right: 2.5rem;">
                        <span id="togglePassword" style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--text-gray);">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24">
                                <path id="eyeOpen" stroke="currentColor" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/>
                                <circle id="eyeDot" cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                    <span class="forgot-password" onclick="openForgotModal()">Forgot Password?</span>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="../register/register.php">Sign up here</a>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal" id="forgotModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeForgotModal()">&times;</button>
            <div class="modal-header">
                <h2>Forgot Password</h2>
                <p>Reset your password using your security question</p>
            </div>
            <?php if ($forgot_error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($forgot_error_message); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['reset_success']) && $_SESSION['reset_success']): ?>
                <div class="alert alert-success" style="text-align:center; font-size:1.1rem; margin-bottom:1.5rem;">
                    Password reset successful! You can now login.
                </div>
                <div style="text-align:center;">
                    <button class="login-btn" onclick="closeForgotModal();">Back to Login</button>
                </div>
                <?php unset($_SESSION['reset_success']); ?>
            <?php elseif (!$show_security_question): ?>
                <form method="POST" action="">
                    <input type="hidden" name="step" value="1">
                    <div class="form-group">
                        <label for="forgot_email">Email Address</label>
                        <input type="email" id="forgot_email" name="forgot_email" required>
                    </div>
                    <button type="submit" name="forgot_password_step1" class="login-btn">Next</button>
                </form>
            <?php elseif ($show_security_question): ?>
                <form method="POST" action="">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="reset_email" value="<?php echo htmlspecialchars($reset_email); ?>">
                    <div class="form-group">
                        <label for="security_question">Security Question</label>
                        <input type="text" id="security_question" name="security_question" value="<?php echo htmlspecialchars($security_question); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="security_answer">Your Answer</label>
                        <input type="text" id="security_answer" name="security_answer" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="forgot_password_step2" class="login-btn">Reset Password</button>
                </form>
            <?php endif; ?>
            <div class="back-to-login">
                <a href="#" onclick="closeForgotModal()">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        function openForgotModal() {
            document.getElementById('forgotModal').classList.add('active');
        }

        function closeForgotModal() {
            document.getElementById('forgotModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('forgotModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeForgotModal();
            }
        });

        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke="currentColor" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><line x1="4" y1="20" x2="20" y2="4" stroke="currentColor" stroke-width="2"/>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke="currentColor" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>';
            }
        });
    </script>
</body>
</html> 