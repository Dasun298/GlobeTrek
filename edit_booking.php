<?php
// Legacy route. All changes are handled by authenticated, CSRF-protected My Bookings forms.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php', true, 303); exit; }
header('Location: my_bookings.php#tourBookings', true, 303);
exit;
