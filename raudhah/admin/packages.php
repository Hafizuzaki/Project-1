<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$flash = getFlash();

// Handle package actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token tidak valid');
        redirect(APP_URL . '/admin/packages.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_package') {
        $name        = sanitize($_POST['name'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $duration    = (int)($_POST['duration_days'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $features    = sanitize($_POST['features'] ?? '');

        if (!$name || !$price) {
            setFlash('error', 'Nama dan harga wajib diisi');
        } else {
            $db->execute(
                "INSERT INTO packages (name, price, duration_days, description, features, status) VALUES (?,?,?,?,?,'active')",
                [$name, $price, $duration, $description, $features]
            );
            setFlash('success', 'Paket berhasil ditambahkan');
        }
        redirect(APP_URL . '/admin/packages.php');
    }

    if ($action === 'toggle_package') {
        $pkgId = (int)($_POST['package_id'] ?? 0);
        $pkg   = $db->fetchOne("SELECT * FROM packages WHERE id = ?", [$pkgId]);
        if ($pkg) {
            $newStatus = ($pkg['status'] === 'active') ? 'inactive' : 'active';
            $db->execute("UPDATE packages SET status = ? WHERE id = ?", [$newStatus, $pkgId]);
            setFlash('success', 'Status paket diperbarui');
        }
        redirect(APP_URL . '/admin/packages.php');
    }
}

$packages = $db->fetchAll("SELECT p.*, (SELECT COUNT(*) FROM registrations r WHERE r.package_id = p.id AND r.payment_status = 'verified') as reg_count FROM packages p ORDER BY p.price ASC");

$pageTitle = 'Kelola Paket Umroh';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
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
            <h1 class="page-title">Kelola Paket Umroh</h1>
            <button class="btn btn-primary" onclick="openModal('createPackageModal')">+ Tambah Paket</button>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?> <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <div class="packages-admin-grid">
            <?php foreach ($packages as $pkg): ?>
            <div class="card package-admin-card <?= $pkg['status'] !== 'active' ? 'package-inactive' : '' ?>">
                <div class="card-header">
                    <h3 class="card-title"><?= e($pkg['name']) ?></h3>
                    <span class="badge badge-<?= $pkg['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($pkg['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="package-price-display"><?= formatRupiah((float)$pkg['price']) ?></div>
                    <div class="package-meta">
                        <span>⏱ <?= $pkg['duration_days'] ?> Hari</span>
                        <span>👥 <?= $pkg['reg_count'] ?> Pendaftar</span>
                    </div>
                    <?php if ($pkg['description']): ?>
                    <p style="color:var(--text-muted);font-size:0.9rem;margin:1rem 0;"><?= e($pkg['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($pkg['features']): ?>
                    <ul style="font-size:0.85rem;color:var(--text-muted);padding-left:1.2rem;">
                        <?php foreach (explode("\n", $pkg['features']) as $f): ?>
                        <?php if (trim($f)): ?>
                        <li><?= e(trim($f)) ?></li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <div style="margin-top:1.5rem;">
                        <form method="POST" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle_package">
                            <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-<?= $pkg['status'] === 'active' ? 'warning' : 'success' ?>">
                                <?= $pkg['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Create Package Modal -->
<div class="modal-overlay" id="createPackageModal">
    <div class="modal modal--lg">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Paket Umroh</h3>
            <button class="modal-close" onclick="closeModal('createPackageModal')">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create_package">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Paket <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" placeholder="Contoh: Paket Reguler" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga (Rp) <span class="required">*</span></label>
                        <input type="number" name="price" class="form-input" min="0" step="500000" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Durasi (Hari)</label>
                    <input type="number" name="duration_days" class="form-input" min="1" value="12">
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-input form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Fitur / Fasilitas (satu per baris)</label>
                    <textarea name="features" class="form-input form-textarea" rows="5" placeholder="Hotel Bintang 5&#10;Visa Umroh&#10;Muthowif Profesional&#10;Tiket Penerbangan PP"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('createPackageModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>
