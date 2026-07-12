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

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
        --app-bg: #0f172a;
        --app-card: rgba(30, 41, 59, 0.76);
        --app-secondary: #334155;
        --app-primary: #3b82f6;
        --app-success: #22c55e;
        --app-warning: #f59e0b;
        --app-danger: #ef4444;
        --app-text: #f8fafc;
        --app-muted: #94a3b8;
        --app-border: rgba(148, 163, 184, 0.18);
        --app-shadow: 0 24px 60px rgba(2, 6, 23, 0.42);
        --app-radius: 18px;
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 34rem),
            radial-gradient(circle at top right, rgba(34, 197, 94, 0.10), transparent 30rem),
            var(--app-bg);
        color: var(--app-text);
    }

    .vehicles-page {
        animation: pageFadeIn 0.45s ease both;
    }

    @keyframes pageFadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dashboard-header,
    .glass-card {
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius);
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.82));
        box-shadow: var(--app-shadow);
        backdrop-filter: blur(18px);
    }

    .dashboard-header {
        padding: 1.35rem;
    }

    .page-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.7rem;
        padding: 0.38rem 0.72rem;
        border: 1px solid rgba(59, 130, 246, 0.28);
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.12);
        color: #bfdbfe;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .dashboard-title {
        margin: 0;
        color: var(--app-text);
        font-size: clamp(1.45rem, 2vw, 2rem);
        font-weight: 700;
        letter-spacing: 0;
    }

    .dashboard-subtitle {
        margin: 0.35rem 0 0;
        color: var(--app-muted);
        font-size: 0.92rem;
    }

    .kpi-card {
        min-width: 180px;
        padding: 1rem;
        border: 1px solid var(--app-border);
        border-left: 4px solid var(--app-primary);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(51, 65, 85, 0.72), rgba(15, 23, 42, 0.82));
        box-shadow: 0 18px 44px rgba(2, 6, 23, 0.26);
        transition: transform 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
    }

    .kpi-card:hover {
        transform: translateY(-4px);
        border-color: rgba(59, 130, 246, 0.48);
        box-shadow: 0 24px 54px rgba(2, 6, 23, 0.36);
    }

    .kpi-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        color: #dbeafe;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.34), rgba(34, 197, 94, 0.16));
        box-shadow: inset 0 0 0 1px rgba(248, 250, 252, 0.08);
    }

    .kpi-label {
        color: var(--app-muted);
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .kpi-value {
        color: var(--app-text);
        font-size: 1.55rem;
        font-weight: 700;
        line-height: 1;
    }

    .btn {
        border-radius: 14px;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn-primary {
        border-color: rgba(59, 130, 246, 0.92);
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        box-shadow: 0 14px 30px rgba(59, 130, 246, 0.26);
    }

    .btn-outline-success {
        border-color: rgba(34, 197, 94, 0.42);
        color: #bbf7d0;
        background: rgba(34, 197, 94, 0.08);
    }

    .btn-outline-success:hover {
        border-color: rgba(34, 197, 94, 0.76);
        color: #f8fafc;
        background: rgba(34, 197, 94, 0.18);
        box-shadow: 0 14px 30px rgba(34, 197, 94, 0.16);
    }

    .btn-outline-primary {
        border-color: rgba(59, 130, 246, 0.42);
        color: #bfdbfe;
        background: rgba(59, 130, 246, 0.08);
    }

    .btn-outline-primary:hover {
        border-color: rgba(59, 130, 246, 0.76);
        color: #ffffff;
        background: rgba(59, 130, 246, 0.18);
    }

    .btn-outline-info {
        border-color: rgba(14, 165, 233, 0.42);
        color: #bae6fd;
        background: rgba(14, 165, 233, 0.08);
    }

    .btn-outline-info:hover {
        border-color: rgba(14, 165, 233, 0.76);
        color: #ffffff;
        background: rgba(14, 165, 233, 0.18);
    }

    .btn-outline-danger {
        border-color: rgba(239, 68, 68, 0.42);
        color: #fecaca;
        background: rgba(239, 68, 68, 0.08);
    }

    .btn-outline-danger:hover {
        border-color: rgba(239, 68, 68, 0.76);
        color: #ffffff;
        background: rgba(239, 68, 68, 0.18);
    }

    .alert {
        border-radius: 16px;
        border: 1px solid var(--app-border);
        color: var(--app-text);
        background: rgba(30, 41, 59, 0.88);
        box-shadow: 0 18px 42px rgba(2, 6, 23, 0.28);
        backdrop-filter: blur(14px);
    }

    .alert-success {
        border-color: rgba(34, 197, 94, 0.34);
    }

    .alert-danger {
        border-color: rgba(239, 68, 68, 0.34);
    }

    .btn-close {
        filter: invert(1) grayscale(100%) brightness(180%);
        opacity: 0.75;
    }

    .table-shell {
        overflow: hidden;
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius);
        background: var(--app-card);
        box-shadow: var(--app-shadow);
        backdrop-filter: blur(18px);
    }

    .table-responsive {
        max-height: calc(100vh - 270px);
    }

    .vehicles-table {
        margin-bottom: 0;
        color: var(--app-text);
        border-color: var(--app-border);
    }

    .vehicles-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 1rem;
        border-bottom: 1px solid var(--app-border);
        background: rgba(15, 23, 42, 0.96);
        color: #cbd5e1;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .vehicles-table tbody tr {
        border-color: var(--app-border);
        background: rgba(30, 41, 59, 0.54);
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .vehicles-table tbody tr:nth-child(even) {
        background: rgba(15, 23, 42, 0.34);
    }

    .vehicles-table tbody tr:hover {
        background: rgba(51, 65, 85, 0.82);
    }

    .vehicles-table td {
        padding: 1rem;
        border-color: var(--app-border);
        color: #e2e8f0;
        font-size: 0.9rem;
        vertical-align: middle;
        white-space: nowrap;
    }

    .vehicles-table strong {
        color: #f8fafc;
        font-weight: 700;
    }

    .vehicles-table code {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 0.26rem 0.55rem;
        border: 1px solid rgba(59, 130, 246, 0.22);
        border-radius: 999px;
        color: #bfdbfe;
        background: rgba(59, 130, 246, 0.10);
        font-size: 0.82rem;
        font-weight: 600;
    }

    .badge {
        border-radius: 999px;
        padding: 0.48rem 0.72rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.07em;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.10);
    }

    .bg-success {
        background: rgba(34, 197, 94, 0.18) !important;
        color: #bbf7d0 !important;
    }

    .bg-primary {
        background: rgba(59, 130, 246, 0.20) !important;
        color: #bfdbfe !important;
    }

    .bg-warning {
        background: rgba(245, 158, 11, 0.20) !important;
        color: #fde68a !important;
    }

    .bg-danger {
        background: rgba(239, 68, 68, 0.20) !important;
        color: #fecaca !important;
    }

    .bg-secondary {
        background: rgba(148, 163, 184, 0.18) !important;
        color: #cbd5e1 !important;
    }

    .action-group .btn {
        min-width: 38px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.45rem 0.65rem;
    }

    .empty-state {
        padding: 3.5rem 1rem !important;
        color: var(--app-muted) !important;
        background: rgba(15, 23, 42, 0.24);
    }

    .empty-state i {
        display: block;
        margin-bottom: 0.75rem;
        color: #64748b;
        font-size: 2.2rem;
    }

    @media (max-width: 991.98px) {
        .dashboard-header {
            padding: 1.1rem;
        }

        .kpi-card {
            width: 100%;
        }

        .dashboard-actions {
            width: 100%;
        }

        .dashboard-actions .btn {
            flex: 1 1 auto;
        }

        .table-responsive {
            max-height: none;
        }
    }

    @media (max-width: 575.98px) {
        .vehicles-page {
            padding-inline: 0.25rem;
        }

        .dashboard-actions {
            flex-direction: column;
        }

        .dashboard-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid py-3 py-lg-4 vehicles-page">
    <div class="dashboard-header d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-4 mb-4">
        <div>
            <div class="page-kicker">
                <i class="bi bi-truck-front"></i>
                Fleet Operations
            </div>
            <h3 class="dashboard-title">Vehicles Registry</h3>
            <p class="dashboard-subtitle">Manage and audit fleet vehicles pool.</p>
        </div>

        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-3">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
                <div>
                    <div class="kpi-label">Registry</div>
                    <div class="kpi-value">Active</div>
                </div>
            </div>

            <div class="dashboard-actions d-flex gap-2">
                <a href="import.php" class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2 px-3 py-2">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Bulk Import CSV
                </a>
                <a href="add.php" class="btn btn-primary d-flex align-items-center justify-content-center gap-2 px-3 py-2">
                    <i class="bi bi-plus-lg"></i> Add Vehicle
                </a>
            </div>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success"></i> <?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-shell">
        <div class="table-responsive">
            <table class="table table-hover align-middle vehicles-table">
                <thead>
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
                            <td colspan="10" class="text-center empty-state">
                                <i class="bi bi-inboxes"></i>
                                No vehicles registered.
                            </td>
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
                                    <div class="d-flex justify-content-end gap-2 action-group">
                                        <a href="documents.php?vehicle_id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-info" title="Documents"><i class="bi bi-file-earmark-text"></i> Documents</a>
                                        <a href="edit.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Vehicle"><i class="bi bi-pencil"></i></a>
                                        <a href="list.php?action=delete&id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Vehicle" onclick="return confirm('Are you sure you want to delete this vehicle?');"><i class="bi bi-trash"></i></a>
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