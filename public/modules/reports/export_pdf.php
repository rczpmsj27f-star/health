<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";
require_once "../../../vendor/autoload.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Check for linked user
$linkedHelper = new LinkedUserHelper($pdo);
$linkedUser = $linkedHelper->getLinkedUser($_SESSION['user_id']);
$canExportLinkedUser = false;

if ($linkedUser && $linkedUser['status'] === 'active') {
    $myPermissions = $linkedHelper->getPermissions($linkedUser['id'], $_SESSION['user_id']);
    $canExportLinkedUser = !empty($myPermissions['can_export_data']);
}

$viewingLinkedUser = isset($_GET['view']) && $_GET['view'] === 'linked' && $linkedUser;
$targetUserId = $viewingLinkedUser ? $linkedUser['linked_user_id'] : $_SESSION['user_id'];

// Check permissions if viewing linked user
if ($viewingLinkedUser && !$canExportLinkedUser) {
    $_SESSION['error_msg'] = "You don't have permission to export their data";
    header("Location: /modules/reports/exports.php");
    exit;
}

// Get target user info
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch();

$type = $_GET['type'] ?? 'current_medications';

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Health Tracker');
$pdf->SetAuthor('Health Tracker');
$pdf->SetTitle('Medication Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

switch ($type) {
    case 'current_medications':
        generateCurrentMedications($pdf, $pdo, $targetUserId, $targetUser);
        break;
    
    case 'schedule':
        $format = $_GET['format'] ?? 'weekly';
        generateSchedule($pdf, $pdo, $targetUserId, $targetUser, $format);
        break;
    
    case 'manual_chart':
        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days'));
        $selectedMeds = $_GET['medications'] ?? [];
        generateManualChart($pdf, $pdo, $targetUserId, $targetUser, $startDate, $endDate, $selectedMeds);
        break;
    
    case 'prn_usage':
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        generatePRNUsage($pdf, $pdo, $targetUserId, $targetUser, $startDate, $endDate);
        break;
    
    default:
        die('Invalid export type');
}

// Close and output PDF document
$filename = 'medication_report_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D');
exit;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function generateCurrentMedications($pdf, $pdo, $userId, $user) {
    // Title
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'Current Medications Report', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'User: ' . htmlspecialchars($user['username']), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Get active medications
    $stmt = $pdo->prepare("
        SELECT m.*, ms.frequency_type, ms.times_per_day, ms.times_per_week, 
               ms.days_of_week, ms.is_prn
        FROM medications m
        LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
        WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL)
        ORDER BY m.name ASC
    ");
    $stmt->execute([$userId]);
    $medications = $stmt->fetchAll();
    
    if (empty($medications)) {
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->Cell(0, 10, 'No active medications found.', 0, 1, 'C');
    } else {
        // Separate scheduled and PRN
        $scheduled = [];
        $prn = [];
        foreach ($medications as $med) {
            if (!empty($med['is_prn'])) {
                $prn[] = $med;
            } else {
                $scheduled[] = $med;
            }
        }
        
        // Scheduled Medications
        if (!empty($scheduled)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Scheduled Medications', 0, 1, 'L');
            $pdf->Ln(2);
            
            foreach ($scheduled as $med) {
                renderMedicationBlock($pdf, $med);
            }
        }
        
        // PRN Medications
        if (!empty($prn)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'PRN Medications (As Needed)', 0, 1, 'L');
            $pdf->Ln(2);
            
            foreach ($prn as $med) {
                renderMedicationBlock($pdf, $med);
            }
        }
    }
}

