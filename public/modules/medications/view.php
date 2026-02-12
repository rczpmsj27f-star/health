<?php 
session_start();
require_once "../../../app/config/database.php";
require_once "../../../app/core/auth.php";
require_once "../../../app/core/TimeFormatter.php";
require_once "../../../app/helpers/security.php";

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$medicationId = $_GET['id'] ?? 0;
$timeFormatter = new TimeFormatter($pdo, $_SESSION['user_id']);

// Get medication details
$stmt = $pdo->prepare("SELECT * FROM medications WHERE id = ? AND user_id = ?");
$stmt->execute([$medicationId, $_SESSION['user_id']]);
$medication = $stmt->fetch();

if (!$medication) {
    $_SESSION['error_msg'] = "Medication not found";
    header("Location: /modules/medications/dashboard.php");
    exit;
}

// Get dose times
$stmt = $pdo->prepare("SELECT * FROM medication_dose_times WHERE medication_id = ? ORDER BY dose_time ASC");
$stmt->execute([$medicationId]);
$doseTimes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($medication['name']) ?> - Details</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <script src="/assets/js/splash-screen.js?v=<?= time() ?>"></script>
    <script src="/assets/js/menu.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/confirm-modal.js?v=<?= time() ?>"></script>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>

    <div id="main-content">
    <div style="max-width: 800px; margin: 0 auto; padding: 16px 16px 40px 16px;">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['error_msg']) ?>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        
        <!-- Header -->
        <div style="margin-bottom: 24px;">
            <h2 style="color: var(--color-primary); font-size: 28px; margin: 0 0 8px 0;">
                💊 <?= htmlspecialchars($medication['name']) ?>
            </h2>
            <p style="color: var(--color-text-secondary); margin: 0;">
                Added <?= date('M d, Y', strtotime($medication['created_at'])) ?>
            </p>
        </div>
        
        <!-- Medication Details Card -->
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: var(--color-primary); font-size: 18px; margin-bottom: 20px;">
                📋 Medication Information
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <label style="display: block; font-weight: 600; color: var(--color-text-secondary); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">
                        Medication Name
                    </label>
                    <div style="font-size: 16px; color: var(--color-text);">
                        <?= htmlspecialchars($medication['name']) ?>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; color: var(--color-text-secondary); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">
                        Instructions
                    </label>
                    <div style="font-size: 16px; color: var(--color-text);">
                        <?= htmlspecialchars($medication['instructions'] ?? 'No instructions provided') ?>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; color: var(--color-text-secondary); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">
                        Started
                    </label>
                    <div style="font-size: 16px; color: var(--color-text);">
                        <?= !empty($medication['start_date']) ? date('d M Y', strtotime($medication['start_date'])) : 'Not specified' ?>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; color: var(--color-text-secondary); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">
                        End Date
                    </label>
                    <div style="font-size: 16px; color: var(--color-text);">
                        <?= !empty($medication['end_date']) ? date('M d, Y', strtotime($medication['end_date'])) : 'Not set' ?>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; color: var(--color-text-secondary); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">
                        Refill Date
                    </label>
                    <div style="font-size: 16px; color: var(--color-text);">
                        <?= !empty($medication['refill_date']) ? date('M d, Y', strtotime($medication['refill_date'])) : 'Not set' ?>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; color: var(--color-text-secondary); font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">
                        Status
                    </label>
                    <div style="font-size: 16px; color: var(--color-text);">
                        <?php $status = $medication['status'] ?? 'active'; ?>
                        <span style="background: <?= $status === 'active' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $status === 'active' ? '#16a34a' : '#dc2626' ?>; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 600;">
                            <?= ucfirst($status) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dose Times Card -->
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: var(--color-primary); font-size: 18px; margin-bottom: 16px;">
                ⏰ Dose Times
            </h3>
            
            <?php if (empty($doseTimes)): ?>
                <p style="color: var(--color-text-secondary); margin: 0;">No dose times set</p>
            <?php else: ?>
                <div style="display: grid; gap: 12px;">
                    <?php foreach ($doseTimes as $dose): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--color-bg-light); border-radius: 6px;">
                        <span style="font-size: 16px; font-weight: 600;">
                            <?= $timeFormatter->formatTime($dose['dose_time']) ?>
                        </span>
                        <span style="font-size: 14px; color: var(--color-text-secondary);">
                            <?= htmlspecialchars($dose['label'] ?? 'Dose') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Notes Card -->
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: var(--color-primary); font-size: 18px; margin-bottom: 16px;">
                📝 Notes
            </h3>
            
            <?php if (empty($medication['notes'])): ?>
                <p style="color: var(--color-text-secondary); margin: 0;">No notes yet</p>
            <?php else: ?>
                <div style="padding: 16px; background: var(--color-bg-light); border-radius: 6px; border-left: 4px solid var(--color-primary);">
                    <div style="font-size: 14px; color: var(--color-text);">
                        <?= nl2br(htmlspecialchars($medication['notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons Section -->
        <div style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px;">
            <h3 style="margin-top: 0; color: var(--color-primary); font-size: 18px; margin-bottom: 16px;">
                ⚡ Actions
            </h3>
            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <a href="/modules/medications/edit.php?id=<?= $medication['id'] ?>" 
                   class="btn btn-primary" 
                   style="flex: 0 1 calc(33.333% - 8px); min-width: 100px; padding: 12px 16px; font-size: 14px; text-decoration: none; text-align: center; box-sizing: border-box;">
                    ✏️ Edit
                </a>
                <a href="/modules/reports/history.php?medication_id=<?= $medication['id'] ?>" 
                   class="btn btn-secondary" 
                   style="flex: 0 1 calc(33.333% - 8px); min-width: 100px; padding: 12px 16px; font-size: 14px; text-decoration: none; text-align: center; box-sizing: border-box;">
                    📜 History
                </a>
                <?php 
                $isArchived = !empty($medication['archived']) && $medication['archived'] == 1;
                $isPastEndDate = !empty($medication['end_date']) && strtotime($medication['end_date']) < time();
                // Show Archive button if medication is past end date OR if already archived (to allow unarchive)
                if ($isPastEndDate || $isArchived):
                ?>
                <a href="/modules/medications/archive_handler.php?id=<?= $medication['id'] ?>&action=<?= $isArchived ? 'unarchive' : 'archive' ?>" 
                   class="btn btn-secondary archive-link" 
                   style="flex: 0 1 calc(33.333% - 8px); min-width: 100px; padding: 12px 16px; font-size: 14px; text-decoration: none; text-align: center; box-sizing: border-box;"
                   data-action="<?= $isArchived ? 'unarchive' : 'archive' ?>">
                    <?= $isArchived ? '📤 Unarchive' : '📦 Archive' ?>
                </a>
                <?php endif; ?>
                <!-- Delete form with CSRF protection -->
                <form id="deleteForm" method="POST" action="/modules/medications/delete_handler.php" style="flex: 0 1 calc(33.333% - 8px); min-width: 100px;">
                    <input type="hidden" name="med_id" value="<?= $medication['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <button type="submit" class="btn btn-danger delete-btn" 
                       style="width: 100%; padding: 12px 16px; font-size: 14px; text-align: center; box-sizing: border-box; background: #dc2626; cursor: pointer;">
                        🗑️ Delete
                    </button>
                </form>
                <a href="/modules/medications/dashboard.php" 
                   class="btn btn-secondary" 
                   style="flex: 0 1 calc(33.333% - 8px); min-width: 100px; padding: 12px 16px; font-size: 14px; text-decoration: none; text-align: center; box-sizing: border-box;">
                    ← Back
                </a>
            </div>
        </div>
    </div>
    
    <script>
    // Handle archive/unarchive confirmation
    document.querySelector('.archive-link')?.addEventListener('click', async function(e) {
        e.preventDefault();
        const action = this.dataset.action;
        const confirmed = await confirmAction(
            `Are you sure you want to ${action} this medication?`,
            `${action.charAt(0).toUpperCase() + action.slice(1)} Medication`
        );
        if (confirmed) {
            window.location.href = this.href;
        }
    });
    
    // Handle delete confirmation
    document.querySelector('.delete-btn')?.addEventListener('click', async function(e) {
        e.preventDefault();
        const confirmed = await ConfirmModal.show({
            title: 'Delete Medication',
            message: 'Are you sure you want to DELETE this medication? This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            danger: true
        });
        if (confirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
    </script>
    </div> <!-- #main-content -->
<?php include __DIR__ . '/../../../app/includes/footer.php'; ?>
</body>
</html>
