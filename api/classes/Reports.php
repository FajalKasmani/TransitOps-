<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;

/**
 * Real-time KPI and Operational Analytics service.
 */
class Reports {
    /**
     * Calculate and return general real-time KPIs for the dashboard.
     *
     * @return array
     */
    public static function getDashboardKPIs(): array {
        try {
            $pdo = Database::getInstance();
        } catch (RuntimeException $e) {
            return [
                'active_vehicles' => 0,
                'available_vehicles' => 0,
                'maintenance_vehicles' => 0,
                'active_trips' => 0,
                'pending_trips' => 0,
                'drivers_on_duty' => 0,
                'fleet_utilization' => 0.0,
                'total_operational_cost' => 0.0
            ];
        }

        // 1. Active Vehicles (on_trip status)
        $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'on_trip' AND is_deleted = 0");
        $activeVehicles = (int)$stmt->fetchColumn();

        // 2. Available Vehicles (available status)
        $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available' AND is_deleted = 0");
        $availableVehicles = (int)$stmt->fetchColumn();

        // 3. Vehicles in Maintenance (in_shop status)
        $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'in_shop' AND is_deleted = 0");
        $maintVehicles = (int)$stmt->fetchColumn();

        // 4. Active Trips (dispatched status)
        $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'dispatched'");
        $activeTrips = (int)$stmt->fetchColumn();

        // 5. Pending Trips (draft status)
        $stmt = $pdo->query("SELECT COUNT(*) FROM trips WHERE status = 'draft'");
        $pendingTrips = (int)$stmt->fetchColumn();

        // 6. Drivers on duty (available, on_trip status)
        $stmt = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status IN ('available', 'on_trip') AND is_deleted = 0");
        $driversOnDuty = (int)$stmt->fetchColumn();

        // 7. Fleet Utilization % (active_trips / total_vehicles * 100)
        $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE is_deleted = 0");
        $totalVehicles = (int)$stmt->fetchColumn();
        $utilization = 0.0;
        if ($totalVehicles > 0) {
            $utilization = round(($activeTrips / $totalVehicles) * 100, 2);
        }

        // 8. Total Operational Cost (Sum of fuel logs, expenses, and maintenance costs)
        $fuelCost = (float)$pdo->query("SELECT SUM(cost) FROM fuel_logs")->fetchColumn();
        $expenseCost = (float)$pdo->query("SELECT SUM(cost) FROM expenses")->fetchColumn();
        $maintCost = (float)$pdo->query("SELECT SUM(cost) FROM maintenance_logs")->fetchColumn();
        $totalCost = $fuelCost + $expenseCost + $maintCost;

        return [
            'active_vehicles' => $activeVehicles,
            'available_vehicles' => $availableVehicles,
            'maintenance_vehicles' => $maintVehicles,
            'active_trips' => $activeTrips,
            'pending_trips' => $pendingTrips,
            'drivers_on_duty' => $driversOnDuty,
            'fleet_utilization' => $utilization,
            'total_operational_cost' => $totalCost
        ];
    }

    /**
     * Calculate ROI and other report metrics per vehicle.
     *
     * @return array
     */
    public static function getVehicleAnalytics(): array {
        try {
            $pdo = Database::getInstance();
        } catch (RuntimeException $e) {
            return [];
        }

        // Get cost breakdowns per vehicle
        $sql = "
            SELECT 
                v.id,
                v.registration_number,
                v.vehicle_name,
                v.acquisition_cost,
                COALESCE((SELECT SUM(cost) FROM fuel_logs WHERE vehicle_id = v.id), 0) as fuel_cost,
                COALESCE((SELECT SUM(cost) FROM expenses WHERE vehicle_id = v.id), 0) as expense_cost,
                COALESCE((SELECT SUM(cost) FROM maintenance_logs WHERE vehicle_id = v.id), 0) as maintenance_cost,
                COALESCE((SELECT SUM(planned_distance * 1.5 + cargo_weight * 0.5) FROM trips WHERE vehicle_id = v.id AND status = 'completed'), 0) as calculated_revenue
            FROM vehicles v
            WHERE v.is_deleted = 0
        ";

        $stmt = $pdo->query($sql);
        $vehicles = $stmt->fetchAll();

        foreach ($vehicles as &$v) {
            $v['total_cost'] = (float)$v['fuel_cost'] + (float)$v['expense_cost'] + (float)$v['maintenance_cost'];
            $v['roi'] = 0.0;
            $acquisition = (float)$v['acquisition_cost'];
            if ($acquisition > 0) {
                $v['roi'] = round((((float)$v['calculated_revenue'] - $v['total_cost']) / $acquisition) * 100, 2);
            }
        }

        return $vehicles;
    }

    /**
     * Calculate fuel efficiency (km/L) per vehicle.
     *
     * @return array
     */
    public static function getFuelEfficiencyReport(): array {
        try {
            $pdo = Database::getInstance();
        } catch (RuntimeException $e) {
            return [];
        }

        $sql = "
            SELECT 
                v.id,
                v.registration_number,
                v.vehicle_name,
                COALESCE((SELECT SUM(actual_distance) FROM trips WHERE vehicle_id = v.id AND status = 'completed'), 0) as total_distance,
                COALESCE((SELECT SUM(liters) FROM fuel_logs WHERE vehicle_id = v.id), 0) as total_liters
            FROM vehicles v
            WHERE v.is_deleted = 0
        ";

        $stmt = $pdo->query($sql);
        $records = $stmt->fetchAll();

        foreach ($records as &$r) {
            $distance = (float)$r['total_distance'];
            $liters = (float)$r['total_liters'];
            $r['efficiency'] = $liters > 0 ? round($distance / $liters, 2) : 0.00;
        }

        return $records;
    }

    /**
     * Calculate and return average safety score of on-duty drivers.
     *
     * @return float
     */
    public static function getAverageSafetyScore(): float {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("SELECT AVG(safety_score) FROM drivers WHERE is_deleted = 0");
            $score = $stmt->fetchColumn();
            return $score !== null ? round((float)$score, 2) : 5.00;
        } catch (\Exception $e) {
            return 5.00;
        }
    }

    /**
     * Fetches vehicles that have high mileage (e.g. >= 10,000 km) and need maintenance alerts.
     */
    public static function getPreventativeMaintenanceAlerts(): array {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("
                SELECT v.*, 
                       (SELECT MAX(date) FROM maintenance_logs WHERE vehicle_id = v.id) as last_maint_date
                FROM vehicles v
                WHERE v.is_deleted = 0 AND v.odometer >= 10000.00
                ORDER BY v.odometer DESC
            ");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
}
