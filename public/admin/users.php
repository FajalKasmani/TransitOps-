<?php
declare(strict_types=1);

require_once __DIR__ . '/../../api/classes/Database.php';
require_once __DIR__ . '/../../api/classes/Auth.php';

use Api\Classes\Auth;
use Api\Classes\Database;

Auth::startSession();

// Restrict access strictly to the System Administrator
if (!Auth::checkAccess(['admin'])) {
    header("Location: ../index.php");
    exit;
}

$error = null;
$success = null;

$pdo = Database::getInstance();

// Handle Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    try {
        if (!Auth::verifyCsrfToken($csrfToken)) {
            throw new Exception("CSRF validation failed. Action aborted.");
        }

        if ($action === 'add') {
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $roleId = (int)($_POST['role_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($email) || empty($fullName) || empty($roleId) || empty($password)) {
                throw new Exception("All fields are required to add a user.");
            }

            if ($password !== $confirmPassword) {
                throw new Exception("Passwords do not match.");
            }

            if (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception("User email already exists in system.");
            }

            // Hash password and insert
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("
                INSERT INTO users (email, password_hash, role_id, full_name, is_active) 
                VALUES (:email, :hash, :role_id, :full_name, 1)
            ");
            $ins->execute([
                'email' => $email,
                'hash' => $hash,
                'role_id' => $roleId,
                'full_name' => $fullName
            ]);

            $success = "User registered successfully.";

        } elseif ($action === 'reset_password') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("Password fields cannot be empty.");
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception("Passwords do not match.");
            }

            if (strlen($newPassword) < 6) {
                throw new Exception("Password must be at least 6 characters.");
            }

            // Update password
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $stmt->execute(['hash' => $hash, 'id' => $userId]);

            $success = "Password reset successfully.";

        } elseif ($action === 'toggle_status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $currentStatus = (int)($_POST['current_status'] ?? 1);
            $newStatus = $currentStatus === 1 ? 0 : 1;

            if ($userId === (int)$_SESSION['user_id']) {
                throw new Exception("You cannot deactivate your own active session account.");
            }

            $stmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $userId]);

            $success = "User status updated successfully.";
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch users
$usersQuery = $pdo->query("
    SELECT u.*, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id ASC
");
$usersList = $usersQuery->fetchAll();

// Fetch roles for dropdown
$rolesQuery = $pdo->query("SELECT * FROM roles ORDER BY name ASC");
$rolesList = $rolesQuery->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-light-theme"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>User Management</h3>
            <p class="text-muted small mb-0">System Administrators can manage users, assign access roles, and reset credentials.</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i> Add User
        </button>
    </div>

    <!-- Alert Messaging -->
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

    <!-- User List Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td><strong>#<?php echo $u['id']; ?></strong></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><span class="badge bg-secondary"><?php echo strtoupper(str_replace('_', ' ', $u['role_name'])); ?></span></td>
                            <td>
                                <?php if ((int)$u['is_active'] === 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Deactivated</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?php echo $u['created_at']; ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- Reset Password Trigger -->
                                    <button class="btn btn-sm btn-outline-warning" 
                                            title="Reset Password"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#resetPasswordModal" 
                                            data-user-id="<?php echo $u['id']; ?>"
                                            data-user-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="bi bi-key-fill"></i> Reset
                                    </button>

                                    <!-- Status Toggle Form -->
                                    <form method="POST" action="users.php" class="d-inline">
                                        <?php echo Auth::getCsrfInput(); ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                                        <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                            <button type="submit" class="btn btn-sm <?php echo (int)$u['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success'; ?>" onclick="return confirm('Toggle status for this user?');">
                                                <i class="bi <?php echo (int)$u['is_active'] === 1 ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot deactivate self">
                                                <i class="bi bi-person-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1e293b; color: #f8fafc; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px;">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title fw-bold" id="addUserModalLabel">Register New User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="users.php" class="needs-validation" novalidate>
                <div class="modal-body p-4">
                    <?php echo Auth::getCsrfInput(); ?>
                    <input type="hidden" name="action" value="add">

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control bg-transparent text-white" id="modal_full_name" name="full_name" required placeholder="Full Name">
                        <label for="modal_full_name" class="text-muted">Full Name *</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control bg-transparent text-white" id="modal_email" name="email" required placeholder="Email Address">
                        <label for="modal_email" class="text-muted">Email Address *</label>
                    </div>

                    <div class="form-floating mb-3">
                        <select class="form-select bg-transparent text-white" id="modal_role_id" name="role_id" required style="color-scheme: dark;">
                            <option value="" style="background-color: #1e293b;">Choose Role...</option>
                            <?php foreach ($rolesList as $role): ?>
                                <option value="<?php echo $role['id']; ?>" style="background-color: #1e293b;"><?php echo strtoupper(str_replace('_', ' ', $role['name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="modal_role_id" class="text-muted">Access Role *</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control bg-transparent text-white" id="modal_password" name="password" minlength="6" required placeholder="Password">
                        <label for="modal_password" class="text-muted">Temporary Password (Min 6 chars) *</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control bg-transparent text-white" id="modal_confirm_password" name="confirm_password" minlength="6" required placeholder="Confirm Password">
                        <label for="modal_confirm_password" class="text-muted">Confirm Password *</label>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 10px;">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #1e293b; color: #f8fafc; border: 1px solid rgba(255,255,255,0.05); border-radius: 16px;">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">Reset Credentials</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="users.php" class="needs-validation" novalidate>
                <div class="modal-body p-4">
                    <?php echo Auth::getCsrfInput(); ?>
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="reset_user_id" name="user_id" value="">

                    <div class="mb-3">
                        <span class="text-muted small">Account:</span>
                        <div class="fw-bold" id="reset_user_email_display"></div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control bg-transparent text-white" id="reset_new_password" name="new_password" minlength="6" required placeholder="New Password">
                        <label for="reset_new_password" class="text-muted">New Password *</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control bg-transparent text-white" id="reset_confirm_password" name="confirm_password" minlength="6" required placeholder="Confirm New Password">
                        <label for="reset_confirm_password" class="text-muted">Confirm New Password *</label>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" style="border-radius: 10px;">Override Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Fill data in reset modal dynamically
    const resetModal = document.getElementById('resetPasswordModal');
    resetModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        const userEmail = button.getAttribute('data-user-email');
        
        resetModal.querySelector('#reset_user_id').value = userId;
        resetModal.querySelector('#reset_user_email_display').textContent = userEmail;
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
