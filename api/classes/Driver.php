<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;
use InvalidArgumentException;

/**
 * Driver entity class managing CRUD operations and business validation rules.
 */
class Driver {
    /**
     * Log driver operations to the audit trail.
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
     * Validate driver data prior to saving.
     *
     * @param array $data Input attributes
     * @param int|null $excludeId ID to exclude during uniqueness checks
     * @throws InvalidArgumentException If validation fails
     */
    public static function validate(array $data, ?int $excludeId = null): void {
        if (empty($data['license_number'])) {
            throw new InvalidArgumentException("License number is required.");
        }
        if (empty($data['name'])) {
            throw new InvalidArgumentException("Driver name is required.");
        }
        if (empty($data['license_category'])) {
            throw new InvalidArgumentException("License category is required.");
        }
        if (empty($data['license_expiry_date'])) {
            throw new InvalidArgumentException("License expiry date is required.");
        }
        if (strtotime($data['license_expiry_date']) === false) {
            throw new InvalidArgumentException("Invalid license expiry date format.");
        }
        if (isset($data['safety_score'])) {
            $score = (float)$data['safety_score'];
            if ($score < 0.00 || $score > 5.00) {
                throw new InvalidArgumentException("Safety score must be between 0.00 and 5.00.");
            }
        }
        if (!in_array($data['status'] ?? 'available', ['available', 'on_trip', 'off_duty', 'suspended'], true)) {
            throw new InvalidArgumentException("Invalid driver status.");
        }

        // Validate unique license number across non-deleted records
        $pdo = Database::getInstance();
        $sql = "SELECT id FROM drivers WHERE license_number = :license AND is_deleted = 0";
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $pdo->prepare($sql);
        $params = ['license' => $data['license_number']];
        if ($excludeId !== null) {
            $params['id'] = $excludeId;
        }
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException("The license number '" . htmlspecialchars($data['license_number']) . "' is already in use.");
        }
    }

    /**
     * Create a new driver record.
     *
     * @param array $data Attribute mapping
     * @return int Inserted ID
     */
    public static function create(array $data): int {
        self::validate($data);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO drivers (license_number, name, license_category, license_expiry_date, contact_number, safety_score, status, email)
            VALUES (:license, :name, :category, :expiry, :contact, :safety, :status, :email)
        ");

        $stmt->execute([
            'license' => $data['license_number'],
            'name' => $data['name'],
            'category' => $data['license_category'],
            'expiry' => $data['license_expiry_date'],
            'contact' => !empty($data['contact_number']) ? $data['contact_number'] : null,
            'safety' => (float)($data['safety_score'] ?? 5.00),
            'status' => $data['status'] ?? 'available',
            'email' => !empty($data['email']) ? $data['email'] : null
        ]);

        $driverId = (int)$pdo->lastInsertId();
        self::logAction('drivers', $driverId, 'CREATE', "Registered driver '{$data['name']}' [{$data['license_number']}]");
        return $driverId;
    }

    /**
     * Update an existing driver record.
     *
     * @param int $id The driver ID
     * @param array $data Attribute mapping
     * @return bool Success status
     */
    public static function update(int $id, array $data): bool {
        self::validate($data, $id);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            UPDATE drivers 
            SET license_number = :license, 
                name = :name, 
                license_category = :category, 
                license_expiry_date = :expiry, 
                contact_number = :contact, 
                safety_score = :safety, 
                status = :status, 
                email = :email
            WHERE id = :id AND is_deleted = 0
        ");

        $success = $stmt->execute([
            'id' => $id,
            'license' => $data['license_number'],
            'name' => $data['name'],
            'category' => $data['license_category'],
            'expiry' => $data['license_expiry_date'],
            'contact' => !empty($data['contact_number']) ? $data['contact_number'] : null,
            'safety' => (float)($data['safety_score'] ?? 5.00),
            'status' => $data['status'] ?? 'available',
            'email' => !empty($data['email']) ? $data['email'] : null
        ]);

        if ($success) {
            self::logAction('drivers', $id, 'UPDATE', "Updated driver details");
        }
        return $success;
    }

    /**
     * Perform soft-delete marking the driver as deleted.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE drivers SET is_deleted = 1 WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            self::logAction('drivers', $id, 'DELETE', "Soft-deleted driver");
        }
        return $success;
    }

    /**
     * Get a driver by ID.
     *
     * @param int $id
     * @return array|null The driver data or null
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = :id AND is_deleted = 0 LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Get all active (non-deleted) drivers.
     *
     * @return array
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM drivers WHERE is_deleted = 0 ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Get only 'available' drivers who are not suspended and hold a valid, non-expired license.
     *
     * @return array
     */
    public static function getAvailable(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT * 
            FROM drivers 
            WHERE status = 'available' 
              AND is_deleted = 0 
              AND license_expiry_date >= CURDATE() 
            ORDER BY id DESC
        ");
        return $stmt->fetchAll();
    }
}
