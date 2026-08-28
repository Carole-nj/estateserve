<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('delivery');

$user_id = $_SESSION['user_id'];
$id      = (int)$_GET['id'];
$status  = $_GET['status'];
$allowed = ['in_progress', 'completed'];

if (in_array($status, $allowed)) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND provider_id = ?");
    $stmt->execute([$status, $id, $user_id]);

    $booking = $pdo->prepare("SELECT b.resident_id, s.name FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.id = ?");
    $booking->execute([$id]);
    $booking = $booking->fetch();

    $msg = "Your order for " . $booking['name'] . " is now " . str_replace('_', ' ', $status) . ".";
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")->execute([$booking['resident_id'], $msg]);
}

header("Location: http://localhost/estateserve/delivery/dashboard.php");
exit();
?>