<?php
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/helpers/security.php";
Auth::requireAdmin();

// Validate ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Invalid user ID");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die("User not found");
}

$roles = $pdo->prepare("
    SELECT r.role_name
    FROM user_role_map m
    JOIN user_roles r ON r.id = m.role_id
    WHERE m.user_id = ?
");
$roles->execute([$id]);
$roleList = $roles->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – View User</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/confirm-modal.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div style="padding: 16px 16px 40px 16px; max-width: 800px; margin: 0 auto;">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success" style="margin-bottom: 16px;">
                <?= htmlspecialchars($_SESSION['success_msg']) ?>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-error" style="margin-bottom: 16px;">
                <?= htmlspecialchars($_SESSION['error_msg']) ?>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        
        <div class="page-card">
        <div class="page-header">
            <h2><?= htmlspecialchars($user['username'] ?? 'Unknown User') ?></h2>
            <p>User Details & Actions</p>
        </div>

        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Name</div>
            <div class="info-value"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . " " . ($user['surname'] ?? ''))) ?: 'Not set' ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Email Verified</div>
            <div class="info-value">
                <span style="color: <?= ($user['is_email_verified'] ?? 0) ? '#28a745' : '#dc3545' ?>">
                    <?= ($user['is_email_verified'] ?? 0) ? "✓ Yes" : "✗ No" ?>
                </span>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Active</div>
            <div class="info-value">
                <span style="color: <?= ($user['is_active'] ?? 1) ? '#28a745' : '#dc3545' ?>">
                    <?= ($user['is_active'] ?? 1) ? "✓ Yes" : "✗ No" ?>
                </span>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Roles</div>
            <div class="info-value"><?= empty($roleList) ? "None" : implode(", ", $roleList) ?></div>
        </div>

        <div style="margin-top: 24px;">
            <h3 style="margin-bottom: 16px; color: #333;">Admin Actions</h3>

            <a class="btn btn-info" href="/modules/admin/toggle_verify.php?id=<?= $id ?>">
                <?= ($user['is_email_verified'] ?? 0) ? "Unverify Email" : "Verify Email" ?>
            </a>

            <a class="btn btn-info" href="/modules/admin/toggle_active.php?id=<?= $id ?>">
                <?= ($user['is_active'] ?? 1) ? "Deactivate Account" : "Activate Account" ?>
            </a>

            <a class="btn btn-info" href="/modules/admin/toggle_admin.php?id=<?= $id ?>">
                <?= in_array("admin", $roleList) ? "Remove Admin" : "Make Admin" ?>
            </a>

            <!-- Force reset password form with CSRF protection -->
            <form id="forceResetForm" method="POST" action="/modules/admin/force_reset.php" style="margin-top: 12px;">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <button type="submit" class="btn btn-deny force-reset-btn" style="width: 100%; cursor: pointer;">
                    Force Password Reset
                </button>
            </form>
            
            <!-- Delete user form with CSRF protection -->
            <form id="deleteUserForm" method="POST" action="/modules/admin/delete_user.php" style="margin-top: 12px;">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <button type="submit" class="btn btn-danger delete-user-btn" style="width: 100%; cursor: pointer;">
                    🗑️ Delete User
                </button>
            </form>
        </div>

        <div class="page-footer">
            <p><a class="btn btn-primary" href="/modules/admin/users.php" style="max-width: 300px; display: inline-block;">Back to User Management</a></p>
        </div>
    </div>
    </div>
    
    <script>
    // Handle force password reset confirmation
    document.querySelector('.force-reset-btn')?.addEventListener('click', async function(e) {
        e.preventDefault();
        const confirmed = await ConfirmModal.show({
            title: 'Force Password Reset',
            message: 'Send password reset email to this user? They will receive an email with instructions to reset their password.',
            confirmText: 'Send Reset Email',
            cancelText: 'Cancel',
            danger: false
        });
        if (confirmed) {
            document.getElementById('forceResetForm').submit();
        }
    });
    
    // Handle delete user confirmation
    document.querySelector('.delete-user-btn')?.addEventListener('click', async function(e) {
        e.preventDefault();
        const confirmed = await ConfirmModal.show({
            title: 'Delete User',
            message: 'Are you sure you want to DELETE this user? This action cannot be undone and will remove all user data.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            danger: true
        });
        if (confirmed) {
            document.getElementById('deleteUserForm').submit();
        }
    });
    </script>
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
