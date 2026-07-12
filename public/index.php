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

<!-- Premium Dark Mode Theme Stylesheet for Dashboard -->
<style>
    /* Scope styling to dashboard container to prevent header conflict */
    .dashboard-wrapper {
        background-color: #0b0f19;
        color: #f8fafc;
        font-family: 'Inter', sans-serif;
    }
    
    .dashboard-wrapper .text-muted {
        color: #94a3b8 !important;
    }

    /* Glassmorphism Card design */
    .dashboard-wrapper .card {
        background-color: #111827;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.3);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    
    .dashboard-wrapper .card:hover {
        transform: translateY(-2px);
        border-color: rgba(139, 92, 246, 0.3) !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
    }

    /* Left-accent colors */
    .border-start-primary {
        border-left: 4px solid #6366f1 !important;
    }
    .border-start-success {
        border-left: 4px solid #10b981 !important;
    }
    .border-start-warning {
        border-left: 4px solid #f59e0b !important;
    }
    .border-start-info {
        border-left: 4px solid #06b6d4 !important;
    }
    .border-start-danger {
        border-left: 4px solid #ef4444 !important;
    }

    /* Card background gradients for subtle modern glows */
    .bg-glow-primary {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(17, 24, 39, 0) 100%) !important;
    }
    .bg-glow-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(17, 24, 39, 0) 100%) !important;
    }
    .bg-glow-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(17, 24, 39, 0) 100%) !important;
    }
    .bg-glow-info {
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(17, 24, 39, 0) 100%) !important;
    }
    .bg-glow-danger {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(17, 24, 39, 0) 100%) !important;
    }

    /* Status badge glows */
    .dashboard-wrapper .badge {
        font-weight: 600;
        letter-spacing: 0.03em;
        padding: 0.4em 0.8em;
        border-radius: 2rem;
    }

    .dashboard-wrapper .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.4rem;
        background-color: currentColor;
    }

    .dashboard-wrapper .status-pulse {
        animation: pulse-glow 2s infinite;
    }

    @keyframes pulse-glow {
        0% { opacity: 0.4; transform: scale(0.9); }
        50% { opacity: 1; transform: scale(1.1); }
        100% { opacity: 0.4; transform: scale(0.9); }
    }

    /* Table styling for dark theme */
    .dashboard-wrapper .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .dashboard-wrapper .table {
        background-color: #111827;
        color: #e2e8f0;
        border-color: rgba(255, 255, 255, 0.05);
        margin: 0;
    }

    .dashboard-wrapper .table-striped>tbody>tr:nth-of-type(odd)>* {
        background-color: rgba(255, 255, 255, 0.015);
        color: #e2e8f0;
    }

    .dashboard-wrapper .table-hover>tbody>tr:hover>* {
        background-color: rgba(255, 255, 255, 0.035) !important;
        color: #fff;
    }

    .dashboard-wrapper .table thead th {
        background-color: #1f2937;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid rgba(255, 255, 255, 0.08);
        padding: 1rem;
    }

    .dashboard-wrapper .table tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.875rem;
    }

    /* Icon wrapper styles */
    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.03);
    }
    
    .icon-box-primary { color: #6366f1; background-color: rgba(99, 102, 241, 0.15); }
    .icon-box-success { color: #10b981; background-color: rgba(16, 185, 129, 0.15); }
    .icon-box-warning { color: #f59e0b; background-color: rgba(245, 158, 11, 0.15); }
    .icon-box-info { color: #06b6d4; background-color: rgba(6, 182, 212, 0.15); }
    .icon-box-danger { color: #ef4444; background-color: rgba(239, 68, 68, 0.15); }

    /* Custom scrollbars */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }
</style>

<div class="dashboard-wrapper min-vh-100 py-4">
    <div class="container-fluid">
        <!-- Header Banner Section -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
            <div>
                <h2 class="h3 mb-1 text-light-theme font-weight-bold" style="letter-spacing: -0.02em; color: #fff;">
                    Welcome, <?php echo htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                </h2>
                <p class="text-muted small mb-0">Platform Overview & Live Operations Status</p>
            </div>
            <div class="bg-dark-subtle border border-secondary border-opacity-25 px-3 py-2 rounded d-flex align-items-center gap-2 text-muted small" style="background-color: #111827 !important; border-color: rgba(255,255,255,0.05) !important;">
                <i class="bi bi-clock-history text-primary"></i>
                <span class="font-monospace">System time: <?php echo date('Y-m-d H:i'); ?></span>
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
                    <div class="card border-0 h-100 border-start-primary bg-glow-primary">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Active Vehicles</h6>
                                    <h3 class="mb-0 fw-bold text-white"><?php echo $kpis['active_vehicles']; ?></h3>
                                </div>
                                <div class="icon-box icon-box-primary fs-3"><i class="bi bi-truck-flatbed"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Available Vehicles -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 h-100 border-start-success bg-glow-success">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Available Vehicles</h6>
                                    <h3 class="mb-0 fw-bold text-white"><?php echo $kpis['available_vehicles']; ?></h3>
                                </div>
                                <div class="icon-box icon-box-success fs-3"><i class="bi bi-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- In Shop -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 h-100 border-start-warning bg-glow-warning">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">In Shop</h6>
                                    <h3 class="mb-0 fw-bold text-white"><?php echo $kpis['maintenance_vehicles']; ?></h3>
                                </div>
                                <div class="icon-box icon-box-warning fs-3"><i class="bi bi-wrench"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fleet Utilization -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 h-100 border-start-info bg-glow-info">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Fleet Utilization</h6>
                                    <h3 class="mb-0 fw-bold text-white"><?php echo $kpis['fleet_utilization']; ?>%</h3>
                                    <div class="progress mt-2" style="height: 4px; background-color: rgba(255,255,255,0.05)">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $kpis['fleet_utilization']; ?>%"></div>
                                    </div>
                                </div>
                                <div class="icon-box icon-box-info fs-3"><i class="bi bi-percent"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Sub-KPIs Grid -->
            <div class="row g-3 mb-4">
                <!-- Active Trips -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card border-0">
                        <div class="card-body d-flex align-items-center justify-content-between py-3">
                            <div>
                                <span class="text-muted small d-block mb-1">Active Trips (Dispatched)</span>
                                <span class="fs-4 fw-bold text-white"><?php echo $kpis['active_trips']; ?></span>
                            </div>
                            <span class="badge bg-primary bg-opacity-25 text-primary p-2 border border-primary border-opacity-25"><i class="bi bi-compass fs-4"></i></span>
                        </div>
                    </div>
                </div>
                <!-- Pending Trips -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card border-0">
                        <div class="card-body d-flex align-items-center justify-content-between py-3">
                            <div>
                                <span class="text-muted small d-block mb-1">Pending Trips (Draft)</span>
                                <span class="fs-4 fw-bold text-white"><?php echo $kpis['pending_trips']; ?></span>
                            </div>
                            <span class="badge bg-warning bg-opacity-25 text-warning p-2 border border-warning border-opacity-25"><i class="bi bi-hourglass-split fs-4"></i></span>
                        </div>
                    </div>
                </div>
                <!-- Total Cost -->
                <div class="col-12 col-sm-12 col-lg-4">
                    <div class="card border-0">
                        <div class="card-body d-flex align-items-center justify-content-between py-3">
                            <div>
                                <span class="text-muted small d-block mb-1">Total Operational Cost</span>
                                <span class="fs-4 fw-bold text-danger">₹<?php echo number_format($kpis['total_operational_cost'], 2); ?></span>
                            </div>
                            <span class="badge bg-danger bg-opacity-25 text-danger p-2 border border-danger border-opacity-25"><i class="bi bi-currency-dollar fs-4"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Fleet Activity Table -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10 py-3">
                    <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                        <i class="bi bi-activity text-primary"></i> Recent Fleet Activity & Trips
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
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
                                        <td><strong class="text-primary font-monospace">#<?php echo $trip['id']; ?></strong></td>
                                        <td>
                                            <div class="fw-semibold text-white"><?php echo htmlspecialchars($trip['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <span class="text-muted small"><?php echo htmlspecialchars($trip['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td class="text-white"><?php echo htmlspecialchars($trip['driver_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <div class="text-white">
                                                <?php echo htmlspecialchars($trip['source'], ENT_QUOTES, 'UTF-8'); ?> 
                                                <i class="bi bi-arrow-right-short text-muted px-1 fs-5"></i> 
                                                <?php echo htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td class="text-white"><?php echo number_format((float)$trip['cargo_weight'], 2); ?></td>
                                        <td class="text-white"><?php echo number_format((float)$trip['planned_distance'], 2); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = match($trip['status']) {
                                                'dispatched' => 'bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25',
                                                'completed' => 'bg-success bg-opacity-25 text-success border border-success border-opacity-25',
                                                'cancelled' => 'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25',
                                                default => 'bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> text-uppercase">
                                                <?php if($trip['status'] === 'dispatched'): ?>
                                                    <span class="status-dot status-pulse"></span>
                                                <?php endif; ?>
                                                <?php echo $trip['status']; ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted font-monospace"><?php echo $trip['created_at']; ?></td>
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
                    <div class="card border-0 h-100 border-start-success bg-glow-success">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Average Safety Score</h6>
                                    <h3 class="mb-0 fw-bold text-white"><?php echo number_format($avgScore, 2); ?> / 5.00</h3>
                                    <div class="progress mt-2" style="height: 4px; background-color: rgba(255,255,255,0.05); width: 150px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($avgScore / 5.0) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="icon-box icon-box-success fs-3"><i class="bi bi-shield-check"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expiring Licenses -->
                <div class="col-12 col-md-4">
                    <div class="card border-0 h-100 border-start-warning bg-glow-warning">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Expiring Licenses (30 Days)</h6>
                                    <h3 class="mb-0 fw-bold text-warning"><?php echo $expiringCount; ?></h3>
                                </div>
                                <div class="icon-box icon-box-warning fs-3"><i class="bi bi-exclamation-triangle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suspended Drivers -->
                <div class="col-12 col-md-4">
                    <div class="card border-0 h-100 border-start-danger bg-glow-danger">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Suspended Drivers</h6>
                                    <h3 class="mb-0 fw-bold text-danger"><?php echo $suspendedCount; ?></h3>
                                </div>
                                <div class="icon-box icon-box-danger fs-3"><i class="bi bi-person-x"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- License Expiry Warnings Table -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10 py-3">
                    <h5 class="mb-0 fw-semibold text-warning d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-octagon-fill text-warning"></i> Critical Driver License Expiries
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
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
                                        <td><strong class="text-white"><?php echo htmlspecialchars($driver['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($driver['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td><span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25"><?php echo htmlspecialchars($driver['license_category'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td class="text-danger fw-semibold">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <span class="font-monospace"><?php echo $driver['license_expiry_date']; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $score = (float)$driver['safety_score'];
                                                $scoreClass = $score >= 4.0 ? 'text-success' : ($score >= 3.0 ? 'text-warning' : 'text-danger');
                                            ?>
                                            <div class="fw-bold <?php echo $scoreClass; ?>">
                                                <i class="bi bi-star-fill small me-1"></i>
                                                <?php echo number_format($score, 2); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusBadge = $driver['status'] === 'suspended' ? 'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25' : 'bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25';
                                            ?>
                                            <span class="badge <?php echo $statusBadge; ?>">
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
                    <div class="card border-0 h-100 border-start-danger bg-glow-danger">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Total Cost</h6>
                                    <h3 class="mb-0 fw-bold text-danger">$<?php echo number_format($totalCost, 2); ?></h3>
                                </div>
                                <div class="icon-box icon-box-danger fs-3"><i class="bi bi-cash-stack"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fuel Cost -->
                <div class="col-12 col-md-3">
                    <div class="card border-0 h-100 border-start-primary bg-glow-primary">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Fuel Expenses</h6>
                                    <h3 class="mb-0 fw-bold text-white">$<?php echo number_format($fuelCost, 2); ?></h3>
                                </div>
                                <div class="icon-box icon-box-primary fs-3"><i class="bi bi-fuel-pump"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Cost -->
                <div class="col-12 col-md-3">
                    <div class="card border-0 h-100 border-start-warning bg-glow-warning">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Maintenance Cost</h6>
                                    <h3 class="mb-0 fw-bold text-white">$<?php echo number_format($maintCost, 2); ?></h3>
                                </div>
                                <div class="icon-box icon-box-warning fs-3"><i class="bi bi-tools"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Expenses -->
                <div class="col-12 col-md-3">
                    <div class="card border-0 h-100 border-start-info bg-glow-info">
                        <div class="card-body py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1 small fw-semibold text-muted">Other Expenses</h6>
                                    <h3 class="mb-0 fw-bold text-white">$<?php echo number_format($expenseCost, 2); ?></h3>
                                </div>
                                <div class="icon-box icon-box-info fs-3"><i class="bi bi-ticket-perforated"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Cost & ROI Registry -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10 py-3">
                    <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-primary"></i> Vehicle Costs & ROI Summary
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
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
                                            <strong class="text-white"><?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span class="text-muted d-block small font-monospace"><?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td class="text-white">$<?php echo number_format((float)$v['acquisition_cost'], 2); ?></td>
                                        <td class="text-white">$<?php echo number_format((float)$v['fuel_cost'], 2); ?></td>
                                        <td class="text-white">$<?php echo number_format((float)$v['maintenance_cost'], 2); ?></td>
                                        <td class="text-white">$<?php echo number_format((float)$v['expense_cost'], 2); ?></td>
                                        <td><strong class="text-danger">$<?php echo number_format((float)$v['total_cost'], 2); ?></strong></td>
                                        <td class="text-success fw-semibold">$<?php echo number_format((float)$v['calculated_revenue'], 2); ?></td>
                                        <td>
                                            <?php 
                                                $roiVal = (float)$v['roi'];
                                                $roiBadge = $roiVal >= 0 ? 'bg-success bg-opacity-25 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25';
                                            ?>
                                            <span class="badge <?php echo $roiBadge; ?>">
                                                <i class="bi <?php echo $roiVal >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill'; ?> me-1"></i>
                                                <?php echo number_format($roiVal, 2); ?>%
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
            <!-- Driver Trips Card -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-10 py-3">
                    <h5 class="mb-0 fw-semibold text-white d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-check text-primary"></i> My Assigned Trips Schedule
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
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
                                        <td><strong class="text-primary font-monospace">#<?php echo $trip['id']; ?></strong></td>
                                        <td>
                                            <div class="fw-semibold text-white"><?php echo htmlspecialchars($trip['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <span class="text-muted small"><?php echo htmlspecialchars($trip['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td class="text-white"><?php echo htmlspecialchars($trip['source'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-white"><?php echo htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-white"><?php echo number_format((float)$trip['cargo_weight'], 2); ?></td>
                                        <td class="text-white"><?php echo number_format((float)$trip['planned_distance'], 2); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = match($trip['status']) {
                                                'dispatched' => 'bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25',
                                                'completed' => 'bg-success bg-opacity-25 text-success border border-success border-opacity-25',
                                                'cancelled' => 'bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25',
                                                default => 'bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> text-uppercase">
                                                <?php if($trip['status'] === 'dispatched'): ?>
                                                    <span class="status-dot status-pulse"></span>
                                                <?php endif; ?>
                                                <?php echo $trip['status']; ?>
                                            </span>
                                        </td>
                                        <td class="text-white font-monospace"><?php echo $trip['start_time'] ?? '-'; ?></td>
                                        <td class="text-white font-monospace"><?php echo $trip['end_time'] ?? '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>