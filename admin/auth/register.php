<?php
require_once '../../config/database.php';

$error_message = '';
$success_message = '';

// 获取所有安全问题
try {
    $pdo = getDBConnection();
    $questions = $pdo->query("SELECT id, question FROM security_questions")->fetchAll();
} catch (Exception $e) {
    $questions = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $role = 'Staff'; // Only allow Staff registration by default
    $special_key = $_POST['special_key'] ?? '';
    $expected_key = 'ABC123'; // special key 现在是 ABC123

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($special_key)) {
        $error_message = 'Please fill in all fields';
    } elseif ($special_key !== $expected_key) {
        $error_message = 'Invalid Special Key!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } else {
        try {
            $pdo = getDBConnection();
            // Check for duplicate username/email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_user WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = 'Username or email already exists';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                // 确保admin_user表有security_question_id和security_answer_hash字段
                $stmt = $pdo->prepare("INSERT INTO admin_user (username, email, password_hash, first_name, last_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $role]);
                $success_message = 'Registration successful! You can now <a href=\'login.php\'>login</a>.';
            }
        } catch (Exception $e) {
            $error_message = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - Spice Fusion</title>
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
        .register-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .register-card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-strong);
            width: 100%;
            max-width: 400px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .register-header p {
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
        .register-btn {
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
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-gray);
        }
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Admin Register</h1>
                <p>Create a new admin account</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
                <input type="hidden" id="special_key" name="special_key">
                <button type="button" class="register-btn" onclick="showSpecialKeyModal()">Register</button>
            </form>
            <!-- Special Key Modal -->
            <div id="specialKeyModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:var(--card-bg); border-radius:12px; max-width:350px; margin:auto; padding:2rem; box-shadow:0 8px 32px rgba(0,0,0,0.3); position:relative;">
                    <h2 style="color:var(--primary-color); margin-bottom:1rem;">Enter Special Key</h2>
                    <input type="password" id="modalSpecialKey" placeholder="Special Key" style="width:100%; padding:0.8rem; border-radius:6px; border:1px solid var(--text-gray); background:var(--background-dark); color:var(--text-light); margin-bottom:1rem;">
                    <div id="specialKeyError" style="color:#FF4B2B; margin-bottom:1rem; display:none;"></div>
                    <div style="display:flex; gap:1rem; justify-content:flex-end;">
                        <button type="button" onclick="hideSpecialKeyModal()" style="background:#666; color:#fff; border:none; border-radius:6px; padding:0.7rem 1.5rem;">Cancel</button>
                        <button type="button" onclick="submitWithSpecialKey()" style="background:var(--gradient-primary); color:#fff; border:none; border-radius:6px; padding:0.7rem 1.5rem;">Confirm</button>
                    </div>
                </div>
            </div>
            <div class="login-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </div>
    <script>
    function showSpecialKeyModal() {
        document.getElementById('modalSpecialKey').value = '';
        document.getElementById('specialKeyError').style.display = 'none';
        document.getElementById('specialKeyModal').style.display = 'flex';
        setTimeout(() => { document.getElementById('modalSpecialKey').focus(); }, 100);
    }
    function hideSpecialKeyModal() {
        document.getElementById('specialKeyModal').style.display = 'none';
    }
    function submitWithSpecialKey() {
        var key = document.getElementById('modalSpecialKey').value.trim();
        if (!key) {
            document.getElementById('specialKeyError').innerText = 'Please enter the special key!';
            document.getElementById('specialKeyError').style.display = 'block';
            return;
        }
        document.getElementById('special_key').value = key;
        document.getElementById('registerForm').submit();
    }
    // 允许按ESC关闭modal
    window.addEventListener('keydown', function(e){
        if(e.key === 'Escape') hideSpecialKeyModal();
    });
    </script>
</body>
</html> 