<?php
header("Content-Type: application/json");

$userX   = intval($_POST['userX'] ?? 0);
$answerX = intval($_POST['answerX'] ?? -999);

if (abs($userX - $answerX) <= 6) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail"]);
}
