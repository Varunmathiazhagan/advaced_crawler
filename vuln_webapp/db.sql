-- Web Application Database Setup
-- Database schema for user management system

CREATE DATABASE IF NOT EXISTS webapp;
USE webapp;

-- Drop tables if they exist
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS admin_logs;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Comments table for user feedback
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Products table for catalog
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    category VARCHAR(50)
);

-- Admin logs table
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user VARCHAR(50),
    action VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample users
INSERT INTO users (username, password, email, role) VALUES
('admin', 'admin123', 'admin@webapp.local', 'admin'),
('john', 'password', 'john@example.com', 'user'),
('alice', '123456', 'alice@example.com', 'user'),
('bob', 'qwerty', 'bob@example.com', 'user'),
('test', 'test', 'test@test.com', 'user');

-- Insert sample products
INSERT INTO products (name, description, price, category) VALUES
('Laptop', 'High-performance laptop', 999.99, 'electronics'),
('Phone', 'Smartphone with great camera', 599.99, 'electronics'),
('Book', 'Programming tutorial book', 29.99, 'books'),
('Coffee Mug', 'Developer coffee mug', 12.99, 'accessories');

-- Insert sample comments
INSERT INTO comments (user_id, comment) VALUES
(1, 'Welcome to our web application!'),
(2, 'This is a test comment'),
(3, 'Great application interface!');

-- Grant permissions
GRANT ALL PRIVILEGES ON webapp.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
