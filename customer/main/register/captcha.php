<?php
// captcha.php
session_start();
header("Content-Type: application/json; charset=utf-8");
// 防止缓存
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 配置。。
$BLOCK_SIZE = 50;
$MIN_WIDTH = 140;
$MIN_HEIGHT = 100;
$TOKEN_TTL = 300; // token 有效期 (秒)

// 检查 GD
if (!function_exists('imagecreatefromjpeg')) {
    echo json_encode(["error" => "Server missing GD extension. Please enable GD (imagecreatefromjpeg)."]);
    exit;
}

// 查找图片（与此文件同目录）- 支持随机选择多个图片
$imageExtensions = ['jpg', 'jpeg', 'png'];
$imageFiles = [];
$dir = __DIR__;

// 扫描目录中的所有图片文件
foreach ($imageExtensions as $ext) {
    // 尝试小写扩展名
    $pattern1 = $dir . DIRECTORY_SEPARATOR . "*.{$ext}";
    $files1 = glob($pattern1);
    if ($files1) {
        $imageFiles = array_merge($imageFiles, $files1);
    }
    
    // 尝试大写扩展名
    $pattern2 = $dir . DIRECTORY_SEPARATOR . "*." . strtoupper($ext);
    $files2 = glob($pattern2);
    if ($files2) {
        $imageFiles = array_merge($imageFiles, $files2);
    }
}

// 去重并重新索引数组（array_rand 需要数字索引）
$imageFiles = array_values(array_unique($imageFiles));

// 如果没有找到图片，尝试默认文件名
if (empty($imageFiles)) {
    $defaultFiles = [
        $dir . DIRECTORY_SEPARATOR . "bg.jpg",
        $dir . DIRECTORY_SEPARATOR . "bg.jpeg",
        $dir . DIRECTORY_SEPARATOR . "bg.png"
    ];
    foreach ($defaultFiles as $file) {
        if (file_exists($file)) {
            $imageFiles[] = $file;
        }
    }
}

if (empty($imageFiles)) {
    echo json_encode(["error" => "No images found in directory: " . $dir]);
    exit;
}

// 随机选择一个图片（使用时间戳和随机数增加随机性，确保每次请求都不同）
// 如果只有一个图片，直接使用；如果有多个，随机选择
if (count($imageFiles) === 1) {
    $imgPath = $imageFiles[0];
} else {
    // 使用更好的随机数生成
    $randomIndex = mt_rand(0, count($imageFiles) - 1);
    $imgPath = $imageFiles[$randomIndex];
}

// 读取图片并判断格式
$ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
switch ($ext) {
    case "jpg":
    case "jpeg":
        $im = @imagecreatefromjpeg($imgPath);
        break;
    case "png":
        $im = @imagecreatefrompng($imgPath);
        break;
    default:
        echo json_encode(["error" => "Unsupported image format"]);
        exit;
}
if ($im === false) {
    echo json_encode(["error" => "Failed to open image (maybe corrupted)"]);
    exit;
}

$width  = imagesx($im);
$height = imagesy($im);
if ($width < $MIN_WIDTH || $height < $MIN_HEIGHT) {
    echo json_encode(["error" => "Image too small. Minimum size: {$MIN_WIDTH}x{$MIN_HEIGHT}"]);
    exit;
}

// 随机拼图位置（避免太靠左/右）
$x = rand(60, max(60, $width - 80));
$y = rand(20, max(20, $height - 80));

// 生成拼图块
$block = imagecreatetruecolor($BLOCK_SIZE, $BLOCK_SIZE);
imagecopy($block, $im, 0, 0, $x, $y, $BLOCK_SIZE, $BLOCK_SIZE);

// 在背景上挖洞（用白色填充，前端视觉上形成缺口）
$mask = imagecreatetruecolor($width, $height);
imagecopy($mask, $im, 0, 0, 0, 0, $width, $height);
$transparent = imagecolorallocate($mask, 255, 255, 255);
imagefilledrectangle($mask, $x, $y, $x + $BLOCK_SIZE, $y + $BLOCK_SIZE, $transparent);

// 输出为 base64 png（block & bg）
ob_start();
imagepng($block);
$blockBase64 = base64_encode(ob_get_clean());

ob_start();
imagepng($mask);
$bgBase64 = base64_encode(ob_get_clean());

// 检查是否有旧的 token 需要继承尝试次数
$oldToken = $_GET['old_token'] ?? '';
$inheritedAttempts = 0;
$inheritedLockedUntil = 0;

if (!empty($oldToken) && isset($_SESSION['captcha_tokens'][$oldToken])) {
    $oldMeta = $_SESSION['captcha_tokens'][$oldToken];
    // 继承尝试次数和锁定状态
    $inheritedAttempts = $oldMeta['attempts'] ?? 0;
    $inheritedLockedUntil = $oldMeta['locked_until'] ?? 0;
    // 删除旧 token
    unset($_SESSION['captcha_tokens'][$oldToken]);
}

// 生成新 token 保存到 session（避免直接在前端传 answerX）
$token = bin2hex(random_bytes(12));
$_SESSION['captcha_tokens'][$token] = [
    'answerX' => $x,
    'answerY' => $y,  
    'created' => time(),
    'attempts' => $inheritedAttempts, // 继承之前的尝试次数
    'locked_until' => $inheritedLockedUntil // 继承锁定状态
];

// 清理过期 token（可选）
foreach ($_SESSION['captcha_tokens'] as $t => $meta) {
    if (isset($meta['created']) && (time() - $meta['created'] > $TOKEN_TTL * 4)) {
        unset($_SESSION['captcha_tokens'][$t]);
    }
}

// 返回数据
echo json_encode([
    "block"   => "data:image/png;base64," . $blockBase64,
    "bg"      => "data:image/png;base64," . $bgBase64,
    "token"   => $token,
    
    // 前端不需要 answerX（安全），verify.php 从 session 读取
    // 但需要 answerY 来对齐拼图块的垂直位置
    "answerY" => $y,
    "width"   => $width,
    "height"  => $height,
    // 调试信息：显示使用的图片文件名（可选，生产环境可以移除）
    "debug_image" => basename($imgPath)
]);
