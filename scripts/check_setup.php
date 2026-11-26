<?php
// CLI only local setup diagnostic; does not alter data or print passwords.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (!extension_loaded('mysqli')) { fwrite(STDERR, "Missing PHP mysqli extension. Use your XAMPP PHP CLI.\n"); exit(1); }
require __DIR__ . '/../includes/db.php';
$required = ['users','admins','staff','packages','bookings','payments','hotels','hotel_bookings','transport','transportation','inquiries'];
foreach ($required as $table) {
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    $ok = $res->num_rows === 1;
    echo str_pad($table, 18) . ($ok ? "OK\n" : "MISSING\n");
    if (!$ok) $missing = true;
}
if (!empty($missing)) { fwrite(STDERR,"Import database/globetrek_demo.sql.\n"); exit(1); }
echo "Database connection and required table checks completed.\n";
