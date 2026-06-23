-- Online Pharmacy Management System - Database Setup
-- Run this SQL in your MySQL/MariaDB server

CREATE DATABASE IF NOT EXISTS pharmacy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_db;

-- ─── CATEGORIES ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── SUPPLIERS ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── MEDICINES ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    category_id INT,
    supplier_id INT,
    batch_number VARCHAR(50),
    barcode VARCHAR(100),
    unit VARCHAR(30) DEFAULT 'Tablet',
    purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 10,
    expiry_date DATE NOT NULL,
    manufacture_date DATE,
    description TEXT,
    status ENUM('active','inactive','discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- ─── SALES ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) NOT NULL UNIQUE,
    customer_name VARCHAR(100) DEFAULT 'Walk-in Customer',
    customer_phone VARCHAR(20),
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_method ENUM('cash','card','online') DEFAULT 'cash',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);

-- ─── SALE ITEMS ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT
);

-- ─── STOCK ADJUSTMENTS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    adjustment_type ENUM('restock','damage','return','correction') DEFAULT 'restock',
    quantity_change INT NOT NULL,
    reason TEXT,
    adjusted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
);

-- ─── SEED DATA ──────────────────────────────────────────────────────────────
INSERT IGNORE INTO categories (name, description) VALUES
('Antibiotics', 'Medicines that kill or inhibit bacteria'),
('Analgesics', 'Pain relief medications'),
('Antidiabetics', 'Blood sugar management'),
('Cardiovascular', 'Heart and blood pressure medications'),
('Vitamins & Supplements', 'Nutritional supplements'),
('Antacids', 'Stomach acid relief'),
('Antihistamines', 'Allergy relief medications'),
('Cough & Cold', 'Respiratory illness treatments');

INSERT IGNORE INTO suppliers (name, contact_person, phone, email) VALUES
('PharmaCo Distributors', 'John Smith', '0800-123-4567', 'orders@pharmaco.com'),
('MediSupply Ltd', 'Sarah Jones', '0800-987-6543', 'supply@medisupply.com'),
('HealthDist Inc', 'Mike Brown', '0800-456-7890', 'info@healthdist.com');

INSERT IGNORE INTO medicines (name, generic_name, category_id, supplier_id, batch_number, unit, purchase_price, selling_price, stock_quantity, min_stock_level, expiry_date) VALUES
('Amoxicillin 500mg', 'Amoxicillin', 1, 1, 'AMOX-2025-001', 'Capsule', 3.50, 6.00, 150, 20, '2026-08-15'),
('Paracetamol 500mg', 'Paracetamol', 2, 1, 'PARA-2025-001', 'Tablet', 0.80, 1.50, 500, 50, '2027-03-20'),
('Ibuprofen 400mg', 'Ibuprofen', 2, 2, 'IBUP-2025-002', 'Tablet', 1.20, 2.50, 320, 30, '2026-11-10'),
('Metformin 850mg', 'Metformin HCl', 3, 2, 'METF-2025-001', 'Tablet', 2.00, 4.00, 8, 25, '2026-06-30'),
('Amlodipine 5mg', 'Amlodipine', 4, 3, 'AMLO-2025-003', 'Tablet', 4.50, 8.00, 90, 15, '2025-12-31'),
('Vitamin C 1000mg', 'Ascorbic Acid', 5, 1, 'VITC-2025-001', 'Tablet', 1.50, 3.00, 600, 50, '2027-06-15'),
('Omeprazole 20mg', 'Omeprazole', 6, 3, 'OMEP-2025-002', 'Capsule', 3.00, 5.50, 4, 20, '2026-04-20'),
('Cetirizine 10mg', 'Cetirizine', 7, 2, 'CETI-2025-001', 'Tablet', 1.00, 2.00, 200, 30, '2027-01-10'),
('Amoxicillin 250mg Syrup', 'Amoxicillin', 1, 1, 'AMSY-2025-001', 'Bottle', 5.50, 9.00, 60, 15, '2025-11-05'),
('Aspirin 100mg', 'Acetylsalicylic Acid', 4, 2, 'ASPI-2025-001', 'Tablet', 0.60, 1.20, 450, 40, '2027-09-30'),
('Loratadine 10mg', 'Loratadine', 7, 3, 'LORA-2025-002', 'Tablet', 1.20, 2.50, 175, 25, '2026-07-22'),
('Salbutamol Inhaler', 'Salbutamol', 8, 1, 'SALB-2025-001', 'Inhaler', 12.00, 20.00, 45, 10, '2026-05-14');

-- Sample sales for today and past 7 days
INSERT INTO sales (invoice_number, customer_name, customer_phone, total_amount, paid_amount, payment_method, sale_date) VALUES
('INV-20260616-001', 'Alice Walker', '0712-345-678', 15.00, 15.00, 'cash', NOW()),
('INV-20260616-002', 'Bob Kariuki', '0723-456-789', 28.50, 30.00, 'cash', NOW()),
('INV-20260615-001', 'Carol Mwangi', NULL, 12.00, 12.00, 'card', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('INV-20260615-002', 'David Otieno', '0745-678-901', 45.00, 45.00, 'online', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('INV-20260614-001', 'Eve Njeri', NULL, 9.00, 9.00, 'cash', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('INV-20260613-001', 'Frank Kimani', '0756-789-012', 60.00, 60.00, 'cash', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('INV-20260612-001', 'Grace Wahu', NULL, 18.00, 20.00, 'cash', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('INV-20260611-001', 'Henry Oloo', '0767-890-123', 33.00, 33.00, 'card', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('INV-20260610-001', 'Irene Achieng', NULL, 22.50, 25.00, 'cash', DATE_SUB(NOW(), INTERVAL 6 DAY));

INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal) VALUES
(1, 2, 10, 1.50, 15.00),
(2, 1, 3, 6.00, 18.00),(2, 6, 3, 3.00, 9.00),(2, 8, 1, 1.50, 1.50),
(3, 8, 6, 2.00, 12.00),
(4, 5, 5, 8.00, 40.00),(4, 6, 2, 3.00, 6.00),(-1+5, 7, 1, 5.50, 5.50),
(5, 3, 4, 2.50, 10.00), -- wait, sale 5 is id=5
(6, 10, 10, 1.20, 12.00),(6, 1, 5, 6.00, 30.00),(6, 6, 6, 3.00, 18.00),
(7, 2, 12, 1.50, 18.00),
(8, 4, 5, 4.00, 20.00),(8, 6, 4, 3.00, 12.00),(8, 8, 1, 2.00, 2.00),
(9, 9, 2, 9.00, 18.00),(9, 8, 2, 2.00, 4.00),(9, 2, 1, 1.50, 1.50);
