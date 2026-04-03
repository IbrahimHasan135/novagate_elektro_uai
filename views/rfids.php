<?php
require_once __DIR__ . '/../config/database.php';

$page = 'rfids';
$pdo = getDB();

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = '1=1';
$params = [];
if ($search) {
    $where .= " AND (rfid_code LIKE ? OR owner_name LIKE ? OR owner_identifier LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT * FROM rfids WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$rfids = $stmt->fetchAll();

if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM rfids WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header('Location: /rfids?deleted=1');
    exit;
}

if (isset($_POST['toggle_id'])) {
    $stmt = $pdo->prepare("UPDATE rfids SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$_POST['toggle_id']]);
    header('Location: /rfids');
    exit;
}
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="page-header">
    <h1>RFID Terdaftar</h1>
    <p>Kelola seluruh kartu/tag RFID yang terdaftar di sistem</p>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">RFID berhasil dihapus</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar RFID</h2>
        <a href="/add-rfid" class="btn btn-primary">+ Tambah RFID</a>
    </div>
    
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Cari kode RFID, nama pemilik..." value="<?= htmlspecialchars($search) ?>" class="form-control">
        <select name="status" class="form-control">
            <option value="">Semua Status</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>
    
    <?php if ($rfids): ?>
    <table>
        <thead>
            <tr>
                <th>Kode RFID</th>
                <th>Nama Pemilik</th>
                <th>ID Pengguna</th>
                <th>Status</th>
                <th>Tanggal Daftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rfids as $rfid): ?>
            <tr>
                <td><code><?= htmlspecialchars($rfid['rfid_code']) ?></code></td>
                <td><?= htmlspecialchars($rfid['owner_name']) ?></td>
                <td><?= $rfid['owner_identifier'] ? htmlspecialchars($rfid['owner_identifier']) : '<span class="text-muted">-</span>' ?></td>
                <td>
                    <span class="badge <?= $rfid['status'] === 'active' ? 'badge-success' : 'badge-error' ?>">
                        <?= $rfid['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td><?= date('d M Y', strtotime($rfid['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="toggle_id" value="<?= $rfid['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <?= $rfid['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus RFID ini?')">
                        <input type="hidden" name="delete_id" value="<?= $rfid['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Tidak ada data RFID</p>
        <a href="/add-rfid" class="btn btn-primary">Tambah RFID Pertama</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>