-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2026 at 07:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `transitops`
--
CREATE DATABASE IF NOT EXISTS `transitops` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `transitops`;

-- --------------------------------------------------------

--
-- Table structure for table `action_logs`
--

CREATE TABLE `action_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `entity` varchar(30) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `action_logs`
--

INSERT INTO `action_logs` (`id`, `user_id`, `entity`, `entity_id`, `action`, `details`, `timestamp`) VALUES
(1, NULL, 'vehicles', 1, 'CREATE', 'Registered vehicle \'Tata Ultra Truck (AHD)\' [GJ-01-AZ-9988]', '2026-07-12 05:49:49'),
(2, NULL, 'vehicles', 2, 'CREATE', 'Registered vehicle \'Mahindra Supro Van (RJK)\' [GJ-03-XX-1234]', '2026-07-12 05:49:49'),
(3, NULL, 'vehicles', 3, 'CREATE', 'Registered vehicle \'Ashok Leyland Dost (SRT)\' [GJ-05-YY-5678]', '2026-07-12 05:49:49'),
(4, NULL, 'vehicles', 4, 'CREATE', 'Registered vehicle \'Maruti Super Carry (BDQ)\' [GJ-06-ZZ-4444]', '2026-07-12 05:49:49'),
(5, NULL, 'vehicles', 5, 'CREATE', 'Registered vehicle \'Hero Super Splendor (GNR)\' [GJ-27-BC-7777]', '2026-07-12 05:49:49'),
(6, NULL, 'drivers', 1, 'CREATE', 'Registered driver \'Vikram Patel\' [GJ01-20230000001]', '2026-07-12 05:49:49'),
(7, NULL, 'drivers', 2, 'CREATE', 'Registered driver \'Hardik Shah\' [GJ03-20230000002]', '2026-07-12 05:49:49'),
(8, NULL, 'drivers', 3, 'CREATE', 'Registered driver \'Jignesh Mehta\' [GJ05-20230000003]', '2026-07-12 05:49:49'),
(9, NULL, 'drivers', 4, 'CREATE', 'Registered driver \'Kirti Vyas\' [GJ06-20230000004]', '2026-07-12 05:49:49'),
(10, NULL, 'drivers', 5, 'CREATE', 'Registered driver \'Ramesh Savaliya\' [GJ27-20230000005]', '2026-07-12 05:49:49'),
(11, NULL, 'trips', 1, 'CREATE', 'Dispatched trip #1 from Ahmedabad Depot 1 to Surat Industrial Hub', '2026-07-12 05:49:50'),
(12, NULL, 'trips', 1, 'UPDATE', 'Updated trip status to \'completed\'', '2026-07-12 05:49:50'),
(13, NULL, 'trips', 2, 'CREATE', 'Dispatched trip #2 from Rajkot GIDC Depot to Jamnagar Port Warehouse', '2026-07-12 05:49:50'),
(14, NULL, 'trips', 2, 'UPDATE', 'Updated trip status to \'completed\'', '2026-07-12 05:49:50'),
(15, NULL, 'trips', 3, 'CREATE', 'Dispatched trip #3 from Vadodara Fulfillment Center to Gandhinagar GIDC Center', '2026-07-12 05:49:50'),
(16, NULL, 'maintenance_logs', 1, 'CREATE', 'Logged maintenance record for vehicle #1', '2026-07-12 05:49:50'),
(17, NULL, 'maintenance_logs', 2, 'CREATE', 'Logged maintenance record for vehicle #4', '2026-07-12 05:49:50'),
(18, NULL, 'fuel_logs', 1, 'CREATE', 'Logged fuel purchase of 60 liters for vehicle #1', '2026-07-12 05:49:50'),
(19, NULL, 'fuel_logs', 2, 'CREATE', 'Logged fuel purchase of 15 liters for vehicle #2', '2026-07-12 05:49:50'),
(20, NULL, 'fuel_logs', 3, 'CREATE', 'Logged fuel purchase of 50 liters for vehicle #3', '2026-07-12 05:49:50'),
(21, NULL, 'expenses', 1, 'CREATE', 'Logged expense type \'toll\' for vehicle #1', '2026-07-12 05:49:50'),
(22, NULL, 'expenses', 2, 'CREATE', 'Logged expense type \'toll\' for vehicle #2', '2026-07-12 05:49:50'),
(23, NULL, 'expenses', 3, 'CREATE', 'Logged expense type \'other\' for vehicle #3', '2026-07-12 05:49:50'),
(24, 1, 'users', 1, 'LOGIN', 'User logged in successfully', '2026-07-12 05:51:50');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `license_number` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `license_category` varchar(10) NOT NULL,
  `license_expiry_date` date NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `safety_score` decimal(3,2) DEFAULT 5.00,
  `status` enum('available','on_trip','off_duty','suspended') NOT NULL DEFAULT 'available',
  `email` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `license_number`, `name`, `license_category`, `license_expiry_date`, `contact_number`, `safety_score`, `status`, `email`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'GJ01-20230000001', 'Vikram Patel', 'Class A', '2028-11-20', '+91 98765 01001', 4.90, 'available', 'vikram@transitops.com', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:50'),
(2, 'GJ03-20230000002', 'Hardik Shah', 'Class B', '2029-04-15', '+91 87654 02002', 4.25, 'available', 'hardik@transitops.com', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:50'),
(3, 'GJ05-20230000003', 'Jignesh Mehta', 'Class A', '2027-08-30', '+91 76543 03003', 3.80, 'on_trip', 'jignesh@transitops.com', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:50'),
(4, 'GJ06-20230000004', 'Kirti Vyas', 'Class C', '2026-07-19', '+91 65432 04004', 4.75, 'available', 'kirti@transitops.com', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:49'),
(5, 'GJ27-20230000005', 'Ramesh Savaliya', 'Class A', '2030-01-01', '+91 54321 05005', 2.10, 'suspended', 'ramesh@transitops.com', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `type` enum('toll','maintenance','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `vehicle_id`, `type`, `description`, `cost`, `date`, `created_at`) VALUES
(1, 1, 'toll', 'Ahmedabad-Vadodara Expressway Toll Pay', 320.00, '2026-07-01', '2026-07-12 05:49:50'),
(2, 2, 'toll', 'Rajkot-Jamnagar Highway Toll Plaza', 90.00, '2026-07-05', '2026-07-12 05:49:50'),
(3, 3, 'other', 'Gujarat State Inter-district Permit fee', 1200.00, '2026-07-08', '2026-07-12 05:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_logs`
--

