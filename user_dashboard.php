<?php
/**
 * JOJAM STUDIOS - User Dashboard (Updated with PDF Download)
 * Main interface for regular users to manage their reservations
 */

require_once 'config.php';

// Ensure user is logged in
requireLogin();

// If user is admin, redirect to admin dashboard
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit();
}

try {
    // Get user's reservations ordered by date
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY date DESC, start_time DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get upcoming reservations
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? AND date >= CURDATE() ORDER BY date ASC, start_time ASC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // ✅ FIXED: Safely load pricing info
    $pricing = [];
    $result = $conn->query("SELECT type, price FROM pricing");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $type = isset($row['type']) ? $row['type'] : null;
            $price = isset($row['price']) ? (float)$row['price'] : 0.0;
            if ($type !== null) {
                $pricing[$type] = $price;
            }
        }
    }

} catch (Exception $e) {
    $error = 'Error loading data: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - JOJAM STUDIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="jojam-styles.css">
    <style>
        .btn-download {
            background: transparent;
            border: 2px solid var(--neon-magenta);
            color: var(--neon-magenta);
            font-size: 0.875rem;
            padding: 5px 10px;
        }
        .btn-download:hover {
            background: var(--neon-magenta);
            color: black;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <span class="navbar-brand neon-text">JOJAM STUDIOS</span>
            <div>
                <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn btn-sm btn-neon">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="neon-text mb-4">USER DASHBOARD</h2>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="glass-card">
                    <h5 class="neon-text">Quick Actions</h5>
                    <button class="btn btn-neon me-2" data-bs-toggle="modal" data-bs-target="#newReservationModal">
                        + New Reservation
                    </button>
                    <a href="calendar_view.php" class="btn btn-neon">📅 View Calendar</a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <h3 class="neon-text"><?php echo count($upcoming); ?></h3>
                    <p>Upcoming Reservations</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <h3 class="neon-text"><?php echo count(array_filter($reservations, fn($r) => $r['status'] === 'accepted')); ?></h3>
                    <p>Accepted Bookings</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center">
                    <h3 class="neon-text"><?php echo count(array_filter($reservations, fn($r) => $r['status'] === 'pending')); ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>
        </div>

        <!-- Reservations Table -->
        <div class="glass-card">
            <h5 class="neon-text mb-3">My Reservations</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr><td colspan="6" class="text-center">No reservations yet. Create your first booking!</td></tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $res): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($res['date'])) ?></td>
                                    <td><?= date('h:i A', strtotime($res['start_time'])) . ' - ' . date('h:i A', strtotime($res['end_time'])) ?></td>
                                    <td><?= ucfirst($res['type']) ?></td>
                                    <td>₱<?= number_format($res['total_price'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $res['status'] ?>">
                                            <?= strtoupper($res['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-neon" onclick="viewDetails(<?= $res['id'] ?>)">View</button>
                                            <?php if ($res['status'] === 'accepted'): ?>
                                                <a href="generate_receipt.php?id=<?= $res['id'] ?>" 
                                                   class="btn btn-sm btn-download" 
                                                   title="Download PDF Receipt">
                                                    📄 PDF Receipt
                                                </a>
                                            <?php elseif ($res['status'] === 'pending'): ?>
                                                <span class="btn btn-sm btn-secondary disabled" title="Waiting for admin approval">
                                                    ⏳ Pending
                                                </span>
                                            <?php elseif ($res['status'] === 'declined'): ?>
                                                <span class="btn btn-sm btn-danger disabled">
                                                    ✗ Declined
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Card -->
        <div class="glass-card mt-4">
            <h5 class="neon-text mb-3">ℹ️ Important Information</h5>
            <ul style="color: #aaa;">
                <li><strong style="color: var(--neon-yellow);">Pending:</strong> Your reservation is waiting for admin approval.</li>
                <li><strong style="color: var(--neon-green);">Accepted:</strong> Your reservation is confirmed! You can download your PDF receipt.</li>
                <li><strong style="color: #ff0000;">Declined:</strong> Your reservation was not approved. Please contact us for details.</li>
            </ul>
        </div>
    </div>

    <!-- Modal for new reservation -->
    <div class="modal fade" id="newReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: rgba(20, 20, 40, 0.95); border: 1px solid var(--neon-blue); color: white;">
                <div class="modal-header">
                    <h5 class="modal-title neon-text">New Reservation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="create_reservation.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" style="color: var(--neon-blue);">Band Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['band_name']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: var(--neon-blue);">Number of Members</label>
                            <input type="number" name="members" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: var(--neon-blue);">Member Roles</label>
                            <input type="text" name="roles" class="form-control" placeholder="e.g., Lead Guitar, Drums, Vocals" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: var(--neon-blue);">Session Type</label>
                            <select name="type" class="form-control" id="sessionType" required>
                                <option value="practice">Band Practice (₱<?= number_format($pricing['practice'] ?? 0, 2); ?>/hour)</option>
                                <option value="recording">Recording Session (₱<?= number_format($pricing['recording'] ?? 0, 2); ?>/hour)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: var(--neon-blue);">Date</label>
                            <input type="date" name="date" class="form-control" min="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="color: var(--neon-blue);">Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" style="color: var(--neon-blue);">End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="alert" style="background: rgba(0, 243, 255, 0.1); border: 1px solid var(--neon-blue); color: white;">
                            <strong>Note:</strong> Final price will be calculated based on duration and session type. Your reservation will be pending until admin approval.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-neon">Submit Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetails(id) {
            window.location.href = 'view_reservation.php?id=' + id;
        }
    </script>
</body>
</html>
