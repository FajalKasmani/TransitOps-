<?php
declare(strict_types=1);

namespace Api\Classes;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Thread-safe PDO Database Connection Singleton for TransitOps.
 * Parses root-level config.php.
 */
class Database {
    /**
     * @var PDO|null The single PDO instance
     */
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        // Disallow direct instantiation
    }

    /**
     * Private clone method to prevent cloning.
     */
    private function __clone() {
        // Disallow cloning
    }

    /**
     * Public wakeup method to prevent unserializing.
     */
    public function __wakeup() {
        throw new RuntimeException("Cannot unserialize a singleton instance.");
    }

    /**
     * Get the persistent, shared PDO connection link.
     *
     * @return PDO The active PDO instance
     * @throws RuntimeException If configuration is invalid or connection fails
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            // Locate config.php in the root directory relative to this file
            $configPath = dirname(__DIR__, 2) . '/config.php';

            if (!file_exists($configPath)) {
                throw new RuntimeException("Database configuration file not found at: " . $configPath);
            }

            // Securely load the configuration
            $config = require $configPath;

            // Extract connection details (checking both constants and configuration array)
            $host = defined('DB_HOST') ? DB_HOST : ($config['host'] ?? null);
            $dbname = defined('DB_NAME') ? DB_NAME : ($config['dbname'] ?? null);
            $username = defined('DB_USER') ? DB_USER : ($config['username'] ?? null);
            $password = defined('DB_PASS') ? DB_PASS : ($config['password'] ?? null);

            if (!$host || !$dbname || $username === null) {
                throw new RuntimeException("Invalid database configuration values. Host, Database Name, and Username are required.");
            }

            // Build secure MySQL Data Source Name (DSN)
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=utf8mb4",
                $host,
                $dbname
            );

            // Establish thread-safe connection using PDO
            try {
                self::$instance = new PDO(
                    $dsn,
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ]
                );
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return self::$instance;
    }
}
