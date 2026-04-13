<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Method not allowed'], 405);
}

$headers = getallheaders();
$apiKey = $headers['X-API-Key'] ?? $headers['X-Api-Key'] ?? null;

if (empty($apiKey)) {
    respond(['success' => false, 'message' => 'API key required'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    respond(['success' => false, 'message' => 'Invalid JSON'], 400);
}

$rfidCode = $input['rfid_code'] ?? null;
$sentAt = $input['sent_at'] ?? null;
$macAddress = $input['mac_address'] ?? null;
$statusType = $input['status_type'] ?? 'enter';

if (!in_array($statusType, ['enter', 'exit'])) {
    $statusType = 'enter';
}

if (empty($rfidCode) || empty($sentAt) || empty($macAddress)) {
    respond(['success' => false, 'message' => 'Missing required fields: rfid_code, sent_at, mac_address'], 400);
}

$parsedSentAt = date_create($sentAt);
if ($parsedSentAt === false) {
    respond(['success' => false, 'message' => 'Invalid sent_at format'], 400);
}

$sentAtForDb = $parsedSentAt->format('Y-m-d H:i:s');
$logDate = $parsedSentAt->format('Y-m-d');

if (!preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $macAddress)) {
    respond(['success' => false, 'message' => 'Invalid MAC address format'], 400);
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $findExistingSession = function (string $rfidCodeValue, string $accessGroupValue, string $logDateValue) use ($pdo) {
        $stmt = $pdo->prepare("
            SELECT id, check_in_at, check_out_at
            FROM access_sessions
            WHERE rfid_code = ? AND access_group = ? AND log_date = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$rfidCodeValue, $accessGroupValue, $logDateValue]);
        return $stmt->fetch();
    };

    $isDebugApiKey = defined('DEBUG_DEVICE_API_KEY') && hash_equals(DEBUG_DEVICE_API_KEY, $apiKey);
    $device = null;

    if ($isDebugApiKey) {
        $stmt = $pdo->prepare("SELECT id, device_name, access_group, is_active FROM devices WHERE mac_address = ?");
        $stmt->execute([$macAddress]);
        $device = $stmt->fetch();

        if ($device) {
            $device['auth_mode'] = 'debug_api_key';
        } else {
            $device = [
                'id' => null,
                'device_name' => 'DEBUG_DEVICE',
                'access_group' => 'DEBUG_AREA',
                'is_active' => true,
                'auth_mode' => 'debug_api_key'
            ];
        }
    } else {
        $stmt = $pdo->prepare("SELECT id, device_name, access_group, is_active FROM devices WHERE mac_address = ? AND api_key = ?");
        $stmt->execute([$macAddress, $apiKey]);
        $device = $stmt->fetch();

        if ($device) {
            $device['auth_mode'] = 'device_api_key';
        }
    }

    $deviceId = $device['id'] ?? null;
    $deviceName = $device['device_name'] ?? null;
    $accessGroup = $device['access_group'] ?? null;
    $deviceActive = $device['is_active'] ?? false;

    if (!empty($deviceName) && empty($accessGroup)) {
        $accessGroup = $deviceName;
    }

    $isRegistered = false;
    $accessStatus = 'unknown_device';
    $rfidId = null;
    $ownerName = null;
    $responseSuccess = false;
    $responseMessage = 'Unknown device';
    $responseCode = 401;
    $checkInAt = null;
    $checkOutAt = null;
    $accessNote = 'Unknown device';

    if (!$device) {
        $deviceName = 'UNKNOWN_DEVICE';
        $accessGroup = null;
    } elseif (!$deviceActive) {
        $accessStatus = 'rejected';
        $responseMessage = 'Device inactive';
        $responseCode = 403;
        $accessNote = 'Device inactive';
        $stmt = $pdo->prepare("SELECT id, owner_name, status FROM rfids WHERE rfid_code = ?");
        $stmt->execute([$rfidCode]);
        $rfid = $stmt->fetch();
        if ($rfid) {
            $isRegistered = true;
            $rfidId = $rfid['id'];
            $ownerName = $rfid['owner_name'];
        }
    } else {
        $stmt = $pdo->prepare("SELECT id, owner_name, status FROM rfids WHERE rfid_code = ?");
        $stmt->execute([$rfidCode]);
        $rfid = $stmt->fetch();

        $accessStatus = 'rejected';
        $responseSuccess = true;
        $responseMessage = 'Access log stored';
        $responseCode = 200;
        $accessNote = 'RFID not registered';

        if ($rfid) {
            $isRegistered = true;
            $rfidId = $rfid['id'];
            $ownerName = $rfid['owner_name'];

            if ($rfid['status'] === 'active') {
                $accessStatus = 'accepted';
                $accessNote = 'Access granted';
            } else {
                $accessNote = 'RFID inactive';
            }
        }
    }

    $pdo->prepare("
        INSERT INTO access_logs (
            device_id,
            rfid_id,
            rfid_code,
            log_date,
            request_at,
            mac_address,
            device_name,
            access_group,
            is_registered,
            access_status,
            status_type,
            raw_payload,
            received_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $deviceId,
        $rfidId,
        $rfidCode,
        $logDate,
        $sentAtForDb,
        $macAddress,
        $deviceName,
        $accessGroup,
        $isRegistered ? 1 : 0,
        $accessStatus,
        $statusType,
        json_encode($input)
    ]);

    if ($accessStatus === 'accepted' && !empty($accessGroup)) {
        $existingSession = $findExistingSession($rfidCode, $accessGroup, $logDate);

        if ($existingSession) {
            $checkInAt = $existingSession['check_in_at'];
            $checkOutAt = $existingSession['check_out_at'];

            if ($statusType === 'exit') {
                if (empty($checkInAt)) {
                    $checkInAt = $sentAtForDb;
                }
                $checkOutAt = $sentAtForDb;
            } else {
                if (empty($checkInAt)) {
                    $checkInAt = $sentAtForDb;
                }
                $checkOutAt = null;
            }

            $pdo->prepare("
                UPDATE access_sessions
                SET device_id = ?,
                    rfid_id = ?,
                    check_in_at = ?,
                    check_out_at = ?,
                    last_tap_at = ?,
                    owner_name = ?,
                    last_device_name = ?,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $deviceId,
                $rfidId,
                $checkInAt,
                $checkOutAt,
                $sentAtForDb,
                $ownerName,
                $deviceName,
                $existingSession['id']
            ]);
        } else {
            $checkInAt = $sentAtForDb;
            $checkOutAt = $statusType === 'exit' ? $sentAtForDb : null;

            $pdo->prepare("
                INSERT INTO access_sessions (
                    device_id,
                    rfid_id,
                    rfid_code,
                    log_date,
                    check_in_at,
                    check_out_at,
                    last_tap_at,
                    mac_address,
                    access_group,
                    owner_name,
                    entry_device_name,
                    last_device_name
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $deviceId,
                $rfidId,
                $rfidCode,
                $logDate,
                $checkInAt,
                $checkOutAt,
                $sentAtForDb,
                $macAddress,
                $accessGroup,
                $ownerName,
                $deviceName,
                $deviceName
            ]);
        }
    }

    if (!empty($deviceId)) {
        $pdo->prepare("UPDATE devices SET last_seen_at = NOW() WHERE id = ?")
            ->execute([$deviceId]);
    }

    $pdo->commit();

    respond([
        'success' => $responseSuccess,
        'message' => $responseMessage,
        'data' => [
            'is_registered' => $isRegistered,
            'access_status' => $accessStatus,
            'status_type' => $statusType,
            'log_date' => $logDate,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'owner_name' => $ownerName,
            'access_note' => $accessNote,
            'auth_mode' => $device['auth_mode'] ?? ($isDebugApiKey ? 'debug_api_key' : 'device_api_key'),
            'device_name' => $deviceName,
            'access_group' => $accessGroup
        ]
    ], $responseCode);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error: " . $e->getMessage());
    respond([
        'success' => false,
        'message' => 'Internal server error',
        'debug_error' => $e->getMessage()
    ], 500);
}
