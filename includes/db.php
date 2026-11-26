<?php
// Database connection; secrets belong only in db.local.php or environment variables.
// Do not add credentials to this tracked file.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$configFile = __DIR__ . '/db.local.php';
if (is_file($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'host' => getenv('GT_DB_HOST') ?: '',
        'name' => getenv('GT_DB_NAME') ?: '',
        'user' => getenv('GT_DB_USER') ?: '',
        'password' => getenv('GT_DB_PASSWORD') === false ? '' : getenv('GT_DB_PASSWORD'),
        'port' => (int)(getenv('GT_DB_PORT') ?: 3306),
    ];
}
if (!is_array($config) || empty($config['host']) || empty($config['name']) ||
    !array_key_exists('user', $config)) {
    http_response_code(503);
    exit('Database not configured. Copy includes/db.example.php to includes/db.local.php and follow README.md.');
}
try {
    $conn = new mysqli(
        (string)$config['host'],
        (string)$config['user'],
        (string)($config['password'] ?? ''),
        (string)$config['name'],
        (int)($config['port'] ?? 3306)
    );
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('GlobeTrek database connection error: ' . $e->getMessage());
    http_response_code(503);
    exit('Database connection unavailable. Check local configuration and MySQL service.');
}
