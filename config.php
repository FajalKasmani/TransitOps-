<?php
declare(strict_types=1);

// Database configuration constants
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'transitops');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session and URL configurations
define('SESSION_LIFETIME', 1800);
define('BASE_URL', 'http://localhost/TransitOps/public');
define('SITE_NAME', 'TransitOps');

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
