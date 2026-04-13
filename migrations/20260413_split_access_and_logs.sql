USE novagate_db;

RENAME TABLE access_logs TO access_sessions;

ALTER TABLE access_sessions
    ADD COLUMN owner_name VARCHAR(120) NULL AFTER mac_address,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

UPDATE access_sessions s
LEFT JOIN rfids r ON s.rfid_id = r.id
SET s.owner_name = r.owner_name
WHERE s.owner_name IS NULL;

CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED DEFAULT NULL,
    rfid_id BIGINT UNSIGNED DEFAULT NULL,
    rfid_code VARCHAR(64) NOT NULL,
    log_date DATE NOT NULL,
    request_at DATETIME NOT NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    mac_address VARCHAR(32) NOT NULL,
    is_registered BOOLEAN DEFAULT FALSE,
    access_status ENUM('accepted', 'rejected', 'unknown_device') DEFAULT 'unknown_device',
    status_type ENUM('enter', 'exit') DEFAULT 'enter',
    raw_payload JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    FOREIGN KEY (rfid_id) REFERENCES rfids(id) ON DELETE SET NULL,
    INDEX idx_rfid_code (rfid_code),
    INDEX idx_log_date (log_date),
    INDEX idx_request_at (request_at),
    INDEX idx_access_status (access_status),
    INDEX idx_status_type (status_type),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
