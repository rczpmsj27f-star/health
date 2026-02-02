<?php
session_start();
require_once "../../../app/config/database.php";

$medId = $_POST['med_id'];

if (!empty($_POST['instructions'])) {
    foreach ($_POST['instructions'] as $i) {
        $stmt = $pdo->prepare("
            INSERT INTO medication_instructions (medication_id, instruction_text)
            VALUES (?, ?)
        ");
        $stmt->execute([$medId, $i]);
    }
}

if (!empty($_POST['other_instruction'])) {
    $stmt = $pdo->prepare("
        INSERT INTO medication_instructions (medication_id, instruction_text)
        VALUES (?, ?)
    ");
    $stmt->execute([$medId, $_POST['other_instruction']]);
}

header("Location: /modules/medications/add_condition.php?med=$medId");
exit;
