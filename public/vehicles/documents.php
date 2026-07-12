<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';
require_once __DIR__ . '/../../api/classes/Document.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;
use Api\Classes\Document;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager'])) {
    header("Location: ../index.php");
    exit;
}

$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$vehicle = Vehicle::getById($vehicleId);

if (!$vehicle) {
    header("Location: list.php?error=not_found");
    exit;
}

$error = null;
$success = null;

// Handle Document Uploads & Deletes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'upload';
    $csrfToken = $_POST['csrf_token'] ?? '';

    try {
        if (!Auth::verifyCsrfToken($csrfToken)) {
            throw new Exception("CSRF verification failed. Request blocked.");
        }

        if ($action === 'upload') {
            $docType = trim($_POST['document_type'] ?? '');
            $expiryDate = trim($_POST['expiry_date'] ?? '');
            
            if (empty($docType)) {
                throw new Exception("Document type is required.");
            }

            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please select a valid file to upload.");
            }

            Document::upload($vehicleId, $docType, $_FILES['document'], !empty($expiryDate) ? $expiryDate : null);
            $success = "Document uploaded and registered successfully.";
        } elseif ($action === 'delete') {
            $docId = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;
            if (Document::delete($docId)) {
                $success = "Document removed successfully.";
            } else {
                throw new Exception("Failed to remove document.");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all documents for this vehicle
$docs = Document::getByVehicleId($vehicleId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme"><i class="bi bi-file-earmark-medical me-2 text-primary"></i>Document Center</h3>
            <p class="text-muted small mb-0">Manage registration certificates, insurance, and receipts for <strong><?php echo htmlspecialchars($vehicle['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></strong> (<?php echo htmlspecialchars($vehicle['registration_number'], ENT_QUOTES, 'UTF-8'); ?>).</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Fleet
        </a>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Upload Document Form -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-cloud-upload me-2 text-info"></i>Upload Document</h5>
                <form method="POST" action="documents.php?vehicle_id=<?php echo $vehicleId; ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php echo Auth::getCsrfInput(); ?>
                    <input type="hidden" name="action" value="upload">

                    <!-- Document Type -->
                    <div class="form-floating mb-3">
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="">Choose type...</option>
                            <option value="registration">Registration Certificate</option>
                            <option value="insurance">Insurance Policy</option>
                            <option value="fuel_receipt">Fuel Receipt</option>
                            <option value="permit">Operational Permit</option>
                            <option value="other">Other Attachment</option>
                        </select>
                        <label for="document_type" class="text-muted">Document Category *</label>
                    </div>

                    <!-- Expiry Date -->
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" placeholder="Expiry Date">
                        <label for="expiry_date" class="text-muted">Expiry Date (Optional)</label>
                    </div>

                    <!-- File Select -->
                    <div class="mb-4">
                        <label for="document" class="form-label fw-semibold text-muted small mb-1">Select File (PDF, PNG, JPG, JPEG - Max 5MB)</label>
                        <input class="form-control" type="file" id="document" name="document" required style="border-radius: 10px;">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-semibold py-2" style="border-radius: 10px;"><i class="bi bi-upload me-2"></i>Upload File</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Document Grid List -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-richtext me-2 text-primary"></i>Roster Documents</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>File Name</th>
                                <th>Expiry Date</th>
                                <th>Uploaded At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No documents uploaded for this vehicle.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($docs as $d): 
                                    $isExpired = $d['expiry_date'] && strtotime($d['expiry_date']) < time();
                                    $categoryLabel = match($d['document_type']) {
                                        'registration' => 'Registration Paper',
                                        'insurance' => 'Insurance Policy',
                                        'fuel_receipt' => 'Fuel Receipt',
                                        'permit' => 'Operational Permit',
                                        default => 'Other'
                                    };
                                ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo $categoryLabel; ?></span></td>
                                        <td>
                                            <a href="download_doc.php?id=<?php echo $d['id']; ?>" target="_blank" class="fw-semibold text-decoration-none">
                                                <i class="bi bi-file-earmark-arrow-down me-1"></i>View File
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($d['expiry_date']): ?>
                                                <span class="<?php echo $isExpired ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                                    <?php echo $d['expiry_date']; ?>
                                                    <?php if ($isExpired): ?> <span class="badge bg-danger ms-1">Expired</span><?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">No Expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?php echo $d['uploaded_at']; ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="documents.php?vehicle_id=<?php echo $vehicleId; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this document?');">
                                                <?php echo Auth::getCsrfInput(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="doc_id" value="<?php echo $d['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
