<?php
declare(strict_types=1);

// Start PHP session before printing any output to prevent header warnings
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Emulate CLI server variables for testing
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHP CLI Test Suite';

require_once __DIR__ . '/../api/classes/Database.php';
require_once __DIR__ . '/../api/classes/Auth.php';
require_once __DIR__ . '/../api/classes/Vehicle.php';
require_once __DIR__ . '/../api/classes/Driver.php';
require_once __DIR__ . '/../api/classes/Document.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;
use Api\Classes\Driver;
use Api\Classes\Document;
use Api\Classes\Database;

echo "========================================\n";
echo "TRANSITOPS - ADVANCED FEATURE TESTS\n";
echo "========================================\n\n";

// 1. TEST CSRF PROTECTION
echo "[1] Testing CSRF Token Protection...\n";
$token = Auth::generateCsrfToken();
echo "Generated CSRF Token: " . $token . "\n";
$isValid = Auth::verifyCsrfToken($token);
echo "Verifying valid token: " . ($isValid ? "PASS" : "FAIL") . "\n";
$isInvalid = Auth::verifyCsrfToken("invalid_token_sample");
echo "Verifying invalid token: " . (!$isInvalid ? "PASS" : "FAIL") . "\n\n";

// 2. TEST BRUTE FORCE LOGGING
echo "[2] Testing Brute-Force Detection & Logging...\n";
$ip = '127.0.0.1';

$pdo = Database::getInstance();
// Clear existing login attempts for clean test (both email and IP)
$pdo->exec("DELETE FROM login_attempts WHERE email = 'admin@transitops.com' OR ip_address = '127.0.0.1'");

// Log 3 failed attempts
echo "Logging 3 failed login attempts...\n";
for ($i = 0; $i < 3; $i++) {
    try {
        Auth::login('admin@transitops.com', 'wrong_pass');
        echo "Attempt " . ($i + 1) . ": Succeeded (Unexpected)\n";
    } catch (Exception $e) {
        echo "Attempt " . ($i + 1) . ": Failed - " . $e->getMessage() . "\n";
    }
}

// Check attempt count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmt->execute(['ip' => $ip]);
$count = (int)$stmt->fetchColumn();
echo "Total logged failed attempts in DB: " . $count . " (" . ($count === 3 ? "PASS" : "FAIL") . ")\n\n";

// 3. TEST SOFT-DELETE AND RESTORE
echo "[3] Testing Soft-Delete and Data Recovery...\n";
try {
    $reg = 'TEST-' . rand(1000, 9999);
    $vId = Vehicle::create([
        'registration_number' => $reg,
        'vehicle_name' => 'Soft Delete Test Vehicle',
        'type' => 'van',
        'max_load_capacity' => 1200.00,
        'odometer' => 5000.00,
        'acquisition_cost' => 15000.00,
        'status' => 'available',
        'region' => 'TestRegion'
    ]);
    echo "Created test vehicle ID: {$vId}\n";
    
    // Soft delete
    Vehicle::delete($vId);
    echo "Soft-deleted vehicle ID: {$vId}\n";
    
    $deleted = Vehicle::getDeleted();
    $foundInDeleted = false;
    foreach ($deleted as $delVeh) {
        if ((int)$delVeh['id'] === $vId) {
            $foundInDeleted = true;
            break;
        }
    }
    echo "Found in deleted registry: " . ($foundInDeleted ? "PASS" : "FAIL") . "\n";
    
    // Restore
    Vehicle::restore($vId);
    echo "Restored vehicle ID: {$vId}\n";
    
    $activeVeh = Vehicle::getById($vId);
    echo "Found in active registry after restore: " . ($activeVeh !== null ? "PASS" : "FAIL") . "\n";
    
    // Cleanup vehicle
    $pdo->prepare("DELETE FROM vehicles WHERE id = :id")->execute(['id' => $vId]);
    echo "Cleaned up test vehicle.\n\n";
} catch (Exception $e) {
    echo "Soft-delete test encountered Exception: " . $e->getMessage() . " (FAIL)\n\n";
}

echo "========================================\n";
echo "TEST SUITE COMPLETION SUMMARY\n";
echo "========================================\n";
