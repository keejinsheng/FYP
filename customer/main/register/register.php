<?php
require_once '../../../config/database.php';

$error_message = '';
$success_message = '';

// 获取所有安全问题
try {
    $pdo = getDBConnection();

} catch (Exception $e) {
    $questions = [];
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = sanitize($_POST['gender'] ?? '');

    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) ) {
        $error_message = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match. ';
    } 
    // Verify slider captcha
if (($_POST['captcha_verified'] ?? '0') !== '1') {
    $error_message = 'Please complete the verification puzzle.';
}

    elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long';
    } elseif (!preg_match('/[a-zA-Z]/', $password)) {
        $error_message = 'Password must contain at least one letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error_message = 'Password must contain at least one number';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = 'Username already exists';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = 'Email address already registered';
                } else {
                    // Create new user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO user (username, email, password_hash, first_name, last_name, phone, date_of_birth, gender)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $username, $email, $password_hash, $first_name, $last_name, 
                        $phone ?: null, $date_of_birth ?: null, $gender ?: null,
                    ]);
                    
                    $success_message = 'Registration successful! Redirecting to login page... ';
                    
                    // Clear form data
                    $_POST = array();
                    header("refresh:2;url=../login/login.php"); // 2秒后跳转到登录页面
                }
            }
        } catch (Exception $e) {
            $error_message = 'Registration failed. Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Spice Fusion</title>
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
            max-width: 500px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--text-gray);
            border-radius: 6px;
            background: var(--background-dark);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* ====== Password Strength Indicator ====== */
        .password-strength-container {
            margin-top: 0.5rem;
        }

        .password-strength-bar {
            display: flex;
            gap: 4px;
            margin-bottom: 0.5rem;
        }

        .password-strength-segment {
            flex: 1;
            height: 4px;
            background: var(--text-gray);
            border-radius: 2px;
            transition: var(--transition);
        }

        .password-strength-segment.weak {
            background: #ff4444;
        }

        .password-strength-segment.medium {
            background: #ffaa00;
        }

        .password-strength-segment.strong {
            background: #4CAF50;
        }

        .password-strength-text {
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .password-strength-text.weak {
            color: #ff4444;
        }

        .password-strength-text.medium {
            color: #ffaa00;
        }

        .password-strength-text.strong {
            color: #4CAF50;
        }

        .password-strength-text.empty {
            color: var(--text-gray);
        }

        .password-requirements {
            font-size: 0.75rem;
            color: var(--text-gray);
            margin-top: 0.25rem;
        }

        .password-requirements .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }

        .password-requirements .requirement.valid {
            color: #4CAF50;
        }

        .password-requirements .requirement.invalid {
            color: var(--text-gray);
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
        /* ====== Slider Captcha Styles ====== */
        .captcha-container {
            padding: 1.5rem;
            background: var(--background-dark);
            border: 1px solid var(--text-gray);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .captcha-container.captcha-fail {
            animation: shake 0.5s;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 75, 43, 0.1);
        }

        .captcha-container.captcha-success {
            border-color: #4CAF50;
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
        }

        .captcha-message {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }

        .captcha-frame {
            position: relative;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            min-height: 150px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--card-bg);
        }

        .captcha-loading {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: none;
            z-index: 10;
        }

        .spinner {
            width: 36px;
            height: 36px;
            border: 4px solid rgba(255, 255, 255, 0.15);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .captcha-bg {
            width: 100%;
            display: block;
            max-width: 100%;
            height: auto;
            filter: brightness(0.9);
            border-radius: 8px;
        }

        .captcha-block {
            position: absolute;
            top: 0;
            left: 0;
            width: 50px;
            height: 50px;
            display: block;
            pointer-events: none;
            transition: left 0.05s linear;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .slider-container {
            margin-top: 1rem;
            position: relative;
        }

        .captcha-slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: var(--card-bg);
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            cursor: pointer;
        }

        .captcha-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--gradient-primary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12h14M12 5l7 7-7 7'/%3E%3C/svg%3E") center/16px no-repeat;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(255, 75, 43, 0.4);
            position: relative;
            transition: var(--transition);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .captcha-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(255, 75, 43, 0.6);
        }

        .captcha-slider::-moz-range-thumb {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--gradient-primary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12h14M12 5l7 7-7 7'/%3E%3C/svg%3E") center/16px no-repeat;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(255, 75, 43, 0.4);
            transition: var(--transition);
        }

        .captcha-slider::-moz-range-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(255, 75, 43, 0.6);
        }

        .captcha-slider::-moz-range-track {
            height: 8px;
            border-radius: 4px;
            background: var(--card-bg);
        }

        .captcha-footer {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: var(--text-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .attempts-info {
            font-weight: 500;
        }

        .attempts-info span {
            color: var(--text-light);
            font-weight: 600;
        }

        .reload-btn {
            background: transparent;
            border: 1px solid var(--text-gray);
            color: var(--text-light);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .reload-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(255, 75, 43, 0.1);
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            50% { transform: translateX(8px); }
            75% { transform: translateX(-6px); }
            100% { transform: translateX(0); }
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }


            .captcha-frame {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join Spice Fusion and start ordering delicious food</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-strength-container">
                            <div class="password-strength-bar">
                                <div class="password-strength-segment" id="strength-seg-1"></div>
                                <div class="password-strength-segment" id="strength-seg-2"></div>
                                <div class="password-strength-segment" id="strength-seg-3"></div>
                                <div class="password-strength-segment" id="strength-seg-4"></div>
                            </div>
                            <div class="password-strength-text empty" id="strength-text"></div>
                            <div class="password-requirements">
                                <div class="requirement invalid" id="req-length">
                                    <span>✓</span>
                                    <span>At least 6 characters</span>
                                </div>
                                <div class="requirement invalid" id="req-letter">
                                    <span>✓</span>
                                    <span>Contains at least one letter</span>
                                </div>
                                <div class="requirement invalid" id="req-number">
                                    <span>✓</span>
                                    <span>Contains at least one number</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <!-- ====== Slider Captcha START ====== -->

                <div class="form-group full-width">
                    <label>Verify you are human *</label>

                    <div id="captchaBox" class="captcha-container">
                        <p id="captchaMsg" class="captcha-message">Slide to complete the puzzle</p>

                        <div id="captchaFrame" class="captcha-frame">
                            <div id="captchaLoading" class="captcha-loading">
                                <div class="spinner"></div>
                            </div>

                            <img id="bgImg" class="captcha-bg" alt="Captcha Background">
                            <img id="blockImg" class="captcha-block" alt="Captcha Block">
            </div>

                        <div class="slider-container">
                            <input type="range" id="slider" class="captcha-slider" min="0" max="250" value="0">
        </div>

                        <div id="captchaFooter" class="captcha-footer">
                            <div id="attemptsInfo" class="attempts-info">
                                Attempts left: <span id="attemptsLeft">—</span>
                            </div>
                            <button type="button" id="reloadCaptcha" class="reload-btn">Reload</button>
        </div>
    </div>

    <input type="hidden" id="captcha_verified" name="captcha_verified" value="0">
    <input type="hidden" id="captcha_token" name="captcha_token" value="">
</div>
                <!-- ====== Slider Captcha END ====== -->
<div class="form-group full-width" style="margin-top: 1.5rem;">
    <label style="margin-bottom:10px; display:block;">Verify you are human *</label>

    <div id="captchaBox" style="padding:10px; background:#222; border:1px solid #444; border-radius:8px;">
        <p style="font-size:14px; color:#ccc; margin-bottom:10px;">Drag slider to complete puzzle</p>

        <div style="position:relative; width:100%; max-width:300px; margin:auto; min-height:150px;">
            <img id="bgImg" width="100%" style="border-radius:6px; display:block; max-width:100%; height:auto;" alt="Captcha Background">
            <img id="blockImg" style="position:absolute; top:0; left:0; width:50px; height:50px; display:block; pointer-events:none;" alt="Captcha Block">
        </div>

        <input type="range" id="slider" min="0" max="250" value="0" 
               style="width:100%; margin-top:15px;">
    </div>

    <!-- 验证成功后存状态 -->
    <input type="hidden" id="captcha_verified" name="captcha_verified" value="0">
</div>
<!-- ====== Slider Captcha END ====== -->


                <button type="submit" class="register-btn">Create Account</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="../login/login.php">Sign in here</a>
            </div>
        </div>
    </div>

<script>
(() => {
    const slider = document.getElementById('slider');
    const blockImg = document.getElementById('blockImg');
    const bgImg = document.getElementById('bgImg');
    const loading = document.getElementById('captchaLoading');
    const captchaBox = document.getElementById('captchaBox');
    const captchaMsg = document.getElementById('captchaMsg');
    const attemptsLeftEl = document.getElementById('attemptsLeft');
    const reloadBtn = document.getElementById('reloadCaptcha');
    const verifiedInput = document.getElementById('captcha_verified');
    const tokenInput = document.getElementById('captcha_token');

    let currentToken = null;
    <script>
    (() => {
        const slider = document.getElementById('slider');
        const blockImg = document.getElementById('blockImg');
        const bgImg = document.getElementById('bgImg');
        const loading = document.getElementById('captchaLoading');
        const captchaBox = document.getElementById('captchaBox');
        const captchaMsg = document.getElementById('captchaMsg');
        const attemptsLeftEl = document.getElementById('attemptsLeft');
        const reloadBtn = document.getElementById('reloadCaptcha');
        const verifiedInput = document.getElementById('captcha_verified');
        const tokenInput = document.getElementById('captcha_token');

        let currentToken = null;
        let currentMax = 250;
        let currentScaleX = 1; // 保存当前的缩放比例

    function setLoading(show) {
        loading.style.display = show ? 'block' : 'none';
    }

    function loadCaptcha() {
        setLoading(true);
            captchaBox.classList.remove('captcha-fail', 'captcha-success');
        captchaMsg.textContent = 'Loading puzzle...';

            // 添加时间戳和随机数防止缓存，确保每次获取新图片
            const timestamp = new Date().getTime();
            const random = Math.random().toString(36).substring(7);
            fetch('captcha.php?t=' + timestamp + '&r=' + random, { 
                method: 'GET', 
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
            .then(r => r.json())
            .then(data => {
                    if (data.error) {
                setLoading(false);
                    captchaMsg.textContent = 'Error: ' + data.error;
                    return;
                }

                    currentToken = data.token;
                    tokenInput.value = currentToken;

                    // 先移除旧的 onload 事件，避免重复触发
                    bgImg.onload = null;
                    
                    // 设置图片源
                bgImg.src = data.bg;
                blockImg.src = data.block;

                    // 等待图片加载完成后再计算位置
                    const setupCaptcha = function() {
                        setLoading(false);
                        const visibleWidth = bgImg.clientWidth || data.width || 300;
                        const visibleHeight = bgImg.clientHeight || data.height || 200;
                        const originalWidth = data.width;
                        const originalHeight = data.height;
                        const answerY = data.answerY || 0;
                        const BLOCK_SIZE = 50; // 原始拼图块大小
                        
                        // 计算缩放比例
                        // 防止除以0或无效值
                        if (!originalWidth || originalWidth <= 0 || !originalHeight || originalHeight <= 0) {
                            console.error('Invalid image dimensions:', { originalWidth, originalHeight });
                            captchaMsg.textContent = 'Invalid image data';
                            return;
                        }
                        
                        const scaleX = visibleWidth / originalWidth;
                        const scaleY = visibleHeight / originalHeight;
                        
                        // 保存缩放比例，用于验证时转换坐标
                        currentScaleX = scaleX > 0 ? scaleX : 1;
                        
                        // 根据缩放比例调整拼图块的尺寸和位置
                        const scaledBlockSize = BLOCK_SIZE * scaleX;
                        blockImg.style.width = scaledBlockSize + 'px';
                        blockImg.style.height = scaledBlockSize + 'px';
                        
                        // 根据缩放比例计算拼图块的垂直位置，使其与背景缺口对齐
                        const blockTop = answerY * scaleY;
                        blockImg.style.top = blockTop + 'px';
                        
                        // 设置滑块范围（基于缩放后的宽度）
                        currentMax = Math.max(80, Math.floor(visibleWidth - scaledBlockSize));
                    slider.max = currentMax;
                    slider.value = 0;
                    blockImg.style.left = '0px';
                        // 只有在没有尝试过的情况下才显示初始次数（新token时）
                        if (!attemptsLeftEl.dataset.hasAttempted && !currentToken) {
                            attemptsLeftEl.textContent = '3';
                        }
                        // 如果有token但还没有尝试过，也显示3
                        if (currentToken && !attemptsLeftEl.dataset.hasAttempted) {
                            attemptsLeftEl.textContent = '3';
                        }
                        captchaMsg.textContent = 'Slide to complete the puzzle';
                    verifiedInput.value = '0';
                    };
                    
                    // 等待背景图片加载完成
                    if (bgImg.complete && bgImg.naturalWidth > 0) {
                        // 图片已缓存，直接设置
                        setTimeout(setupCaptcha, 50);
                    } else {
                        // 图片需要加载，等待 onload
                        bgImg.onload = setupCaptcha;
                        bgImg.onerror = function() {
                            setLoading(false);
                            captchaMsg.textContent = 'Failed to load image';
                        };
                    }
            })
            .catch(err => {
                setLoading(false);
                    captchaMsg.textContent = 'Failed to load puzzle. Please try again.';
                console.error(err);
            });
    }

    function showFail(remainingAttempts) {
        captchaBox.classList.remove('captcha-success');
        captchaBox.classList.add('captcha-fail');
        captchaMsg.textContent = 'Verification failed. Try again.';
            // 确保显示剩余尝试次数
            if (remainingAttempts !== undefined && remainingAttempts !== '—' && remainingAttempts !== null) {
        attemptsLeftEl.textContent = remainingAttempts;
                attemptsLeftEl.dataset.hasAttempted = 'true';
            } else {
                attemptsLeftEl.textContent = '—';
            }
            // 失败后重新加载新图片，但传递旧token以继承尝试次数
            setTimeout(() => {
                // 保存旧token
                const oldToken = currentToken;
                // 重新加载，传递旧token
                if (oldToken) {
                    const timestamp = new Date().getTime();
                    const random = Math.random().toString(36).substring(7);
                    fetch('captcha.php?t=' + timestamp + '&r=' + random + '&old_token=' + encodeURIComponent(oldToken), { 
                        method: 'GET', 
                        cache: 'no-cache',
                        headers: {
                            'Cache-Control': 'no-cache',
                            'Pragma': 'no-cache'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            captchaMsg.textContent = 'Error: ' + data.error;
                            return;
                        }
                        // 更新图片和token
                    bgImg.src = data.bg;
                    blockImg.src = data.block;
                    currentToken = data.token;
                    tokenInput.value = currentToken;
                    
                    // 调试信息：显示使用的背景图片（可以在控制台查看）
                    if (data.debug_image) {
                        console.log('Loaded background image:', data.debug_image);
                    }
                        
                        // 重置滑块
                        slider.value = 0;
                        blockImg.style.left = '0px';
                        captchaBox.classList.remove('captcha-fail');
                        captchaMsg.textContent = 'Slide to complete the puzzle';
                        
                        // 重新计算位置
        setTimeout(() => {
                            const visibleWidth = bgImg.clientWidth || data.width || 300;
                            const visibleHeight = bgImg.clientHeight || data.height || 200;
                            const originalWidth = data.width;
                            const originalHeight = data.height;
                            const answerY = data.answerY || 0;
                            const BLOCK_SIZE = 50;
                            
                            if (!originalWidth || originalWidth <= 0 || !originalHeight || originalHeight <= 0) {
                                return;
                            }
                            
                            const scaleX = visibleWidth / originalWidth;
                            const scaleY = visibleHeight / originalHeight;
                            currentScaleX = scaleX > 0 ? scaleX : 1;
                            
                            const scaledBlockSize = BLOCK_SIZE * scaleX;
                            blockImg.style.width = scaledBlockSize + 'px';
                            blockImg.style.height = scaledBlockSize + 'px';
                            const blockTop = answerY * scaleY;
                            blockImg.style.top = blockTop + 'px';
                            
                            currentMax = Math.max(80, Math.floor(visibleWidth - scaledBlockSize));
                            slider.max = currentMax;
                        }, 100);
                    })
                    .catch(err => {
                        console.error(err);
                        captchaMsg.textContent = 'Failed to reload puzzle';
                    });
                } else {
                    // 如果没有旧token，正常重新加载
            loadCaptcha();
                }
        }, 900);
    }

    function showLocked(seconds) {
        captchaBox.classList.remove('captcha-success');
        captchaBox.classList.add('captcha-fail');
            attemptsLeftEl.textContent = '0';
            attemptsLeftEl.dataset.hasAttempted = 'true';
        let s = seconds;
        function tick() {
            if (s <= 0) {
                    // 锁定时间到，重新加载新的验证码
                    currentToken = null;
                    attemptsLeftEl.dataset.hasAttempted = '';
                loadCaptcha();
                return;
            }
            captchaMsg.textContent = 'Too many attempts. Locked for ' + s + 's';
            s--;
            setTimeout(tick, 1000);
        }
        tick();
    }

    slider.addEventListener('input', function() {
        blockImg.style.left = this.value + 'px';
    });

    slider.addEventListener('change', function() {
            const sliderValue = parseInt(this.value, 10);
        if (!currentToken) {
                captchaMsg.textContent = 'Token missing. Reloading...';
            loadCaptcha();
            return;
        }
            
            // 将滑块值（缩放后的坐标）转换为原始图片坐标
            // 因为后端验证使用的是原始图片的坐标
            if (currentScaleX <= 0 || isNaN(currentScaleX)) {
                currentScaleX = 1; // 默认值，防止除以0或NaN
            }
            const userX = Math.round(sliderValue / currentScaleX);
            
        captchaMsg.textContent = 'Verifying...';
        setLoading(true);

        fetch('verify.php', {
            method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `token=${encodeURIComponent(currentToken)}&userX=${encodeURIComponent(userX)}`
        })
        .then(r => r.json())
        .then(data => {
            setLoading(false);
            if (!data) {
                captchaMsg.textContent = 'Server error';
                return;
            }
            if (data.status === 'success') {
                captchaBox.classList.remove('captcha-fail');
                captchaBox.classList.add('captcha-success');
                captchaMsg.textContent = '✔ Verification success';
                attemptsLeftEl.textContent = 'OK';
                verifiedInput.value = '1';
            } else if (data.status === 'fail') {
                    // 显示剩余尝试次数
                    const remaining = data.remaining_attempts;
                    console.log('Verification failed. Remaining attempts:', remaining, 'Attempts:', data.attempts);
                    // 确保显示正确的剩余次数
                    if (remaining !== undefined && remaining !== null) {
                        showFail(remaining);
                    } else {
                        showFail('—');
                    }
            } else if (data.status === 'locked') {
                    // 锁定状态，显示0次并开始倒计时
                    console.log('Account locked. Remaining seconds:', data.remaining_seconds);
                    attemptsLeftEl.textContent = '0';
                showLocked(data.remaining_seconds ?? 0);
            } else if (data.status === 'expired' || data.reason === 'expired') {
                captchaMsg.textContent = 'Token expired. Reloading...';
                setTimeout(loadCaptcha, 800);
            } else if (data.reason === 'invalid_token') {
                captchaMsg.textContent = 'Invalid token. Reloading...';
                setTimeout(loadCaptcha, 800);
            } else {
                    captchaMsg.textContent = 'Verification error';
                console.log(data);
            }
        })
        .catch(err => {
            setLoading(false);
            captchaMsg.textContent = 'Network error';
            console.error(err);
        });
    });

    reloadBtn.addEventListener('click', loadCaptcha);
    loadCaptcha();
})();

    // ====== Password Strength Indicator ======
    (function() {
        const passwordInput = document.getElementById('password');
        const strengthSegments = [
            document.getElementById('strength-seg-1'),
            document.getElementById('strength-seg-2'),
            document.getElementById('strength-seg-3'),
            document.getElementById('strength-seg-4')
        ];
        const strengthText = document.getElementById('strength-text');
        const reqLength = document.getElementById('req-length');
        const reqLetter = document.getElementById('req-letter');
        const reqNumber = document.getElementById('req-number');

        function checkPasswordStrength(password) {
            let strength = 0;
            let strengthLevel = 'empty';
            let strengthLabel = '';

            // 检查各项要求
            const hasLength = password.length >= 6;
            const hasLetter = /[a-zA-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);

            // 更新要求指示器
            if (hasLength) {
                reqLength.classList.remove('invalid');
                reqLength.classList.add('valid');
            } else {
                reqLength.classList.remove('valid');
                reqLength.classList.add('invalid');
            }

            if (hasLetter) {
                reqLetter.classList.remove('invalid');
                reqLetter.classList.add('valid');
            } else {
                reqLetter.classList.remove('valid');
                reqLetter.classList.add('invalid');
            }

            if (hasNumber) {
                reqNumber.classList.remove('invalid');
                reqNumber.classList.add('valid');
            } else {
                reqNumber.classList.remove('valid');
                reqNumber.classList.add('invalid');
            }

            if (password.length === 0) {
                strengthLevel = 'empty';
                strengthLabel = '';
            } else {
                // 长度检查
                if (password.length >= 8) {
                    strength += 1;
                } else if (password.length >= 6) {
                    strength += 0.5;
}

                // 包含小写字母
                if (/[a-z]/.test(password)) {
                    strength += 1;
                }

                // 包含大写字母
                if (/[A-Z]/.test(password)) {
                    strength += 1;
                }

                // 包含数字
                if (/[0-9]/.test(password)) {
                    strength += 1;
                }

                // 包含特殊字符
                if (/[^a-zA-Z0-9]/.test(password)) {
                    strength += 1;
                }

                // 确定强度等级
                if (strength <= 2) {
                    strengthLevel = 'weak';
                    strengthLabel = 'weak';
                } else if (strength <= 3.5) {
                    strengthLevel = 'medium';
                    strengthLabel = 'medium';
                } else {
                    strengthLevel = 'strong';
                    strengthLabel = 'strong';
                }
            }

            // 更新强度条
            strengthSegments.forEach((seg, index) => {
                seg.classList.remove('weak', 'medium', 'strong');
                if (strengthLevel === 'empty') {
                    // 不显示任何颜色
                } else if (strengthLevel === 'weak') {
                    if (index === 0) {
                        seg.classList.add('weak');
                    }
                } else if (strengthLevel === 'medium') {
                    if (index <= 1) {
                        seg.classList.add('medium');
                    }
                } else if (strengthLevel === 'strong') {
                    seg.classList.add('strong');
                }
            });

            // 更新文字
            strengthText.textContent = strengthLabel;
            strengthText.className = 'password-strength-text ' + strengthLevel;
        }

        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // 初始化
        checkPasswordStrength(passwordInput.value);
    })();
    </script>
let answerX = 0;
let slider = document.getElementById("slider");
let blockImg = document.getElementById("blockImg");
let verifiedInput = document.getElementById("captcha_verified");

function loadCaptcha() {
    fetch("captcha.php")
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert("验证码加载失败: " + data.error);
                return;
            }

            document.getElementById("bgImg").src = data.bg;
            blockImg.src = data.block;

            answerX = data.answerX;
            slider.value = 0;
            blockImg.style.left = "0px";
            verifiedInput.value = "0";
        })
        .catch(err => {
            alert("验证码加载失败，请刷新页面重试");
        });
}

// 滑动拼图
slider.addEventListener("input", function () {
    blockImg.style.left = this.value + "px";
});

// 松手验证
slider.addEventListener("change", function () {
    let userX = this.value;

    fetch("verify.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `userX=${userX}&answerX=${answerX}`
    })
    .then(res => res.json())
    .then(result => {
        if (result.status === "success") {
            alert("✔ Verification Success!");
            verifiedInput.value = "1";
        } else {
            alert("✖ Verification Failed. Try again!");
            loadCaptcha();
        }
    });
});

loadCaptcha();

</script>

</body>
</html> 