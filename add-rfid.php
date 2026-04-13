<?php
require_once __DIR__ . '/config/database.php';

$page = 'add-rfid';
$pdo = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfidCode = trim($_POST['rfid_code'] ?? '');
    $ownerName = trim($_POST['owner_name'] ?? '');
    $ownerIdentifier = trim($_POST['owner_identifier'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($rfidCode) || empty($ownerName)) {
        $error = 'Kode RFID dan nama pemilik wajib diisi';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM rfids WHERE rfid_code = ?");
        $stmt->execute([$rfidCode]);
        if ($stmt->fetch()) {
            $error = 'Kode RFID sudah terdaftar';
        } else {
            $stmt = $pdo->prepare("INSERT INTO rfids (rfid_code, owner_name, owner_identifier, status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$rfidCode, $ownerName, $ownerIdentifier ?: null, $status, $notes ?: null]);
            $success = 'RFID berhasil didaftarkan';
        }
    }
}
?>
<?php require_once __DIR__ . '/views/partials/header.php'; ?>

<div class="page-header mb-4">
    <h1 class="h3 fw-bold text-primary">Daftarkan RFID Baru</h1>
    <p class="text-muted">Tambahkan kartu/tag RFID baru ke sistem</p>
</div>

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

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-plus-circle me-2"></i>Form Pendaftaran RFID
        </h5>
        <a href="rfids.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
    
    <div class="card-body">
        <form method="POST">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label for="rfid_code" class="form-label fw-medium">Kode RFID *</label>
                    <input type="text" id="rfid_code" name="rfid_code" class="form-control" 
                           placeholder="Contoh: A1B2C3D4" required 
                           value="<?= htmlspecialchars($_POST['rfid_code'] ?? '') ?>">
                    <div class="form-text">Kode yang dibaca oleh device RFID</div>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="owner_name" class="form-label fw-medium">Nama Pemilik *</label>
                    <input type="text" id="owner_name" name="owner_name" class="form-control" 
                           placeholder="Contoh: John Doe" required
                           value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label for="owner_identifier" class="form-label fw-medium">ID Pengguna <span class="text-muted">(Opsional)</span></label>
                    <input type="text" id="owner_identifier" name="owner_identifier" class="form-control" 
                           placeholder="Contoh: NIM, NIK, ID Karyawan"
                           value="<?= htmlspecialchars($_POST['owner_identifier'] ?? '') ?>">
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="status" class="form-label fw-medium">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label fw-medium">Catatan <span class="text-muted">(Opsional)</span></label>
                <textarea id="notes" name="notes" class="form-control" rows="3" 
                          placeholder="Informasi tambahan..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Simpan RFID
                </button>
                <a href="rfids.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>