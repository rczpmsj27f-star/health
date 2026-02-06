<?php
/**
 * Time Formatting Helpers
 * Functions for formatting time based on user preferences
 */

/**
 * Get user's time format preference
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return string Time format ('12h' or '24h')
 */
function getUserTimeFormat($pdo, $userId) {
    static $cache = [];
    
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    
    $stmt = $pdo->prepare("SELECT time_format FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $format = $stmt->fetchColumn();
    
    // Default to 12h if no preference set
    $cache[$userId] = $format ?: '12h';
    return $cache[$userId];
}

/**
 * Format time according to user preference
 * @param string $time Time string (HH:MM:SS or HH:MM format)
 * @param string $format Time format ('12h' or '24h')
 * @return string Formatted time
 */
function formatTime($time, $format = '12h') {
    if (empty($time)) {
        return '';
    }
    
    // Parse the time
    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return $time; // Return original if parsing fails
    }
    
    if ($format === '24h') {
        return date('H:i', $timestamp);
    } else {
        return date('g:i A', $timestamp);
    }
}

/**
 * Format datetime according to user preference
 * @param string $datetime DateTime string
 * @param string $format Time format ('12h' or '24h')
 * @param bool $includeDate Whether to include the date
 * @return string Formatted datetime
 */
function formatDateTime($datetime, $format = '12h', $includeDate = true) {
    if (empty($datetime)) {
        return '';
    }
    
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }
    
    $dateFormat = $includeDate ? 'M j, Y ' : '';
    
    if ($format === '24h') {
        return date($dateFormat . 'H:i', $timestamp);
    } else {
        return date($dateFormat . 'g:i A', $timestamp);
    }
}

/**
 * Format time for user (convenience function with PDO and user ID)
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $time Time string
 * @return string Formatted time
 */
function formatTimeForUser($pdo, $userId, $time) {
    $format = getUserTimeFormat($pdo, $userId);
    return formatTime($time, $format);
}

/**
 * Format datetime for user (convenience function with PDO and user ID)
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $datetime DateTime string
 * @param bool $includeDate Whether to include the date
 * @return string Formatted datetime
 */
function formatDateTimeForUser($pdo, $userId, $datetime, $includeDate = true) {
    $format = getUserTimeFormat($pdo, $userId);
    return formatDateTime($datetime, $format, $includeDate);
}

/**
 * Get time input format for HTML input fields
 * @param string $format Time format ('12h' or '24h')
 * @return string HTML5 time input step attribute
 */
function getTimeInputStep($format = '24h') {
    return '60'; // 1 minute steps for both formats
}
