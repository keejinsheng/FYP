<?php
// 生成拼图验证码
header("Content-Type: application/json");

// 使用绝对路径确保能找到图片文件
$img = __DIR__ . "/bg.jpg"; // 使用 __DIR__ 获取当前目录的绝对路径

// 检查文件是否存在
if (!file_exists($img)) {
    // 如果 bg.jpg 不存在，尝试 bg.jpeg
    $img = __DIR__ . "/bg.jpeg";
    if (!file_exists($img)) {
        http_response_code(500);
        echo json_encode(["error" => "Background image not found"]);
        exit;
    }
}

// 尝试读取图片
$im = @imagecreatefromjpeg($img);
if ($im === false) {
    // 如果 JPEG 失败，尝试其他格式
    $im = @imagecreatefrompng($img);
    if ($im === false) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load image"]);
        exit;
    }
}

$width = imagesx($im);
$height = imagesy($im);

// 拼图块尺寸
$blockSize = 50;

// 确保图片足够大
if ($width < 140 || $height < 100) {
    http_response_code(500);
    echo json_encode(["error" => "Image too small. Minimum size: 140x100"]);
    exit;
}

// 随机生成位置 (避免靠边)
$x = rand(60, max(60, $width - 80));
$y = rand(20, max(20, $height - 80));

// 创建拼图块
$block = imagecreatetruecolor($blockSize, $blockSize);
imagecopy($block, $im, 0, 0, $x, $y, $blockSize, $blockSize);

// 给拼图背景制作空洞
$mask = $im;
$white = imagecolorallocate($mask, 255, 255, 255);
imagefilledrectangle($mask, $x, $y, $x + $blockSize, $y + $blockSize, $white);

// 输出图片 Base64
ob_start();
imagepng($block);
$blockImg = base64_encode(ob_get_clean());

ob_start();
imagepng($mask);
$bgImg = base64_encode(ob_get_clean());

// 输出 JSON 给前端
echo json_encode([
    "block"   => "data:image/png;base64," . $blockImg,
    "bg"      => "data:image/png;base64," . $bgImg,
    "answerX" => $x
]);
