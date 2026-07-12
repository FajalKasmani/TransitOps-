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

// Handle delete action (soft-delete)
$error = null;
$successMsg = null;
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (Vehicle::delete($id)) {
        $successMsg = "Vehicle deleted successfully.";
    } else {
        $error = "Failed to delete vehicle.";
    }
}

$vehicles = Vehicle::getAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Vehicles Registry</h3>
            <p class="text-muted small mb-0">Manage and audit fleet vehicles pool.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="import.php" class="btn btn-outline-success d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Bulk Import CSV
            </a>
            <a href="add.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Vehicle
            </a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Reg Number</th>
                        <th>Vehicle Name</th>
                        <th>Type</th>
                        <th>Max Capacity (kg)</th>
                        <th>Odometer (km)</th>
                        <th>Acquisition Cost</th>
                        <th>Status</th>
                        <th>Region</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehicles)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No vehicles registered.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td><strong>#<?php echo $v['id']; ?></strong></td>
                                <td><code><?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="text-transform: capitalize;"><?php echo htmlspecialchars($v['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo number_format((float)$v['max_load_capacity'], 2); ?></td>
                                <td><?php echo number_format((float)$v['odometer'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$v['acquisition_cost'], 2); ?></td>
                                <td>
                                    <?php 
                                        $badgeClass = match($v['status']) {
                                            'available' => 'bg-success',
                                            'on_trip' => 'bg-primary',
                                            'in_shop' => 'bg-warning text-dark',
                                            'retired' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $v['status']; ?></span>
                                </td>
                                <td><?php echo $v['region'] ? htmlspecialchars($v['region'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="documents.php?vehicle_id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-info" title="Documents"><i class="bi bi-file-earmark-text"></i> Documents</a>
                                        <a href="edit.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <a href="list.php?action=delete&id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this vehicle?');"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
