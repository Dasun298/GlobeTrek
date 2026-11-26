
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = (string)($_SESSION['role'] ?? '');

// Search
$search = $_GET['search'] ?? '';
$search = is_string($search) ? trim($search) : '';
$search = substr($search, 0, 100);

// Pagination
$perPage = 9;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

// Count packages
if ($search !== '') {
    $term = '%' . $search . '%';

    $countStmt = $conn->prepare(
        'SELECT COUNT(*) FROM packages
         WHERE package_name LIKE ?
            OR destination LIKE ?
            OR details LIKE ?'
    );

    $countStmt->bind_param('sss', $term, $term, $term);

} else {
    $countStmt = $conn->prepare('SELECT COUNT(*) FROM packages');
}

$countStmt->execute();
$countStmt->bind_result($totalPackages);
$countStmt->fetch();
$countStmt->close();

$totalPackages = (int)$totalPackages;
$totalPages = max(1, (int)ceil($totalPackages / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Load packages from MySQL
if ($search !== '') {
    $stmt = $conn->prepare(
        'SELECT id, package_name, destination,
                details, price, image
         FROM packages
         WHERE package_name LIKE ?
            OR destination LIKE ?
            OR details LIKE ?
         ORDER BY id DESC
         LIMIT ? OFFSET ?'
    );

    $stmt->bind_param(
        'sssii',
        $term,
        $term,
        $term,
        $perPage,
        $offset
    );

} else {
    $stmt = $conn->prepare(
        'SELECT id, package_name, destination,
                details, price, image
         FROM packages
         ORDER BY id DESC
         LIMIT ? OFFSET ?'
    );

    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$packages = $stmt->get_result();

function pageUrl($page, $search) {
    return 'packages.php?' . http_build_query([
        'search' => $search,
        'page' => $page
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Browse travel packages and discover beautiful destinations with GlobeTrek Adventures.">

    <title>Tour Packages | GlobeTrek Adventures</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/packages.css">

    <script src="js/home.js" defer></script>
</head>

<body>

<a class="skip-link" href="#packagesMain">
    Skip to packages
</a>

<!-- NAVIGATION -->
<header class="site-header">

    <div class="container navbar">

        <a class="logo" href="index.php">
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
            aria-expanded="false"
        >
            ☰
        </button>

        <nav id="mainNav"
             class="nav-links"
             aria-label="Main navigation">

            <a href="index.php">Home</a>

            <a href="index.php#destinations">
                Destinations
            </a>

            <a href="packages.php"
               class="active"
               aria-current="page">
                Tour Packages
            </a>

            <a href="contact.php">Contact</a>

            <?php if ($isLoggedIn): ?>

                <?php if ($userRole === 'admin'): ?>

                    <a href="admin/dashboard.php">
                        Admin Panel
                    </a>

                <?php elseif ($userRole === 'staff'): ?>

                    <a href="staff/dashboard.php">
                        Staff Panel
                    </a>

                <?php else: ?>

                    <a href="my_bookings.php">
                        My Bookings
                    </a>

                    <a href="update_profile.php">
                        Profile
                    </a>

                <?php endif; ?>

                <a class="nav-button" href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">Login</a>

                <a class="nav-button" href="register.php">
                    Get Started ↗
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<main id="packagesMain">

    <!-- HERO -->
    <section class="packages-hero">

        <div class="container packages-hero-content">

            <p class="hero-label">
                ✦ DISCOVER YOUR NEXT ADVENTURE
            </p>

            <h1>
                Explore Our
                <span>Tour Packages.</span>
            </h1>

            <p>
                From misty mountains to sunny beaches,
                find a journey worth remembering.
            </p>

            <a class="hero-back" href="index.php">
                ← Back to Homepage
            </a>

        </div>

    </section>

    <!-- PACKAGE CONTENT -->
    <section class="packages-section">

        <div class="container">

            <!-- SEARCH BAR -->
            <form
                class="packages-search"
                action="packages.php"
                method="GET"
                role="search"
            >

                <div class="packages-search-copy">

                    <span class="search-symbol"
                          aria-hidden="true">
                        ⌕
                    </span>

                    <div>
                        <strong>Find your adventure</strong>
                        <small>
                            Search our destinations &amp; packages
                        </small>
                    </div>

                </div>

                <label
                    class="sr-only"
                    for="packageSearch">
                    Search destination or package
                </label>

                <input
                    id="packageSearch"
                    type="search"
                    name="search"
                    maxlength="100"
                    value="<?php echo e($search); ?>"
                    placeholder="Try Ella, Kandy, Galle..."
                >

                <button class="btn btn-primary" type="submit">
                    Search Trips →
                </button>

                <?php if ($search !== ''): ?>

                    <a class="clear-search" href="packages.php">
                        Clear
                    </a>

                <?php endif; ?>

            </form>

            <!-- HEADING -->
            <div class="packages-heading">

                <div>

                    <p class="section-label">
                        HANDPICKED EXPERIENCES
                    </p>

                    <h2>
                        <?php if ($search !== ''): ?>

                            Search <span>Results.</span>

                        <?php else: ?>

                            Find Your Perfect
                            <span>Escape.</span>

                        <?php endif; ?>
                    </h2>

                    <p class="packages-subtitle">

                        <?php if ($search !== ''): ?>

                            Showing packages matching
                            <strong>
                                "<?php echo e($search); ?>"
                            </strong>

                        <?php else: ?>

                            Discover incredible experiences
                            across Sri Lanka.

                        <?php endif; ?>

                    </p>

                </div>

                <span class="result-count">
                    <?php echo $totalPackages; ?>
                    package<?php echo $totalPackages === 1 ? '' : 's'; ?>
                    found
                </span>

            </div>

            <!-- PACKAGE CARDS -->
            <?php if ($packages->num_rows > 0): ?>

                <div class="packages-grid">

                    <?php while ($item = $packages->fetch_assoc()): ?>

                        <?php
                        $imageName = basename(
                            (string)($item['image'] ?? '')
                        );

                        $imageExists = $imageName !== ''
                            && is_file(
                                __DIR__ . '/uploads/' . $imageName
                            );
                        ?>

                        <article class="package-card">

                            <div class="package-photo">

                                <?php if ($imageExists): ?>

                                    <img
                                        src="uploads/<?php echo e(rawurlencode($imageName)); ?>"
                                        alt="<?php echo e($item['destination']); ?> tour destination"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div
                                        class="package-placeholder"
                                        aria-label="Image unavailable">
                                        ✈
                                    </div>

                                <?php endif; ?>

                                <span class="package-photo-label">
                                    Explore Sri Lanka
                                </span>

                            </div>

                            <div class="package-body">

                                <p class="package-location">
                                    ⌖ <?php echo e($item['destination']); ?>
                                </p>

                                <h3>
                                    <?php echo e($item['package_name']); ?>
                                </h3>

                                <p class="package-details">
                                    <?php echo e($item['details']); ?>
                                </p>

                                <div class="package-bottom">

                                    <div class="package-price">

                                        <small>Package price</small>

                                        <strong>
                                            Rs.
                                            <?php echo number_format((float)$item['price'], 2); ?>
                                        </strong>

                                    </div>

                                    <a
                                        class="package-book"
                                        href="booking.php?id=<?php echo (int)$item['id']; ?>"
                                    >
                                        Book Now ↗
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <!-- NO RESULTS -->
                <div class="packages-empty">

                    <div aria-hidden="true">⌕</div>

                    <h3>No packages found</h3>

                    <p>
                        Try another destination or search keyword.
                    </p>

                    <a class="btn btn-primary"
                       href="packages.php">
                        View All Packages →
                    </a>

                </div>

            <?php endif; ?>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>

                <nav
                    class="packages-pagination"
                    aria-label="Package pages"
                >

                    <?php if ($page > 1): ?>

                        <a href="<?php echo e(pageUrl($page - 1, $search)); ?>">
                            ← Previous
                        </a>

                    <?php endif; ?>

                    <span>
                        Page <?php echo $page; ?>
                        of <?php echo $totalPages; ?>
                    </span>

                    <?php if ($page < $totalPages): ?>

                        <a href="<?php echo e(pageUrl($page + 1, $search)); ?>">
                            Next →
                        </a>

                    <?php endif; ?>

                </nav>

            <?php endif; ?>

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
            <a href="index.php#destinations">Destinations</a>
            <a href="packages.php">Tour Packages</a>
            <a href="contact.php">Contact Us</a>

        </div>

        <div class="footer-links">

            <h4>Your Account</h4>

            <?php if ($isLoggedIn): ?>

                <?php if ($userRole === 'admin'): ?>

                    <a href="admin/dashboard.php">Dashboard</a>

                <?php elseif ($userRole === 'staff'): ?>

                    <a href="staff/dashboard.php">Dashboard</a>

                <?php else: ?>

                    <a href="my_bookings.php">My Bookings</a>
                    <a href="update_profile.php">Profile</a>

                <?php endif; ?>

                <a href="logout.php">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>
                <a href="register.php">Register</a>

            <?php endif; ?>

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

<?php $stmt->close(); ?>

</body>
</html>
