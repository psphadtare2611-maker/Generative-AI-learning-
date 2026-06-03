<?php
/**
 * Test User Data Isolation
 * This script verifies that different users have different data in the database
 */

require_once 'db_connect.php';

echo "=== Testing User Data Isolation ===\n\n";

// Get all users
$users_sql = "SELECT id, username, full_name FROM users WHERE is_active = 1 LIMIT 5";
$users_result = $conn->query($users_sql);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

if (empty($users)) {
    echo "❌ No users found in database. Please create users first.\n";
    exit(1);
}

echo "Found " . count($users) . " user(s) in database:\n\n";

foreach ($users as $user) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "User ID: {$user['id']}\n";
    echo "Username: {$user['username']}\n";
    echo "Full Name: {$user['full_name']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Check payments
    $sql = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $payment_data = $stmt->get_result()->fetch_assoc();
    echo "  💳 Payments: {$payment_data['count']} records (Total: ₹" . number_format($payment_data['total'], 2) . ")\n";
    
    // Check passes
    $sql = "SELECT COUNT(*) as count FROM user_passes WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $pass_count = $stmt->get_result()->fetch_assoc()['count'];
    echo "  🎫 Passes: {$pass_count} pass(es)\n";
    
    // Check active passes
    $sql = "SELECT COUNT(*) as count FROM user_passes WHERE user_id = ? AND status = 'active' AND valid_until >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $active_pass_count = $stmt->get_result()->fetch_assoc()['count'];
    echo "  ✅ Active Passes: {$active_pass_count}\n";
    
    // Check trips
    $sql = "SELECT COUNT(*) as count, COALESCE(SUM(fare), 0) as total FROM trips WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $trip_data = $stmt->get_result()->fetch_assoc();
    echo "  🚌 Trips: {$trip_data['count']} trip(s) (Total Fare: ₹" . number_format($trip_data['total'], 2) . ")\n";
    
    // Check notifications
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $notification_count = $stmt->get_result()->fetch_assoc()['count'];
    echo "  🔔 Unread Notifications: {$notification_count}\n";
    
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Test Complete!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Verify data isolation
if (count($users) >= 2) {
    echo "📊 Data Isolation Check:\n";
    $different_data = false;
    
    for ($i = 0; $i < count($users) - 1; $i++) {
        for ($j = $i + 1; $j < count($users); $j++) {
            $user1_id = $users[$i]['id'];
            $user2_id = $users[$j]['id'];
            
            // Compare payment counts
            $sql = "SELECT COUNT(*) as count FROM payments WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user1_id);
            $stmt->execute();
            $user1_payments = $stmt->get_result()->fetch_assoc()['count'];
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user2_id);
            $stmt->execute();
            $user2_payments = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($user1_payments != $user2_payments) {
                $different_data = true;
                echo "  ✅ {$users[$i]['username']} and {$users[$j]['username']} have different payment counts\n";
            }
        }
    }
    
    if ($different_data) {
        echo "\n✅ SUCCESS: Users have isolated data!\n";
    } else {
        echo "\n⚠️  WARNING: All users have the same data counts. This might be expected if all users are new.\n";
    }
} else {
    echo "ℹ️  Note: Create at least 2 users to test data isolation.\n";
}

?>