function renderMedicationBlock($pdf, $med) {
    // Medication name
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, htmlspecialchars($med['name']), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    
    // Schedule
    if (!empty($med['is_prn'])) {
        $pdf->Cell(40, 5, 'Schedule:', 0, 0, 'L');
        $pdf->Cell(0, 5, 'As and when needed (PRN)', 0, 1, 'L');
    } elseif ($med['frequency_type']) {
        $pdf->Cell(40, 5, 'Schedule:', 0, 0, 'L');
        if ($med['frequency_type'] === 'per_day') {
            $schedule = $med['times_per_day'] . ' time' . ($med['times_per_day'] > 1 ? 's' : '') . ' per day';
        } else {
            $schedule = $med['times_per_week'] . ' time' . ($med['times_per_week'] > 1 ? 's' : '') . ' per week';
            if ($med['days_of_week']) {
                $schedule .= ' on ' . $med['days_of_week'];
            }
        }
        $pdf->Cell(0, 5, $schedule, 0, 1, 'L');
    }
    
    // Instructions
    if (!empty($med['instructions'])) {
        $pdf->Cell(40, 5, 'Instructions:', 0, 0, 'L');
        $pdf->MultiCell(0, 5, htmlspecialchars($med['instructions']), 0, 'L');
    }
    
    // Start date
    if (!empty($med['start_date'])) {
        $pdf->Cell(40, 5, 'Started:', 0, 0, 'L');
        $pdf->Cell(0, 5, date('M j, Y', strtotime($med['start_date'])), 0, 1, 'L');
    }
    
    // End date
    if (!empty($med['end_date'])) {
        $pdf->Cell(40, 5, 'End Date:', 0, 0, 'L');
        $pdf->Cell(0, 5, date('M j, Y', strtotime($med['end_date'])), 0, 1, 'L');
    }
    
    // Stock
    if (!empty($med['current_stock'])) {
        $pdf->Cell(40, 5, 'Current Stock:', 0, 0, 'L');
        $pdf->Cell(0, 5, $med['current_stock'], 0, 1, 'L');
    }
    
    // Notes
    if (!empty($med['notes'])) {
        $pdf->Cell(40, 5, 'Notes:', 0, 0, 'L');
        $pdf->MultiCell(0, 5, htmlspecialchars($med['notes']), 0, 'L');
    }
    
    $pdf->Ln(4);
}

