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
     * @return string Formatted time or empty string if invalid
     */
    public function formatTime($time) {
        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return ''; // Return empty string for invalid time
        }
        
        if ($this->use24Hour) {
            return date('H:i', $timestamp);
        } else {
            return date('g:i A', $timestamp);
        }
    }
    
    /**
     * Format a datetime string according to user preference
     * 
     * @param string $datetime Datetime string to format
     * @return string Formatted datetime or empty string if invalid
     */
    public function formatDateTime($datetime) {
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return ''; // Return empty string for invalid datetime
        }
        
        if ($this->use24Hour) {
            return date('M d, Y H:i', $timestamp);
        } else {
            return date('M d, Y g:i A', $timestamp);
        }
    }
}
