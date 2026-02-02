<?php
require_once "../../../app/core/Auth.php";
Auth::requireAdmin();
require_once "../../../app/config/database.php";

$id = $_GET['id'];

$stmt = $pdo->prepare("
    UPDATE users
    SET is_active = IF(is_active = 1, 0, 1)
    WHERE id = ?
");
$stmt->execute([$id]);

header("Location: /modules/admin/view_user.php?id=$id");
exit;
