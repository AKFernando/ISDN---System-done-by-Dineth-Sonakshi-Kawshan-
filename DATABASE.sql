CREATE DATABASE IF NOT EXISTS isdn_db;
USE isdn_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'rdc', 'customer') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    rdc_location VARCHAR(100),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    rdc_location VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_reference VARCHAR(255),
    status ENUM('pending', 'dispatched', 'delivered') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    rdc_staff_id INT,
    status ENUM('pending', 'dispatched', 'delivered') DEFAULT 'pending',
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_date TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (rdc_staff_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (username, password, role, name, email, phone, rdc_location) VALUES
('admin', MD5('admin123'), 'admin', 'Head Office Admin', 'admin@islandlink.com', '555-0001', NULL),
('rdc_staff', MD5('rdc123'), 'rdc', 'RDC Staff Member', 'rdc@islandlink.com', '555-0002', 'North RDC'),
('customer', MD5('customer123'), 'customer', 'Retail Store Owner', 'customer@retailer.com', '555-0003', NULL);

INSERT INTO products (name, category, price, stock, rdc_location, image) VALUES
('Laptop Computer', 'Electronics', 899.99, 50, 'North RDC', NULL),
('Office Chair', 'Furniture', 149.99, 100, 'South RDC', NULL),
('Wireless Mouse', 'Electronics', 29.99, 200, 'North RDC', NULL),
('Desk Lamp', 'Furniture', 39.99, 150, 'East RDC', NULL),
('USB Cable', 'Electronics', 9.99, 500, 'North RDC', NULL),
('Filing Cabinet', 'Furniture', 199.99, 75, 'South RDC', NULL),
('Monitor 24inch', 'Electronics', 249.99, 80, 'North RDC', NULL),
('Keyboard', 'Electronics', 49.99, 120, 'East RDC', NULL);
