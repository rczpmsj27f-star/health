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
$currentTimeStamp = strtotime(date('H:i'));

foreach ($medications as $med) {
    $doseTime = strtotime($med['dose_time']);
    $isOverdue = false;
    
    if (!empty($med['special_timing']) && $med['special_timing'] === 'on_waking') {
        $isOverdue = $currentTimeStamp > strtotime('09:00');
        $logic = "on_waking (after 9am)";
    } elseif (!empty($med['special_timing']) && $med['special_timing'] === 'before_bed') {
        $isOverdue = $currentTimeStamp > strtotime('22:00');
        $logic = "before_bed (after 10pm)";
    } else {
        $isOverdue = $currentTimeStamp > $doseTime;
        $logic = "regular (current > dose_time)";
    }
    
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
