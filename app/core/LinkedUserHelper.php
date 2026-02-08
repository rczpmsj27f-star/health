<?php
/**
 * LinkedUserHelper
 * Manages linked user relationships, invite codes, and permissions
 */

class LinkedUserHelper {
    private $pdo;
    
    // Number of users required to set permissions before link activation
    const REQUIRED_PERMISSIONS_COUNT = 2;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a unique 10-character invite code
     * Uses characters that avoid confusion (no 0/O, 1/I/l, etc.)
     */
    public function generateInviteCode() {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxRetries = 10;
        $attempt = 0;
        
        do {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM user_links WHERE invite_code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetch();
            
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw new Exception("Failed to generate unique invite code");
            }
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Create a link invitation
     */
    public function createInvitation($inviterId) {
        $code = $this->generateInviteCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO user_links (user_a_id, user_b_id, invite_code, invited_by, expires_at, status)
            VALUES (?, ?, ?, ?, ?, 'pending_a')
        ");
        $stmt->execute([$inviterId, null, $code, $inviterId, $expiresAt]);
        
        return [
            'link_id' => $this->pdo->lastInsertId(),
            'invite_code' => $code,
            'expires_at' => $expiresAt
        ];
    }
    
    /**
     * Find invitation by code
     */
    public function findInvitation($inviteCode) {
        $stmt = $this->pdo->prepare("
            SELECT ul.*, u.first_name as inviter_name
            FROM user_links ul
            LEFT JOIN users u ON ul.invited_by = u.id
            WHERE ul.invite_code = ? 
            AND ul.status = 'pending_a'
            AND (ul.expires_at IS NULL OR ul.expires_at > NOW())
        ");
        $stmt->execute([$inviteCode]);
        return $stmt->fetch();
    }
    
    /**
     * Accept an invite code
     */
    public function acceptInvite($userId, $inviteCode) {
        $invite = $this->findInvitation($inviteCode);
        
        if (!$invite) {
            return ['success' => false, 'error' => 'Invalid or expired invite code'];
        }
        
        if ((int)$invite['invited_by'] === (int)$userId) {
            return ['success' => false, 'error' => 'You cannot accept your own invitation'];
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE user_links 
            SET user_b_id = ?, status = 'pending_b', accepted_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $invite['id']]);
        
        return [
            'success' => true, 
            'link_id' => $invite['id'], 
            'inviter_name' => $invite['inviter_name']
        ];
    }
    
    /**
     * Get linked user for a given user
     */
    public function getLinkedUser($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                ul.*,
                CASE 
                    WHEN ul.user_a_id = ? THEN ul.user_b_id
                    ELSE ul.user_a_id
                END as linked_user_id,
                CASE 
                    WHEN ul.user_a_id = ? THEN u2.first_name
                    ELSE u1.first_name
                END as linked_user_name
            FROM user_links ul
            LEFT JOIN users u1 ON ul.user_a_id = u1.id
            LEFT JOIN users u2 ON ul.user_b_id = u2.id
            WHERE (ul.user_a_id = ? OR ul.user_b_id = ?)
            AND ul.status IN ('pending_b', 'active')
            LIMIT 1
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get pending invitations sent by user
     */
    public function getPendingInvites($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_links 
            WHERE invited_by = ? 
            AND status = 'pending_a'
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Revoke an invite
     */
    public function revokeInvite($linkId, $userId) {
        $stmt = $this->pdo->prepare("
            UPDATE user_links 
            SET status = 'revoked' 
            WHERE id = ? AND invited_by = ?
        ");
        return $stmt->execute([$linkId, $userId]);
    }
    
    /**
     * Save permissions
     */
    public function savePermissions($linkId, $userId, $permissions) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_link_permissions (
                link_id, user_id, can_view_medications, can_view_schedule, 
                can_mark_taken, can_add_medications, can_edit_medications, 
                can_delete_medications, notify_on_medication_taken,
                notify_on_overdue, receive_nudges
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                can_view_medications = VALUES(can_view_medications),
                can_view_schedule = VALUES(can_view_schedule),
                can_mark_taken = VALUES(can_mark_taken),
                can_add_medications = VALUES(can_add_medications),
                can_edit_medications = VALUES(can_edit_medications),
                can_delete_medications = VALUES(can_delete_medications),
                notify_on_medication_taken = VALUES(notify_on_medication_taken),
                notify_on_overdue = VALUES(notify_on_overdue),
                receive_nudges = VALUES(receive_nudges)
        ");
        
        return $stmt->execute([
            $linkId, $userId,
            $permissions['can_view_medications'] ?? 0,
            $permissions['can_view_schedule'] ?? 0,
            $permissions['can_mark_taken'] ?? 0,
            $permissions['can_add_medications'] ?? 0,
            $permissions['can_edit_medications'] ?? 0,
            $permissions['can_delete_medications'] ?? 0,
            $permissions['notify_on_medication_taken'] ?? 0,
            $permissions['notify_on_overdue'] ?? 0,
            $permissions['receive_nudges'] ?? 1
        ]);
    }
    
    /**
     * Get permissions for a user
     */
    public function getPermissions($linkId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_link_permissions 
            WHERE link_id = ? AND user_id = ?
        ");
        $stmt->execute([$linkId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Activate link after both users set permissions
     */
    public function activateLink($linkId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM user_link_permissions WHERE link_id = ?
        ");
        $stmt->execute([$linkId]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= self::REQUIRED_PERMISSIONS_COUNT) {
            $stmt = $this->pdo->prepare("
                UPDATE user_links SET status = 'active' WHERE id = ?
            ");
            $stmt->execute([$linkId]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Unlink users
     */
    public function unlinkUsers($linkId) {
        $stmt = $this->pdo->prepare("DELETE FROM user_links WHERE id = ?");
        return $stmt->execute([$linkId]);
    }
    
    /**
     * Check if user can nudge (1 hour cooldown)
     */
    public function canNudge($fromUserId, $toUserId, $medicationId) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM nudge_history 
            WHERE from_user_id = ? 
            AND to_user_id = ? 
            AND medication_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$fromUserId, $toUserId, $medicationId]);
        
        return !$stmt->fetch();
    }
    
    /**
     * Record a nudge
     */
    public function recordNudge($fromUserId, $toUserId, $medicationId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO nudge_history (from_user_id, to_user_id, medication_id)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$fromUserId, $toUserId, $medicationId]);
    }
}
