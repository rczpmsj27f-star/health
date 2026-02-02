<?php
session_start();
require_once "../../../app/config/database.php";

if (!empty($_FILES['profile_picture']['name'])) {
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = uniqid("pp_") . "." . $ext;
    $target = __DIR__ . "/../../../uploads/profile/" . $filename;

    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target);

    $path = "/uploads/profile/" . $filename;

    $stmt = $pdo->prepare("UPDATE users SET profile_picture_path = ? WHERE id = ?");
    $stmt->execute([$path, $_SESSION['user_id']]);
}

header("Location: /modules/profile/view.php");
exit;
