<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use Exception;
use RuntimeException;

/**
 * Service class to manage secure vehicle document uploads and storage.
 */
class Document {
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
            // Fail silently
        }
    }

    /**
     * Upload and register a document for a vehicle.
     *
     * @param int $vehicleId
     * @param string $docType (e.g. registration, insurance, receipt)
     * @param array $file $_FILES array element
     * @param string|null $expiryDate Expiry date of document
     * @return int The inserted document ID
     * @throws Exception on invalid file types or upload issues
     */
    public static function upload(int $vehicleId, string $docType, array $file, ?string $expiryDate = null): int {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed with error code: " . $file['error']);
        }

        // Validate File Size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("File size exceeds 5MB limit.");
        }

        // Validate File Type
        $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception("Invalid file type. Allowed types: PDF, PNG, JPG, JPEG.");
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new Exception("Invalid file content MIME type.");
        }

        // Check if vehicle exists and is active
        $vehicle = Vehicle::getById($vehicleId);
        if (!$vehicle) {
            throw new Exception("Target vehicle not found or inactive.");
        }

        // Define secure path outside public directory
        $uploadDir = __DIR__ . '/../../uploads';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory.");
            }
        }

        // Generate unique name to prevent collisions/information disclosure
        $filename = sprintf(
            "vehicle_%d_%d_%s.%s",
            $vehicleId,
            time(),
            bin2hex(random_bytes(8)),
            $extension
        );
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new Exception("Failed to store uploaded file.");
        }

        // Save path in database
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_documents (vehicle_id, document_type, file_path, expiry_date) 
            VALUES (:vehicle_id, :document_type, :file_path, :expiry_date)
        ");
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'document_type' => trim($docType),
            'file_path' => $filename, // Save only filename for portability
            'expiry_date' => !empty($expiryDate) ? $expiryDate : null
        ]);

        $docId = (int)$pdo->lastInsertId();
        self::logAction('vehicles', $vehicleId, 'UPLOAD_DOC', "Uploaded document (Type: $docType) ID: $docId");

        return $docId;
    }

    /**
     * Retrieve all documents registered to a vehicle.
     *
     * @param int $vehicleId
     * @return array
     */
    public static function getByVehicleId(int $vehicleId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT * 
            FROM vehicle_documents 
            WHERE vehicle_id = :vehicle_id 
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return $stmt->fetchAll();
    }

    /**
     * Get details of a single document.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM vehicle_documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Delete document record and physical file.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool {
        $doc = self::getById($id);
        if (!$doc) {
            return false;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM vehicle_documents WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);

        if ($success) {
            $filePath = __DIR__ . '/../../uploads/' . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            self::logAction('vehicles', (int)$doc['vehicle_id'], 'DELETE_DOC', "Removed document ID: $id");
        }

        return $success;
    }
}
