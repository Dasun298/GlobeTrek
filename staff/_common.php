<?php
// Shared Staff authorization, database helpers, layout and CSRF protection.
// UTF-8 without BOM. PHP 8.1+, MySQLi on XAMPP.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header('Location: ../login.php', true, 302);
    exit;
}
require_once __DIR__ . '/../includes/db.php';
// Re-check staff account on every request; deleting a staff account revokes its old session.
$staffSessionId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($staffSessionId === false || $staffSessionId < 1) {
    unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['username']);
    header('Location: ../login.php'); exit;
}
$checkStaff = $conn->prepare('SELECT username FROM staff WHERE id = ? LIMIT 1');
$checkStaff->bind_param('i', $staffSessionId);
$checkStaff->execute();
$validStaff = $checkStaff->get_result()->fetch_assoc();
$checkStaff->close();
if (!$validStaff) {
    unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['username']);
    header('Location: ../login.php'); exit;
}
$_SESSION['username'] = (string)$validStaff['username'];
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: private, no-store');
header('Referrer-Policy: strict-origin-when-cross-origin');

function sh($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function spost(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
function sget(string $key): string { return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : ''; }
function sid($value): int { $n = filter_var($value, FILTER_VALIDATE_INT); return ($n !== false && $n > 0) ? (int)$n : 0; }
function squery(string $sql, string $types = '', array $args = []): mysqli_stmt {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$args);
    $stmt->execute();
    return $stmt;
}
function srows(string $sql, string $types = '', array $args = []): array {
    $stmt = squery($sql,$types,$args);
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}
function sone(string $sql, string $types = '', array $args = []): ?array {
    $stmt = squery($sql,$types,$args);
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ?: null;
}
function snumber(string $sql, string $types = '', array $args = []): float {
    $stmt = squery($sql,$types,$args);
    $v = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close(); return (float)$v;
}
function sexec(string $sql,string $types = '',array $args = []): int {
    $stmt = squery($sql,$types,$args);
    $n = $stmt->affected_rows; $stmt->close(); return $n;
}
function sflash(string $message, string $kind='success'): void { $_SESSION['gt_staff_flash'] = [$message,$kind]; }
function sredirect(string $url): void { header('Location: ' . $url, true, 303); exit; }
function sfail(Throwable $e, string $target): void {
    if (!($e instanceof RuntimeException)) error_log('GlobeTrek Staff: ' . $e->getMessage());
    sflash($e instanceof RuntimeException ? $e->getMessage() : 'Could not complete database action. Check XAMPP PHP error log.', 'error');
    sredirect($target);
}
function stoken(): string {
    if (empty($_SESSION['gt_staff_csrf'])) $_SESSION['gt_staff_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['gt_staff_csrf'];
}
function stoken_field(): void { echo '<input type="hidden" name="csrf_token" value="' . sh(stoken()) . '">'; }
function srequire_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(stoken(),spost('csrf_token'))) {
        throw new RuntimeException('Session expired. Refresh the page before submitting.');
    }
}
function sbadge($value): string {
    $s = trim((string)$value); $key = strtolower($s);
    $allowed = ['pending','confirmed','completed','cancelled','approved','available','unavailable','ready','reserved','booked','paid'];
    $key = in_array($key,$allowed,true) ? $key : 'other';
    return '<span class="status-pill status-' . sh($key) . '">' . sh($s ?: 'Unknown') . '</span>';
}
function smoney($value): string {
    $clean = preg_replace('/[^\d.]/', '', (string)$value);
    if ($clean === '' || !is_numeric($clean)) return 'Not set';
    return 'Rs. ' . number_format((float)$clean,2);
}
function svalid_price(string $price): string {
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) || (float)$price <= 0) {
        throw new RuntimeException('Enter a price above zero (up to 2 decimal places).');
    }
    return number_format((float)$price,2,'.','');
}
function svalid_text(string $value, string $label, int $max = 100, bool $allowEmpty=false): string {
    if ((!$allowEmpty && $value === '') || strlen($value) > $max) {
        throw new RuntimeException($label . ' is required (maximum ' . $max . ' characters).');
    }
    return $value;
}
function simage_url($filename,bool $hotel=false): string {
    $f = basename(str_replace('\\','/',(string)($filename ?? '')));
    if ($f === '' || !preg_match('/^[A-Za-z0-9_.-]+$/D',$f)) return '';
    $dirs = $hotel ? ['uploads/hotels/','uploads/'] : ['uploads/'];
    foreach ($dirs as $dir) if (is_file(__DIR__ . '/../' . $dir . $f)) return '../' . $dir . rawurlencode($f);
    return '';
}
function supload_image(string $field, bool $hotel=false): ?string {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if (!is_array($f) || ($f['error'] ?? -1) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Image upload failed. Choose a valid file.');
    }
    if ($f['size'] <= 0 || $f['size'] > 5 * 1024 * 1024) throw new RuntimeException('Image must be 5 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/avif'=>'avif'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Only JPG, PNG, WEBP and AVIF files are supported.');
    if (!@getimagesize($f['tmp_name'])) throw new RuntimeException('The uploaded file is not a valid image.');
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/' . ($hotel ? 'hotels/' : '');
    if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Could not create image folder.');
    if (!move_uploaded_file($f['tmp_name'],$dir . $name)) throw new RuntimeException('Failed to store image.');
    return $name;
}
function sconfirm_action(int $id, string $action, string $label, string $color='good', string $question='Continue with this action?'): void {
    echo '<form method="post" class="inline-form" data-confirm="'.sh($question).'">';
    stoken_field();
    echo '<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="'.sh($action).'">';
    echo '<button type="submit" class="btn btn-'.sh($color).' btn-sm">'.sh($label).'</button></form>';
}
function stour_action(int $id, string $action): void {
    global $conn;
    $statuses = ['confirm'=>['pending','Confirmed'], 'complete'=>['confirmed','Completed'], 'cancel'=>['pending','Cancelled']];
    if (!$id || !isset($statuses[$action])) throw new RuntimeException('Invalid booking action.');
    [$from,$to] = $statuses[$action];
    $transaction=false;
    try {
        $conn->begin_transaction(); $transaction=true;
        $booking = sone('SELECT status FROM bookings WHERE id=? FOR UPDATE','i',[$id]);
        if (!$booking || strtolower(trim((string)$booking['status'])) !== $from) {
            throw new RuntimeException('Booking status has changed. Refresh and retry.');
        }
        if ($action === 'cancel') {
            $paid = snumber("SELECT COUNT(*) FROM payments WHERE booking_id=? AND LOWER(TRIM(status)) IN ('paid','approved','completed','successful')",'i',[$id]);
            if ($paid) throw new RuntimeException('This booking has a payment record. Contact an Admin before cancellation.');
        }
        $changed = sexec('UPDATE bookings SET status=? WHERE id=? AND LOWER(TRIM(status))=?','sis',[$to,$id,$from]);
        if ($changed !== 1) throw new RuntimeException('Could not update the booking.');
        if ($action === 'cancel') {
            sexec("UPDATE payments SET status='Cancelled' WHERE booking_id=? AND LOWER(TRIM(status))='pending'",'i',[$id]);
        }
        $conn->commit(); $transaction=false;
    } catch (Throwable $e) { if ($transaction) $conn->rollback(); throw $e; }
}
function sstart(string $title,string $active='dashboard',string $description=''): void {
    $display=(string)($_SESSION['username'] ?? 'Staff');
    $flash=$_SESSION['gt_staff_flash'] ?? null;
    unset($_SESSION['gt_staff_flash']);
    $menu=[
       ['dashboard','◫','Dashboard','dashboard.php'],
       ['packages','✈','Tour Packages','packages.php'],
       ['bookings','▤','Tour Bookings','bookings.php'],
       ['hotel_bookings','⌂','Hotel Bookings','hotel_bookings.php'],
       ['customers','♙','Customers','customers.php'],
       ['hotels','⌂','Hotels','hotels.php'],
       ['hotel_transport','🚐','Hotel Transport','hotel_transport.php'],
       ['transport','↗','Tour Transportation','transport.php'],
       ['inquiries','✉','Inquiries','inquiries.php'],
       ['reports','▥','Reports','reports.php'],
       ['profile','♧','My Account','profile.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= sh($title) ?> | GlobeTrek Staff</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/staff-dashboard.css?v=20261112"><script src="../js/staff-dashboard.js?v=20261112" defer></script>
</head><body><a class="skip-link" href="#content">Skip to content</a>
<div class="admin-shell">
<aside class="admin-sidebar" id="adminSidebar" aria-label="Staff menu">
  <a href="dashboard.php" class="admin-brand"><span class="brand-icon">✦</span> Globe<span>Trek</span><small>STAFF OPERATIONS</small></a>
  <p class="nav-label">STAFF WORKSPACE</p>
  <nav class="sidebar-links" aria-label="Staff navigation">
  <?php foreach($menu as [$key,$symbol,$label,$href]): ?>
  <a href="<?= sh($href) ?>" class="<?= $key===$active ? 'active':'' ?>" <?= $key===$active?'aria-current="page"':'' ?>><span class="side-icon" aria-hidden="true"><?= sh($symbol) ?></span><?= sh($label) ?></a>
  <?php endforeach; ?></nav>
  <div class="sidebar-footer"><a href="../index.php">← Visit Website</a><a href="../logout.php">Sign Out ↗</a></div>
</aside><div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
<div class="admin-main-wrap"><header class="admin-topbar">
<button class="menu-button" id="sidebarToggle" type="button" aria-controls="adminSidebar" aria-expanded="false" aria-label="Open staff menu">☰</button>
<div class="breadcrumb">GlobeTrek <span>/</span> <strong><?= sh($title) ?></strong></div>
<div class="admin-identity"><span class="avatar"><?= sh(strtoupper(substr($display,0,1))) ?></span><span><?= sh($display) ?><small>Staff Member</small></span></div>
</header><main class="admin-content" id="content"><div class="page-heading"><div><p class="eyebrow">✦ GLOBETREK STAFF WORKSPACE</p><h1><?= sh($title) ?><em>.</em></h1><p><?= sh($description ?: 'Coordinate bookings and unforgettable journeys.') ?></p></div></div>
<?php if ($flash): ?><div class="manager-alert <?= $flash[1] === 'error'?'manager-error':'manager-success' ?>" role="status"><?= sh($flash[0]) ?></div><?php endif; ?>
<?php
}
function send(): void { ?>
</main><footer class="admin-footer">© <?= date('Y') ?> GlobeTrek Adventures · Staff Workspace</footer></div></div></body></html>
<?php }
