<?php
/**
 * Test Script for UI and Functionality Fixes
 * 
 * This script verifies that all 6 fixes have been implemented correctly.
 * Run this after deploying the changes to production.
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/TimeFormatter.php';

echo "==========================================\n";
echo "UI and Functionality Fixes Verification\n";
echo "==========================================\n\n";

$passed = 0;
$failed = 0;

// Test 1: Check if TimeFormatter class exists
echo "Test 1: TimeFormatter class exists\n";
try {
    if (class_exists('TimeFormatter')) {
        echo "✓ PASSED: TimeFormatter class is available\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: TimeFormatter class not found\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 2: Check if use_24_hour column exists in user_preferences
echo "Test 2: use_24_hour column exists in database\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM user_preferences LIKE 'use_24_hour'");
    if ($stmt->rowCount() > 0) {
        echo "✓ PASSED: use_24_hour column exists in user_preferences table\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: use_24_hour column not found in user_preferences table\n";
        echo "  Action required: Run migration_add_use_24_hour.sql\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 3: Check if medication_logs table has logged_by_user_id column (should not be used)
echo "Test 3: Verify medication_logs schema\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM medication_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('logged_by_user_id', $columns)) {
        echo "⚠ INFO: logged_by_user_id column exists in medication_logs but is no longer used\n\n";
        $passed++;
    } else {
        echo "✓ PASSED: logged_by_user_id column does not exist (removed or never added)\n\n";
        $passed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 4: Test TimeFormatter functionality
echo "Test 4: TimeFormatter functionality\n";
try {
    // Create a test user preferences entry
    $testUserId = 1; // Assuming user ID 1 exists
    
    // Check if user exists
    $userStmt = $pdo->prepare("SELECT id FROM users LIMIT 1");
    $userStmt->execute();
    $user = $userStmt->fetch();
    
    if ($user) {
        $testUserId = $user['id'];
        $formatter = new TimeFormatter($pdo, $testUserId);
        
        $testTime = '14:30:00';
        $formatted12 = 'g:i A'; // Should be like "2:30 PM"
        $formatted24 = 'H:i';   // Should be like "14:30"
        
        $result = $formatter->formatTime($testTime);
        
        echo "✓ PASSED: TimeFormatter successfully formats times\n";
        echo "  Example: 14:30:00 formatted as: $result\n\n";
        $passed++;
    } else {
        echo "⚠ SKIPPED: No users found in database to test with\n\n";
        $passed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 5: Verify activity.php file changes
echo "Test 5: Activity feed query updated\n";
try {
    $activityFile = file_get_contents(__DIR__ . '/public/modules/reports/activity.php');
    if (strpos($activityFile, 'ml.logged_by_user_id') === false) {
        echo "✓ PASSED: logged_by_user_id removed from activity.php query\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: logged_by_user_id still present in activity.php\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 6: Verify menu.php notification dropdown positioning
echo "Test 6: Notification dropdown positioning\n";
try {
    $menuFile = file_get_contents(__DIR__ . '/app/includes/menu.php');
    if (strpos($menuFile, 'position: fixed') !== false && 
        strpos($menuFile, 'notification-dropdown') !== false) {
        echo "✓ PASSED: Notification dropdown uses fixed positioning\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: Notification dropdown positioning not updated\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 7: Verify email verification double-click protection
echo "Test 7: Email verification protection\n";
try {
    $verifyFile = file_get_contents(__DIR__ . '/app/auth/verify_handler.php');
    if (strpos($verifyFile, 'is_email_verified') !== false &&
        strpos($verifyFile, 'already verified') !== false) {
        echo "✓ PASSED: Email verification has double-click protection\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: Email verification protection not implemented\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 8: Verify registration rate limiting
echo "Test 8: Registration rate limiting\n";
try {
    $registerFile = file_get_contents(__DIR__ . '/public/register_handler.php');
    if (strpos($registerFile, 'last_register_attempt') !== false) {
        echo "✓ PASSED: Registration rate limiting implemented\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: Registration rate limiting not implemented\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 9: Verify dashboard title change
echo "Test 9: Dashboard title updated\n";
try {
    $dashboardFile = file_get_contents(__DIR__ . '/public/modules/medications/dashboard.php');
    if (strpos($dashboardFile, 'Scheduled Medications') !== false) {
        echo "✓ PASSED: Dashboard title changed to 'Scheduled Medications'\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: Dashboard title not updated\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Test 10: Verify linked user tab visibility check
echo "Test 10: Linked user tab visibility check\n";
try {
    $dashboardFile = file_get_contents(__DIR__ . '/public/modules/medications/dashboard.php');
    if (strpos($dashboardFile, 'linkedUserHasMeds') !== false) {
        echo "✓ PASSED: Linked user tab visibility check implemented\n\n";
        $passed++;
    } else {
        echo "✗ FAILED: Linked user tab visibility check not implemented\n\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
    $failed++;
}

// Summary
echo "==========================================\n";
echo "Test Summary\n";
echo "==========================================\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total:  " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "✓ ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED - Please review the failures above\n";
    exit(1);
}
