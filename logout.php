<?php
/**
 * JOJAM STUDIOS - Logout Handler
 * Destroys user session and redirects to login page
 */

// Start session
session_start();

// Destroy all session data
session_destroy();

// Clear session variables
$_SESSION = array();

// Redirect to login page
header('Location: login.php');
exit();
?>