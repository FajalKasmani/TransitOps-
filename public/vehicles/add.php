<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';
require_once __DIR__ . '/../../api/classes/Vehicle.php';

use Api\Classes\Auth;
use Api\Classes\Vehicle;

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
            'registration_number' => trim($_POST['registration_number'] ?? ''),
            'vehicle_name' => trim($_POST['vehicle_name'] ?? ''),
            'type' => $_POST['type'] ?? '',
            'max_load_capacity' => $_POST['max_load_capacity'] !== '' ? (float)$_POST['max_load_capacity'] : 0.00,
            'odometer' => $_POST['odometer'] !== '' ? (float)$_POST['odometer'] : 0.00,
            'acquisition_cost' => $_POST['acquisition_cost'] !== '' ? (float)$_POST['acquisition_cost'] : 0.00,
            'status' => $_POST['status'] ?? 'available',
            'region' => trim($_POST['region'] ?? '')
        ];

        try {
            Vehicle::create($data);
            header("Location: list.php?created=1");
            exit;
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    :root {
        --bg-dark-canvas: #0f172a; /* Slate 900 */
        --bg-dark-card: #1e293b;   /* Slate 800 */
        --border-dark: #334155;     /* Slate 700 */
        --text-primary: #f8fafc;    /* Slate 50 */
        --text-secondary: #94a3b8;  /* Slate 400 */
        
        --accent-gradient: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
        --input-focus-glow: rgba(59, 130, 246, 0.25);
    }

    /* Force dark theme aesthetics on container wrapper if parent template isn't dark */
    .dark-theme-wrapper {
        color: var(--text-primary);
    }
    
    .ui-card-dark {
        background-color: var(--bg-dark-card) !important;
        border: 1px solid var(--border-dark) !important;
        border-radius: 16px !important;
    }

    .form-section-title-dark {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #38bdf8; /* Sky 400 */
        font-weight: 700;
        border-bottom: 1px solid var(--border-dark);
        padding-bottom: 8px;
    }

    /* Custom Floating Inputs for Dark Environment */
    .ui-card-dark .form-floating > .form-control,
    .ui-card-dark .form-floating > .form-select {
        background-color: #111827 !important; /* Rich Dark Input */
        border: 1.5px solid var(--border-dark);
        color: var(--text-primary) !important;
        border-radius: 12px !important;
    }

    /* Target option dropdown text for cross-browser consistency */
    .ui-card-dark .form-select option {
        background-color: #111827;
        color: var(--text-primary);
    }

    /* Focused State fixes for placeholder text & labels */
    .ui-card-dark .form-floating > .form-control:focus,
    .ui-card-dark .form-floating > .form-select:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 4px var(--input-focus-glow) !important;
    }

    .ui-card-dark .form-floating > label {
        color: var(--text-secondary) !important;
    }

    /* Ensure dynamic floating labels adapt well over dark input backgrounds */
    .ui-card-dark .form-floating > .form-control:focus ~ label,
    .ui-card-dark .form-floating > .form-control:not(:placeholder-shown) ~ label,
    .ui-card-dark .form-floating > .form-select:focus ~ label,
    .ui-card-dark .form-floating > .form-select:not([value=""]) ~ label {
        color: #38bdf8 !important;
        transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
    }

    .custom-gradient-btn {
        background: var(--accent-gradient);
        border: none;
        color: #fff;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .custom-gradient-btn:hover {
        opacity: 0.95;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        color: #fff;
    }

    .back-btn-dark {
        background: #1e293b;
        border: 1px solid var(--border-dark) !important;
        color: var(--text-secondary) !important;
        border-radius: 10px;
        transition: all 0.2s;
    }

    .back-btn-dark:hover {
        background: #334155;
        color: var(--text-primary) !important;
    }

    .btn-reset-dark {
        background: transparent;
        border: 1px solid var(--border-dark);
        color: var(--text-secondary);
        border-radius: 12px;
        transition: background 0.2s;
    }

    .btn-reset-dark:hover {
        background: #334155;
        color: var(--text-primary);
    }
</style>

<div class="container py-4 dark-theme-wrapper" style="max-width: 960px;">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 pb-3 border-bottom border-secondary-subtle" style="border-color: var(--border-dark) !important;">
        <div>
            <h3 class="fw-bold tracking-tight text-white mb-1">Register New Vehicle</h3>
            <p class="small mb-0" style="color: var(--text-secondary);">Expand and track your fleet assets by deploying a new unit.</p>
        </div>
        <a href="list.php" class="btn btn-sm back-btn-dark px-3 py-2 d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Registry
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger bg-danger-subtle text-danger border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong class="d-block">Submission Failed</strong>
                <span class="small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card ui-card-dark shadow-lg p-4 p-md-5">
        <form method="POST" action="add.php" class="needs-validation" novalidate>
            <?php echo Auth::getCsrfInput(); ?>
            
            <div class="row g-4">
                <div class="col-12 mb-2">
                    <div class="form-section-title-dark"><i class="bi bi-card-text me-2"></i>Identity & Type</div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="registration_number" name="registration_number" required placeholder="e.g. TX-984-L">
                        <label for="registration_number">Registration Number *</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" required placeholder="e.g. Volvo FH16">
                        <label for="vehicle_name">Vehicle Name / Model *</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select type...</option>
                            <option value="car">🚗 Car</option>
                            <option value="van">🚐 Van</option>
                            <option value="truck">🚛 Truck</option>
                            <option value="motorcycle">🏍️ Motorcycle</option>
                        </select>
                        <label for="type">Vehicle Type *</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="max_load_capacity" name="max_load_capacity" required placeholder="e.g. 15000">
                        <label for="max_load_capacity">Max Capacity (kg) *</label>
                    </div>
                </div>

                <div class="col-12 mt-5 mb-2">
                    <div class="form-section-title-dark"><i class="bi bi-speedometer2 me-2"></i>Status & Metrics</div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="odometer" name="odometer" value="0.00" placeholder="e.g. 120">
                        <label for="odometer">Odometer Reading (km)</label>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" class="form-control" id="acquisition_cost" name="acquisition_cost" value="0.00" placeholder="e.g. 45000">
                        <label for="acquisition_cost">Acquisition Cost (₹)</label>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="status" name="status" required>
                            <option value="available" selected>🟢 Available</option>
                            <option value="on_trip">🟡 On Trip</option>
                            <option value="in_shop">🔴 In Shop</option>
                            <option value="retired">⚫ Retired</option>
                        </select>
                        <label for="status">Initial Status *</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="region" name="region" placeholder="e.g. West">
                        <label for="region">Operating Region / Zone</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5 pt-3 border-top" style="border-color: var(--border-dark) !important;">
                <button type="reset" class="btn btn-reset-dark px-4 py-2 fw-medium">Reset Form</button>
                <button type="submit" class="btn custom-gradient-btn px-5 py-2" style="border-radius: 12px;">
                    <i class="bi bi-plus-circle-fill me-2"></i>Save Vehicle
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>