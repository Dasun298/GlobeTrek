<?php
// Legacy payment endpoint (previously stored raw card data and made unsafe SQL inserts).
// Only the booking form can create a pending booking. No real card payments are processed.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php', true, 303); exit; }
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$role = (string)($_SESSION['role'] ?? 'user');
$target = $role === 'admin' ? 'admin/dashboard.php' :
          ($role === 'staff' ? 'staff/dashboard.php' :
          ($id && $id > 0 ? 'booking.php?id=' . $id : 'my_bookings.php'));
header('Location: ' . $target, true, 303);
exit;
