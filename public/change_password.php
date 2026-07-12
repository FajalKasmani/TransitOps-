<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/classes/Database.php';
require_once __DIR__ . '/../api/classes/Auth.php';

use Api\Classes\Auth;
use Api\Classes\Database;

Auth::startSession();

// Check access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    try {
        if (!Auth::verifyCsrfToken($csrfToken)) {
            throw new Exception("CSRF verification failed. Request blocked.");
        }

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new Exception("All password fields are required.");
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception("New passwords do not match.");
        }

        if (strlen($newPassword) < 6) {
            throw new Exception("New password must be at least 6 characters long.");
        }

        // Verify current password
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $userHash = $stmt->fetchColumn();

        if (!$userHash || !password_verify($currentPassword, $userHash)) {
            throw new Exception("Current password is incorrect.");
        }

        // Change password
        if (Auth::changePassword($userId, $newPassword)) {
            $success = "Password updated successfully.";
        } else {
            throw new Exception("Failed to update password. Internal database error.");
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-2">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active text-light-theme" aria-current="page">Change Password</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme"><i class="bi bi-key-fill me-2 text-warning"></i>Security Settings</h3>
            <p class="text-muted small mb-0">Update your account credentials to keep your profile secure.</p>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm p-4 p-md-5">
                <h5 class="fw-bold mb-4 text-light-theme">Change Password</h5>
                <form method="POST" action="change_password.php" class="needs-validation" novalidate>
                    <?php echo Auth::getCsrfInput(); ?>

                    <!-- Current Password -->
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="current_password" name="current_password" required placeholder="Current Password">
                        <label for="current_password" class="text-muted">Current Password *</label>
                    </div>

                    <!-- New Password -->
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required placeholder="New Password">
                        <label for="new_password" class="text-muted">New Password (Min 6 chars) *</label>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required placeholder="Confirm New Password">
                        <label for="confirm_password" class="text-muted">Confirm New Password *</label>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="index.php" class="btn btn-outline-secondary" style="border-radius: 10px;">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="border-radius: 10px;"><i class="bi bi-shield-lock me-2"></i>Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
