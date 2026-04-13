<?php
require_once __DIR__ . '/config/database.php';

$page = 'dashboard';

$today = date('Y-m-d');
$pdo = getDB();
$filterDate = $_GET['date'] ?? $today;
$filterRfid = trim($_GET['rfid'] ?? '');
$filterStatus = $_GET['status'] ?? '';

$accessWhere = ["asess.log_date = :filter_date"];
$accessParams = ['filter_date' => $filterDate];

$logWhere = ["al.log_date = :filter_date"];
$logParams = ['filter_date' => $filterDate];

if ($filterRfid !== '') {
    $accessWhere[] = "asess.rfid_code LIKE :rfid";
    $logWhere[] = "al.rfid_code LIKE :rfid";
    $accessParams['rfid'] = '%' . $filterRfid . '%';
    $logParams['rfid'] = '%' . $filterRfid . '%';
}

if ($filterStatus !== '') {
    $logWhere[] = "al.access_status = :status";
    $logParams['status'] = $filterStatus;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM access_sessions WHERE log_date = ?");
$stmt->execute([$today]);
$todayAccess = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM access_logs WHERE log_date = ?");
$stmt->execute([$today]);
$todayLogs = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM rfids WHERE status = 'active'");
$activeRfid = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM devices WHERE is_active = 1");
$activeDevices = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM access_sessions");
$totalAccess = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM access_logs");
$totalLogs = $stmt->fetchColumn();

$limit = 20;

$accessSql = "
    SELECT asess.*
    FROM access_sessions asess
    WHERE " . implode(' AND ', $accessWhere) . "
    ORDER BY asess.last_tap_at DESC, asess.updated_at DESC
    LIMIT $limit
";
$accessStmt = $pdo->prepare($accessSql);
$accessStmt->execute($accessParams);
$accesses = $accessStmt->fetchAll();

$logsSql = "
    SELECT al.*, r.owner_name
    FROM access_logs al
    LEFT JOIN rfids r ON al.rfid_id = r.id
    WHERE " . implode(' AND ', $logWhere) . "
    ORDER BY al.request_at DESC, al.received_at DESC
    LIMIT $limit
";
$logsStmt = $pdo->prepare($logsSql);
$logsStmt->execute($logParams);
$logs = $logsStmt->fetchAll();
?>
<?php require_once __DIR__ . '/views/partials/header.php'; ?>

<div class="page-header mb-4">
    <h1 class="h3 fw-bold text-primary">Dashboard Monitoring</h1>
    <p class="text-muted">Monitor akses pintu RFID secara real-time</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary-subtle text-primary me-3">
                    <i class="bi bi-door-open"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= $todayAccess ?></h3>
                    <p class="text-muted mb-0 small">Akses Hari Ini</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success-subtle text-success me-3">
                    <i class="bi bi-credit-card-2-front"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= $activeRfid ?></h3>
                    <p class="text-muted mb-0 small">RFID Aktif</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning-subtle text-warning me-3">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= $todayLogs ?></h3>
                    <p class="text-muted mb-0 small">Log Hari Ini</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-info-subtle text-info me-3">
                    <i class="bi bi-hdd"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold"><?= $activeDevices ?></h3>
                    <p class="text-muted mb-0 small">Device Aktif</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-clock-history me-2"></i>Akses Harian
        </h5>
        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
    </div>
    
    <form method="GET" class="p-3 border-bottom">
        <div class="row g-2">
            <div class="col-12 col-md-3">
                <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <input type="text" name="rfid" placeholder="Cari kode RFID..." value="<?= htmlspecialchars($filterRfid) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="accepted" <?= $filterStatus === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="unknown_device" <?= $filterStatus === 'unknown_device' ? 'selected' : '' ?>>Unknown Device</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <?php if ($accesses): ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="px-4">Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Kode RFID</th>
                    <th>Pemilik</th>
                    <th>Device Masuk</th>
                    <th>Tap Terakhir</th>
                    <th>Grup Akses</th>
                    <th>MAC Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accesses as $access): ?>
                <tr>
                    <td class="px-4">
                        <div><?= date('d M Y', strtotime($access['log_date'])) ?></div>
                        <div class="text-muted small">Tap terakhir <?= date('H:i:s', strtotime($access['last_tap_at'])) ?></div>
                    </td>
                    <td><?= $access['check_in_at'] ? date('H:i:s', strtotime($access['check_in_at'])) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= $access['check_out_at'] ? date('H:i:s', strtotime($access['check_out_at'])) : '<span class="text-muted">-</span>' ?></td>
                    <td><code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($access['rfid_code']) ?></code></td>
                    <td><?= $access['owner_name'] ? htmlspecialchars($access['owner_name']) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= $access['entry_device_name'] ? htmlspecialchars($access['entry_device_name']) : '<span class="text-muted">Unknown</span>' ?></td>
                    <td><?= $access['last_device_name'] ? htmlspecialchars($access['last_device_name']) : '<span class="text-muted">Unknown</span>' ?></td>
                    <td><?= $access['access_group'] ? htmlspecialchars($access['access_group']) : '<span class="text-muted">-</span>' ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($access['mac_address']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">Belum ada data akses pada tanggal ini</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card table-card mt-4">
    <div class="card-header py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-list-ul me-2"></i>Log Request
        </h5>
    </div>
    <div class="table-responsive">
        <?php if ($logs): ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="px-4">Waktu</th>
                    <th>Kode RFID</th>
                    <th>Pemilik</th>
                    <th>Device</th>
                    <th>Grup Akses</th>
                    <th>MAC Address</th>
                    <th>Tipe</th>
                    <th>Keterangan</th>
                    <th class="px-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="px-4">
                        <div><?= date('d M Y', strtotime($log['request_at'])) ?></div>
                        <div class="text-muted small"><?= date('H:i:s', strtotime($log['request_at'])) ?></div>
                    </td>
                    <td><code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($log['rfid_code']) ?></code></td>
                    <td><?= $log['owner_name'] ? htmlspecialchars($log['owner_name']) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= $log['device_name'] ? htmlspecialchars($log['device_name']) : '<span class="text-muted">Unknown</span>' ?></td>
                    <td><?= $log['access_group'] ? htmlspecialchars($log['access_group']) : '<span class="text-muted">-</span>' ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($log['mac_address']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= htmlspecialchars($log['status_type']) ?></span></td>
                    <td>
                        <?php if ($log['access_status'] === 'accepted'): ?>
                            <span class="text-success">Akses diterima</span>
                        <?php elseif ($log['access_status'] === 'rejected' && $log['is_registered']): ?>
                            <span class="text-danger">RFID tidak aktif</span>
                        <?php elseif ($log['access_status'] === 'rejected'): ?>
                            <span class="text-danger">RFID belum terdaftar</span>
                        <?php else: ?>
                            <span class="text-warning">Device tidak dikenal</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4">
                        <?php
                        $statusClass = match($log['access_status']) {
                            'accepted' => 'badge-success-custom',
                            'rejected' => 'badge-danger-custom',
                            default => 'badge-warning-custom'
                        };
                        $statusText = match($log['access_status']) {
                            'accepted' => 'Accepted',
                            'rejected' => 'Rejected',
                            default => 'Unknown Device'
                        };
                        ?>
                        <span class="badge badge-status <?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">Belum ada log request pada tanggal ini</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
