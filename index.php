
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$username = (string) ($_SESSION['username'] ?? '');
$userRole = (string) ($_SESSION['role'] ?? '');

function homeEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Explore amazing destinations and book unforgettable travel experiences with GlobeTrek Adventures.">

    <title>GlobeTrek Adventures | Explore the World</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="css/home.css">
    <script src="js/home.js" defer></script>
</head>

<body>

<a class="skip-link" href="#mainContent">Skip to content</a>

<!-- NAVIGATION -->
<header class="site-header">

    <div class="container navbar">

        <a href="index.php" class="logo">
            <span class="logo-symbol">✦</span>
            Globe<span>Trek</span>
            <small>ADVENTURES</small>
        </a>

        <button class="menu-toggle"
                id="menuToggle"
                type="button"
                aria-label="Open navigation menu"
                aria-controls="mainNav"
                aria-expanded="false">
            ☰
        </button>

        <nav class="nav-links" id="mainNav" aria-label="Main navigation">

            <a href="index.php" class="active">Home</a>
            <a href="#destinations">Destinations</a>
            <a href="packages.php">Tour Packages</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>

            <?php if ($isLoggedIn): ?>

                <?php if ($userRole === 'admin'): ?>
                    <a href="admin/dashboard.php">Admin Panel</a>

                <?php elseif ($userRole === 'staff'): ?>
                    <a href="staff/dashboard.php">Staff Panel</a>

                <?php else: ?>
                    <a href="my_bookings.php">My Bookings</a>
                    <a href="update_profile.php">Profile</a>
                <?php endif; ?>

                <a href="logout.php" class="nav-button">Logout</a>

            <?php else: ?>

                <a href="login.php">Login</a>
                <a href="register.php" class="nav-button">
                    Get Started ↗
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<main id="mainContent">

    <!-- HERO SECTION -->
    <section class="hero">

        <div class="container hero-content">

            <p class="hero-label">
                ✦ YOUR NEXT ADVENTURE STARTS HERE
            </p>

            <?php if ($isLoggedIn && $username !== ''): ?>

                <p class="welcome-text">
                    Welcome back,
                    <?php echo homeEscape($username); ?>!
                </p>

            <?php endif; ?>

            <h1>
                The World Is Yours <br>
                To <span>Explore.</span>
            </h1>

            <p class="hero-description">
                Discover breathtaking destinations, unforgettable
                journeys, and beautiful experiences with
                GlobeTrek Adventures.
            </p>

            <div class="hero-buttons">

                <a href="packages.php" class="btn btn-primary">
                    Explore Packages ↗
                </a>

                <a href="#destinations" class="btn btn-outline">
                    Discover Destinations ↓
                </a>

            </div>

        </div>

        <div class="hero-bottom container">
            <span>EXPLORE MORE. EXPERIENCE MORE.</span>
            <a href="#searchTrips">SCROLL TO DISCOVER ↓</a>
        </div>

    </section>

    <!-- SEARCH SECTION -->
    <section class="search-section" id="searchTrips">

        <div class="container">

            <form action="packages.php"
                  method="GET"
                  class="search-box">

                <div class="search-title">
                    <div class="search-icon">⌕</div>

                    <div>
                        <h3>Where to next?</h3>
                        <p>Find your perfect getaway</p>
                    </div>
                </div>

                <div class="search-input">

                    <label for="destination">
                        DESTINATION
                    </label>

                    <input type="search"
                           id="destination"
                           name="search"
                           placeholder="Search Ella, Galle, Yala..."
                           maxlength="100"
                           required>

                </div>

                <button type="submit" class="btn btn-primary">
                    Search Trips →
                </button>

            </form>

        </div>

    </section>

    <!-- DESTINATIONS SECTION -->
    <section class="destinations-section section-padding"
             id="destinations">

        <div class="container">

            <div class="section-heading">

                <div>
                    <p class="section-label">THE WORLD IS WAITING</p>

                    <h2>
                        Discover Your Next
                        <span>Escape.</span>
                    </h2>

                    <p>
                        Explore beautiful destinations and create
                        unforgettable memories on every journey.
                    </p>
                </div>

                <a href="packages.php" class="view-link">
                    View All Packages ↗
                </a>

            </div>

            <div class="destination-grid">

                <a href="packages.php?search=Ella"
                   class="destination-card card-ella">

                    <span class="destination-tag">
                        NATURE & ADVENTURE
                    </span>

                    <div class="destination-info">
                        <h3>Ella</h3>
                        <p>Sri Lanka · Explore Destination ↗</p>
                    </div>

                </a>

                <a href="packages.php?search=Galle"
                   class="destination-card card-galle">

                    <span class="destination-tag">
                        HISTORY & CULTURE
                    </span>

                    <div class="destination-info">
                        <h3>Galle</h3>
                        <p>Sri Lanka · Explore Destination ↗</p>
                    </div>

                </a>

                <a href="packages.php?search=Yala"
                   class="destination-card card-yala">

                    <span class="destination-tag">
                        WILDLIFE & SAFARI
                    </span>

                    <div class="destination-info">
                        <h3>Yala</h3>
                        <p>Sri Lanka · Explore Destination ↗</p>
                    </div>

                </a>

            </div>

        </div>

    </section>

    <!-- SERVICES -->
    <section class="services-section section-padding">

        <div class="container">

            <div class="section-center">

                <p class="section-label">TRAVEL MADE SIMPLE</p>

                <h2>
                    Every Journey,
                    <span>Made Memorable.</span>
                </h2>

                <p>
                    Everything you need to plan your next
                    unforgettable travel experience.
                </p>

            </div>

            <div class="services-grid">

                <div class="service-card">

                    <div class="service-icon">✈</div>

                    <h3>Tour Packages</h3>

                    <p>
                        Discover exciting travel packages
                        and explore amazing destinations.
                    </p>

                </div>

                <div class="service-card">

                    <div class="service-icon">⌂</div>

                    <h3>Hotel Reservations</h3>

                    <p>
                        Find comfortable accommodation
                        options for your perfect holiday.
                    </p>

                </div>

                <div class="service-card">

                    <div class="service-icon">♡</div>

                    <h3>Easy Bookings</h3>

                    <p>
                        Book your trips and manage your
                        reservations in one convenient place.
                    </p>

                </div>

            </div>

        </div>

    </section>

    <!-- ABOUT SECTION -->
    <section class="about-section section-padding"
             id="about">

        <div class="container about-grid">

            <div class="about-image"
                 role="img"
                 aria-label="Beautiful tropical coastline in Sri Lanka">
            </div>

            <div class="about-content">

                <p class="section-label">OUR STORY</p>

                <h2>
                    Not Just Places.
                    <span>Extraordinary Memories.</span>
                </h2>

                <p>
                    GlobeTrek Adventures is a travel and tourism
                    company based in Negombo, Sri Lanka.

                    Our goal is to make travel planning simple,
                    convenient, and enjoyable.
                </p>

                <p>
                    We help travelers discover exciting tour
                    packages, accommodation options, and
                    unforgettable experiences through one
                    user-friendly online platform.
                </p>

                <a href="contact.php" class="btn btn-dark">
                    Contact Us ↗
                </a>

            </div>

        </div>

    </section>

    <!-- CALL TO ACTION -->
    <section class="cta-section">

        <div class="container cta-content">

            <p class="hero-label">READY FOR SOMETHING NEW?</p>

            <h2>
                Your Next Great Story
                <br>
                Starts With A Journey.
            </h2>

            <a href="packages.php" class="btn btn-white">
                Explore All Packages ↗
            </a>

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

            <h4>Quick Links</h4>

            <a href="index.php">Home</a>
            <a href="packages.php">Tour Packages</a>
            <a href="#destinations">Destinations</a>
            <a href="contact.php">Contact Us</a>

        </div>

        <div class="footer-links">

            <h4>Account</h4>

            <?php if ($isLoggedIn): ?>

                <a href="logout.php">Logout</a>

                <?php if ($userRole === 'admin'): ?>
                    <a href="admin/dashboard.php">Dashboard</a>
                <?php elseif ($userRole === 'staff'): ?>
                    <a href="staff/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="my_bookings.php">My Bookings</a>
                    <a href="update_profile.php">My Profile</a>
                <?php endif; ?>

            <?php else: ?>

                <a href="login.php">Login</a>
                <a href="register.php">Register</a>

            <?php endif; ?>

        </div>

    </div>

    <div class="container footer-bottom">

        <p>
            &copy; <?php echo date('Y'); ?>
            GlobeTrek Adventures. All Rights Reserved.
        </p>

        <p>Made for the love of travel.</p>

    </div>

</footer>

</body>
</html>
