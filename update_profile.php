
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

// Login and role protection
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = (string)($_SESSION['role'] ?? 'user');

if (in_array($role, ['admin', 'staff'], true)) {
    header('Location: ' . $role . '/dashboard.php');
    exit;
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function textInput($key) {
    return is_string($_POST[$key] ?? null)
        ? trim($_POST[$key])
        : '';
}

function passwordInput($key) {
    return is_string($_POST[$key] ?? null)
        ? $_POST[$key]
        : '';
}

// Load customer
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare(
    'SELECT id, username, email, password
     FROM users WHERE id = ? LIMIT 1'
);

$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    unset(
        $_SESSION['user_id'],
        $_SESSION['username'],
        $_SESSION['role']
    );

    header('Location: login.php');
    exit;
}

// CSRF protection
if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];

$notice = (string)(
    $_SESSION['profile_notice'] ?? ''
);

unset($_SESSION['profile_notice']);

$displayUsername = (string)$user['username'];
$displayEmail = (string)$user['email'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $displayUsername = textInput('username');
    $displayEmail = textInput('email');

    $currentPassword = passwordInput('current_password');
    $newPassword = passwordInput('new_password');
    $confirmPassword = passwordInput('confirm_password');

    $token = textInput('profile_csrf');

    // CSRF validation
    if (
        $token === '' ||
        !hash_equals($_SESSION['profile_csrf'], $token)
    ) {
        $errors[] =
            'Invalid form request. Refresh the page and try again.';
    }

    // Username validation
    if (
        strlen($displayUsername) < 3 ||
        strlen($displayUsername) > 100 ||
        !preg_match(
            '/^[A-Za-z0-9_.-]+$/',
            $displayUsername
        )
    ) {
        $errors[] =
            'Username must be 3–100 characters (letters, numbers, dot, dash or underscore).';
    }

    // Email validation
    if (
        !filter_var($displayEmail, FILTER_VALIDATE_EMAIL) ||
        strlen($displayEmail) > 100
    ) {
        $errors[] =
            'Enter a valid email address of 100 characters or fewer.';
    }

    // Current password is always required
    if ($currentPassword === '') {
        $errors[] =
            'Enter your current password to save changes.';
    }

    // New password is optional
    if ($newPassword !== '' || $confirmPassword !== '') {

        if (
            strlen($newPassword) < 8 ||
            strlen($newPassword) > 72
        ) {
            $errors[] =
                'New password must be between 8 and 72 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] =
                'New password and confirm password do not match.';
        }
    }

    // Verify current password
    if (!$errors) {

        $storedPassword = (string)$user['password'];

        $hashInfo = password_get_info($storedPassword);

        $isHashed = (
            $hashInfo['algoName'] ?? 'unknown'
        ) !== 'unknown';

        $passwordMatches = $isHashed && password_verify(
            $currentPassword,
            $storedPassword
        );

        if (!$passwordMatches) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    // Save details
    if (!$errors) {

        try {

            // Check duplicate username or email
            $check = $conn->prepare(
                'SELECT id FROM users
                 WHERE (username = ? OR email = ?)
                   AND id <> ?
                 LIMIT 1'
            );

            $check->bind_param(
                'ssi',
                $displayUsername,
                $displayEmail,
                $userId
            );

            $check->execute();

            $duplicate = (bool)$check
                ->get_result()
                ->fetch_assoc();

            $check->close();

            if ($duplicate) {

                $errors[] =
                    'This username or email is already in use.';

            } else {

                // Upgrade an old plaintext password too
                $updatePassword =
                    $newPassword !== '' || !$isHashed;

                if ($updatePassword) {

                    $hash = password_hash(
                        $newPassword !== ''
                            ? $newPassword
                            : $currentPassword,
                        PASSWORD_DEFAULT
                    );

                    $update = $conn->prepare(
                        'UPDATE users
                         SET username = ?,
                             email = ?,
                             password = ?
                         WHERE id = ?'
                    );

                    $update->bind_param(
                        'sssi',
                        $displayUsername,
                        $displayEmail,
                        $hash,
                        $userId
                    );

                } else {

                    $update = $conn->prepare(
                        'UPDATE users
                         SET username = ?,
                             email = ?
                         WHERE id = ?'
                    );

                    $update->bind_param(
                        'ssi',
                        $displayUsername,
                        $displayEmail,
                        $userId
                    );
                }

                $update->execute();
                $update->close();

                $_SESSION['username'] = $displayUsername;

                if ($newPassword !== '') {
                    session_regenerate_id(true);
                }

                $_SESSION['profile_csrf'] =
                    bin2hex(random_bytes(32));

                $_SESSION['profile_notice'] =
                    'Your profile has been updated successfully.';

                header('Location: update_profile.php');
                exit;
            }

        } catch (mysqli_sql_exception $exception) {

            error_log(
                'Profile update error: ' .
                $exception->getMessage()
            );

            $errors[] =
                'Could not update your profile. Please try again.';
        }
    }
}

$avatarLetter = strtoupper(
    substr(
        $displayUsername !== ''
            ? $displayUsername
            : 'T',
        0,
        1
    )
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Profile | GlobeTrek Adventures</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/profile.css">

    <script src="js/home.js" defer></script>
    <script src="js/auth.js" defer></script>
</head>

<body>

<a class="skip-link" href="#profileMain">
    Skip to profile
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
            class="menu-toggle"
            id="menuToggle"
            type="button"
            aria-label="Open navigation menu"
            aria-controls="mainNav"
            aria-expanded="false">
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

            <a href="contact.php">Contact</a>

            <a href="my_bookings.php">
                My Bookings
            </a>

            <a href="update_profile.php"
               class="active"
               aria-current="page">
                Profile
            </a>

            <a href="logout.php" class="nav-button">
                Logout
            </a>

        </nav>

    </div>
</header>

<main id="profileMain">

    <!-- HERO -->
    <section class="profile-hero">

        <div class="container">

            <p class="hero-label">
                ✦ YOUR GLOBETREK ACCOUNT
            </p>

            <h1>
                Your Journey,
                <em>Your Profile.</em>
            </h1>

            <p>
                Keep your personal details up to date
                and travel with confidence.
            </p>

        </div>

    </section>

    <!-- PROFILE CONTENT -->
    <section class="profile-section">

        <div class="container profile-grid">

            <!-- LEFT: PROFILE CARD -->
            <aside class="profile-sidebar">

                <div class="profile-avatar"
                     aria-hidden="true">
                    <?php echo e($avatarLetter); ?>
                </div>

                <p class="profile-side-label">
                    GLOBETREK TRAVELLER
                </p>

                <h2>
                    <?php echo e($displayUsername); ?>
                </h2>

                <p class="profile-side-email">
                    <?php echo e($displayEmail); ?>
                </p>

                <div class="profile-side-line"></div>

                <a href="update_profile.php"
                   class="profile-side-link current">
                    ✦ &nbsp; Account Settings
                </a>

                <a href="my_bookings.php"
                   class="profile-side-link">
                    ✈ &nbsp; My Bookings
                </a>

                <a href="packages.php"
                   class="profile-side-link">
                    ⌖ &nbsp; Explore Packages
                </a>

                <a href="logout.php"
                   class="profile-side-link">
                    ↗ &nbsp; Sign Out
                </a>

            </aside>

            <!-- RIGHT: EDIT PROFILE -->
            <div class="profile-panel">

                <p class="section-label">
                    MANAGE YOUR ACCOUNT
                </p>

                <h2>
                    Edit your <em>profile.</em>
                </h2>

                <p class="profile-subtitle">
                    Update your username, email or password.
                    Your current password is required to
                    save changes.
                </p>

                <!-- SUCCESS MESSAGE -->
                <?php if ($notice !== ''): ?>

                    <div class="profile-alert profile-success"
                         role="status">

                        <?php echo e($notice); ?>

                    </div>

                <?php endif; ?>

                <!-- VALIDATION ERRORS -->
                <?php if ($errors): ?>

                    <div class="profile-alert profile-error"
                         role="alert">

                        <strong>
                            Please fix the following:
                        </strong>

                        <ul>
                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?php echo e($error); ?>
                                </li>

                            <?php endforeach; ?>
                        </ul>

                    </div>

                <?php endif; ?>

                <form class="profile-form"
                      method="post"
                      action="update_profile.php">

                    <input
                        type="hidden"
                        name="profile_csrf"
                        value="<?php echo e($_SESSION['profile_csrf']); ?>">

                    <!-- PERSONAL INFORMATION -->
                    <h3>Personal Information</h3>

                    <div class="profile-field-grid">

                        <div class="profile-field">

                            <label for="username">
                                Username <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                minlength="3"
                                maxlength="100"
                                pattern="[A-Za-z0-9_.-]+"
                                autocomplete="username"
                                value="<?php echo e($displayUsername); ?>"
                                required>

                        </div>

                        <div class="profile-field">

                            <label for="email">
                                Email address <span>*</span>
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="100"
                                autocomplete="email"
                                value="<?php echo e($displayEmail); ?>"
                                required>

                        </div>

                    </div>

                    <div class="profile-separator"></div>

                    <!-- SECURITY -->
                    <h3>Account Security</h3>

                    <p class="profile-help">
                        Leave the new password fields empty
                        if you only want to change your
                        username or email.
                    </p>

                    <div class="profile-field">

                        <label for="current_password">
                            Current password <span>*</span>
                        </label>

                        <div class="profile-password-wrap">

                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                placeholder="Enter your current password"
                                required>

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="current_password"
                                aria-label="Show password"
                                aria-pressed="false">
                                Show
                            </button>

                        </div>

                    </div>

                    <div class="profile-field-grid">

                        <div class="profile-field">

                            <label for="new_password">
                                New password
                                <small>(optional)</small>
                            </label>

                            <div class="profile-password-wrap">

                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    minlength="8"
                                    maxlength="72"
                                    autocomplete="new-password"
                                    placeholder="At least 8 characters">

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="new_password"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                    Show
                                </button>

                            </div>

                        </div>

                        <div class="profile-field">

                            <label for="confirm_password">
                                Confirm new password
                            </label>

                            <div class="profile-password-wrap">

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    maxlength="72"
                                    autocomplete="new-password"
                                    placeholder="Re-enter new password">

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="confirm_password"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                    Show
                                </button>

                            </div>

                        </div>

                    </div>

                    <div class="profile-note">
                        🔒 Your password is securely hashed
                        before it is saved. We never display
                        your current password.
                    </div>

                    <!-- BUTTONS -->
                    <div class="profile-actions">

                        <button
                            type="submit"
                            class="btn btn-primary profile-save">

                            Save Changes ↗

                        </button>

                        <a href="my_bookings.php"
                           class="profile-cancel">
                            Back to My Bookings
                        </a>

                    </div>

                </form>

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
            <a href="update_profile.php">My Profile</a>
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
