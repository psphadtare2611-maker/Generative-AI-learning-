<?php
/**
 * Enhanced Logout System with Security Cleanup
 * This file handles secure user logout with proper session cleanup
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';

// Perform secure logout
secureLogout();

// Redirect to home page
header('Location: index.php');
exit();
?>