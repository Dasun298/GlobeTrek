
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = (string)($_SESSION['role'] ?? 'user');

if (in_array($role, ['admin', 'staff'], true)) {
    header('Location: ' . $role . '/dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$username = (string)($_SESSION['username'] ?? 'Traveller');

$today = (new DateTimeImmutable(
    'now',
    new DateTimeZone('Asia/Colombo')
))->format('Y-m-d');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function field($name) {
    return isset($_POST[$name]) && is_string($_POST[$name])
        ? trim($_POST[$name])
        : '';
}

function positiveId($value) {
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id && $id > 0 ? (int)$id : null;
}

function validTravelDate($value, $today) {
    if (
        !is_string($value) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
    ) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value
    );

    return $date !== false &&
        $date->format('Y-m-d') === $value &&
        $value >= $today;
}

function statusClass($status) {
    $status = strtolower(trim((string)$status));

    return in_array(
        $status,
        ['pending', 'confirmed', 'cancelled',
         'completed', 'paid', 'approved'],
        true
    ) ? 'status-' . $status : 'status-other';
}

function flashMessage($text, $type = 'success') {
    $_SESSION['bookings_flash'] = [
        'text' => $text,
        'type' => $type
    ];

    header('Location: my_bookings.php');
    exit;
}

function fetchOne($conn, $sql, $types, ...$values) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function canEditPackage($row) {
    $bookingPending = strtolower(
        trim((string)($row['status'] ?? ''))
    ) === 'pending';

    $payStatus = strtolower(
        trim((string)($row['payment_status'] ?? ''))
    );

    return $bookingPending &&
        in_array($payStatus, ['', 'pending'], true);
}

$packageMethods = [
    'Cash', 'Visa Card', 'Master Card', 'Bank Transfer'
];

$hotelMethods = [
    'Cash', 'Visa Card', 'Master Card'
];

/* ==================================
   PROCESS BOOKING ACTIONS
   ================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $inTransaction = false;

    try {
        if (!hash_equals(
            $_SESSION['csrf_token'],
            field('csrf_token')
        )) {
            throw new RuntimeException(
                'Invalid request. Refresh the page and try again.'
            );
        }

        $action = field('action');

        $packageAction = in_array(
            $action,
            ['update_package', 'cancel_package'],
            true
        );

        $hotelAction = in_array(
            $action,
            ['update_hotel', 'cancel_hotel'],
            true
        );

        /* PACKAGE EDIT / CANCEL */

        if ($packageAction) {

            $id = positiveId(field('booking_id'));

            if (!$id) {
                throw new RuntimeException(
                    'Invalid tour booking.'
                );
            }

            $conn->begin_transaction();
            $inTransaction = true;

            $current = fetchOne(
                $conn,
                "SELECT b.status,
                    (SELECT py.status FROM payments py
                     WHERE py.booking_id = b.id
                     ORDER BY py.id DESC LIMIT 1)
                     AS payment_status
                 FROM bookings b
                 WHERE b.id = ? AND b.user_id = ?
                 FOR UPDATE",
                'ii',
                $id,
                $userId
            );

            if (!$current || !canEditPackage($current)) {
                throw new RuntimeException(
                    'Only pending, unpaid tour bookings can be changed.'
                );
            }

            if ($action === 'update_package') {

                $newPackageId = positiveId(
                    field('package_id')
                );

                $date = field('travel_date');
                $method = field('payment_method');

                if (
                    !$newPackageId ||
                    !validTravelDate($date, $today) ||
                    !in_array($method, $packageMethods, true)
                ) {
                    throw new RuntimeException(
                        'Choose a valid package, future travel date and payment method.'
                    );
                }

                $pkg = fetchOne(
                    $conn,
                    'SELECT price FROM packages WHERE id = ? LIMIT 1',
                    'i',
                    $newPackageId
                );

                if (!$pkg || (float)$pkg['price'] <= 0) {
                    throw new RuntimeException(
                        'The selected package is unavailable.'
                    );
                }

                $stmt = $conn->prepare(
                    "UPDATE bookings
                     SET package_id = ?,
                         travel_date = ?,
                         payment_method = ?
                     WHERE id = ?
                       AND user_id = ?
                       AND status = 'Pending'"
                );

                $stmt->bind_param(
                    'issii',
                    $newPackageId,
                    $date,
                    $method,
                    $id,
                    $userId
                );

                $stmt->execute();
                $stmt->close();

                // Update only pending payment amounts
                $amount = (float)$pkg['price'];

                $stmt = $conn->prepare(
                    "UPDATE payments
                     SET amount = ?
                     WHERE booking_id = ?
                       AND status = 'Pending'"
                );

                $stmt->bind_param(
                    'di',
                    $amount,
                    $id
                );

                $stmt->execute();
                $stmt->close();

                $success = 'Your tour booking was updated.';

            } else {

                // Keep the booking history
                $stmt = $conn->prepare(
                    "UPDATE bookings
                     SET status = 'Cancelled'
                     WHERE id = ?
                       AND user_id = ?
                       AND status = 'Pending'"
                );

                $stmt->bind_param('ii', $id, $userId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare(
                    "UPDATE payments
                     SET status = 'Cancelled'
                     WHERE booking_id = ?
                       AND status = 'Pending'"
                );

                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                $success = 'Your tour booking was cancelled.';
            }

            $conn->commit();
            $inTransaction = false;

            flashMessage($success);
        }

        /* HOTEL EDIT / CANCEL */

        if ($hotelAction) {

            $id = positiveId(
                field('hotel_booking_id')
            );

            if (!$id) {
                throw new RuntimeException(
                    'Invalid hotel booking.'
                );
            }

            $current = fetchOne(
                $conn,
                'SELECT status
                 FROM hotel_bookings
                 WHERE id = ? AND user_id = ?
                 LIMIT 1',
                'ii',
                $id,
                $userId
            );

            if (
                !$current ||
                strtolower((string)$current['status']) !== 'pending'
            ) {
                throw new RuntimeException(
                    'Only pending hotel bookings can be changed.'
                );
            }

            if ($action === 'update_hotel') {

                $hotelId = positiveId(field('hotel_id'));
                $transportId = positiveId(field('transport_id'));
                $checkIn = field('checkin_date');
                $checkOut = field('checkout_date');
                $method = field('payment_method');

                if (
                    !$hotelId ||
                    !$transportId ||
                    !validTravelDate($checkIn, $today) ||
                    !validTravelDate($checkOut, $today) ||
                    $checkOut <= $checkIn ||
                    !in_array($method, $hotelMethods, true)
                ) {
                    throw new RuntimeException(
                        'Check the hotel, vehicle, dates and payment method.'
                    );
                }

                $hotelExists = fetchOne(
                    $conn,
                    "SELECT id FROM hotels
                     WHERE id = ? AND status = 'Available'",
                    'i',
                    $hotelId
                );

                $vehicleExists = fetchOne(
                    $conn,
                    "SELECT id FROM transport
                     WHERE id = ? AND status = 'Available'",
                    'i',
                    $transportId
                );

                if (!$hotelExists || !$vehicleExists) {
                    throw new RuntimeException(
                        'The chosen hotel or vehicle is no longer available.'
                    );
                }

                $stmt = $conn->prepare(
                    "UPDATE hotel_bookings
                     SET hotel_id = ?,
                         transport_id = ?,
                         checkin_date = ?,
                         checkout_date = ?,
                         payment_method = ?
                     WHERE id = ?
                       AND user_id = ?
                       AND status = 'Pending'"
                );

                $stmt->bind_param(
                    'iisssii',
                    $hotelId,
                    $transportId,
                    $checkIn,
                    $checkOut,
                    $method,
                    $id,
                    $userId
                );

                $stmt->execute();
                $stmt->close();

                flashMessage('Your hotel booking was updated.');
            }

            $stmt = $conn->prepare(
                "UPDATE hotel_bookings
                 SET status = 'Cancelled'
                 WHERE id = ?
                   AND user_id = ?
                   AND status = 'Pending'"
            );

            $stmt->bind_param('ii', $id, $userId);
            $stmt->execute();
            $stmt->close();

            flashMessage('Your hotel booking was cancelled.');
        }

        /* CREATE NEW HOTEL BOOKING */

        if ($action === 'create_hotel') {

            $hotelId = positiveId(field('hotel_id'));
            $transportId = positiveId(field('transport_id'));
            $checkIn = field('checkin_date');
            $checkOut = field('checkout_date');
            $method = field('payment_method');

            if (
                !$hotelId ||
                !$transportId ||
                !validTravelDate($checkIn, $today) ||
                !validTravelDate($checkOut, $today) ||
                $checkOut <= $checkIn ||
                !in_array($method, $hotelMethods, true)
            ) {
                throw new RuntimeException(
                    'Enter valid hotel booking details and dates.'
                );
            }

            $hotelExists = fetchOne(
                $conn,
                "SELECT id FROM hotels
                 WHERE id = ? AND status = 'Available'",
                'i',
                $hotelId
            );

            $vehicleExists = fetchOne(
                $conn,
                "SELECT id FROM transport
                 WHERE id = ? AND status = 'Available'",
                'i',
                $transportId
            );

            if (!$hotelExists || !$vehicleExists) {
                throw new RuntimeException(
                    'That hotel or vehicle is unavailable.'
                );
            }

            $stmt = $conn->prepare(
                "INSERT INTO hotel_bookings
                 (user_id, hotel_id, transport_id,
                  checkin_date, checkout_date,
                  payment_method, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
            );

            $stmt->bind_param(
                'iiisss',
                $userId,
                $hotelId,
                $transportId,
                $checkIn,
                $checkOut,
                $method
            );

            $stmt->execute();
            $stmt->close();

            flashMessage(
                'Your hotel booking request was submitted.'
            );
        }

        throw new RuntimeException(
            'Unknown booking action.'
        );

    } catch (Throwable $ex) {

        if ($inTransaction) {
            $conn->rollback();
        }

        if (!($ex instanceof RuntimeException)) {
            error_log(
                'My Bookings error: ' . $ex->getMessage()
            );

            flashMessage(
                'Something went wrong. Please try again.',
                'error'
            );
        }

        flashMessage($ex->getMessage(), 'error');
    }
}

/* ==================================
   DISPLAY MESSAGES
   ================================== */

$flash = $_SESSION['bookings_flash'] ?? null;
unset($_SESSION['bookings_flash']);

if (!$flash && isset($_SESSION['booking_message'])) {

    $flash = [
        'text' => (string)$_SESSION['booking_message'],
        'type' => 'success'
    ];

    unset($_SESSION['booking_message']);
}

/* ==================================
   LOAD TOUR BOOKINGS
   ================================== */

$stmt = $conn->prepare(
    "SELECT b.id, b.package_id, b.customer_name,
            b.travel_date, b.payment_method, b.status,
            p.package_name, p.destination, p.image, p.price,
            (SELECT py.status FROM payments py
             WHERE py.booking_id = b.id
             ORDER BY py.id DESC LIMIT 1)
             AS payment_status
     FROM bookings b
     LEFT JOIN packages p ON p.id = b.package_id
     WHERE b.user_id = ?
     ORDER BY b.id DESC"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$tourBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ==================================
   LOAD HOTEL BOOKINGS
   ================================== */

$stmt = $conn->prepare(
    "SELECT hb.id, hb.hotel_id, hb.transport_id,
            hb.checkin_date, hb.checkout_date,
            hb.payment_method, hb.status,
            h.hotel_name, h.location, h.image,
            h.price AS hotel_price,
            t.vehicle_name, t.price AS transport_price
     FROM hotel_bookings hb
     LEFT JOIN hotels h ON h.id = hb.hotel_id
     LEFT JOIN transport t ON t.id = hb.transport_id
     WHERE hb.user_id = ?
     ORDER BY hb.id DESC"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$hotelBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* LOAD PACKAGES, HOTELS & TRANSPORT */

$packages = $conn->query(
    'SELECT id, package_name
     FROM packages ORDER BY package_name'
)->fetch_all(MYSQLI_ASSOC);

$hotels = $conn->query(
    "SELECT id, hotel_name, location, image, price
     FROM hotels
     WHERE status = 'Available'
     ORDER BY id DESC"
)->fetch_all(MYSQLI_ASSOC);

$vehicles = $conn->query(
    "SELECT id, vehicle_name, price
     FROM transport
     WHERE status = 'Available'
     ORDER BY vehicle_name"
)->fetch_all(MYSQLI_ASSOC);

/* DASHBOARD STATISTICS */

$allBookings = array_merge(
    $tourBookings,
    $hotelBookings
);

$pendingCount = 0;
$confirmedCount = 0;

foreach ($allBookings as $booking) {

    $current = strtolower(
        trim((string)($booking['status'] ?? ''))
    );

    if ($current === 'pending') {
        $pendingCount++;
    }

    if (in_array(
        $current,
        ['confirmed', 'approved'],
        true
    )) {
        $confirmedCount++;
    }
}

/* SAFE LOCAL IMAGE PATH */

function imagePath($filename, $hotel = false) {

    $filename = basename((string)$filename);

    if ($filename === '') {
        return '';
    }

    $paths = $hotel
        ? ['uploads/hotels/', 'uploads/']
        : ['uploads/'];

    foreach ($paths as $prefix) {

        if (is_file(__DIR__ . '/' . $prefix . $filename)) {
            return $prefix . rawurlencode($filename);
        }
    }

    return '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>My Bookings | GlobeTrek Adventures</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/my-bookings.css">

    <script src="js/home.js" defer></script>
</head>

<body>

<a class="skip-link" href="#dashboard">
    Skip to bookings
</a>

<!-- NAVIGATION -->
<header class="site-header">

    <div class="container navbar">

        <a href="index.php" class="logo">
            <span class="logo-symbol">✦</span>
            Globe<span>Trek</span>
            <small>ADVENTURES</small>
        </a>

        <button
            class="menu-toggle"
            type="button"
            id="menuToggle"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="Open navigation menu">
            ☰
        </button>

        <nav class="nav-links"
             id="mainNav"
             aria-label="Main navigation">

            <a href="index.php">Home</a>

            <a href="index.php#destinations">
                Destinations
            </a>

            <a href="packages.php">
                Tour Packages
            </a>

            <a href="contact.php">
                Contact
            </a>

            <a href="my_bookings.php"
               class="active"
               aria-current="page">
                My Bookings
            </a>

            <a href="update_profile.php">Profile</a>

            <a href="logout.php" class="nav-button">
                Logout
            </a>

        </nav>

    </div>

</header>

<main id="dashboard">

    <!-- HERO -->
    <section class="bookings-hero">

        <div class="container">

            <p class="hero-label">
                ✦ YOUR TRAVEL DASHBOARD
            </p>

            <h1>
                Every Journey,
                <em>One Place.</em>
            </h1>

            <p>
                Welcome back, <?= e($username) ?>.
                Manage your trip plans and hotel stays with ease.
            </p>

        </div>

    </section>

    <section class="bookings-main">

        <div class="container">

            <!-- MESSAGES -->
            <?php if ($flash): ?>

                <div
                    class="dashboard-alert <?= ($flash['type'] ?? '') === 'error'
                        ? 'alert-error' : 'alert-success' ?>"
                    role="status">

                    <?= e($flash['text'] ?? '') ?>

                </div>

            <?php endif; ?>

            <!-- DASHBOARD HEADING -->
            <div class="dashboard-intro">

                <div>

                    <p class="section-label">
                        YOUR ADVENTURES
                    </p>

                    <h2>My <em>Bookings.</em></h2>

                    <p>
                        Review, edit or cancel eligible
                        booking requests.
                    </p>

                </div>

                <a class="btn btn-primary"
                   href="packages.php">
                    Explore Packages ↗
                </a>

            </div>

            <!-- STATISTICS -->
            <div class="stat-grid">

                <div class="stat-card">
                    <span>Total Bookings</span>

                    <strong>
                        <?= count($allBookings) ?>
                    </strong>

                    <small>Tour + hotel requests</small>
                </div>

                <div class="stat-card">
                    <span>Pending</span>

                    <strong>
                        <?= $pendingCount ?>
                    </strong>

                    <small>Awaiting confirmation</small>
                </div>

                <div class="stat-card">
                    <span>Confirmed</span>

                    <strong>
                        <?= $confirmedCount ?>
                    </strong>

                    <small>Confirmed reservations</small>
                </div>

            </div>

            <!-- SECTION LINKS -->
            <nav class="dashboard-tabs"
                 aria-label="Booking sections">

                <a href="#tourBookings">
                    ✈ Tour Bookings
                    <span><?= count($tourBookings) ?></span>
                </a>

                <a href="#hotelBookings">
                    ⌂ Hotel Bookings
                    <span><?= count($hotelBookings) ?></span>
                </a>

                <a href="#availableHotels">
                    ＋ Book a Hotel
                </a>

            </nav>

            <!-- TOUR BOOKINGS -->
            <section class="dashboard-section"
                     id="tourBookings">

                <div class="dashboard-section-head">

                    <div>
                        <p class="section-label">
                            YOUR TRAVEL PLANS
                        </p>

                        <h2>Tour <em>Bookings.</em></h2>
                    </div>

                    <a href="packages.php" class="text-link">
                        Browse packages ↗
                    </a>

                </div>

                <?php if (!$tourBookings): ?>

                    <div class="empty-state">

                        <span>✈</span>

                        <h3>No tour bookings yet</h3>

                        <p>
                            Explore our destinations and
                            find your next adventure.
                        </p>

                        <a href="packages.php"
                           class="btn btn-primary">
                            Explore Packages →
                        </a>

                    </div>

                <?php else: ?>

                    <div class="booking-card-list">

                        <?php foreach ($tourBookings as $b): ?>

                            <?php
                            $photo = imagePath($b['image'] ?? '');
                            $editable = canEditPackage($b);
                            ?>

                            <article class="booking-card">

                                <div class="booking-card-top">

                                    <div class="booking-photo">

                                        <?php if ($photo): ?>

                                            <img
                                                src="<?= e($photo) ?>"
                                                alt="<?= e($b['destination'] ?? 'Tour destination') ?>"
                                                loading="lazy">

                                        <?php else: ?>

                                            <span aria-label="No photo available">
                                                ✈
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="booking-card-info">

                                        <span class="booking-type">
                                            TOUR PACKAGE · #<?= (int)$b['id'] ?>
                                        </span>

                                        <h3>
                                            <?= e($b['package_name'] ?? 'Package no longer available') ?>
                                        </h3>

                                        <p class="booking-location">
                                            ⌖ <?= e($b['destination'] ?? 'Destination unavailable') ?>
                                        </p>

                                        <div class="status-line">

                                            <span class="status-pill <?= statusClass($b['status']) ?>">
                                                <?= e($b['status'] ?: 'Unknown') ?>
                                            </span>

                                            <span class="payment-label">
                                                Payment:
                                                <?= e($b['payment_status'] ?: 'Not recorded') ?>
                                            </span>

                                        </div>

                                    </div>

                                </div>

                                <div class="booking-data">

                                    <div>
                                        <small>Travel Date</small>
                                        <strong>
                                            <?= e($b['travel_date'] ?: 'Not set') ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <small>Payment Method</small>
                                        <strong>
                                            <?= e($b['payment_method'] ?: 'Not selected') ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <small>Current Package Price</small>
                                        <strong>
                                            <?= $b['price'] !== null
                                                ? 'Rs. ' . number_format((float)$b['price'], 2)
                                                : 'Unavailable' ?>
                                        </strong>
                                    </div>

                                </div>

                                <?php if ($editable): ?>

                                    <div class="booking-actions">

                                        <details class="edit-details">

                                            <summary>
                                                Edit Booking
                                                <span aria-hidden="true">⌄</span>
                                            </summary>

                                            <form method="post"
                                                  class="edit-form">

                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= e($_SESSION['csrf_token']) ?>">

                                                <input type="hidden"
                                                       name="action"
                                                       value="update_package">

                                                <input type="hidden"
                                                       name="booking_id"
                                                       value="<?= (int)$b['id'] ?>">

                                                <div class="field-grid">

                                                    <label>
                                                        Travel date

                                                        <input
                                                            type="date"
                                                            name="travel_date"
                                                            min="<?= e($today) ?>"
                                                            value="<?= e($b['travel_date']) ?>"
                                                            required>
                                                    </label>

                                                    <label>
                                                        Payment method

                                                        <select name="payment_method"
                                                                required>

                                                            <?php foreach ($packageMethods as $method): ?>

                                                                <option
                                                                    value="<?= e($method) ?>"
                                                                    <?= $method === $b['payment_method'] ? 'selected' : '' ?>>

                                                                    <?= e($method) ?>

                                                                </option>

                                                            <?php endforeach; ?>

                                                        </select>
                                                    </label>

                                                    <label class="wide-field">
                                                        Change package

                                                        <select name="package_id"
                                                                required>

                                                            <?php foreach ($packages as $p): ?>

                                                                <option
                                                                    value="<?= (int)$p['id'] ?>"
                                                                    <?= (int)$p['id'] === (int)$b['package_id'] ? 'selected' : '' ?>>

                                                                    <?= e($p['package_name']) ?>

                                                                </option>

                                                            <?php endforeach; ?>

                                                        </select>
                                                    </label>

                                                </div>

                                                <p class="form-note">
                                                    No card numbers are collected.
                                                    Changing the package updates
                                                    its pending payment amount.
                                                </p>

                                                <button class="btn btn-primary"
                                                        type="submit">
                                                    Save Changes
                                                </button>

                                            </form>

                                        </details>

                                        <form
                                            method="post"
                                            class="cancel-form"
                                            onsubmit="return confirm('Cancel this tour booking?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION['csrf_token']) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="cancel_package">

                                            <input type="hidden"
                                                   name="booking_id"
                                                   value="<?= (int)$b['id'] ?>">

                                            <button class="cancel-button"
                                                    type="submit">
                                                Cancel Booking
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <p class="readonly-note">
                                        This booking is read-only because it
                                        is no longer pending or has a recorded payment.
                                    </p>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <!-- HOTEL BOOKINGS -->
            <section class="dashboard-section"
                     id="hotelBookings">

                <div class="dashboard-section-head">

                    <div>
                        <p class="section-label">YOUR STAYS</p>
                        <h2>Hotel <em>Bookings.</em></h2>
                    </div>

                    <a href="#availableHotels" class="text-link">
                        Book another hotel ↗
                    </a>

                </div>

                <?php if (!$hotelBookings): ?>

                    <div class="empty-state">

                        <span>⌂</span>

                        <h3>No hotel bookings yet</h3>

                        <p>
                            Find a comfortable place to
                            stay on your next trip.
                        </p>

                        <a href="#availableHotels"
                           class="btn btn-primary">
                            Explore Hotels →
                        </a>

                    </div>

                <?php else: ?>

                    <div class="booking-card-list">

                        <?php foreach ($hotelBookings as $b): ?>

                            <?php
                            $photo = imagePath(
                                $b['image'] ?? '',
                                true
                            );

                            $editable = strtolower(
                                trim((string)$b['status'])
                            ) === 'pending';
                            ?>

                            <article class="booking-card">

                                <div class="booking-card-top">

                                    <div class="booking-photo">

                                        <?php if ($photo): ?>

                                            <img
                                                src="<?= e($photo) ?>"
                                                alt="<?= e($b['hotel_name'] ?? 'Hotel') ?>"
                                                loading="lazy">

                                        <?php else: ?>

                                            <span aria-label="No photo available">
                                                ⌂
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="booking-card-info">

                                        <span class="booking-type">
                                            HOTEL STAY · #<?= (int)$b['id'] ?>
                                        </span>

                                        <h3>
                                            <?= e($b['hotel_name'] ?? 'Hotel no longer available') ?>
                                        </h3>

                                        <p class="booking-location">
                                            ⌖ <?= e($b['location'] ?? 'Location unavailable') ?>
                                        </p>

                                        <div class="status-line">

                                            <span class="status-pill <?= statusClass($b['status']) ?>">
                                                <?= e($b['status'] ?: 'Unknown') ?>
                                            </span>

                                            <span class="payment-label">
                                                Payment: not tracked for hotel stays
                                            </span>

                                        </div>

                                    </div>

                                </div>

                                <div class="booking-data">

                                    <div>
                                        <small>Check-in</small>
                                        <strong>
                                            <?= e($b['checkin_date'] ?: 'Not set') ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <small>Check-out</small>
                                        <strong>
                                            <?= e($b['checkout_date'] ?: 'Not set') ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <small>Transport</small>
                                        <strong>
                                            <?= e($b['vehicle_name'] ?: 'Not selected') ?>
                                        </strong>
                                    </div>

                                    <div>
                                        <small>Payment Method</small>
                                        <strong>
                                            <?= e($b['payment_method'] ?: 'Not selected') ?>
                                        </strong>
                                    </div>

                                </div>

                                <?php if ($editable): ?>

                                    <div class="booking-actions">

                                        <details class="edit-details">

                                            <summary>
                                                Edit Hotel Booking
                                                <span aria-hidden="true">⌄</span>
                                            </summary>

                                            <form method="post"
                                                  class="edit-form">

                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= e($_SESSION['csrf_token']) ?>">

                                                <input type="hidden"
                                                       name="action"
                                                       value="update_hotel">

                                                <input type="hidden"
                                                       name="hotel_booking_id"
                                                       value="<?= (int)$b['id'] ?>">

                                                <div class="field-grid">

                                                    <label>
                                                        Hotel

                                                        <select name="hotel_id"
                                                                required>

                                                            <?php foreach ($hotels as $h): ?>

                                                                <option
                                                                    value="<?= (int)$h['id'] ?>"
                                                                    <?= (int)$h['id'] === (int)$b['hotel_id'] ? 'selected' : '' ?>>

                                                                    <?= e($h['hotel_name']) ?>

                                                                </option>

                                                            <?php endforeach; ?>

                                                        </select>
                                                    </label>

                                                    <label>
                                                        Transport

                                                        <select name="transport_id"
                                                                required>

                                                            <?php foreach ($vehicles as $v): ?>

                                                                <option
                                                                    value="<?= (int)$v['id'] ?>"
                                                                    <?= (int)$v['id'] === (int)$b['transport_id'] ? 'selected' : '' ?>>

                                                                    <?= e($v['vehicle_name']) ?>

                                                                </option>

                                                            <?php endforeach; ?>

                                                        </select>
                                                    </label>

                                                    <label>
                                                        Check-in

                                                        <input type="date"
                                                               name="checkin_date"
                                                               min="<?= e($today) ?>"
                                                               value="<?= e($b['checkin_date']) ?>"
                                                               required>
                                                    </label>

                                                    <label>
                                                        Check-out

                                                        <input type="date"
                                                               name="checkout_date"
                                                               min="<?= e($today) ?>"
                                                               value="<?= e($b['checkout_date']) ?>"
                                                               required>
                                                    </label>

                                                    <label class="wide-field">
                                                        Payment method

                                                        <select name="payment_method"
                                                                required>

                                                            <?php foreach ($hotelMethods as $method): ?>

                                                                <option
                                                                    value="<?= e($method) ?>"
                                                                    <?= $method === $b['payment_method'] ? 'selected' : '' ?>>

                                                                    <?= e($method) ?>

                                                                </option>

                                                            <?php endforeach; ?>

                                                        </select>
                                                    </label>

                                                </div>

                                                <p class="form-note">
                                                    Choose a check-out date after
                                                    check-in. No card numbers are collected.
                                                </p>

                                                <button class="btn btn-primary"
                                                        type="submit">
                                                    Save Changes
                                                </button>

                                            </form>

                                        </details>

                                        <form
                                            method="post"
                                            class="cancel-form"
                                            onsubmit="return confirm('Cancel this hotel booking?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION['csrf_token']) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="cancel_hotel">

                                            <input type="hidden"
                                                   name="hotel_booking_id"
                                                   value="<?= (int)$b['id'] ?>">

                                            <button class="cancel-button"
                                                    type="submit">
                                                Cancel Booking
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <p class="readonly-note">
                                        This hotel booking cannot be edited
                                        because it is no longer pending.
                                    </p>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <!-- AVAILABLE HOTELS -->
            <section class="dashboard-section"
                     id="availableHotels">

                <div class="dashboard-section-head">

                    <div>
                        <p class="section-label">
                            PLAN YOUR STAY
                        </p>

                        <h2>Available <em>Hotels.</em></h2>

                        <p class="section-hint">
                            Choose a hotel and transport to
                            request a stay.
                        </p>
                    </div>

                </div>

                <?php if (!$hotels): ?>

                    <div class="empty-state">

                        <h3>No hotels available right now</h3>

                        <p>
                            Please check back another time.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="hotel-grid">

                        <?php foreach ($hotels as $hotel): ?>

                            <?php
                            $photo = imagePath(
                                $hotel['image'] ?? '',
                                true
                            );
                            ?>

                            <article class="hotel-card">

                                <div class="hotel-photo">

                                    <?php if ($photo): ?>

                                        <img
                                            src="<?= e($photo) ?>"
                                            alt="<?= e($hotel['hotel_name']) ?>"
                                            loading="lazy">

                                    <?php else: ?>

                                        <span aria-label="No photo available">
                                            ⌂
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="hotel-content">

                                    <p class="booking-location">
                                        ⌖ <?= e($hotel['location']) ?>
                                    </p>

                                    <h3>
                                        <?= e($hotel['hotel_name']) ?>
                                    </h3>

                                    <p class="hotel-price">

                                        Listed price:

                                        <strong>
                                            Rs. <?= number_format((float)$hotel['price'], 2) ?>
                                        </strong>

                                    </p>

                                    <details class="edit-details hotel-request">

                                        <summary>
                                            Request a Stay
                                            <span aria-hidden="true">⌄</span>
                                        </summary>

                                        <form method="post"
                                              class="edit-form">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION['csrf_token']) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="create_hotel">

                                            <input type="hidden"
                                                   name="hotel_id"
                                                   value="<?= (int)$hotel['id'] ?>">

                                            <div class="field-grid one-column">

                                                <label>
                                                    Check-in

                                                    <input type="date"
                                                           name="checkin_date"
                                                           min="<?= e($today) ?>"
                                                           required>
                                                </label>

                                                <label>
                                                    Check-out

                                                    <input type="date"
                                                           name="checkout_date"
                                                           min="<?= e($today) ?>"
                                                           required>
                                                </label>

                                                <label>
                                                    Transport

                                                    <select name="transport_id"
                                                            required>

                                                        <option value="">
                                                            Select a vehicle
                                                        </option>

                                                        <?php foreach ($vehicles as $v): ?>

                                                            <option value="<?= (int)$v['id'] ?>">
                                                                <?= e($v['vehicle_name']) ?>
                                                                · Rs. <?= number_format((float)$v['price'], 2) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>
                                                </label>

                                                <label>
                                                    Payment method

                                                    <select name="payment_method"
                                                            required>

                                                        <?php foreach ($hotelMethods as $method): ?>

                                                            <option value="<?= e($method) ?>">
                                                                <?= e($method) ?>
                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>
                                                </label>

                                            </div>

                                            <p class="form-note">
                                                This creates a pending booking
                                                request. No payment is charged.
                                            </p>

                                            <button
                                                class="btn btn-primary"
                                                type="submit"
                                                <?= !$vehicles ? 'disabled' : '' ?>>

                                                Submit Request →

                                            </button>

                                            <?php if (!$vehicles): ?>

                                                <p class="form-note">
                                                    No transport options are
                                                    currently available.
                                                </p>

                                            <?php endif; ?>

                                        </form>

                                    </details>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </section>

</main>

<!-- FOOTER -->
<footer class="site-footer">

    <div class="container footer-main">

        <div class="footer-about">

            <a href="index.php" class="logo">
                <span class="logo-symbol">✦</span>
                Globe<span>Trek</span>
                <small>ADVENTURES</small>
            </a>

            <p>
                Explore beyond boundaries.
                <br>
                Make every journey count.
            </p>

        </div>

        <div class="footer-links">

            <h4>Explore</h4>

            <a href="index.php">Home</a>
            <a href="packages.php">Tour Packages</a>
            <a href="contact.php">Contact Us</a>

        </div>

        <div class="footer-links">

            <h4>My Account</h4>

            <a href="my_bookings.php">My Bookings</a>
            <a href="update_profile.php">Profile</a>
            <a href="logout.php">Logout</a>

        </div>

    </div>

    <div class="container footer-bottom">

        <p>
            &copy; <?= date('Y') ?>
            GlobeTrek Adventures. All rights reserved.
        </p>

        <p>Made for the love of travel.</p>

    </div>

</footer>

</body>
</html>
