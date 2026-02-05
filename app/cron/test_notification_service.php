<?php

/**
 * Test script for NotificationService
 * 
 * Usage: php test_notification_service.php
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/NotificationService.php';

echo "=== NotificationService Test ===\n\n";

try {
    $notificationService = new NotificationService();
    
    // Test 1: Get all active Player IDs
    echo "Test 1: Getting active Player IDs...\n";
    $playerIds = $notificationService->getActivePlayerIds();
    echo "Found " . count($playerIds) . " active Player IDs\n";
    if (count($playerIds) > 0) {
        echo "Player IDs: " . implode(', ', array_slice($playerIds, 0, 3)) . (count($playerIds) > 3 ? '...' : '') . "\n";
    }
    echo "\n";
    
    // Test 2: Check if a specific user has a Player ID (use user_id 1 for testing)
    echo "Test 2: Checking Player ID for user 1...\n";
    $userId = 1;
    $playerId = $notificationService->getUserPlayerId($userId);
    if ($playerId) {
        echo "User $userId has Player ID: $playerId\n";
    } else {
        echo "User $userId does not have a Player ID or notifications are disabled\n";
    }
    echo "\n";
    
    // Test 3: Verify database query
    echo "Test 3: Verifying notification settings in database...\n";
    $stmt = $pdo->prepare("
        SELECT user_id, notifications_enabled, onesignal_player_id 
        FROM user_notification_settings 
        WHERE onesignal_player_id IS NOT NULL 
        LIMIT 5
    ");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($settings) . " users with Player IDs:\n";
    foreach ($settings as $setting) {
        echo "  - User {$setting['user_id']}: ";
        echo "Notifications " . ($setting['notifications_enabled'] ? 'enabled' : 'disabled');
        echo ", Player ID: " . substr($setting['onesignal_player_id'], 0, 20) . "...\n";
    }
    echo "\n";
    
    echo "=== Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
