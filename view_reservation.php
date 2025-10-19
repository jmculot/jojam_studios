<?php
/**
 * JOJAM STUDIOS - View Reservation Details
 * Shows detailed information about a specific reservation
 */

require_once 'config.php';

// Ensure user is logged in
requireLogin();

// Get reservation ID
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservation_id <= 0) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit();
}

// Fetch reservation details
$sql = "
    SELECT r.*, u.username, u.email, u.contact_number, u.band_name
    FROM reservations r 
    INNER JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    die('Reservation not found!');
}

// Security: users can only view their own reservations (unless admin)
if (!isAdmin() && $reservation['user_id'] != $_SESSION['user_id']) {
    die('Access denied!');
}

// Handle status update (Admin only)
if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $update_stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $reservation_id);
    $update_stmt->execute();
    
    header("Location: view_reservation.php?id=$reservation_id&updated=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - JOJAM STUDIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="jojam-styles.css">
</head>
<body>
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <span class="navbar-brand neon-text">JOJAM STUDIOS</span>
            <div>
                <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn btn-sm btn-neon">
                    ← Back to Dashboard
                </a>
                <a href="logout.php" class="btn btn-sm btn-neon">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                
                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success">Reservation status updated successfully!</div>
                <?php endif; ?>

                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="neon-text mb-0">Reservation Details</h2>
                        <span class="badge badge-<?php echo $reservation['status']; ?> fs-5">
                            <?php echo strtoupper($reservation['status']); ?>
                        </span>
                    </div>

                    <!-- Customer Information -->
                    <div class="mb-4">
                        <h5 class="neon-text mb-3">👤 Customer Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Band Name:</label>
                                <p class="fw-bold"><?php echo htmlspecialchars($reservation['band_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Contact Person:</label>
                                <p class="fw-bold"><?php echo htmlspecialchars($reservation['username']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Email:</label>
                                <p class="fw-bold"><?php echo htmlspecialchars($reservation['email']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Contact Number:</label>
                                <p class="fw-bold"><?php echo htmlspecialchars($reservation['contact_number']); ?></p>
                            </div>
                        </div>
                    </div>

                    <hr style="border-color: var(--neon-blue);">

                    <!-- Booking Information -->
                    <div class="mb-4">
                        <h5 class="neon-text mb-3">🎸 Booking Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Session Type:</label>
                                <p class="fw-bold"><?php echo ucfirst($reservation['type']); ?> Session</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Date:</label>
                                <p class="fw-bold"><?php echo date('F d, Y', strtotime($reservation['date'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Start Time:</label>
                                <p class="fw-bold"><?php echo date('h:i A', strtotime($reservation['start_time'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">End Time:</label>
                                <p class="fw-bold"><?php echo date('h:i A', strtotime($reservation['end_time'])); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Number of Members:</label>
                                <p class="fw-bold"><?php echo $reservation['members']; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Member Roles:</label>
                                <p class="fw-bold"><?php echo htmlspecialchars($reservation['roles']); ?></p>
                            </div>
                        </div>
                    </div>

                    <hr style="border-color: var(--neon-blue);">

                    <!-- Payment Information -->
                    <div class="mb-4">
                        <h5 class="neon-text mb-3">💰 Payment Information</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="text-muted-custom">Total Price:</label>
                                <h3 class="neon-text">₱<?php echo number_format($reservation['total_price'], 2); ?></h3>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Reservation ID:</label>
                                <p class="fw-bold">RES-<?php echo str_pad($reservation['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted-custom">Created:</label>
                                <p class="fw-bold"><?php echo date('F d, Y', strtotime($reservation['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Controls -->
                    <?php if (isAdmin()): ?>
                        <hr style="border-color: var(--neon-magenta);">
                        <div class="mb-3">
                            <h5 class="neon-text mb-3">⚙️ Admin Controls</h5>
                            <form method="POST" class="d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <label class="form-label">Update Status:</label>
                                    <select name="status" class="form-control">
                                        <option value="pending" <?php echo $reservation['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="accepted" <?php echo $reservation['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        <option value="declined" <?php echo $reservation['status'] == 'declined' ? 'selected' : ''; ?>>Declined</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-neon">Update Status</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex gap-2">
                        <?php if ($reservation['status'] === 'accepted'): ?>
                            <a href="generate_receipt.php?id=<?php echo $reservation['id']; ?>" class="btn btn-neon">
                                📄 Download Receipt (PDF)
                            </a>
                        <?php endif; ?>
                        <a href="calendar_view.php" class="btn btn-neon">📅 View Calendar</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>