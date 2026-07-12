<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;

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

                // Map columns: reg_number, name, type, max_load, odometer, cost, status, region
                $regNumber = trim($data[0] ?? '');
                $name = trim($data[1] ?? '');
                $type = trim($data[2] ?? '');
                $maxLoad = isset($data[3]) && $data[3] !== '' ? (float)$data[3] : 0.00;
                $odometer = isset($data[4]) && $data[4] !== '' ? (float)$data[4] : 0.00;
                $cost = isset($data[5]) && $data[5] !== '' ? (float)$data[5] : 0.00;
                $status = trim($data[6] ?? 'available');
                $region = trim($data[7] ?? '');

                if ($status === '') {
                    $status = 'available';
                }

                $vehicleData = [
                    'registration_number' => $regNumber,
                    'vehicle_name' => $name,
                    'type' => $type,
                    'max_load_capacity' => $maxLoad,
                    'odometer' => $odometer,
                    'acquisition_cost' => $cost,
                    'status' => $status,
                    'region' => $region
                ];

                try {
                    Vehicle::create($vehicleData);
                    $importedCount++;
                } catch (Exception $e) {
                    $importErrors[] = "Row {$rowNum} (Reg: {$regNumber}): " . $e->getMessage();
                }
            }
            fclose($handle);

            if ($importedCount > 0) {
                $success = "Successfully imported {$importedCount} vehicles.";
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

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme"><i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Bulk Import Vehicles</h3>
            <p class="text-muted small mb-0">Onboard multiple fleet vehicles using a standard CSV template.</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Fleet
        </a>
    </div>

    <!-- Alert Messaging -->
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
        <!-- Import Form -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-upload me-2 text-info"></i>Upload CSV File</h5>
                <form method="POST" action="import.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?php echo Auth::getCsrfInput(); ?>
                    
                    <div class="mb-4">
                        <label for="csv_file" class="form-label fw-semibold text-muted small">Choose CSV File *</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required style="border-radius: 10px;">
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary fw-semibold py-2" style="border-radius: 10px;">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Parse & Import Fleet
                        </button>
                    </div>
                </form>

                <hr class="border-secondary my-4">

                <h6 class="fw-bold text-muted small mb-2">CSV Template Structure:</h6>
                <div class="small text-muted mb-3">
                    Your CSV must contain a header row. Ensure columns are structured in this exact sequence:
                </div>
                <div class="bg-black bg-opacity-25 p-3 rounded small text-info mb-0">
                    <code>registration_number, vehicle_name, type, max_load_capacity, odometer, acquisition_cost, status, region</code>
                </div>
                <div class="small text-muted mt-2">
                    * <strong>type</strong>: car, van, truck, motorcycle<br>
                    * <strong>status</strong>: available, on_trip, in_shop, retired
                </div>
            </div>
        </div>

        <!-- Import Row Errors Log -->
        <?php if (!empty($importErrors)): ?>
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm p-4 border-start border-danger border-4">
                    <h5 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-octagon me-2"></i>Row Validation Failures</h5>
                    <div class="small text-muted mb-3">
                        The following rows could not be imported due to data validation errors (e.g. duplicate registration numbers):
                    </div>
                    <div class="bg-black bg-opacity-25 p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                        <ul class="text-danger mb-0 ps-3">
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
