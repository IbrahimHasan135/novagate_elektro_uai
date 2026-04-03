<?php
require_once __DIR__ . '/../config/database.php';

$page = 'devices';
$pdo = getDB();

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = '1=1';
$params = [];
if ($search) {
    $where .= " AND (device_name LIKE ? OR mac_address LIKE ? OR location LIKE ?)";
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
    $apiKey = trim($_POST['api_key'] ?? '');
    
    if (empty($deviceName) || empty($macAddress) || empty($apiKey)) {
        $error = 'Nama device, MAC address, dan API key wajib diisi';
    } elseif (!preg_match('/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $macAddress)) {
        $error = 'Format MAC address tidak valid';
    } else {
        $stmt = $pdo->prepare("INSERT INTO devices (device_name, mac_address, api_key, location) VALUES (?, ?, ?, ?)");
        $stmt->execute([$deviceName, $macAddress, $apiKey, $location ?: null]);
        $success = 'Device berhasil ditambahkan';
    }
}
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="page-header">
    <h1>Pengelolaan Device</h1>
    <p>Kelola device RFID yang terhubung ke sistem</p>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Device berhasil dihapus</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Tambah Device Baru</h2>
    </div>
    <form method="POST" class="form-row">
        <input type="hidden" name="add_device" value="1">
        <div class="form-group">
            <label for="device_name">Nama Device</label>
            <input type="text" id="device_name" name="device_name" class="form-control" placeholder="Contoh: Pintu Lab Elektro" required>
        </div>
        <div class="form-group">
            <label for="mac_address">MAC Address</label>
            <input type="text" id="mac_address" name="mac_address" class="form-control" placeholder="AA:BB:CC:DD:EE:FF" required>
        </div>
        <div class="form-group">
            <label for="location">Lokasi</label>
            <input type="text" id="location" name="location" class="form-control" placeholder="Contoh: Gedung A, Lantai 2">
        </div>
        <div class="form-group">
            <label for="api_key">API Key</label>
            <input type="text" id="api_key" name="api_key" class="form-control" placeholder="Generated API key" required>
            <small class="text-muted">Gunakan button generate untuk membuat API key</small>
        </div>
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-primary">Tambah Device</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Device</h2>
    </div>
    
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Cari nama, MAC address, atau lokasi..." value="<?= htmlspecialchars($search) ?>" class="form-control">
        <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>
    
    <?php if ($devices): ?>
    <table>
        <thead>
            <tr>
                <th>Nama Device</th>
                <th>MAC Address</th>
                <th>Lokasi</th>
                <th>API Key</th>
                <th>Status</th>
                <th>Terakhir Seen</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($devices as $device): ?>
            <tr>
                <td><strong><?= htmlspecialchars($device['device_name']) ?></strong></td>
                <td><code><?= htmlspecialchars($device['mac_address']) ?></code></td>
                <td><?= $device['location'] ? htmlspecialchars($device['location']) : '<span class="text-muted">-</span>' ?></td>
                <td><code class="text-small"><?= htmlspecialchars($device['api_key']) ?></code></td>
                <td>
                    <span class="badge <?= $device['is_active'] ? 'badge-success' : 'badge-error' ?>">
                        <?= $device['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td>
                    <?php if ($device['last_seen_at']): ?>
                        <span class="text-small text-muted"><?= date('d M Y H:i', strtotime($device['last_seen_at'])) ?></span>
                    <?php else: ?>
                        <span class="text-muted">Belum pernah</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="toggle_id" value="<?= $device['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <?= $device['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus device ini?')">
                        <input type="hidden" name="delete_id" value="<?= $device['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Tidak ada device yang terdaftar</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>