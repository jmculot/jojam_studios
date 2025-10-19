<?php
/**
 * JOJAM STUDIOS - Calendar View
 * Displays all reservations in a monthly calendar format
 */

require_once 'config.php';

// Ensure user is logged in
requireLogin();

// Get current month and year (or from URL params)
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year  = isset($_GET['year'])  ? intval($_GET['year'])  : date('Y');

// Get all reservations for the current month
try {
    $stmt = $conn->prepare("
        SELECT r.*, u.username 
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        WHERE MONTH(r.date) = ? AND YEAR(r.date) = ? 
        AND r.status != 'declined'
        ORDER BY r.date, r.start_time
    ");
    $stmt->bind_param("ii", $current_month, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservations = [];

    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    // Group reservations by date
    $calendar_data = [];
    foreach ($reservations as $res) {
        $date = $res['date'];
        if (!isset($calendar_data[$date])) {
            $calendar_data[$date] = [];
        }
        $calendar_data[$date][] = $res;
    }

} catch (Exception $e) {
    $error = 'Error loading calendar: ' . $e->getMessage();
}

// Calendar calculations
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$day_of_week = date('w', $first_day);
$month_name = date('F Y', $first_day);

// Previous and next month links
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - JOJAM STUDIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="jojam-styles.css">
    
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="glass-card mb-4 d-flex justify-content-between align-items-center">
            <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn btn-neon">
                ← Back to Dashboard
            </a>
            <h2 class="neon-text mb-0"><?php echo $month_name; ?></h2>
            <div>
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-neon me-2">←</a>
                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-neon me-2">Today</a>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-neon">→</a>
            </div>
        </div>

        <!-- Legend -->
        <div class="glass-card mb-4 d-flex gap-4">
            <div><span class="booking-item pending">Pending</span> Pending Approval</div>
            <div><span class="booking-item accepted">Accepted</span> Accepted Booking</div>
        </div>

        <!-- Calendar -->
        <div class="glass-card">
            <table class="calendar w-100">
                <thead>
                    <tr>
                        <th>Sunday</th><th>Monday</th><th>Tuesday</th>
                        <th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day = 1;
                    $today = date('Y-m-d');

                    for ($week = 0; $week < 6; $week++) {
                        echo "<tr>";
                        for ($dow = 0; $dow < 7; $dow++) {
                            if ($week == 0 && $dow < $day_of_week) {
                                echo '<td class="other-month"></td>';
                                continue;
                            }
                            if ($day > $days_in_month) {
                                echo '<td class="other-month"></td>';
                                continue;
                            }

                            $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                            $is_today = ($current_date == $today);
                            $has_bookings = isset($calendar_data[$current_date]);
                            $class = ($is_today ? 'today ' : '') . ($has_bookings ? 'has-booking' : '');

                            echo "<td class='$class'>";
                            echo "<div class='day-number'>$day</div>";

                            if ($has_bookings) {
                                foreach ($calendar_data[$current_date] as $booking) {
                                    $time = date('h:i A', strtotime($booking['start_time']));
                                    $status_class = $booking['status'];
                                    echo "<div class='booking-item $status_class' onclick='viewBooking({$booking['id']})'>";
                                    echo htmlspecialchars($time . ' - ' . $booking['band_name']);
                                    echo "</div>";
                                }
                            }
                            echo "</td>";
                            $day++;
                        }
                        echo "</tr>";
                        if ($day > $days_in_month) break;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function viewBooking(id) {
            window.location.href = 'view_reservation.php?id=' + id;
        }
    </script>
</body>
</html>
