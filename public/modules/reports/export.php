<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$format = $_GET['format'] ?? 'csv';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-90 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get data
$stmt = $pdo->prepare("
    SELECT 
        m.name as medication,
        m.dosage,
        ml.scheduled_date_time,
        ml.taken_at,
        ml.status,
        ml.skipped_reason
    FROM medication_logs ml
    JOIN medications m ON ml.medication_id = m.id
    WHERE m.user_id = ?
    AND ml.scheduled_date_time BETWEEN ? AND ?
    ORDER BY ml.scheduled_date_time DESC
");
$stmt->execute([$_SESSION['user_id'], $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$data = $stmt->fetchAll();

if ($format === 'csv') {
    header('Content-Type: text/csv');
    $filename = 'medication-history-' . date('Y-m-d') . '.csv';
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Medication', 'Dosage', 'Scheduled Time', 'Status', 'Taken At', 'Reason']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['medication'],
            $row['dosage'],
            $row['scheduled_date_time'],
            $row['status'],
            $row['taken_at'] ?? '',
            $row['skipped_reason'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

if ($format === 'json') {
    header('Content-Type: application/json');
    $filename = 'medication-history-' . date('Y-m-d') . '.json';
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
