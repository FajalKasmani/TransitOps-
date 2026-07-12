<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Document.php';

use Api\Classes\Auth;
use Api\Classes\Document;

Auth::startSession();

// Check if the user is authorized to access documents
if (!Auth::checkAccess(['admin', 'fleet_manager'])) {
    http_response_code(403);
    echo "Forbidden: Access Denied.";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc = Document::getById($id);

if (!$doc) {
    http_response_code(404);
    echo "Error: Document not found.";
    exit;
}

$uploadDir = __DIR__ . '/../../uploads';
$fullPath = $uploadDir . '/' . $doc['file_path'];

if (!file_exists($fullPath)) {
    http_response_code(404);
    echo "Error: File does not exist on storage server.";
    exit;
}

// Clean output buffer to prevent corrupted file delivery
if (ob_get_level()) {
    ob_end_clean();
}

$mime = mime_content_type($fullPath);
header("Content-Type: " . $mime);
header("Content-Length: " . filesize($fullPath));
header("Content-Disposition: inline; filename=\"" . basename($doc['file_path']) . "\"");
header("Cache-Control: private, max-age=0, must-revalidate");
header("Pragma: public");

readfile($fullPath);
exit;
