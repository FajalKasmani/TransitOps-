<?php
declare(strict_types=1);

/**
 * TransitOps - Database Seeding Script (Gujarat Region Focus)
 */

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TransitOps - Database Seeder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 2rem 0; }
        .card { background-color: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-8">
            <div class="card shadow-sm p-4 p-md-5">
                <h3 class="fw-bold mb-4 text-center">TransitOps Database Seeder (Gujarat Region)</h3>';

require_once __DIR__ . '/api/classes/Database.php';
require_once __DIR__ . '/api/classes/Vehicle.php';
require_once __DIR__ . '/api/classes/Driver.php';
require_once __DIR__ . '/api/classes/Trip.php';
require_once __DIR__ . '/api/classes/Maintenance.php';
require_once __DIR__ . '/api/classes/Fuel.php';
require_once __DIR__ . '/api/classes/Expense.php';

use Api\Classes\Database;
use Api\Classes\Vehicle;
use Api\Classes\Driver;
use Api\Classes\Trip;
use Api\Classes\Maintenance;
use Api\Classes\Fuel;
use Api\Classes\Expense;

try {
    $pdo = Database::getInstance();

    // Clear existing data
    echo '<div class="mb-3 text-muted">Clearing previous operational tables...</div>';
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE action_logs");
    $pdo->exec("TRUNCATE TABLE vehicle_documents");
    $pdo->exec("TRUNCATE TABLE expenses");
    $pdo->exec("TRUNCATE TABLE fuel_logs");
    $pdo->exec("TRUNCATE TABLE maintenance_logs");
    $pdo->exec("TRUNCATE TABLE trips");
    $pdo->exec("TRUNCATE TABLE drivers");
    $pdo->exec("TRUNCATE TABLE vehicles");
    $pdo->exec("DELETE FROM users WHERE email != 'admin@transitops.com'"); // Keep default admin, delete rest
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Seed 5 Vehicles (Gujarat GJ State Registrations)
    echo '<div class="mb-3 text-muted">Registering 5 fleet vehicles with GJ registration plates...</div>';
    $vehicles = [
        ['registration_number' => 'GJ-01-AZ-9988', 'vehicle_name' => 'Tata Ultra Truck (AHD)', 'type' => 'truck', 'max_load_capacity' => 12000.00, 'odometer' => 14500.00, 'acquisition_cost' => 1800000.00, 'status' => 'available', 'region' => 'Gujarat - Ahmedabad'],
        ['registration_number' => 'GJ-03-XX-1234', 'vehicle_name' => 'Mahindra Supro Van (RJK)', 'type' => 'van', 'max_load_capacity' => 2500.00, 'odometer' => 8400.00, 'acquisition_cost' => 650000.00, 'status' => 'available', 'region' => 'Gujarat - Rajkot'],
        ['registration_number' => 'GJ-05-YY-5678', 'vehicle_name' => 'Ashok Leyland Dost (SRT)', 'type' => 'truck', 'max_load_capacity' => 5000.00, 'odometer' => 22000.50, 'acquisition_cost' => 950000.00, 'status' => 'available', 'region' => 'Gujarat - Surat'],
        ['registration_number' => 'GJ-06-ZZ-4444', 'vehicle_name' => 'Maruti Super Carry (BDQ)', 'type' => 'van', 'max_load_capacity' => 1500.00, 'odometer' => 12500.00, 'acquisition_cost' => 520000.00, 'status' => 'in_shop', 'region' => 'Gujarat - Vadodara'],
        ['registration_number' => 'GJ-27-BC-7777', 'vehicle_name' => 'Hero Super Splendor (GNR)', 'type' => 'motorcycle', 'max_load_capacity' => 150.00, 'odometer' => 1200.00, 'acquisition_cost' => 95000.00, 'status' => 'available', 'region' => 'Gujarat - Gandhinagar'],
    ];

    $vIds = [];
    foreach ($vehicles as $v) {
        $vIds[] = Vehicle::create($v);
    }

    // 2. Seed 5 Drivers
    echo '<div class="mb-3 text-muted">Registering 5 local Gujarat regional drivers...</div>';
    $drivers = [
        ['license_number' => 'GJ01-20230000001', 'name' => 'Vikram Patel', 'license_category' => 'Class A', 'license_expiry_date' => '2028-11-20', 'contact_number' => '+91 98765 01001', 'safety_score' => 4.90, 'status' => 'available', 'email' => 'vikram@transitops.com'],
        ['license_number' => 'GJ03-20230000002', 'name' => 'Hardik Shah', 'license_category' => 'Class B', 'license_expiry_date' => '2029-04-15', 'contact_number' => '+91 87654 02002', 'safety_score' => 4.25, 'status' => 'available', 'email' => 'hardik@transitops.com'],
        ['license_number' => 'GJ05-20230000003', 'name' => 'Jignesh Mehta', 'license_category' => 'Class A', 'license_expiry_date' => '2027-08-30', 'contact_number' => '+91 76543 03003', 'safety_score' => 3.80, 'status' => 'available', 'email' => 'jignesh@transitops.com'],
        ['license_number' => 'GJ06-20230000004', 'name' => 'Kirti Vyas', 'license_category' => 'Class C', 'license_expiry_date' => date('Y-m-d', strtotime('+7 days')), 'contact_number' => '+91 65432 04004', 'safety_score' => 4.75, 'status' => 'available', 'email' => 'kirti@transitops.com'],
        ['license_number' => 'GJ27-20230000005', 'name' => 'Ramesh Savaliya', 'license_category' => 'Class A', 'license_expiry_date' => '2030-01-01', 'contact_number' => '+91 54321 05005', 'safety_score' => 2.10, 'status' => 'suspended', 'email' => 'ramesh@transitops.com'],
    ];

    $dIds = [];
    foreach ($drivers as $d) {
        $dIds[] = Driver::create($d);
    }

    // 3. Seed Users with Gujarat test credentials
    echo '<div class="mb-3 text-muted">Registering matching login credentials for all roles...</div>';
    $hashedPass = password_hash('ChangeMe123!', PASSWORD_BCRYPT);
    
    $usersToSeed = [
        ['email' => 'fleet_manager@transitops.com', 'password_hash' => $hashedPass, 'role' => 'fleet_manager', 'full_name' => 'Hardik Mehta'],
        ['email' => 'safety_officer@transitops.com', 'password_hash' => $hashedPass, 'role' => 'safety_officer', 'full_name' => 'Jignesh Shah'],
        ['email' => 'financial_analyst@transitops.com', 'password_hash' => $hashedPass, 'role' => 'financial_analyst', 'full_name' => 'Kirti Vyas'],
        ['email' => 'vikram@transitops.com', 'password_hash' => $hashedPass, 'role' => 'driver', 'full_name' => 'Vikram Patel'],
        ['email' => 'hardik@transitops.com', 'password_hash' => $hashedPass, 'role' => 'driver', 'full_name' => 'Hardik Shah'],
    ];

    foreach ($usersToSeed as $u) {
        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = :role LIMIT 1");
        $roleStmt->execute(['role' => $u['role']]);
        $roleId = (int)$roleStmt->fetchColumn();

        $ins = $pdo->prepare("
            INSERT INTO users (email, password_hash, role_id, full_name, is_active) 
            VALUES (:email, :hash, :role_id, :name, 1)
        ");
        $ins->execute([
            'email' => $u['email'],
            'hash' => $u['password_hash'],
            'role_id' => $roleId,
            'name' => $u['full_name']
        ]);
    }

    // 4. Seed Trips (Completed & Active Gujarat Routes)
    echo '<div class="mb-3 text-muted">Scheduling dispatch and completing mock Gujarat routes...</div>';
    
    // Completed Route 1
    $t1 = Trip::create([
        'vehicle_id' => $vIds[0],
        'driver_id' => $dIds[0],
        'source' => 'Ahmedabad Depot 1',
        'destination' => 'Surat Industrial Hub',
        'cargo_weight' => 8000.00,
        'planned_distance' => 265.00,
        'status' => 'dispatched'
    ]);
    Trip::update($t1, [
        'vehicle_id' => $vIds[0],
        'driver_id' => $dIds[0],
        'source' => 'Ahmedabad Depot 1',
        'destination' => 'Surat Industrial Hub',
        'cargo_weight' => 8000.00,
        'planned_distance' => 265.00,
        'actual_distance' => 268.50,
        'status' => 'completed'
    ]);

    // Completed Route 2
    $t2 = Trip::create([
        'vehicle_id' => $vIds[1],
        'driver_id' => $dIds[1],
        'source' => 'Rajkot GIDC Depot',
        'destination' => 'Jamnagar Port Warehouse',
        'cargo_weight' => 1800.00,
        'planned_distance' => 92.00,
        'status' => 'dispatched'
    ]);
    Trip::update($t2, [
        'vehicle_id' => $vIds[1],
        'driver_id' => $dIds[1],
        'source' => 'Rajkot GIDC Depot',
        'destination' => 'Jamnagar Port Warehouse',
        'cargo_weight' => 1800.00,
        'planned_distance' => 92.00,
        'actual_distance' => 94.20,
        'status' => 'completed'
    ]);

    // Active Route (dispatched, locks vehicle 2 & driver 2)
    Trip::create([
        'vehicle_id' => $vIds[2],
        'driver_id' => $dIds[2],
        'source' => 'Vadodara Fulfillment Center',
        'destination' => 'Gandhinagar GIDC Center',
        'cargo_weight' => 4500.00,
        'planned_distance' => 115.00,
        'status' => 'dispatched'
    ]);

    // 5. Seed Maintenance Logs
    echo '<div class="mb-3 text-muted">Registering vehicle servicing and shop locks...</div>';
    Maintenance::create([
        'vehicle_id' => $vIds[0],
        'description' => 'Tata Ultra cabin fan and brake pad repair at Ahmedabad GIDC Workshop',
        'cost' => 1250.00,
        'date' => '2026-06-01',
        'status' => 'closed',
        'notes' => 'Replaced front pads'
    ]);

    Maintenance::create([
        'vehicle_id' => $vIds[3],
        'description' => 'Radiator overhaul at Vadodara Maruti service center',
        'cost' => 4200.00,
        'date' => '2026-07-10',
        'status' => 'open', // Locks vehicle to 'in_shop'
        'notes' => 'Awaiting coolants shipment'
    ]);

    // 6. Seed Fuel Purchases
    echo '<div class="mb-3 text-muted">Registering fuel procurement logs...</div>';
    Fuel::create(['vehicle_id' => $vIds[0], 'liters' => 60.00, 'cost' => 6100.00, 'date' => '2026-07-01']); // Rs. 6100 fuel
    Fuel::create(['vehicle_id' => $vIds[1], 'liters' => 15.00, 'cost' => 1550.00, 'date' => '2026-07-05']);
    Fuel::create(['vehicle_id' => $vIds[2], 'liters' => 50.00, 'cost' => 5100.00, 'date' => '2026-07-08']);

    // 7. Seed Operational Expenses
    echo '<div class="mb-3 text-muted">Registering highway tolls and checking permits...</div>';
    Expense::create(['vehicle_id' => $vIds[0], 'type' => 'toll', 'description' => 'Ahmedabad-Vadodara Expressway Toll Pay', 'cost' => 320.00, 'date' => '2026-07-01']);
    Expense::create(['vehicle_id' => $vIds[1], 'type' => 'toll', 'description' => 'Rajkot-Jamnagar Highway Toll Plaza', 'cost' => 90.00, 'date' => '2026-07-05']);
    Expense::create(['vehicle_id' => $vIds[2], 'type' => 'other', 'description' => 'Gujarat State Inter-district Permit fee', 'cost' => 1200.00, 'date' => '2026-07-08']);

    echo '<hr class="border-secondary my-4">';
    echo '<div class="alert alert-success text-center mb-4" role="alert">
            <h5 class="alert-heading fw-bold mb-1">Seeding Completed!</h5>
            Platform database is now loaded with Gujarat region records.
          </div>';
    
    echo '<div class="card p-3 mb-4 bg-black bg-opacity-25 border-0">
            <h6 class="fw-bold text-success mb-2">Seeded Logins for Testing (Password: ChangeMe123!)</h6>
            <div class="small">
                <strong>System Admin:</strong> <code>admin@transitops.com</code><br>
                <strong>Fleet Manager:</strong> <code>fleet_manager@transitops.com</code><br>
                <strong>Safety Officer:</strong> <code>safety_officer@transitops.com</code><br>
                <strong>Financial Analyst:</strong> <code>financial_analyst@transitops.com</code><br>
                <strong>Driver (Vikram Patel):</strong> <code>vikram@transitops.com</code><br>
                <strong>Driver (Hardik Shah):</strong> <code>hardik@transitops.com</code>
            </div>
          </div>';

    echo '<div class="d-grid gap-2">
            <a href="public/login.php" class="btn btn-primary fw-bold py-2">Go to Login</a>
          </div>';

} catch (\Exception $e) {
    echo '<hr class="border-secondary my-4">';
    echo '<div class="alert alert-danger text-center" role="alert">
            <h5 class="alert-heading fw-bold">Seeding Failed!</h5>
            <p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
}

echo '</div></div></div></div></body></html>';
