<?php
// Never cancel through a GET URL; My Bookings uses a CSRF-protected POST form.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php', true, 303); exit; }
header('Location: my_bookings.php#tourBookings', true, 303);
exit;
