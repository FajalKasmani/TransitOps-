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

<style>
    :root {
        --bg-dark-canvas: #0f172a; /* Slate 900 */
        --bg-dark-card: #1e293b;   /* Slate 800 */
        --border-dark: #334155;     /* Slate 700 */
        --text-primary: #f8fafc;    /* Slate 50 */
        --text-secondary: #94a3b8;  /* Slate 400 */
        --table-row-hover: #243249;
    }

    .dark-theme-wrapper {
        color: var(--text-primary);
    }
    
    .ui-card-dark {
        background-color: var(--bg-dark-card) !important;
        border: 1px solid var(--border-dark) !important;
        border-radius: 16px !important;
        overflow: hidden;
    }

    .ui-card-dark .card-header {
        background-color: #111827 !important; /* Deep contrast header split */
        border-bottom: 1px solid var(--border-dark) !important;
    }

    /* Tabs Dark Configuration Overrides */
    .ui-card-dark .nav-tabs {
        border-bottom: none;
    }

    .ui-card-dark .nav-tabs .nav-link {
        color: var(--text-secondary) !important;
        border: none !important;
        border-radius: 8px;
        padding: 10px 20px;
        transition: all 0.2s ease;
        margin-right: 4px;
    }

    .ui-card-dark .nav-tabs .nav-link:hover {
        background-color: #1e293b;
        color: var(--text-primary) !important;
    }

    .ui-card-dark .nav-tabs .nav-link.active {
        background-color: var(--bg-dark-card) !important;
        color: #38bdf8 !important; /* Sky 400 Accent Link */
        font-weight: 600;
        box-shadow: 0 -2px 10px rgba(56, 189, 248, 0.15);
    }

    /* Dark Mode Structured Tables */
    .ui-card-dark .table {
        color: var(--text-primary) !important;
        margin-bottom: 0;
    }

    .ui-card-dark .table th {
        background-color: #111827;
        color: var(--text-secondary);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 2px solid var(--border-dark);
        padding: 14px 16px;
    }

    .ui-card-dark .table td {
        border-bottom: 1px solid var(--border-dark);
        padding: 14px 16px;
        background-color: transparent !important;
    }

    .ui-card-dark .table-hover tbody tr:hover td {
        background-color: var(--table-row-hover) !important;
        color: var(--text-primary) !important;
    }

    .btn-restore-dark {
        background-color: #065f46; /* Emerald Deep Dark Accent */
        border: 1px solid #10b981;
        color: #ecfdf5 !important;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-restore-dark:hover {
        background-color: #047857;
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.35);
    }

    .code-accent-dark {
        background-color: #0f172a !important;
        border: 1px solid var(--border-dark);
        color: #38bdf8 !important;
        padding: 3px 8px;
        border-radius: 6px;
    }
</style>

<div class="container-fluid py-4 dark-theme-wrapper">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 pb-3 border-bottom" style="border-color: var(--border-dark) !important;">
        <div>
            <h3 class="fw-bold tracking-tight text-white mb-1"><i class="bi bi-trash3-fill me-2 text-danger"></i>Trash Bin & Data Recovery</h3>
            <p class="small mb-0" style="color: var(--text-secondary);">Secured operational zone. Review and restore soft-deleted fleet vehicles and driver resource configurations.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger bg-danger-subtle text-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Recovery Operation Aborted</strong>
                <span class="small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success bg-success-subtle text-success border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Success</strong>
                <span class="small"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card ui-card-dark border-0 shadow-lg">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="trashTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button" role="tab" aria-controls="vehicles" aria-selected="true">
                        <i class="bi bi-truck me-2"></i>Deleted Vehicles <span class="badge bg-dark-subtle border border-secondary text-secondary ms-1 small"><?php echo count($deletedVehicles); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="drivers-tab" data-bs-toggle="tab" data-bs-target="#drivers" type="button" role="tab" aria-controls="drivers" aria-selected="false">
                        <i class="bi bi-person-badge me-2"></i>Deleted Drivers <span class="badge bg-dark-subtle border border-secondary text-secondary ms-1 small"><?php echo count($deletedDrivers); ?></span>
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-0">
            <div class="tab-content" id="trashTabsContent">
                
                <div class="tab-pane fade show active" id="vehicles" role="tabpanel" aria-labelledby="vehicles-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-archive-fill d-block fs-2 mb-2 opacity-50"></i>
                                            No deleted vehicles found in the system archive.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deletedVehicles as $v): ?>
                                        <tr>
                                            <td><span class="text-white fw-semibold"><?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><code class="code-accent-dark"><?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td><span class="badge bg-secondary-subtle border border-secondary text-secondary-emphasis px-2.5 py-1.5"><?php echo ucfirst($v['type']); ?></span></td>
                                            <td><span class="badge bg-warning-subtle border border-warning text-warning-emphasis px-2.5 py-1.5"><?php echo ucfirst(str_replace('_', ' ', $v['status'])); ?></span></td>
                                            <td style="color: var(--text-secondary);"><?php echo number_format((float)$v['odometer'], 2); ?> km</td>
                                            <td class="text-end">
                                                <form method="POST" action="trash.php" class="d-inline">
                                                    <?php echo Auth::getCsrfInput(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                                    <input type="hidden" name="type" value="vehicle">
                                                    <input type="hidden" name="action" value="restore">
                                                    <button type="submit" class="btn btn-sm btn-restore-dark px-3 py-1.5 d-inline-flex align-items-center gap-1.5">
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

                <div class="tab-pane fade" id="drivers" role="tabpanel" aria-labelledby="drivers-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-archive-fill d-block fs-2 mb-2 opacity-50"></i>
                                            No deleted drivers found in the system archive.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deletedDrivers as $d): ?>
                                        <tr>
                                            <td><span class="text-white fw-semibold"><?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            <td><code class="code-accent-dark"><?php echo htmlspecialchars($d['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><span class="fw-bold text-info"><i class="bi bi-shield-check me-1"></i><?php echo number_format((float)$d['safety_score'], 2); ?></span></td>
                                            <td><span class="badge bg-secondary-subtle border border-secondary text-secondary-emphasis px-2.5 py-1.5"><?php echo ucfirst(str_replace('_', ' ', $d['status'])); ?></span></td>
                                            <td class="text-end">
                                                <form method="POST" action="trash.php" class="d-inline">
                                                    <?php echo Auth::getCsrfInput(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                                    <input type="hidden" name="type" value="driver">
                                                    <input type="hidden" name="action" value="restore">
                                                    <button type="submit" class="btn btn-sm btn-restore-dark px-3 py-1.5 d-inline-flex align-items-center gap-1.5">
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