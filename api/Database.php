<?php
declare(strict_types=1);

namespace Api;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Thread-safe PDO Database Connection Singleton for TransitOps.
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
     * Private clone method to prevent cloning of the singleton instance.
     */
    private function __clone() {
        // Disallow cloning
    }

    /**
     * Public wakeup method to prevent unserializing of the singleton instance.
     * Must be public as per PHP specifications, but throws an exception.
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
            // Locate configuration file dynamically relative to this file
            $configPath = dirname(__DIR__) . '/config/config.php';

            if (!file_exists($configPath)) {
                throw new RuntimeException("Configuration file not found at: " . $configPath);
            }

            // Securely load the configuration array
            $config = require $configPath;

            if (!is_array($config)) {
                throw new RuntimeException("Invalid configuration file format. Must return an array.");
            }

            // Validate all required keys
            $requiredKeys = ['host', 'dbname', 'username', 'password'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $config)) {
                    throw new RuntimeException("Missing required configuration key: '" . $key . "'");
                }
            }

            // Build secure MySQL Data Source Name (DSN)
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=utf8mb4",
                $config['host'],
                $config['dbname']
            );

            // Establish thread-safe connection using PDO
            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
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
