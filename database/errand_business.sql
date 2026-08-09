-- Create Database
CREATE DATABASE IF NOT EXISTS errand_business_db;
USE errand_business_db;

-- ==========================
-- USERS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'uploads/profile_pictures/default.jpg',
    contact_number VARCHAR(20) NOT NULL,
    role ENUM('client','tasker','admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- ERRANDS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS errands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    tasker_id INT NULL DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    category ENUM('delivery', 'moving', 'cleaning', 'repair', 'grocery', 'laundry', 'project', 'other') NOT NULL,
    description TEXT,
    fee DECIMAL(10,2) DEFAULT 0.00,
    deadline DATETIME NOT NULL,
    location VARCHAR(200) NOT NULL,
    contact TEXT NOT NULL,
    status ENUM('pending','pending_payment','in_process', 'pending_completion', 'pending_failure', 'dispute') DEFAULT 'pending',
    cancel_requested_by_tasker TINYINT(1) DEFAULT 0 COMMENT '0 = normal, 1 = tasker wants to cancel',
    cancel_requested_by_client TINYINT(1) DEFAULT 0 COMMENT '0 = normal, 1 = client wants to cancel',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL DEFAULT NULL,      -- when tasker accepts the errand
    in_process_at TIMESTAMP NULL DEFAULT NULL,    -- when errand moves to in_process
    completed_at TIMESTAMP NULL DEFAULT NULL,     -- when errand is completed
    failed_at TIMESTAMP NULL DEFAULT NULL,        -- when errand failed

    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tasker_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================
-- ERRAND ATTACHMENTS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS errand_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    errand_id INT NOT NULL,
    file_path VARCHAR(255), -- e.g., uploads/attachments/file1.jpg
    original_name VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (errand_id) REFERENCES errands(id) ON DELETE CASCADE
);

-- ==========================
-- PAYMENT PROOFS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS payment_proofs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    errand_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL, -- e.g., uploads/payments/proof1.jpg
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (errand_id) REFERENCES errands(id) ON DELETE CASCADE
);

-- ==========================
-- NOTIFICATIONS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('errand','system','alert') NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,       -- 0 = unread (red dot), 1 = read
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================
-- HISTORY TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    errand_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category ENUM('delivery', 'moving', 'cleaning', 'repair', 'grocery', 'laundry', 'project', 'other') NOT NULL,
    description TEXT,
    fee DECIMAL(10,2) DEFAULT 0.00,
    deadline DATETIME NOT NULL,
    location VARCHAR(200) NOT NULL,
    contact TEXT NOT NULL,
    status ENUM('completed','failed') NOT NULL,

    client_id INT,
    client_last_name VARCHAR(50),
    client_first_name VARCHAR(50),
    client_middle_name VARCHAR(50),
    client_email VARCHAR(100),
    client_contact_number VARCHAR(20),
    client_profile_picture VARCHAR(255) DEFAULT 'uploads/history/profile_pictures/default.jpg',

    tasker_id INT,
    tasker_last_name VARCHAR(50),
    tasker_first_name VARCHAR(50),
    tasker_middle_name VARCHAR(50),
    tasker_email VARCHAR(100),
    tasker_contact_number VARCHAR(20),
    tasker_profile_picture VARCHAR(255) DEFAULT 'uploads/history/profile_pictures/default.jpg',

    created_at DATETIME,
    accepted_at DATETIME,
    in_process_at DATETIME,
    completed_at DATETIME,
    failed_at DATETIME,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- delete flags
    tasker_deleted TINYINT(1) DEFAULT 0 COMMENT '0 = visible, 1 = deleted by tasker',
    client_deleted TINYINT(1) DEFAULT 0 COMMENT '0 = visible, 1 = deleted by client'
);


-- ==========================
-- HISTORY ERRAND ATTACHMENTS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS history_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    history_errand_id INT NOT NULL,   -- references history.id
    file_path VARCHAR(255),           -- e.g., uploads/history/attachments/file1.jpg
    original_name VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (history_errand_id) REFERENCES history(id) ON DELETE CASCADE
);

-- ==========================
-- HISTORY PAYMENT PROOFS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS history_payment_proofs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    history_errand_id INT NOT NULL,     -- references history_errands.id 
    file_path VARCHAR(255) NOT NULL,    -- e.g., uploads/history/payments/proof1.jpg
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (history_errand_id) REFERENCES history_errands(id) ON DELETE CASCADE
);
