<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

function pairingRespond(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === '') {
    pairingRespond(['success' => false, 'message' => 'Action is required'], 400);
}

try {
    $pdo = getDB();

    if ($action === 'start') {
        $deviceId = (int) ($_POST['device_id'] ?? 0);
        if ($deviceId <= 0) {
            pairingRespond(['success' => false, 'message' => 'Device is required'], 400);
        }

        $stmt = $pdo->prepare("SELECT id, device_name, access_group, is_active FROM devices WHERE id = ?");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch();

        if (!$device) {
            pairingRespond(['success' => false, 'message' => 'Device not found'], 404);
        }

        if (!(bool) $device['is_active']) {
            pairingRespond(['success' => false, 'message' => 'Device is inactive'], 400);
        }

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE rfid_pairings SET status = 'cancelled', completed_at = NOW() WHERE device_id = ? AND status = 'open'")
            ->execute([$deviceId]);

        $pdo->prepare("INSERT INTO rfid_pairings (device_id, status) VALUES (?, 'open')")
            ->execute([$deviceId]);

        $pairingId = (int) $pdo->lastInsertId();
        $pdo->commit();

        pairingRespond([
            'success' => true,
            'message' => 'Pairing started',
            'data' => [
                'pairing_id' => $pairingId,
                'device_id' => (int) $device['id'],
                'device_name' => $device['device_name'],
                'access_group' => $device['access_group']
            ]
        ]);
    }

    if ($action === 'poll') {
        $pairingId = (int) ($_GET['pairing_id'] ?? 0);
        if ($pairingId <= 0) {
            pairingRespond(['success' => false, 'message' => 'Pairing ID is required'], 400);
        }

        $stmt = $pdo->prepare("
            SELECT rp.*, d.device_name, d.access_group
            FROM rfid_pairings rp
            INNER JOIN devices d ON d.id = rp.device_id
            WHERE rp.id = ?
            LIMIT 1
        ");
        $stmt->execute([$pairingId]);
        $pairing = $stmt->fetch();

        if (!$pairing) {
            pairingRespond(['success' => false, 'message' => 'Pairing not found'], 404);
        }

        $matchedLog = null;
        if (!empty($pairing['matched_log_id'])) {
            $stmt = $pdo->prepare("SELECT id, rfid_code, request_at FROM access_logs WHERE id = ? LIMIT 1");
            $stmt->execute([$pairing['matched_log_id']]);
            $matchedLog = $stmt->fetch();
        }

        if (!$matchedLog && $pairing['status'] === 'open') {
            $stmt = $pdo->prepare("
                SELECT id, rfid_code, request_at
                FROM access_logs
                WHERE device_id = ?
                  AND is_registered = 0
                  AND access_status = 'rejected'
                  AND request_at >= ?
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$pairing['device_id'], $pairing['started_at']]);
            $matchedLog = $stmt->fetch();

            if ($matchedLog) {
                $pdo->prepare("UPDATE rfid_pairings SET matched_log_id = ?, paired_rfid_code = ? WHERE id = ?")
                    ->execute([$matchedLog['id'], $matchedLog['rfid_code'], $pairingId]);
            }
        }

        pairingRespond([
            'success' => true,
            'message' => 'Pairing status loaded',
            'data' => [
                'pairing_id' => (int) $pairing['id'],
                'status' => $pairing['status'],
                'device_id' => (int) $pairing['device_id'],
                'device_name' => $pairing['device_name'],
                'access_group' => $pairing['access_group'],
                'rfid_code' => $matchedLog['rfid_code'] ?? $pairing['paired_rfid_code'],
                'matched_at' => $matchedLog['request_at'] ?? null
            ]
        ]);
    }

    if ($action === 'stop') {
        $pairingId = (int) ($_POST['pairing_id'] ?? 0);
        if ($pairingId <= 0) {
            pairingRespond(['success' => false, 'message' => 'Pairing ID is required'], 400);
        }

        $pdo->prepare("UPDATE rfid_pairings SET status = 'cancelled', completed_at = NOW() WHERE id = ? AND status = 'open'")
            ->execute([$pairingId]);

        pairingRespond(['success' => true, 'message' => 'Pairing stopped']);
    }

    if ($action === 'complete') {
        $pairingId = (int) ($_POST['pairing_id'] ?? 0);
        $rfidCode = trim($_POST['rfid_code'] ?? '');

        if ($pairingId <= 0 || $rfidCode === '') {
            pairingRespond(['success' => false, 'message' => 'Pairing ID and RFID code are required'], 400);
        }

        $pdo->prepare("
            UPDATE rfid_pairings
            SET status = 'completed',
                paired_rfid_code = ?,
                completed_at = NOW()
            WHERE id = ?
        ")->execute([$rfidCode, $pairingId]);

        pairingRespond(['success' => true, 'message' => 'Pairing completed']);
    }

    pairingRespond(['success' => false, 'message' => 'Invalid action'], 400);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    pairingRespond([
        'success' => false,
        'message' => 'Internal server error',
        'debug_error' => $e->getMessage()
    ], 500);
}
