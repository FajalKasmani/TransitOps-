<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Driver.php';

use Api\Classes\Auth;
use Api\Classes\Driver;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager', 'safety_officer'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role_name'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$driver = Driver::getById($id);

if (!$driver) {
    header("Location: list.php?error=not_found");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "CSRF security verification failed. Request blocked.";
    } else {
        if ($role === 'safety_officer') {
            // Safety Officers can only update the driver's safety score
            $safetyScore = isset($_POST['safety_score']) ? (float)$_POST['safety_score'] : (float)$driver['safety_score'];
            if ($safetyScore < 0.00 || $safetyScore > 5.00) {
                $error = "Safety score must be between 0.00 and 5.00.";
            } else {
                $data = $driver;
                $data['safety_score'] = $safetyScore;
                try {
                    Driver::update($id, $data);
                    header("Location: list.php?updated=1");
                    exit;
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        } else {
            // Admins and Fleet Managers can modify all fields
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
                Driver::update($id, $data);
                header("Location: list.php?updated=1");
                exit;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $driver = array_merge($driver, $data);
            }
        }
    }
}

$isSafetyOfficer = ($role === 'safety_officer');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Edit Driver Profile</h3>
            <p class="text-muted small mb-0">
                <?php echo $isSafetyOfficer ? 'Update safety score audit settings.' : 'Update personnel profile records.'; ?>
            </p>
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
        <form method="POST" action="edit.php?id=<?php echo $id; ?>" class="needs-validation" novalidate>
            <?php echo Auth::getCsrfInput(); ?>
            <div class="row g-3">
                <!-- Driver Name -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($driver['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> placeholder="Driver Name">
                        <label for="name" class="text-muted">Driver Name *</label>
                    </div>
                </div>

                <!-- License Number -->
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="license_number" name="license_number" required value="<?php echo htmlspecialchars($driver['license_number'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> placeholder="License Number">
                        <label for="license_number" class="text-muted">License Number *</label>
                    </div>
                </div>

                <!-- License Category -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="license_category" name="license_category" required value="<?php echo htmlspecialchars($driver['license_category'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> placeholder="License Category">
                        <label for="license_category" class="text-muted">License Category *</label>
                    </div>
                </div>

                <!-- License Expiry Date -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date" required value="<?php echo $driver['license_expiry_date']; ?>" <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> placeholder="License Expiry Date">
                        <label for="license_expiry_date" class="text-muted">License Expiry Date *</label>
                    </div>
                </div>

                <!-- Safety Score (Editable by Safety Officer) -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" min="0" max="5" class="form-control text-success fw-bold" id="safety_score" name="safety_score" required value="<?php echo number_format((float)$driver['safety_score'], 2, '.', ''); ?>" placeholder="Safety Score">
                        <label for="safety_score" class="text-muted">Safety Score (0.00 - 5.00) *</label>
                    </div>
                </div>

                <!-- Contact Number -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($driver['contact_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> placeholder="Contact Number">
                        <label for="contact_number" class="text-muted">Contact Number</label>
                    </div>
                </div>

                <!-- Status -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="status" name="status" required <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> style="border-radius: 10px;">
                            <option value="available" <?php echo $driver['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="on_trip" <?php echo $driver['status'] === 'on_trip' ? 'selected' : ''; ?>>On Trip</option>
                            <option value="off_duty" <?php echo $driver['status'] === 'off_duty' ? 'selected' : ''; ?>>Off Duty</option>
                            <option value="suspended" <?php echo $driver['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                        <label for="status" class="text-muted">Status *</label>
                    </div>
                </div>

                <!-- Email -->
                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSafetyOfficer ? 'disabled' : ''; ?> placeholder="Email Address">
                        <label for="email" class="text-muted">Email Address</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="list.php" class="btn btn-outline-secondary" style="border-radius: 10px;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
