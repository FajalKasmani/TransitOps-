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
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = "CSRF security verification failed. Request blocked.";
    } else {
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
        background-color: #111827 !important; /* Deep input well background */
        border: 1.5px solid var(--border-dark);
        color: var(--text-primary) !important;
        border-radius: 12px !important;
    }

    .ui-card-dark .form-select option {
        background-color: #111827;
        color: var(--text-primary);
    }

    /* Focus States */
    .ui-card-dark .form-floating > .form-control:focus,
    .ui-card-dark .form-floating > .form-select:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 4px var(--input-focus-glow) !important;
    }

    .ui-card-dark .form-floating > label {
        color: var(--text-secondary) !important;
    }

    /* Ensure label transitions cleanly with the custom background styling */
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
            <h3 class="fw-bold tracking-tight text-white mb-1"><i class="bi bi-person-plus me-2 text-info"></i>Register Driver</h3>
            <p class="small mb-0" style="color: var(--text-secondary);">Create a new personnel profile and manage operations clearance variables.</p>
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
                    <div class="form-section-title-dark"><i class="bi bi-person-badge me-2"></i>Identity & Licensing</div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. John Doe">
                        <label for="name">Driver Name *</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="license_number" name="license_number" required placeholder="e.g. DL-1234567-8">
                        <label for="license_number">License Number *</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="license_category" name="license_category" required placeholder="e.g. Class A CDL">
                        <label for="license_category">License Category *</label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date" required placeholder="Expiry Date">
                        <label for="license_expiry_date">License Expiry Date *</label>
                    </div>
                </div>

                <div class="col-12 mt-5 mb-2">
                    <div class="form-section-title-dark"><i class="bi bi-sliders me-2"></i>Operations & Contact</div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="number" step="0.01" min="0" max="5" class="form-control" id="safety_score" name="safety_score" value="5.00" placeholder="5.00">
                        <label for="safety_score">Safety Score (0.00 - 5.00)</label>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <select class="form-select" id="status" name="status" required>
                            <option value="available" selected>🟢 Available</option>
                            <option value="on_trip">🟡 On Trip</option>
                            <option value="off_duty">⚪ Off Duty</option>
                            <option value="suspended">🔴 Suspended</option>
                        </select>
                        <label for="status">Initial Status *</label>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="e.g. +1 (555) 123-4567">
                        <label for="contact_number">Contact Number</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="e.g. driver@transitops.com">
                        <label for="email">Email Address</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-5 pt-3 border-top" style="border-color: var(--border-dark) !important;">
                <button type="reset" class="btn btn-reset-dark px-4 py-2 fw-medium">Reset Form</button>
                <button type="submit" class="btn custom-gradient-btn px-5 py-2" style="border-radius: 12px;">
                    <i class="bi bi-person-check-fill me-2"></i>Save Driver Profile
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>