<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

// Get selected category from query string
$selectedCategory = $_GET['category'] ?? 'special_instructions';

// Category definitions
$categories = [
    'special_instructions' => [
        'name' => 'Special Instructions',
        'description' => 'Manage medication instructions options'
    ],
    'skipped_reasons' => [
        'name' => 'Skipped Reasons',
        'description' => 'Manage reasons for skipping medications'
    ],
    'late_logging_reasons' => [
        'name' => 'Late Logging Reasons',
        'description' => 'Manage reasons for late logging'
    ],
    'early_logging_reasons' => [
        'name' => 'Early Logging Reasons',
        'description' => 'Manage reasons for early logging'
    ]
];

// Get category_id for selected category
$stmt = $pdo->prepare("SELECT id FROM dropdown_categories WHERE category_key = ?");
$stmt->execute([$selectedCategory]);
$categoryRow = $stmt->fetch();

if (!$categoryRow) {
    die("Error: Invalid category");
}

$categoryId = $categoryRow['id'];

// Fetch active and inactive options separately
$stmtActive = $pdo->prepare("
    SELECT * FROM dropdown_options 
    WHERE category_id = ? AND is_active = TRUE
    ORDER BY display_order ASC
");
$stmtActive->execute([$categoryId]);
$activeOptions = $stmtActive->fetchAll();

$stmtInactive = $pdo->prepare("
    SELECT * FROM dropdown_options 
    WHERE category_id = ? AND is_active = FALSE
    ORDER BY display_order ASC
");
$stmtInactive->execute([$categoryId]);
$inactiveOptions = $stmtInactive->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dropdown Maintenance – <?= htmlspecialchars($categories[$selectedCategory]['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/confirm-modal.js?v=<?= time() ?>"></script>
    <style>
        .page-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 80px 16px 40px 16px;
        }

        /* Purple header with white text */
        .dropdown-header {
            background: var(--color-primary);
            color: #ffffff;
            padding: 24px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dropdown-header-content h2 {
            color: #ffffff !important;
            margin: 0 0 8px 0;
            font-size: 24px;
        }

        .dropdown-header-content p {
            color: #ffffff !important;
            margin: 0;
            font-size: 14px;
        }

        .dropdown-header .btn {
            background: transparent;
            color: #ffffff;
            border: 2px solid #ffffff;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .dropdown-header .btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Category selector */
        .category-selector {
            margin-bottom: 24px;
        }

        .category-selector label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text-primary);
        }

        .category-selector select {
            width: 100%;
            max-width: 400px;
            padding: 10px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--color-text);
            background: var(--color-bg-white);
        }

        /* Section headers (collapsible) */
        .section-header {
            background: var(--color-bg-gray);
            padding: 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .section-header:hover {
            background: #e0e0e0;
        }

        .section-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toggle-icon {
            display: inline-block;
            transition: transform 0.2s;
        }

        .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }

        .section-content {
            margin-bottom: 24px;
        }

        .section-content.collapsed {
            display: none;
        }

        /* Option cards */
        .option-card {
            background: var(--color-bg-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 12px;
        }

        .option-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .option-text {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 500;
            color: var(--color-text-primary);
        }

        .option-emoji {
            font-size: 20px;
        }

        .option-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .option-status.active {
            background: #d4edda;
            color: #155724;
        }

        .option-status.inactive {
            background: #fff3cd;
            color: #856404;
        }

        .option-card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .option-card-actions button {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit {
            background: var(--color-info);
            color: white;
            border-color: var(--color-info);
        }

        .btn-edit:hover {
            background: var(--color-info-hover);
        }

        .btn-deactivate {
            background: var(--color-warning);
            color: #333;
            border-color: var(--color-warning);
        }

        .btn-deactivate:hover {
            background: var(--color-warning-hover);
        }

        .btn-activate {
            background: var(--color-success);
            color: white;
            border-color: var(--color-success);
        }

        .btn-activate:hover {
            background: var(--color-success-hover);
        }

        .btn-reorder {
            background: var(--color-secondary);
            color: white;
            border-color: var(--color-secondary);
        }

        .btn-reorder:hover {
            background: var(--color-secondary-hover);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--color-text-secondary);
            font-style: italic;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--color-border);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            color: var(--color-text-primary);
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: var(--color-text-primary);
        }

        .modal-body input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--color-text);
            margin-bottom: 16px;
        }

        .modal-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding-top: 12px;
            border-top: 1px solid var(--color-border);
        }

        .modal-actions button {
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-secondary {
            background: var(--color-bg-gray);
            color: var(--color-text);
            border: 1px solid var(--color-border);
        }

        .btn-secondary:hover {
            background: var(--color-border);
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
            border: 1px solid var(--color-primary);
        }

        .btn-primary:hover {
            background: var(--color-primary-hover);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>
    
    <div class="page-content">
        <!-- Purple header -->
        <div class="dropdown-header">
            <div class="dropdown-header-content">
                <h2><?= htmlspecialchars($categories[$selectedCategory]['name']) ?></h2>
                <p><?= htmlspecialchars($categories[$selectedCategory]['description']) ?></p>
            </div>
            <button class="btn" onclick="showAddModal()">+ Add New</button>
        </div>

        <!-- Category selector -->
        <div class="category-selector">
            <label for="categorySelect">Select Category:</label>
            <select name="category" id="categorySelect" onchange="window.location.href='?category='+this.value">
                <?php foreach ($categories as $key => $cat): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $key === $selectedCategory ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Active Options Section -->
        <div class="section-header" onclick="toggleSection('active')">
            <h3>
                <span class="toggle-icon" id="active-toggle">▼</span>
                Active Options (<?= count($activeOptions) ?>)
            </h3>
        </div>
        <div class="section-content" id="active-section">
            <?php if (empty($activeOptions)): ?>
                <div class="empty-state">No active options</div>
            <?php else: ?>
                <?php foreach ($activeOptions as $index => $option): ?>
                    <div class="option-card">
                        <div class="option-card-header">
                            <div class="option-text">
                                <?php if (!empty($option['icon_emoji'])): ?>
                                    <span class="option-emoji"><?= htmlspecialchars($option['icon_emoji']) ?></span>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($option['option_value']) ?></span>
                            </div>
                            <span class="option-status active">Active</span>
                        </div>
                        <div class="option-card-actions">
                            <button class="btn-edit" onclick="editOption(<?= htmlspecialchars(json_encode($option, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)">Edit</button>
                            <button class="btn-deactivate" onclick="confirmDeactivate(<?= $option['id'] ?>, '<?= htmlspecialchars($option['option_value'], ENT_QUOTES) ?>')">Deactivate</button>
                            <?php if ($index > 0): ?>
                                <button class="btn-reorder" onclick="moveItem(<?= $option['id'] ?>, 'up')">Move Up</button>
                            <?php endif; ?>
                            <?php if ($index < count($activeOptions) - 1): ?>
                                <button class="btn-reorder" onclick="moveItem(<?= $option['id'] ?>, 'down')">Move Down</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Inactive Options Section -->
        <div class="section-header" onclick="toggleSection('inactive')">
            <h3>
                <span class="toggle-icon" id="inactive-toggle">▼</span>
                Inactive Options (<?= count($inactiveOptions) ?>)
            </h3>
        </div>
        <div class="section-content" id="inactive-section">
            <?php if (empty($inactiveOptions)): ?>
                <div class="empty-state">No inactive options</div>
            <?php else: ?>
                <?php foreach ($inactiveOptions as $index => $option): ?>
                    <div class="option-card">
                        <div class="option-card-header">
                            <div class="option-text">
                                <?php if (!empty($option['icon_emoji'])): ?>
                                    <span class="option-emoji"><?= htmlspecialchars($option['icon_emoji']) ?></span>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($option['option_value']) ?></span>
                            </div>
                            <span class="option-status inactive">Inactive</span>
                        </div>
                        <div class="option-card-actions">
                            <button class="btn-edit" onclick="editOption(<?= htmlspecialchars(json_encode($option, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)">Edit</button>
                            <button class="btn-activate" onclick="confirmActivate(<?= $option['id'] ?>, '<?= htmlspecialchars($option['option_value'], ENT_QUOTES) ?>')">Activate</button>
                            <?php if ($index > 0): ?>
                                <button class="btn-reorder" onclick="moveItem(<?= $option['id'] ?>, 'up')">Move Up</button>
                            <?php endif; ?>
                            <?php if ($index < count($inactiveOptions) - 1): ?>
                                <button class="btn-reorder" onclick="moveItem(<?= $option['id'] ?>, 'down')">Move Down</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add New Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Option</h3>
            </div>
            <form method="POST" action="dropdown_maintenance_add.php">
                <div class="modal-body">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>">
                    <label>Option Text *</label>
                    <input type="text" id="addOptionText" name="option_text" required>
                    <label>Emoji (optional)</label>
                    <input type="text" id="addEmoji" name="emoji" placeholder="💊">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Option</h3>
            </div>
            <form method="POST" action="dropdown_maintenance_edit.php">
                <div class="modal-body">
                    <input type="hidden" id="editId" name="id">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>">
                    <label>Option Text *</label>
                    <input type="text" id="editOptionText" name="option_text" required>
                    <label>Emoji (optional)</label>
                    <input type="text" id="editEmoji" name="emoji">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle section collapse/expand
        function toggleSection(section) {
            const content = document.getElementById(section + '-section');
            const toggle = document.getElementById(section + '-toggle');
            
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                toggle.classList.remove('collapsed');
            } else {
                content.classList.add('collapsed');
                toggle.classList.add('collapsed');
            }
        }

        // Show add modal
        function showAddModal() {
            document.getElementById('addModal').classList.add('show');
        }

        // Close add modal
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }

        // Show edit modal
        function editOption(option) {
            document.getElementById('editId').value = option.id;
            document.getElementById('editOptionText').value = option.option_value;
            document.getElementById('editEmoji').value = option.icon_emoji || '';
            document.getElementById('editModal').classList.add('show');
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        // Close modals on background click
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Confirm deactivate
        async function confirmDeactivate(id, text) {
            const confirmed = await ConfirmModal.show({
                title: 'Deactivate Option',
                message: `Are you sure you want to deactivate "${text}"?`,
                confirmText: 'Deactivate',
                cancelText: 'Cancel',
                danger: false
            });
            
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'dropdown_maintenance_toggle.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);
                
                const categoryInput = document.createElement('input');
                categoryInput.type = 'hidden';
                categoryInput.name = 'category';
                categoryInput.value = '<?= htmlspecialchars($selectedCategory) ?>';
                form.appendChild(categoryInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Confirm activate
        async function confirmActivate(id, text) {
            const confirmed = await ConfirmModal.show({
                title: 'Activate Option',
                message: `Are you sure you want to activate "${text}"?`,
                confirmText: 'Activate',
                cancelText: 'Cancel',
                danger: false
            });
            
            if (confirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'dropdown_maintenance_toggle.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);
                
                const categoryInput = document.createElement('input');
                categoryInput.type = 'hidden';
                categoryInput.name = 'category';
                categoryInput.value = '<?= htmlspecialchars($selectedCategory) ?>';
                form.appendChild(categoryInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Move item up or down
        function moveItem(id, direction) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'dropdown_maintenance_reorder.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);
            
            const directionInput = document.createElement('input');
            directionInput.type = 'hidden';
            directionInput.name = 'direction';
            directionInput.value = direction;
            form.appendChild(directionInput);
            
            const categoryInput = document.createElement('input');
            categoryInput.type = 'hidden';
            categoryInput.name = 'category';
            categoryInput.value = '<?= htmlspecialchars($selectedCategory) ?>';
            form.appendChild(categoryInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
