<!-- Individual Medication Item -->
<div class="med-item-compact" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: white; border-radius: 8px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <div class="med-info" style="flex: 1; display: flex; align-items: center; gap: 8px;">
        <?= renderMedicationIcon($med['icon'] ?? 'pill', $med['color'] ?? '#5b21b6', '24px', $med['secondary_color'] ?? null) ?>
        <div>
            <strong style="font-size: 15px;"><?= htmlspecialchars($med['name']) ?></strong>
            <span style="color: var(--color-text-secondary);"> • <?= htmlspecialchars(rtrim(rtrim(number_format($med['dose_amount'], 2, '.', ''), '0'), '.') . ' ' . $med['dose_unit']) ?></span>
        </div>
    </div>
    
    <div class="med-actions" style="display: flex; gap: 8px;">
        <?php if ($med['log_status'] === 'taken'): ?>
            <span class="status-taken" style="background: #10b981; color: white; padding: 8px 16px; border-radius: 6px; font-size: 14px; white-space: nowrap;">
                ✓ Taken <?= $med['taken_at'] && strtotime($med['taken_at']) ? date('H:i', strtotime($med['taken_at'])) : '' ?>
            </span>
        <?php elseif ($med['log_status'] === 'skipped'): ?>
            <span class="status-skipped" style="background: #f59e0b; color: white; padding: 8px 16px; border-radius: 6px; font-size: 14px; white-space: nowrap;">
                ⊘ Skipped<?= $med['skipped_reason'] ? ': ' . htmlspecialchars($med['skipped_reason']) : '' ?>
            </span>
        <?php else: ?>
            <?php
            // Check if this is for a linked user and if med is overdue
            $isForLinkedUser = isset($viewingLinkedUser) && $viewingLinkedUser;
            $isOverdue = strtotime($med['scheduled_date_time']) < time();
            // Check if the linked user wants to receive nudges
            $canNudge = $isForLinkedUser && $isOverdue && isset($theirPermissions) && $theirPermissions && $theirPermissions['receive_nudges'];
            ?>
            
            <button type="button" 
                    class="btn-taken" 
                    onclick="markAsTaken(<?= $med['id'] ?>, '<?= htmlspecialchars($med['scheduled_date_time']) ?>')"
                    style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; white-space: nowrap;">
                ✓ Take
            </button>
            <button type="button" 
                    class="btn-skipped" 
                    onclick="showSkipModal(<?= $med['id'] ?>, '<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($med['scheduled_date_time']) ?>')"
                    style="background: #f59e0b; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; white-space: nowrap;">
                ⊘ Skipped
            </button>
            
            <?php if ($canNudge): ?>
            <button type="button" 
                    class="btn-nudge" 
                    onclick="sendNudge(<?= $med['id'] ?>, <?= $targetUserId ?>)"
                    style="background: #8b5cf6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; white-space: nowrap;">
                👋 Nudge
            </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
