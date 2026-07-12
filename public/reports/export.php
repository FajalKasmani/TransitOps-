<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Reports.php';

use Api\Classes\Auth;
use Api\Classes\Reports;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'financial_analyst'])) {
    http_response_code(403);
    echo "Forbidden: Access denied.";
    exit;
}

$type = $_GET['type'] ?? '';

if ($type === 'efficiency') {
    $filename = "fuel_efficiency_report_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    // Write headers
    fputcsv($output, ['Vehicle ID', 'Registration Number', 'Vehicle Name', 'Total Distance (km)', 'Total Fuel (Liters)', 'Fuel Efficiency (km/L)']);

    $data = Reports::getFuelEfficiencyReport();
    foreach ($data as $r) {
        fputcsv($output, [
            $r['id'],
            $r['registration_number'],
            $r['vehicle_name'],
            number_format((float)$r['total_distance'], 2, '.', ''),
            number_format((float)$r['total_liters'], 2, '.', ''),
            number_format((float)$r['efficiency'], 2, '.', '')
        ]);
    }
    fclose($output);
    exit;
} elseif ($type === 'roi') {
    $filename = "vehicle_roi_report_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    // Write headers
    fputcsv($output, [
        'Vehicle ID', 
        'Registration Number', 
        'Vehicle Name', 
        'Acquisition Cost (₹)', 
        'Fuel Cost (₹)', 
        'Expense Cost (₹)', 
        'Maintenance Cost (₹)', 
        'Total Operating Cost (₹)', 
        'Calculated Revenue (₹)', 
        'ROI (%)'
    ]);

    $data = Reports::getVehicleAnalytics();
    foreach ($data as $r) {
        fputcsv($output, [
            $r['id'],
            $r['registration_number'],
            $r['vehicle_name'],
            number_format((float)$r['acquisition_cost'], 2, '.', ''),
            number_format((float)$r['fuel_cost'], 2, '.', ''),
            number_format((float)$r['expense_cost'], 2, '.', ''),
            number_format((float)$r['maintenance_cost'], 2, '.', ''),
            number_format((float)$r['total_cost'], 2, '.', ''),
            number_format((float)$r['calculated_revenue'], 2, '.', ''),
            number_format((float)$r['roi'], 2, '.', '')
        ]);
    }
    fclose($output);
    exit;
} else {
    http_response_code(400);
    echo "Bad Request: Invalid report export type.";
    exit;
}
