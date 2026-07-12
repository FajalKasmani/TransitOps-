<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';
require_once __DIR__ . '/../../api/classes/Driver.php';
require_once __DIR__ . '/../../api/classes/Trip.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;
use Api\Classes\Driver;
use Api\Classes\Trip;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id' => $_POST['vehicle_id'] !== '' ? (int)$_POST['vehicle_id'] : null,
        'driver_id' => $_POST['driver_id'] !== '' ? (int)$_POST['driver_id'] : null,
        'source' => trim($_POST['source'] ?? ''),
        'destination' => trim($_POST['destination'] ?? ''),
        'cargo_weight' => $_POST['cargo_weight'] !== '' ? (float)$_POST['cargo_weight'] : 0.00,
        'planned_distance' => $_POST['planned_distance'] !== '' ? (float)$_POST['planned_distance'] : 0.00,
        'status' => $_POST['status'] ?? 'draft'
    ];

    try {
        Trip::create($data);
        header("Location: list.php?created=1");
        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

$availableVehicles = Vehicle::getAvailable();
$availableDrivers = Driver::getAvailable();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Dispatch New Trip</h3>
            <p class="text-muted small mb-0">Create a new routing plan and assign transport assets.</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Logs
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4 p-md-5">
        <form method="POST" action="add.php" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- Vehicle Assignment -->
                <div class="col-12 col-md-6">
                    <label for="vehicle_id" class="form-label fw-semibold text-muted small">Vehicle Assignment *</label>
                    <select class="form-select" id="vehicle_id" name="vehicle_id" required style="border-radius: 10px;">
                        <option value="">Select an available vehicle...</option>
                        <?php foreach ($availableVehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                <?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?>) - Max Load: <?php echo $v['max_load_capacity']; ?>kg
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Driver Assignment -->
                <div class="col-12 col-md-6">
                    <label for="driver_id" class="form-label fw-semibold text-muted small">Driver Assignment *</label>
                    <select class="form-select" id="driver_id" name="driver_id" required style="border-radius: 10px;">
                        <option value="">Select an available driver...</option>
                        <?php foreach ($availableDrivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>">
                                <?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?> (Licence: <?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?>) - Safety Score: <?php echo $d['safety_score']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Source Location -->
                <div class="col-12 col-md-6">
                    <label for="source" class="form-label fw-semibold text-muted small">Source Location *</label>
                    <input type="text" class="form-control" id="source" name="source" required placeholder="e.g. Dallas Fulfillment Center" style="border-radius: 10px;">
                </div>

                <!-- Destination Location -->
                <div class="col-12 col-md-6">
                    <label for="destination" class="form-label fw-semibold text-muted small">Destination Location *</label>
                    <input type="text" class="form-control" id="destination" name="destination" required placeholder="e.g. Houston Distribution Hub" style="border-radius: 10px;">
                </div>

                <!-- Cargo Weight -->
                <div class="col-12 col-md-4">
                    <label for="cargo_weight" class="form-label fw-semibold text-muted small">Cargo Weight (kg) *</label>
                    <input type="number" step="0.01" class="form-control" id="cargo_weight" name="cargo_weight" required placeholder="e.g. 8500.00" style="border-radius: 10px;">
                </div>

                <!-- Planned Distance -->
                <div class="col-12 col-md-4">
                    <label for="planned_distance" class="form-label fw-semibold text-muted small">Planned Distance (km) *</label>
                    <input type="number" step="0.01" class="form-control" id="planned_distance" name="planned_distance" required placeholder="e.g. 385.20" style="border-radius: 10px;">
                </div>

                <!-- Dispatch Status -->
                <div class="col-12 col-md-4">
                    <label for="status" class="form-label fw-semibold text-muted small">Dispatch Status *</label>
                    <select class="form-select" id="status" name="status" required style="border-radius: 10px;">
                        <option value="draft" selected>Draft (Save only)</option>
                        <option value="dispatched">Dispatched (Lock vehicle & driver)</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <button type="reset" class="btn btn-outline-secondary" style="border-radius: 10px;">Reset</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Dispatch Route</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
