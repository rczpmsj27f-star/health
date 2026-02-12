<?php
require_once "../../../app/includes/cache-buster.php";
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

// Get all categories with active/inactive counts
$categories_stmt = $pdo->query("
    SELECT 
        c.*,
        SUM(CASE WHEN o.is_active = 1 THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN o.is_active = 0 THEN 1 ELSE 0 END) as inactive_count
    FROM dropdown_categories c
    LEFT JOIN dropdown_options o ON c.id = o.category_id
    GROUP BY c.id
    ORDER BY c.category_name ASC
");
$categories = $categories_stmt->fetchAll();

// Get all options for all categories, separated by active/inactive
$all_options = [];
foreach ($categories as $category) {
    $category_id = $category['id'];
    
    // Get active options
    $active_stmt = $pdo->prepare("
        SELECT * FROM dropdown_options 
        WHERE category_id = ? AND is_active = 1
        ORDER BY display_order ASC
    ");
    $active_stmt->execute([$category_id]);
    $all_options[$category_id]['active'] = $active_stmt->fetchAll();
    
    // Get inactive options
    $inactive_stmt = $pdo->prepare("
        SELECT * FROM dropdown_options 
        WHERE category_id = ? AND is_active = 0
        ORDER BY display_order ASC
    ");
    $inactive_stmt->execute([$category_id]);
    $all_options[$category_id]['inactive'] = $inactive_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Dropdown Maintenance</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/confirm-modal.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1200px;
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
        
        /* Category sections */
        .category-section {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .category-header {
            background: var(--color-primary);
            color: white;
            padding: 20px 24px;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-header:hover {
            background: #6d28d9;
        }
        
        .category-header-left {
            flex: 1;
        }
        
        .category-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: white;
        }
        
        .category-description {
            font-size: 13px;
            margin: 0;
            color: white;
            opacity: 0.9;
        }
        
        .category-counts {
            font-size: 13px;
            color: white;
            margin-right: 12px;
        }
        
        .toggle-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
            display: inline-block;
            transform: rotate(-90deg);
        }
        
        .toggle-icon.expanded {
            transform: rotate(0deg);
        }
        
        .category-content {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        
        .category-content.expanded {
            padding: 20px 24px;
            max-height: 5000px;
        }
        
        /* Subsection headers (Active/Inactive) */
        .subsection-header {
            background: var(--color-bg-gray);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .subsection-header:hover {
            background: #e0e0e0;
        }
        
        .subsection-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .subsection-content {
            margin-bottom: 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        
        .subsection-content.expanded {
            max-height: 3000px;
        }
        
        /* Options list - full-width rows */
        .options-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .option-card {
            background: var(--color-bg-white);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 14px;
            transition: all 0.2s;
        }
        
        .option-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--color-primary);
        }
        
        .option-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .option-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .option-icon {
            font-size: 20px;
        }
        
        .option-text {
            font-size: 15px;
            font-weight: 500;
            color: var(--color-text-primary);
        }
        
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .option-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            cursor: pointer;
            transition: all 0.2s;
            background: var(--color-bg-white);
        }
        
        .btn-sm:hover {
            background: var(--color-bg-gray);
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .btn-edit:hover {
            background: #2563eb;
        }
        
        .btn-activate {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        .btn-activate:hover {
            background: #059669;
        }
        
        .btn-deactivate {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }
        
        .btn-deactivate:hover {
            background: #d97706;
        }
        
        .btn-add-option {
            background: white;
            color: var(--color-primary);
            border: 2px solid white;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-add-option:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: var(--color-text-secondary);
            font-style: italic;
            font-size: 14px;
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
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-text-primary);
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: var(--color-text-primary);
        }
        
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--color-text);
        }
        
        .form-group small {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: var(--color-text-secondary);
        }
        
        .modal-footer {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid var(--color-border);
        }
        
        .btn-cancel {
            background: var(--color-bg-gray);
            color: var(--color-text);
            border: 1px solid var(--color-border);
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-cancel:hover {
            background: var(--color-border);
        }
        
        .btn-primary {
            background: var(--color-primary);
            color: white;
            border: 1px solid var(--color-primary);
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: #6d28d9;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

<div id="main-content">
    <div class="page-content">
        <div class="page-title">
            <h2>🛠️ Dropdown Maintenance</h2>
            <p>Manage dropdown options used throughout the application</p>
        </div>
        
        <?php foreach ($categories as $category): ?>
            <div class="category-section">
                <div class="category-header" onclick="toggleCategory(<?= $category['id'] ?>)">
                    <div class="category-header-left">
                        <h3 class="category-title"><?= htmlspecialchars($category['category_name']) ?></h3>
                        <p class="category-description"><?= htmlspecialchars($category['description'] ?? '') ?></p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="category-counts">(<?= $category['active_count'] ?> active, <?= $category['inactive_count'] ?> inactive)</span>
                        <button class="btn-add-option" 
                                data-category-id="<?= $category['id'] ?>"
                                data-category-name="<?= htmlspecialchars($category['category_name'], ENT_QUOTES) ?>"
                                onclick="showAddModal(this.dataset.categoryId, this.dataset.categoryName); event.stopPropagation();">+ Add</button>
                        <span class="toggle-icon" id="cat-toggle-<?= $category['id'] ?>">▼</span>
                    </div>
                </div>
                
                <div class="category-content" id="cat-content-<?= $category['id'] ?>">
                    <!-- Active Options Subsection -->
                    <div class="subsection-header" onclick="toggleSubsection('active-<?= $category['id'] ?>')">
                        <h4 class="subsection-title">
                            <span class="toggle-icon" id="subsec-toggle-active-<?= $category['id'] ?>">▼</span>
                            Active Options (<?= count($all_options[$category['id']]['active']) ?>)
                        </h4>
                    </div>
                    <div class="subsection-content" id="subsec-content-active-<?= $category['id'] ?>">
                        <?php if (empty($all_options[$category['id']]['active'])): ?>
                            <div class="empty-state">No active options</div>
                        <?php else: ?>
                            <div class="options-grid">
                                <?php foreach ($all_options[$category['id']]['active'] as $index => $option): ?>
                                    <div class="option-card">
                                        <div class="option-card-header">
                                            <div class="option-info">
                                                <?php if (!empty($option['icon_emoji'])): ?>
                                                    <span class="option-icon"><?= htmlspecialchars($option['icon_emoji']) ?></span>
                                                <?php endif; ?>
                                                <span class="option-text"><?= htmlspecialchars($option['option_value']) ?></span>
                                            </div>
                                            <span class="badge badge-success">Active</span>
                                        </div>
                                        <div class="option-actions">
                                            <button class="btn-sm btn-edit" 
                                                    data-option='<?= htmlspecialchars(json_encode($option, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>'
                                                    data-category-name="<?= htmlspecialchars($category['category_name'], ENT_QUOTES) ?>"
                                                    onclick="editOption(JSON.parse(this.dataset.option), this.dataset.categoryName)">✏️ Edit</button>
                                            <button class="btn-sm btn-deactivate" 
                                                    data-option-id="<?= $option['id'] ?>"
                                                    data-option-text="<?= htmlspecialchars($option['option_value'], ENT_QUOTES) ?>"
                                                    onclick="toggleOption(this.dataset.optionId, 0, this.dataset.optionText)">Deactivate</button>
                                            <?php if ($index > 0): ?>
                                                <button class="btn-sm" onclick="reorderOption(<?= $option['id'] ?>, <?= $category['id'] ?>, 'up', 1)" title="Move Up">↑</button>
                                            <?php endif; ?>
                                            <?php if ($index < count($all_options[$category['id']]['active']) - 1): ?>
                                                <button class="btn-sm" onclick="reorderOption(<?= $option['id'] ?>, <?= $category['id'] ?>, 'down', 1)" title="Move Down">↓</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Inactive Options Subsection -->
                    <div class="subsection-header" onclick="toggleSubsection('inactive-<?= $category['id'] ?>')">
                        <h4 class="subsection-title">
                            <span class="toggle-icon" id="subsec-toggle-inactive-<?= $category['id'] ?>">▼</span>
                            Inactive Options (<?= count($all_options[$category['id']]['inactive']) ?>)
                        </h4>
                    </div>
                    <div class="subsection-content" id="subsec-content-inactive-<?= $category['id'] ?>">
                        <?php if (empty($all_options[$category['id']]['inactive'])): ?>
                            <div class="empty-state">No inactive options</div>
                        <?php else: ?>
                            <div class="options-grid">
                                <?php foreach ($all_options[$category['id']]['inactive'] as $index => $option): ?>
                                    <div class="option-card">
                                        <div class="option-card-header">
                                            <div class="option-info">
                                                <?php if (!empty($option['icon_emoji'])): ?>
                                                    <span class="option-icon"><?= htmlspecialchars($option['icon_emoji']) ?></span>
                                                <?php endif; ?>
                                                <span class="option-text"><?= htmlspecialchars($option['option_value']) ?></span>
                                            </div>
                                            <span class="badge badge-warning">Inactive</span>
                                        </div>
                                        <div class="option-actions">
                                            <button class="btn-sm btn-edit" 
                                                    data-option='<?= htmlspecialchars(json_encode($option, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>'
                                                    data-category-name="<?= htmlspecialchars($category['category_name'], ENT_QUOTES) ?>"
                                                    onclick="editOption(JSON.parse(this.dataset.option), this.dataset.categoryName)">✏️ Edit</button>
                                            <button class="btn-sm btn-activate" 
                                                    data-option-id="<?= $option['id'] ?>"
                                                    data-option-text="<?= htmlspecialchars($option['option_value'], ENT_QUOTES) ?>"
                                                    onclick="toggleOption(this.dataset.optionId, 1, this.dataset.optionText)">Activate</button>
                                            <?php if ($index > 0): ?>
                                                <button class="btn-sm" onclick="reorderOption(<?= $option['id'] ?>, <?= $category['id'] ?>, 'up', 0)" title="Move Up">↑</button>
                                            <?php endif; ?>
                                            <?php if ($index < count($all_options[$category['id']]['inactive']) - 1): ?>
                                                <button class="btn-sm" onclick="reorderOption(<?= $option['id'] ?>, <?= $category['id'] ?>, 'down', 0)" title="Move Down">↓</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Add/Edit Option Modal -->
    <div id="optionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Option</h3>
            </div>
            
            <form id="optionForm">
                <input type="hidden" id="option_id" name="option_id">
                <input type="hidden" id="category_id" name="category_id">
                <input type="hidden" id="action" name="action" value="add">
                
                <div class="form-group">
                    <label>Option Text *</label>
                    <input type="text" id="option_value" name="option_value" required placeholder="e.g., Take with water">
                    <small>This text is used for both display and database storage</small>
                </div>
                
                <div class="form-group">
                    <label>Icon (emoji)</label>
                    <input type="text" id="icon_emoji" name="icon_emoji" placeholder="e.g., 💧" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="0" min="0">
                    <small>Lower numbers appear first</small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle category expansion
        function toggleCategory(catId) {
            const content = document.getElementById('cat-content-' + catId);
            const toggle = document.getElementById('cat-toggle-' + catId);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                toggle.classList.remove('expanded');
            } else {
                content.classList.add('expanded');
                toggle.classList.add('expanded');
            }
        }
        
        // Toggle subsection expansion
        function toggleSubsection(subsecId) {
            const content = document.getElementById('subsec-content-' + subsecId);
            const toggle = document.getElementById('subsec-toggle-' + subsecId);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                toggle.classList.remove('expanded');
            } else {
                content.classList.add('expanded');
                toggle.classList.add('expanded');
            }
        }
        
        // Show add modal
        function showAddModal(categoryId, categoryName) {
            document.getElementById('modalTitle').textContent = 'Add Option to ' + categoryName;
            document.getElementById('action').value = 'add';
            document.getElementById('optionForm').reset();
            document.getElementById('option_id').value = '';
            document.getElementById('category_id').value = categoryId;
            document.getElementById('optionModal').classList.add('show');
        }
        
        // Show edit modal
        function editOption(option, categoryName) {
            document.getElementById('modalTitle').textContent = 'Edit Option in ' + categoryName;
            document.getElementById('action').value = 'edit';
            document.getElementById('option_id').value = option.id;
            document.getElementById('category_id').value = option.category_id;
            document.getElementById('option_value').value = option.option_value;
            document.getElementById('icon_emoji').value = option.icon_emoji || '';
            document.getElementById('display_order').value = option.display_order;
            document.getElementById('optionModal').classList.add('show');
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('optionModal').classList.remove('show');
        }
        
        // Close modal on background click
        document.getElementById('optionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Handle form submission
        document.getElementById('optionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('dropdown_maintenance_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await ConfirmModal.show({
                        title: 'Success',
                        message: result.message || 'Operation successful',
                        confirmText: 'OK',
                        cancelText: null
                    });
                    location.reload();
                } else {
                    await ConfirmModal.show({
                        title: 'Error',
                        message: result.message || 'Operation failed',
                        confirmText: 'OK',
                        cancelText: null
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                await ConfirmModal.show({
                    title: 'Error',
                    message: 'An error occurred. Please try again.',
                    confirmText: 'OK',
                    cancelText: null
                });
            }
        });
        
        // Toggle option active/inactive
        async function toggleOption(optionId, newState, optionText) {
            const action = newState ? 'activate' : 'deactivate';
            const confirmed = await ConfirmModal.show({
                title: (newState ? 'Activate' : 'Deactivate') + ' Option',
                message: 'Are you sure you want to ' + action + ' "' + optionText + '"?' + 
                         (newState ? ' It will become visible in forms.' : ' It will be hidden from forms but preserved for data integrity.'),
                confirmText: newState ? 'Activate' : 'Deactivate',
                cancelText: 'Cancel',
                danger: !newState
            });
            
            if (!confirmed) return;
            
            const formData = new FormData();
            formData.append('action', 'toggle_active');
            formData.append('option_id', optionId);
            formData.append('is_active', newState ? '1' : '0');
            
            try {
                const response = await fetch('dropdown_maintenance_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    await ConfirmModal.show({
                        title: 'Error',
                        message: result.message || 'Operation failed',
                        confirmText: 'OK',
                        cancelText: null
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                await ConfirmModal.show({
                    title: 'Error',
                    message: 'An error occurred. Please try again.',
                    confirmText: 'OK',
                    cancelText: null
                });
            }
        }
        
        // Reorder option within its active/inactive subsection
        async function reorderOption(optionId, categoryId, direction, isActive) {
            const formData = new FormData();
            formData.append('action', 'reorder');
            formData.append('option_id', optionId);
            formData.append('category_id', categoryId);
            formData.append('direction', direction);
            formData.append('is_active', isActive);
            
            try {
                const response = await fetch('dropdown_maintenance_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    await ConfirmModal.show({
                        title: 'Error',
                        message: result.message || 'Operation failed',
                        confirmText: 'OK',
                        cancelText: null
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                await ConfirmModal.show({
                    title: 'Error',
                    message: 'An error occurred. Please try again.',
                    confirmText: 'OK',
                    cancelText: null
                });
            }
        }
    </script>
</div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
