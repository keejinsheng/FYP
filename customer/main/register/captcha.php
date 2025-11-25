<?php
header("Content-Type: application/json");

// 绝对路径背景图
$imgPath = __DIR__ . "/bg.jpg";

if (!file_exists($imgPath)) {
    $imgPath = __DIR__ . "/bg.jpeg";
    if (!file_exists($imgPath)) {
        echo json_encode(["error" => "Background image not found"]);
        exit;
    }
}

// 自动判断图片格式
$ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));

switch ($ext) {
    case "jpg":
    case "jpeg":
        $im = imagecreatefromjpeg($imgPath);
        break;
    case "png":
        $im = imagecreatefrompng($imgPath);
        break;
    default:
        echo json_encode(["error" => "Unsupported image format"]);
        exit;
}

$width  = imagesx($im);
$height = imagesy($im);
$blockSize = 50;

// 生成拼图块位置
$x = rand(60, $width - 80);
$y = rand(20, $height - 80);

// 拼图块
$block = imagecreatetruecolor($blockSize, $blockSize);
imagecopy($block, $im, 0, 0, $x, $y, $blockSize, $blockSize);

// 背景图（挖洞）
$mask = imagecreatetruecolor($width, $height);
imagecopy($mask, $im, 0, 0, 0, 0, $width, $height);

$white = imagecolorallocate($mask, 255, 255, 255);
imagefilledrectangle($mask, $x, $y, $x + $blockSize, $y + $blockSize, $white);

// 输出 block
ob_start();
imagepng($block);
$blockBase64 = base64_encode(ob_get_clean());

// 输出背景图
ob_start();
imagepng($mask);
$bgBase64 = base64_encode(ob_get_clean());

echo json_encode([
    "block"   => "data:image/png;base64," . $blockBase64,
    "bg"      => "data:image/png;base64," . $bgBase64,
    "answerX" => $x
]);
