<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireLogin();

$user = getCurrentUser();
$db   = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);

$packages     = $db->fetchAll("SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC");
$ptBankAccounts = $db->fetchAll("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY id ASC");

$registration = $db->fetchOne(
    "SELECT r.*, p.name AS package_name FROM registrations r
     LEFT JOIN packages p ON p.id = r.package_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 1",
    [$user['id']]
);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Pembayaran — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="dashboard-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1 class="page-title">Upload Pembayaran</h1>
            <p class="page-subtitle">Upload bukti pembayaran untuk mengaktifkan akun Anda</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?>
            <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <?php if ($registration && in_array($registration['status'], ['pending', 'verified'])): ?>
        <!-- Registration Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Status Pendaftaran</h3>
            </div>
            <div class="card-body">
                <div class="registration-status">
                    <div class="reg-info-grid">
                        <div class="reg-info-item">
                            <span class="reg-label">No. Registrasi</span>
                            <span class="reg-value"><?= e($registration['registration_number']) ?></span>
                        </div>
                        <div class="reg-info-item">
                            <span class="reg-label">Paket</span>
                            <span class="reg-value"><?= e($registration['package_name']) ?></span>
                        </div>
                        <div class="reg-info-item">
                            <span class="reg-label">Jumlah Dibayar</span>
                            <span class="reg-value"><?= formatRupiah((float)$registration['amount_paid']) ?></span>
                        </div>
                        <div class="reg-info-item">
                            <span class="reg-label">Status</span>
                            <span class="reg-value">
                                <?php if ($registration['status'] === 'pending'): ?>
                                <span class="badge badge-warning">⏳ Menunggu Verifikasi</span>
                                <?php elseif ($registration['status'] === 'verified'): ?>
                                <span class="badge badge-success">✅ Terverifikasi</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="reg-info-item">
                            <span class="reg-label">Tanggal Upload</span>
                            <span class="reg-value"><?= formatDate($registration['created_at']) ?></span>
                        </div>
                    </div>

                    <?php if ($registration['proof_image']): ?>
                    <div style="margin-top:1.5rem;">
                        <p class="reg-label" style="margin-bottom:0.5rem;">Bukti Pembayaran:</p>
                        <img src="<?= getUploadUrl($registration['proof_image']) ?>" 
                             alt="Bukti Pembayaran"
                             style="max-width:300px;border-radius:8px;border:2px solid var(--border);">
                    </div>
                    <?php endif; ?>

                    <?php if ($registration['status'] === 'pending'): ?>
                    <div class="alert alert-info" style="margin-top:1.5rem;">
                        <strong>Pembayaran Anda sedang diverifikasi oleh admin.</strong><br>
                        Proses verifikasi biasanya membutuhkan waktu 1x24 jam pada hari kerja.
                        Hubungi kami jika ada pertanyaan.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($registration['status'] === 'pending'): ?>
        <!-- Allow re-upload if pending -->
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h3 class="card-title">Upload Ulang Bukti</h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom:1rem;">Jika bukti pembayaran kurang jelas, Anda dapat mengupload ulang.</p>
                <form method="POST" action="<?= APP_URL ?>/php/user_actions.php" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="upload_payment">
                    <input type="hidden" name="package_id" value="<?= $registration['package_id'] ?>">
                    <input type="hidden" name="amount_paid" value="<?= $registration['amount_paid'] ?>">
                    <div class="form-group">
                        <label class="form-label">Bukti Pembayaran Baru</label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <input type="file" name="proof_image" id="proofImage" accept="image/*" required>
                            <div class="file-upload-placeholder">
                                <span class="upload-icon">📷</span>
                                <p>Klik atau drag foto bukti transfer</p>
                                <small>JPG, PNG, max 5MB</small>
                            </div>
                            <img id="imagePreview" style="display:none; max-width:100%; border-radius:8px;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload Ulang</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Upload Form -->
        <div class="dashboard-grid-2">
            <!-- Bank Accounts PT -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rekening PT Raudhah</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted" style="margin-bottom:1.5rem;font-size:0.9rem;">
                        Transfer pembayaran ke salah satu rekening berikut. Minimum transfer <strong>Rp 2.000.000</strong>.
                    </p>
                    <?php foreach ($ptBankAccounts as $bank): ?>
                    <div class="bank-card" style="margin-bottom:1rem;">
                        <div class="bank-logo"><?= strtoupper(substr($bank['bank_name'], 0, 3)) ?></div>
                        <div class="bank-info">
                            <strong><?= e($bank['bank_name']) ?></strong>
                            <span class="bank-number" data-copy="<?= e($bank['account_number']) ?>"><?= e($bank['account_number']) ?></span>
                            <span class="bank-name"><?= e($bank['account_holder']) ?></span>
                        </div>
                        <button class="btn btn-sm btn-outline copy-btn" data-clipboard="<?= e($bank['account_number']) ?>">Salin</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Upload Bukti</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= APP_URL ?>/php/user_actions.php" enctype="multipart/form-data" id="paymentForm">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="upload_payment">

                        <div class="form-group">
                            <label class="form-label">Pilih Paket <span class="required">*</span></label>
                            <select name="package_id" class="form-input form-select" required>
                                <option value="">— Pilih Paket —</option>
                                <?php foreach ($packages as $pkg): ?>
                                <option value="<?= $pkg['id'] ?>" data-price="<?= $pkg['price'] ?>">
                                    <?= e($pkg['name']) ?> — <?= formatRupiah((float)$pkg['price']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jumlah Transfer (Rp) <span class="required">*</span></label>
                            <input type="number"
                                   name="amount_paid"
                                   id="amountPaid"
                                   class="form-input"
                                   min="2000000"
                                   placeholder="Minimum Rp 2.000.000"
                                   required>
                            <small class="form-hint" id="amountHint">Minimum pembayaran: Rp 2.000.000</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bukti Transfer <span class="required">*</span></label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" name="proof_image" id="proofImage" accept="image/*" required>
                                <div class="file-upload-placeholder">
                                    <span class="upload-icon">📷</span>
                                    <p>Klik atau drag foto bukti transfer</p>
                                    <small>JPG, PNG, max 5MB</small>
                                </div>
                                <img id="imagePreview" style="display:none; max-width:100%; border-radius:8px;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Upload Bukti Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
<script>
// Auto-fill amount from package selection
document.querySelector('select[name="package_id"]')?.addEventListener('change', function() {
    const price = this.options[this.selectedIndex]?.dataset.price;
    if (price) {
        document.getElementById('amountPaid').value = price;
        document.getElementById('amountHint').textContent = 'Harga paket: ' + new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR'}).format(price);
    }
});
</script>
</body>
</html>
