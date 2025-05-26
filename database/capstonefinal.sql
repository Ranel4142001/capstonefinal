-- Database: capstonefinal
-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS capstonefinal;

-- Use the newly created database
USE capstonefinal;

/* --- */
/* 1. Users Table */
/* Stores information about system users (e.g., admin, cashier, manager) */
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, -- Stores bcrypt hashed passwords
    role VARCHAR(20) DEFAULT 'cashier', -- e.g., 'admin', 'cashier', 'manager'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
);

/* --- */

/* 2. Categories Table */
/* Organizes products into logical groups */
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL
);

/* --- */

-- 3. Suppliers Table
-- Stores details of product suppliers
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL
);

/* --- */

-- 4. Products Table
-- Stores information about all inventory items
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(50) UNIQUE NOT NULL, -- Unique identifier for scanning
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,      -- Selling price
    cost_price DECIMAL(10, 2) NULL,     -- Cost to the business (for profit calculation)
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT NULL,
    supplier_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,     -- 1 for active, 0 for inactive/discontinued
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

/* --- */

-- 5. Customers Table
-- (Optional) Stores information about customers for loyalty or tracking purposes
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

/* --- */

-- 6. Sales Table
-- Records each completed sales transaction
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    payment_method VARCHAR(50) NOT NULL, -- e.g., 'Cash', 'Credit Card', 'GCash'
    cashier_id INT NOT NULL,             -- Who processed the sale
    customer_id INT NULL,                -- Who made the purchase (if recorded)
    status VARCHAR(20) DEFAULT 'completed', -- e.g., 'completed', 'returned', 'on_hold'
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT, -- Prevent deleting user if they have sales
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

/* --- */

-- 7. Sale_Items Table
-- Details each product included in a sale
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_sale DECIMAL(10, 2) NOT NULL, -- Price at the time of sale (in case product price changes)
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE, -- If sale is deleted, items are too
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT -- Prevent deleting product if it's in a sale
);

/* --- */

-- 8. Stock_Adjustments Table
-- Tracks manual changes to inventory (e.g., damage, returns, stock-take)
CREATE TABLE stock_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,           -- e.g., 'add', 'remove', 'damage', 'return', 'initial_stock'
    quantity_change INT NOT NULL,        -- Positive for increases, negative for decreases
    reason TEXT NULL,
    adjusted_by_user_id INT NOT NULL,    -- Who made the adjustment
    adjustment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (adjusted_by_user_id) REFERENCES users(id) ON DELETE RESTRICT
);  











INSERT INTO categories (name) VALUES
('Electronics'),
('Clothing'),
('Food & Beverage'),
('Books'),
('Home Goods');



INSERT INTO products (name, barcode, description, price, cost_price, stock_quantity, category_id, supplier_id, is_active) VALUES
('Laptop Pro', '123456789012', 'High-performance laptop for professionals', 1200.00, 900.00, 15, 1, NULL, 1),
('Wireless Mouse', '987654321098', 'Ergonomic wireless mouse', 25.50, 15.00, 50, 1, NULL, 1),
('T-Shirt (Medium)', '112233445566', 'Comfortable cotton t-shirt', 15.00, 7.50, 100, 2, NULL, 1),
('Coffee Beans (250g)', '223344556677', 'Freshly roasted arabica beans', 8.75, 4.00, 40, 3, NULL, 1),
('The Great Novel', '334455667788', 'A compelling fiction novel', 20.00, 10.00, 30, 4, NULL, 1),
('Desk Lamp', '445566778899', 'Modern LED desk lamp', 35.99, 20.00, 20, 5, NULL, 1),
('Bluetooth Speaker', '556677889900', 'Portable speaker with clear sound', 75.00, 40.00, 25, 1, NULL, 1),
('Jeans (Size 32)', '667788990011', 'Slim fit denim jeans', 45.00, 25.00, 60, 2, NULL, 1),
('Energy Drink (Can)', '778899001122', 'Refreshing energy boost', 2.50, 1.00, 200, 3, NULL, 1),
('Cookbook: Italian', '889900112233', 'Authentic Italian recipes', 30.00, 15.00, 10, 4, NULL, 1),
('Yoga Mat', '990011223344', 'Non-slip yoga mat', 22.00, 10.00, 35, 5, NULL, 1);


ALTER TABLE `sales`
ADD COLUMN `cash_received` DECIMAL(10,2) NULL AFTER `payment_method`,
ADD COLUMN `change_due` DECIMAL(10,2) NULL AFTER `cash_received`;
