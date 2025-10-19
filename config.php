<?php
// =============================================
// JOJAM STUDIOS - CONFIGURATION FILE
// =============================================

// --- SESSION SETTINGS (Start session first, before any output) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CONNECTION SETTINGS ---
$servername = "localhost";
$username   = "root";             // default XAMPP username
$password   = "";                 // default XAMPP has no password
$database   = "jojam_studios";    // ✅ make sure this database exists in phpMyAdmin

// --- CONNECT TO DATABASE ---
$conn = new mysqli($servername, $username, $password, $database);

// --- CHECK CONNECTION ---
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper character handling
$conn->set_charset("utf8mb4");

// =============================================
// HELPER FUNCTIONS
// =============================================

// ✅ Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ✅ Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// ✅ Check if current user is an admin
function isAdmin() {
    return (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');
}

// ✅ Redirect if not an admin
function requireAdmin() {
    requireLogin(); // First check if logged in
    if (!isAdmin()) {
        header("Location: user_dashboard.php"); // redirect non-admins to user dashboard
        exit();
    }
}

// ✅ Safe SQL helper function (prevents SQL injection)
function escape($value) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($value));
}

// ✅ Format date/time nicely
function formatDate($datetime) {
    if (!$datetime) return "N/A";
    return date("F j, Y g:i A", strtotime($datetime));
}

// ✅ Calculate hours between two times
function calculateHours($start_time, $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    $diff = $end - $start;
    return round($diff / 3600, 2); // Convert seconds to hours
}
?>