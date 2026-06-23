-- Run this in phpMyAdmin → pharmacy_db → SQL tab
-- Adds user authentication to your existing pharmacy system

USE pharmacy_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pharmacist','cashier') DEFAULT 'pharmacist',
    status ENUM('active','inactive') DEFAULT 'active',
    avatar_color VARCHAR(20) DEFAULT '#00d4aa',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin account: username=admin / password=Admin@1234
INSERT IGNORE INTO users (full_name, email, username, password, role) VALUES
('System Administrator', 'admin@pharmacare.com', 'admin',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Note: The hashed password above = 'password'
-- Change it after first login for security!
