<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('provider');

$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
header("Location: http://localhost/estateserve/provider/dashboard.php");
exit();
?>