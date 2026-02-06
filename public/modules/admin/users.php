<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
Auth::requireAdmin();

$search = $_GET['q'] ?? "";

if ($search) {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE email LIKE ? OR username LIKE ? OR first_name LIKE ? OR surname LIKE ?
        ORDER BY username ASC
    ");
    $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
}

$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Users</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <style>
        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 16px 16px 16px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .page-title h2 {
            margin: 0 0 8px 0;
            font-size: 28px;
            color: #333;
        }
        
        .search-form {
            max-width: 600px;
            margin: 0 auto 24px;
            display: flex;
            gap: 8px;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .search-form button {
            width: auto;
            min-width: 100px;
            padding: 12px 20px;
        }

        .user-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .user-row {
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }

        .user-row:last-child {
            border-bottom: none;
        }

        .user-row:hover {
            background-color: #f8f9fa;
        }

        .user-row-header {
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .user-info {
            flex: 1;
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .user-username {
            font-weight: 500;
            color: #333;
            min-width: 150px;
        }

        .user-last-login {
            color: #666;
            font-size: 14px;
        }

        .expand-icon {
            color: #999;
            font-size: 18px;
            transition: transform 0.2s;
        }

        .user-row.expanded .expand-icon {
            transform: rotate(90deg);
        }

        .user-actions {
            display: none;
            padding: 16px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }

        .user-row.expanded .user-actions {
            display: block;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            font-size: 14px;
            padding: 8px 16px;
        }

        .user-count {
            text-align: center;
            color: #666;
            margin-bottom: 16px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .user-username {
                min-width: auto;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/menu.php'; ?>

    <div class="page-content">
        <div class="page-title">
            <h2>User Management</h2>
            <p>Search and manage system users</p>
        </div>

        <form method="GET" class="search-form">
            <input type="text" name="q" placeholder="Search by name, email, or username..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-info" type="submit">Search</button>
        </form>

        <?php if (empty($users)): ?>
            <div class="content-card" style="text-align: center;">
                <p style="color: #666; margin: 0;">No users found.</p>
            </div>
        <?php else: ?>
            <div class="user-count">
                <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?> found
            </div>
            
            <div class="user-list">
                <?php foreach ($users as $u): ?>
                    <div class="user-row" data-user-id="<?= $u['id'] ?>">
                        <div class="user-row-header">
                            <div class="user-info">
                                <div class="user-username"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="user-last-login">
                                    Last login: <?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?>
                                </div>
                            </div>
                            <span class="expand-icon">›</span>
                        </div>
                        <div class="user-actions">
                            <div class="action-buttons">
                                <a class="btn btn-info" href="/modules/admin/view_user.php?id=<?= $u['id'] ?>">View Details</a>
                                <a class="btn btn-deny btn-reset-password" href="/modules/admin/force_reset.php?id=<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>">Reset Password</a>
                                <button class="btn btn-deny btn-delete-user" data-user-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>">Delete User</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="page-footer">
            <p><a class="btn btn-info" href="/dashboard.php" style="max-width: 300px; display: inline-block;">Back to Dashboard</a></p>
        </div>
    </div>

    <script>
        // Expandable row functionality
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.user-row-header');
            
            rows.forEach(header => {
                header.addEventListener('click', function(e) {
                    // Don't expand if clicking a link or button
                    if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
                    
                    const row = this.parentElement;
                    row.classList.toggle('expanded');
                });
            });

            // Reset password confirmation
            document.querySelectorAll('.btn-reset-password').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const username = this.dataset.username;
                    if (!confirm('Force password reset for ' + username + '?')) {
                        e.preventDefault();
                    }
                });
            });

            // Delete user confirmation and POST request
            document.querySelectorAll('.btn-delete-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const username = this.dataset.username;
                    
                    if (!confirm('Are you sure you want to delete ' + username + '? This action cannot be undone.')) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/modules/admin/delete_user.php';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id';
                    input.value = userId;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>
</body>
</html>
