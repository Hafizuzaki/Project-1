<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$flash = getFlash();

$filter = sanitize($_GET['status'] ?? 'pending_verification');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where  = $filter ? "WHERE r.payment_status = '$filter'" : "";
$total  = (int)$db->fetchOne("SELECT COUNT(*) as c FROM registrations r $where", [])['c'];
$registrations = $db->fetchAll(
    "SELECT r.*, u.full_name, u.username, u.referral_code, u.status AS user_status,
            p.name AS package_name, p.price AS package_price
     FROM registrations r
     LEFT JOIN users u ON u.id = r.user_id
     LEFT JOIN packages p ON p.id = r.package_id
     $where ORDER BY r.created_at DESC LIMIT $perPage OFFSET $offset"
);

$pageTitle = 'Verifikasi Pembayaran';
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
            <h1 class="page-title">Verifikasi Pembayaran</h1>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?> <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Status Tabs -->
        <div class="tab-bar" style="margin-bottom:1.5rem;">
            <a href="?status=pending_verification" class="tab-item <?= $filter === 'pending_verification' ? 'active' : '' ?>">Pending Verifikasi</a>
            <a href="?status=verified" class="tab-item <?= $filter === 'verified' ? 'active' : '' ?>">Terverifikasi</a>
            <a href="?status=rejected" class="tab-item <?= $filter === 'rejected' ? 'active' : '' ?>">Ditolak</a>
            <a href="?status=" class="tab-item <?= $filter === '' ? 'active' : '' ?>">Semua</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pembayaran</h3>
                <span class="badge badge-primary"><?= $total ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($registrations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <p>Tidak ada data pembayaran</p>
                </div>
                <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Reg</th>
                            <th>Member</th>
                            <th>Paket</th>
                            <th>Jumlah</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><code><?= e($reg['registration_number']) ?></code></td>
                            <td>
                                <strong><?= e($reg['full_name']) ?></strong><br>
                                <small>@<?= e($reg['username']) ?></small>
                            </td>
                            <td><?= e($reg['package_name']) ?></td>
                            <td><?= formatRupiah((float)$reg['payment_amount']) ?></td>
                            <td>
                                <?php if ($reg['payment_proof']): ?>
                                <a href="<?= getUploadUrl($reg['payment_proof']) ?>" target="_blank" class="btn btn-sm btn-outline">
                                    🖼 Lihat
                                </a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusMap = ['unpaid'=>'danger','pending_verification'=>'warning','verified'=>'success','rejected'=>'danger'];
                                $labelMap = ['unpaid'=>'Belum Bayar','pending_verification'=>'Pending','verified'=>'Terverifikasi','rejected'=>'Ditolak'];
                                ?>
                                <span class="badge badge-<?= $statusMap[$reg['payment_status']] ?? 'secondary' ?>">
                                    <?= $labelMap[$reg['payment_status']] ?? $reg['payment_status'] ?>
                                </span>
                            </td>
                            <td><small><?= formatDate($reg['created_at'], 'd/m/Y H:i') ?></small></td>
                            <td>
                                <?php if ($reg['payment_status'] === 'pending_verification'): ?>
                                <div class="action-btns">
                                    <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="verify_payment">
                                        <input type="hidden" name="registration_id" value="<?= $reg['id'] ?>">
                                        <input type="hidden" name="verify_action" value="verify">
                                        <button type="submit" class="btn btn-sm btn-success" data-confirm="Verifikasi pembayaran ini?">✅ Verifikasi</button>
                                    </form>
                                    <button class="btn btn-sm btn-danger" onclick="openRejectModal(<?= $reg['id'] ?>)">❌ Tolak</button>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total > $perPage): ?>
                <div class="pagination-wrapper">
                    <?= renderPagination($total, $perPage, $page, APP_URL . '/admin/payments.php?status=' . $filter) ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tolak Pembayaran</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="verify_payment">
            <input type="hidden" name="verify_action" value="reject">
            <input type="hidden" name="registration_id" id="rejectRegId" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Alasan Penolakan <span class="required">*</span></label>
                    <textarea name="rejection_reason" class="form-input form-textarea" rows="3"
                              placeholder="Contoh: Jumlah transfer tidak sesuai, bukti tidak jelas..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
<script>
function openRejectModal(regId) {
    document.getElementById('rejectRegId').value = regId;
    openModal('rejectModal');
}
</script>
</body>
</html>
