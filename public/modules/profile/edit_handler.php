<?php
session_start();
require_once "../../../app/config/database.php";

$stmt = $pdo->prepare("
    UPDATE users
    SET username = ?, first_name = ?, surname = ?
    WHERE id = ?
");

$stmt->execute([
    $_POST['username'],
    $_POST['first_name'],
    $_POST['surname'],
    $_SESSION['user_id']
]);

header("Location: /modules/profile/view.php");
exit;
