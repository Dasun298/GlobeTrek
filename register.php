
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function inputText($key) {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key]) : '';
}

// Redirect signed-in users
if (isset($_SESSION['user_id'])) {

    $role = (string)($_SESSION['role'] ?? 'user');

    $target = $role === 'admin'
        ? 'admin/dashboard.php'
        : (
            $role === 'staff'
                ? 'staff/dashboard.php'
                : 'index.php'
        );

    header('Location: ' . $target);
    exit;
}

// CSRF protection
if (empty($_SESSION['register_csrf'])) {
    $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$username = '';
$email = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = inputText('username');
    $email = inputText('email');

    $password = isset($_POST['password']) &&
                is_string($_POST['password'])
        ? $_POST['password']
        : '';

    $confirm = isset($_POST['confirm_password']) &&
               is_string($_POST['confirm_password'])
        ? $_POST['confirm_password']
        : '';

    $token = inputText('csrf_token');

    if (!hash_equals($_SESSION['register_csrf'], $token)) {

        $error = 'Your session expired. Refresh and try again.';

    } elseif (
        strlen($username) < 3 ||
        strlen($username) > 100 ||
        !preg_match('/^[A-Za-z0-9_.-]+$/', $username)
    ) {

        $error = 'Username: 3–100 letters, numbers, dots, dashes or underscores.';

    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 100
    ) {

        $error = 'Enter a valid email address (maximum 100 characters).';

    } elseif (
        strlen($password) < 8 ||
        strlen($password) > 72
    ) {

        $error = 'Password must contain 8–72 characters.';

    } elseif (!hash_equals($password, $confirm)) {

        $error = 'Passwords do not match.';

    } else {

        // Check for existing account
        $check = $conn->prepare(
            'SELECT id FROM users
             WHERE username = ? OR email = ?
             LIMIT 1'
        );

        $check->bind_param('ss', $username, $email);
        $check->execute();

        $exists = (bool)$check->get_result()->fetch_assoc();

        $check->close();

        if ($exists) {

            $error = 'This username or email is already registered.';

        } else {

            // Customer registration only
            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            try {

                $stmt = $conn->prepare(
                    "INSERT INTO users
                     (username, password, email, role)
                     VALUES (?, ?, ?, 'customer')"
                );

                $stmt->bind_param(
                    'sss',
                    $username,
                    $hash,
                    $email
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['register_csrf'] =
                    bin2hex(random_bytes(32));

                header('Location: login.php?registered=1');
                exit;

            } catch (mysqli_sql_exception $exception) {

                error_log(
                    'Registration error: ' .
                    $exception->getMessage()
                );

                $error = 'Registration could not be completed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account | GlobeTrek Adventures</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/auth.css">

    <script src="js/home.js" defer></script>
    <script src="js/auth.js" defer></script>
</head>

<body>

<a class="skip-link" href="#authMain">
    Skip to registration
</a>

<!-- NAVIGATION -->
<header class="site-header auth-header">

    <div class="container navbar">

        <a href="index.php" class="logo">
            <span class="logo-symbol">✦</span>
            Globe<span>Trek</span>
            <small>ADVENTURES</small>
        </a>

        <button
            id="menuToggle"
            class="menu-toggle"
            type="button"
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

            <a href="packages.php">
                Tour Packages
            </a>

            <a href="contact.php">Contact</a>

            <a href="login.php">Login</a>

            <a href="register.php"
               class="nav-button active"
               aria-current="page">
                Get Started ↗
            </a>

        </nav>

    </div>

</header>

<main class="auth-layout" id="authMain">

    <!-- TRAVEL IMAGE -->
    <section class="auth-visual register-visual"
             aria-label="Travel inspiration">

        <div class="auth-visual-content">

            <p class="auth-eyebrow">
                ✦ YOUR ADVENTURE BEGINS HERE
            </p>

            <h1>
                Start exploring
                <br>
                <em>with us.</em>
            </h1>

            <p>
                Discover inspiring places and turn
                travel dreams into memories.
            </p>

            <div class="auth-visual-bottom">
                NEW PLACES. NEW STORIES.
            </div>

        </div>

    </section>

    <!-- REGISTER FORM -->
    <section class="auth-form-side"
             aria-labelledby="registerHeading">

        <div class="auth-card">

            <p class="section-label">JOIN GLOBETREK</p>

            <h2 id="registerHeading">
                Create your <em>account.</em>
            </h2>

            <p class="auth-subtitle">
                Create a free customer account
                to start planning.
            </p>

            <?php if ($error): ?>

                <div class="auth-message error"
                     role="alert">
                    <?= esc($error) ?>
                </div>

            <?php endif; ?>

            <form method="post"
                  action="register.php"
                  class="auth-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= esc($_SESSION['register_csrf']) ?>">

                <!-- USERNAME -->
                <div class="auth-field">

                    <label for="username">
                        Username
                    </label>

                    <input
                        id="username"
                        name="username"
                        type="text"
                        minlength="3"
                        maxlength="100"
                        pattern="[A-Za-z0-9_.-]+"
                        autocomplete="username"
                        placeholder="Choose a username"
                        value="<?= esc($username) ?>"
                        required>

                </div>

                <!-- EMAIL -->
                <div class="auth-field">

                    <label for="email">
                        Email address
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="100"
                        autocomplete="email"
                        placeholder="you@example.com"
                        value="<?= esc($email) ?>"
                        required>

                </div>

                <!-- PASSWORD -->
                <div class="auth-field">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-wrap">

                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            placeholder="At least 8 characters"
                            required>

                        <button
                            class="password-toggle"
                            type="button"
                            data-target="password"
                            aria-label="Show password"
                            aria-pressed="false">
                            Show
                        </button>

                    </div>

                </div>

                <!-- CONFIRM PASSWORD -->
                <div class="auth-field">

                    <label for="confirmPassword">
                        Confirm password
                    </label>

                    <div class="password-wrap">

                        <input
                            id="confirmPassword"
                            name="confirm_password"
                            type="password"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            placeholder="Re-enter password"
                            required>

                        <button
                            class="password-toggle"
                            type="button"
                            data-target="confirmPassword"
                            aria-label="Show password"
                            aria-pressed="false">
                            Show
                        </button>

                    </div>

                </div>

                <button
                    class="auth-submit"
                    type="submit"
                    name="register"
                    value="1">
                    Create Account ↗
                </button>

            </form>

            <p class="auth-switch">
                Already have an account?
                <a href="login.php">
                    Sign in
                </a>
            </p>

        </div>

    </section>

</main>

<footer class="auth-footer">
    © <?= date('Y') ?> GlobeTrek Adventures ·
    Made for the love of travel.
</footer>

</body>
</html>
