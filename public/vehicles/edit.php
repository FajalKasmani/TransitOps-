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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle = Vehicle::getById($id);

if (!$vehicle) {
    header("Location: list.php?error=not_found");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'registration_number' => trim($_POST['registration_number'] ?? ''),
        'vehicle_name' => trim($_POST['vehicle_name'] ?? ''),
        'type' => $_POST['type'] ?? '',
        'max_load_capacity' => $_POST['max_load_capacity'] !== '' ? (float)$_POST['max_load_capacity'] : 0.00,
        'odometer' => $_POST['odometer'] !== '' ? (float)$_POST['odometer'] : 0.00,
        'acquisition_cost' => $_POST['acquisition_cost'] !== '' ? (float)$_POST['acquisition_cost'] : 0.00,
        'status' => $_POST['status'] ?? 'available',
        'region' => trim($_POST['region'] ?? '')
    ];

    try {
        Vehicle::update($id, $data);
        header("Location: list.php?updated=1");
        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
        // Repopulate fields for presentation
        $vehicle = array_merge($vehicle, $data);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Edit Vehicle Specifications</h3>
            <p class="text-muted small mb-0">Update configurations for vehicle #<?php echo $id; ?>.</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Registry
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4 p-md-5">
        <form method="POST" action="edit.php?id=<?php echo $id; ?>" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- Registration Number -->
                <div class="col-12 col-md-6">
                    <label for="registration_number" class="form-label fw-semibold text-muted small">Registration Number *</label>
                    <input type="text" class="form-control" id="registration_number" name="registration_number" required value="<?php echo htmlspecialchars($vehicle['registration_number'], ENT_QUOTES, 'UTF-8'); ?>" style="border-radius: 10px;">
                </div>

                <!-- Vehicle Name -->
                <div class="col-12 col-md-6">
                    <label for="vehicle_name" class="form-label fw-semibold text-muted small">Vehicle Name / Model *</label>
                    <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" required value="<?php echo htmlspecialchars($vehicle['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?>" style="border-radius: 10px;">
                </div>

                <!-- Vehicle Type -->
                <div class="col-12 col-md-4">
                    <label for="type" class="form-label fw-semibold text-muted small">Vehicle Type *</label>
                    <select class="form-select" id="type" name="type" required style="border-radius: 10px;">
                        <option value="car" <?php echo $vehicle['type'] === 'car' ? 'selected' : ''; ?>>Car</option>
                        <option value="van" <?php echo $vehicle['type'] === 'van' ? 'selected' : ''; ?>>Van</option>
                        <option value="truck" <?php echo $vehicle['type'] === 'truck' ? 'selected' : ''; ?>>Truck</option>
                        <option value="motorcycle" <?php echo $vehicle['type'] === 'motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
                    </select>
                </div>

                <!-- Max Load Capacity -->
                <div class="col-12 col-md-4">
                    <label for="max_load_capacity" class="form-label fw-semibold text-muted small">Max Capacity (kg) *</label>
                    <input type="number" step="0.01" class="form-control" id="max_load_capacity" name="max_load_capacity" required value="<?php echo number_format((float)$vehicle['max_load_capacity'], 2, '.', ''); ?>" style="border-radius: 10px;">
                </div>

                <!-- Odometer -->
                <div class="col-12 col-md-4">
                    <label Budge for="odometer" class="form-label fw-semibold text-muted small">Odometer (km)</label>
                    <input type="number" step="0.01" class="form-control" id="odometer" name="odometer" value="<?php echo number_format((float)$vehicle['odometer'], 2, '.', ''); ?>" style="border-radius: 10px;">
                </div>

                <!-- Acquisition Cost -->
                <div class="col-12 col-md-4">
                    <label for="acquisition_cost" class="form-label fw-semibold text-muted small">Acquisition Cost ($)</label>
                    <input type="number" step="0.01" class="form-control" id="acquisition_cost" name="acquisition_cost" value="<?php echo number_format((float)$vehicle['acquisition_cost'], 2, '.', ''); ?>" style="border-radius: 10px;">
                </div>

                <!-- Status -->
                <div class="col-12 col-md-4">
                    <label for="status" class="form-label fw-semibold text-muted small">Status *</label>
                    <select class="form-select" id="status" name="status" required style="border-radius: 10px;">
                        <option value="available" <?php echo $vehicle['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="on_trip" <?php echo $vehicle['status'] === 'on_trip' ? 'selected' : ''; ?>>On Trip</option>
                        <option value="in_shop" <?php echo $vehicle['status'] === 'in_shop' ? 'selected' : ''; ?>>In Shop</option>
                        <option value="retired" <?php echo $vehicle['status'] === 'retired' ? 'selected' : ''; ?>>Retired</option>
                    </select>
                </div>

                <!-- Operating Region -->
                <div class="col-12 col-md-4">
                    <label for="region" class="form-label fw-semibold text-muted small">Operating Region</label>
                    <input type="text" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($vehicle['region'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-radius: 10px;">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="list.php" class="btn btn-outline-secondary" style="border-radius: 10px;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Update Specifications</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
