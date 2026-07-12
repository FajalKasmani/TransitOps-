<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;
use InvalidArgumentException;

/**
 * Expense entity class managing general fleet operating expenses.
 */
class Expense {
    /**
     * Log expense actions to the audit trail.
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
     * Validate expense log properties.
     */
    public static function validate(array $data): void {
        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException("Vehicle assignment is required.");
        }
        if (!in_array($data['type'] ?? '', ['toll', 'maintenance', 'other'], true)) {
            throw new InvalidArgumentException("Invalid expense type. Allowed types are: toll, maintenance, other.");
        }
        if (empty($data['description'])) {
            throw new InvalidArgumentException("Description is required.");
        }
        if (isset($data['cost']) && (float)$data['cost'] < 0) {
            throw new InvalidArgumentException("Expense cost cannot be negative.");
        }
        if (empty($data['date']) || strtotime($data['date']) === false) {
            throw new InvalidArgumentException("A valid date is required.");
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
     * Create a new expense log entry.
     */
    public static function create(array $data): int {
        self::validate($data);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO expenses (vehicle_id, type, description, cost, date)
            VALUES (:vehicle_id, :type, :description, :cost, :date)
        ");

        $stmt->execute([
            'vehicle_id' => (int)$data['vehicle_id'],
            'type' => $data['type'],
            'description' => $data['description'],
            'cost' => (float)($data['cost'] ?? 0.00),
            'date' => $data['date']
        ]);

        $logId = (int)$pdo->lastInsertId();
        self::logAction('expenses', $logId, 'CREATE', "Logged expense type '{$data['type']}' for vehicle #{$data['vehicle_id']}");
        return $logId;
    }

    /**
     * Update an existing expense log entry.
     */
    public static function update(int $id, array $data): bool {
        self::validate($data);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            UPDATE expenses 
            SET vehicle_id = :vehicle_id, 
                type = :type, 
                description = :description, 
                cost = :cost, 
                date = :date
            WHERE id = :id
        ");

        $success = $stmt->execute([
            'id' => $id,
            'vehicle_id' => (int)$data['vehicle_id'],
            'type' => $data['type'],
            'description' => $data['description'],
            'cost' => (float)($data['cost'] ?? 0.00),
            'date' => $data['date']
        ]);

        if ($success) {
            self::logAction('expenses', $id, 'UPDATE', "Updated expense specifications");
        }
        return $success;
    }

    /**
     * Delete an expense log entry.
     */
    public static function delete(int $id): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            self::logAction('expenses', $id, 'DELETE', "Deleted expense log record");
        }
        return $success;
    }

    /**
     * Fetch an expense log by ID.
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Retrieve all expense logs.
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT e.*, v.vehicle_name, v.registration_number 
            FROM expenses e
            JOIN vehicles v ON e.vehicle_id = v.id
            ORDER BY e.id DESC
        ");
        return $stmt->fetchAll();
    }
}
