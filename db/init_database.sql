-- ============================================
-- Bank Management System - Complete Database Setup
-- ============================================
-- This file contains the complete database initialization script
-- Run this ONCE to set up the entire database with schema and test data

-- ============================================
-- PART 1: CREATE DATABASE & TABLES
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS bank_management_system;
USE bank_management_system;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
    status ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(50),
    state VARCHAR(50),
    zip_code VARCHAR(10),
    aadhar_number VARCHAR(20),
    pan_number VARCHAR(20),
    registration_status ENUM('PENDING', 'APPROVED', 'REJECTED', 'CLOSED') NOT NULL DEFAULT 'PENDING',
    rejection_reason TEXT,
    approved_by INT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_phone (phone),
    INDEX idx_customer_code (customer_code),
    INDEX idx_registration_status (registration_status)
);

-- Accounts Table
CREATE TABLE IF NOT EXISTS accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    account_type ENUM('SAVINGS', 'CURRENT') NOT NULL DEFAULT 'SAVINGS',
    balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    status ENUM('ACTIVE', 'INACTIVE', 'CLOSED') NOT NULL DEFAULT 'ACTIVE',
    opening_balance DECIMAL(15, 2) DEFAULT 0.00,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    UNIQUE KEY unique_account_number (account_number),
    INDEX idx_account_number (account_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status)
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    transaction_type ENUM('DEPOSIT', 'WITHDRAW', 'TRANSFER') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    related_account_id INT,
    balance_before DECIMAL(15, 2),
    balance_after DECIMAL(15, 2),
    remark TEXT,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (related_account_id) REFERENCES accounts(account_id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_account_id (account_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_related_account (related_account_id)
);

-- Registration Requests Table (for tracking customer registration requests)
CREATE TABLE IF NOT EXISTS registration_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(50),
    state VARCHAR(50),
    zip_code VARCHAR(10),
    aadhar_number VARCHAR(20),
    pan_number VARCHAR(20),
    username VARCHAR(50) UNIQUE NOT NULL,
    request_status ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    rejection_reason TEXT,
    reviewed_by INT,
    reviewed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_request_status (request_status),
    INDEX idx_email (email)
);

-- Admin Users Table (to store admin accounts separately for better tracking)
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_code VARCHAR(20) UNIQUE NOT NULL,
    employee_id VARCHAR(20),
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(15),
    department VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_admin_code (admin_code)
);

-- Activity Logs Table (for audit trail)
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- ============================================
-- PART 2: INSERT DEFAULT ADMIN USER & DATA
-- ============================================

-- Create default admin user (username: admin, password: admin@123)
INSERT IGNORE INTO users (username, password_hash, role, status) VALUES 
('admin', '$2y$10$QND.yNnk6EWKrqFdwVKndeC15xo4BqbHVvuzMPv8cTcG0M0P8Z8xm', 'ADMIN', 'ACTIVE');

-- Insert admin details
INSERT IGNORE INTO admin_users (user_id, admin_code, full_name, email, department) VALUES 
(1, 'ADMIN001', 'System Administrator', 'admin@bank.com', 'Administration');

-- ============================================
-- PART 3: INSERT TEST USERS (Optional Demo Data)
-- ============================================

-- Create test customer user
INSERT IGNORE INTO users (username, password_hash, role, status) VALUES 
('anshubank', '$2y$10$bL4cQQkYNLmEVBQz6.xLtOlN5z8pL3nM2sVwXxYyZz1uNoPqLcHU2', 'CUSTOMER', 'ACTIVE');

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Verify database and tables created
SELECT 'Database initialized successfully!' AS status;

-- Show all users
SELECT '--- ALL USERS ---' AS info;
SELECT user_id, username, role, status, created_at FROM users;

-- Show admin users
SELECT '--- ADMIN USERS ---' AS info;
SELECT au.admin_id, u.username, au.admin_code, au.full_name, au.email, au.department 
FROM admin_users au 
JOIN users u ON au.user_id = u.user_id;

-- Count tables
SELECT '--- TABLE COUNT ---' AS info;
SELECT COUNT(*) as table_count FROM information_schema.tables 
WHERE table_schema = 'bank_management_system';

-- ============================================
-- DEMO CREDENTIALS
-- ============================================
-- Admin User:
--   Username: admin
--   Password: admin@123
--
-- Customer User:
--   Username: anshubank
--   Password: Demo@1234
--
-- ============================================
-- NOTES
-- ============================================
-- - This script creates 7 tables with proper relationships
-- - All tables have appropriate indexes for performance
-- - Foreign key constraints ensure data integrity
-- - Default admin user is pre-created
-- - To reset: DROP DATABASE bank_management_system; (then run this script again)
-- ============================================
