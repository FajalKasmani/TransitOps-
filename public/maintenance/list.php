<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Maintenance.php';

use Api\Classes\Auth;
use Api\Classes\Maintenance;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'safety_officer'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';

// Fetch logs
$logs = Maintenance::getAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Maintenance Logs</h3>
            <p class="text-muted small mb-0">Record vehicle repairs, inspections, and downtime records.</p>
        </div>
        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
            <a href="add.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-tools"></i> Log Maintenance
            </a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Log ID</th>
                        <th>Vehicle</th>
                        <th>Description</th>
                        <th>Cost</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
                            <th class="text-end">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No maintenance logs recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><strong>#<?php echo $log['id']; ?></strong></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($log['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <span class="text-muted small"><?php echo htmlspecialchars($log['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="fw-semibold text-danger">₹<?php echo number_format((float)$log['cost'], 2); ?></td>
                                <td><?php echo htmlspecialchars($log['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php 
                                        $badgeClass = $log['status'] === 'open' ? 'bg-warning text-dark' : 'bg-success';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $log['status']; ?></span>
                                </td>
                                <td class="small text-muted" style="max-width: 200px;"><?php echo $log['notes'] ? htmlspecialchars($log['notes'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
                                    <td class="text-end">
                                        <a href="edit.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    </td>
                                <?php endif; ?>
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
