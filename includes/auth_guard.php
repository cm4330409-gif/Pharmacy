<?php
/**
 * AUTH GUARD
 * Add this line at the TOP of every protected page (index.php, medicines.php, etc.):
 *
 *   require_once 'includes/auth_guard.php';
 *
 * Place it BEFORE the header include.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Make user info available everywhere
$currentUser = [
    'id'   => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'] ?? 'User',
    'role' => $_SESSION['user_role'] ?? 'pharmacist',
    'username' => $_SESSION['username'] ?? '',
];
?>
