<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php', true, 303); exit; }
$role = (string)($_SESSION['role'] ?? 'user');
$target = $role === 'admin' ? 'admin/dashboard.php' :
          ($role === 'staff' ? 'staff/dashboard.php' : 'my_bookings.php');
header('Location: ' . $target, true, 303);
exit;
