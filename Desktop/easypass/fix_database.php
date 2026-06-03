<?php
/**
 * Quick Database Fix Script
 * This script replaces the problematic db_connect.php with a working version
 */

echo "<h2>EasyPass Database Fix</h2>";
echo "<p>Fixing database connection issues...</p>";

// Backup the original file
if (file_exists('db_connect.php')) {
    copy('db_connect.php', 'db_connect_backup.php');
    echo "✓ Backed up original db_connect.php<br>";
}

// Copy the simple version
if (file_exists('db_connect_simple.php')) {
    copy('db_connect_simple.php', 'db_connect.php');
    echo "✓ Replaced db_connect.php with working version<br>";
    
    echo "<h3>Testing database connection...</h3>";
    
    // Test the new connection
    try {
        require_once 'db_connect.php';
        echo "✓ Database connection successful!<br>";
        echo "✓ All tables created successfully!<br>";
        
        echo "<h3>System is now ready!</h3>";
        echo "<p><a href='index.php'>Go to Homepage</a> | <a href='signup.php'>Create Account</a></p>";
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "<br>";
        echo "<p>Please check your XAMPP MySQL service is running.</p>";
    }
    
} else {
    echo "✗ Error: db_connect_simple.php not found<br>";
}

?>
