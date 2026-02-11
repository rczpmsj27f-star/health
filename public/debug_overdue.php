<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';

if (empty($_SESSION['user_id'])) {
    die('Not logged in');
}

$todayDayOfWeek = date('D');
$todayDate = date('Y-m-d');
$currentTime = date('H:i:s');

echo "<h1>Debug Overdue Medication Count</h1>";
echo "<p><strong>Today:</strong> $todayDate</p>";
echo "<p><strong>Current Time:</strong> $currentTime</p>";
echo "<p><strong>Day of Week:</strong> $todayDayOfWeek</p>";
echo "<hr>";

$stmt = $pdo->prepare("
    SELECT DISTINCT
        m.id as med_id,
        m.name as med_name,
        mdt.dose_time, 
        ms.special_timing
    FROM medications m
    LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
    LEFT JOIN medication_dose_times mdt ON m.id = mdt.medication_id
    WHERE m.user_id = ?
    AND (m.archived = 0 OR m.archived IS NULL)
    AND (ms.is_prn = 0 OR ms.is_prn IS NULL)
    AND (
        ms.frequency_type = 'per_day' 
        OR (ms.frequency_type = 'per_week' AND ms.days_of_week LIKE ?)
    )
    AND mdt.dose_time IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM medication_logs ml2 
        WHERE ml2.medication_id = m.id 
        AND DATE(ml2.scheduled_date_time) = ?
        AND TIME(ml2.scheduled_date_time) = mdt.dose_time
        AND ml2.status IN ('taken', 'skipped')
    )
    ORDER BY mdt.dose_time
");
$stmt->execute([$_SESSION['user_id'], "%$todayDayOfWeek%", $todayDate]);
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Query Results (" . count($medications) . " rows)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Med ID</th><th>Name</th><th>Dose Time</th><th>Special Timing</th><th>Overdue?</th></tr>";

$overdueCount = 0;
$currentRealDateTime = new DateTime();

foreach ($medications as $med) {
    if (empty($med['dose_time'])) {
        continue;
    }
    
    // Handle special timing overrides
    if ($med['special_timing'] === 'on_waking') {
        // "On waking" medications are considered overdue after 9:00 AM
        $effectiveTime = $todayDate . ' 09:00:00';
        $logic = "on_waking (after 9am)";
    } elseif ($med['special_timing'] === 'before_bed') {
        // "Before bed" medications are considered overdue after 10:00 PM
        $effectiveTime = $todayDate . ' 22:00:00';
        $logic = "before_bed (after 10pm)";
    } else {
        // Regular timed medications use their actual dose_time
        $effectiveTime = $todayDate . ' ' . $med['dose_time'];
        $logic = "regular (DateTime comparison)";
    }
    
    $scheduledDT = new DateTime($effectiveTime);
    
    // Only count as overdue if the effective scheduled datetime is in the past
    $isOverdue = $scheduledDT < $currentRealDateTime;
    
    if ($isOverdue) {
        $overdueCount++;
    }
    
    echo "<tr>";
    echo "<td>{$med['med_id']}</td>";
    echo "<td>" . htmlspecialchars($med['med_name']) . "</td>";
    echo "<td>{$med['dose_time']}</td>";
    echo "<td>" . ($med['special_timing'] ?? 'null') . "</td>";
    echo "<td style='background:" . ($isOverdue ? '#ffcccc' : '#ccffcc') . "'>";
    echo ($isOverdue ? 'YES' : 'NO') . " ($logic)";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<h2>Overdue Count: $overdueCount</h2>";
?>
