-- Fishing Boat Catch & Earnings Management System Database

CREATE DATABASE IF NOT EXISTS fishing_management;
USE fishing_management;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'staff') DEFAULT 'staff',
    full_name VARCHAR(100),
    mobile VARCHAR(15),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Boats Table
CREATE TABLE boats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    boat_name VARCHAR(100) NOT NULL,
    boat_number VARCHAR(50) UNIQUE NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    owner_contact VARCHAR(15),
    boat_type ENUM('Mechanized', 'Fiber', 'Catamaran', 'Other') NOT NULL,
    registration_number VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Fishermen Table
CREATE TABLE fishermen (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fisherman_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15),
    role ENUM('Captain', 'Crew') DEFAULT 'Crew',
    status ENUM('active', 'inactive') DEFAULT 'active',
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Fish Types Table
CREATE TABLE fish_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fish_name VARCHAR(100) NOT NULL,
    category ENUM('Fish', 'Prawn', 'Crab', 'Other') NOT NULL,
    unit VARCHAR(20) DEFAULT 'Kg',
    default_rate DECIMAL(10, 2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Boat Trips Table
CREATE TABLE boat_trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    boat_id INT NOT NULL,
    trip_date DATE NOT NULL,
    trip_reference VARCHAR(100),
    trip_id_auto VARCHAR(50) UNIQUE,
    total_catch_kg DECIMAL(10, 2) DEFAULT 0,
    total_income DECIMAL(12, 2) DEFAULT 0,
    total_expenses DECIMAL(12, 2) DEFAULT 0,
    net_profit DECIMAL(12, 2) DEFAULT 0,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE,
    INDEX idx_boat_date (boat_id, trip_date),
    INDEX idx_trip_date (trip_date)
);

-- Trip Fishermen Assignment Table
CREATE TABLE trip_fishermen (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    fisherman_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    share_percentage DECIMAL(5, 2) DEFAULT 0,
    share_amount DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES boat_trips(id) ON DELETE CASCADE,
    FOREIGN KEY (fisherman_id) REFERENCES fishermen(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trip_fisherman (trip_id, fisherman_id),
    INDEX idx_trip_id (trip_id),
    INDEX idx_fisherman_id (fisherman_id)
);

-- Boat Crew Mapping (boat to fishermen) - allows configuring which fishermen belong to which boats
CREATE TABLE IF NOT EXISTS boat_crew (
    id INT PRIMARY KEY AUTO_INCREMENT,
    boat_id INT NOT NULL,
    fisherman_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_boat_fisher (boat_id, fisherman_id),
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE,
    FOREIGN KEY (fisherman_id) REFERENCES fishermen(id) ON DELETE CASCADE
);

-- Sample boat_crew mappings
INSERT IGNORE INTO boat_crew (boat_id, fisherman_id) VALUES
    (1, 1),
    (1, 2),
    (1, 3),
    (2, 4),
    (3, 5);

-- Daily Catch Table
CREATE TABLE daily_catch (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    fish_type_id INT NOT NULL,
    quantity_kg DECIMAL(10, 2) NOT NULL,
    rate_per_kg DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL,
    catch_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES boat_trips(id) ON DELETE CASCADE,
    FOREIGN KEY (fish_type_id) REFERENCES fish_types(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id),
    INDEX idx_catch_date (catch_date)
);

-- Expenses Table
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    expense_date DATE NOT NULL,
    expense_type ENUM('Fuel', 'Ice', 'Food', 'Net Repair', 'Port Charges', 'Other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES boat_trips(id) ON DELETE CASCADE,
    INDEX idx_trip_id (trip_id),
    INDEX idx_expense_date (expense_date)
);

-- Insert sample data
INSERT INTO users (username, password, email, role, full_name, mobile) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'admin@fishing.com', 'admin', 'Admin User', '9876543210'),
('staff', '$2y$10$YourHashedPasswordHere', 'staff@fishing.com', 'staff', 'Staff User', '9876543211');

INSERT INTO boats (boat_name, boat_number, owner_name, owner_contact, boat_type) VALUES
('Ocean Wave', 'BT001', 'John Doe', '9876543210', 'Mechanized'),
('Blue Sea', 'BT002', 'Jane Smith', '9876543211', 'Fiber'),
('Sea Captain', 'BT003', 'Robert Johnson', '9876543212', 'Catamaran');

INSERT INTO fishermen (fisherman_name, mobile_number, role) VALUES
('Ravi Kumar', '9876543213', 'Captain'),
('Suresh Nair', '9876543214', 'Crew'),
('Hari Singh', '9876543215', 'Crew'),
('Mohan Das', '9876543216', 'Captain'),
('Prasad Rao', '9876543217', 'Crew');

INSERT INTO fish_types (fish_name, category, default_rate) VALUES
('Vanjaram', 'Fish', 450.00),
('Tuna', 'Fish', 350.00),
('Prawn', 'Prawn', 600.00),
('Crab', 'Crab', 500.00),
('Mackerel', 'Fish', 200.00);
