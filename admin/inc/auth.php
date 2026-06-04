<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_SESSION['role'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['role'] = $user['role'] ?? 'user';
}

$allowed_roles = ['admin', 'warehouse'];

if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../index.php'); 
    exit;
}