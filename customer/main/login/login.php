<?php
require_once '../../../config/database.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$error_message = '';
$success_message = '';


// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_id'], $_POST['password']) && !isset($_POST['step'])) {

    $login_id = sanitize($_POST['login_id'] ?? '');   // 可以是 username 或 email
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error_message = 'Please fill in all fields';
    } else {
        try {
            $pdo = getDBConnection();

            // 🔥 核心查询：允许 username OR email 登录
            $stmt = $pdo->prepare("
                SELECT user_id, username, email, password_hash, first_name, last_name 
                FROM user 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$login_id, $login_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // 保存 session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];

                redirect('../index/index.php');
            } else {
                $error_message = 'Invalid username/email or password';
            }
        } catch (Exception $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
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
            --shadow-strong: 0 8px 16px rgba(0, 0, 0, 0.2);
            --border-radius: 12px;
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--background-dark);
            color: var(--text-light);
        }

        .form-group input:focus {
            border-color: var(--primary-color);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
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

            <form method="POST" action="">
                <!-- Username or Email -->
                <div class="form-group">
                    <label for="login_id">Username or Email</label>
                    <input type="text" id="login_id" name="login_id" required
                           value="<?php echo htmlspecialchars($_POST['login_id'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password" required >
                        <span id="togglePassword"
                              style="position:absolute; right:1rem; top:50%; transform:translateY(-50%); cursor:pointer; color:#aaa;">
                            👁
                        </span>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="forgot_password.php" class="forgot-password" style="color:#FF4B2B;">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <div style="text-align:center; margin-top:1.5rem;">
                Don't have an account? <a href="../register/register.php" style="color:#FF4B2B;">Sign up here</a>
            </div>
        </div>
    </div>

<script>
document.getElementById('togglePassword').addEventListener('click', () => {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
});
</script>

</body>
</html>
