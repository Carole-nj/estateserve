<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('delivery');

$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
header("Location: http://localhost/estateserve/delivery/dashboard.php");
exit();
?>