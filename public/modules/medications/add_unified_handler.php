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
        INSERT INTO medications (user_id, nhs_medication_id, name, current_stock, start_date, end_date, icon, color, secondary_color)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $_POST['nhs_med_id'] ?: null,
        $_POST['med_name'],
        !empty($_POST['current_stock']) ? $_POST['current_stock'] : null,
        !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        !empty($_POST['end_date']) ? $_POST['end_date'] : null,
        $_POST['medication_icon'] ?? 'pill',
        $_POST['medication_color'] ?? '#5b21b6',
        !empty($_POST['secondary_color']) ? $_POST['secondary_color'] : null
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
        INSERT INTO medication_schedules (medication_id, frequency_type, times_per_day, times_per_week, days_of_week, is_prn, initial_dose, subsequent_dose, max_doses_per_day, min_hours_between_doses, special_timing, custom_instructions)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Get special timing fields (Issue #104)
    $specialTiming = !$isPrn && !empty($_POST['special_timing']) ? $_POST['special_timing'] : null;
    $customInstructions = !$isPrn && !empty($_POST['custom_instructions']) ? $_POST['custom_instructions'] : null;
    
    $stmt->execute([
        $medId,
        $isPrn ? null : $_POST['frequency_type'],
        $isPrn ? null : ($_POST['times_per_day'] ?: null),
        $isPrn ? null : ($_POST['times_per_week'] ?: null),
        $isPrn ? null : $daysOfWeek,
        $isPrn,
        $isPrn && !empty($_POST['initial_dose']) ? $_POST['initial_dose'] : null,
        $isPrn && !empty($_POST['subsequent_dose']) ? $_POST['subsequent_dose'] : null,
        $isPrn && !empty($_POST['max_doses_per_day']) ? $_POST['max_doses_per_day'] : null,
        $isPrn && !empty($_POST['min_hours_between_doses']) ? $_POST['min_hours_between_doses'] : null,
        $specialTiming,
        $customInstructions
    ]);
    
    // 3b. Insert dose times and create future medication logs (Issue #102)
    if (!$isPrn && !empty($_POST['frequency_type']) && $_POST['frequency_type'] === 'per_day' && !empty($_POST['times_per_day'])) {
        $timesPerDay = (int)$_POST['times_per_day'];
        $currentTime = new DateTime();
        $today = new DateTime('today');
        
        if ($timesPerDay > 1) {
            // Multiple doses per day with specific times
            for ($i = 1; $i <= $timesPerDay; $i++) {
                $timeKey = "dose_time_$i";
                if (!empty($_POST[$timeKey])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO medication_dose_times (medication_id, dose_number, dose_time)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$medId, $i, $_POST[$timeKey]]);
                    
                    // Create medication log ONLY if the dose time is in the future (Issue #102)
                    $doseTime = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d') . ' ' . $_POST[$timeKey] . ':00');
                    if ($doseTime > $currentTime) {
                        $stmt = $pdo->prepare("
                            INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status)
                            VALUES (?, ?, ?, 'pending')
                        ");
                        $stmt->execute([$medId, $userId, $doseTime->format('Y-m-d H:i:s')]);
                    }
                }
            }
        } elseif ($timesPerDay == 1) {
            // Once daily - create a pending log for today only if there's a future time
            // If no specific time is set, default to noon
            $doseTimeStr = !empty($_POST['dose_time_1']) ? $_POST['dose_time_1'] : '12:00';
            
            // Save dose time if provided
            if (!empty($_POST['dose_time_1'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO medication_dose_times (medication_id, dose_number, dose_time)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$medId, 1, $_POST['dose_time_1']]);
            }
            
            // Create log ONLY if dose time is in the future
            $doseTime = DateTime::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d') . ' ' . $doseTimeStr . ':00');
            if ($doseTime > $currentTime) {
                $stmt = $pdo->prepare("
                    INSERT INTO medication_logs (medication_id, user_id, scheduled_date_time, status)
                    VALUES (?, ?, ?, 'pending')
                ");
                $stmt->execute([$medId, $userId, $doseTime->format('Y-m-d H:i:s')]);
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

    // 5. Insert condition (optional)
    if (!empty($_POST['condition_name'])) {
        $name = trim($_POST['condition_name']);
        $stmt = $pdo->prepare("INSERT INTO conditions (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $stmt->execute([$name]);
        
        $condId = $pdo->lastInsertId();
        
        $link = $pdo->prepare("
            INSERT INTO medication_conditions (medication_id, condition_id)
            VALUES (?, ?)
        ");
        $link->execute([$medId, $condId]);
    }

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
