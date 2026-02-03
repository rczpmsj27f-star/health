<?php
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = Auth::isAdmin();

// Get active medications with stock information
$stmt = $pdo->prepare("
    SELECT m.*, md.dose_amount, md.dose_unit
    FROM medications m
    LEFT JOIN medication_doses md ON m.id = md.medication_id
    WHERE m.user_id = ? AND (m.archived = 0 OR m.archived IS NULL)
    ORDER BY m.name
");
$stmt->execute([$userId]);
$medications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Stock</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/modal.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 16px 16px 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .page-title h2 {
            margin: 0 0 8px 0;
            font-size: 32px;
            color: var(--color-primary);
            font-weight: 600;
        }
        
        .page-title p {
            margin: 0;
            color: var(--color-text-secondary);
        }
        
        .stock-list {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        .stock-item {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--color-border);
        }
        
        .stock-item:last-child {
            border-bottom: none;
        }
        
        .stock-info h3 {
            margin: 0 0 4px 0;
            font-size: 18px;
            color: var(--color-text);
        }
        
        .stock-info p {
            margin: 0;
            font-size: 14px;
            color: var(--color-text-secondary);
        }
        
        .stock-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .stock-level {
            text-align: right;
            min-width: 120px;
        }
        
        .stock-count {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }
        
        .stock-count.high {
            color: #28a745;
        }
        
        .stock-count.medium {
            color: #ffc107;
        }
        
        .stock-count.low {
            color: #dc3545;
        }
        
        .stock-count.empty {
            color: var(--color-text-secondary);
        }
        
        .stock-label {
            font-size: 12px;
            color: var(--color-text-secondary);
            margin-top: 4px;
        }
        
        .stock-updated {
            font-size: 11px;
            color: var(--color-text-secondary);
            font-style: italic;
        }
        
        .stock-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn-add-stock {
            background: var(--color-success);
            color: white;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .btn-add-stock:hover {
            opacity: 0.9;
        }
        
        .btn-remove-stock {
            background: var(--color-danger);
            color: white;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .btn-remove-stock:hover {
            opacity: 0.9;
        }
        
        .btn-edit-med {
            background: var(--color-primary);
            color: white;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s;
            text-align: center;
            display: inline-block;
            flex: 1;
            min-width: 150px;
        }
        
        .btn-edit-med:hover {
            opacity: 0.9;
        }
        
        .no-meds {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-secondary);
        }
        
        .no-meds p {
            margin: 0 0 20px 0;
        }
        
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 16px;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--color-primary);
            color: var(--color-bg-white);
        }
        
        .btn-secondary {
            background: var(--color-secondary);
            color: var(--color-bg-white);
        }
    </style>
