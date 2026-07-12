<?php
declare(strict_types=1);

/**
 * TransitOps - Database Installer Script
 */

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransitOps - Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 2rem 0; }
        .card { background-color: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; }
        pre { background-color: #0f172a; color: #38bdf8; border: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm p-4 p-md-5">
                <h3 class="fw-bold mb-4 text-center">TransitOps Platform Installer</h3>';

// 1. Load config
if (!file_exists(__DIR__ . '/config.php')) {
    echo '<div class="alert alert-danger" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i> Error: <strong>config.php</strong> was not found in the root directory.
          </div>';
    echo '</div></div></div></div></body></html>';
    exit;
}

$config = require __DIR__ . '/config.php';
$host = $config['host'] ?? '127.0.0.1';
$dbname = $config['dbname'] ?? 'transitops';
$username = $config['username'] ?? 'root';
$password = $config['password'] ?? '';

try {
    echo '<div class="mb-3 text-muted">Connecting to MySQL server at <code>' . htmlspecialchars($host) . '</code>...</div>';
    $pdo = new PDO("mysql:host={$host}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo '<div class="mb-3 text-muted">Creating database <code>' . htmlspecialchars($dbname) . '</code> (if not exists)...</div>';
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    // 2. Read schema.sql
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("schema.sql file was not found in the root directory.");
    }
    
    echo '<div class="mb-3 text-muted">Reading and parsing schema definitions...</div>';
    $sql = file_get_contents($schemaPath);
    
    echo '<div class="mb-3 text-muted">Executing table creation and seeding queries...</div>';
    $pdo->exec($sql);
    
    // 3. Hash correction for ChangeMe123!
    echo '<div class="mb-3 text-muted">Generating secure Bcrypt hash for <code>admin@transitops.com</code>...</div>';
    $correctHash = password_hash('ChangeMe123!', PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = 'admin@transitops.com'");
    $stmt->execute(['hash' => $correctHash]);

    echo '<hr class="border-secondary my-4">';
    echo '<div class="alert alert-success d-flex align-items-center mb-4" role="alert">
            <div>
                <h5 class="alert-heading fw-bold mb-1">Installation Successful!</h5>
                Database and initial seeds have been prepared successfully.
            </div>
          </div>';
    
    echo '<div class="card p-3 mb-4 bg-black bg-opacity-25 border-0">
            <h6 class="fw-bold text-info">Default Admin Credentials</h6>
            <div class="small">
                <strong>Email:</strong> <code>admin@transitops.com</code><br>
                <strong>Password:</strong> <code>ChangeMe123!</code>
            </div>
          </div>';
          
    echo '<div class="d-grid">
            <a href="public/login.php" class="btn btn-primary fw-bold py-2">Proceed to Login</a>
          </div>';

} catch (Exception $e) {
    echo '<hr class="border-secondary my-4">';
    echo '<div class="alert alert-danger" role="alert">
            <h5 class="alert-heading fw-bold">Installation Failed!</h5>
            <p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
}

echo '</div></div></div></div></body></html>';
