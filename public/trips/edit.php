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
use Api\Classes\Database;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'driver'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$trip = Trip::getById($id);

if (!$trip) {
    header("Location: list.php?error=not_found");
    exit;
}

$pdo = Database::getInstance();
$driverId = 0;

if ($role === 'driver') {
    // Find driver id to verify ownership
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE email = :email AND is_deleted = 0 LIMIT 1");
    $stmt->execute(['email' => $email]);
    $driverId = (int)$stmt->fetchColumn();

    if ($driverId !== (int)$trip['driver_id']) {
        header("Location: list.php?error=unauthorized");
        exit;
    }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "CSRF security verification failed. Request blocked.";
    } else {
        if ($role === 'driver') {
            // Drivers can only update status and actual_distance
            $status = $_POST['status'] ?? $trip['status'];
            $actualDistance = ($_POST['actual_distance'] !== '') ? (float)$_POST['actual_distance'] : null;

            // Verify status transitions for driver
            if (!in_array($status, ['dispatched', 'completed', 'cancelled'], true)) {
                $error = "Drivers can only set dispatched, completed, or cancelled statuses.";
            } else {
                $data = $trip;
                $data['status'] = $status;
                $data['actual_distance'] = $actualDistance;

                try {
                    Trip::update($id, $data);
                    header("Location: list.php?updated=1");
                    exit;
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        } else {
            // Admins and Fleet Managers can modify all fields
            $data = [
                'vehicle_id' => $_POST['vehicle_id'] !== '' ? (int)$_POST['vehicle_id'] : null,
                'driver_id' => $_POST['driver_id'] !== '' ? (int)$_POST['driver_id'] : null,
                'source' => trim($_POST['source'] ?? ''),
                'destination' => trim($_POST['destination'] ?? ''),
                'cargo_weight' => $_POST['cargo_weight'] !== '' ? (float)$_POST['cargo_weight'] : 0.00,
                'planned_distance' => $_POST['planned_distance'] !== '' ? (float)$_POST['planned_distance'] : 0.00,
                'actual_distance' => ($_POST['actual_distance'] !== '') ? (float)$_POST['actual_distance'] : null,
                'status' => $_POST['status'] ?? 'draft'
            ];

            try {
                Trip::update($id, $data);
                header("Location: list.php?updated=1");
                exit;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $trip = array_merge($trip, $data);
            }
        }
    }
}

// Prepare vehicle map (ensuring currently assigned is visible even if not 'available' status)
$vehiclesMap = [];
$currVeh = Vehicle::getById((int)$trip['vehicle_id']);
if ($currVeh) {
    $vehiclesMap[$currVeh['id']] = $currVeh;
}
foreach (Vehicle::getAvailable() as $v) {
    $vehiclesMap[$v['id']] = $v;
}

// Prepare driver map (ensuring currently assigned is visible)
$driversMap = [];
$currDrv = Driver::getById((int)$trip['driver_id']);
if ($currDrv) {
    $driversMap[$currDrv['id']] = $currDrv;
}
foreach (Driver::getAvailable() as $d) {
    $driversMap[$d['id']] = $d;
}

$isDriver = ($role === 'driver');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="list.php" class="text-decoration-none">Trips</a></li>
            <li class="breadcrumb-item active text-light-theme" aria-current="page">Edit Trip #<?php echo $id; ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Edit Trip Specifications</h3>
            <p class="text-muted small mb-0">Update route plan and dispatch settings for trip #<?php echo $id; ?>.</p>
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
        <form method="POST" action="edit.php?id=<?php echo $id; ?>" class="needs-validation" novalidate>
            <?php echo Auth::getCsrfInput(); ?>
            <div class="row g-3">
                <!-- Vehicle Assignment -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="vehicle_id" name="vehicle_id" required <?php echo $isDriver ? 'disabled' : ''; ?> style="border-radius: 10px;">
                            <?php foreach ($vehiclesMap as $v): ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo (int)$trip['vehicle_id'] === (int)$v['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?>) - Max Load: <?php echo $v['max_load_capacity']; ?>kg [Status: <?php echo $v['status']; ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="vehicle_id" class="text-muted">Vehicle Assignment *</label>
                    </div>
                </div>

                <!-- Driver Assignment -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="driver_id" name="driver_id" required <?php echo $isDriver ? 'disabled' : ''; ?> style="border-radius: 10px;">
                            <?php foreach ($driversMap as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo (int)$trip['driver_id'] === (int)$d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?> (Licence: <?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?>) - Safety Score: <?php echo $d['safety_score']; ?> [Status: <?php echo $d['status']; ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="driver_id" class="text-muted">Driver Assignment *</label>
                    </div>
                </div>

                <!-- Source Location -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="source" name="source" required value="<?php echo htmlspecialchars($trip['source'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isDriver ? 'disabled' : ''; ?> placeholder="Source Location">
                        <label for="source" class="text-muted">Source Location *</label>
                    </div>
                </div>

                <!-- Destination Location -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="destination" name="destination" required value="<?php echo htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isDriver ? 'disabled' : ''; ?> placeholder="Destination Location">
                        <label for="destination" class="text-muted">Destination Location *</label>
                    </div>
                </div>

                <!-- Cargo Weight -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="cargo_weight" name="cargo_weight" required value="<?php echo number_format((float)$trip['cargo_weight'], 2, '.', ''); ?>" <?php echo $isDriver ? 'disabled' : ''; ?> placeholder="Cargo Weight (kg)">
                        <label for="cargo_weight" class="text-muted">Cargo Weight (kg) *</label>
                    </div>
                </div>

                <!-- Planned Distance -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="planned_distance" name="planned_distance" required value="<?php echo number_format((float)$trip['planned_distance'], 2, '.', ''); ?>" <?php echo $isDriver ? 'disabled' : ''; ?> placeholder="Planned Distance (km)">
                        <label for="planned_distance" class="text-muted">Planned Distance (km) *</label>
                    </div>
                </div>

                <!-- Actual Distance (Editable when dispatched/completed) -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control text-success fw-bold" id="actual_distance" name="actual_distance" value="<?php echo $trip['actual_distance'] !== null ? number_format((float)$trip['actual_distance'], 2, '.', '') : ''; ?>" placeholder="Actual Distance (km)">
                        <label for="actual_distance" class="text-muted">Actual Distance (km)</label>
                    </div>
                </div>

                <!-- Dispatch Status -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="status" name="status" required style="border-radius: 10px;">
                            <?php if (!$isDriver): ?>
                                <option value="draft" <?php echo $trip['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <?php endif; ?>
                            <option value="dispatched" <?php echo $trip['status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="completed" <?php echo $trip['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $trip['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <label for="status" class="text-muted">Dispatch Status *</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="list.php" class="btn btn-outline-secondary" style="border-radius: 10px;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Update Route Specifications</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
