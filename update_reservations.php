<?php
/**
 * JOJAM STUDIOS - Update Reservation Status (Fixed + Secure)
 * Handles AJAX requests to accept or decline reservations.
 */

require_once 'config.php';

// --- Ensure user is admin ---
if (!function_exists('requireAdmin')) {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied: admin only']);
        exit();
    }
} else {
    requireAdmin();
}

// --- JSON response header ---
header('Content-Type: application/json');

// --- Allow only POST requests ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// --- Sanitize and validate input ---
$reservation_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$new_status = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : '';

if ($reservation_id <= 0 || !in_array($new_status, ['accepted', 'declined'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation data']);
    exit();
}

try {
    // --- Check if reservation exists ---
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit();
    }

    // --- Prevent redundant updates ---
    if ($reservation['status'] === $new_status) {
        echo json_encode(['success' => false, 'message' => 'Reservation already ' . $new_status]);
        exit();
    }

    // --- Update reservation ---
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET status = ?, 
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $reservation_id]);

    // --- (Optional) Mark accepted reservation timestamp ---
    if ($new_status === 'accepted') {
        $conn->prepare("
            UPDATE reservations 
            SET approved_at = NOW()
            WHERE id = ?
        ")->execute([$reservation_id]);
    }

    // --- Response ---
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Reservation ' . ucfirst($new_status) . ' successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes made. Possibly already updated.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . htmlspecialchars($e->getMessage())
    ]);
}
?>
