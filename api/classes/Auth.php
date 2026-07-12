<?php
declare(strict_types=1);

namespace Api\Classes;

use RuntimeException;
use PDO;

/**
 * Robust authentication and RBAC authorization class.
 */
class Auth {
    /**
     * Start the PHP session securely if not already started.
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', '1');
            }
            
            session_start();
        }
    }

    /**
     * Authenticates a user against the database and stores details in session.
     *
     * @param string $email
     * @param string $password
     * @return bool True if login succeeds, false otherwise
     */
    public static function login(string $email, string $password): bool {
        self::startSession();

        try {
            $pdo = Database::getInstance();
        } catch (RuntimeException $e) {
            return false;
        }

        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent Session Fixation
            session_regenerate_id(true);

            // Store user details in session
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = (int)$user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['last_activity'] = time();

            // Log the login event to action_logs
            try {
                $auditStmt = $pdo->prepare("
                    INSERT INTO action_logs (user_id, entity, entity_id, action, details) 
                    VALUES (:user_id, 'users', :entity_id, 'LOGIN', 'User logged in successfully')
                ");
                $auditStmt->execute([
                    'user_id' => (int)$user['id'],
                    'entity_id' => (int)$user['id']
                ]);
            } catch (\Exception $e) {
                // Fail silently for audit logs during login
            }

            return true;
        }

        return false;
    }

    /**
     * Logs out the user, audits the action, and destroys the session.
     */
    public static function logout(): void {
        self::startSession();

        if (isset($_SESSION['user_id'])) {
            try {
                $pdo = Database::getInstance();
                $auditStmt = $pdo->prepare("
                    INSERT INTO action_logs (user_id, entity, entity_id, action, details) 
                    VALUES (:user_id, 'users', :entity_id, 'LOGOUT', 'User logged out')
                ");
                $auditStmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'entity_id' => $_SESSION['user_id']
                ]);
            } catch (\Exception $e) {
                // Fail silently for audit logs during logout
            }
        }

        // Unset all session variables
        $_SESSION = [];

        // Delete session cookie if active
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }

    /**
     * Checks if the logged-in user belongs to one of the allowed roles.
     *
     * @param array $allowed_roles Array of role names permitted to access (e.g. ['admin', 'fleet_manager'])
     * @return bool True if permitted, false otherwise
     */
    public static function checkAccess(array $allowed_roles): bool {
        self::startSession();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name'])) {
            return false;
        }

        return in_array($_SESSION['role_name'], $allowed_roles, true);
    }

    /**
     * Enforces the session lifetime timeout (e.g., 30 minutes).
     *
     * @param int $timeoutSeconds Timeout limit in seconds (default is 1800)
     * @return bool True if the session is still active, false if it has timed out
     */
    public static function enforceTimeout(int $timeoutSeconds = 1800): bool {
        self::startSession();

        if (isset($_SESSION['user_id'])) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutSeconds)) {
                self::logout();
                return false;
            }
            // Update last activity timestamp
            $_SESSION['last_activity'] = time();
        }

        return true;
    }
}
