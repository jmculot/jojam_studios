<?php
/**
 * JOJAM STUDIOS - Admin Dashboard (Full CRUD Control with Quick Actions)
 * Complete management for users, reservations, and pricing.
 */

require_once 'config.php';

// Ensure user is admin
requireAdmin();

// Handle Deletion (Users or Reservations)
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    header("Location: admin_dashboard.php");
    exit();
}

if (isset($_GET['delete_reservation'])) {
    $id = intval($_GET['delete_reservation']);
    $conn->query("DELETE FROM reservations WHERE id = $id");
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Status Updates (Accept/Decline)
if (isset($_GET['accept_reservation'])) {
    $id = intval($_GET['accept_reservation']);
    $conn->query("UPDATE reservations SET status = 'accepted' WHERE id = $id");
    header("Location: admin_dashboard.php?status_updated=accepted");
    exit();
}

if (isset($_GET['decline_reservation'])) {
    $id = intval($_GET['decline_reservation']);
    $conn->query("UPDATE reservations SET status = 'declined' WHERE id = $id");
    header("Location: admin_dashboard.php?status_updated=declined");
    exit();
}

// Handle Pricing Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $type = $_POST['type'];
    $price = floatval($_POST['price']);
    $stmt = $conn->prepare("UPDATE pricing SET price = ? WHERE type = ?");
    $stmt->bind_param('ds', $price, $type);
    $stmt->execute();
    header("Location: admin_dashboard.php?pricing_updated=1");
    exit();
}

// Dashboard Statistics
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$total_reservations = $conn->query("SELECT COUNT(*) AS total FROM reservations")->fetch_assoc()['total'];
$pending_reservations = $conn->query("SELECT COUNT(*) AS total FROM reservations WHERE status = 'pending'")->fetch_assoc()['total'];
$monthly_revenue = $conn->query("SELECT SUM(total_price) AS total FROM reservations WHERE status = 'accepted' AND MONTH(date)=MONTH(CURDATE())")->fetch_assoc()['total'] ?? 0;

// Fetch Lists
$users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$reservations = $conn->query("SELECT r.*, u.username, u.band_name FROM reservations r JOIN users u ON r.user_id=u.id ORDER BY r.date DESC")->fetch_all(MYSQLI_ASSOC);
$pricing = $conn->query("SELECT * FROM pricing")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JOJAM STUDIOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="jojam-styles.css">
</head>
<body>
<nav class="navbar navbar-dark">
    <div class="container-fluid">
        <span class="navbar-brand neon-text">⚡ JOJAM STUDIOS ADMIN</span>
        <div>
            <span class="text-light me-3">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="btn btn-sm btn-neon">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="neon-text mb-4">ADMIN CONTROL PANEL</h2>

    <!-- Success Messages -->
    <?php if (isset($_GET['status_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Reservation has been <?php echo htmlspecialchars($_GET['status_updated']); ?>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['pricing_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Pricing has been updated.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row text-center mb-4">
        <div class="col-md-3"><div class="glass-card"><h4><?php echo $total_users; ?></h4>Users</div></div>
        <div class="col-md-3"><div class="glass-card"><h4><?php echo $total_reservations; ?></h4>Reservations</div></div>
        <div class="col-md-3"><div class="glass-card"><h4><?php echo $pending_reservations; ?></h4>Pending</div></div>
        <div class="col-md-3"><div class="glass-card"><h4>₱<?php echo number_format($monthly_revenue,2); ?></h4>Monthly Revenue</div></div>
    </div>

    <!-- USERS MANAGEMENT -->
    <div class="glass-card">
        <h4 class="neon-text mb-3">👤 Manage Users</h4>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead><tr><th>ID</th><th>Username</th><th>Band</th><th>Email</th><th>Contact</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['band_name']) ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($u['contact_number'] ?? '-') ?></td>
                        <td>
                            <a href="?delete_user=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RESERVATION MANAGEMENT -->
    <div class="glass-card">
        <h4 class="neon-text mb-3">🎸 Manage Reservations</h4>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Band</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['band_name']) ?></td>
                        <td><?= ucfirst($r['type']) ?></td>
                        <td><?= date('M d, Y', strtotime($r['date'])) ?></td>
                        <td><?= date('h:i A', strtotime($r['start_time'])) ?> - <?= date('h:i A', strtotime($r['end_time'])) ?></td>
                        <td>
                            <span class="badge 
                                <?php if($r['status']=='pending') echo 'badge-pending';
                                elseif($r['status']=='accepted') echo 'badge-accepted';
                                else echo 'badge-declined'; ?>">
                                <?= ucfirst($r['status']); ?>
                            </span>
                        </td>
                        <td>₱<?= number_format($r['total_price'],2) ?></td>
                        <td>
                            <div class="btn-group-vertical btn-group-sm" role="group">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <a href="?accept_reservation=<?= $r['id'] ?>" 
                                       class="btn btn-accept mb-1" 
                                       onclick="return confirm('Accept this reservation?')">
                                        ✓ Accept
                                    </a>
                                    <a href="?decline_reservation=<?= $r['id'] ?>" 
                                       class="btn btn-decline mb-1" 
                                       onclick="return confirm('Decline this reservation?')">
                                        ✗ Decline
                                    </a>
                                <?php endif; ?>
                                <a href="view_reservation.php?id=<?= $r['id'] ?>" class="btn btn-neon btn-sm mb-1">View</a>
                                <?php if ($r['status'] === 'accepted'): ?>
                                    <a href="generate_receipt.php?id=<?= $r['id'] ?>" class="btn btn-neon btn-sm mb-1">Receipt</a>
                                <?php endif; ?>
                                <a href="?delete_reservation=<?= $r['id'] ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Delete this reservation?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PRICING MANAGEMENT -->
    <div class="glass-card">
        <h4 class="neon-text mb-3">💰 Manage Pricing</h4>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead><tr><th>Session Type</th><th>Current Price</th><th>Update Price</th></tr></thead>
                <tbody>
                    <?php foreach ($pricing as $p): ?>
                    <tr>
                        <form method="POST">
                            <td><?= ucfirst($p['type']) ?></td>
                            <td>₱<?= number_format($p['price'], 2) ?></td>
                            <td>
                                <div class="input-group">
                                    <input type="hidden" name="type" value="<?= $p['type'] ?>">
                                    <input type="number" step="0.01" name="price" class="form-control" placeholder="Enter new price" required>
                                    <button type="submit" name="update_price" class="btn btn-neon btn-sm">Update</button>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>