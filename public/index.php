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

<style>
/* ---------- Dashboard Table ---------- */

.dashboard-table{
    margin:0;
    border-collapse:separate;
    border-spacing:0;
}

.dashboard-table thead th{
    padding:16px;
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:.05rem;
    font-weight:700;
    border:none;
}

.dashboard-table tbody td{
    padding:16px;
    border:none;
    transition:.25s;
    vertical-align:middle;
}

.dashboard-table tbody tr{
    transition:.25s;
}

.dashboard-table tbody tr:hover{
    transform:translateY(-2px);
}

/* Light Theme */

[data-bs-theme="light"] .dashboard-table{
    background:#fff;
}

[data-bs-theme="light"] .dashboard-table thead th{
    background:#f8fafc;
    color:#475569;
}

[data-bs-theme="light"] .dashboard-table tbody tr{
    border-bottom:1px solid #e5e7eb;
}

[data-bs-theme="light"] .dashboard-table tbody tr:hover{
    background:#f8fbff;
}

/* Dark Theme */

[data-bs-theme="dark"] .dashboard-table{
    background:#0f172a;
}

[data-bs-theme="dark"] .dashboard-table thead th{
    background:#1e293b;
    color:#cbd5e1;
}

[data-bs-theme="dark"] .dashboard-table tbody td{
    color:#e2e8f0;
}

[data-bs-theme="dark"] .dashboard-table tbody tr{
    border-bottom:1px solid rgba(255,255,255,.05);
}

[data-bs-theme="dark"] .dashboard-table tbody tr:hover{
    background:rgba(99,102,241,.08);
}

/* Status Badge */

.status-badge{
    padding:7px 14px;
    border-radius:30px;
    font-size:11px;
    font-weight:600;
    letter-spacing:.05rem;
}

/* Avatar */

.avatar-circle{
    width:36px;
    height:36px;
    border-radius:50%;
    background:#4f46e5;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
}
</style>


