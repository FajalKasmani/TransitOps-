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

<style>
    :root {
        --bg-dark-canvas: #0f172a; /* Slate 900 */
        --bg-dark-card: #1e293b;   /* Slate 800 */
        --border-dark: #334155;     /* Slate 700 */
        --text-primary: #f8fafc;    /* Slate 50 */
        --text-secondary: #94a3b8;  /* Slate 400 */
        
        --accent-gradient: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
        --success-glow: rgba(34, 197, 94, 0.15);
    }

    .dark-registry-wrapper {
        color: var(--text-primary);
    }
    
    .ui-card-dark {
        background-color: var(--bg-dark-card) !important;
        border: 1px solid var(--border-dark) !important;
        border-radius: 16px !important;
        overflow: hidden;
    }

    /* Table styling for ultra dark panels */
    .custom-table-dark {
        background-color: transparent !important;
        color: var(--text-primary) !important;
    }

    .custom-table-dark thead th {
        background-color: #111827 !important; /* Deepest black header tint */
        color: #38bdf8 !important; /* Light sky highlight */
        font-weight: 600;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--border-dark) !important;
        padding: 16px 20px;
    }

    .custom-table-dark tbody tr {
        border-bottom: 1px solid rgba(51, 65, 85, 0.6) !important;
        transition: background-color 0.2s ease;
    }

    .custom-table-dark tbody tr:hover {
        background-color: rgba(51, 65, 85, 0.4) !important;
    }

    .custom-table-dark td {
        padding: 16px 20px !important;
        border: none;
        color: var(--text-primary);
    }

    .custom-table-dark td .text-secondary-dim {
        color: var(--text-secondary);
    }

    /* Action button variations */
    .action-btn-dark {
        border-radius: 8px;
        padding: 6px 10px;
        background-color: #111827;
        border: 1px solid var(--border-dark);
        color: var(--text-secondary);
        transition: all 0.2s ease;
    }

    .action-btn-dark.btn-edit:hover {
        border-color: #3b82f6;
        color: #60a5fa;
        background-color: rgba(59, 130, 246, 0.1);
    }

    .action-btn-dark.btn-delete:hover {
        border-color: #ef4444;
        color: #f87171;
        background-color: rgba(239, 68, 68, 0.1);
    }

    /* Modern Action Buttons */
    .custom-btn-gradient {
        background: var(--accent-gradient);
        border: none;
        color: #fff;
        font-weight: 500;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .custom-btn-gradient:hover {
        opacity: 0.95;
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
        color: #fff;
    }

    .custom-btn-outline-success {
        border: 1px solid #22c55e;
        color: #4ade80;
        background: rgba(34, 197, 94, 0.05);
        border-radius: 10px;
        transition: all 0.2s;
    }

    .custom-btn-outline-success:hover {
        background: #22c55e;
        color: #fff;
        box-shadow: 0 4px 12px var(--success-glow);
    }
</style>

<div class="container-fluid py-4 dark-registry-wrapper">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom border-secondary-subtle" style="border-color: var(--border-dark) !important;">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="bi bi-people me-2 text-info"></i>Drivers Registry</h3>
            <p class="small mb-0" style="color: var(--text-secondary);">Manage active personnel logs, dynamic performance data, and global fleet compliance metrics.</p>
        </div>
        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
            <div class="d-flex gap-2">
                <a href="import.php" class="btn custom-btn-outline-success btn-sm px-3 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Bulk Import CSV
                </a>
                <a href="add.php" class="btn custom-btn-gradient btn-sm px-3 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-plus-lg"></i> Add Driver
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success bg-success-subtle text-success border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-3"></i>
            <div>
                <span class="small fw-medium"><?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger bg-danger-subtle text-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-3"></i>
            <div>
                <span class="small fw-medium"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card ui-card-dark shadow-lg">
        <div class="table-responsive">
            <table class="table custom-table-dark table-hover align-middle mb-0">
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
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x fs-2 mb-2 d-block"></i>
                                No operators or drivers currently found in registry.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($drivers as $d): 
                            $isExpired = strtotime($d['license_expiry_date']) < time();
                        ?>
                            <tr>
                                <td><span class="text-secondary-dim font-monospace">#<?php echo $d['id']; ?></span></td>
                                <td class="fw-semibold text-white"><?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code class="text-info bg-dark-subtle px-2 py-1 rounded small border border-secondary-subtle" style="background-color: #111827 !important; border-color: var(--border-dark) !important;"><?php echo htmlspecialchars($d['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><span class="badge border border-secondary bg-dark text-secondary-dim text-uppercase px-2 py-1.5"><?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td class="<?php echo $isExpired ? 'text-danger fw-bold' : ''; ?>">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="bi bi-calendar3 small text-secondary-dim"></i>
                                        <?php echo htmlspecialchars($d['license_expiry_date'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($isExpired): ?>
                                            <span class="badge bg-danger text-white small ms-1" style="font-size: 0.65rem;">EXPIRED</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><span class="text-secondary-dim"><?php echo $d['contact_number'] ? htmlspecialchars($d['contact_number'], ENT_QUOTES, 'UTF-8') : '-'; ?></span></td>
                                <td>
                                    <?php 
                                        $score = (float)$d['safety_score'];
                                        $scoreClass = $score >= 4.0 ? 'text-success bg-success-subtle border border-success' : ($score >= 3.0 ? 'text-warning bg-warning-subtle border border-warning' : 'text-danger bg-danger-subtle border border-danger');
                                    ?>
                                    <span class="px-2 py-1 rounded fw-bold text-nowrap <?php echo $scoreClass; ?>" style="font-size: 0.85rem;">
                                        ★ <?php echo number_format($score, 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match($d['status']) {
                                            'available' => 'bg-success-subtle text-success border border-success',
                                            'on_trip' => 'bg-primary-subtle text-primary border border-primary',
                                            'off_duty' => 'bg-secondary-subtle text-secondary border border-secondary',
                                            'suspended' => 'bg-danger-subtle text-danger border border-danger',
                                            default => 'bg-secondary-subtle text-secondary border border-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase px-2 py-1.5" style="font-size: 0.75rem; letter-spacing: 0.02em;">
                                        <?php echo $d['status']; ?>
                                    </span>
                                </td>
                                <td><span class="text-secondary-dim small"><?php echo $d['email'] ? htmlspecialchars($d['email'], ENT_QUOTES, 'UTF-8') : '-'; ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="edit.php?id=<?php echo $d['id']; ?>" class="action-btn-dark btn-edit" title="Modify Record">
                                            <i class="bi <?php echo $role === 'safety_officer' ? 'bi-shield-shaded' : 'bi-pencil'; ?>"></i>
                                        </a>
                                        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
                                            <a href="list.php?action=delete&id=<?php echo $d['id']; ?>" class="action-btn-dark btn-delete" title="Delete Operator" onclick="return confirm('Are you sure you want to delete this driver?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
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