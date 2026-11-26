<?php
// GlobeTrek Admin - common authentication, database, layout and utility functions.
// UTF-8 without BOM; PHP 8.1+ / MySQLi (XAMPP).
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php', true, 302);
    exit;
}
require_once __DIR__ . '/../includes/db.php';
// Deleted administrators must not retain access through an old session.
$activeId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if (!$activeId || $activeId < 1) {
    unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['username']);
    header('Location: ../login.php'); exit;
}
$authCheck = $conn->prepare('SELECT username FROM admins WHERE id = ? LIMIT 1');
$authCheck->bind_param('i', $activeId);
$authCheck->execute();
$activeAdmin = $authCheck->get_result()->fetch_assoc();
$authCheck->close();
if (!$activeAdmin) {
    unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['username']);
    header('Location: ../login.php'); exit;
}
$_SESSION['username'] = (string)$activeAdmin['username'];
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: private, no-store');
header('Referrer-Policy: strict-origin-when-cross-origin');

function h($x): string { return htmlspecialchars((string)($x ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function postv(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
function getv(string $key): string { return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : ''; }
function recid($id): int { $n = filter_var($id, FILTER_VALIDATE_INT); return ($n !== false && $n > 0) ? (int)$n : 0; }
function stmt(string $query, string $types = '', array $params = []): mysqli_stmt {
    global $conn;
    $st = $conn->prepare($query);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    return $st;
}
function fetch_all_db(string $sql, string $types = '', array $params = []): array {
    $st = stmt($sql, $types, $params);
    $res = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $res;
}
function fetch_one_db(string $sql, string $types = '', array $params = []): ?array {
    $st = stmt($sql, $types, $params);
    $res = $st->get_result()->fetch_assoc();
    $st->close();
    return $res ?: null;
}
function scalar_db(string $sql, string $types = '', array $params = []) {
    $st = stmt($sql, $types, $params);
    $res = $st->get_result()->fetch_row()[0] ?? 0;
    $st->close();
    return $res;
}
function exec_db(string $sql, string $types = '', array $params = []): int {
    $st = stmt($sql, $types, $params);
    $n = $st->affected_rows;
    $st->close();
    return $n;
}
function flash(string $text, string $kind = 'success'): void { $_SESSION['gt_admin_flash'] = [$text, $kind]; }
function redirect_to(string $path): void { header('Location: ' . $path, true, 303); exit; }
function admin_error(Throwable $e, string $target): void {
    if (!($e instanceof RuntimeException)) { error_log('GlobeTrek Admin: ' . $e->getMessage()); }
    flash($e instanceof RuntimeException ? $e->getMessage() : 'Could not complete the database action. Check the XAMPP PHP error log.', 'error');
    redirect_to($target);
}
function csrf(): string {
    if (empty($_SESSION['gt_admin_csrf'])) { $_SESSION['gt_admin_csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['gt_admin_csrf'];
}
function csrf_field(): void { echo '<input type="hidden" name="csrf_token" value="' . h(csrf()) . '">'; }
function require_admin_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf(), postv('csrf_token'))) {
        throw new RuntimeException('Invalid security token. Refresh the page and try again.');
    }
}
function status_badge($value): string {
    $status = trim((string)($value ?? ''));
    $class = strtolower($status);
    $valid = ['pending','confirmed','approved','paid','completed','cancelled','available','unavailable','ready','reserved','booked'];
    if (!in_array($class, $valid, true)) $class = 'other';
    return '<span class="status-pill status-' . $class . '">' . h($status ?: 'Unknown') . '</span>';
}
function money($value): string {
    // Legacy transportation prices contain both numeric and "Rs. 5000" strings.
    $clean = preg_replace('/[^\d.]/', '', (string)$value);
    if ($clean === '' || !is_numeric($clean)) return 'Not set';
    return 'Rs. ' . number_format((float)$clean, 2);
}
function admin_image_url($filename, bool $hotel = false): string {
    $file = basename(str_replace('\\', '/', (string)($filename ?? '')));
    if ($file === '' || !preg_match('/^[a-zA-Z0-9_.-]+$/', $file)) return '';
    $paths = $hotel ? ['uploads/hotels/', 'uploads/'] : ['uploads/'];
    foreach ($paths as $dir) {
        if (is_file(__DIR__ . '/../' . $dir . $file)) return '../' . $dir . rawurlencode($file);
    }
    return '';
}
function save_image_upload(string $key, bool $hotel = false): ?string {
    if (!isset($_FILES[$key]) || ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$key];
    if (!is_array($f) || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Image upload failed. Choose an image and retry.');
    }
    if ($f['size'] <= 0 || $f['size'] > 5 * 1024 * 1024) throw new RuntimeException('Image must be 5 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif'];
    if (!isset($exts[$mime])) throw new RuntimeException('Upload a JPG, PNG, WEBP or AVIF image.');
    if (!@getimagesize($f['tmp_name'])) throw new RuntimeException('The uploaded file is not a valid image.');
    $name = bin2hex(random_bytes(12)) . '.' . $exts[$mime];
    $directory = __DIR__ . '/../uploads/' . ($hotel ? 'hotels/' : '');
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('Image directory is missing or not writable.');
    if (!move_uploaded_file($f['tmp_name'], $directory . $name)) throw new RuntimeException('Could not save the uploaded image.');
    return $name;
}
function action_button(int $id, string $action, string $label, string $tone = 'good', string $question = ''): void {
    echo '<form method="post" class="inline-form" data-confirm="' . h($question ?: ('Continue with ' . $label . '?')) . '">';
    csrf_field();
    echo '<input type="hidden" name="id" value="' . $id . '">';
    echo '<input type="hidden" name="action" value="' . h($action) . '">';
    echo '<button class="btn btn-' . h($tone) . ' btn-sm" type="submit">' . h($label) . '</button></form>';
}
function page_start(string $title, string $active = 'dashboard', string $description = ''): void {
    $name = (string)($_SESSION['username'] ?? 'Administrator');
    $flashMessage = $_SESSION['gt_admin_flash'] ?? null;
    unset($_SESSION['gt_admin_flash']);
    $links = [
        ['dashboard', '◫', 'Dashboard', 'dashboard.php'],
        ['bookings', '▤', 'Tour Bookings', 'bookings.php'],
        ['hotel_bookings', '⌂', 'Hotel Bookings', 'hotel_bookings.php'],
        ['packages', '✈', 'Tour Packages', 'catalog.php?type=packages'],
        ['hotels', '⌂', 'Hotels', 'catalog.php?type=hotels'],
        ['transport', '🚐', 'Hotel Transport', 'catalog.php?type=transport'],
        ['transportation', '↗', 'Tour Transportation', 'catalog.php?type=transportation'],
        ['users', '♙', 'Customers', 'manage_users.php'],
        ['staff', '♧', 'Staff Accounts', 'staff_accounts.php'],
        ['payments', '▣', 'Payments', 'payments.php'],
        ['inquiries', '✉', 'Inquiries', 'inquiries.php'],
        ['reports', '▥', 'Reports', 'reports.php'],
        ['analytics', '▥', 'Analytics', 'analytics.php'],
        ['security', '♢', 'Security', 'security.php'],
        ['backup', '↧', 'Backup Guide', 'backup.php'],
    ];
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= h($title) ?> | GlobeTrek Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/admin-dashboard.css?v=20261112">
<script src="../js/admin-dashboard.js?v=20261112" defer></script>
</head>
<body>
<a class="skip-link" href="#content">Skip to content</a>
<div class="admin-shell">
<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin sidebar">
  <a href="dashboard.php" class="admin-brand"><span class="brand-icon">✦</span> Globe<span>Trek</span><small>ADMINISTRATION</small></a>
  <p class="nav-label">PLATFORM MANAGEMENT</p>
  <nav class="sidebar-links" aria-label="Admin navigation">
    <?php foreach ($links as [$key,$icon,$label,$url]): ?>
      <a href="<?= h($url) ?>" class="<?= $active === $key ? 'active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><span class="side-icon" aria-hidden="true"><?= h($icon) ?></span><?= h($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer"><a href="../index.php">← View Website</a><a href="../logout.php">Sign Out ↗</a></div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
<div class="admin-main-wrap">
<header class="admin-topbar"><button class="menu-button" id="sidebarToggle" type="button" aria-controls="adminSidebar" aria-expanded="false" aria-label="Open admin menu">☰</button><div class="breadcrumb">GlobeTrek <span>/</span> <strong><?= h($title) ?></strong></div><div class="admin-identity"><span class="avatar"><?= h(strtoupper(substr($name,0,1))) ?></span><span><?= h($name) ?><small>Administrator</small></span></div></header>
<main class="admin-content" id="content">
<div class="page-heading"><div><p class="eyebrow">✦ GLOBETREK CONTROL CENTER</p><h1><?= h($title) ?><em>.</em></h1><p><?= h($description ?: 'Manage your travel platform with confidence.') ?></p></div></div>
<?php if ($flashMessage): ?><div class="manager-alert <?= $flashMessage[1] === 'error' ? 'manager-error' : 'manager-success' ?>" role="status"><?= h($flashMessage[0]) ?></div><?php endif; ?>
<?php
}
function page_end(): void { ?>
</main><footer class="admin-footer">© <?= date('Y') ?> GlobeTrek Adventures · Administration</footer>
</div></div></body></html>
<?php }
