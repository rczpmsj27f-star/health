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
            padding: 64px 12px 12px 12px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 12px;
        }
        
        .page-title h2 {
            margin: 0 0 4px 0;
            font-size: 22px;
            color: #333;
        }

        .page-title p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        
        .search-form {
            max-width: 600px;
            margin: 0 auto 12px;
            display: flex;
            gap: 6px;
        }
        
        .search-form input {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1);
        }
        
        .search-form button {
            width: auto;
            min-width: 70px;
            padding: 6px 12px !important;
            font-size: 13px !important;
        }

        .user-list {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .user-row {
            border-bottom: 1px solid #eee;
            transition: background-color 0.15s;
        }

        .user-row:last-child {
            border-bottom: none;
        }

        .user-row:hover {
            background-color: #f8f9fa;
        }

        .user-row-header {
            padding: 8px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .user-info {
            flex: 1;
            display: flex;
            gap: 16px;
            align-items: center;
            min-width: 0;
        }

        .user-username {
            font-weight: 500;
            color: #333;
            min-width: 120px;
            font-size: 14px;
        }

        .user-last-login {
            color: #666;
            font-size: 12px;
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .action-buttons .btn {
            font-size: 11px !important;
            padding: 4px 8px !important;
            border-radius: 4px !important;
            white-space: nowrap;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-buttons .btn:hover {
            transform: none !important;
        }

        .user-count {
            text-align: center;
            color: #666;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .page-footer {
            text-align: center;
            margin-top: 12px;
        }

        .page-footer .btn {
            font-size: 13px !important;
            padding: 6px 16px !important;
        }

        @media (max-width: 768px) {
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .user-username {
                min-width: auto;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .action-buttons .btn {
                width: 100%;
            }

            .user-row-header {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (min-width: 769px) {
            .user-row-header {
                min-height: 40px;
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
                                    <span aria-label="Last login">Last:</span> <?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <a class="btn btn-info" href="/modules/admin/view_user.php?id=<?= $u['id'] ?>" aria-label="View details for <?= htmlspecialchars($u['username']) ?>">View</a>
                                <a class="btn btn-deny btn-reset-password" href="/modules/admin/force_reset.php?id=<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" aria-label="Reset password for <?= htmlspecialchars($u['username']) ?>">Reset PW</a>
                                <button class="btn btn-deny btn-delete-user" data-user-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" aria-label="Delete user <?= htmlspecialchars($u['username']) ?>">Delete</button>
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
        document.addEventListener('DOMContentLoaded', function() {
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
