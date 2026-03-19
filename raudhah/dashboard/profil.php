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

// User bank accounts feature requires schema with user_id column in bank_accounts table
// Temporarily disabled pending schema update
$userBanks = [];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — <?= APP_NAME ?></title>
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
            <h1 class="page-title">Profil Saya</h1>
            <p class="page-subtitle">Kelola informasi pribadi dan rekening bank Anda</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?>
            <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <div class="dashboard-grid-2">
            <!-- Profile Info -->
            <div class="card" id="profile">
                <div class="card-header">
                    <h3 class="card-title">Informasi Pribadi</h3>
                </div>
                <div class="card-body">
                    <!-- Avatar -->
                    <div style="text-align:center; margin-bottom:2rem;">
                        <div class="profile-avatar-large" id="avatarPreviewWrapper">
                            <?php if ($user['profile_photo']): ?>
                            <img src="<?= getUploadUrl($user['profile_photo']) ?>" alt="Foto Profil" id="avatarPreview">
                            <?php else: ?>
                            <div class="avatar-placeholder-large" id="avatarPlaceholder">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" action="<?= APP_URL ?>/php/user_actions.php" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" name="profile_photo" id="profilePhoto" class="form-input" accept="image/*">
                            <small class="form-hint">JPG, PNG, max 2MB (opsional)</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                            <input type="text" name="full_name" class="form-input" value="<?= e($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-input" value="<?= e($user['username']) ?>" disabled>
                            <small class="form-hint">Username tidak dapat diubah</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" value="<?= e($user['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor HP</label>
                            <input type="tel" name="phone" class="form-input" value="<?= e($user['phone'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-input form-textarea" rows="3"><?= e($user['address'] ?? '') ?></textarea>
                        </div>

                        <hr style="margin: 1.5rem 0; border-color: var(--border);">
                        <h4 style="margin-bottom:1rem; color:var(--text-muted);">Ganti Password (opsional)</h4>

                        <div class="form-group">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="current_password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-input" minlength="8">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-input">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
                    </form>
                </div>
            </div>

            <!-- Bank Accounts -->
            <div>
                <div class="card" id="bank">
                    <div class="card-header">
                        <h3 class="card-title">Rekening Bank</h3>
                        <button class="btn btn-sm btn-outline" onclick="openModal('addBankModal')">+ Tambah</button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($userBanks)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🏦</div>
                            <p>Belum ada rekening bank</p>
                            <button class="btn btn-primary btn-sm" onclick="openModal('addBankModal')">Tambah Rekening</button>
                        </div>
                        <?php else: ?>
                        <div style="padding:1rem;">
                            <?php foreach ($userBanks as $bank): ?>
                            <div class="bank-card" style="margin-bottom:1rem; position:relative;">
                                <?php if ($bank['is_primary']): ?>
                                <span class="badge badge-success" style="position:absolute;top:-8px;right:8px;font-size:0.7rem;">Utama</span>
                                <?php endif; ?>
                                <div class="bank-logo"><?= strtoupper(substr($bank['bank_name'], 0, 3)) ?></div>
                                <div class="bank-info">
                                    <strong><?= e($bank['bank_name']) ?></strong>
                                    <span><?= e($bank['account_number']) ?></span>
                                    <span><?= e($bank['account_name']) ?></span>
                                </div>
                                <form method="POST" action="<?= APP_URL ?>/php/user_actions.php" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_bank">
                                    <input type="hidden" name="bank_id" value="<?= $bank['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus rekening ini?">🗑</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Account Info Card -->
                <div class="card" style="margin-top:1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title">Info Akun</h3>
                    </div>
                    <div class="card-body">
                        <div class="reg-info-grid">
                            <div class="reg-info-item">
                                <span class="reg-label">Kode Referral</span>
                                <span class="reg-value referral-code-display" style="font-size:1.1rem;letter-spacing:2px;">
                                    <?= e($user['referral_code']) ?>
                                </span>
                            </div>
                            <div class="reg-info-item">
                                <span class="reg-label">Status Akun</span>
                                <span class="reg-value">
                                    <?php
                                    $sBadge = ['active'=>'success','pending'=>'warning','suspended'=>'danger'];
                                    $sLabel = ['active'=>'Aktif','pending'=>'Menunggu','suspended'=>'Ditangguhkan'];
                                    ?>
                                    <span class="badge badge-<?= $sBadge[$user['status']] ?? 'secondary' ?>">
                                        <?= $sLabel[$user['status']] ?? $user['status'] ?>
                                    </span>
                                </span>
                            </div>
                            <div class="reg-info-item">
                                <span class="reg-label">Bergabung</span>
                                <span class="reg-value"><?= formatDate($user['created_at'], 'd F Y') ?></span>
                            </div>
                            <div class="reg-info-item">
                                <span class="reg-label">Posisi di Tree</span>
                                <span class="reg-value"><?= $user['position'] ? ucfirst($user['position']) : 'Root' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Bank Modal -->
<div class="modal-overlay" id="addBankModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Rekening Bank</h3>
            <button class="modal-close" onclick="closeModal('addBankModal')">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/php/user_actions.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_bank">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Bank <span class="required">*</span></label>
                    <input type="text" name="bank_name" class="form-input" placeholder="Contoh: BCA, BRI, Mandiri" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Rekening <span class="required">*</span></label>
                    <input type="text" name="account_number" class="form-input" placeholder="Nomor rekening" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Pemilik Rekening <span class="required">*</span></label>
                    <input type="text" name="account_name" class="form-input" placeholder="Nama sesuai buku tabungan" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="is_primary" value="1"> Jadikan rekening utama
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addBankModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
<script>
// Profile photo preview
document.getElementById('profilePhoto')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('avatarPlaceholder')?.remove();
        let img = document.getElementById('avatarPreview');
        if (!img) {
            img = document.createElement('img');
            img.id = 'avatarPreview';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
            document.getElementById('avatarPreviewWrapper').appendChild(img);
        }
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>
