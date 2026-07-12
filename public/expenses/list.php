<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Fuel.php';
require_once __DIR__ . '/../../api/classes/Expense.php';

use Api\Classes\Auth;
use Api\Classes\Fuel;
use Api\Classes\Expense;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'financial_analyst'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';

// Handle delete action
$error = null;
$successMsg = null;
if (isset($_GET['action']) && isset($_GET['type']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $type = $_GET['type'];

    if ($type === 'fuel') {
        if (Fuel::delete($id)) {
            $successMsg = "Fuel log entry removed.";
        } else {
            $error = "Failed to remove fuel log.";
        }
    } elseif ($type === 'expense') {
        if (Expense::delete($id)) {
            $successMsg = "Expense log entry removed.";
        } else {
            $error = "Failed to remove expense log.";
        }
    }
}

$fuelLogs = Fuel::getAll();
$expenseLogs = Expense::getAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Financial Operations Log</h3>
            <p class="text-muted small mb-0">Record and audit operating expenses and fuel procurement.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="add_fuel.php" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <i class="bi bi-droplet-fill"></i> Fuel Purchase
            </a>
            <a href="add_expense.php" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="bi bi-receipt"></i> Log Expense
            </a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-fuel-tab" data-bs-toggle="pill" data-bs-target="#pills-fuel" type="button" role="tab" aria-controls="pills-fuel" aria-selected="true">
                <i class="bi bi-droplet me-2"></i> Fuel Procurement
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-expense-tab" data-bs-toggle="pill" data-bs-target="#pills-expense" type="button" role="tab" aria-controls="pills-expense" aria-selected="false">
                <i class="bi bi-receipt-cutoff me-2"></i> General Expenses
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <!-- Fuel Procurement Tab -->
        <div class="tab-pane fade show active" id="pills-fuel" role="tabpanel" aria-labelledby="pills-fuel-tab">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Vehicle</th>
                                <th>Volume (Liters)</th>
                                <th>Total Cost</th>
                                <th>Date Logged</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fuelLogs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No fuel logs registered.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fuelLogs as $f): ?>
                                    <tr>
                                        <td><strong>#<?php echo $f['id']; ?></strong></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($f['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <span class="text-muted small"><?php echo htmlspecialchars($f['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td><?php echo number_format((float)$f['liters'], 2); ?> L</td>
                                        <td class="fw-bold text-danger">₹<?php echo number_format((float)$f['cost'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($f['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-end">
                                            <a href="list.php?action=delete&type=fuel&id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove fuel log entry?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- General Expenses Tab -->
        <div class="tab-pane fade" id="pills-expense" role="tabpanel" aria-labelledby="pills-expense-tab">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Total Cost</th>
                                <th>Date Logged</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenseLogs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No expenses registered.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenseLogs as $e): ?>
                                    <tr>
                                        <td><strong>#<?php echo $e['id']; ?></strong></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($e['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <span class="text-muted small"><?php echo htmlspecialchars($e['registration_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $badgeClass = match($e['type']) {
                                                    'toll' => 'bg-info text-dark',
                                                    'maintenance' => 'bg-warning text-dark',
                                                    'other' => 'bg-secondary',
                                                    default => 'bg-secondary'
                                                };
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo $e['type']; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($e['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="fw-bold text-danger">₹<?php echo number_format((float)$e['cost'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($e['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-end">
                                            <a href="list.php?action=delete&type=expense&id=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove expense log entry?');"><i class="bi bi-trash"></i></a>
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

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
