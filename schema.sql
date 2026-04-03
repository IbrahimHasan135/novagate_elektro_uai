CREATE DATABASE IF NOT EXISTS novagate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE novagate_db;

CREATE TABLE IF NOT EXISTS devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    mac_address VARCHAR(32) NOT NULL UNIQUE,
    api_key VARCHAR(128) NOT NULL,
    location VARCHAR(150) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_seen_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rfids (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rfid_code VARCHAR(64) NOT NULL UNIQUE,
    owner_name VARCHAR(120) NOT NULL,
    owner_identifier VARCHAR(80) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED DEFAULT NULL,
    rfid_id BIGINT UNSIGNED DEFAULT NULL,
    rfid_code VARCHAR(64) NOT NULL,
    sent_at DATETIME NOT NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    mac_address VARCHAR(32) NOT NULL,
    is_registered BOOLEAN DEFAULT FALSE,
    access_status ENUM('accepted', 'rejected', 'unknown_device') DEFAULT 'unknown_device',
    raw_payload JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    FOREIGN KEY (rfid_id) REFERENCES rfids(id) ON DELETE SET NULL,
    INDEX idx_rfid_code (rfid_code),
    INDEX idx_sent_at (sent_at),
    INDEX idx_access_status (access_status),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;