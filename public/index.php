<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/classes/Database.php';
require_once __DIR__ . '/../api/classes/Auth.php';
require_once __DIR__ . '/../api/classes/Reports.php';

use Api\Classes\Auth;
use Api\Classes\Reports;
use Api\Classes\Database;

Auth::startSession();

// Header inclusion handles Auth and Timeout checks.
require_once __DIR__ . '/includes/header.php';

$role = $_SESSION['role_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

$pdo = Database::getInstance();
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1 text-light-theme font-weight-bold">Welcome Back, <?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-muted small mb-0">Platform Overview & Live Operations Status</p>
        </div>
        <div class="text-muted small">
            <i class="bi bi-clock-history me-1"></i> System time: <?php echo date('Y-m-d H:i'); ?>
        </div>
    </div>

    <?php if (in_array($role, ['admin', 'fleet_manager'], true)): 
        // -----------------------------------------------------
        // FLEET MANAGER & ADMIN VIEW
        // -----------------------------------------------------
        $kpis = Reports::getDashboardKPIs();
        
        // Fetch recent trips
        $tripsStmt = $pdo->query("
            SELECT t.*, v.vehicle_name, v.registration_number, d.name as driver_name 
            FROM trips t 
            JOIN vehicles v ON t.vehicle_id = v.id 
            JOIN drivers d ON t.driver_id = d.id 
            ORDER BY t.created_at DESC 
            LIMIT 5
        ");
        $recentTrips = $tripsStmt->fetchAll();
    ?>
        <!-- KPI Cards Grid -->
        <div class="row g-3 mb-4">
            <!-- Active Vehicles -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-primary bg-gradient bg-opacity-10 text-primary border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Active Vehicles</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['active_vehicles']; ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-truck-flatbed"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Available Vehicles -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-success bg-gradient bg-opacity-10 text-success border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Available Vehicles</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['available_vehicles']; ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-check-circle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Shop -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-warning bg-gradient bg-opacity-10 text-warning border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">In Shop</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['maintenance_vehicles']; ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-wrench"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fleet Utilization -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-info bg-gradient bg-opacity-10 text-info border-start border-info border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Fleet Utilization</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['fleet_utilization']; ?>%</h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-percent"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- Active Trips -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block">Active Trips (Dispatched)</span>
                            <span class="fs-4 fw-bold"><?php echo $kpis['active_trips']; ?></span>
                        </div>
                        <span class="badge bg-primary rounded-pill p-2"><i class="bi bi-compass fs-4"></i></span>
                    </div>
                </div>
            </div>
            
            <!-- Pending Trips -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block">Pending Trips (Draft)</span>
                            <span class="fs-4 fw-bold"><?php echo $kpis['pending_trips']; ?></span>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill p-2"><i class="bi bi-hourglass-split fs-4"></i></span>
                    </div>
                </div>
            </div>

            <!-- Total Cost -->
            <div class="col-12 col-sm-12 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block">Total Operational Cost</span>
                            <span class="fs-4 fw-bold">₹<?php echo number_format($kpis['total_operational_cost'], 2); ?></span>
                        </div>
                        <span class="badge bg-danger rounded-pill p-2"><i class="bi bi-currency-dollar fs-4"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Fleet Activity Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold text-light-theme">Recent Fleet Activity & Trips</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Trip ID</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Route</th>
                            <th>Cargo Wt. (kg)</th>
                            <th>Distance (km)</th>
                            <th>Status</th>
                            <th>Dispatched At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTrips)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No recent trips found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTrips as $trip): ?>
                                <tr>
                                    <td><strong>#<?php echo $trip['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($trip['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <span class="text-muted small"><?php echo htmlspecialchars($trip['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($trip['driver_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($trip['source'], ENT_QUOTES, 'UTF-8'); ?> <i class="bi bi-arrow-right small text-muted px-1"></i> <?php echo htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo number_format((float)$trip['cargo_weight'], 2); ?></td>
                                    <td><?php echo number_format((float)$trip['planned_distance'], 2); ?></td>
                                    <td>
                                        <?php 
                                            $badgeClass = match($trip['status']) {
                                                'dispatched' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $trip['status']; ?></span>
                                    </td>
                                    <td class="small text-muted"><?php echo $trip['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($role === 'safety_officer'): 
        // -----------------------------------------------------
        // SAFETY OFFICER VIEW
        // -----------------------------------------------------
        $avgScore = Reports::getAverageSafetyScore();
        
        // Expiring licenses count
        $expiringStmt = $pdo->query("
            SELECT COUNT(*) 
            FROM drivers 
            WHERE license_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_deleted = 0
        ");
        $expiringCount = (int)$expiringStmt->fetchColumn();

        // Suspended drivers count
        $suspendedStmt = $pdo->query("
            SELECT COUNT(*) 
            FROM drivers 
            WHERE status = 'suspended' AND is_deleted = 0
        ");
        $suspendedCount = (int)$suspendedStmt->fetchColumn();

        // Expiring licenses list
        $expiringListStmt = $pdo->query("
            SELECT * 
            FROM drivers 
            WHERE license_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_deleted = 0 
            ORDER BY license_expiry_date ASC 
            LIMIT 5
        ");
        $expiringDrivers = $expiringListStmt->fetchAll();
    ?>
        <!-- Safety KPI Grid -->
        <div class="row g-3 mb-4">
            <!-- Avg Safety Score -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm bg-success bg-gradient bg-opacity-10 text-success border-start border-success border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Average Safety Score</h6>
                                <h3 class="mb-0 fw-bold"><?php echo number_format($avgScore, 2); ?> / 5.00</h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-shield-check"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiring Licenses -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm bg-warning bg-gradient bg-opacity-10 text-warning border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Expiring Licenses (30 Days)</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $expiringCount; ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suspended Drivers -->
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm bg-danger bg-gradient bg-opacity-10 text-danger border-start border-danger border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Suspended Drivers</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $suspendedCount; ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-person-x"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- License Expiry Warnings Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-semibold text-warning"><i class="bi bi-exclamation-octagon-fill me-2"></i>Critical Driver License Expiries</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Driver Name</th>
                            <th>License Number</th>
                            <th>Category</th>
                            <th>Expiry Date</th>
                            <th>Safety Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expiringDrivers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No licenses expiring in the next 30 days.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expiringDrivers as $driver): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($driver['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($driver['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($driver['license_category'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td class="text-danger fw-semibold"><i class="bi bi-calendar-event me-1"></i><?php echo $driver['license_expiry_date']; ?></td>
                                    <td>
                                        <div class="fw-bold <?php echo (float)$driver['safety_score'] >= 4.0 ? 'text-success' : ((float)$driver['safety_score'] >= 3.0 ? 'text-warning' : 'text-danger'); ?>">
                                            <?php echo number_format((float)$driver['safety_score'], 2); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $driver['status'] === 'suspended' ? 'bg-danger' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars($driver['status'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($role === 'financial_analyst'): 
        // -----------------------------------------------------
        // FINANCIAL ANALYST VIEW
        // -----------------------------------------------------
        $fuelCost = (float)$pdo->query("SELECT SUM(cost) FROM fuel_logs")->fetchColumn();
        $expenseCost = (float)$pdo->query("SELECT SUM(cost) FROM expenses")->fetchColumn();
        $maintCost = (float)$pdo->query("SELECT SUM(cost) FROM maintenance_logs")->fetchColumn();
        $totalCost = $fuelCost + $expenseCost + $maintCost;

        $vehicleAnalytics = Reports::getVehicleAnalytics();
    ?>
        <!-- Financial KPI Grid -->
        <div class="row g-3 mb-4">
            <!-- Total Operational Cost -->
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm bg-danger bg-gradient bg-opacity-10 text-danger border-start border-danger border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Total Cost</h6>
                                <h3 class="mb-0 fw-bold">₹<?php echo number_format($totalCost, 2); ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-cash-stack"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fuel Cost -->
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm bg-primary bg-gradient bg-opacity-10 text-primary border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Fuel Expenses</h6>
                                <h3 class="mb-0 fw-bold">₹<?php echo number_format($fuelCost, 2); ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-fuel-pump"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Cost -->
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm bg-warning bg-gradient bg-opacity-10 text-warning border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Maintenance Cost</h6>
                                <h3 class="mb-0 fw-bold">₹<?php echo number_format($maintCost, 2); ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-tools"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Expenses -->
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm bg-info bg-gradient bg-opacity-10 text-info border-start border-info border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Other Expenses</h6>
                                <h3 class="mb-0 fw-bold">₹<?php echo number_format($expenseCost, 2); ?></h3>
                            </div>
                            <div class="fs-1"><i class="bi bi-ticket-perforated"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Cost & ROI Registry -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-semibold text-light-theme"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Vehicle Costs & ROI Summary</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Vehicle</th>
                            <th>Acquisition Cost</th>
                            <th>Fuel Cost</th>
                            <th>Maintenance Cost</th>
                            <th>Other Cost</th>
                            <th>Total Cost</th>
                            <th>Calculated Revenue</th>
                            <th>ROI %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehicleAnalytics)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No vehicle analytics data available.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehicleAnalytics as $v): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="text-muted d-block small"><?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>₹<?php echo number_format((float)$v['acquisition_cost'], 2); ?></td>
                                    <td>₹<?php echo number_format((float)$v['fuel_cost'], 2); ?></td>
                                    <td>₹<?php echo number_format((float)$v['maintenance_cost'], 2); ?></td>
                                    <td>₹<?php echo number_format((float)$v['expense_cost'], 2); ?></td>
                                    <td><strong class="text-danger">₹<?php echo number_format((float)$v['total_cost'], 2); ?></strong></td>
                                    <td class="text-success">₹<?php echo number_format((float)$v['calculated_revenue'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $v['roi'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo number_format($v['roi'], 2); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($role === 'driver'): 
        // -----------------------------------------------------
        // DRIVER VIEW
        // -----------------------------------------------------
        $driverStmt = $pdo->prepare("SELECT id FROM drivers WHERE email = :email AND is_deleted = 0 LIMIT 1");
        $driverStmt->execute(['email' => $email]);
        $driverId = (int)$driverStmt->fetchColumn();

        // Fetch assigned trips
        $assignedStmt = $pdo->prepare("
            SELECT t.*, v.vehicle_name, v.registration_number 
            FROM trips t 
            JOIN vehicles v ON t.vehicle_id = v.id 
            WHERE t.driver_id = :driver_id 
            ORDER BY t.created_at DESC
        ");
        $assignedStmt->execute(['driver_id' => $driverId]);
        $myTrips = $assignedStmt->fetchAll();
    ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="mb-0 fw-semibold text-light-theme"><i class="bi bi-calendar-check me-2 text-primary"></i>My Assigned Trips Schedule</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Trip ID</th>
                            <th>Vehicle</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Weight (kg)</th>
                            <th>Distance (km)</th>
                            <th>Status</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myTrips)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">You have no assigned trips.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myTrips as $trip): ?>
                                <tr>
                                    <td><strong>#<?php echo $trip['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($trip['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <span class="text-muted small"><?php echo htmlspecialchars($trip['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($trip['source'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo number_format((float)$trip['cargo_weight'], 2); ?></td>
                                    <td><?php echo number_format((float)$trip['planned_distance'], 2); ?></td>
                                    <td>
                                        <?php 
                                            $badgeClass = match($trip['status']) {
                                                'dispatched' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $trip['status']; ?></span>
                                    </td>
                                    <td><?php echo $trip['start_time'] ?? '-'; ?></td>
                                    <td><?php echo $trip['end_time'] ?? '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>
