
<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape output
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

// Get session details
$is_logged_in = isset($_SESSION['user_id'], $_SESSION['role']);

$user_role = $is_logged_in
    ? (string) $_SESSION['role']
    : '';

// Active link helper
function isActivePage($page, $current_page) {
    return $page === $current_page ? 'active' : '';
}

?>

<style>
    .main-navigation {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 15px 5%;
        background: #102a43;
    }

    .main-navigation .brand {
        color: white;
        font-size: 22px;
        font-weight: bold;
        text-decoration: none;
    }

    .main-navigation .nav-links {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .main-navigation .nav-links a {
        color: white;
        text-decoration: none;
        padding: 9px 12px;
        border-radius: 5px;
        transition: 0.2s;
    }

    .main-navigation .nav-links a:hover,
    .main-navigation .nav-links a.active {
        background: #087f8c;
    }

    .main-navigation .menu-toggle {
        display: none;
        color: white;
        background: transparent;
        border: 1px solid white;
        border-radius: 5px;
        padding: 7px 12px;
        font-size: 20px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .main-navigation {
            align-items: flex-start;
        }

        .main-navigation .menu-toggle {
            display: block;
        }

        .main-navigation .nav-links {
            display: none;
            flex-direction: column;
            align-items: stretch;
            width: 100%;
            gap: 5px;
            padding-top: 15px;
        }

        .main-navigation .nav-links.show {
            display: flex;
        }

        .main-navigation .nav-links a {
            display: block;
        }
    }
</style>

<nav class="main-navigation" aria-label="Main navigation">

    <a href="index.php" class="brand">
        GlobeTrek
    </a>

    <button
        type="button"
        class="menu-toggle"
        id="headerMenuButton"
        aria-label="Open navigation menu"
        aria-controls="headerNavLinks"
        aria-expanded="false"
    >
        ☰
    </button>

    <div class="nav-links" id="headerNavLinks">

        <a
            href="index.php"
            class="<?php echo isActivePage('index.php', $current_page); ?>"
        >
            Home
        </a>

        <a
            href="packages.php"
            class="<?php echo isActivePage('packages.php', $current_page); ?>"
        >
            Travel Packages
        </a>

        <?php if ($is_logged_in && $user_role === 'admin'): ?>

            <a href="admin/dashboard.php">
                Admin Panel
            </a>

            <a href="logout.php">
                Logout
            </a>

        <?php elseif ($is_logged_in && $user_role === 'staff'): ?>

            <a href="staff/dashboard.php">
                Staff Panel
            </a>

            <a href="logout.php">
                Logout
            </a>

        <?php elseif ($is_logged_in && $user_role === 'user'): ?>

            <a
                href="my_bookings.php"
                class="<?php echo isActivePage('my_bookings.php', $current_page); ?>"
            >
                My Bookings
            </a>

            <a
                href="update_profile.php"
                class="<?php echo isActivePage('update_profile.php', $current_page); ?>"
            >
                Profile
            </a>

            <a
                href="contact.php"
                class="<?php echo isActivePage('contact.php', $current_page); ?>"
            >
                Contact
            </a>

            <a href="logout.php">
                Logout
            </a>

        <?php else: ?>

            <a href="contact.php">
                Contact
            </a>

            <a href="login.php">
                Login
            </a>

            <a href="register.php">
                Register
            </a>

        <?php endif; ?>

    </div>

</nav>

<script>
    (function () {

        const menuButton = document.getElementById('headerMenuButton');
        const navLinks = document.getElementById('headerNavLinks');

        if (menuButton && navLinks) {

            menuButton.addEventListener('click', function () {

                const isOpen = navLinks.classList.toggle('show');

                menuButton.setAttribute('aria-expanded', isOpen);

                menuButton.setAttribute(
                    'aria-label',
                    isOpen ? 'Close navigation menu' : 'Open navigation menu'
                );

            });

        }

    })();
</script>
