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

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Fleet Analytics & Reports</h3>
            <p class="text-muted small mb-0">Evaluate vehicle efficiency, financial performance, and return on investment.</p>
        </div>
    </div>

    <!-- ROI Report Card -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i> Vehicle ROI Calculations</h5>
            <a href="export.php?type=roi" class="btn btn-sm btn-success d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Acquisition Cost</th>
                        <th>Fuel Cost</th>
                        <th>Other Expenses</th>
                        <th>Maintenance Cost</th>
                        <th class="fw-semibold">Total Cost</th>
                        <th class="text-success">Calculated Revenue</th>
                        <th class="text-end">Vehicle ROI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roiData)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No vehicle records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roiData as $r): 
                            $roiVal = (float)$r['roi'];
                            $roiColor = $roiVal > 0 ? 'text-success' : ($roiVal < 0 ? 'text-danger' : 'text-muted');
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($r['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <code class="small text-muted"><?php echo htmlspecialchars($r['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>₹<?php echo number_format((float)$r['acquisition_cost'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$r['fuel_cost'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$r['expense_cost'], 2); ?></td>
                                <td>₹<?php echo number_format((float)$r['maintenance_cost'], 2); ?></td>
                                <td class="fw-semibold text-danger">₹<?php echo number_format((float)$r['total_cost'], 2); ?></td>
                                <td class="fw-semibold text-success">₹<?php echo number_format((float)$r['calculated_revenue'], 2); ?></td>
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

    <!-- Fuel Efficiency Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-droplet me-2 text-info"></i> Fuel Efficiency Report</h5>
            <a href="export.php?type=efficiency" class="btn btn-sm btn-success d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Total Completed Distance (km)</th>
                        <th>Total Refueled (Liters)</th>
                        <th class="text-end">Average Fuel Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($efficiencyData)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No efficiency records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($efficiencyData as $eff): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($eff['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <code class="small text-muted"><?php echo htmlspecialchars($eff['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td><?php echo number_format((float)$eff['total_distance'], 2); ?> km</td>
                                <td><?php echo number_format((float)$eff['total_liters'], 2); ?> L</td>
                                <td class="text-end fw-bold text-info">
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