</head>
<body>
    <a href="/modules/medications/dashboard.php" class="back-to-dashboard" title="Back to Medication Dashboard">
        ← Back to Dashboard
    </a>
    
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
                <a href="/modules/medications/compliance.php">Compliance</a>
                <a href="/modules/medications/log_prn.php">Log PRN</a>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="/modules/admin/users.php">⚙️ User Management</a>
        <?php endif; ?>
        <a href="/logout.php">🚪 Logout</a>
    </div>

    <div class="page-content">
        <div class="page-title">
            <h2>📦 Medication Stock</h2>
            <p>Track and update your medication stock levels</p>
        </div>
        
        <?php if (empty($medications)): ?>
            <div class="no-meds">
                <p>You don't have any active medications yet.</p>
                <a class="btn btn-primary" href="/modules/medications/add_unified.php">➕ Add Medication</a>
            </div>
        <?php else: ?>
            <div class="stock-list">
                <?php foreach ($medications as $med): ?>
                    <div class="stock-item">
                        <div class="stock-header">
                            <div class="stock-info">
                                <h3>💊 <?= htmlspecialchars($med['name']) ?></h3>
                                <p style="margin: 4px 0; font-size: 14px; color: var(--color-text-secondary);">
                                    <?php 
                                    $infoParts = [];
                                    if (!empty($med['dose_amount']) && !empty($med['dose_unit'])) {
                                        $infoParts[] = htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']);
                                    }
                                    if (!empty($med['created_at'])) {
                                        $infoParts[] = 'Date added: ' . date('M j, Y', strtotime($med['created_at']));
                                    }
                                    if (!empty($med['end_date'])) {
                                        $infoParts[] = 'End due: ' . date('M j, Y', strtotime($med['end_date']));
                                    }
                                    echo implode(' • ', $infoParts);
                                    ?>
                                </p>
                                <?php if ($med['stock_updated_at']): ?>
                                    <p class="stock-updated">Stock updated: <?= date('M j, Y H:i', strtotime($med['stock_updated_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="stock-level">
                                <?php
                                $stockClass = '';
                                $stockDisplay = $med['current_stock'] ?? '—';
                                
                                if ($med['current_stock'] === null) {
                                    $stockClass = 'empty';
                                } elseif ($med['current_stock'] == 0) {
                                    $stockClass = 'empty';
                                } elseif ($med['current_stock'] < 5) {
                                    $stockClass = 'low';
                                } elseif ($med['current_stock'] < 10) {
                                    $stockClass = 'medium';
                                } else {
                                    $stockClass = 'high';
                                }
                                ?>
                                <div class="stock-count <?= $stockClass ?>">
                                    <?= $stockDisplay ?>
                                </div>
                                <div class="stock-label">
                                    Current stock
                                </div>
                            </div>
                        </div>
                        
                        <div class="stock-actions">
                            <button class="btn-add-stock" data-med-id="<?= $med['id'] ?>" data-med-name="<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>">
                                Add Stock
                            </button>
                            <button class="btn-remove-stock" data-med-id="<?= $med['id'] ?>" data-med-name="<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>">
                                Remove Stock
                            </button>
                            <a href="/modules/medications/edit.php?id=<?= $med['id'] ?>" class="btn-edit-med">
                                Edit Medication
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
                        <input type="text" id="medication_name" readonly style="background: var(--color-bg-gray);">
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity to Add *</label>
                        <input type="number" name="quantity" id="quantity" min="1" required placeholder="e.g., 30">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            Enter the number of tablets/doses to add to current stock
                        </small>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStockModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">✅ Add Stock</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Remove Stock Modal -->
    <div id="removeStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➖ Remove Stock</h3>
            </div>
            <form method="POST" action="/modules/medications/remove_stock_handler.php" id="removeStockForm">
                <div class="modal-body">
                    <input type="hidden" name="medication_id" id="remove_medication_id">
                    
                    <div class="form-group">
                        <label>Medication</label>
                        <input type="text" id="remove_medication_name" readonly style="background: var(--color-bg-gray);">
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity to Remove *</label>
                        <input type="number" name="quantity" id="remove_quantity" min="1" required placeholder="e.g., 10">
                        <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                            Enter the number of tablets/doses to remove from stock
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason for Removal *</label>
                        <select name="reason" id="remove_reason" required onchange="toggleOtherReason()">
                            <option value="">Select a reason...</option>
                            <option value="Taken/Used">Taken/Used</option>
                            <option value="Expired">Expired</option>
                            <option value="Lost/Damaged">Lost/Damaged</option>
                            <option value="Given away">Given away</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="other_reason_group" style="display: none;">
                        <label>Please specify *</label>
                        <input type="text" name="other_reason" id="other_reason" placeholder="Enter reason...">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRemoveStockModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">✅ Remove Stock</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners to all Add Stock buttons
        var addStockButtons = document.querySelectorAll('.btn-add-stock');
        addStockButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var medId = this.getAttribute('data-med-id');
                var medName = this.getAttribute('data-med-name');
                showAddStockModal(medId, medName);
            });
        });
        
        // Add event listeners to all Remove Stock buttons
        var removeStockButtons = document.querySelectorAll('.btn-remove-stock');
        removeStockButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var medId = this.getAttribute('data-med-id');
                var medName = this.getAttribute('data-med-name');
                showRemoveStockModal(medId, medName);
            });
        });
        
        // Cancel button handler
        var cancelBtn = document.querySelector('.cancel-stock-modal');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeAddStockModal);
        }
        
        // Close modal when clicking outside
        var addModal = document.getElementById('addStockModal');
        if (addModal) {
            addModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAddStockModal();
                }
            });
        }
        
        var removeModal = document.getElementById('removeStockModal');
        if (removeModal) {
            removeModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeRemoveStockModal();
                }
            });
        }
        
        // Check for success messages from session
        <?php if (isset($_SESSION['success'])): ?>
            showSuccessModal('<?= htmlspecialchars($_SESSION['success'], ENT_QUOTES) ?>');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            alert('<?= htmlspecialchars($_SESSION['error'], ENT_QUOTES) ?>');
            <?php unset($_SESSION['error']); ?>
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
    
    function showRemoveStockModal(medId, medName) {
        document.getElementById('remove_medication_id').value = medId;
        document.getElementById('remove_medication_name').value = medName;
        document.getElementById('remove_quantity').value = '';
        document.getElementById('remove_reason').value = '';
        document.getElementById('other_reason').value = '';
        document.getElementById('other_reason_group').style.display = 'none';
        document.getElementById('removeStockModal').classList.add('active');
    }
    
    function closeRemoveStockModal() {
        document.getElementById('removeStockModal').classList.remove('active');
    }
    
    function toggleOtherReason() {
        var reasonSelect = document.getElementById('remove_reason');
        var otherReasonGroup = document.getElementById('other_reason_group');
        var otherReasonInput = document.getElementById('other_reason');
        
        if (reasonSelect.value === 'Other') {
            otherReasonGroup.style.display = 'block';
            otherReasonInput.required = true;
        } else {
            otherReasonGroup.style.display = 'none';
            otherReasonInput.required = false;
        }
    }
    </script>
</body>
</html>
