<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;
use Api\Classes\Driver;

Auth::startSession();

// Restrict access strictly to the System Administrator
if (!Auth::checkAccess(['admin'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;
$success = null;

// Handle Restore Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $type = $_POST['type'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    try {
        if (!Auth::verifyCsrfToken($csrfToken)) {
            throw new Exception("CSRF validation failed. Action aborted.");
        }

        if ($action === 'restore') {
            if ($type === 'vehicle') {
                if (Vehicle::restore($id)) {
                    $success = "Vehicle restored successfully.";
                } else {
                    throw new Exception("Failed to restore vehicle.");
                }
            } elseif ($type === 'driver') {
                if (Driver::restore($id)) {
                    $success = "Driver profile restored successfully.";
                } else {
                    throw new Exception("Failed to restore driver profile.");
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch soft-deleted lists
$deletedVehicles = Vehicle::getDeleted();
$deletedDrivers = Driver::getDeleted();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme"><i class="bi bi-trash3 me-2 text-danger"></i>Trash Bin & Data Recovery</h3>
            <p class="text-muted small mb-0">System Administrators can recover soft-deleted fleet vehicles and driver accounts.</p>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs Layout -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <ul class="nav nav-tabs card-header-tabs" id="trashTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button" role="tab" aria-controls="vehicles" aria-selected="true">
                        <i class="bi bi-truck me-2"></i>Deleted Vehicles (<?php echo count($deletedVehicles); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="drivers-tab" data-bs-toggle="tab" data-bs-target="#drivers" type="button" role="tab" aria-controls="drivers" aria-selected="false">
                        <i class="bi bi-person-badge me-2"></i>Deleted Drivers (<?php echo count($deletedDrivers); ?>)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="trashTabsContent">
                <!-- Vehicles Tab -->
                <div class="tab-pane fade show active p-3" id="vehicles" role="tabpanel" aria-labelledby="vehicles-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Vehicle Name</th>
                                    <th>Registration Number</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Odometer</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deletedVehicles)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No deleted vehicles found in trash.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deletedVehicles as $v): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td><code><?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($v['type']); ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo ucfirst(str_replace('_', ' ', $v['status'])); ?></span></td>
                                            <td><?php echo number_format((float)$v['odometer'], 2); ?> km</td>
                                            <td class="text-end">
                                                <form method="POST" action="trash.php" class="d-inline">
                                                    <?php echo Auth::getCsrfInput(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                                    <input type="hidden" name="type" value="vehicle">
                                                    <input type="hidden" name="action" value="restore">
                                                    <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1 float-end ms-2">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
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

                <!-- Drivers Tab -->
                <div class="tab-pane fade p-3" id="drivers" role="tabpanel" aria-labelledby="drivers-tab">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Driver Name</th>
                                    <th>License Number</th>
                                    <th>Category</th>
                                    <th>Safety Score</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deletedDrivers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No deleted drivers found in trash.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deletedDrivers as $d): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td><code><?php echo htmlspecialchars($d['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td><?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><strong class="text-success"><?php echo number_format((float)$d['safety_score'], 2); ?></strong></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $d['status'])); ?></span></td>
                                            <td class="text-end">
                                                <form method="POST" action="trash.php" class="d-inline">
                                                    <?php echo Auth::getCsrfInput(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                                    <input type="hidden" name="type" value="driver">
                                                    <input type="hidden" name="action" value="restore">
                                                    <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1 float-end ms-2">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
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
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
