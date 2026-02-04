<?php
// Include OneSignal configuration
require __DIR__ . '/../config.php';

session_start();
if (!empty($_SESSION['user_id'])) {
    header("Location: /dashboard.php");
} else {
    header("Location: /login.php");
}
exit;