<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeInDown">
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
        $maintAlerts = Reports::getPreventativeMaintenanceAlerts();
    ?>
        <?php
        $totalVehicles = max(1, (int)$kpis['active_vehicles'] + (int)$kpis['available_vehicles'] + (int)$kpis['maintenance_vehicles']);
        $activePct = ((int)$kpis['active_vehicles'] / $totalVehicles) * 100;
        $availablePct = ((int)$kpis['available_vehicles'] / $totalVehicles) * 100;
        $maintPct = ((int)$kpis['maintenance_vehicles'] / $totalVehicles) * 100;
        $utilizationPct = (float)$kpis['fleet_utilization'];
        ?>
        <!-- KPI Cards Grid -->
        <div class="row g-3 mb-4 animate__animated animate__fadeInUp">
            <!-- Active Vehicles -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 h-100 text-primary border-start border-primary border-4 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Active Vehicles</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['active_vehicles']; ?></h3>
                            </div>
                            <div class="fs-1 opacity-75"><i class="bi bi-truck-flatbed"></i></div>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $activePct; ?>%" aria-valuenow="<?php echo $activePct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Available Vehicles -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 h-100 text-success border-start border-success border-4 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Available Vehicles</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['available_vehicles']; ?></h3>
                            </div>
                            <div class="fs-1 opacity-75"><i class="bi bi-check-circle"></i></div>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $availablePct; ?>%" aria-valuenow="<?php echo $availablePct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Shop -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 h-100 text-warning border-start border-warning border-4 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">In Shop</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['maintenance_vehicles']; ?></h3>
                            </div>
                            <div class="fs-1 opacity-75"><i class="bi bi-wrench"></i></div>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $maintPct; ?>%" aria-valuenow="<?php echo $maintPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fleet Utilization -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 h-100 text-info border-start border-info border-4 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Fleet Utilization</h6>
                                <h3 class="mb-0 fw-bold"><?php echo $kpis['fleet_utilization']; ?>%</h3>
                            </div>
                            <div class="fs-1 opacity-75"><i class="bi bi-percent"></i></div>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $utilizationPct; ?>%" aria-valuenow="<?php echo $utilizationPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $totalTrips = (int)$kpis['active_trips'] + (int)$kpis['pending_trips'];
        $activeTripsPct = $totalTrips > 0 ? ((int)$kpis['active_trips'] / $totalTrips) * 100 : 0;
        $pendingTripsPct = $totalTrips > 0 ? ((int)$kpis['pending_trips'] / $totalTrips) * 100 : 0;
        ?>
        <div class="row g-3 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <!-- Active Trips -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card border-0 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <span class="text-muted small d-block text-uppercase fw-semibold">Active Trips (Dispatched)</span>
                                <span class="fs-3 fw-bold text-primary"><?php echo $kpis['active_trips']; ?></span>
                            </div>
                            <span class="badge bg-primary bg-gradient rounded-pill p-3 shadow-sm"><i class="bi bi-compass fs-4"></i></span>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $activeTripsPct; ?>%" aria-valuenow="<?php echo $activeTripsPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Trips -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card border-0 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <span class="text-muted small d-block text-uppercase fw-semibold">Pending Trips (Draft)</span>
                                <span class="fs-3 fw-bold text-warning"><?php echo $kpis['pending_trips']; ?></span>
                            </div>
                            <span class="badge bg-warning text-dark bg-gradient rounded-pill p-3 shadow-sm"><i class="bi bi-hourglass-split fs-4"></i></span>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pendingTripsPct; ?>%" aria-valuenow="<?php echo $pendingTripsPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Cost -->
            <div class="col-12 col-sm-12 col-lg-4">
                <div class="card border-0 hover-lift card-3d">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <span class="text-muted small d-block text-uppercase fw-semibold">Total Operational Cost</span>
                                <span class="fs-3 fw-bold text-danger">₹<?php echo number_format($kpis['total_operational_cost'], 2); ?></span>
                            </div>
                            <span class="badge bg-danger bg-gradient rounded-pill p-3 shadow-sm"><i class="bi bi-currency-dollar fs-4"></i></span>
                        </div>
                        <div class="progress-glass">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Charts (New) -->
        <div class="row g-3 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <!-- Fleet Status Chart -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body">
                        <h6 class="fw-semibold text-muted mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Fleet Status Overview</h6>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="fleetStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Trips Timeline Chart -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body">
                        <h6 class="fw-semibold text-muted mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Recent Trips Activity</h6>
                        <div style="position: relative; height: 250px; width: 100%;">
                            <canvas id="recentTripsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preventative Maintenance Alerts -->
        <?php if (!empty($maintAlerts)): ?>
            <div class="alert alert-warning border-0 shadow-sm d-flex flex-column gap-2 mb-4" role="alert" style="border-radius: 12px; background-color: #3b2e11; color: #fef08a;">
                <div class="d-flex align-items-center gap-2 fw-bold">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <span>Preventative Maintenance Alerts</span>
                </div>
                <div class="small">
                    The following vehicles have exceeded the 10,000 km odometer threshold and require preventative service inspections:
                    <ul class="mb-0 mt-2">
                        <?php foreach ($maintAlerts as $alertVeh): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($alertVeh['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></strong> 
                                (<code><?php echo htmlspecialchars($alertVeh['registration_number'], ENT_QUOTES, 'UTF-8'); ?></code>) 
                                - Current Odometer: <strong><?php echo number_format((float)$alertVeh['odometer'], 2); ?> km</strong>. 
                                Last Maintenance: <em><?php echo $alertVeh['last_maint_date'] ? htmlspecialchars($alertVeh['last_maint_date'], ENT_QUOTES, 'UTF-8') : 'None'; ?></em>.
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Fleet Activity Table -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold text-light-theme">Recent Fleet Activity & Trips</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
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

<?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if(typeof Chart !== 'undefined') {
        const theme = document.documentElement.getAttribute('data-bs-theme') || 'dark';
        const textColor = theme === 'dark' ? '#94a3b8' : '#475569';
        
        // Fleet Status Chart
        const fleetCtx = document.getElementById('fleetStatusChart');
        if(fleetCtx) {
            new Chart(fleetCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Available', 'In Shop'],
                    datasets: [{
                        data: [<?php echo (int)$kpis['active_vehicles']; ?>, <?php echo (int)$kpis['available_vehicles']; ?>, <?php echo (int)$kpis['maintenance_vehicles']; ?>],
                        backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor } }
                    }
                }
            });
        }
        
        // Trips Activity Chart (Mocking past 5 days for visual)
        const tripsCtx = document.getElementById('recentTripsChart');
        if(tripsCtx) {
            new Chart(tripsCtx, {
                type: 'bar',
                data: {
                    labels: ['Day -4', 'Day -3', 'Day -2', 'Yesterday', 'Today'],
                    datasets: [{
                        label: 'Trips Completed',
                        data: [12, 19, 15, 17, <?php echo (int)$kpis['active_trips'] + (int)$kpis['pending_trips']; ?>],
                        backgroundColor: '#6366f1',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(150, 150, 150, 0.1)' }, ticks: { color: textColor } },
                        x: { grid: { display: false }, ticks: { color: textColor } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }
});
</script>
<?php endif; ?>

<?php 
require_once __DIR__ . '/includes/footer.php'; 
?>
