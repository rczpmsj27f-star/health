<?php
/**
 * TimeFormatter - Handles time formatting based on user preferences
 * 
 * This class reads the user's time format preference (24-hour vs 12-hour)
 * and provides methods to format times and datetimes accordingly.
 */
class TimeFormatter {
    private $use24Hour;
    
    /**
     * Initialize the formatter with user's preference
     * 
     * @param PDO $pdo Database connection
     * @param int $userId User ID to get preferences for
     */
    public function __construct($pdo, $userId) {
        $stmt = $pdo->prepare("SELECT use_24_hour FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $this->use24Hour = $result ? (bool)$result['use_24_hour'] : false;
    }
    
    /**
     * Format a time string according to user preference
     * 
     * @param string $time Time string to format
     * @return string Formatted time
     */
    public function formatTime($time) {
        if ($this->use24Hour) {
            return date('H:i', strtotime($time));
        } else {
            return date('g:i A', strtotime($time));
        }
    }
    
    /**
     * Format a datetime string according to user preference
     * 
     * @param string $datetime Datetime string to format
     * @return string Formatted datetime
     */
    public function formatDateTime($datetime) {
        if ($this->use24Hour) {
            return date('M d, Y H:i', strtotime($datetime));
        } else {
            return date('M d, Y g:i A', strtotime($datetime));
        }
    }
}
