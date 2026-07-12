<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';
require_once __DIR__ . '/../../api/classes/Driver.php';
require_once __DIR__ . '/../../api/classes/Trip.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;
use Api\Classes\Driver;
use Api\Classes\Trip;

Auth::startSession();

if (!Auth::checkAccess(['admin', 'fleet_manager'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "CSRF security verification failed. Request blocked.";
    } else {
        $data = [
            'vehicle_id' => $_POST['vehicle_id'] !== '' ? (int)$_POST['vehicle_id'] : null,
            'driver_id' => $_POST['driver_id'] !== '' ? (int)$_POST['driver_id'] : null,
            'source' => trim($_POST['source'] ?? ''),
            'destination' => trim($_POST['destination'] ?? ''),
            'cargo_weight' => $_POST['cargo_weight'] !== '' ? (float)$_POST['cargo_weight'] : 0.00,
            'planned_distance' => $_POST['planned_distance'] !== '' ? (float)$_POST['planned_distance'] : 0.00,
            'status' => $_POST['status'] ?? 'draft'
        ];

        try {
            Trip::create($data);
            header("Location: list.php?created=1");
            exit;
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$availableVehicles = Vehicle::getAvailable();
$availableDrivers = Driver::getAvailable();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="list.php" class="text-decoration-none">Trips</a></li>
            <li class="breadcrumb-item active text-light-theme" aria-current="page">Dispatch</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme">Dispatch New Trip</h3>
            <p class="text-muted small mb-0">Create a new routing plan and assign transport assets.</p>
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

    <!-- Wizard Stepper Indicators -->
    <div class="card border-0 shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between position-relative mx-auto" style="max-width: 600px; width: 100%;">
            <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-secondary" style="height: 2px; z-index: 1;"></div>
            <div class="position-absolute top-50 start-0 translate-middle-y bg-primary transition-all" id="wizard-progress" style="height: 2px; z-index: 1; width: 0%;"></div>
            
            <!-- Step 1 Indicator -->
            <div class="text-center position-relative" style="z-index: 2;">
                <button type="button" class="btn btn-primary rounded-circle border-0 d-flex align-items-center justify-content-center fw-bold transition-all" id="step-indicator-1" style="width: 40px; height: 40px;">1</button>
                <span class="small fw-semibold text-muted d-block mt-2">Route Details</span>
            </div>
            <!-- Step 2 Indicator -->
            <div class="text-center position-relative" style="z-index: 2;">
                <button type="button" class="btn btn-secondary rounded-circle border-0 d-flex align-items-center justify-content-center fw-bold transition-all" id="step-indicator-2" style="width: 40px; height: 40px;">2</button>
                <span class="small fw-semibold text-muted d-block mt-2">Asset Assignment</span>
            </div>
            <!-- Step 3 Indicator -->
            <div class="text-center position-relative" style="z-index: 2;">
                <button type="button" class="btn btn-secondary rounded-circle border-0 d-flex align-items-center justify-content-center fw-bold transition-all" id="step-indicator-3" style="width: 40px; height: 40px;">3</button>
                <span class="small fw-semibold text-muted d-block mt-2">Cargo & Status</span>
            </div>
        </div>
    </div>

    <!-- Wizard Form Card -->
    <div class="card border-0 shadow-sm p-4 p-md-5">
        <form method="POST" action="add.php" id="dispatchForm" class="needs-validation" novalidate>
            <?php echo Auth::getCsrfInput(); ?>

            <!-- STEP 1: ROUTE DETAILS -->
            <div class="wizard-step" id="step-1-content">
                <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Step 1: Route Planning</h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="source" name="source" required placeholder="Source Location">
                            <label for="source" class="text-muted">Source Location *</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="destination" name="destination" required placeholder="Destination Location">
                            <label for="destination" class="text-muted">Destination Location *</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="planned_distance" name="planned_distance" required placeholder="Planned Distance (km)">
                            <label for="planned_distance" class="text-muted">Planned Distance (km) *</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: ASSET ASSIGNMENT -->
            <div class="wizard-step d-none" id="step-2-content">
                <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-truck me-2"></i>Step 2: Assign Fleet Assets</h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                <option value="">Select an available vehicle...</option>
                                <?php foreach ($availableVehicles as $v): ?>
                                    <option value="<?php echo $v['id']; ?>">
                                        <?php echo htmlspecialchars($v['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($v['registration_number'], ENT_QUOTES, 'UTF-8'); ?>) - Max Load: <?php echo $v['max_load_capacity']; ?>kg
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="vehicle_id" class="text-muted">Vehicle Assignment *</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="driver_id" name="driver_id" required>
                                <option value="">Select an available driver...</option>
                                <?php foreach ($availableDrivers as $d): ?>
                                    <option value="<?php echo $d['id']; ?>">
                                        <?php echo htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8'); ?> (Licence: <?php echo htmlspecialchars($d['license_category'], ENT_QUOTES, 'UTF-8'); ?>) - Safety Score: <?php echo $d['safety_score']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="driver_id" class="text-muted">Driver Assignment *</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: CARGO & DISPATCH STATUS -->
            <div class="wizard-step d-none" id="step-3-content">
                <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-box-seam-fill me-2"></i>Step 3: Cargo & Dispatch Status</h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="cargo_weight" name="cargo_weight" required placeholder="Cargo Weight (kg)">
                            <label for="cargo_weight" class="text-muted">Cargo Weight (kg) *</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="status" name="status" required>
                                <option value="draft" selected>Draft (Save only)</option>
                                <option value="dispatched">Dispatched (Lock vehicle & driver)</option>
                            </select>
                            <label for="status" class="text-muted">Dispatch Status *</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Form Actions -->
            <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary px-4 d-none" id="prevBtn" style="border-radius: 10px;">
                    <i class="bi bi-chevron-left me-2"></i>Previous
                </button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="resetBtn" style="border-radius: 10px;">Reset</button>
                    <button type="button" class="btn btn-primary px-4" id="nextBtn" style="border-radius: 10px;">
                        Next<i class="bi bi-chevron-right ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.transition-all {
    transition: all 0.3s ease-in-out;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let currentStep = 1;
    const totalSteps = 3;
    const form = document.getElementById("dispatchForm");
    const nextBtn = document.getElementById("nextBtn");
    const prevBtn = document.getElementById("prevBtn");
    const resetBtn = document.getElementById("resetBtn");
    const progress = document.getElementById("wizard-progress");

    function updateWizard() {
        // Toggle Step contents
        for (let i = 1; i <= totalSteps; i++) {
            const stepContent = document.getElementById(`step-${i}-content`);
            const indicator = document.getElementById(`step-indicator-${i}`);
            
            if (i === currentStep) {
                stepContent.classList.remove("d-none");
                indicator.classList.remove("btn-secondary", "btn-success");
                indicator.classList.add("btn-primary");
            } else if (i < currentStep) {
                stepContent.classList.add("d-none");
                indicator.classList.remove("btn-secondary", "btn-primary");
                indicator.classList.add("btn-success");
            } else {
                stepContent.classList.add("d-none");
                indicator.classList.remove("btn-primary", "btn-success");
                indicator.classList.add("btn-secondary");
            }
        }

        // Update progress bar
        const pct = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progress.style.width = `${pct}%`;

        // Toggle buttons visibility
        if (currentStep === 1) {
            prevBtn.classList.add("d-none");
        } else {
            prevBtn.classList.remove("d-none");
        }

        if (currentStep === totalSteps) {
            nextBtn.innerHTML = `Confirm & Dispatch <i class="bi bi-check-lg ms-2"></i>`;
            nextBtn.classList.remove("btn-primary");
            nextBtn.classList.add("btn-success");
        } else {
            nextBtn.innerHTML = `Next<i class="bi bi-chevron-right ms-2"></i>`;
            nextBtn.classList.remove("btn-success");
            nextBtn.classList.add("btn-primary");
        }
    }

    function validateStep(step) {
        const stepContent = document.getElementById(`step-${step}-content`);
        const inputs = stepContent.querySelectorAll("input, select");
        let valid = true;
        
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                valid = false;
                input.classList.add("is-invalid");
            } else {
                input.classList.remove("is-invalid");
            }
        });
        
        return valid;
    }

    nextBtn.addEventListener("click", function() {
        if (!validateStep(currentStep)) {
            form.reportValidity();
            return;
        }

        if (currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        } else {
            // Last step: Submit Form
            form.submit();
        }
    });

    prevBtn.addEventListener("click", function() {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    resetBtn.addEventListener("click", function() {
        form.reset();
        currentStep = 1;
        updateWizard();
        form.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    });

    // Update initial layout
    updateWizard();
});
</script>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
