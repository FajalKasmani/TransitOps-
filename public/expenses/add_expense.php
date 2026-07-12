<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';
require_once __DIR__ . '/../../api/classes/Expense.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;
use Api\Classes\Expense;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'financial_analyst'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'vehicle_id' => $_POST['vehicle_id'] !== '' ? (int)$_POST['vehicle_id'] : null,
        'type' => $_POST['type'] ?? '',
        'description' => trim($_POST['description'] ?? ''),
        'cost' => $_POST['cost'] !== '' ? (float)$_POST['cost'] : 0.00,
        'date' => $_POST['date'] ?? ''
    ];

    try {
        Expense::create($data);
        header("Location: list.php?created=1");
        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

$vehicles = Vehicle::getAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Record Operating Expense</h3>
            <p class="text-muted small mb-0">Record tolls, maintenance procurement, and other general expenses.</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Logs
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4 p-md-5">
        <form method="POST" action="add_expense.php" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- Vehicle Select -->
                <div class="col-12 col-md-6">
                    <label for="vehicle_id" class="form-label fw-semibold text-muted small">Vehicle *</label>
                    <select class="form-select" id="vehicle_id" name="vehicle_id" required style="border-radius: 10px;">
                        <option value="">Select vehicle...</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                <?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Log Date -->
                <div class="col-12 col-md-6">
                    <label for="date" class="form-label fw-semibold text-muted small">Expense Date *</label>
                    <input type="date" class="form-control" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>" style="border-radius: 10px;">
                </div>

                <!-- Expense Type -->
                <div class="col-12 col-md-6">
                    <label for="type" class="form-label fw-semibold text-muted small">Expense Type *</label>
                    <select class="form-select" id="type" name="type" required style="border-radius: 10px;">
                        <option value="">Select category...</option>
                        <option value="toll">Toll / Road Charge</option>
                        <option value="maintenance">Maintenance Part/Log</option>
                        <option value="other">Other Operational Expense</option>
                    </select>
                </div>

                <!-- Total Cost -->
                <div class="col-12 col-md-6">
                    <label for="cost" class="form-label fw-semibold text-muted small">Expense Cost (₹) *</label>
                    <input type="number" step="0.01" class="form-control" id="cost" name="cost" required placeholder="e.g. 45.00" style="border-radius: 10px;">
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold text-muted small">Description / Memo *</label>
                    <input type="text" class="form-control" id="description" name="description" required placeholder="e.g. George Washington Bridge toll charge" style="border-radius: 10px;">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <button type="reset" class="btn btn-outline-secondary" style="border-radius: 10px;">Reset</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Save Expense Record</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
