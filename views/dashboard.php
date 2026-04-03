<?php
require_once __DIR__ . '/../config/database.php';

$page = 'dashboard';

$today = date('Y-m-d');
$pdo = getDB();

$stmt = $pdo->query("SELECT COUNT(*) FROM access_logs WHERE DATE(sent_at) = '$today'");
$todayAccess = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM rfids WHERE status = 'active'");
$activeRfid = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM devices WHERE is_active = 1");
$activeDevices = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM access_logs");
$totalLogs = $stmt->fetchColumn();

$limit = 20;
$logs = $pdo->query("
    SELECT al.*, d.device_name, r.owner_name 
    FROM access_logs al 
    LEFT JOIN devices d ON al.device_id = d.id 
    LEFT JOIN rfids r ON al.rfid_id = r.id 
    ORDER BY al.received_at DESC 
    LIMIT $limit
")->fetchAll();

$filterDate = $_GET['date'] ?? '';
$filterRfid = $_GET['rfid'] ?? '';
$filterDevice = $_GET['device'] ?? '';
$filterStatus = $_GET['status'] ?? '';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="page-header">
    <h1>Dashboard Monitoring</h1>
    <p>Monitor akses pintu RFID secara real-time</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">🚪</div>
        <div class="stat-info">
            <h3><?= $todayAccess ?></h3>
            <p>Akses Hari Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📱</div>
        <div class="stat-info">
            <h3><?= $activeRfid ?></h3>
            <p>RFID Aktif</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📋</div>
        <div class="stat-info">
            <h3><?= $totalLogs ?></h3>
            <p>Total Log Akses</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">🔧</div>
        <div class="stat-info">
            <h3><?=$activeDevices ?></h3>
            <p>Device Aktif</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Log Akses Terbaru</h2>
        <button class="btn btn-secondary btn-sm" onclick="location.reload()">↻ Refresh</button>
    </div>
    
    <form method="GET" class="search-bar">
        <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="form-control">
        <input type="text" name="rfid" placeholder="Cari kode RFID..." value="<?= htmlspecialchars($filterRfid) ?>" class="form-control">
        <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="accepted" <?= $filterStatus === 'accepted' ? 'selected' : '' ?>>Accepted</option>
            <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <option value="unknown_device" <?= $filterStatus === 'unknown_device' ? 'selected' : '' ?>>Unknown Device</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    
    <?php if ($logs): ?>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Kode RFID</th>
                <th>Pemilik</th>
                <th>Device</th>
                <th>MAC Address</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td>
                    <div><?= date('d M Y', strtotime($log['sent_at'])) ?></div>
                    <div class="text-muted text-small"><?= date('H:i:s', strtotime($log['sent_at'])) ?></div>
                </td>
                <td><code><?= htmlspecialchars($log['rfid_code']) ?></code></td>
                <td><?= $log['owner_name'] ? htmlspecialchars($log['owner_name']) : '<span class="text-muted">-</span>' ?></td>
                <td><?= $log['device_name'] ? htmlspecialchars($log['device_name']) : '<span class="text-muted">Unknown</span>' ?></td>
                <td class="text-muted text-small"><?= htmlspecialchars($log['mac_address']) ?></td>
                <td>
                    <?php 
                    $statusClass = match($log['access_status']) {
                        'accepted' => 'badge-success',
                        'rejected' => 'badge-error',
                        default => 'badge-warning'
                    };
                    $statusText = match($log['access_status']) {
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        default => 'Unknown Device'
                    };
                    ?>
                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Belum ada data akses</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>