CREATE TABLE `fuel_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `liters` decimal(12,2) NOT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `fuel_logs`
--

INSERT INTO `fuel_logs` (`id`, `vehicle_id`, `liters`, `cost`, `date`, `created_at`) VALUES
(1, 1, 60.00, 6100.00, '2026-07-01', '2026-07-12 05:49:50'),
(2, 2, 15.00, 1550.00, '2026-07-05', '2026-07-12 05:49:50'),
(3, 3, 50.00, 5100.00, '2026-07-08', '2026-07-12 05:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `vehicle_id`, `description`, `cost`, `date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Tata Ultra cabin fan and brake pad repair at Ahmedabad GIDC Workshop', 1250.00, '2026-06-01', 'closed', 'Replaced front pads', '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(2, 4, 'Radiator overhaul at Vadodara Maruti service center', 4200.00, '2026-07-10', 'open', 'Awaiting coolants shipment', '2026-07-12 05:49:50', '2026-07-12 05:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator with full system privileges'),
(2, 'fleet_manager', 'Fleet Manager responsible for vehicles and trips'),
(3, 'driver', 'Driver assigned to vehicle trips'),
(4, 'safety_officer', 'Safety Officer responsible for driver safety reviews and audits'),
(5, 'financial_analyst', 'Financial Analyst responsible for tracking costs and expenses');

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `source` varchar(200) NOT NULL,
  `destination` varchar(200) NOT NULL,
  `cargo_weight` decimal(10,2) NOT NULL,
  `planned_distance` decimal(12,2) NOT NULL,
  `status` enum('draft','dispatched','completed','cancelled') NOT NULL DEFAULT 'draft',
  `actual_distance` decimal(12,2) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `vehicle_id`, `driver_id`, `source`, `destination`, `cargo_weight`, `planned_distance`, `status`, `actual_distance`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Ahmedabad Depot 1', 'Surat Industrial Hub', 8000.00, 265.00, 'completed', 268.50, '2026-07-12 07:49:50', '2026-07-12 07:49:50', '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(2, 2, 2, 'Rajkot GIDC Depot', 'Jamnagar Port Warehouse', 1800.00, 92.00, 'completed', 94.20, '2026-07-12 07:49:50', '2026-07-12 07:49:50', '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(3, 3, 3, 'Vadodara Fulfillment Center', 'Gandhinagar GIDC Center', 4500.00, 115.00, 'dispatched', NULL, '2026-07-12 07:49:50', NULL, '2026-07-12 05:49:50', '2026-07-12 05:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role_id`, `full_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@transitops.com', '$2y$10$58W28dEnlZkbZi8uhD8vQOpoh7kJ4yR9osVHZJvEcLVlSaBn7MI4.', 1, 'System Administrator', 1, '2026-07-12 05:31:16', '2026-07-12 05:31:16'),
(2, 'fleet_manager@transitops.com', '$2y$10$.UmkpKVP7B7NQUDUh5YHH.GGVHGDCS9VPMTdJETIFQeazmJKch8c2', 2, 'Hardik Mehta', 1, '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(3, 'safety_officer@transitops.com', '$2y$10$.UmkpKVP7B7NQUDUh5YHH.GGVHGDCS9VPMTdJETIFQeazmJKch8c2', 4, 'Jignesh Shah', 1, '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(4, 'financial_analyst@transitops.com', '$2y$10$.UmkpKVP7B7NQUDUh5YHH.GGVHGDCS9VPMTdJETIFQeazmJKch8c2', 5, 'Kirti Vyas', 1, '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(5, 'vikram@transitops.com', '$2y$10$.UmkpKVP7B7NQUDUh5YHH.GGVHGDCS9VPMTdJETIFQeazmJKch8c2', 3, 'Vikram Patel', 1, '2026-07-12 05:49:50', '2026-07-12 05:49:50'),
(6, 'hardik@transitops.com', '$2y$10$.UmkpKVP7B7NQUDUh5YHH.GGVHGDCS9VPMTdJETIFQeazmJKch8c2', 3, 'Hardik Shah', 1, '2026-07-12 05:49:50', '2026-07-12 05:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(20) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `type` enum('car','van','truck','motorcycle') NOT NULL,
  `max_load_capacity` decimal(10,2) NOT NULL,
  `odometer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `acquisition_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('available','on_trip','in_shop','retired') NOT NULL DEFAULT 'available',
  `region` varchar(50) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `registration_number`, `vehicle_name`, `type`, `max_load_capacity`, `odometer`, `acquisition_cost`, `status`, `region`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 'GJ-01-AZ-9988', 'Tata Ultra Truck (AHD)', 'truck', 12000.00, 14500.00, 1800000.00, 'available', 'Gujarat - Ahmedabad', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:50'),
(2, 'GJ-03-XX-1234', 'Mahindra Supro Van (RJK)', 'van', 2500.00, 8400.00, 650000.00, 'available', 'Gujarat - Rajkot', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:50'),
(3, 'GJ-05-YY-5678', 'Ashok Leyland Dost (SRT)', 'truck', 5000.00, 22000.50, 950000.00, 'on_trip', 'Gujarat - Surat', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:50'),
(4, 'GJ-06-ZZ-4444', 'Maruti Super Carry (BDQ)', 'van', 1500.00, 12500.00, 520000.00, 'in_shop', 'Gujarat - Vadodara', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:49'),
(5, 'GJ-27-BC-7777', 'Hero Super Splendor (GNR)', 'motorcycle', 150.00, 1200.00, 95000.00, 'available', 'Gujarat - Gandhinagar', 0, '2026-07-12 05:49:49', '2026-07-12 05:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_documents`
--

CREATE TABLE `vehicle_documents` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `action_logs`
--
ALTER TABLE `action_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_entity` (`entity`,`entity_id`),
  ADD KEY `idx_audit_timestamp` (`timestamp`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_driver_expiry` (`license_expiry_date`),
  ADD KEY `idx_driver_status` (`status`),
  ADD KEY `idx_driver_lookup` (`is_deleted`,`status`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expense_vehicle` (`vehicle_id`),
  ADD KEY `idx_expense_date` (`date`);

--
-- Indexes for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fuel_vehicle` (`vehicle_id`),
  ADD KEY `idx_fuel_date` (`date`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_lookup` (`email`,`ip_address`,`attempt_time`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_maint_vehicle` (`vehicle_id`),
  ADD KEY `idx_maint_status` (`status`),
  ADD KEY `idx_maint_date` (`date`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trip_vehicle` (`vehicle_id`),
  ADD KEY `idx_trip_driver` (`driver_id`),
  ADD KEY `idx_trip_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_role` (`role_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `idx_vehicle_status` (`status`),
  ADD KEY `idx_vehicle_region` (`region`),
  ADD KEY `idx_vehicle_lookup` (`is_deleted`,`status`);

--
-- Indexes for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_vehicle` (`vehicle_id`),
  ADD KEY `idx_doc_expiry` (`expiry_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_logs`
--
ALTER TABLE `action_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `action_logs`
--
ALTER TABLE `action_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD CONSTRAINT `fk_fuel_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `fk_maintenance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `fk_trips_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trips_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD CONSTRAINT `fk_doc_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
