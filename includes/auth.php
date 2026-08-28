<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: http://localhost/estateserve/login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header("Location: http://localhost/estateserve/unauthorized.php");
        exit();
    }
}

function redirectByRole() {
    switch ($_SESSION['role']) {
        case 'admin':    header("Location: http://localhost/estateserve/admin/dashboard.php");    break;
        case 'resident': header("Location: http://localhost/estateserve/resident/dashboard.php"); break;
        case 'provider': header("Location: http://localhost/estateserve/provider/dashboard.php"); break;
        case 'delivery': header("Location: http://localhost/estateserve/delivery/dashboard.php"); break;
    }
    exit();
}