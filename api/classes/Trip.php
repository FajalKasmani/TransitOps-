<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;
use InvalidArgumentException;

/**
 * Trip entity class managing CRUD operations, capacity checks, and status automations.
 */
class Trip {
    /**
     * Log trip activities to the audit trail.
     */
    private static function logAction(string $entity, int $entityId, string $action, string $details = null): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("
                INSERT INTO action_logs (user_id, entity, entity_id, action, details) 
                VALUES (:user_id, :entity, :entity_id, :action, :details)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'entity' => $entity,
                'entity_id' => $entityId,
                'action' => $action,
                'details' => $details
            ]);
        } catch (\Exception $e) {
            // Fail-safe execution
        }
    }

    /**
     * Validate trip parameters before writing to the database.
     *
     * @param array $data Input properties
     * @param int|null $tripId ID of current trip if executing an update
     * @throws InvalidArgumentException If validation checks fail
     */
    public static function validate(array $data, ?int $tripId = null): void {
        $pdo = Database::getInstance();

        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException("Vehicle assignment is required.");
        }
        if (empty($data['driver_id'])) {
            throw new InvalidArgumentException("Driver assignment is required.");
        }
        if (empty($data['source'])) {
            throw new InvalidArgumentException("Source location is required.");
        }
        if (empty($data['destination'])) {
            throw new InvalidArgumentException("Destination location is required.");
        }
        if (!isset($data['cargo_weight']) || (float)$data['cargo_weight'] <= 0) {
            throw new InvalidArgumentException("Cargo weight must be greater than 0.");
        }
        if (!isset($data['planned_distance']) || (float)$data['planned_distance'] <= 0) {
            throw new InvalidArgumentException("Planned distance must be greater than 0.");
        }
        if (isset($data['actual_distance']) && $data['actual_distance'] !== null && (float)$data['actual_distance'] < 0) {
            throw new InvalidArgumentException("Actual distance cannot be negative.");
        }
        if (!in_array($data['status'] ?? 'draft', ['draft', 'dispatched', 'completed', 'cancelled'], true)) {
            throw new InvalidArgumentException("Invalid trip status.");
        }

        // Fetch vehicle capacity specifications
        $vehStmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id AND is_deleted = 0 LIMIT 1");
        $vehStmt->execute(['id' => (int)$data['vehicle_id']]);
        $vehicle = $vehStmt->fetch();
        if (!$vehicle) {
            throw new InvalidArgumentException("Selected vehicle does not exist or is deleted.");
        }

        // Enforce max load capacity check
        if ((float)$data['cargo_weight'] > (float)$vehicle['max_load_capacity']) {
            throw new InvalidArgumentException(sprintf(
                "Cargo weight (%s kg) exceeds vehicle maximum capacity (%s kg).",
                $data['cargo_weight'],
                $vehicle['max_load_capacity']
            ));
        }

        // Fetch driver specifications
        $drvStmt = $pdo->prepare("SELECT * FROM drivers WHERE id = :id AND is_deleted = 0 LIMIT 1");
        $drvStmt->execute(['id' => (int)$data['driver_id']]);
        $driver = $drvStmt->fetch();
        if (!$driver) {
            throw new InvalidArgumentException("Selected driver does not exist or is deleted.");
        }

        // Enforce license validity check
        if (strtotime($driver['license_expiry_date']) < time()) {
            throw new InvalidArgumentException("Selected driver has an expired license.");
        }

        // Enforce availability checks on dispatch
        $status = $data['status'] ?? 'draft';
        if ($status === 'dispatched') {
            $currentAssignedVehicle = null;
            $currentAssignedDriver = null;

            if ($tripId !== null) {
                $currentTripStmt = $pdo->prepare("SELECT vehicle_id, driver_id FROM trips WHERE id = :id LIMIT 1");
                $currentTripStmt->execute(['id' => $tripId]);
                $curr = $currentTripStmt->fetch();
                if ($curr) {
                    $currentAssignedVehicle = (int)$curr['vehicle_id'];
                    $currentAssignedDriver = (int)$curr['driver_id'];
                }
            }

            if ($vehicle['status'] !== 'available' && (int)$vehicle['id'] !== $currentAssignedVehicle) {
                throw new InvalidArgumentException("Vehicle is currently unavailable (Status: " . $vehicle['status'] . ").");
            }

            if ($driver['status'] !== 'available' && (int)$driver['id'] !== $currentAssignedDriver) {
                throw new InvalidArgumentException("Driver is currently unavailable (Status: " . $driver['status'] . ").");
            }
        }
    }

    /**
     * Create a new trip log, executing status automations within an ACID transaction.
     *
     * @param array $data Property mapping
     * @return int Inserted ID
     */
    public static function create(array $data): int {
        self::validate($data);

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $status = $data['status'] ?? 'draft';
            $startTime = ($status === 'dispatched') ? date('Y-m-d H:i:s') : null;

            $stmt = $pdo->prepare("
                INSERT INTO trips (vehicle_id, driver_id, source, destination, cargo_weight, planned_distance, status, start_time)
                VALUES (:vehicle_id, :driver_id, :source, :destination, :cargo_weight, :planned_distance, :status, :start_time)
            ");
            
            $stmt->execute([
                'vehicle_id' => (int)$data['vehicle_id'],
                'driver_id' => (int)$data['driver_id'],
                'source' => $data['source'],
                'destination' => $data['destination'],
                'cargo_weight' => (float)$data['cargo_weight'],
                'planned_distance' => (float)$data['planned_distance'],
                'status' => $status,
                'start_time' => $startTime
            ]);

            $tripId = (int)$pdo->lastInsertId();

            // Automation 1: Update statuses to on_trip when dispatched
            if ($status === 'dispatched') {
                $updVeh = $pdo->prepare("UPDATE vehicles SET status = 'on_trip' WHERE id = :id");
                $updVeh->execute(['id' => (int)$data['vehicle_id']]);

                $updDrv = $pdo->prepare("UPDATE drivers SET status = 'on_trip' WHERE id = :id");
                $updDrv->execute(['id' => (int)$data['driver_id']]);
            }

            $pdo->commit();
            self::logAction('trips', $tripId, 'CREATE', "Dispatched trip #{$tripId} from {$data['source']} to {$data['destination']}");
            return $tripId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException("Trip creation failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Update trip configuration, executing release automations inside an ACID transaction.
     *
     * @param int $id The trip ID
     * @param array $data Property mapping
     * @return bool Success status
     */
    public static function update(int $id, array $data): bool {
        self::validate($data, $id);

        $pdo = Database::getInstance();
        
        $currStmt = $pdo->prepare("SELECT * FROM trips WHERE id = :id LIMIT 1");
        $currStmt->execute(['id' => $id]);
        $oldTrip = $currStmt->fetch();
        if (!$oldTrip) {
            throw new InvalidArgumentException("Trip not found.");
        }

        $pdo->beginTransaction();

        try {
            $status = $data['status'] ?? 'draft';
            $oldStatus = $oldTrip['status'];
            $oldVehicleId = (int)$oldTrip['vehicle_id'];
            $oldDriverId = (int)$oldTrip['driver_id'];
            $newVehicleId = (int)$data['vehicle_id'];
            $newDriverId = (int)$data['driver_id'];

            $startTime = $oldTrip['start_time'];
            $endTime = $oldTrip['end_time'];

            if ($oldStatus !== 'dispatched' && $status === 'dispatched') {
                $startTime = date('Y-m-d H:i:s');
            }
            if ($status === 'completed' && $endTime === null) {
                $endTime = date('Y-m-d H:i:s');
            }

            $stmt = $pdo->prepare("
                UPDATE trips 
                SET vehicle_id = :vehicle_id, 
                    driver_id = :driver_id, 
                    source = :source, 
                    destination = :destination, 
                    cargo_weight = :cargo_weight, 
                    planned_distance = :planned_distance, 
                    status = :status, 
                    actual_distance = :actual_distance,
                    start_time = :start_time, 
                    end_time = :end_time
                WHERE id = :id
            ");

            $actualDistance = (isset($data['actual_distance']) && $data['actual_distance'] !== '') ? (float)$data['actual_distance'] : null;

            $stmt->execute([
                'id' => $id,
                'vehicle_id' => $newVehicleId,
                'driver_id' => $newDriverId,
                'source' => $data['source'],
                'destination' => $data['destination'],
                'cargo_weight' => (float)$data['cargo_weight'],
                'planned_distance' => (float)$data['planned_distance'],
                'status' => $status,
                'actual_distance' => $actualDistance,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);

            // Release previous assets to 'available' if changed or trip terminal status reached
            if ($oldStatus === 'dispatched') {
                if ($newVehicleId !== $oldVehicleId || in_array($status, ['completed', 'cancelled'], true)) {
                    $relVeh = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = :id");
                    $relVeh->execute(['id' => $oldVehicleId]);
                }
                if ($newDriverId !== $oldDriverId || in_array($status, ['completed', 'cancelled'], true)) {
                    $relDrv = $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = :id");
                    $relDrv->execute(['id' => $oldDriverId]);
                }
            }

            // Automation: Lock newly assigned assets to on_trip status
            if ($status === 'dispatched') {
                $bindVeh = $pdo->prepare("UPDATE vehicles SET status = 'on_trip' WHERE id = :id");
                $bindVeh->execute(['id' => $newVehicleId]);

                $bindDrv = $pdo->prepare("UPDATE drivers SET status = 'on_trip' WHERE id = :id");
                $bindDrv->execute(['id' => $newDriverId]);
            }

            $pdo->commit();
            self::logAction('trips', $id, 'UPDATE', "Updated trip status to '{$status}'");
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException("Trip update failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Retrieve trip profile details by ID.
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Retrieve all logged trips.
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT t.*, v.vehicle_name, v.registration_number, d.name as driver_name 
            FROM trips t
            JOIN vehicles v ON t.vehicle_id = v.id
            JOIN drivers d ON t.driver_id = d.id
            ORDER BY t.id DESC
        ");
        return $stmt->fetchAll();
    }
}
