-- User links table
CREATE TABLE IF NOT EXISTS user_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_a_id INT NOT NULL,
    user_b_id INT NOT NULL,
    invite_code VARCHAR(10) UNIQUE NOT NULL,
    status ENUM('pending_a', 'pending_b', 'active', 'rejected', 'revoked') DEFAULT 'pending_a',
    invited_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_a_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_b_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id),
    INDEX idx_invite_code (invite_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User link permissions table
CREATE TABLE IF NOT EXISTS user_link_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    link_id INT NOT NULL,
    user_id INT NOT NULL,
    can_view_medications BOOLEAN DEFAULT 0,
    can_view_schedule BOOLEAN DEFAULT 0,
    can_mark_taken BOOLEAN DEFAULT 0,
    can_add_medications BOOLEAN DEFAULT 0,
    can_edit_medications BOOLEAN DEFAULT 0,
    can_delete_medications BOOLEAN DEFAULT 0,
    notify_on_medication_taken BOOLEAN DEFAULT 0,
    notify_on_overdue BOOLEAN DEFAULT 0,
    receive_nudges BOOLEAN DEFAULT 1,
    FOREIGN KEY (link_id) REFERENCES user_links(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_link_user (link_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_user_id INT NULL,
    related_medication_id INT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    in_app BOOLEAN DEFAULT 1,
    push BOOLEAN DEFAULT 1,
    email BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_type (user_id, notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nudge history table
CREATE TABLE IF NOT EXISTS nudge_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    medication_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_nudge_lookup (to_user_id, medication_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add special_time column to medication_dose_times table
ALTER TABLE medication_dose_times 
ADD COLUMN special_time VARCHAR(100) NULL AFTER dose_time;
