-- ====================================================================
-- CSE 391: Programming for the Internet - Assignment 3
-- Project: Car Workshop Online Appointment System
-- Database Schema & Initial Data
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `car_workshop_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `car_workshop_db`;

-- Drop tables if resetting
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `mechanics`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------
-- Table 1: Mechanics
-- Holds senior workshop mechanics and their maximum daily limit (4)
-- --------------------------------------------------------------------
CREATE TABLE `mechanics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `specialty` VARCHAR(150) NOT NULL,
  `experience_years` INT NOT NULL DEFAULT 10,
  `phone` VARCHAR(25) NOT NULL,
  `avatar_color` VARCHAR(20) NOT NULL DEFAULT '#2563eb',
  `max_daily_capacity` INT NOT NULL DEFAULT 4,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table 2: Appointments
-- Stores client booking requests, car details, date and assigned mechanic
-- --------------------------------------------------------------------
CREATE TABLE `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_name` VARCHAR(100) NOT NULL,
  `client_address` TEXT NOT NULL,
  `client_phone` VARCHAR(30) NOT NULL,
  `car_license_no` VARCHAR(50) NOT NULL,
  `car_engine_no` VARCHAR(50) NOT NULL,
  `appointment_date` DATE NOT NULL,
  `mechanic_id` INT NOT NULL,
  `status` ENUM('confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'confirmed',
  `admin_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_appointment_mechanic` 
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX `idx_date_mechanic` (`appointment_date`, `mechanic_id`),
  INDEX `idx_license_date` (`car_license_no`, `appointment_date`),
  INDEX `idx_phone_date` (`client_phone`, `appointment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Table 3: Admins
-- Store workshop manager/admin credentials
-- --------------------------------------------------------------------
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Seed Data: 5 Senior Mechanics
-- --------------------------------------------------------------------
INSERT INTO `mechanics` (`id`, `name`, `specialty`, `experience_years`, `phone`, `avatar_color`, `max_daily_capacity`) VALUES
(1, 'Robert "Bob" Vance', 'Senior Engine Diagnostic & Powertrain Tuning', 15, '01711001122', '#1e40af', 4),
(2, 'David Miller', 'Master Transmission & Gearbox Overhaul', 13, '01811223344', '#047857', 4),
(3, 'Carlos Rodriguez', 'Auto Electrical, Hybrid Systems & ECU Programming', 14, '01911334455', '#b45309', 4),
(4, 'Alex Chen', 'High-Performance Brakes, Suspension & Steering', 11, '01611445566', '#7c3aed', 4),
(5, 'Marcus Williams', 'Complete Vehicle Inspection & AC Climate Systems', 16, '01511556677', '#c026d3', 4);

-- --------------------------------------------------------------------
-- Seed Data: Admin User
-- Default credentials:
-- Username: admin
-- Password: admin123
-- --------------------------------------------------------------------
INSERT INTO `admins` (`username`, `password_hash`, `full_name`, `email`) VALUES
('admin', '$2y$10$wE0vj1aOq8u61vWsmg2jce2GZJvK.dYt5n/i3M9WdZ5X9O4OaL0n.', 'Workshop Manager', 'manager@workshop.com');
-- Password hash corresponds to 'admin123'

