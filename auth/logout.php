<?php
/**
 * KMC Robotics Club - Logout Handler
 */

require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php?logged_out=1');
exit;
