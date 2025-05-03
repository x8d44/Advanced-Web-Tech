-- Grocery Store Database Schema
-- Drop database if exists (comment this in production)
-- DROP DATABASE IF EXISTS grocery_store;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS grocery_store;
USE grocery_store;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create enhanced captcha table with improved security features
CREATE TABLE IF NOT EXISTS captcha (
    captcha_id INT AUTO_INCREMENT PRIMARY KEY,
    captcha_text VARCHAR(10) NOT NULL,         -- Text of the CAPTCHA
    image_path VARCHAR(255) NOT NULL,          -- Path to CAPTCHA image
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When CAPTCHA was created
    expires_at TIMESTAMP NULL DEFAULT NULL,    -- When CAPTCHA expires
    used BOOLEAN DEFAULT FALSE                 -- Whether CAPTCHA has been used
);

-- Create an index for performance optimization on CAPTCHA table
CREATE INDEX idx_captcha_expires ON captcha(expires_at, used);

-- Create orders table with customer information
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create orders table that groups items 
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_cart_user (user_id),
    INDEX idx_cart_product (product_id)
);

-- Data

-- Insert vegetable products
INSERT INTO products (product_name, description, price, category, image_path) VALUES
('Potato', 'Fresh potatoes from local farmers', 1.99, 'Vegetables', 'potato.jpg'),
('Carrots', 'Organic carrots rich in vitamins', 2.49, 'Vegetables', 'carrots.jpg'),
('Broccoli', 'Fresh green broccoli', 3.29, 'Vegetables', 'broccoli.jpg');

-- Insert meat products
INSERT INTO products (product_name, description, price, category, image_path) VALUES
('Chicken', 'Free-range chicken', 5.99, 'Meat', 'chicken.jpg'),
('Fish', 'Fresh salmon fillet', 8.99, 'Meat', 'fish.jpg'),
('Beef', 'Grass-fed beef', 9.99, 'Meat', 'beef.jpg'),
('Pork', 'Premium pork cuts', 7.49, 'Meat', 'pork.jpg');

-- Insert captcha images with enhanced security features
INSERT INTO captcha (captcha_text, image_path, expires_at, used) VALUES
('Aeik2', 'image1.jpg', DATE_ADD(NOW(), INTERVAL 1 HOUR), 0),
('ecb4f', 'image2.jpg', DATE_ADD(NOW(), INTERVAL 1 HOUR), 0),
('7plBJ8', 'image3.jpg', DATE_ADD(NOW(), INTERVAL 1 HOUR), 0),
('24qVg', 'image4.jpg', DATE_ADD(NOW(), INTERVAL 1 HOUR), 0);

-- Create indexes for common queries
CREATE INDEX idx_product_category ON products(category);
CREATE INDEX idx_order_user ON orders(user_id);
CREATE INDEX idx_order_status ON orders(status);
CREATE INDEX idx_order_date ON orders(order_date);