<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/db.php";

// Check login
if (!isset($_SESSION['user_id']) || in_array(($_SESSION['role'] ?? 'user'), ['admin', 'staff'], true)) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_hotel'])) {
    header("Location: my_bookings.php");
    exit();
}

// CSRF protection
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['booking_message'] = "Invalid request. Please try again.";
    header("Location: my_bookings.php");
    exit();
}

// Get submitted values
$hotel_id = filter_var($_POST['hotel_id'] ?? '', FILTER_VALIDATE_INT);
$transport_id = filter_var($_POST['transport_id'] ?? '', FILTER_VALIDATE_INT);

$checkin_date = $_POST['checkin_date'] ?? '';
$checkout_date = $_POST['checkout_date'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';

// Validate date format
function validBookingDate($date) {
    if (
        !is_string($date) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    ) {
        return false;
    }

    $parts = explode('-', $date);

    return checkdate(
        (int)$parts[1],
        (int)$parts[2],
        (int)$parts[0]
    );
}

$allowed_methods = ['Cash', 'Visa Card', 'Master Card'];

if (
    !$hotel_id ||
    !$transport_id ||
    !validBookingDate($checkin_date) ||
    !validBookingDate($checkout_date) ||
    $checkin_date < date('Y-m-d') ||
    $checkout_date <= $checkin_date ||
    !in_array($payment_method, $allowed_methods, true)
) {
    $_SESSION['booking_message'] = "Please enter valid booking details.";
    header("Location: my_bookings.php");
    exit();
}

try {

    // Check hotel availability
    $stmt = $conn->prepare(
        "SELECT id FROM hotels
         WHERE id = ? AND status = 'Available'
         LIMIT 1"
    );

    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();

    $hotel_exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    // Check transport availability
    $stmt = $conn->prepare(
        "SELECT id FROM transport
         WHERE id = ? AND status = 'Available'
         LIMIT 1"
    );

    $stmt->bind_param("i", $transport_id);
    $stmt->execute();

    $transport_exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$hotel_exists || !$transport_exists) {
        $_SESSION['booking_message'] =
            "Selected hotel or transportation is unavailable.";

        header("Location: my_bookings.php");
        exit();
    }

    // Do not collect or store card details.
    // Payment must be handled by a secure payment gateway.

    $stmt = $conn->prepare(
        "INSERT INTO hotel_bookings
        (
            user_id,
            hotel_id,
            transport_id,
            checkin_date,
            checkout_date,
            payment_method,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
    );

    $stmt->bind_param(
        "iiisss",
        $user_id,
        $hotel_id,
        $transport_id,
        $checkin_date,
        $checkout_date,
        $payment_method
    );

    $stmt->execute();
    $stmt->close();

    $_SESSION['booking_message'] =
        "Hotel booking submitted successfully!";

} catch (mysqli_sql_exception $e) {

    error_log("Hotel booking error: " . $e->getMessage());

    $_SESSION['booking_message'] =
        "Booking failed. Please try again.";
}

header("Location: my_bookings.php");
exit();
?>
