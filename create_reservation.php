<?php
/**
 * JOJAM STUDIOS - Create Reservation Handler
 * Processes new reservation requests from users
 */

require_once 'config.php';

// Ensure user is logged in
requireLogin();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data with validation
    $members    = isset($_POST['members']) ? intval($_POST['members']) : 0;
    $roles      = isset($_POST['roles']) ? trim($_POST['roles']) : '';
    $type       = isset($_POST['type']) ? trim($_POST['type']) : '';
    $date       = isset($_POST['date']) ? trim($_POST['date']) : '';
    $start_time = isset($_POST['start_time']) ? trim($_POST['start_time']) : '';
    $end_time   = isset($_POST['end_time']) ? trim($_POST['end_time']) : '';

    try {
        // === VALIDATIONS ===
        if (empty($members) || empty($roles) || empty($type) || empty($date) || empty($start_time) || empty($end_time)) {
            $_SESSION['error'] = 'All fields are required.';
            header('Location: user_dashboard.php');
            exit();
        }

        if ($members < 1) {
            $_SESSION['error'] = 'Number of members must be at least 1.';
            header('Location: user_dashboard.php');
            exit();
        }

        // Validate date is not in the past
        $selectedDate = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selectedDate < $today) {
            $_SESSION['error'] = 'Please select a future date.';
            header('Location: user_dashboard.php');
            exit();
        }

        // Validate time range
        if ($start_time >= $end_time) {
            $_SESSION['error'] = 'End time must be after start time.';
            header('Location: user_dashboard.php');
            exit();
        }

        // === GET PRICE FOR SELECTED TYPE ===
        $stmt = $conn->prepare("SELECT price_per_hour FROM pricing WHERE type = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Invalid session type selected.';
            header('Location: user_dashboard.php');
            exit();
        }

        $row = $result->fetch_assoc();
        $price_per_hour = floatval($row['price_per_hour']);
        $stmt->close();

        // === CALCULATE DURATION & TOTAL PRICE ===
        $start = new DateTime($start_time);
        $end   = new DateTime($end_time);
        $duration = $start->diff($end);
        $hours = $duration->h + ($duration->i / 60); // convert minutes to hours

        if ($hours <= 0) {
            $_SESSION['error'] = 'Invalid time range selected.';
            header('Location: user_dashboard.php');
            exit();
        }

        $total_price = $hours * $price_per_hour;

        // === CHECK FOR OVERLAPPING RESERVATIONS ===
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS count 
            FROM reservations 
            WHERE date = ? 
              AND status != 'declined'
              AND (
                  (start_time < ? AND end_time > ?) OR
                  (start_time < ? AND end_time > ?) OR
                  (start_time >= ? AND end_time <= ?)
              )
        ");
        $stmt->bind_param("sssssss", $date, $end_time, $start_time, $end_time, $end_time, $start_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $overlap = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();

        if ($overlap > 0) {
            $_SESSION['error'] = 'This time slot is already booked. Please choose a different time.';
            header('Location: user_dashboard.php');
            exit();
        }

        // === INSERT NEW RESERVATION ===
        $stmt = $conn->prepare("
            INSERT INTO reservations 
            (user_id, band_name, date, start_time, end_time, members, roles, type, total_price, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        // ✅ FIXED: Correct parameter types — user_id (int), members (int), total_price (double)
        $stmt->bind_param(
            "issssissd",
            $_SESSION['user_id'],     // i
            $_SESSION['band_name'],   // s
            $date,                    // s
            $start_time,              // s
            $end_time,                // s
            $members,                 // i
            $roles,                   // s
            $type,                    // s
            $total_price              // d
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Reservation submitted successfully! Waiting for admin approval.';
        } else {
            $_SESSION['error'] = 'Failed to create reservation. Please try again.';
        }

        $stmt->close();
        header('Location: user_dashboard.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to create reservation: ' . $e->getMessage();
        header('Location: user_dashboard.php');
        exit();
    }

} else {
    // Redirect if accessed directly
    header('Location: user_dashboard.php');
    exit();
}
?>
