<?php
session_start();

$userX = intval($_POST['userX']);
$answerX = intval($_POST['answerX']);

if (abs($userX - $answerX) < 6) {
    echo "success";
} else {
    echo "fail";
}
