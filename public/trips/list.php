<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Trip.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Trip;
use Api\Classes\Database;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'driver'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';
$email = $_SESSION['email'] ?? '';

$pdo = Database::getInstance();
$trips = [];

if ($role === 'driver') {
    // Find driver id
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE email = :email AND is_deleted = 0 LIMIT 1");
    $stmt->execute(['email' => $email]);
    $driverId = (int)$stmt->fetchColumn();

    // Fetch driver-specific trips
    $stmt = $pdo->prepare("
        SELECT t.*, v.vehicle_name, v.registration_number, d.name as driver_name 
        FROM trips t
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN drivers d ON t.driver_id = d.id
        WHERE t.driver_id = :driver_id
        ORDER BY t.id DESC
    ");
    $stmt->execute(['driver_id' => $driverId]);
    $trips = $stmt->fetchAll();
} else {
    // Fetch all trips for Admin/Fleet Manager
    $trips = Trip::getAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Trip Dispatch Logs</h3>
            <p class="text-muted small mb-0">Track vehicle routing, cargo loading, and scheduling status.</p>
        </div>
        <?php if (in_array($role, ['admin', 'fleet_manager'], true)): ?>
            <a href="add.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-send-plus-fill"></i> Dispatch New Trip
            </a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Trip ID</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Route Details</th>
                        <th>Cargo Weight (kg)</th>
                        <th>Distance (km)</th>
                        <th>Status</th>
                        <th>Timestamps</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trips)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No trips recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trips as $t): ?>
                            <tr>
                                <td><strong>#<?php echo $t['id']; ?></strong></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($t['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <span class="text-muted small"><?php echo htmlspecialchars($t['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($t['driver_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($t['source'], ENT_QUOTES, 'UTF-8'); ?> <i class="bi bi-arrow-right text-muted px-1"></i> <?php echo htmlspecialchars($t['destination'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td><?php echo number_format((float)$t['cargo_weight'], 2); ?> kg</td>
                                <td>
                                    <div>Planned: <?php echo number_format((float)$t['planned_distance'], 2); ?> km</div>
                                    <?php if ($t['actual_distance'] !== null): ?>
                                        <span class="text-success small fw-semibold">Actual: <?php echo number_format((float)$t['actual_distance'], 2); ?> km</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match($t['status']) {
                                            'draft' => 'bg-secondary',
                                            'dispatched' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $t['status']; ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?php if ($t['start_time']): ?>
                                        <div>Started: <?php echo $t['start_time']; ?></div>
                                    <?php endif; ?>
                                    <?php if ($t['end_time']): ?>
                                        <div>Ended: <?php echo $t['end_time']; ?></div>
                                    <?php endif; ?>
                                    <?php if (!$t['start_time'] && !$t['end_time']): ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi <?php echo ($role === 'driver') ? 'bi-play-circle-fill' : 'bi-pencil'; ?>"></i>
                                    </a>
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
