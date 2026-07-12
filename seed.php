<?php
declare(strict_types=1);

/**
 * TransitOps - Realistic Database Seeding Script for Demo
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
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm p-4 p-md-5">
                <h3 class="fw-bold mb-4 text-center">TransitOps Database Seeder</h3>';

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

    // Clear existing data to avoid constraint/uniqueness violations
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
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. Seed 5 Vehicles
    echo '<div class="mb-3 text-muted">Registering 5 fleet vehicles (Trucks, Vans, Motorcycles)...</div>';
    $vehicles = [
        ['registration_number' => 'MH-12-PQ-9081', 'vehicle_name' => 'Tata Ultra Truck', 'type' => 'truck', 'max_load_capacity' => 12000.00, 'odometer' => 14500.00, 'acquisition_cost' => 1800000.00, 'status' => 'available', 'region' => 'West Region'],
        ['registration_number' => 'DL-01-AB-1234', 'vehicle_name' => 'Mahindra Supro Van', 'type' => 'van', 'max_load_capacity' => 2500.00, 'odometer' => 8400.00, 'acquisition_cost' => 650000.00, 'status' => 'available', 'region' => 'North Region'],
        ['registration_number' => 'KA-03-MM-5678', 'vehicle_name' => 'Ashok Leyland Dost', 'type' => 'truck', 'max_load_capacity' => 5000.00, 'odometer' => 22000.50, 'acquisition_cost' => 950000.00, 'status' => 'available', 'region' => 'South Region'],
        ['registration_number' => 'GJ-05-XY-9999', 'vehicle_name' => 'Maruti Super Carry', 'type' => 'van', 'max_load_capacity' => 1500.00, 'odometer' => 4100.00, 'acquisition_cost' => 520000.00, 'status' => 'in_shop', 'region' => 'West Region'],
        ['registration_number' => 'HR-26-CC-7777', 'vehicle_name' => 'Hero Achiever Bike', 'type' => 'motorcycle', 'max_load_capacity' => 150.00, 'odometer' => 1200.00, 'acquisition_cost' => 95005.00, 'status' => 'available', 'region' => 'North Region'],
    ];

    $vIds = [];
    foreach ($vehicles as $v) {
        $vIds[] = Vehicle::create($v);
    }

    // 2. Seed 5 Drivers
    echo '<div class="mb-3 text-muted">Registering 5 drivers with varying compliance scores...</div>';
    $drivers = [
        ['license_number' => 'DL-9081234567', 'name' => 'Rajesh Kumar', 'license_category' => 'Class A', 'license_expiry_date' => '2028-11-20', 'contact_number' => '+91 98765 43210', 'safety_score' => 4.90, 'status' => 'available', 'email' => 'rajesh@transitops.com'],
        ['license_number' => 'MH-4501234568', 'name' => 'Amit Sharma', 'license_category' => 'Class B', 'license_expiry_date' => '2029-04-15', 'contact_number' => '+91 87654 32109', 'safety_score' => 4.25, 'status' => 'available', 'email' => 'amit@transitops.com'],
        ['license_number' => 'KA-1201234569', 'name' => 'Sandeep Patil', 'license_category' => 'Class A', 'license_expiry_date' => '2027-08-30', 'contact_number' => '+91 76543 21098', 'safety_score' => 3.80, 'status' => 'available', 'email' => 'sandeep@transitops.com'],
        ['license_number' => 'GJ-0201234570', 'name' => 'Vikram Patel', 'license_category' => 'Class C', 'license_expiry_date' => date('Y-m-d', strtotime('+7 days')), 'contact_number' => '+91 65432 10987', 'safety_score' => 4.75, 'status' => 'available', 'email' => 'vikram@transitops.com'], // Expiring in exactly 7 days
        ['license_number' => 'HR-0501234571', 'name' => 'Sunny Deol', 'license_category' => 'Class A', 'license_expiry_date' => '2030-01-01', 'contact_number' => '+91 54321 09876', 'safety_score' => 2.10, 'status' => 'suspended', 'email' => 'sunny@transitops.com'],
    ];

    $dIds = [];
    foreach ($drivers as $d) {
        $dIds[] = Driver::create($d);
    }

    // 3. Seed Trips (Completed & Active)
    echo '<div class="mb-3 text-muted">Scheduling dispatch and completing mock routes...</div>';
    
    // Completed Trip 1
    $t1 = Trip::create([
        'vehicle_id' => $vIds[0],
        'driver_id' => $dIds[0],
        'source' => 'Mumbai Port',
        'destination' => 'Pune Warehouse',
        'cargo_weight' => 8000.00,
        'planned_distance' => 150.00,
        'status' => 'dispatched'
    ]);
    Trip::update($t1, [
        'vehicle_id' => $vIds[0],
        'driver_id' => $dIds[0],
        'source' => 'Mumbai Port',
        'destination' => 'Pune Warehouse',
        'cargo_weight' => 8000.00,
        'planned_distance' => 150.00,
        'actual_distance' => 152.50,
        'status' => 'completed'
    ]);

    // Completed Trip 2
    $t2 = Trip::create([
        'vehicle_id' => $vIds[1],
        'driver_id' => $dIds[1],
        'source' => 'Delhi Okhla Depot',
        'destination' => 'Gurugram Hub',
        'cargo_weight' => 1800.00,
        'planned_distance' => 45.00,
        'status' => 'dispatched'
    ]);
    Trip::update($t2, [
        'vehicle_id' => $vIds[1],
        'driver_id' => $dIds[1],
        'source' => 'Delhi Okhla Depot',
        'destination' => 'Gurugram Hub',
        'cargo_weight' => 1800.00,
        'planned_distance' => 45.00,
        'actual_distance' => 48.20,
        'status' => 'completed'
    ]);

    // Active Dispatched Route (locks vehicle 2 & driver 2 to 'on_trip')
    $t3 = Trip::create([
        'vehicle_id' => $vIds[2],
        'driver_id' => $dIds[2],
        'source' => 'Bengaluru City Depot',
        'destination' => 'Mysuru Hub',
        'cargo_weight' => 4500.00,
        'planned_distance' => 140.00,
        'status' => 'dispatched'
    ]);

    // 4. Seed Maintenance Logs
    echo '<div class="mb-3 text-muted">Registering vehicle servicing and shop locks...</div>';
    Maintenance::create([
        'vehicle_id' => $vIds[0],
        'description' => 'Brake pad replacement and test',
        'cost' => 1250.00,
        'date' => '2026-06-01',
        'status' => 'closed',
        'notes' => 'Pads replaced'
    ]);

    Maintenance::create([
        'vehicle_id' => $vIds[3],
        'description' => 'Radiator repair and coolants replacement',
        'cost' => 4200.00,
        'date' => '2026-07-10',
        'status' => 'open', // Locks vehicle to 'in_shop'
        'notes' => 'Awaiting spares'
    ]);

    // 5. Seed Fuel Purchases
    echo '<div class="mb-3 text-muted">Registering fuel procurement costs...</div>';
    Fuel::create(['vehicle_id' => $vIds[0], 'liters' => 60.00, 'cost' => 6100.00, 'date' => '2026-07-01']);
    Fuel::create(['vehicle_id' => $vIds[1], 'liters' => 15.00, 'cost' => 1550.00, 'date' => '2026-07-05']);
    Fuel::create(['vehicle_id' => $vIds[2], 'liters' => 50.00, 'cost' => 5100.00, 'date' => '2026-07-08']);

    // 6. Seed Operational Expenses
    echo '<div class="mb-3 text-muted">Registering highway tolls and checking permits...</div>';
    Expense::create(['vehicle_id' => $vIds[0], 'type' => 'toll', 'description' => 'Expressway Toll Gate Pay', 'cost' => 320.00, 'date' => '2026-07-01']);
    Expense::create(['vehicle_id' => $vIds[1], 'type' => 'toll', 'description' => 'City Checkpost Toll Tax', 'cost' => 80.00, 'date' => '2026-07-05']);
    Expense::create(['vehicle_id' => $vIds[2], 'type' => 'other', 'description' => 'State Permit Fees', 'cost' => 1200.00, 'date' => '2026-07-08']);

    echo '<hr class="border-secondary my-4">';
    echo '<div class="alert alert-success text-center mb-4" role="alert">
            <h5 class="alert-heading fw-bold mb-1">Seeding Completed!</h5>
            Platform database is now loaded with operational demo records.
          </div>';
    echo '<div class="d-grid">
            <a href="public/index.php" class="btn btn-primary fw-bold py-2">Go to Dashboard</a>
          </div>';

} catch (\Exception $e) {
    echo '<hr class="border-secondary my-4">';
    echo '<div class="alert alert-danger text-center" role="alert">
            <h5 class="alert-heading fw-bold">Seeding Failed!</h5>
            <p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
}

echo '</div></div></div></div></body></html>';
