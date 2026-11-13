 -- =====================================================
-- Squeaky Clean Washing Bay — Full MySQL Schema + Seeds
-- =====================================================

CREATE DATABASE IF NOT EXISTS squeaky_clean_washing_bay_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE squeaky_clean_washing_bay_db;

-- -----------------------------------------------------
-- Users (manager / attendant login)
-- Demo passwords are both: password
-- user_status: inactive attendants cannot log in (manager-controlled)
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_role ENUM('manager', 'attendant') NOT NULL,
    user_status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
);

-- -----------------------------------------------------
-- Washers (staff who clean vehicles)
-- -----------------------------------------------------
CREATE TABLE washers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    washer_name VARCHAR(100) NOT NULL,
    washer_status ENUM('active', 'inactive') DEFAULT 'active'
);

-- -----------------------------------------------------
-- Bays (4 physical washing bays)
-- -----------------------------------------------------
CREATE TABLE bays (
    id INT PRIMARY KEY,
    bay_name VARCHAR(10) NOT NULL,
    bay_status ENUM('empty', 'occupied') DEFAULT 'empty'
);

-- -----------------------------------------------------
-- Services (packages with tiered pricing by car type, GHS)
-- -----------------------------------------------------
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL,
    price_sedan DECIMAL(10,2) NOT NULL,
    price_suv DECIMAL(10,2) NOT NULL,
    price_van DECIMAL(10,2) NOT NULL
);

-- -----------------------------------------------------
-- Vehicles (intake / job records)
-- -----------------------------------------------------
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_plate VARCHAR(20) NOT NULL,
    car_type ENUM('Sedan', 'SUV', 'Van') NOT NULL,
    vehicle_status ENUM('pending', 'washing', 'completed') DEFAULT 'pending',
    assigned_bay_id INT NULL,
    assigned_washer_id INT NULL,
    checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_email VARCHAR(100) NULL,
    customer_phone VARCHAR(20) NULL,
    FOREIGN KEY (assigned_bay_id) REFERENCES bays(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_washer_id) REFERENCES washers(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- Transactions (billing & payment — feeds manager revenue)
-- payment_status: pending -> paid when Cash is recorded or Paystack MoMo succeeds
-- paystack_reference: set when a MoMo (Paystack) charge is started
-- -----------------------------------------------------
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_method ENUM('cash', 'momo', 'none') DEFAULT 'none',
    paystack_reference VARCHAR(100) NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Machinery (add / remove / toggle Active vs Under Repair in app)
-- -----------------------------------------------------
CREATE TABLE machinery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    machine_name VARCHAR(100) NOT NULL,
    machinery_status ENUM('active', 'under repair') DEFAULT 'active'
);

-- =====================================================
-- SEED DATA
-- =====================================================

-- Passwords: password  (Argon2i hash via PHP password_hash)
INSERT INTO users (username, user_password, user_role, user_status) VALUES
('manager', '$argon2i$v=19$m=65536,t=4,p=1$NVhORUN6eDE4enpYUVAwVA$yzhc0xj2difQaVTHuJfbXfg4le08o6m3vMFohwU80Bo', 'manager', 'active'),
('attendant', '$argon2i$v=19$m=65536,t=4,p=1$NVhORUN6eDE4enpYUVAwVA$yzhc0xj2difQaVTHuJfbXfg4le08o6m3vMFohwU80Bo', 'attendant', 'active');

INSERT INTO washers (washer_name, washer_status) VALUES
('Kwame Asante', 'active'),
('Ama Mensah', 'active'),
('Kofi Boateng', 'active'),
('Efua Darko', 'active'),
('Yaw Owusu', 'active');

INSERT INTO bays (id, bay_name, bay_status) VALUES
(1, 'Bay 1', 'empty'),
(2, 'Bay 2', 'empty'),
(3, 'Bay 3', 'empty'),
(4, 'Bay 4', 'empty');

INSERT INTO services (service_name, price_sedan, price_suv, price_van) VALUES
('Interior Cleaning', 60.00, 85.00, 95.00),
('Exterior Wash', 50.00, 70.00, 80.00),
('Full Detailed Wash', 180.00, 250.00, 300.00);

INSERT INTO machinery (machine_name, machinery_status) VALUES
('Pressure Washer', 'active'),
('Water Pump', 'active'),
('Commercial Vacuum', 'active'),
('Foam Cannon', 'active');
