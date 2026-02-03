<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medId = $_GET['id'];
$isAdmin = Auth::isAdmin();

$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ?");
$stmt->execute([$medId]);
$med = $stmt->fetch();

// Check if medication is archived
$isArchived = !empty($med['archived']) && $med['archived'] == 1;

$stmt = $pdo->prepare("SELECT * FROM medication_doses WHERE medication_id = ?");
$stmt->execute([$medId]);
$dose = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM medication_schedules WHERE medication_id = ?");
$stmt->execute([$medId]);
$schedule = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM medication_instructions WHERE medication_id = ?");
$stmt->execute([$medId]);
$instructions = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM medication_alerts WHERE medication_id = ?");
$stmt->execute([$medId]);
$alerts = $stmt->fetchAll();

// Days of week for visualizer
$daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$activeDays = [];
if ($schedule && $schedule['days_of_week']) {
    $activeDays = array_map('trim', explode(',', $schedule['days_of_week']));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($med['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
/* Critical inline styles - fallback if external CSS doesn't load */
.tile {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 24px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: #ffffff;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.tile .tile-title, .tile .tile-desc, .tile .tile-icon {
    color: #ffffff;
}
.btn {
    padding: 14px 20px;
    border-radius: 6px;
    border: none;
    font-size: 16px;
    color: #ffffff;
    display: block;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    min-height: 48px;
}
.btn-primary { background: #2563eb; color: #fff; }
.btn-secondary { background: #6c757d; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-info { background: #007bff; color: #fff; }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <div class="menu" id="menu">
        <h3>Menu</h3>
        <a href="/dashboard.php">🏠 Dashboard</a>
        <a href="/modules/profile/view.php">👤 My Profile</a>
        
        <div class="menu-parent">
            <a href="/modules/medications/dashboard.php" class="menu-parent-link">💊 Medications</a>
            <div class="menu-children">
                <a href="/modules/medications/list.php">My Medications</a>
                <a href="/modules/medications/stock.php">Medication Stock</a>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div style="padding: 80px 16px 40px 16px; max-width: 800px; margin: 0 auto;">
        <div class="page-card">
            <div class="page-header">
                <h2>💊 <?= htmlspecialchars($med['name']) ?></h2>
                <p>Medication Details</p>
            </div>

            <div class="section-header">Dosage Information</div>
            <div class="info-item">
                <div class="info-label">Dose Amount</div>
                <div class="info-value"><?= number_format($dose['dose_amount'], 2) ?> <?= htmlspecialchars($dose['dose_unit']) ?></div>
            </div>

            <div class="section-header">Schedule</div>
            <div class="schedule-grid">
                <?php if ($schedule['frequency_type'] === 'per_day'): ?>
                    <div class="schedule-time">
                        <div class="schedule-time-label">⏰ Daily Schedule</div>
                        <div class="schedule-time-value">
                            <?= htmlspecialchars($schedule['times_per_day']) ?> time(s) per day
                        </div>
                    </div>
                    
                    <!-- Daily visualizer -->
                    <div class="day-visualizer">
                        <?php foreach ($daysOfWeek as $day): ?>
                            <div class="day-badge active"><?= $day ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="schedule-time">
                        <div class="schedule-time-label">📅 Weekly Schedule</div>
                        <div class="schedule-time-value">
                            <?= htmlspecialchars($schedule['times_per_week']) ?> time(s) per week
                        </div>
                    </div>
                    
                    <!-- Weekly visualizer -->
                    <div class="day-visualizer">
                        <?php foreach ($daysOfWeek as $day): ?>
                            <div class="day-badge <?= in_array($day, $activeDays) ? 'active' : '' ?>">
                                <?= $day ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($instructions)): ?>
            <div class="section-header">Special Instructions</div>
            <div class="schedule-grid">
                <?php foreach ($instructions as $i): ?>
                    <div class="schedule-time">
                        <div class="schedule-time-value">
                            📋 <?= htmlspecialchars($i['instruction_text']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($alerts)): ?>
            <div class="section-header">NHS System Alerts</div>
            <?php foreach ($alerts as $a): ?>
                <div class="nhs-alert">
                    <div class="nhs-alert-title"><?= htmlspecialchars($a['alert_title']) ?></div>
                    <div class="nhs-alert-body"><?= nl2br(htmlspecialchars($a['alert_body'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$isArchived): ?>
                <!-- If NOT archived, show these buttons -->
                <div class="action-buttons three-col" style="margin-top: 32px;">
                    <a class="btn btn-primary" href="/modules/medications/edit.php?id=<?= $medId ?>">✏️ Edit</a>
                    <button class="btn btn-success" id="addStockBtn" data-med-id="<?= $medId ?>" data-med-name="<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>">📦 Add Stock</button>
                    <a class="btn btn-secondary" href="/modules/medications/archive_handler.php?id=<?= $medId ?>&action=archive" onclick="return confirm('Archive this medication?')">📦 Archive</a>
                </div>
                
                <div class="action-buttons" style="margin-top: 16px;">
                    <a class="btn btn-danger" href="/modules/medications/delete_handler.php?id=<?= $medId ?>" onclick="return confirm('Are you sure you want to delete this medication? This action cannot be undone.');">🗑️ Delete</a>
                </div>
            <?php else: ?>
                <!-- If ARCHIVED, show only Unarchive and Delete -->
                <div class="action-buttons" style="margin-top: 32px;">
                    <button class="btn btn-primary" onclick="showUnarchiveModal(<?= $medId ?>)">📤 Unarchive</button>
                    <a class="btn btn-danger" href="/modules/medications/delete_handler.php?id=<?= $medId ?>" onclick="return confirm('Are you sure you want to delete this medication? This action cannot be undone.');">🗑️ Delete</a>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 16px;">
                <a class="btn btn-info" href="/modules/medications/list.php">⬅️ Back to Medications</a>
            </div>
        </div>
    </div>
    
    <!-- Add Stock Modal -->
    <div id="addStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📦 Add Stock</h3>
            </div>
            <form method="POST" action="/modules/medications/add_stock_handler.php" id="addStockForm">
                <div class="modal-body">
                    <input type="hidden" name="medication_id" id="medication_id">
                    
                    <div class="form-group">
                        <label>Medication</label>
                        <input type="text" id="medication_name" readonly style="background: var(--color-bg-gray); padding: 12px; border: 1px solid var(--color-border); border-radius: 6px; width: 100%;">
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity to Add *</label>
                        <input type="number" name="quantity" id="quantity" min="1" required placeholder="e.g., 30" style="padding: 12px; border: 1px solid var(--color-border); border-radius: 6px; width: 100%;">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            Enter the number of tablets/doses to add to current stock
                        </small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary cancel-stock-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">✅ Add Stock</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Unarchive Confirmation Modal -->
    <div id="unarchiveModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h3>📤 Unarchive Medication</h3>
            <p>Are you sure you want to unarchive <strong><?= htmlspecialchars($med['name']) ?></strong>?</p>
            <div class="modal-buttons" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button class="btn btn-secondary" onclick="closeUnarchiveModal()">No, Cancel</button>
                <button class="btn btn-primary" onclick="confirmUnarchive(<?= $medId ?>)">Yes, Unarchive</button>
            </div>
        </div>
    </div>
    
    <style>
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: var(--color-bg-white);
        border-radius: var(--radius-md);
        padding: 32px;
        max-width: 500px;
        width: 90%;
        box-shadow: var(--shadow-lg);
    }
    
    .modal-header {
        margin-bottom: 24px;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 24px;
        color: var(--color-primary);
    }
    
    .modal-body {
        margin-bottom: 24px;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--color-text);
    }
    
    .btn-success {
        background: #28a745;
        color: #ffffff;
    }
    
    .btn-success:hover {
        background: #218838;
    }
    </style>
    
    <script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Add Stock button handler
        var addStockBtn = document.getElementById('addStockBtn');
        if (addStockBtn) {
            addStockBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var medId = this.getAttribute('data-med-id');
                var medName = this.getAttribute('data-med-name');
                showAddStockModal(medId, medName);
            });
        }
        
        // Cancel button handler
        var cancelBtn = document.querySelector('.cancel-stock-modal');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeAddStockModal);
        }
        
        // Close modal when clicking outside
        var modal = document.getElementById('addStockModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAddStockModal();
                }
            });
        }
        
        var unarchiveModal = document.getElementById('unarchiveModal');
        if (unarchiveModal) {
            unarchiveModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeUnarchiveModal();
                }
            });
        }
        
        // Check for success messages from session
        <?php if (isset($_SESSION['success'])): ?>
            showSuccessModal('<?= htmlspecialchars($_SESSION['success'], ENT_QUOTES) ?>');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    });
    
    function showAddStockModal(medId, medName) {
        document.getElementById('medication_id').value = medId;
        document.getElementById('medication_name').value = medName;
        document.getElementById('quantity').value = '';
        document.getElementById('addStockModal').classList.add('active');
    }
    
    function closeAddStockModal() {
        document.getElementById('addStockModal').classList.remove('active');
    }
    
    function showUnarchiveModal(medId) {
        document.getElementById('unarchiveModal').style.display = 'flex';
    }
    
    function closeUnarchiveModal() {
        document.getElementById('unarchiveModal').style.display = 'none';
    }
    
    function confirmUnarchive(medId) {
        window.location.href = '/modules/medications/archive_handler.php?id=' + medId + '&action=unarchive';
    }
    </script>
</body>
</html>
