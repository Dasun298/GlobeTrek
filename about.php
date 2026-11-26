<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = (string) ($_SESSION['role'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Get to know GlobeTrek Adventures and discover a more inspiring way to explore Sri Lanka.">
    <title>About Us | GlobeTrek Adventures</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/about.css">
    <script src="js/home.js" defer></script>
</head>
<body>
<a class="skip-link" href="#aboutMain">Skip to content</a>

<header class="site-header">
    <div class="container navbar">
        <a href="index.php" class="logo">
            <span class="logo-symbol">✦</span>
            Globe<span>Trek</span>
            <small>ADVENTURES</small>
        </a>

        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open navigation menu" aria-controls="mainNav" aria-expanded="false">
            ☰
        </button>

        <nav class="nav-links" id="mainNav" aria-label="Main navigation">
            <a href="index.php">Home</a>
            <a href="index.php#destinations">Destinations</a>
            <a href="packages.php">Tour Packages</a>
            <a href="about.php" class="active" aria-current="page">About</a>
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
                <a href="register.php" class="nav-button">Get Started ↗</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="aboutMain">
    <section class="about-hero">
        <div class="container about-hero-content">
            <p class="hero-label">✦ THE STORY BEHIND THE JOURNEY</p>
            <h1>Travel with <em>meaning.</em></h1>
            <p>We believe the best journeys bring you closer to the places, people, and moments that make exploring unforgettable.</p>
        </div>
    </section>

    <section class="about-story">
        <div class="container about-story-grid">
            <div class="about-story-copy">
                <span class="section-label">A LITTLE ABOUT US</span>
                <h2>More than a place on the map.</h2>
                <p>GlobeTrek Adventures is here to make discovering Sri Lanka feel simple, inspiring, and personal. From the first spark of an idea to the journey itself, we bring destinations and travel experiences together in one place.</p>
                <p>Whether you are dreaming of a quiet escape, a new landscape, or a trip shared with the people you love, your next story can start right here.</p>
                <a href="packages.php" class="btn btn-primary">Explore Tour Packages ↗</a>
            </div>
            <div class="about-story-note">
                <span class="about-note-symbol" aria-hidden="true">✦</span>
                <p>Explore beyond boundaries.<br>Make every journey count.</p>
                <span class="about-note-caption">GLOBETREK ADVENTURES</span>
            </div>
        </div>
    </section>

    <section class="about-values">
        <div class="container">
            <div class="about-values-heading">
                <span class="section-label">THE WAY WE SEE TRAVEL</span>
                <h2>Every journey has a story.</h2>
                <p>We make it easier to find the next chapter that feels like yours.</p>
            </div>
            <div class="about-value-grid">
                <article class="about-value-card">
                    <span class="about-value-number">01</span>
                    <h3>Find your inspiration</h3>
                    <p>Explore destinations and discover experiences that turn “someday” into a plan.</p>
                </article>
                <article class="about-value-card">
                    <span class="about-value-number">02</span>
                    <h3>Make planning simpler</h3>
                    <p>Browse tour packages and booking details in one convenient place.</p>
                </article>
                <article class="about-value-card">
                    <span class="about-value-number">03</span>
                    <h3>Travel your way</h3>
                    <p>Choose the places and moments that make a trip meaningful to you.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="about-cta">
        <div class="container about-cta-content">
            <div>
                <span class="section-label">YOUR NEXT ADVENTURE AWAITS</span>
                <h2>Ready to explore?</h2>
                <p>Take a look at the places and experiences waiting for you.</p>
            </div>
            <a href="packages.php" class="btn btn-primary">Find Your Tour ↗</a>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-main">
        <div class="footer-about">
            <a href="index.php" class="logo"><span class="logo-symbol">✦</span>Globe<span>Trek</span><small>ADVENTURES</small></a>
            <p>Explore beyond boundaries. Make every journey count.</p>
        </div>
        <div class="footer-links">
            <h4>Explore</h4>
            <a href="index.php">Home</a>
            <a href="packages.php">Tour Packages</a>
            <a href="about.php" aria-current="page">About Us</a>
            <a href="contact.php">Contact Us</a>
        </div>
        <div class="footer-links">
            <h4>Account</h4>
            <a href="login.php">Login</a>
            <a href="my_bookings.php">My Bookings</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>© <?= date('Y') ?> GlobeTrek Adventures. All rights reserved.</p>
        <p>Made for the love of travel.</p>
    </div>
</footer>
</body>
</html>
