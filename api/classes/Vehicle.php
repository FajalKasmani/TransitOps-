<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;
use InvalidArgumentException;

/**
 * Vehicle entity class managing CRUD operations and business validation rules.
 */
class Vehicle {
    /**
     * Log vehicle operations to the audit trail.
     */
    private static function logAction(string $entity, int $entityId, string $action, string $details = null): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
            // Fail-safe execution: audit logging should not break the core transaction
        }
    }

    /**
     * Validate the vehicle fields before insertion or update.
     *
     * @param array $data Input properties
     * @param int|null $excludeId ID to exclude during uniqueness checks
     * @throws InvalidArgumentException If inputs violate business rules
     */
    public static function validate(array $data, ?int $excludeId = null): void {
        if (empty($data['registration_number'])) {
            throw new InvalidArgumentException("Registration number is required.");
        }
        if (empty($data['vehicle_name'])) {
            throw new InvalidArgumentException("Vehicle name is required.");
        }
        if (!in_array($data['type'] ?? '', ['car', 'van', 'truck', 'motorcycle'], true)) {
            throw new InvalidArgumentException("Invalid vehicle type. Allowed types are: car, van, truck, motorcycle.");
        }
        if (!isset($data['max_load_capacity']) || (float)$data['max_load_capacity'] <= 0) {
            throw new InvalidArgumentException("Maximum load capacity must be greater than 0.");
        }
        if (isset($data['odometer']) && (float)$data['odometer'] < 0) {
            throw new InvalidArgumentException("Odometer reading cannot be negative.");
        }
        if (isset($data['acquisition_cost']) && (float)$data['acquisition_cost'] < 0) {
            throw new InvalidArgumentException("Acquisition cost cannot be negative.");
        }
        if (!in_array($data['status'] ?? 'available', ['available', 'on_trip', 'in_shop', 'retired'], true)) {
            throw new InvalidArgumentException("Invalid vehicle status.");
        }

        // Enforce registration number uniqueness across non-deleted records
        $pdo = Database::getInstance();
        $sql = "SELECT id FROM vehicles WHERE registration_number = :reg AND is_deleted = 0";
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $pdo->prepare($sql);
        $params = ['reg' => $data['registration_number']];
        if ($excludeId !== null) {
            $params['id'] = $excludeId;
        }
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException("The registration number '" . htmlspecialchars($data['registration_number']) . "' is already in use.");
        }
    }

    /**
     * Insert a new vehicle into the registry.
     *
     * @param array $data Property mapping
     * @return int Inserted ID
     */
    public static function create(array $data): int {
        self::validate($data);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (registration_number, vehicle_name, type, max_load_capacity, odometer, acquisition_cost, status, region)
            VALUES (:reg, :name, :type, :capacity, :odometer, :cost, :status, :region)
        ");
        
        $stmt->execute([
            'reg' => $data['registration_number'],
            'name' => $data['vehicle_name'],
            'type' => $data['type'],
            'capacity' => (float)$data['max_load_capacity'],
            'odometer' => (float)($data['odometer'] ?? 0.00),
            'cost' => (float)($data['acquisition_cost'] ?? 0.00),
            'status' => $data['status'] ?? 'available',
            'region' => !empty($data['region']) ? $data['region'] : null
        ]);

        $vehicleId = (int)$pdo->lastInsertId();
        self::logAction('vehicles', $vehicleId, 'CREATE', "Registered vehicle '{$data['vehicle_name']}' [{$data['registration_number']}]");
        return $vehicleId;
    }

    /**
     * Update vehicle registry settings.
     *
     * @param int $id The target vehicle ID
     * @param array $data Property mapping
     * @return bool Success status
     */
    public static function update(int $id, array $data): bool {
        self::validate($data, $id);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            UPDATE vehicles 
            SET registration_number = :reg, 
                vehicle_name = :name, 
                type = :type, 
                max_load_capacity = :capacity, 
                odometer = :odometer, 
                acquisition_cost = :cost, 
                status = :status, 
                region = :region
            WHERE id = :id AND is_deleted = 0
        ");

        $success = $stmt->execute([
            'id' => $id,
            'reg' => $data['registration_number'],
            'name' => $data['vehicle_name'],
            'type' => $data['type'],
            'capacity' => (float)$data['max_load_capacity'],
            'odometer' => (float)($data['odometer'] ?? 0.00),
            'cost' => (float)($data['acquisition_cost'] ?? 0.00),
            'status' => $data['status'] ?? 'available',
            'region' => !empty($data['region']) ? $data['region'] : null
        ]);

        if ($success) {
            self::logAction('vehicles', $id, 'UPDATE', "Updated vehicle specifications");
        }
        return $success;
    }

    /**
     * Perform a soft-delete marking the vehicle as deleted.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE vehicles SET is_deleted = 1 WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            self::logAction('vehicles', $id, 'DELETE', "Soft-deleted vehicle from registry");
        }
        return $success;
    }

    /**
     * Fetch a vehicle entry by ID.
     *
     * @param int $id
     * @return array|null The vehicle data or null if not found
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id AND is_deleted = 0 LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Retrieve all non-deleted vehicles.
     *
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM vehicles WHERE is_deleted = 0 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Retrieve only 'available' vehicles suitable for dispatch.
     *
     * @return array
     */
    public static function getAvailable(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' AND is_deleted = 0 ORDER BY id DESC");
        return $stmt->fetchAll();
    }
}
