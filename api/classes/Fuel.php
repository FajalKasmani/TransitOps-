<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;
use InvalidArgumentException;

/**
 * Fuel entity class managing fuel purchase logs.
 */
class Fuel {
    /**
     * Log fuel actions to the audit trail.
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
            // Fail-safe execution
        }
    }

    /**
     * Validate fuel properties.
     */
    public static function validate(array $data): void {
        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException("Vehicle assignment is required.");
        }
        if (!isset($data['liters']) || (float)$data['liters'] <= 0) {
            throw new InvalidArgumentException("Liters must be greater than 0.");
        }
        if (isset($data['cost']) && (float)$data['cost'] < 0) {
            throw new InvalidArgumentException("Fuel cost cannot be negative.");
        }
        if (empty($data['date']) || strtotime($data['date']) === false) {
            throw new InvalidArgumentException("A valid purchase date is required.");
        }

        // Verify vehicle exists in non-deleted registry
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = :id AND is_deleted = 0");
        $stmt->execute(['id' => (int)$data['vehicle_id']]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException("Selected vehicle does not exist or has been deleted.");
        }
    }

    /**
     * Create a new fuel log entry.
     */
    public static function create(array $data): int {
        self::validate($data);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO fuel_logs (vehicle_id, liters, cost, date)
            VALUES (:vehicle_id, :liters, :cost, :date)
        ");

        $stmt->execute([
            'vehicle_id' => (int)$data['vehicle_id'],
            'liters' => (float)$data['liters'],
            'cost' => (float)($data['cost'] ?? 0.00),
            'date' => $data['date']
        ]);

        $logId = (int)$pdo->lastInsertId();
        self::logAction('fuel_logs', $logId, 'CREATE', "Logged fuel purchase of {$data['liters']} liters for vehicle #{$data['vehicle_id']}");
        return $logId;
    }

    /**
     * Update an existing fuel log entry.
     */
    public static function update(int $id, array $data): bool {
        self::validate($data);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            UPDATE fuel_logs 
            SET vehicle_id = :vehicle_id, 
                liters = :liters, 
                cost = :cost, 
                date = :date
            WHERE id = :id
        ");

        $success = $stmt->execute([
            'id' => $id,
            'vehicle_id' => (int)$data['vehicle_id'],
            'liters' => (float)$data['liters'],
            'cost' => (float)($data['cost'] ?? 0.00),
            'date' => $data['date']
        ]);

        if ($success) {
            self::logAction('fuel_logs', $id, 'UPDATE', "Updated fuel log specification");
        }
        return $success;
    }

    /**
     * Delete a fuel log entry.
     */
    public static function delete(int $id): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM fuel_logs WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            self::logAction('fuel_logs', $id, 'DELETE', "Deleted fuel log record");
        }
        return $success;
    }

    /**
     * Fetch a fuel log by ID.
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM fuel_logs WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Retrieve all fuel logs.
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT f.*, v.vehicle_name, v.registration_number 
            FROM fuel_logs f
            JOIN vehicles v ON f.vehicle_id = v.id
            ORDER BY f.id DESC
        ");
        return $stmt->fetchAll();
    }
}
