<?php
// 生成拼图验证码
header("Content-Type: application/json");

$img = "bg.jpg"; // 你可以换成任何背景图

$im = imagecreatefromjpeg($img);
$width = imagesx($im);
$height = imagesy($im);

// 拼图块尺寸
$blockSize = 50;

// 随机生成位置 (避免靠边)
$x = rand(60, $width - 80);
$y = rand(20, $height - 80);

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