function generateSchedule($pdf, $pdo, $userId, $user, $format) {
    // Title
    $pdf->SetFont('helvetica', 'B', 18);
    $formatTitle = ucfirst($format) . ' Medication Schedule';
    $pdf->Cell(0, 10, $formatTitle, 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'User: ' . htmlspecialchars($user['username']), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    
    if ($format === 'weekly') {
        $pdf->Cell(0, 6, 'Week of: ' . date('F j, Y'), 0, 1, 'C');
    } else {
        $pdf->Cell(0, 6, 'Month: ' . date('F Y'), 0, 1, 'C');
    }
    
    $pdf->Ln(5);
    
    // Get medications with dose times
    $stmt = $pdo->prepare("
        SELECT m.id, m.name, ms.is_prn
        FROM medications m
        LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
        WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL)
        ORDER BY m.name ASC
    ");
    $stmt->execute([$userId]);
    $medications = $stmt->fetchAll();
    
    if (empty($medications)) {
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->Cell(0, 10, 'No active medications found.', 0, 1, 'C');
        return;
    }
    
    // Get dose times for each medication
    $medWithTimes = [];
    $prnMeds = [];
    
    foreach ($medications as $med) {
        if (!empty($med['is_prn'])) {
            $prnMeds[] = $med;
            continue;
        }
        
        $stmt = $pdo->prepare("
            SELECT dose_time 
            FROM medication_dose_times 
            WHERE medication_id = ?
            ORDER BY dose_time ASC
        ");
        $stmt->execute([$med['id']]);
        $times = $stmt->fetchAll();
        
        if (!empty($times)) {
            $medWithTimes[] = [
                'medication' => $med,
                'times' => $times
            ];
        }
    }
    
    // Create schedule table
    if ($format === 'weekly') {
        renderWeeklySchedule($pdf, $medWithTimes);
    } else {
        renderMonthlySchedule($pdf, $medWithTimes);
    }
    
    // Add PRN section
    if (!empty($prnMeds)) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'PRN Medications (As Needed):', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        foreach ($prnMeds as $med) {
            $pdf->Cell(5, 5, chr(149), 0, 0, 'L'); // Bullet point
            $pdf->Cell(0, 5, htmlspecialchars($med['name']), 0, 1, 'L');
        }
    }
}

function renderWeeklySchedule($pdf, $medWithTimes) {
    if (empty($medWithTimes)) {
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 6, 'No scheduled medications with dose times.', 0, 1, 'L');
        return;
    }
    
    $pdf->SetFont('helvetica', 'B', 9);
    
    // Header row
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $colWidth = 25;
    
    $pdf->Cell(40, 7, 'Medication / Time', 1, 0, 'C');
    foreach ($days as $day) {
        $pdf->Cell($colWidth, 7, $day, 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Data rows
    $pdf->SetFont('helvetica', '', 8);
    foreach ($medWithTimes as $item) {
        $med = $item['medication'];
        $times = $item['times'];
        
        foreach ($times as $time) {
            $timeLabel = date('g:i A', strtotime($time['dose_time']));
            
            $pdf->Cell(40, 6, htmlspecialchars($med['name']) . "\n" . $timeLabel, 1, 0, 'L');
            
            // Checkboxes for each day
            for ($i = 0; $i < 7; $i++) {
                $pdf->Cell($colWidth, 6, '', 1, 0, 'C');
            }
            $pdf->Ln();
        }
    }
}

function renderMonthlySchedule($pdf, $medWithTimes) {
    if (empty($medWithTimes)) {
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 6, 'No scheduled medications with dose times.', 0, 1, 'L');
        return;
    }
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Daily Medication Schedule', 0, 1, 'L');
    $pdf->Ln(2);
    
    foreach ($medWithTimes as $item) {
        $med = $item['medication'];
        $times = $item['times'];
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, htmlspecialchars($med['name']), 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        foreach ($times as $time) {
            $timeLabel = date('g:i A', strtotime($time['dose_time']));
            $pdf->Cell(10, 5, chr(149), 0, 0, 'L'); // Bullet
            $pdf->Cell(0, 5, $timeLabel, 0, 1, 'L');
        }
        $pdf->Ln(3);
    }
}

function generateManualChart($pdf, $pdo, $userId, $user, $startDate, $endDate, $selectedMeds) {
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Manual Medication Chart', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'User: ' . htmlspecialchars($user['username']), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Period: ' . date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Get medications
    if (!empty($selectedMeds)) {
        $placeholders = str_repeat('?,', count($selectedMeds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT m.id, m.name, ms.is_prn
            FROM medications m
            LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
            WHERE m.user_id = ? AND m.id IN ($placeholders) AND (m.archived = 0 OR m.archived IS NULL)
            ORDER BY m.name ASC
        ");
        $params = array_merge([$userId], $selectedMeds);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("
            SELECT m.id, m.name, ms.is_prn
            FROM medications m
            LEFT JOIN medication_schedules ms ON m.id = ms.medication_id
            WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL)
            ORDER BY m.name ASC
        ");
        $stmt->execute([$userId]);
    }
    $medications = $stmt->fetchAll();
    
    if (empty($medications)) {
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 10, 'No medications found for the selected criteria.', 0, 1, 'C');
        return;
    }
    
    // Calculate date range
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $days = $interval->days + 1;
    
    // Create chart
    $pdf->SetFont('helvetica', 'B', 8);
    
    // Determine if landscape is better
    if ($days > 7) {
        // Use landscape for longer periods
        $pdf->AddPage('L');
    }
    
    // Header row with dates
    $dateColWidth = 15;
    $pdf->Cell(50, 8, 'Medication', 1, 0, 'C');
    
    $currentDate = clone $start;
    for ($i = 0; $i < min($days, 14); $i++) { // Limit to 14 days for readability
        $pdf->Cell($dateColWidth, 8, $currentDate->format('M j'), 1, 0, 'C');
        $currentDate->modify('+1 day');
    }
    $pdf->Ln();
    
    // Medication rows
    $pdf->SetFont('helvetica', '', 8);
    
    foreach ($medications as $med) {
        // Get dose times
        $stmt = $pdo->prepare("
            SELECT dose_time 
            FROM medication_dose_times 
            WHERE medication_id = ?
            ORDER BY dose_time ASC
        ");
        $stmt->execute([$med['id']]);
        $times = $stmt->fetchAll();
        
        if (empty($times) && empty($med['is_prn'])) {
            // No times, single row
            $pdf->Cell(50, 7, htmlspecialchars($med['name']), 1, 0, 'L');
            for ($i = 0; $i < min($days, 14); $i++) {
                $pdf->Cell($dateColWidth, 7, '[ ]', 1, 0, 'C'); // Checkbox
            }
            $pdf->Ln();
        } elseif (!empty($med['is_prn'])) {
            // PRN medication
            $pdf->Cell(50, 7, htmlspecialchars($med['name']) . ' (PRN)', 1, 0, 'L');
            for ($i = 0; $i < min($days, 14); $i++) {
                $pdf->Cell($dateColWidth, 7, '[ ]', 1, 0, 'C'); // Checkbox
            }
            $pdf->Ln();
        } else {
            // Multiple dose times - show each time on its own row
            foreach ($times as $idx => $time) {
                $timeStr = date('g:i A', strtotime($time['dose_time']));
                
                if ($idx === 0) {
                    // First row: medication name with time
                    $pdf->Cell(50, 7, htmlspecialchars($med['name']) . ' - ' . $timeStr, 1, 0, 'L');
                } else {
                    // Subsequent rows: indented time only
                    $pdf->Cell(50, 7, '    ' . $timeStr, 1, 0, 'L');
                }
                
                for ($i = 0; $i < min($days, 14); $i++) {
                    $pdf->Cell($dateColWidth, 7, '[ ]', 1, 0, 'C'); // Checkbox
                }
                $pdf->Ln();
            }
        }
    }
    
    // Legend
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Instructions: Check off each box when the medication is taken.', 0, 1, 'L');
}

function generatePRNUsage($pdf, $pdo, $userId, $user, $startDate, $endDate) {
    // Title
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'PRN Medication Usage Report', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'User: ' . htmlspecialchars($user['username']), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Period: ' . date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Get PRN medications and their usage
    $stmt = $pdo->prepare("
        SELECT m.id, m.name
        FROM medications m
        JOIN medication_schedules ms ON m.id = ms.medication_id
        WHERE m.user_id = ? AND ms.is_prn = 1 AND (m.archived = 0 OR m.archived IS NULL)
        ORDER BY m.name ASC
    ");
    $stmt->execute([$userId]);
    $prnMeds = $stmt->fetchAll();
    
    if (empty($prnMeds)) {
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->Cell(0, 10, 'No PRN medications found.', 0, 1, 'C');
        return;
    }
    
    foreach ($prnMeds as $med) {
        // Get PRN logs for this medication
        $stmt = $pdo->prepare("
            SELECT taken_at, quantity_taken, status
            FROM medication_logs
            WHERE medication_id = ? 
            AND user_id = ?
            AND taken_at BETWEEN ? AND ?
            AND status = 'taken'
            ORDER BY taken_at DESC
        ");
        $stmt->execute([$med['id'], $userId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $logs = $stmt->fetchAll();
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, htmlspecialchars($med['name']), 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Total Uses: ' . count($logs), 0, 1, 'L');
        $pdf->Ln(2);
        
        if (empty($logs)) {
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->Cell(0, 5, 'No usage recorded during this period.', 0, 1, 'L');
        } else {
            // Table header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(60, 6, 'Date/Time', 1, 0, 'C');
            $pdf->Cell(40, 6, 'Quantity Taken', 1, 0, 'C');
            $pdf->Cell(80, 6, 'Status', 1, 0, 'C');
            $pdf->Ln();
            
            // Table data
            $pdf->SetFont('helvetica', '', 8);
            foreach ($logs as $log) {
                $pdf->Cell(60, 6, date('M j, Y g:i A', strtotime($log['taken_at'])), 1, 0, 'L');
                $pdf->Cell(40, 6, htmlspecialchars((string)($log['quantity_taken'] ?? 1)), 1, 0, 'C');
                $pdf->Cell(80, 6, htmlspecialchars(ucfirst($log['status'])), 1, 0, 'L');
                $pdf->Ln();
            }
        }
        
        $pdf->Ln(5);
    }
}
