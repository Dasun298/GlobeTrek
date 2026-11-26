<?php
require_once __DIR__ . '/_common.php';
// Old confirm_booking.php?id=... links are deliberately read-only.
// Confirm requests only through CSRF-protected forms in bookings.php.
sredirect('bookings.php');
