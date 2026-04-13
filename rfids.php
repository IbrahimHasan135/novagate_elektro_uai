<?php
require_once __DIR__ . '/config/database.php';

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
    header('Location: rfids.php?deleted=1');
    exit;
}

if (isset($_POST['toggle_id'])) {
    $stmt = $pdo->prepare("UPDATE rfids SET status = IF(status='active','inactive','active') WHERE id = ?");
    $stmt->execute([$_POST['toggle_id']]);
    header('Location: rfids.php');
    exit;
}
?>
<?php require_once __DIR__ . '/views/partials/header.php'; ?>

<div class="page-header mb-4">
    <h1 class="h3 fw-bold text-primary">RFID Terdaftar</h1>
    <p class="text-muted">Kelola seluruh kartu/tag RFID yang terdaftar di sistem</p>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i>RFID berhasil dihapus
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-credit-card-2-front me-2"></i>Daftar RFID
        </h5>
        <a href="add-rfid.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah RFID
        </a>
    </div>
    
    <form method="GET" class="p-3 border-bottom">
        <div class="row g-2">
            <div class="col-12 col-md-5">
                <input type="text" name="search" placeholder="Cari kode RFID, nama pemilik..." value="<?= htmlspecialchars($search) ?>" class="form-control form-control-sm">
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
        <?php if ($rfids): ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Kode RFID</th>
                    <th>Nama Pemilik</th>
                    <th>ID Pengguna</th>
                    <th>Status</th>
                    <th>Tanggal Daftar</th>
                    <th class="px-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rfids as $rfid): ?>
                <tr>
                    <td><code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($rfid['rfid_code']) ?></code></td>
                    <td class="fw-medium"><?= htmlspecialchars($rfid['owner_name']) ?></td>
                    <td><?= $rfid['owner_identifier'] ? htmlspecialchars($rfid['owner_identifier']) : '<span class="text-muted">-</span>' ?></td>
                    <td>
                        <span class="badge badge-status <?= $rfid['status'] === 'active' ? 'badge-success-custom' : 'badge-danger-custom' ?>">
                            <?= $rfid['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($rfid['created_at'])) ?></td>
                    <td class="px-4">
                        <div class="d-flex gap-1">
                            <form method="POST">
                                <input type="hidden" name="toggle_id" value="<?= $rfid['id'] ?>">
                                <button type="submit" class="btn btn-outline-<?= $rfid['status'] === 'active' ? 'warning' : 'success' ?> btn-sm">
                                    <i class="bi bi-toggle-<?= $rfid['status'] === 'active' ? 'off' : 'on' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Yakin hapus RFID ini?')">
                                <input type="hidden" name="delete_id" value="<?= $rfid['id'] ?>">
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
            <i class="bi bi-credit-card text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">Tidak ada data RFID</p>
            <a href="add-rfid.php" class="btn btn-primary mt-2">Tambah RFID Pertama</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>