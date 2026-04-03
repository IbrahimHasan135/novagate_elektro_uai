<?php
require_once __DIR__ . '/../config/database.php';

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
<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="page-header">
    <h1>Daftarkan RFID Baru</h1>
    <p>Tambahkan kartu/tag RFID baru ke sistem</p>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Form Pendaftaran RFID</h2>
            <a href="/rfids" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="rfid_code">Kode RFID *</label>
                <input type="text" id="rfid_code" name="rfid_code" class="form-control" 
                       placeholder="Contoh: A1B2C3D4" required 
                       value="<?= htmlspecialchars($_POST['rfid_code'] ?? '') ?>">
                <small class="text-muted">Kode yang dibaca oleh device RFID</small>
            </div>
            
            <div class="form-group">
                <label for="owner_name">Nama Pemilik *</label>
                <input type="text" id="owner_name" name="owner_name" class="form-control" 
                       placeholder="Contoh: John Doe" required
                       value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="owner_identifier">ID Pengguna (Opsional)</label>
                <input type="text" id="owner_identifier" name="owner_identifier" class="form-control" 
                       placeholder="Contoh: NIM, NIK, ID Karyawan"
                       value="<?= htmlspecialchars($_POST['owner_identifier'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Catatan (Opsional)</label>
            <textarea id="notes" name="notes" class="form-control" rows="3" 
                      placeholder="Informasi tambahan..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Simpan RFID</button>
            <a href="/rfids" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>