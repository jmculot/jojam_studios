<?php
/**
 * JOJAM STUDIOS - Receipt Generator (Fixed MySQLi version)
 * Generates PDF receipts for accepted reservations
 * Uses FPDF library (http://www.fpdf.org/)
 */

require_once 'config.php';
require_once 'fpdf/fpdf.php';

session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ✅ Get reservation ID safely
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservation_id <= 0) {
    die('⚠️ Invalid reservation ID.');
}

// ✅ Fetch reservation + user details
$sql = "
    SELECT r.*, u.username, u.email, u.contact_number 
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
    die('⚠️ Reservation not found!');
}

// ✅ Security: users can only view their own receipts (unless admin)
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    if ($reservation['user_id'] != $_SESSION['user_id']) {
        die('⚠️ Access denied!');
    }
} elseif (!isset($_SESSION['role']) && $reservation['user_id'] != $_SESSION['user_id']) {
    die('⚠️ Access denied!');
}

// ✅ Only generate if status is accepted
if (strtolower($reservation['status']) !== 'accepted') {
    die('⚠️ Receipt can only be generated for accepted reservations!');
}

// ✅ Start PDF Generation
$pdf = new FPDF();
$pdf->AddPage();

// --- Header ---
$pdf->SetDrawColor(0, 243, 255); // Neon cyan border
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 24);
$pdf->Cell(0, 15, 'JOJAM STUDIOS', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Band Studio Reservation Receipt', 0, 1, 'C');
$pdf->Ln(10);

// --- Reservation Details ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'RESERVATION DETAILS', 1, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 8, 'Receipt No:', 1, 0);
$pdf->Cell(0, 8, 'RES-' . str_pad($reservation['id'], 6, '0', STR_PAD_LEFT), 1, 1);

$pdf->Cell(60, 8, 'Date Issued:', 1, 0);
$pdf->Cell(0, 8, date('F d, Y'), 1, 1);

$pdf->Cell(60, 8, 'Status:', 1, 0);
$pdf->Cell(0, 8, strtoupper($reservation['status']), 1, 1);
$pdf->Ln(5);

// --- Customer Info ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'CUSTOMER INFORMATION', 1, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 8, 'Band Name:', 1, 0);
$pdf->Cell(0, 8, utf8_decode($reservation['band_name']), 1, 1);

$pdf->Cell(60, 8, 'Contact Person:', 1, 0);
$pdf->Cell(0, 8, utf8_decode($reservation['username']), 1, 1);

$pdf->Cell(60, 8, 'Email:', 1, 0);
$pdf->Cell(0, 8, $reservation['email'], 1, 1);

$pdf->Cell(60, 8, 'Contact Number:', 1, 0);
$pdf->Cell(0, 8, $reservation['contact_number'], 1, 1);
$pdf->Ln(5);

// --- Booking Info ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'BOOKING INFORMATION', 1, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(60, 8, 'Session Type:', 1, 0);
$pdf->Cell(0, 8, ucfirst($reservation['type']) . ' Session', 1, 1);

$pdf->Cell(60, 8, 'Date:', 1, 0);
$pdf->Cell(0, 8, date('F d, Y', strtotime($reservation['date'])), 1, 1);

$pdf->Cell(60, 8, 'Time:', 1, 0);
$time_display = date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time']));
$pdf->Cell(0, 8, $time_display, 1, 1);

$pdf->Cell(60, 8, 'Number of Members:', 1, 0);
$pdf->Cell(0, 8, $reservation['members'], 1, 1);

$pdf->Cell(60, 8, 'Member Roles:', 1, 0);
$pdf->Cell(0, 8, utf8_decode($reservation['roles']), 1, 1);
$pdf->Ln(5);

// --- Payment Info ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'PAYMENT INFORMATION', 1, 1, 'L');

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(60, 10, 'Total Amount:', 1, 0);
$pdf->Cell(0, 10, '₱ ' . number_format($reservation['total_price'], 2), 1, 1);

$pdf->Ln(10);

// --- Footer ---
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Thank you for choosing JOJAM STUDIOS!', 0, 1, 'C');
$pdf->Cell(0, 10, 'For inquiries, email us at contact@jojamstudios.com', 0, 1, 'C');

// ✅ Output the PDF
$pdf->Output('I', 'JOJAM_Receipt_' . $reservation['id'] . '.pdf');
exit;
?>
