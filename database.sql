-- ERP System Database Schema

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS erp_system;
USE erp_system;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
    department_id INT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    manager_id INT NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add foreign key constraint to users after departments table is created
ALTER TABLE users
ADD CONSTRAINT fk_user_department
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Product Categories Table
CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    parent_id INT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE SET NULL
);

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_name VARCHAR(100) NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(50) NULL,
    state VARCHAR(50) NULL,
    zip_code VARCHAR(20) NULL,
    country VARCHAR(50) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    category_id INT NULL,
    supplier_id INT NULL,
    cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5, 2) NULL DEFAULT 0.00,
    weight DECIMAL(10, 2) NULL,
    dimensions VARCHAR(50) NULL,
    status ENUM('active', 'inactive', 'discontinued') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    company VARCHAR(100) NULL,
    address TEXT NULL,
    city VARCHAR(50) NULL,
    state VARCHAR(50) NULL,
    zip_code VARCHAR(20) NULL,
    country VARCHAR(50) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    user_id INT NULL,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processing', 'completed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'other') NULL,
    payment_status ENUM('paid', 'unpaid', 'partial', 'refunded') NOT NULL DEFAULT 'unpaid',
    shipping_address TEXT NULL,
    billing_address TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Inventory Transactions Table
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('purchase', 'sale', 'return', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_id INT NULL COMMENT 'Could be order_id, purchase_id, etc.',
    notes TEXT NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, role, status)
VALUES ('admin', 'admin@example.com', '$2y$10$3dnB0g.YTlVaCkWpFVy.U.J32XM57YVaQ3GFNLQYfL5PXdUZVzrUm', 'Admin', 'User', 'admin', 'active');

-- Insert Default Departments
INSERT INTO departments (name, code, description, status) VALUES
('Management', 'MGMT', 'Company management department', 'active'),
('Sales', 'SALES', 'Sales and marketing department', 'active'),
('Finance', 'FIN', 'Finance and accounting department', 'active'),
('IT', 'IT', 'Information Technology department', 'active'),
('HR', 'HR', 'Human Resources department', 'active'),
('Operations', 'OPS', 'Operations department', 'active');

-- Update the admin user to belong to Management department
UPDATE users SET department_id = 1 WHERE username = 'admin';

-- Insert Default Product Categories
INSERT INTO product_categories (name, description, status) VALUES
('Electronics', 'Electronic devices and gadgets', 'active'),
('Furniture', 'Office and home furniture', 'active'),
('Clothing', 'Apparel and accessories', 'active'),
('Food & Beverage', 'Food and drink products', 'active'),
('Books', 'Books and publications', 'active'),
('Software', 'Software and digital products', 'active');

-- Insert Default Suppliers
INSERT INTO suppliers (name, contact_name, email, phone, address, city, state, country, status) VALUES
('Tech Supplies Inc.', 'John Smith', 'john@techsupplies.com', '555-123-4567', '123 Tech St', 'San Francisco', 'CA', 'USA', 'active'),
('Furniture Depot', 'Emma Johnson', 'emma@furnituredepot.com', '555-234-5678', '456 Oak Ave', 'Chicago', 'IL', 'USA', 'active'),
('Global Foods', 'Michael Brown', 'michael@globalfoods.com', '555-345-6789', '789 Market St', 'New York', 'NY', 'USA', 'active');

-- Insert Sample Products
INSERT INTO products (name, sku, description, category_id, supplier_id, cost_price, selling_price, quantity, reorder_level, status) VALUES
('Laptop Pro X', 'LP-001', '15-inch professional laptop with 16GB RAM', 1, 1, 800.00, 1200.00, 20, 5, 'active'),
('Office Desk', 'FD-001', 'Standard office desk with drawers', 2, 2, 150.00, 250.00, 15, 3, 'active'),
('Wireless Mouse', 'AM-001', 'Ergonomic wireless mouse', 1, 1, 15.00, 30.00, 50, 10, 'active'),
('Office Chair', 'FC-001', 'Adjustable office chair with lumbar support', 2, 2, 100.00, 180.00, 12, 3, 'active'),
('Keyboard', 'KB-001', 'Mechanical keyboard with RGB lighting', 1, 1, 40.00, 70.00, 30, 8, 'active');

-- Insert Sample Customers
INSERT INTO customers (first_name, last_name, email, phone, company, address, city, state, country, status) VALUES
('Alice', 'Smith', 'alice@example.com', '555-111-2222', 'ABC Corp', '123 Main St', 'Boston', 'MA', 'USA', 'active'),
('Bob', 'Johnson', 'bob@example.com', '555-222-3333', 'XYZ Inc', '456 Pine Ave', 'Seattle', 'WA', 'USA', 'active'),
('Carol', 'Williams', 'carol@example.com', '555-333-4444', 'Acme Ltd', '789 Elm St', 'Denver', 'CO', 'USA', 'active');

-- Insert Sample Orders
INSERT INTO orders (order_number, customer_id, user_id, order_date, status, total_amount, payment_method, payment_status) VALUES
('ORD-20230001', 1, 1, NOW(), 'completed', 1450.00, 'credit_card', 'paid'),
('ORD-20230002', 2, 1, NOW(), 'processing', 250.00, 'bank_transfer', 'paid'),
('ORD-20230003', 3, 1, NOW(), 'pending', 270.00, 'credit_card', 'unpaid');

-- Insert Sample Order Items
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 1, 1200.00, 1200.00),
(1, 3, 1, 30.00, 30.00),
(1, 5, 1, 70.00, 70.00),
(1, 4, 1, 150.00, 150.00),
(2, 4, 1, 180.00, 180.00),
(2, 3, 1, 30.00, 30.00),
(2, 5, 1, 70.00, 70.00),
(3, 3, 3, 30.00, 90.00),
(3, 5, 2, 70.00, 140.00);

-- Update inventory after sales
UPDATE products SET quantity = quantity - 1 WHERE id = 1;  -- Laptop
UPDATE products SET quantity = quantity - 3 WHERE id = 3;  -- Mouse
UPDATE products SET quantity = quantity - 3 WHERE id = 4;  -- Chair
UPDATE products SET quantity = quantity - 3 WHERE id = 5;  -- Keyboard