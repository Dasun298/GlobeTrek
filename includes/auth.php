<?php

// Start session only if it has not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect current page location
$currentPage = str_replace(
    '\\',
    '/',
    $_SERVER['SCRIPT_NAME'] ?? ''
);

// Set correct login redirect path
if (
    strpos($currentPage, '/admin/') !== false ||
    strpos($currentPage, '/staff/') !== false
) {
    $loginPath = '../login.php';
} else {
    $loginPath = 'login.php';
}

// Check whether user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $loginPath);
    exit();
}

// Admin pages must only be accessed by admins
if (strpos($currentPage, '/admin/') !== false) {

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'admin'
    ) {
        header("Location: ../login.php");
        exit();
    }
}

// Staff pages must only be accessed by staff
if (strpos($currentPage, '/staff/') !== false) {

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'staff'
    ) {
        header("Location: ../login.php");
        exit();
    }
}

?>