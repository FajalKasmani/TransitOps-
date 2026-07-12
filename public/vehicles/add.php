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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "CSRF security verification failed. Request blocked.";
    } else {
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
            Vehicle::create($data);
            header("Location: list.php?created=1");
            exit;
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Register Vehicle</h3>
            <p class="text-muted small mb-0">Add a new transport asset to the fleet.</p>
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
        <form method="POST" action="add.php" class="needs-validation" novalidate>
            <?php echo Auth::getCsrfInput(); ?>
            <div class="row g-3">
                <!-- Registration Number -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="registration_number" name="registration_number" required placeholder="e.g. TX-984-L" style="border-radius: 10px;">
                        <label for="registration_number" class="text-muted">Registration Number *</label>
                    </div>
                </div>

                <!-- Vehicle Name -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" required placeholder="e.g. Volvo FH16" style="border-radius: 10px;">
                        <label for="vehicle_name" class="text-muted">Vehicle Name / Model *</label>
                    </div>
                </div>

                <!-- Vehicle Type -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="type" name="type" required style="border-radius: 10px;">
                            <option value="">Select type...</option>
                            <option value="car">Car</option>
                            <option value="van">Van</option>
                            <option value="truck">Truck</option>
                            <option value="motorcycle">Motorcycle</option>
                        </select>
                        <label for="type" class="text-muted">Vehicle Type *</label>
                    </div>
                </div>

                <!-- Max Load Capacity -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="max_load_capacity" name="max_load_capacity" required placeholder="e.g. 15000" style="border-radius: 10px;">
                        <label for="max_load_capacity" class="text-muted">Max Capacity (kg) *</label>
                    </div>
                </div>

                <!-- Odometer -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="odometer" name="odometer" value="0.00" placeholder="e.g. 120" style="border-radius: 10px;">
                        <label for="odometer" class="text-muted">Odometer (km)</label>
                    </div>
                </div>

                <!-- Acquisition Cost -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="acquisition_cost" name="acquisition_cost" value="0.00" placeholder="e.g. 45000" style="border-radius: 10px;">
                        <label for="acquisition_cost" class="text-muted">Acquisition Cost (₹)</label>
                    </div>
                </div>

                <!-- Initial Status -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="status" name="status" required style="border-radius: 10px;">
                            <option value="available" selected>Available</option>
                            <option value="on_trip">On Trip</option>
                            <option value="in_shop">In Shop</option>
                            <option value="retired">Retired</option>
                        </select>
                        <label for="status" class="text-muted">Initial Status *</label>
                    </div>
                </div>

                <!-- Operating Region -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="region" name="region" placeholder="e.g. West" style="border-radius: 10px;">
                        <label for="region" class="text-muted">Operating Region</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <button type="reset" class="btn btn-outline-secondary" style="border-radius: 10px;">Reset</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
