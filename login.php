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

$roles = [
    'user' => [
        'table' => 'users',
        'target' => 'index.php'
    ],
    'staff' => [
        'table' => 'staff',
        'target' => 'staff/dashboard.php'
    ],
    'admin' => [
        'table' => 'admins',
        'target' => 'admin/dashboard.php'
    ]
];

// Redirect already logged-in users
if (isset($_SESSION['user_id'])) {
    $existingRole = (string)($_SESSION['role'] ?? 'user');

    header(
        'Location: ' .
        ($roles[$existingRole]['target'] ?? 'index.php')
    );
    exit;
}

// CSRF protection
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$message = isset($_GET['registered']) &&
           $_GET['registered'] === '1'
    ? 'Account created successfully. Please sign in.'
    : '';

$error = '';
$role = 'user';
$username = '';

// Basic session-based login attempt limit
$attempts = $_SESSION['login_attempts'] ?? [];

$attempts = array_values(array_filter(
    $attempts,
    function ($time) {
        return is_int($time) && $time > time() - 900;
    }
));

$_SESSION['login_attempts'] = $attempts;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role = inputText('role');
    $username = inputText('username');

    $password = isset($_POST['password']) &&
                is_string($_POST['password'])
        ? $_POST['password']
        : '';

    $token = inputText('csrf_token');

    if (!hash_equals($_SESSION['login_csrf'], $token)) {

        $error = 'Your session expired. Refresh and try again.';

    } elseif (count($attempts) >= 10) {

        $error = 'Too many attempts. Please try again later.';

    } elseif (
        !isset($roles[$role]) ||
        $username === '' ||
        $password === '' ||
        strlen($username) > 100 ||
        strlen($password) > 4096
    ) {

        $error = 'Invalid username, password or account type.';

    } else {

        // The table comes from a fixed whitelist
        $table = $roles[$role]['table'];

        $stmt = $conn->prepare(
            "SELECT id, username, password
             FROM `$table`
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();

        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $valid = false;
        $legacyPassword = false;

        if ($account) {

            $stored = (string)$account['password'];

            $hashInfo = password_get_info($stored);
            $isHash = !empty($hashInfo['algo']);

            $legacyPassword = false;

            // Hashed credentials only. Never accept old plaintext passwords.
            $valid = $isHash && password_verify($password, $stored);
        }

        if ($valid) {

            if (
                $legacyPassword ||
                password_needs_rehash(
                    $stored,
                    PASSWORD_DEFAULT
                )
            ) {

                $newHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $update = $conn->prepare(
                    "UPDATE `$table`
                     SET password = ?
                     WHERE id = ?"
                );

                $id = (int)$account['id'];

                $update->bind_param(
                    'si',
                    $newHash,
                    $id
                );

                $update->execute();
                $update->close();
            }

            session_regenerate_id(true);

            unset($_SESSION['login_attempts']);

            $_SESSION['user_id'] = (int)$account['id'];
            $_SESSION['username'] = $account['username'];
            $_SESSION['role'] = $role;

            header('Location: ' . $roles[$role]['target']);
            exit;
        }

        $_SESSION['login_attempts'][] = time();

        $error = 'Invalid username, password or account type.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | GlobeTrek Adventures</title>

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
    Skip to login
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

        <nav
            id="mainNav"
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

            <a href="login.php"
               class="active"
               aria-current="page">
                Login
            </a>

            <a href="register.php" class="nav-button">
                Get Started ↗
            </a>

        </nav>

    </div>
</header>

<main class="auth-layout" id="authMain">

    <!-- LEFT TRAVEL IMAGE -->
    <section class="auth-visual"
             aria-label="Travel inspiration">

        <div class="auth-visual-content">

            <p class="auth-eyebrow">
                ✦ GLOBETREK ADVENTURES
            </p>

            <h1>
                Good to have
                <br>
                you <em>back.</em>
            </h1>

            <p>
                Your next beautiful journey is just
                one sign-in away.
            </p>

            <div class="auth-visual-bottom">
                EXPLORE MORE. EXPERIENCE MORE.
            </div>

        </div>

    </section>

    <!-- LOGIN FORM -->
    <section class="auth-form-side"
             aria-labelledby="loginHeading">

        <div class="auth-card">

            <p class="section-label">WELCOME BACK</p>

            <h2 id="loginHeading">
                Sign in to <em>GlobeTrek.</em>
            </h2>

            <p class="auth-subtitle">
                Access your bookings and travel account.
            </p>

            <?php if ($message): ?>

                <div class="auth-message success"
                     role="status">
                    <?= esc($message) ?>
                </div>

            <?php endif; ?>

            <?php if ($error): ?>

                <div class="auth-message error"
                     role="alert">
                    <?= esc($error) ?>
                </div>

            <?php endif; ?>

            <form method="post"
                  action="login.php"
                  class="auth-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= esc($_SESSION['login_csrf']) ?>">

                <!-- ROLE -->
                <div class="auth-field">

                    <label for="role">Account type</label>

                    <select id="role" name="role" required>

                        <option value="user"
                            <?= $role === 'user' ? 'selected' : '' ?>>
                            Customer
                        </option>

                        <option value="staff"
                            <?= $role === 'staff' ? 'selected' : '' ?>>
                            Staff
                        </option>

                        <option value="admin"
                            <?= $role === 'admin' ? 'selected' : '' ?>>
                            Admin
                        </option>

                    </select>

                </div>

                <!-- USERNAME -->
                <div class="auth-field">

                    <label for="username">
                        Username
                    </label>

                    <input
                        id="username"
                        name="username"
                        type="text"
                        maxlength="100"
                        autocomplete="username"
                        placeholder="Enter your username"
                        value="<?= esc($username) ?>"
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
                            autocomplete="current-password"
                            placeholder="Enter your password"
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

                <button
                    class="auth-submit"
                    type="submit"
                    name="login"
                    value="1">
                    Sign In ↗
                </button>

            </form>

            <p class="auth-switch">
                New to GlobeTrek?
                <a href="register.php">
                    Create an account
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
