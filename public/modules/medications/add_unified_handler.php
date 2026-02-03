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
        INSERT INTO medications (user_id, nhs_medication_id, name, current_stock, end_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $_POST['nhs_med_id'] ?: null,
        $_POST['med_name'],
        !empty($_POST['current_stock']) ? $_POST['current_stock'] : null,
        !empty($_POST['end_date']) ? $_POST['end_date'] : null
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
    $daysOfWeek = null;
    if (!empty($_POST['days_of_week']) && is_array($_POST['days_of_week'])) {
        $daysOfWeek = implode(', ', $_POST['days_of_week']);
    }
    
    $isPrn = !empty($_POST['is_prn']) ? 1 : 0;
    
    $stmt = $pdo->prepare("
        INSERT INTO medication_schedules (medication_id, frequency_type, times_per_day, times_per_week, days_of_week, is_prn, doses_per_administration, max_doses_per_day, min_hours_between_doses)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $medId,
        $isPrn ? null : $_POST['frequency_type'],
        $isPrn ? null : ($_POST['times_per_day'] ?: null),
        $isPrn ? null : ($_POST['times_per_week'] ?: null),
        $isPrn ? null : $daysOfWeek,
        $isPrn,
        $isPrn && !empty($_POST['doses_per_administration']) ? $_POST['doses_per_administration'] : 1,
        $isPrn && !empty($_POST['max_doses_per_day']) ? $_POST['max_doses_per_day'] : null,
        $isPrn && !empty($_POST['min_hours_between_doses']) ? $_POST['min_hours_between_doses'] : null
    ]);
    
    // 3b. Insert dose times if times_per_day > 1
    if (!$isPrn && !empty($_POST['frequency_type']) && $_POST['frequency_type'] === 'per_day' && !empty($_POST['times_per_day']) && $_POST['times_per_day'] > 1) {
        for ($i = 1; $i <= $_POST['times_per_day']; $i++) {
            $timeKey = "dose_time_$i";
            if (!empty($_POST[$timeKey])) {
                $stmt = $pdo->prepare("
                    INSERT INTO medication_dose_times (medication_id, dose_number, dose_time)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$medId, $i, $_POST[$timeKey]]);
            }
        }
    }

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

    // Redirect to list with success modal
    $_SESSION['success'] = 'Medication added successfully!';
    header("Location: /modules/medications/list.php");
    exit;

} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to add medication: " . $e->getMessage();
    header("Location: /modules/medications/add_unified.php");
    exit;
}
