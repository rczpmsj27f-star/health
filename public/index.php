<?php
require_once __DIR__ . '/../app/includes/cache-buster.php';
if (!empty($_SESSION['user_id'])) {
    header("Location: /dashboard.php");
} else {
    header("Location: /login.php");
}
exit;
