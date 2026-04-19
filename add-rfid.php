<?php
require_once __DIR__ . '/config/database.php';

$page = 'add-rfid';
$pdo = getDB();
$devicesStmt = $pdo->query("SELECT id, device_name, access_group, is_active FROM devices WHERE is_active = 1 ORDER BY device_name ASC");
$devices = $devicesStmt->fetchAll();

$error = '';
$success = '';
$pairingCompleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfidCode = trim($_POST['rfid_code'] ?? '');
    $ownerName = trim($_POST['owner_name'] ?? '');
    $ownerIdentifier = trim($_POST['owner_identifier'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');
    $pairingId = (int) ($_POST['pairing_id'] ?? 0);

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

            if ($pairingId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE rfid_pairings
                    SET status = 'completed',
                        paired_rfid_code = ?,
                        completed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$rfidCode, $pairingId]);
                $pairingCompleted = true;
            }

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

<div class="card mb-4">
    <div class="card-header py-3">
        <h5 class="card-title mb-0 fw-semibold">
            <i class="bi bi-broadcast-pin me-2"></i>Open Pairing
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label for="pairing_device_id" class="form-label fw-medium">Pilih Device</label>
                <select id="pairing_device_id" class="form-select">
                    <option value="">Pilih device untuk pairing...</option>
                    <?php foreach ($devices as $device): ?>
                    <option value="<?= (int) $device['id'] ?>">
                        <?= htmlspecialchars($device['device_name']) ?><?= $device['access_group'] ? ' - ' . htmlspecialchars($device['access_group']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">RFID yang ditolak dari device ini akan otomatis masuk ke kolom kode RFID.</div>
            </div>
            <div class="col-12 col-lg-7 d-flex flex-wrap gap-2">
                <button type="button" id="start-pairing-btn" class="btn btn-primary">
                    <i class="bi bi-play-circle me-1"></i> Mulai Pairing
                </button>
                <button type="button" id="stop-pairing-btn" class="btn btn-outline-danger" disabled>
                    <i class="bi bi-stop-circle me-1"></i> Stop Pairing
                </button>
            </div>
        </div>

        <div id="pairing-status" class="alert alert-info mt-3 mb-0 d-none"></div>
    </div>
</div>

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
            <input type="hidden" id="pairing_id" name="pairing_id" value="">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label for="rfid_code" class="form-label fw-medium">Kode RFID *</label>
                    <input type="text" id="rfid_code" name="rfid_code" class="form-control"
                           placeholder="Contoh: A1B2C3D4" required
                           value="<?= htmlspecialchars($_POST['rfid_code'] ?? '') ?>">
                    <div class="form-text">Bisa diisi manual atau otomatis lewat pairing.</div>
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

<script>
const pairingApiUrl = 'api/v1/rfid-pairing.php';
const startPairingBtn = document.getElementById('start-pairing-btn');
const stopPairingBtn = document.getElementById('stop-pairing-btn');
const pairingDeviceSelect = document.getElementById('pairing_device_id');
const pairingStatusEl = document.getElementById('pairing-status');
const pairingIdInput = document.getElementById('pairing_id');
const rfidCodeInput = document.getElementById('rfid_code');
let pairingTimer = null;

function setPairingStatus(message, type = 'info') {
    pairingStatusEl.className = `alert alert-${type} mt-3 mb-0`;
    pairingStatusEl.textContent = message;
    pairingStatusEl.classList.remove('d-none');
}

function clearPairingStatus() {
    pairingStatusEl.classList.add('d-none');
    pairingStatusEl.textContent = '';
}

async function postPairing(action, payload) {
    const formData = new URLSearchParams();
    formData.append('action', action);

    Object.entries(payload).forEach(([key, value]) => {
        formData.append(key, value);
    });

    const response = await fetch(pairingApiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
    });

    return response.json();
}

async function pollPairing() {
    const pairingId = pairingIdInput.value;
    if (!pairingId) {
        return;
    }

    try {
        const response = await fetch(`${pairingApiUrl}?action=poll&pairing_id=${encodeURIComponent(pairingId)}`);
        const result = await response.json();

        if (!result.success) {
            setPairingStatus(result.message || 'Gagal membaca status pairing', 'danger');
            stopPolling(false);
            return;
        }

        const data = result.data;
        if (data.status !== 'open' && !data.rfid_code) {
            setPairingStatus('Pairing sudah tidak aktif.', 'secondary');
            stopPolling(false);
            return;
        }

        if (data.rfid_code) {
            rfidCodeInput.value = data.rfid_code;
            setPairingStatus(`RFID ${data.rfid_code} tertangkap dari ${data.device_name}. Tinggal lengkapi data lalu simpan.`, 'success');
        } else {
            setPairingStatus(`Menunggu kartu ditap di ${data.device_name}${data.access_group ? ' (' + data.access_group + ')' : ''}...`, 'info');
        }
    } catch (error) {
        setPairingStatus('Gagal polling pairing. Cek koneksi lalu coba lagi.', 'danger');
        stopPolling(false);
    }
}

function startPolling() {
    stopPolling(false);
    pollPairing();
    pairingTimer = window.setInterval(pollPairing, 2000);
}

function stopPolling(clearState = true) {
    if (pairingTimer) {
        window.clearInterval(pairingTimer);
        pairingTimer = null;
    }

    if (clearState) {
        pairingIdInput.value = '';
        stopPairingBtn.disabled = true;
        startPairingBtn.disabled = false;
    }
}

startPairingBtn.addEventListener('click', async () => {
    const deviceId = pairingDeviceSelect.value;
    if (!deviceId) {
        setPairingStatus('Pilih device dulu untuk membuka pairing.', 'warning');
        return;
    }

    startPairingBtn.disabled = true;

    try {
        const result = await postPairing('start', { device_id: deviceId });
        if (!result.success) {
            setPairingStatus(result.message || 'Gagal memulai pairing', 'danger');
            startPairingBtn.disabled = false;
            return;
        }

        pairingIdInput.value = result.data.pairing_id;
        stopPairingBtn.disabled = false;
        setPairingStatus(`Pairing aktif di ${result.data.device_name}${result.data.access_group ? ' (' + result.data.access_group + ')' : ''}.`, 'info');
        startPolling();
    } catch (error) {
        setPairingStatus('Gagal memulai pairing. Coba lagi.', 'danger');
        startPairingBtn.disabled = false;
    }
});

stopPairingBtn.addEventListener('click', async () => {
    const pairingId = pairingIdInput.value;
    if (!pairingId) {
        stopPolling();
        clearPairingStatus();
        return;
    }

    try {
        await postPairing('stop', { pairing_id: pairingId });
    } catch (error) {
    }

    stopPolling();
    setPairingStatus('Pairing dihentikan.', 'secondary');
});

<?php if ($pairingCompleted): ?>
stopPolling();
setPairingStatus('RFID berhasil disimpan dan pairing ditutup.', 'success');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
