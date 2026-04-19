USE novagate_db;

CREATE TABLE IF NOT EXISTS rfid_pairings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    status ENUM('open', 'completed', 'cancelled') DEFAULT 'open',
    paired_rfid_code VARCHAR(64) DEFAULT NULL,
    matched_log_id BIGINT UNSIGNED DEFAULT NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (matched_log_id) REFERENCES access_logs(id) ON DELETE SET NULL,
    INDEX idx_rfid_pairings_device_status (device_id, status),
    INDEX idx_rfid_pairings_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
