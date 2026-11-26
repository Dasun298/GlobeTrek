<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
function ce($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
if (empty($_SESSION['contact_csrf'])) $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
$data = ['name'=>'','email'=>'','subject'=>'','message'=>''];
$error = '';
$success = (string)($_SESSION['contact_success'] ?? '');
unset($_SESSION['contact_success']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) $data[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['contact_csrf'], $token)) $error = 'Session expired. Reload this page and try again.';
    elseif (strlen($data['name']) < 2 || strlen($data['name']) > 100) $error = 'Enter a name of 2–100 characters.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100) $error = 'Enter a valid email address (up to 100 characters).';
    elseif (strlen($data['subject']) < 3 || strlen($data['subject']) > 255) $error = 'Enter a subject of 3–255 characters.';
    elseif (strlen($data['message']) < 10 || strlen($data['message']) > 5000) $error = 'Message should contain 10–5000 characters.';
    else {
        try {
            $stmt = $conn->prepare('INSERT INTO inquiries (name,email,subject,message) VALUES (?,?,?,?)');
            $stmt->bind_param('ssss',$data['name'],$data['email'],$data['subject'],$data['message']);
            $stmt->execute(); $stmt->close();
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['contact_success'] = 'We received your inquiry. Our team can review it from the staff/admin panel.';
            header('Location: contact.php', true, 303); exit;
        } catch (Throwable $ex) {
            error_log('Contact inquiry error: '.$ex->getMessage());
            $error = 'Could not save your inquiry. Please try again.';
        }
    }
}
$isLoggedIn = isset($_SESSION['user_id']);
$role = (string)($_SESSION['role'] ?? 'user');
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Get in touch with the GlobeTrek Adventures travel team.">
<title>Contact GlobeTrek | Travel Adventures</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/home.css"><link rel="stylesheet" href="css/contact.css">
<script src="js/home.js" defer></script></head><body>
<a href="#contactMain" class="skip-link">Skip to contact form</a>
<header class="site-header"><div class="container navbar">
<a href="index.php" class="logo"><span class="logo-symbol">✦</span>Globe<span>Trek</span><small>ADVENTURES</small></a>
<button type="button" class="menu-toggle" id="menuToggle" aria-label="Open navigation menu" aria-controls="mainNav" aria-expanded="false">☰</button>
<nav class="nav-links" id="mainNav" aria-label="Main navigation">
<a href="index.php">Home</a><a href="index.php#destinations">Destinations</a><a href="packages.php">Tour Packages</a><a href="contact.php" class="active" aria-current="page">Contact</a>
<?php if ($isLoggedIn): ?>
<?php if ($role === 'admin'): ?><a href="admin/dashboard.php">Admin Panel</a>
<?php elseif ($role === 'staff'): ?><a href="staff/dashboard.php">Staff Panel</a>
<?php else: ?><a href="my_bookings.php">My Bookings</a><a href="update_profile.php">Profile</a><?php endif; ?>
<a class="nav-button" href="logout.php">Logout</a>
<?php else: ?><a href="login.php">Login</a><a class="nav-button" href="register.php">Get Started ↗</a><?php endif; ?>
</nav></div></header>
<main id="contactMain"><section class="contact-hero"><div class="container">
<p class="hero-label">✦ HERE TO HELP</p><h1>Let's Talk <em>Travel.</em></h1>
<p>Questions about an adventure, a package or a booking? Send the team a message.</p>
</div></section>
<section class="contact-section"><div class="container contact-layout">
<aside class="contact-info"><span class="section-label">PLAN YOUR NEXT ADVENTURE</span>
<h2>We're here to <em>help.</em></h2><p>Share your questions or travel ideas. Your inquiry is stored securely for staff to review.</p>
<div class="contact-info-card"><strong>Tour packages</strong><p>Browse our Sri Lankan travel packages and choose a destination that inspires you.</p><a href="packages.php">Explore Packages ↗</a></div>
<div class="contact-info-card"><strong>Your bookings</strong><p>Signed-in customers can check the status of their booking requests at any time.</p><a href="my_bookings.php">My Bookings ↗</a></div></aside>
<div class="contact-form-card"><span class="section-label">SEND AN INQUIRY</span><h2>Get in touch.</h2>
<p>Fill in the form and we'll keep your message in our inquiry dashboard.</p>
<?php if ($success): ?><div class="contact-alert success" role="status"><?= ce($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="contact-alert error" role="alert"><?= ce($error) ?></div><?php endif; ?>
<form method="post" action="contact.php" class="contact-form">
<input type="hidden" name="csrf_token" value="<?= ce($_SESSION['contact_csrf']) ?>">
<label>Full name <span>*</span><input name="name" maxlength="100" autocomplete="name" value="<?= ce($data['name']) ?>" placeholder="Your full name" required></label>
<label>Email address <span>*</span><input type="email" name="email" maxlength="100" autocomplete="email" value="<?= ce($data['email']) ?>" placeholder="you@example.com" required></label>
<label>Subject <span>*</span><input name="subject" maxlength="255" value="<?= ce($data['subject']) ?>" placeholder="How can we help?" required></label>
<label>Message <span>*</span><textarea name="message" maxlength="5000" rows="6" placeholder="Tell us more about your inquiry..." required><?= ce($data['message']) ?></textarea></label>
<button class="btn btn-primary contact-submit" type="submit">Send Inquiry ↗</button>
</form><p class="contact-footnote">This demo saves your inquiry to the database; it does not automatically send an email.</p></div>
</div></section></main>
<footer class="site-footer"><div class="container footer-main"><div class="footer-about"><a href="index.php" class="logo"><span class="logo-symbol">✦</span>Globe<span>Trek</span><small>ADVENTURES</small></a><p>Explore beyond boundaries. Make every journey count.</p></div><div class="footer-links"><h4>Explore</h4><a href="index.php">Home</a><a href="packages.php">Tour Packages</a><a href="contact.php">Contact Us</a></div><div class="footer-links"><h4>Account</h4><a href="login.php">Login</a><a href="my_bookings.php">My Bookings</a></div></div><div class="container footer-bottom"><p>© <?= date('Y') ?> GlobeTrek Adventures. All rights reserved.</p><p>Made for the love of travel.</p></div></footer>
</body></html>
