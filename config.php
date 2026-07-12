<?php
declare(strict_types=1);

// Database configuration constants (wrapped in checks to avoid redefine warnings)
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'transitops');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// Session and URL configurations
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 1800);
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/TransitOps/public');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'TransitOps');
}

// Return configuration array for modular inclusion
return [
    'host' => DB_HOST,
    'dbname' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'base_url' => BASE_URL,
    'session_lifetime' => SESSION_LIFETIME,
    'site_name' => SITE_NAME,
];
