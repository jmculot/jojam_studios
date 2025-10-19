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
    
    // Get form data
    $members    = intval($_POST['members']);
    $roles      = trim($_POST['roles']);
    $type       = $_POST['type'];
    $date       = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];

    try {
        // --- Get price per hour for the selected session type ---
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

        // --- Calculate duration and total price ---
        $start = new DateTime($start_time);
        $end   = new DateTime($end_time);
        $duration = $start->diff($end);
        $hours = $duration->h + ($duration->i / 60); // convert minutes to decimal hours

        if ($hours <= 0) {
            $_SESSION['error'] = 'Invalid time range selected.';
            header('Location: user_dashboard.php');
            exit();
        }

        $total_price = $hours * $price_per_hour;

        // --- Check for overlapping reservations ---
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS count FROM reservations 
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

        // --- Insert new reservation ---
        $stmt = $conn->prepare("
            INSERT INTO reservations 
            (user_id, band_name, date, start_time, end_time, members, roles, type, total_price, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param(
            "isssssdsd",
            $_SESSION['user_id'],
            $_SESSION['band_name'],
            $date,
            $start_time,
            $end_time,
            $members,
            $roles,
            $type,
            $total_price
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
    // If accessed directly, redirect to dashboard
    header('Location: user_dashboard.php');
    exit();
}
?>
