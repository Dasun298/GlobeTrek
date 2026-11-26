
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = (string) ($_SESSION['role'] ?? 'user');

if ($role === 'admin' || $role === 'staff') {
    header('Location: ' . $role . '/dashboard.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function postText($key) {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key]) : '';
}

// Get selected package
$packageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$packageId || $packageId < 1) {
    header('Location: packages.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, package_name, destination, details, price, image
     FROM packages WHERE id = ? LIMIT 1"
);

$stmt->bind_param('i', $packageId);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) {
    header('Location: packages.php');
    exit;
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$today = (new DateTimeImmutable(
    'now',
    new DateTimeZone('Asia/Colombo')
))->format('Y-m-d');

$methods = [
    'Cash',
    'Visa Card',
    'Master Card',
    'Bank Transfer'
];

$errors = [];

$form = [
    'customer_name' => '',
    'id_number' => '',
    'travel_date' => '',
    'payment_method' => ''
];

// Success message after redirect
$success = $_SESSION['booking_success'] ?? '';
unset($_SESSION['booking_success']);

// Handle booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($form as $key => $unused) {
        $form[$key] = postText($key);
    }

    $token = postText('csrf_token');

    if (
        $token === '' ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $errors[] = 'Session expired. Refresh the page and try again.';
    }

    // Validate name
    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($form['customer_name'], 'UTF-8')
        : strlen($form['customer_name']);

    if ($nameLength < 2 || $nameLength > 100) {
        $errors[] = 'Enter a customer name (2–100 characters).';
    }

    // Validate NIC / passport
    if (
        $form['id_number'] === '' ||
        strlen($form['id_number']) > 50
    ) {
        $errors[] = 'Enter a valid NIC or passport number.';
    }

    // Validate travel date
    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $form['travel_date']
    );

    $dateIssues = DateTimeImmutable::getLastErrors();

    $validDate = $date &&
        $date->format('Y-m-d') === $form['travel_date'] &&
        (
            $dateIssues === false ||
            (
                $dateIssues['warning_count'] === 0 &&
                $dateIssues['error_count'] === 0
            )
        );

    if (!$validDate || $form['travel_date'] < $today) {
        $errors[] = 'Choose a valid travel date today or later.';
    }

    // Validate payment method
    if (!in_array($form['payment_method'], $methods, true)) {
        $errors[] = 'Choose a payment method.';
    }

    $price = (float)$package['price'];

    if ($price <= 0) {
        $errors[] = 'This package is currently unavailable.';
    }

    // Save booking
    if (!$errors) {

        try {
            $conn->begin_transaction();

            $userId = (int)$_SESSION['user_id'];
            $status = 'Pending';

            $booking = $conn->prepare(
                "INSERT INTO bookings
                (
                    user_id,
                    package_id,
                    customer_name,
                    id_number,
                    payment_method,
                    travel_date,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $booking->bind_param(
                'iisssss',
                $userId,
                $packageId,
                $form['customer_name'],
                $form['id_number'],
                $form['payment_method'],
                $form['travel_date'],
                $status
            );

            $booking->execute();

            $bookingId = $conn->insert_id;
            $booking->close();

            // Payment record is pending
            $paymentStatus = 'Pending';

            $payment = $conn->prepare(
                "INSERT INTO payments
                (booking_id, amount, status)
                VALUES (?, ?, ?)"
            );

            $payment->bind_param(
                'ids',
                $bookingId,
                $price,
                $paymentStatus
            );

            $payment->execute();
            $payment->close();

            $conn->commit();

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $_SESSION['booking_success'] =
                'Booking request #' . (int)$bookingId .
                ' submitted! Your booking and payment are pending confirmation.';

            // Prevent duplicate booking on refresh
            header('Location: booking.php?id=' . $packageId);
            exit;

        } catch (Throwable $ex) {

            $conn->rollback();

            error_log('Booking failed: ' . $ex->getMessage());

            $errors[] = 'Could not save your booking. Please try again.';
        }
    }
}

// Package image
$imageFile = basename((string)($package['image'] ?? ''));

$imageAvailable = $imageFile !== '' &&
    is_file(__DIR__ . '/uploads/' . $imageFile);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>
        Book <?php echo e($package['package_name']); ?> | GlobeTrek
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/booking.css">

    <script src="js/home.js" defer></script>
</head>

<body>

<a class="skip-link" href="#bookingContent">
    Skip to booking
</a>

<!-- NAVBAR -->
<header class="site-header">

    <div class="container navbar">

        <a href="index.php" class="logo">
            <span class="logo-symbol">✦</span>
            Globe<span>Trek</span>
            <small>ADVENTURES</small>
        </a>

        <button
            type="button"
            id="menuToggle"
            class="menu-toggle"
            aria-label="Open navigation menu"
            aria-controls="mainNav"
            aria-expanded="false">
            ☰
        </button>

        <nav id="mainNav"
             class="nav-links"
             aria-label="Main navigation">

            <a href="index.php">Home</a>

            <a href="index.php#destinations">
                Destinations
            </a>

            <a href="packages.php" class="active">
                Tour Packages
            </a>

            <a href="contact.php">Contact</a>

            <a href="my_bookings.php">My Bookings</a>

            <a href="update_profile.php">Profile</a>

            <a href="logout.php" class="nav-button">
                Logout
            </a>

        </nav>

    </div>

</header>

<main id="bookingContent">

    <!-- HERO -->
    <section class="booking-banner">

        <div class="container">

            <p class="hero-label">
                ✦ GLOBETREK ADVENTURES
            </p>

            <h1>
                Plan Your <span>Next Escape.</span>
            </h1>

            <p>
                Just a few details away from your
                next unforgettable journey.
            </p>

            <a href="packages.php" class="booking-back">
                ← Back to Tour Packages
            </a>

        </div>

    </section>

    <!-- BOOKING SECTION -->
    <section class="booking-section">

        <div class="container">

            <div class="booking-intro">

                <div>
                    <p class="section-label">
                        SIMPLE &amp; CONVENIENT
                    </p>

                    <h2>
                        Complete Your <span>Booking.</span>
                    </h2>

                    <p>
                        Review your package and send
                        your booking request.
                    </p>
                </div>

                <span class="booking-step">
                    01 Package Details / 02 Your Details
                </span>

            </div>

            <!-- SUCCESS -->
            <?php if ($success !== ''): ?>

                <div class="booking-alert booking-alert-success"
                     role="status">

                    <strong>Request received!</strong>

                    <p><?php echo e($success); ?></p>

                    <a href="my_bookings.php">
                        View My Bookings →
                    </a>

                </div>

            <?php endif; ?>

            <!-- ERRORS -->
            <?php if ($errors): ?>

                <div class="booking-alert booking-alert-error"
                     role="alert">

                    <strong>Please check your details:</strong>

                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>

                </div>

            <?php endif; ?>

            <div class="booking-grid">

                <!-- LEFT: PACKAGE SUMMARY -->
                <aside class="booking-summary">

                    <div class="booking-summary-image">

                        <?php if ($imageAvailable): ?>

                            <img
                                src="uploads/<?php echo e(rawurlencode($imageFile)); ?>"
                                alt="<?php echo e($package['destination']); ?>"
                                loading="lazy">

                        <?php else: ?>

                            <div class="booking-image-placeholder">
                                ✈
                            </div>

                        <?php endif; ?>

                        <span class="booking-image-badge">
                            YOUR SELECTED PACKAGE
                        </span>

                    </div>

                    <div class="booking-summary-body">

                        <p class="booking-location">
                            ⌖ <?php echo e($package['destination']); ?>
                        </p>

                        <h3>
                            <?php echo e($package['package_name']); ?>
                        </h3>

                        <p class="booking-description">
                            <?php echo nl2br(e($package['details'])); ?>
                        </p>

                        <div class="booking-price-row">

                            <div>
                                <small>Package price</small>

                                <strong>
                                    Rs.
                                    <?php echo number_format((float)$package['price'], 2); ?>
                                </strong>
                            </div>

                            <span class="booking-status">
                                Payment pending
                            </span>

                        </div>

                        <p class="booking-summary-note">
                            The final booking and payment status
                            will be confirmed by the travel team.
                        </p>

                    </div>

                </aside>

                <!-- RIGHT: BOOKING FORM -->
                <div class="booking-form-card">

                    <div class="booking-form-head">

                        <span class="booking-form-icon">✈</span>

                        <div>
                            <h3>Traveller Information</h3>

                            <p>
                                Fill in the details below
                                to request your booking.
                            </p>
                        </div>

                    </div>

                    <form
                        action="booking.php?id=<?php echo (int)$packageId; ?>"
                        method="POST"
                        class="booking-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo e($_SESSION['csrf_token']); ?>">

                        <!-- CUSTOMER NAME -->
                        <div class="booking-field">

                            <label for="customer_name">
                                Full Name <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                maxlength="100"
                                autocomplete="name"
                                placeholder="Enter your full name"
                                value="<?php echo e($form['customer_name']); ?>"
                                required>

                        </div>

                        <!-- NIC / PASSPORT -->
                        <div class="booking-field">

                            <label for="id_number">
                                NIC / Passport Number <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="id_number"
                                name="id_number"
                                maxlength="50"
                                placeholder="Enter your NIC or passport number"
                                value="<?php echo e($form['id_number']); ?>"
                                required>

                        </div>

                        <!-- TRAVEL DATE -->
                        <div class="booking-field">

                            <label for="travel_date">
                                Preferred Travel Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                id="travel_date"
                                name="travel_date"
                                min="<?php echo e($today); ?>"
                                value="<?php echo e($form['travel_date']); ?>"
                                required>

                        </div>

                        <!-- PAYMENT METHOD -->
                        <div class="booking-field">

                            <label for="payment_method">
                                Preferred Payment Method <span>*</span>
                            </label>

                            <select
                                id="payment_method"
                                name="payment_method"
                                required>

                                <option value="">
                                    Choose a payment method
                                </option>

                                <?php foreach ($methods as $method): ?>

                                    <option
                                        value="<?php echo e($method); ?>"
                                        <?php echo $form['payment_method'] === $method ? 'selected' : ''; ?>>

                                        <?php echo e($method); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- PAYMENT INFORMATION -->
                        <div class="booking-payment-info">

                            <strong>Payment Information</strong>

                            <p>
                                This form only records your preferred
                                payment method. No payment is charged
                                online. Do not enter card numbers
                                or CVV details.
                            </p>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary booking-submit">

                            Submit Booking Request ↗

                        </button>

                        <p class="booking-form-footer">
                            Booking status starts as
                            <strong>Pending</strong>
                            until confirmed.
                        </p>

                    </form>

                </div>

            </div>

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

            <h4>Your Account</h4>

            <a href="my_bookings.php">My Bookings</a>
            <a href="update_profile.php">Profile</a>
            <a href="logout.php">Logout</a>

        </div>

    </div>

    <div class="container footer-bottom">

        <p>
            &copy; <?php echo date('Y'); ?>
            GlobeTrek Adventures. All rights reserved.
        </p>

        <p>Made for the love of travel.</p>

    </div>

</footer>

</body>
</html>
