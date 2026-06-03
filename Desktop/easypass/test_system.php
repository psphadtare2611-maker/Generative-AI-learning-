<?php
/**
 * System Test Script
 * This script tests if all the fixes are working properly
 */

echo "<h2>EasyPass System Test</h2>";
echo "<p>Testing system components...</p>";

// Test 1: Session handling
echo "<h3>1. Testing Session Handling</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "✓ Session started successfully<br>";
} else {
    echo "✓ Session already active<br>";
}

// Test 2: Database connection
echo "<h3>2. Testing Database Connection</h3>";
try {
    require_once 'db_connect.php';
    echo "✓ Database connection successful<br>";
    echo "✓ All tables created/verified<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Function loading
echo "<h3>3. Testing Function Loading</h3>";
try {
    require_once 'auth_check.php';
    echo "✓ Authentication functions loaded<br>";
    
    require_once 'includes/functions.php';
    echo "✓ Utility functions loaded<br>";
} catch (Exception $e) {
    echo "✗ Function loading error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if functions exist
echo "<h3>4. Testing Function Availability</h3>";
$required_functions = [
    'getCurrentUser',
    'checkUserLogin',
    'sanitizeInput',
    'validateEmail',
    'hashPassword',
    'getPassTypes'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "✓ Function '$func' is available<br>";
    } else {
        echo "✗ Function '$func' is missing<br>";
    }
}

// Test 5: Database tables
echo "<h3>5. Testing Database Tables</h3>";
$required_tables = [
    'users',
    'pass_types',
    'routes',
    'user_passes',
    'trips',
    'payments',
    'notifications',
    'system_settings',
    'login_attempts',
    'user_activity_log'
];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Table '$table' exists<br>";
    } else {
        echo "✗ Table '$table' is missing<br>";
    }
}

echo "<h3>System Test Complete!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='signup.php'>Test Signup</a> | <a href='login.php'>Test Login</a></p>";

?>
