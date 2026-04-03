<?php
require_once __DIR__ . '/../config/database.php';

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

if (empty($rfidCode) || empty($sentAt) || empty($macAddress)) {
    respond(['success' => false, 'message' => 'Missing required fields: rfid_code, sent_at, mac_address'], 400);
}

if (!preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $macAddress)) {
    respond(['success' => false, 'message' => 'Invalid MAC address format'], 400);
}

try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id, device_name, is_active FROM devices WHERE mac_address = ? AND api_key = ?");
    $stmt->execute([$macAddress, $apiKey]);
    $device = $stmt->fetch();
    
    if (!$device) {
        $pdo->prepare("INSERT INTO access_logs (rfid_code, sent_at, mac_address, is_registered, access_status, raw_payload, received_at) VALUES (?, ?, ?, ?, 'unknown_device', ?, NOW())")
            ->execute([
                $rfidCode, 
                $sentAt, 
                $macAddress, 
                false, 
                json_encode($input)
            ]);
        respond(['success' => false, 'message' => 'Unknown device'], 401);
    }
    
    if (!$device['is_active']) {
        $pdo->prepare("INSERT INTO access_logs (device_id, rfid_code, sent_at, mac_address, is_registered, access_status, raw_payload, received_at) VALUES (?, ?, ?, ?, ?, 'rejected', ?, NOW())")
            ->execute([
                $device['id'],
                $rfidCode, 
                $sentAt, 
                $macAddress, 
                false,
                json_encode($input)
            ]);
        respond(['success' => false, 'message' => 'Device inactive'], 403);
    }
    
    $stmt = $pdo->prepare("SELECT id, owner_name, status FROM rfids WHERE rfid_code = ?");
    $stmt->execute([$rfidCode]);
    $rfid = $stmt->fetch();
    
    $isRegistered = false;
    $accessStatus = 'rejected';
    $rfidId = null;
    $ownerName = null;
    
    if ($rfid) {
        $isRegistered = true;
        $rfidId = $rfid['id'];
        $ownerName = $rfid['owner_name'];
        
        if ($rfid['status'] === 'active') {
            $accessStatus = 'accepted';
        } else {
            $accessStatus = 'rejected';
        }
    }
    
    $pdo->prepare("INSERT INTO access_logs (device_id, rfid_id, rfid_code, sent_at, mac_address, is_registered, access_status, raw_payload, received_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
        ->execute([
            $device['id'],
            $rfidId,
            $rfidCode, 
            $sentAt, 
            $macAddress, 
            $isRegistered,
            $accessStatus,
            json_encode($input)
        ]);
    
    $pdo->prepare("UPDATE devices SET last_seen_at = NOW() WHERE id = ?")
        ->execute([$device['id']]);
    
    respond([
        'success' => true,
        'message' => 'Access log stored',
        'data' => [
            'is_registered' => $isRegistered,
            'access_status' => $accessStatus,
            'owner_name' => $ownerName
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'Internal server error'], 500);
}