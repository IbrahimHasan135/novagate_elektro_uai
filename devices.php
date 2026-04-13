<?php
require_once __DIR__ . '/config/database.php';

$page = 'devices';
$pdo = getDB();

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = '1=1';
$params = [];
if ($search) {
    $where .= " AND (device_name LIKE ? OR mac_address LIKE ? OR location LIKE ? OR access_group LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter) {
    $where .= " AND is_active = ?";
    $params[] = $statusFilter === 'active' ? 1 : 0;
}

$stmt = $pdo->prepare("SELECT * FROM devices WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$devices = $stmt->fetchAll();

$error = '';
$success = '';

if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header('Location: devices.php?deleted=1');
    exit;
}

if (isset($_POST['toggle_id'])) {
    $stmt = $pdo->prepare("UPDATE devices SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$_POST['toggle_id']]);
    header('Location: devices.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_device'])) {
    $deviceName = trim($_POST['device_name'] ?? '');
    $macAddress = trim($_POST['mac_address'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $accessGroup = trim($_POST['access_group'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');
    
    if (empty($deviceName) || empty($macAddress) || empty($apiKey)) {
        $error = 'Nama device, MAC address, dan API key wajib diisi';
    } elseif (!preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $macAddress)) {
        $error = 'Format MAC address tidak valid';
    } else {
        $stmt = $pdo->prepare("INSERT INTO devices (device_name, mac_address, api_key, location, access_group) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$deviceName, $macAddress, $apiKey, $location ?: null, $accessGroup ?: null]);
        $success = 'Device berhasil ditambahkan';
    }
}
?>
<?php require_once __DIR__ . '/views/partials/header.php'; ?>

<div class="page-header mb-4">
    <h1 class="h3 fw-bold text-primary">Pengelolaan Device</h1>
    <p class="text-muted">Kelola device RFID yang terhubung ke sistem</p>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>Device berhasil dihapus
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-plus-circle me-2"></i>Tambah Device Baru
        </h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="add_device" value="1">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="device_name" class="form-label fw-medium">Nama Device</label>
                    <input type="text" id="device_name" name="device_name" class="form-control" placeholder="Contoh: Pintu Lab Elektro" required>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="mac_address" class="form-label fw-medium">MAC Address</label>
                    <input type="text" id="mac_address" name="mac_address" class="form-control" placeholder="AA:BB:CC:DD:EE:FF" required>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="location" class="form-label fw-medium">Lokasi</label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="Contoh: Gedung A, Lantai 2">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="access_group" class="form-label fw-medium">Grup Akses</label>
                    <input type="text" id="access_group" name="access_group" class="form-control" placeholder="Contoh: Lab Elektro / Ruang Server">
                    <div class="form-text">Device dengan grup akses sama akan dianggap satu ruangan/satu sesi akses</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="api_key" class="form-label fw-medium">API Key</label>
                    <div class="input-group">
                        <input type="text" id="api_key" name="api_key" class="form-control" placeholder="Generated API key" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('api_key').value = generateApiKey()">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>
                    <div class="form-text">Klik tombol panah untuk generate API key</div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Device
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-header py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-hdd me-2"></i>Daftar Device
        </h5>
    </div>
    
    <form method="GET" class="p-3 border-bottom">
        <div class="row g-2">
            <div class="col-12 col-md-5">
                <input type="text" name="search" placeholder="Cari nama, MAC address, atau lokasi..." value="<?= htmlspecialchars($search) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
            </div>
        </div>
    </form>
    
    <div class="table-responsive">
        <?php if ($devices): ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nama Device</th>
                    <th>MAC Address</th>
                    <th>Lokasi</th>
                    <th>Grup Akses</th>
                    <th>API Key</th>
                    <th>Status</th>
                    <th>Terakhir Seen</th>
                    <th class="px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $device): ?>
                <tr>
                    <td class="fw-medium"><?= htmlspecialchars($device['device_name']) ?></td>
                    <td><code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($device['mac_address']) ?></code></td>
                    <td><?= $device['location'] ? htmlspecialchars($device['location']) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= $device['access_group'] ? htmlspecialchars($device['access_group']) : '<span class="text-muted">-</span>' ?></td>
                    <td><code class="bg-light px-2 py-1 rounded small"><?= htmlspecialchars($device['api_key']) ?></code></td>
                    <td>
                        <span class="badge badge-status <?= $device['is_active'] ? 'badge-success-custom' : 'badge-danger-custom' ?>">
                            <?= $device['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?php if ($device['last_seen_at']): ?>
                            <?= date('d M Y H:i', strtotime($device['last_seen_at'])) ?>
                        <?php else: ?>
                            Belum pernah
                        <?php endif; ?>
                    </td>
                    <td class="px-4">
                        <div class="d-flex gap-1">
                            <form method="POST">
                                <input type="hidden" name="toggle_id" value="<?= $device['id'] ?>">
                                <button type="submit" class="btn btn-outline-<?= $device['is_active'] ? 'warning' : 'success' ?> btn-sm">
                                    <i class="bi bi-toggle-<?= $device['is_active'] ? 'off' : 'on' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Yakin hapus device ini?')">
                                <input type="hidden" name="delete_id" value="<?= $device['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-hdd text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">Tidak ada device yang terdaftar</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function generateApiKey() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < 32; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}
</script>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
