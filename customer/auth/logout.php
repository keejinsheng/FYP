<?php
require_once '../../config/database.php';

session_start();
session_destroy();

header('Location: /dwp5431/Group%201/DWP/customer/main/login/login.php');
exit;
?> 