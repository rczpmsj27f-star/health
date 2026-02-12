<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/TimeFormatter.php";
require_once "../../../app/helpers/security.php";
Auth::requireAdmin();

// Initialize TimeFormatter - admins see their own time preferences
$timeFormatter = new TimeFormatter($pdo, $_SESSION['user_id']);

$search = $_GET['q'] ?? "";
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
if ($search) {
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM users
        WHERE email LIKE ? OR username LIKE ? OR first_name LIKE ? OR surname LIKE ?
    ");
    $count_stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM users");
}
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users for current page
if ($search) {
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE email LIKE ? OR username LIKE ? OR first_name LIKE ? OR surname LIKE ?
        ORDER BY username ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(4, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(5, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(6, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY username ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
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
    <script src="/assets/js/confirm-modal.js?v=<?= time() ?>" defer></script>
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
            flex-direction: column;
            gap: 6px;
        }
        
        .search-form input {
            width: 100%;
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
            width: 100%;
            padding: 7px 12px !important;
            font-size: 13px !important;
            text-align: center;
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
            padding: 2px 8px;
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

        .user-last-login abbr {
            text-decoration: none;
            cursor: help;
        }

        .action-buttons {
            display: flex !important;
            grid-template-columns: none !important;
            gap: 4px !important;
            flex-shrink: 0;
            flex-wrap: nowrap !important;
            margin-bottom: 0 !important;
        }

        .action-buttons .btn {
            font-size: 10px !important;
            padding: 4px 8px !important;
            border-radius: 4px !important;
            white-space: nowrap;
            min-height: 24px !important;
            max-height: 24px !important;
            height: 24px !important;
            display: inline-flex !important;
            width: auto !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            align-items: center;
            justify-content: center;
            line-height: 1 !important;
            box-sizing: border-box;
        }

        .action-buttons .btn:hover {
            transform: none !important;
        }

        .btn-orange {
            background: #FF9800 !important;
            color: #ffffff !important;
        }

        .btn-orange:hover {
            background: #e68900 !important;
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
            padding: 7px 16px !important;
        }

        .pagination-controls {
            text-align: center;
            margin-top: 12px;
        }

        .pagination-controls .btn {
            font-size: 13px !important;
            padding: 7px 16px !important;
            margin: 0 4px;
        }

        @media (max-width: 768px) {
            .user-info {
                flex-direction: row;
                align-items: center;
                gap: 8px;
            }

            .user-username {
                min-width: auto;
                font-size: 13px;
            }
            
            .user-last-login {
                font-size: 11px;
            }

            .action-buttons {
                display: flex !important;
                flex-wrap: nowrap !important;
                width: auto !important;
            }

            .action-buttons .btn {
                flex: none !important;
                width: auto !important;
                padding: 3px 6px !important;
                min-height: 22px !important;
                max-height: 22px !important;
                height: 22px !important;
                font-size: 10px !important;
                margin-top: 0 !important;
            }

            .user-row-header {
                flex-direction: row;
                align-items: center;
                padding: 2px 6px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

<div id="main-content">
    <div class="page-content">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success" style="max-width: 1200px; margin: 0 auto 12px;">
                <?= htmlspecialchars($_SESSION['success_msg']) ?>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-error" style="max-width: 1200px; margin: 0 auto 12px;">
                <?= htmlspecialchars($_SESSION['error_msg']) ?>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        
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
            <div class="user-count" role="status" aria-live="polite">
                <?php
                $start = $offset + 1;
                $end = min($offset + count($users), $total_users);
                ?>
                Showing <?= $start ?>-<?= $end ?> of <?= $total_users ?> user<?= $total_users !== 1 ? 's' : '' ?>
                <?php if ($total_pages > 1): ?>
                    (Page <?= $page ?> of <?= $total_pages ?>)
                <?php endif; ?>
            </div>
            
            <div class="user-list">
                <?php foreach ($users as $u): ?>
                    <div class="user-row" data-user-id="<?= $u['id'] ?>">
                        <div class="user-row-header">
                            <div class="user-info">
                                <div class="user-username"><?= htmlspecialchars($u['username']) ?></div>
                                <div class="user-last-login">
                                    <abbr title="Last login">Last:</abbr> <?= $u['last_login'] ? $timeFormatter->formatDateTime($u['last_login']) : 'Never' ?>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <a class="btn btn-info" href="/modules/admin/view_user.php?id=<?= $u['id'] ?>" aria-label="View details for <?= htmlspecialchars($u['username']) ?>">View</a>
                                <button class="btn btn-orange btn-reset-password" data-user-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" aria-label="Reset password for <?= htmlspecialchars($u['username']) ?>">Reset PW</button>
                                <button class="btn btn-deny btn-delete-user" data-user-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" aria-label="Delete user <?= htmlspecialchars($u['username']) ?>">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <?php
                        $prev_params = ['page' => $page - 1];
                        if ($search) $prev_params['q'] = $search;
                        ?>
                        <a class="btn btn-info" href="?<?= http_build_query($prev_params) ?>" aria-label="Go to previous page (page <?= $page - 1 ?>)">Previous Page</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <?php
                        $next_params = ['page' => $page + 1];
                        if ($search) $next_params['q'] = $search;
                        ?>
                        <a class="btn btn-info" href="?<?= http_build_query($next_params) ?>" aria-label="Go to next page (page <?= $page + 1 ?>)">Next Page</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="page-footer">
            <p><a class="btn btn-primary" href="/dashboard.php" style="max-width: 300px; display: inline-block;">Back to Dashboard</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reset password confirmation and POST request
            document.querySelectorAll('.btn-reset-password').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const userId = this.dataset.userId;
                    const username = this.dataset.username;
                    
                    const confirmed = await ConfirmModal.show({
                        title: 'Force Password Reset',
                        message: 'Send password reset email to ' + username + '?',
                        confirmText: 'Send Reset Email',
                        cancelText: 'Cancel',
                        danger: false
                    });
                    
                    if (!confirmed) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/modules/admin/force_reset.php';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = userId;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = '<?= generate_csrf_token() ?>';

                    const redirectInput = document.createElement('input');
                    redirectInput.type = 'hidden';
                    redirectInput.name = 'redirect';
                    redirectInput.value = 'users';

                    form.appendChild(idInput);
                    form.appendChild(csrfInput);
                    form.appendChild(redirectInput);
                    document.body.appendChild(form);
                    form.submit();
                });
            });

            // Delete user confirmation and POST request
            document.querySelectorAll('.btn-delete-user').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const userId = this.dataset.userId;
                    const username = this.dataset.username;
                    
                    const confirmed = await ConfirmModal.show({
                        title: 'Delete User',
                        message: 'Are you sure you want to delete ' + username + '? This action cannot be undone.',
                        confirmText: 'Delete',
                        cancelText: 'Cancel',
                        danger: true
                    });
                    
                    if (!confirmed) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/modules/admin/delete_user.php';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = userId;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = '<?= generate_csrf_token() ?>';

                    form.appendChild(idInput);
                    form.appendChild(csrfInput);
                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>
</div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
