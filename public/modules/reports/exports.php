<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/LinkedUserHelper.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Exports & Reports</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 16px 16px 40px 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .page-title h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
            color: var(--color-primary);
        }

        .page-title p {
            margin: 0;
            font-size: 14px;
            color: var(--color-text-secondary);
        }
        
        .export-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 24px;
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        
        .export-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .export-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .export-card-icon {
            font-size: 32px;
        }
        
        .export-card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-text-primary);
            margin: 0;
        }
        
        .export-card-description {
            color: var(--color-text-secondary);
            font-size: 14px;
            margin: 0 0 16px 0;
            line-height: 1.5;
        }
        
        .export-options {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .btn-export {
            background: var(--color-primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-export:hover {
            background: #6d28d9;
            transform: translateY(-1px);
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--color-text-primary);
            font-size: 14px;
        }
        
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 14px;
            color: var(--color-text);
        }
        
        .form-inline {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        @media (max-width: 600px) {
            .form-inline {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    
    <div id="main-content">
    <div class="page-content">
        <div class="page-title">
            <h2>📊 Medication Exports & Reports</h2>
            <p>Generate PDF reports and printable medication charts</p>
            <p style="color: var(--color-info); font-size: 13px; margin-top: 8px;">
                📌 PDF reports open in a new window so you can stay in the app
            </p>
        </div>

        <?php include __DIR__ . '/../../../app/includes/user_switcher.php'; ?>
        
        <!-- Current Medications Export -->
        <div class="export-card">
            <div class="export-card-header">
                <span class="export-card-icon">💊</span>
                <h3 class="export-card-title">Current Medications</h3>
            </div>
            <p class="export-card-description">
                Export a complete list of all active (unarchived) medications with details including dosage, schedule, and instructions.
            </p>
            <div class="export-options">
                <a href="export_pdf.php?type=current_medications<?= $viewingLinkedUser ? '&view=linked' : '' ?>" 
                   class="btn-export" target="_blank">
                    📄 Download PDF
                </a>
            </div>
        </div>
        
        <!-- Medication Schedule Export -->
        <div class="export-card">
            <div class="export-card-header">
                <span class="export-card-icon">📅</span>
                <h3 class="export-card-title">Medication Schedule</h3>
            </div>
            <p class="export-card-description">
                Generate a tabular schedule showing all medications and their dose times. Available in weekly or monthly format.
            </p>
            <div class="export-options">
                <a href="export_pdf.php?type=schedule&format=weekly<?= $viewingLinkedUser ? '&view=linked' : '' ?>" 
                   class="btn-export" target="_blank">
                    📄 Weekly Schedule PDF
                </a>
                <a href="export_pdf.php?type=schedule&format=monthly<?= $viewingLinkedUser ? '&view=linked' : '' ?>" 
                   class="btn-export" target="_blank">
                    📄 Monthly Schedule PDF
                </a>
            </div>
        </div>
        
        <!-- Manual Medication Chart -->
        <div class="export-card">
            <div class="export-card-header">
                <span class="export-card-icon">📋</span>
                <h3 class="export-card-title">Manual Medication Chart</h3>
            </div>
            <p class="export-card-description">
                Create a printable chart with tick boxes for manually tracking medication doses. Perfect for care settings or keeping paper records.
            </p>
            
            <form action="export_pdf.php" method="get" target="_blank" style="margin-top: 20px;">
                <input type="hidden" name="type" value="manual_chart">
                <?php if ($viewingLinkedUser): ?>
                <input type="hidden" name="view" value="linked">
                <?php endif; ?>
                
                <div class="form-inline">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="medications">Select Medications (optional - leave empty for all)</label>
                    <select id="medications" name="medications[]" multiple size="5" 
                            style="height: auto; padding: 8px;">
                        <?php
                        // Get all active medications for the target user
                        $stmt = $pdo->prepare("
                            SELECT id, name 
                            FROM medications 
                            WHERE user_id = ? AND (archived = 0 OR archived IS NULL)
                            ORDER BY name ASC
                        ");
                        $stmt->execute([$targetUserId]);
                        $meds = $stmt->fetchAll();
                        
                        foreach ($meds as $med):
                        ?>
                            <option value="<?= $med['id'] ?>">
                                <?= htmlspecialchars($med['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; margin-top: 4px; color: var(--color-text-secondary);">
                        Hold Ctrl (Cmd on Mac) to select multiple medications
                    </small>
                </div>
                
                <button type="submit" class="btn-export" style="border: none; cursor: pointer;">
                    📄 Generate Chart PDF
                </button>
            </form>
        </div>
        
        <!-- PRN Usage Report -->
        <div class="export-card">
            <div class="export-card-header">
                <span class="export-card-icon">🆘</span>
                <h3 class="export-card-title">PRN Usage Report</h3>
            </div>
            <p class="export-card-description">
                Export a detailed report of PRN (as needed) medication usage over a specified time period.
            </p>
            
            <form action="export_pdf.php" method="get" target="_blank" style="margin-top: 20px;">
                <input type="hidden" name="type" value="prn_usage">
                <?php if ($viewingLinkedUser): ?>
                <input type="hidden" name="view" value="linked">
                <?php endif; ?>
                
                <div class="form-inline">
                    <div class="form-group">
                        <label for="prn_start_date">Start Date</label>
                        <input type="date" id="prn_start_date" name="start_date" 
                               value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prn_end_date">End Date</label>
                        <input type="date" id="prn_end_date" name="end_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-export" style="border: none; cursor: pointer;">
                    📄 Download PRN Report PDF
                </button>
            </form>
        </div>
    </div>
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
