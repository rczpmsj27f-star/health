<?php
session_start();
require_once "../../../app/config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. Insert medication
    $stmt = $pdo->prepare("
        INSERT INTO medications (user_id, nhs_medication_id, name)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $_POST['nhs_med_id'] ?: null,
        $_POST['med_name']
    ]);
    
    $medId = $pdo->lastInsertId();

    // 2. Insert dose
    $stmt = $pdo->prepare("
        INSERT INTO medication_doses (medication_id, dose_amount, dose_unit)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $medId,
        $_POST['dose_amount'],
        $_POST['dose_unit']
    ]);

    // 3. Insert schedule
    $stmt = $pdo->prepare("
        INSERT INTO medication_schedules (medication_id, frequency_type, times_per_day, times_per_week, days_of_week)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $medId,
        $_POST['frequency_type'],
        $_POST['times_per_day'] ?: null,
        $_POST['times_per_week'] ?: null,
        $_POST['days_of_week'] ?: null
    ]);

    // 4. Insert instructions
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

    // 5. Insert condition
    $name = trim($_POST['condition_name']);
    $stmt = $pdo->prepare("INSERT INTO conditions (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    $stmt->execute([$name]);
    
    $condId = $pdo->lastInsertId();
    
    $link = $pdo->prepare("
        INSERT INTO medication_conditions (medication_id, condition_id)
        VALUES (?, ?)
    ");
    $link->execute([$medId, $condId]);

    // Commit transaction
    $pdo->commit();

    // Redirect to view the medication
    header("Location: /modules/medications/view.php?id=$medId");
    exit;

} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to add medication: " . $e->getMessage();
    header("Location: /modules/medications/add_unified.php");
    exit;
}
