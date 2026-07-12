<?php
declare(strict_types=1);

// Global Exception Handler to log issues silently and display branded 500 page
if (!function_exists('global_exception_handler')) {
    function global_exception_handler(Throwable $exception) {
        $logFile = __DIR__ . '/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMsg = "[{$timestamp}] Uncaught Exception: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()}\nStack Trace:\n{$exception->getTraceAsString()}\n\n";
        error_log($logMsg, 3, $logFile);

        if (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(500);

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
                  (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

        if ($isJson) {
            echo json_encode(['error' => 'An internal server error occurred. Please contact the administrator.']);
            exit;
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Internal Server Error - TransitOps</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                body { background: #0f172a; color: #f8fafc; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .error-card { max-width: 500px; padding: 2.5rem; border-radius: 16px; background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); text-align: center; }
            </style>
        </head>
        <body>
            <div class="error-card shadow-lg">
                <div class="text-danger mb-4" style="font-size: 4rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h2 class="fw-bold mb-3">Internal Server Error</h2>
                <p class="text-muted mb-4">An unexpected error occurred during execution. The system administrator has been logged and notified.</p>
                <a href="/TransitOps/public/index.php" class="btn btn-primary px-4 py-2" style="border-radius: 10px;">Return to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

set_exception_handler('global_exception_handler');


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
    'enable_login_lockout' => true, // Set to false to disable login lockout for testing
];
