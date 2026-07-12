<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use RuntimeException;
use InvalidArgumentException;

/**
 * Maintenance entity class managing vehicle maintenance logs and lifecycle syncs.
 */
class Maintenance {
    /**
     * Log maintenance activities to the audit trail.
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
     * Validate maintenance data fields.
     */
    public static function validate(array $data): void {
        if (empty($data['vehicle_id'])) {
            throw new InvalidArgumentException("Vehicle assignment is required.");
        }
        if (empty($data['description'])) {
            throw new InvalidArgumentException("Description is required.");
        }
        if (empty($data['date']) || strtotime($data['date']) === false) {
            throw new InvalidArgumentException("A valid date is required.");
        }
        if (isset($data['cost']) && (float)$data['cost'] < 0) {
            throw new InvalidArgumentException("Maintenance cost cannot be negative.");
        }
        if (!in_array($data['status'] ?? 'open', ['open', 'closed'], true)) {
            throw new InvalidArgumentException("Invalid status.");
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
     * Create a new maintenance record and update vehicle state inside a transaction.
     */
    public static function create(array $data): int {
        self::validate($data);

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_logs (vehicle_id, description, cost, date, status, notes)
                VALUES (:vehicle_id, :description, :cost, :date, :status, :notes)
            ");

            $stmt->execute([
                'vehicle_id' => (int)$data['vehicle_id'],
                'description' => $data['description'],
                'cost' => (float)($data['cost'] ?? 0.00),
                'date' => $data['date'],
                'status' => $data['status'] ?? 'open',
                'notes' => !empty($data['notes']) ? $data['notes'] : null
            ]);

            $logId = (int)$pdo->lastInsertId();

            // Automation: Lock vehicle to 'in_shop' status if maintenance is open
            if (($data['status'] ?? 'open') === 'open') {
                $updVeh = $pdo->prepare("UPDATE vehicles SET status = 'in_shop' WHERE id = :id");
                $updVeh->execute(['id' => (int)$data['vehicle_id']]);
            }

            $pdo->commit();
            self::logAction('maintenance_logs', $logId, 'CREATE', "Logged maintenance record for vehicle #{$data['vehicle_id']}");
            return $logId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException("Failed to create maintenance log: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Update a maintenance record and adjust vehicle state inside a transaction.
     */
    public static function update(int $id, array $data): bool {
        self::validate($data);

        $pdo = Database::getInstance();

        $currStmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = :id LIMIT 1");
        $currStmt->execute(['id' => $id]);
        $oldLog = $currStmt->fetch();
        if (!$oldLog) {
            throw new InvalidArgumentException("Maintenance log not found.");
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE maintenance_logs 
                SET vehicle_id = :vehicle_id, 
                    description = :description, 
                    cost = :cost, 
                    date = :date, 
                    status = :status, 
                    notes = :notes
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $id,
                'vehicle_id' => (int)$data['vehicle_id'],
                'description' => $data['description'],
                'cost' => (float)($data['cost'] ?? 0.00),
                'date' => $data['date'],
                'status' => $data['status'] ?? 'open',
                'notes' => !empty($data['notes']) ? $data['notes'] : null
            ]);

            $newStatus = $data['status'] ?? 'open';
            $oldStatus = $oldLog['status'];
            $newVehId = (int)$data['vehicle_id'];
            $oldVehId = (int)$oldLog['vehicle_id'];

            // Release old vehicle from shop status if vehicle changed
            if ($newVehId !== $oldVehId) {
                if ($oldStatus === 'open') {
                    $resVeh = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = :id AND status = 'in_shop'");
                    $resVeh->execute(['id' => $oldVehId]);
                }
            }

            // Automations: adjust vehicle status
            if ($newStatus === 'open') {
                $updVeh = $pdo->prepare("UPDATE vehicles SET status = 'in_shop' WHERE id = :id");
                $updVeh->execute(['id' => $newVehId]);
            } else if ($newStatus === 'closed') {
                $updVeh = $pdo->prepare("
                    UPDATE vehicles 
                    SET status = 'available' 
                    WHERE id = :id AND status != 'retired'
                ");
                $updVeh->execute(['id' => $newVehId]);
            }

            $pdo->commit();
            self::logAction('maintenance_logs', $id, 'UPDATE', "Updated maintenance configurations");
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException("Failed to update maintenance log: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Fetch a maintenance log by ID.
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Retrieve all maintenance logs.
     */
    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT m.*, v.vehicle_name, v.registration_number 
            FROM maintenance_logs m
            JOIN vehicles v ON m.vehicle_id = v.id
            ORDER BY m.id DESC
        ");
        return $stmt->fetchAll();
    }
}
