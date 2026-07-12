<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Driver;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'license_number' => trim($_POST['license_number'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'license_category' => trim($_POST['license_category'] ?? ''),
        'license_expiry_date' => $_POST['license_expiry_date'] ?? '',
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'safety_score' => $_POST['safety_score'] !== '' ? (float)$_POST['safety_score'] : 5.00,
        'status' => $_POST['status'] ?? 'available',
        'email' => trim($_POST['email'] ?? '')
    ];

    try {
        Driver::create($data);
        header("Location: list.php?created=1");
        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Register Driver</h3>
            <p class="text-muted small mb-0">Create a new driver profile.</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Registry
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4 p-md-5">
        <form method="POST" action="add.php" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- Driver Name -->
                <div class="col-12 col-md-6">
                    <label for="name" class="form-label fw-semibold text-muted small">Driver Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. John Doe" style="border-radius: 10px;">
                </div>

                <!-- License Number -->
                <div class="col-12 col-md-6">
                    <label for="license_number" class="form-label fw-semibold text-muted small">License Number *</label>
                    <input type="text" class="form-control" id="license_number" name="license_number" required placeholder="e.g. DL-1234567-8" style="border-radius: 10px;">
                </div>

                <!-- License Category -->
                <div class="col-12 col-md-4">
                    <label for="license_category" class="form-label fw-semibold text-muted small">License Category *</label>
                    <input type="text" class="form-control" id="license_category" name="license_category" required placeholder="e.g. Class A CDL" style="border-radius: 10px;">
                </div>

                <!-- License Expiry Date -->
                <div class="col-12 col-md-4">
                    <label for="license_expiry_date" class="form-label fw-semibold text-muted small">License Expiry Date *</label>
                    <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date" required style="border-radius: 10px;">
                </div>

                <!-- Safety Score -->
                <div class="col-12 col-md-4">
                    <label for="safety_score" class="form-label fw-semibold text-muted small">Safety Score (0.00 - 5.00)</label>
                    <input type="number" step="0.01" min="0" max="5" class="form-control" id="safety_score" name="safety_score" value="5.00" placeholder="5.00" style="border-radius: 10px;">
                </div>

                <!-- Contact Number -->
                <div class="col-12 col-md-4">
                    <label for="contact_number" class="form-label fw-semibold text-muted small">Contact Number</label>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="e.g. +1 (555) 123-4567" style="border-radius: 10px;">
                </div>

                <!-- Status -->
                <div class="col-12 col-md-4">
                    <label for="status" class="form-label fw-semibold text-muted small">Status *</label>
                    <select class="form-select" id="status" name="status" required style="border-radius: 10px;">
                        <option value="available" selected>Available</option>
                        <option value="on_trip">On Trip</option>
                        <option value="off_duty">Off Duty</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <!-- Email -->
                <div class="col-12 col-md-4">
                    <label for="email" class="form-label fw-semibold text-muted small">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="e.g. driver@transitops.com" style="border-radius: 10px;">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <button type="reset" class="btn btn-outline-secondary" style="border-radius: 10px;">Reset</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Save Driver</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
