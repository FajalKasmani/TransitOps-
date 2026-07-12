<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Driver;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;
$success = null;
$importErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    try {
        if (!Auth::verifyCsrfToken($csrfToken)) {
            throw new Exception("CSRF security verification failed. Request blocked.");
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload a valid CSV file.");
        }

        $filePath = $_FILES['csv_file']['tmp_name'];
        
        // Validate MIME/extension
        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            throw new Exception("Only CSV files are allowed.");
        }

        if (($handle = fopen($filePath, "r")) !== false) {
            $rowNum = 0;
            $importedCount = 0;

            // Optional: read header row
            $header = fgetcsv($handle, 1000, ",");
            $rowNum++; // row 1 is header

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowNum++;
                // Skip empty rows
                if (empty($data) || count($data) < 3) {
                    continue;
                }

                // Map columns: license_number, name, category, expiry_date, contact, safety_score, status, email
                $licenseNumber = trim($data[0] ?? '');
                $name = trim($data[1] ?? '');
                $category = trim($data[2] ?? '');
                $expiryDate = trim($data[3] ?? '');
                $contact = trim($data[4] ?? '');
                $safetyScore = isset($data[5]) && $data[5] !== '' ? (float)$data[5] : 5.00;
                $status = trim($data[6] ?? 'available');
                $email = trim($data[7] ?? '');

                if ($status === '') {
                    $status = 'available';
                }

                $driverData = [
                    'license_number' => $licenseNumber,
                    'name' => $name,
                    'license_category' => $category,
                    'license_expiry_date' => $expiryDate,
                    'contact_number' => $contact,
                    'safety_score' => $safetyScore,
                    'status' => $status,
                    'email' => $email
                ];

                try {
                    Driver::create($driverData);
                    $importedCount++;
                } catch (Exception $e) {
                    $importErrors[] = "Row {$rowNum} (License: {$licenseNumber}): " . $e->getMessage();
                }
            }
            fclose($handle);

            if ($importedCount > 0) {
                $success = "Successfully imported {$importedCount} drivers.";
            }
            if (!empty($importErrors)) {
                $error = "Import completed with some errors.";
            }
        } else {
            throw new Exception("Failed to open uploaded CSV file.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    :root {
        --bg-dark-canvas: #0f172a; /* Slate 900 */
        --bg-dark-card: #1e293b;   /* Slate 800 */
        --border-dark: #334155;     /* Slate 700 */
        --text-primary: #f8fafc;    /* Slate 50 */
        --text-secondary: #94a3b8;  /* Slate 400 */
        
        --accent-gradient: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
        --input-focus-glow: rgba(59, 130, 246, 0.25);
    }

    .dark-theme-wrapper {
        color: var(--text-primary);
    }
    
    .ui-card-dark {
        background-color: var(--bg-dark-card) !important;
        border: 1px solid var(--border-dark) !important;
        border-radius: 16px !important;
    }

    /* Custom Input File Styling for Dark Canvas */
    .ui-card-dark .form-control[type="file"] {
        background-color: #111827 !important;
        border: 1.5px solid var(--border-dark);
        color: var(--text-primary);
        border-radius: 12px;
        padding: 12px;
    }

    .ui-card-dark .form-control[type="file"]::file-selector-button {
        background-color: #1e293b;
        color: #38bdf8;
        border: 1px solid var(--border-dark);
        border-radius: 8px;
        padding: 4px 12px;
        transition: background 0.2s;
    }

    .ui-card-dark .form-control[type="file"]::file-selector-button:hover {
        background-color: #334155;
    }

    .ui-card-dark .form-control[type="file"]:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 4px var(--input-focus-glow) !important;
    }

    .custom-gradient-btn {
        background: var(--accent-gradient);
        border: none;
        color: #fff;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .custom-gradient-btn:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        color: #fff;
    }

    .back-btn-dark {
        background: #1e293b;
        border: 1px solid var(--border-dark) !important;
        color: var(--text-secondary) !important;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .back-btn-dark:hover {
        background: #334155;
        color: var(--text-primary) !important;
    }

    /* Deep Code Block styling */
    .dark-code-block {
        background-color: #0b0f19 !important;
        border: 1px solid var(--border-dark);
        font-family: var(--bs-font-monospace);
    }
</style>

<div class="container-fluid py-4 dark-theme-wrapper">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 pb-3 border-bottom border-secondary-subtle" style="border-color: var(--border-dark) !important;">
        <div>
            <h3 class="fw-bold tracking-tight text-white mb-1"><i class="bi bi-file-earmark-spreadsheet me-2 text-info"></i>Bulk Import Drivers</h3>
            <p class="small mb-0" style="color: var(--text-secondary);">Onboard multiple drivers to the roster using a standard CSV template.</p>
        </div>
        <a href="list.php" class="btn btn-sm back-btn-dark px-3 py-2 d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Roster
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger bg-danger-subtle text-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Import Problem Identified</strong>
                <span class="small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success bg-success-subtle text-success border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Process Complete</strong>
                <span class="small"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card ui-card-dark border-0 shadow-lg p-4">
                <h5 class="fw-bold mb-3 text-white"><i class="bi bi-upload me-2 text-info"></i>Upload CSV File</h5>
                <form method="POST" action="import.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php echo Auth::getCsrfInput(); ?>
                    
                    <div class="mb-4">
                        <label for="csv_file" class="form-label fw-semibold small" style="color: var(--text-secondary);">Choose CSV File *</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>

                    <div class="d-grid mb-2">
                        <button type="submit" class="btn custom-gradient-btn py-2.5" style="border-radius: 12px;">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Parse & Import Roster
                        </button>
                    </div>
                </form>

                <hr style="border-color: var(--border-dark); opacity: 1;" class="my-4">

                <h6 class="fw-bold small mb-2 text-white-50">CSV Template Structure:</h6>
                <div class="small mb-3" style="color: var(--text-secondary);">
                    Your CSV must contain a header row. Ensure columns are structured in this exact sequence:
                </div>
                <div class="dark-code-block p-3 rounded small text-info mb-0 overflow-x-auto">
                    <code>license_number, name, license_category, license_expiry_date, contact_number, safety_score, status, email</code>
                </div>
                <div class="small mt-3" style="color: var(--text-secondary); line-height: 1.6;">
                    <span class="text-warning">*</span> <strong>license_expiry_date</strong>: <span class="text-white-50">YYYY-MM-DD</span> format<br>
                    <span class="text-warning">*</span> <strong>status</strong>: <span class="badge bg-dark border border-secondary text-light">available</span>, <span class="badge bg-dark border border-secondary text-light">on_trip</span>, <span class="badge bg-dark border border-secondary text-light">off_duty</span>, <span class="badge bg-dark border border-secondary text-light">suspended</span>
                </div>
            </div>
        </div>

        <?php if (!empty($importErrors)): ?>
            <div class="col-12 col-lg-7">
                <div class="card ui-card-dark border-0 shadow-lg p-4 border-start border-danger border-4">
                    <h5 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-octagon me-2"></i>Row Validation Failures</h5>
                    <div class="small mb-3" style="color: var(--text-secondary);">
                        The following rows could not be processed due to validation conflicts (e.g., duplicate unique licenses or structural discrepancies):
                    </div>
                    <div class="dark-code-block p-3 rounded" style="max-height: 340px; overflow-y: auto;">
                        <ul class="text-danger mb-0 ps-3 small" style="line-height: 1.7;">
                            <?php foreach ($importErrors as $rowErr): ?>
                                <li><?php echo htmlspecialchars($rowErr, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>