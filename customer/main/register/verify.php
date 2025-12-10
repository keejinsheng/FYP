<?php
<<<<<<< HEAD
header("Content-Type: application/json");

$userX   = intval($_POST['userX'] ?? 0);
$answerX = intval($_POST['answerX'] ?? -999);

if (abs($userX - $answerX) <= 6) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail"]);
=======
// verify.php
session_start();
header("Content-Type: application/json; charset=utf-8");

// 参数与策略
$MAX_ATTEMPTS = 3; // 3次机会
$LOCKOUT_SECONDS = 300; // 锁定时间 5 分钟
$TOLERANCE = 6; // 允许误差 px

// 读取 POST
$token = $_POST['token'] ?? '';
$userX = intval($_POST['userX'] ?? -999);

if (empty($token) || !isset($_SESSION['captcha_tokens'][$token])) {
    echo json_encode(['status' => 'fail', 'reason' => 'invalid_token']);
    exit;
}

$meta = &$_SESSION['captcha_tokens'][$token];
$now = time();

// 检查是否过期或被锁定
if (isset($meta['locked_until']) && $meta['locked_until'] > $now) {
    $remain = $meta['locked_until'] - $now;
    echo json_encode(['status' => 'locked', 'remaining_seconds' => $remain, 'attempts' => $meta['attempts'] ?? 0]);
    exit;
}

// 检查是否过期（可选：设置 ttl）
$TTL = 3600; // 1 小时
if (isset($meta['created']) && ($now - $meta['created'] > $TTL)) {
    unset($_SESSION['captcha_tokens'][$token]);
    echo json_encode(['status' => 'fail', 'reason' => 'expired']);
    exit;
}

// 验证
$answerX = intval($meta['answerX'] ?? -999);

if (abs($userX - $answerX) <= $TOLERANCE) {
    // 验证成功：将 token 标记为成功并删除
    unset($_SESSION['captcha_tokens'][$token]);
    echo json_encode(['status' => 'success']);
    exit;
} else {
    // 失败，增加尝试次数
    $meta['attempts'] = ($meta['attempts'] ?? 0) + 1;

    if ($meta['attempts'] >= $MAX_ATTEMPTS) {
        $meta['locked_until'] = $now + $LOCKOUT_SECONDS;
        echo json_encode(['status' => 'locked', 'remaining_seconds' => $LOCKOUT_SECONDS, 'attempts' => $meta['attempts']]);
    } else {
        $left = $MAX_ATTEMPTS - $meta['attempts'];
        echo json_encode(['status' => 'fail', 'attempts' => $meta['attempts'], 'remaining_attempts' => $left]);
    }
    exit;
>>>>>>> 24875fb43610183a3f4ce4d3603736e9d0186736
}
