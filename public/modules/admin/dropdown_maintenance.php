<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

// Get all categories with option counts
$categories_stmt = $pdo->query("
    SELECT 
        c.*,
        COUNT(o.id) as option_count
    FROM dropdown_categories c
    LEFT JOIN dropdown_options o ON c.id = o.category_id
    GROUP BY c.id
    ORDER BY c.category_name ASC
");
$categories = $categories_stmt->fetchAll();

// Get selected category details if viewing one
$selected_category_id = $_GET['category'] ?? null;
$selected_category = null;
$options = [];

if ($selected_category_id) {
    $cat_stmt = $pdo->prepare("SELECT * FROM dropdown_categories WHERE id = ?");
    $cat_stmt->execute([$selected_category_id]);
    $selected_category = $cat_stmt->fetch();
    
    if ($selected_category) {
        $opt_stmt = $pdo->prepare("
            SELECT * FROM dropdown_options 
            WHERE category_id = ? 
            ORDER BY display_order ASC, option_label ASC
        ");
        $opt_stmt->execute([$selected_category_id]);
        $options = $opt_stmt->fetchAll();
    }
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
    <script src="/assets/js/confirm-modal.js?v=<?= time() ?>"></script>
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 16px 40px 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 24px;
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
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .category-card {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--color-text);
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .category-card.active {
            border: 2px solid var(--color-primary);
        }
        
        .category-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 8px 0;
            color: var(--color-text-primary);
        }
        
        .category-description {
            font-size: 13px;
            color: var(--color-text-secondary);
            margin: 0 0 12px 0;
        }
        
        .category-stats {
            font-size: 12px;
            color: var(--color-text-secondary);
            display: flex;
            gap: 8px;
        }
        
        .options-section {
            background: var(--color-bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: var(--color-primary);
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            margin: -24px -24px 20px -24px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        
        .section-header p {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .options-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .options-table th {
            background: var(--color-bg-gray);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--color-text-primary);
            border-bottom: 2px solid var(--color-border);
        }
        
        .options-table td {
            padding: 12px;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text);
        }
        
        .options-table tr:hover {
            background: var(--color-bg-gray);
        }
        
        .option-inactive {
            opacity: 0.5;
        }
        
        .option-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            background: none;
            border: 1px solid var(--color-border);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            background: var(--color-bg-gray);
        }
        
        .btn-add {
            background: white;
            color: var(--color-primary);
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-add:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            color: var(--color-text);
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
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>
    
    <div class="page-content">
        <div class="page-title">
            <h2>🎛️ Dropdown Maintenance</h2>
            <p>Manage dropdown options used throughout the application</p>
        </div>
        
        <?php if (!$selected_category): ?>
            <!-- Category Selection View -->
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="?category=<?= $category['id'] ?>" class="category-card">
                        <h3 class="category-name"><?= htmlspecialchars($category['category_name']) ?></h3>
                        <p class="category-description"><?= htmlspecialchars($category['description'] ?? '') ?></p>
                        <div class="category-stats">
                            <span>📋 <?= $category['option_count'] ?> options</span>
                            <span>🔑 <?= htmlspecialchars($category['category_key']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Options Management View -->
            <div style="margin-bottom: 16px;">
                <a href="?" style="color: var(--color-primary); text-decoration: none;">← Back to Categories</a>
            </div>
            
            <div class="options-section">
                <div class="section-header">
                    <div>
                        <h3 class="section-title"><?= htmlspecialchars($selected_category['category_name']) ?></h3>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--color-text-secondary);">
                            <?= htmlspecialchars($selected_category['description'] ?? '') ?>
                        </p>
                    </div>
                    <button class="btn-add" onclick="showAddModal()">+ Add Option</button>
                </div>
                
                <table class="options-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Order</th>
                            <th style="width: 60px;">Icon</th>
                            <th>Label</th>
                            <th>Value</th>
                            <th style="width: 80px;">Status</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options as $option): ?>
                            <tr class="<?= $option['is_active'] ? '' : 'option-inactive' ?>">
                                <td><?= $option['display_order'] ?></td>
                                <td><?= htmlspecialchars($option['icon_emoji'] ?? '') ?></td>
                                <td><?= htmlspecialchars($option['option_label']) ?></td>
                                <td><code><?= htmlspecialchars($option['option_value']) ?></code></td>
                                <td>
                                    <?php if ($option['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="option-actions">
                                    <button class="btn-icon" onclick="editOption(<?= htmlspecialchars(json_encode($option)) ?>)" title="Edit">✏️</button>
                                    <button class="btn-icon" onclick="toggleActive(<?= $option['id'] ?>, <?= $option['is_active'] ? 'false' : 'true' ?>)" title="<?= $option['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <?= $option['is_active'] ? '👁️' : '🚫' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($options)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--color-text-secondary);">
                                    No options yet. Click "Add Option" to create one.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add/Edit Option Modal -->
    <div id="optionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Option</h3>
            </div>
            
            <form id="optionForm">
                <input type="hidden" id="option_id" name="option_id">
                <input type="hidden" id="category_id" name="category_id" value="<?= $selected_category['id'] ?? '' ?>">
                <input type="hidden" id="action" name="action" value="add">
                
                <div class="form-group">
                    <label>Label *</label>
                    <input type="text" id="option_label" name="option_label" required placeholder="e.g., Take with water">
                </div>
                
                <div class="form-group">
                    <label>Value *</label>
                    <input type="text" id="option_value" name="option_value" required placeholder="e.g., Take with water">
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                        This is what gets stored in the database
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Icon (emoji)</label>
                    <input type="text" id="icon_emoji" name="icon_emoji" placeholder="e.g., 💧" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="0" min="0">
                    <small style="color: var(--color-text-secondary); display: block; margin-top: 4px;">
                        Lower numbers appear first
                    </small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-add">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Option';
            document.getElementById('action').value = 'add';
            document.getElementById('optionForm').reset();
            document.getElementById('option_id').value = '';
            document.getElementById('category_id').value = '<?= $selected_category['id'] ?? '' ?>';
            document.getElementById('optionModal').classList.add('show');
        }
        
        function editOption(option) {
            document.getElementById('modalTitle').textContent = 'Edit Option';
            document.getElementById('action').value = 'edit';
            document.getElementById('option_id').value = option.id;
            document.getElementById('category_id').value = option.category_id;
            document.getElementById('option_label').value = option.option_label;
            document.getElementById('option_value').value = option.option_value;
            document.getElementById('icon_emoji').value = option.icon_emoji || '';
            document.getElementById('display_order').value = option.display_order;
            document.getElementById('optionModal').classList.add('show');
        }
        
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
                    alert(result.message || 'Operation successful');
                    location.reload();
                } else {
                    alert(result.message || 'Operation failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        });
        
        async function toggleActive(optionId, newState) {
            if (!confirm('Are you sure you want to ' + (newState ? 'activate' : 'deactivate') + ' this option?')) {
                return;
            }
            
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
                    alert(result.message || 'Operation failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            }
        }
    </script>
</body>
</html>
