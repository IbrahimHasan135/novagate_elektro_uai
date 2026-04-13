USE novagate_db;

ALTER TABLE devices
    ADD COLUMN access_group VARCHAR(100) NULL AFTER location;

UPDATE devices
SET access_group = COALESCE(NULLIF(location, ''), device_name)
WHERE access_group IS NULL;

RENAME TABLE access_logs TO access_sessions;

ALTER TABLE access_sessions
    DROP INDEX uniq_access_session_daily,
    ADD COLUMN access_group VARCHAR(100) NULL AFTER mac_address,
    ADD COLUMN owner_name VARCHAR(120) NULL AFTER mac_address,
    ADD COLUMN entry_device_name VARCHAR(100) NULL AFTER owner_name,
    ADD COLUMN last_device_name VARCHAR(100) NULL AFTER entry_device_name,
    ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

UPDATE access_sessions s
LEFT JOIN rfids r ON s.rfid_id = r.id
LEFT JOIN devices d ON s.device_id = d.id
SET s.owner_name = r.owner_name
    , s.access_group = COALESCE(d.access_group, d.location, s.mac_address)
    , s.entry_device_name = d.device_name
    , s.last_device_name = d.device_name
WHERE s.owner_name IS NULL;

UPDATE access_sessions s
LEFT JOIN devices d ON s.device_id = d.id
SET s.access_group = COALESCE(s.access_group, d.access_group, d.location, s.mac_address),
    s.entry_device_name = COALESCE(s.entry_device_name, d.device_name),
    s.last_device_name = COALESCE(s.last_device_name, d.device_name)
WHERE s.access_group IS NULL OR s.entry_device_name IS NULL OR s.last_device_name IS NULL;

ALTER TABLE access_sessions
    MODIFY COLUMN access_group VARCHAR(100) NOT NULL,
    ADD INDEX idx_access_sessions_access_group (access_group),
    ADD UNIQUE KEY uniq_access_session_daily (rfid_code, access_group, log_date);

CREATE TABLE IF NOT EXISTS access_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED DEFAULT NULL,
    rfid_id BIGINT UNSIGNED DEFAULT NULL,
    rfid_code VARCHAR(64) NOT NULL,
    log_date DATE NOT NULL,
    request_at DATETIME NOT NULL,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    mac_address VARCHAR(32) NOT NULL,
    device_name VARCHAR(100) DEFAULT NULL,
    access_group VARCHAR(100) DEFAULT NULL,
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
