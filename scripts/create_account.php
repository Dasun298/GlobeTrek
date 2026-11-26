<?php
// Create an administrator or staff account for LOCAL SETUP ONLY.
// Run in a terminal: php scripts/create_account.php admin
// No default passwords or public web route.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$role = $argv[1] ?? '';
if (!in_array($role, ['admin','staff'], true)) {
    fwrite(STDERR, "Usage: php scripts/create_account.php admin|staff\n");
    exit(1);
}
require_once __DIR__ . '/../includes/db.php';
function prompt(string $label): string {
    echo $label;
    return trim((string)fgets(STDIN));
}
$name = prompt("Choose $role username (3–100 letters, numbers, . _ -): ");
if (!preg_match('/^[A-Za-z0-9_.-]{3,100}$/D',$name)) {
    fwrite(STDERR,"Invalid username.\n"); exit(1);
}
// Windows PowerShell may echo typed input; do not use a password reused elsewhere.
$password = prompt('Choose a UNIQUE demo password (at least 12 characters): ');
$confirm = prompt('Repeat the demo password: ');
if (strlen($password) < 12 || strlen($password) > 72 || !hash_equals($password,$confirm)) {
    fwrite(STDERR,"Password must be 12–72 characters and match confirmation.\n"); exit(1);
}
// Build the SQL only from our fixed whitelist (admins/staff).
$table = $role === 'admin' ? 'admins' : 'staff';
$check = $conn->prepare("SELECT id FROM `$table` WHERE username=? LIMIT 1");
$check->bind_param('s',$name);
$check->execute();
$exists = $check->get_result()->fetch_assoc();
$check->close();
if ($exists) { fwrite(STDERR,"That username already exists.\n"); exit(1); }
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO `$table` (username,password) VALUES (?,?)");
$stmt->bind_param('ss',$name,$hash);
$stmt->execute(); $stmt->close();
unset($password,$confirm);
echo ucfirst($role)." account created successfully. No password is stored in source files.\n";
