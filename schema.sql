-- Drop existing tables in reverse dependency order
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS action_logs;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS vehicle_documents;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS fuel_logs;
DROP TABLE IF EXISTS maintenance_logs;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. vehicles table
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    vehicle_name VARCHAR(100) NOT NULL,
    type ENUM('car','van','truck','motorcycle') NOT NULL,
    max_load_capacity DECIMAL(10,2) NOT NULL,
    odometer DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    acquisition_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('available','on_trip','in_shop','retired') NOT NULL DEFAULT 'available',
    region VARCHAR(50) NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_vehicle_load CHECK (max_load_capacity > 0),
    CONSTRAINT chk_vehicle_odometer CHECK (odometer >= 0),
    CONSTRAINT chk_vehicle_cost CHECK (acquisition_cost >= 0),
    INDEX idx_vehicle_status (status),
    INDEX idx_vehicle_region (region),
    INDEX idx_vehicle_lookup (is_deleted, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. drivers table
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_number VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    license_category VARCHAR(10) NOT NULL,
    license_expiry_date DATE NOT NULL,
    contact_number VARCHAR(20) NULL,
    safety_score DECIMAL(3,2) DEFAULT 5.00,
    status ENUM('available','on_trip','off_duty','suspended') NOT NULL DEFAULT 'available',
    email VARCHAR(255) NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_driver_safety CHECK (safety_score >= 0.00 AND safety_score <= 5.00),
    INDEX idx_driver_expiry (license_expiry_date),
    INDEX idx_driver_status (status),
    INDEX idx_driver_lookup (is_deleted, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. trips table
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    source VARCHAR(200) NOT NULL,
    destination VARCHAR(200) NOT NULL,
    cargo_weight DECIMAL(10,2) NOT NULL,
    planned_distance DECIMAL(12,2) NOT NULL,
    status ENUM('draft','dispatched','completed','cancelled') NOT NULL DEFAULT 'draft',
    actual_distance DECIMAL(12,2) NULL DEFAULT NULL,
    start_time DATETIME NULL DEFAULT NULL,
    end_time DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trips_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_trips_driver FOREIGN KEY (driver_id) REFERENCES drivers (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_trip_cargo CHECK (cargo_weight > 0),
    CONSTRAINT chk_trip_planned_dist CHECK (planned_distance > 0),
    CONSTRAINT chk_trip_actual_dist CHECK (actual_distance IS NULL OR actual_distance >= 0),
    INDEX idx_trip_vehicle (vehicle_id),
    INDEX idx_trip_driver (driver_id),
    INDEX idx_trip_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. maintenance_logs table
CREATE TABLE maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    date DATE NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_maintenance_cost CHECK (cost >= 0),
    INDEX idx_maint_vehicle (vehicle_id),
    INDEX idx_maint_status (status),
    INDEX idx_maint_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. fuel_logs table
CREATE TABLE fuel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    liters DECIMAL(12,2) NOT NULL,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fuel_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_fuel_liters CHECK (liters > 0),
    CONSTRAINT chk_fuel_cost CHECK (cost >= 0),
    INDEX idx_fuel_vehicle (vehicle_id),
    INDEX idx_fuel_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    type ENUM('toll','maintenance','other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expense_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_expense_cost CHECK (cost >= 0),
    INDEX idx_expense_vehicle (vehicle_id),
    INDEX idx_expense_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. vehicle_documents table
CREATE TABLE vehicle_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    expiry_date DATE NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_doc_vehicle (vehicle_id),
    INDEX idx_doc_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. action_logs table
CREATE TABLE action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    entity VARCHAR(30) NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    details TEXT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_entity (entity, entity_id),
    INDEX idx_audit_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. login_attempts table
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_lookup (email, ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for roles
INSERT INTO roles (name, description) VALUES
('admin', 'Administrator with full system privileges'),
('fleet_manager', 'Fleet Manager responsible for vehicles and trips'),
('driver', 'Driver assigned to vehicle trips'),
('safety_officer', 'Safety Officer responsible for driver safety reviews and audits'),
('financial_analyst', 'Financial Analyst responsible for tracking costs and expenses');

-- Seed data for default admin user
INSERT INTO users (email, password_hash, role_id, full_name, is_active) VALUES
('admin@transitops.com', '$2y$10$7R1eO9kUX1.o/kXF9zUj1OqQoG4pP31.wHlR2fEexmZ3s7dGjKm6K', (SELECT id FROM roles WHERE name = 'admin'), 'System Administrator', 1);
