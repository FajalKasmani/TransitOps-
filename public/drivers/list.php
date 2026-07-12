<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Driver;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'safety_officer'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';

// Handle delete action (restricted to admin & fleet_manager)
$error = null;
$successMsg = null;
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!in_array($role, ['admin', 'fleet_manager'], true)) {
        $error = "Unauthorized action. Only Admins and Fleet Managers can remove drivers.";
    } else {
        $id = (int)$_GET['id'];
        if (Driver::delete($id)) {
            $successMsg = "Driver profile deleted successfully.";
        } else {
            $error = "Failed to delete driver profile.";
        }
    }
}

$drivers = Driver::getAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Drivers Registry</h3>
            <p class="text-muted small mb-0">Manage personnel, safety scores, and compliance.</p>
        </div>
        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
            <div class="d-flex gap-2">
                <a href="import.php" class="btn btn-outline-success d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Bulk Import CSV
                </a>
                <a href="add.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-plus-lg"></i> Add Driver
                </a>
            </div>
        <?php endif; ?>
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
            <table class="table dashboard-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>License Number</th>
                        <th>Category</th>
                        <th>License Expiry</th>
                        <th>Contact Number</th>
                        <th>Safety Score</th>
                        <th>Status</th>
                        <th>Email</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($drivers)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No drivers registered.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($drivers as $d): 
                            $isExpired = strtotime($d['license_expiry_date']) < time();
                        ?>
                            <tr>
                                <td><strong>#<?php echo $d['id']; ?></strong></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars($d['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="<?php echo $isExpired ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo htmlspecialchars($d['license_expiry_date'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger small ms-1">EXPIRED</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $d['contact_number'] ? htmlspecialchars($d['contact_number'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td>
                                    <div class="fw-bold <?php echo (float)$d['safety_score'] >= 4.0 ? 'text-success' : ((float)$d['safety_score'] >= 3.0 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo number_format((float)$d['safety_score'], 2); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match($d['status']) {
                                            'available' => 'bg-success',
                                            'on_trip' => 'bg-primary',
                                            'off_duty' => 'bg-secondary',
                                            'suspended' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $d['status']; ?></span>
                                </td>
                                <td><?php echo $d['email'] ? htmlspecialchars($d['email'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="edit.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi <?php echo $role === 'safety_officer' ? 'bi-shield-shaded' : 'bi-pencil'; ?>"></i>
                                        </a>
                                        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
                                            <a href="list.php?action=delete&id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this driver?');"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>
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
