<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Reports.php';

use Api\Classes\Auth;
use Api\Classes\Reports;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'financial_analyst'])) {
    header("Location: ../index.php");
    exit;
}

$roiData = Reports::getVehicleAnalytics();
$efficiencyData = Reports::getFuelEfficiencyReport();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght=300;400;500;600;700&display=swap');

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

    .reports-page {
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
    .table-shell {
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
    }

    .dashboard-subtitle {
        margin: 0.35rem 0 0;
        color: var(--app-muted);
        font-size: 0.92rem;
    }

    .section-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--app-text);
        display: flex;
        align-items: center;
        margin: 0;
    }

    .table-shell-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--app-border);
        background: rgba(15, 23, 42, 0.2);
    }

    .btn {
        border-radius: 14px;
        font-weight: 600;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
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

    .table-shell {
        overflow: hidden;
        margin-bottom: 2rem;
        background: var(--app-card);
    }

    .table-responsive {
        max-height: calc(100vh - 270px);
    }

    .reports-table {
        margin-bottom: 0;
        color: var(--app-text);
        border-color: var(--app-border);
    }

    .reports-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--app-border);
        background: rgba(15, 23, 42, 0.96);
        color: #cbd5e1;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .reports-table tbody tr {
        border-color: var(--app-border);
        background: rgba(30, 41, 59, 0.54);
        transition: background-color 0.2s ease;
    }

    .reports-table tbody tr:nth-child(even) {
        background: rgba(15, 23, 42, 0.34);
    }

    .reports-table tbody tr:hover {
        background: rgba(51, 65, 85, 0.82);
    }

    .reports-table td {
        padding: 1rem 1.5rem;
        border-color: var(--app-border);
        color: #e2e8f0;
        font-size: 0.9rem;
        vertical-align: middle;
        white-space: nowrap;
    }

    .reports-table td code {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.5rem;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 999px;
        color: var(--app-muted);
        background: rgba(148, 163, 184, 0.08);
        font-size: 0.78rem;
        font-weight: 500;
    }

    .text-success-theme { color: #22c55e !important; }
    .text-danger-theme { color: #ef4444 !important; }
    .text-info-theme { color: #0ea5e9 !important; }

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
        .dashboard-header { padding: 1.1rem; }
        .table-responsive { max-height: none; }
    }
</style>

<div class="container-fluid py-3 py-lg-4 reports-page">
    
    <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mb-4">
        <div>
            <div class="page-kicker">
                <i class="bi bi-bar-chart-line"></i> Financial Analytics
            </div>
            <h3 class="dashboard-title">Fleet Analytics & Reports</h3>
            <p class="dashboard-subtitle">Evaluate vehicle efficiency, financial performance, and return on investment.</p>
        </div>
    </div>

    <div class="table-shell">
        <div class="table-shell-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <h5 class="section-card-title">
                <i class="bi bi-graph-up-arrow me-2 text-primary"></i> Vehicle ROI Calculations
            </h5>
            <a href="export.php?type=roi" class="btn btn-sm btn-outline-success d-flex align-items-center justify-content-center gap-2 px-3 py-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle reports-table">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Acquisition Cost</th>
                        <th>Fuel Cost</th>
                        <th>Other Expenses</th>
                        <th>Maintenance Cost</th>
                        <th>Total Cost</th>
                        <th class="text-success-theme">Calculated Revenue</th>
                        <th class="text-end">Vehicle ROI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roiData)): ?>
                        <tr>
                            <td colspan="8" class="text-center empty-state">
                                <i class="bi bi-inboxes"></i> No vehicle records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roiData as $r): 
                            $roiVal = (float)$r['roi'];
                            $roiColor = $roiVal > 0 ? 'text-success-theme' : ($roiVal < 0 ? 'text-danger-theme' : 'text-muted');
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-light"><?php echo htmlspecialchars($r['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <code><?php echo htmlspecialchars($r['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>₹<?php echo number_format((float)$r['acquisition_cost'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$r['fuel_cost'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$r['expense_cost'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$r['maintenance_cost'], 2); ?></td>
                                <td class="fw-semibold text-danger-theme">₹<?php echo number_format((float)$r['total_cost'], 2); ?></td>
                                <td class="fw-semibold text-success-theme">₹<?php echo number_format((float)$r['calculated_revenue'], 2); ?></td>
                                <td class="text-end fw-bold <?php echo $roiColor; ?>">
                                    <?php echo number_format($roiVal, 2); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-shell">
        <div class="table-shell-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <h5 class="section-card-title">
                <i class="bi bi-droplet me-2 text-info-theme"></i> Fuel Efficiency Report
            </h5>
            <a href="export.php?type=efficiency" class="btn btn-sm btn-outline-success d-flex align-items-center justify-content-center gap-2 px-3 py-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle reports-table">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Total Completed Distance</th>
                        <th>Total Refueled</th>
                        <th class="text-end">Average Fuel Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($efficiencyData)): ?>
                        <tr>
                            <td colspan="4" class="text-center empty-state">
                                <i class="bi bi-inboxes"></i> No efficiency records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($efficiencyData as $eff): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-light"><?php echo htmlspecialchars($eff['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <code><?php echo htmlspecialchars($eff['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td><?php echo number_format((float)$eff['total_distance'], 2); ?> km</td>
                                <td><?php echo number_format((float)$eff['total_liters'], 2); ?> L</td>
                                <td class="text-end fw-bold text-info-theme">
                                    <?php echo number_format((float)$eff['efficiency'], 2); ?> km/L
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