<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireLogin();

$user = getCurrentUser();
$db   = Database::getInstance();

try {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
    if (!$user) {
        redirectTo('/login.php');
    }
} catch (Exception $e) {
    logActivity('error', "Failed to fetch user: " . $e->getMessage());
    die("Terjadi kesalahan saat memuat data pengguna.");
}

// Hitung komisi detail
try {
    $commStats = $db->fetchOne(
        "SELECT
            COUNT(*) AS total_trx,
            SUM(CASE WHEN status='paid'    THEN amount ELSE 0 END) AS total_paid,
            SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) AS total_pending,
            COUNT(CASE WHEN status='paid'    THEN 1 END) AS count_paid,
            COUNT(CASE WHEN status='pending' THEN 1 END) AS count_pending
         FROM commissions WHERE user_id = ?",
        [$user['id']]
    );
} catch (Exception $e) {
    logActivity('error', "Failed to fetch commission stats: " . $e->getMessage());
    $commStats = [
        'total_trx' => 0, 'total_paid' => 0, 'total_pending' => 0,
        'count_paid' => 0, 'count_pending' => 0
    ];
}

$totalPaid    = (float)($commStats['total_paid'] ?? 0);
$totalPending = (float)($commStats['total_pending'] ?? 0);
$totalAll     = $totalPaid + $totalPending;

// Pagination riwayat komisi
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$totalTrx    = (int)($commStats['total_trx'] ?? 0);

try {
    $commissions = $db->fetchAll(
        "SELECT c.*, u.full_name AS from_name, u.username AS from_username
         FROM commissions c
         LEFT JOIN users u ON u.id = c.from_user_id
         WHERE c.user_id = ?
         ORDER BY c.created_at DESC LIMIT ? OFFSET ?",
        [$user['id'], $perPage, $offset]
    );
} catch (Exception $e) {
    logActivity('error', "Failed to fetch commissions: " . $e->getMessage());
    $commissions = [];
}

// Withdrawal history
try {
    $withdrawals = $db->fetchAll(
        "SELECT * FROM withdrawals
         WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
        [$user['id']]
    );
} catch (Exception $e) {
    logActivity('error', "Failed to fetch withdrawals: " . $e->getMessage());
    $withdrawals = [];
}

// Note: user bank accounts feature requires schema with user_id in bank_accounts table
// For now, users will enter bank details directly in withdrawal form
$userBanks = [];

try {
    $hasPendingWd = (bool)$db->fetchOne("SELECT id FROM withdrawals WHERE user_id=? AND status='pending'", [$user['id']]);
} catch (Exception $e) {
    logActivity('error', "Failed to check pending withdrawal: " . $e->getMessage());
    $hasPendingWd = false;
}

// Saldo tersedia = komisi paid - yang sudah ditarik
$saldoTersedia = max(0, $totalPaid - (float)($user['withdrawn_commission'] ?? 0));

$flash = getFlash();
$pageTitle = 'Komisi & Penarikan';
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
            <h1 class="page-title">Komisi & Penarikan</h1>
            <p class="page-subtitle">Pantau komisi referral dan ajukan penarikan</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?> <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <!-- ============================================
             FRAME STATISTIK KOMISI (utama)
             ============================================ -->
        <div class="commission-frame">
            <div class="commission-frame__header">
                <span class="commission-frame__icon">💰</span>
                <h2 class="commission-frame__title">Ringkasan Komisi Referral</h2>
            </div>
            <div class="commission-frame__body">
                <!-- Total Keseluruhan -->
                <div class="comm-stat-main">
                    <span class="comm-stat-main__label">Total Komisi Seluruhnya</span>
                    <span class="comm-stat-main__value"><?= formatRupiah($totalAll) ?></span>
                    <span class="comm-stat-main__sub"><?= $totalTrx ?> transaksi referral</span>
                </div>

                <!-- Grid detail -->
                <div class="comm-stat-grid">
                    <!-- Sudah Dibayar -->
                    <div class="comm-stat-card comm-stat-card--paid">
                        <div class="comm-stat-card__icon">✅</div>
                        <div class="comm-stat-card__info">
                            <span class="comm-stat-card__label">Sudah Dibayar</span>
                            <span class="comm-stat-card__value"><?= formatRupiah($totalPaid) ?></span>
                            <span class="comm-stat-card__count"><?= (int)($commStats['count_paid'] ?? 0) ?> transaksi</span>
                        </div>
                    </div>

                    <!-- Pending / Belum Dibayar -->
                    <div class="comm-stat-card comm-stat-card--pending">
                        <div class="comm-stat-card__icon">⏳</div>
                        <div class="comm-stat-card__info">
                            <span class="comm-stat-card__label">Sedang Diproses</span>
                            <span class="comm-stat-card__value"><?= formatRupiah($totalPending) ?></span>
                            <span class="comm-stat-card__count"><?= (int)($commStats['count_pending'] ?? 0) ?> transaksi</span>
                        </div>
                    </div>

                    <!-- Saldo Tersedia (untuk ditarik) -->
                    <div class="comm-stat-card comm-stat-card--available">
                        <div class="comm-stat-card__icon">💳</div>
                        <div class="comm-stat-card__info">
                            <span class="comm-stat-card__label">Saldo Dapat Ditarik</span>
                            <span class="comm-stat-card__value"><?= formatRupiah($saldoTersedia) ?></span>
                            <span class="comm-stat-card__count">dari komisi yang sudah dibayar</span>
                        </div>
                    </div>

                    <!-- Sudah Ditarik -->
                    <div class="comm-stat-card comm-stat-card--withdrawn">
                        <div class="comm-stat-card__icon">📤</div>
                        <div class="comm-stat-card__info">
                            <span class="comm-stat-card__label">Sudah Ditarik</span>
                            <span class="comm-stat-card__value"><?= formatRupiah((float)($user['withdrawn_commission'] ?? 0)) ?></span>
                            <span class="comm-stat-card__count">total penarikan disetujui</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <?php if ($totalAll > 0): ?>
                <div class="comm-progress">
                    <div class="comm-progress__label">
                        <span>Sudah Dibayar</span>
                        <span><?= $totalAll > 0 ? round($totalPaid / $totalAll * 100) : 0 ?>%</span>
                    </div>
                    <div class="comm-progress__bar">
                        <div class="comm-progress__fill comm-progress__fill--paid"
                             style="width: <?= $totalAll > 0 ? ($totalPaid / $totalAll * 100) : 0 ?>%"></div>
                        <div class="comm-progress__fill comm-progress__fill--pending"
                             style="width: <?= $totalAll > 0 ? ($totalPending / $totalAll * 100) : 0 ?>%"></div>
                    </div>
                    <div class="comm-progress__legend">
                        <span class="legend-paid">● Dibayar</span>
                        <span class="legend-pending">● Pending</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- / FRAME STATISTIK -->

        <div class="dashboard-grid-2" style="margin-top:2rem;">
            <!-- Withdrawal Request -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ajukan Penarikan</h3>
                </div>
                <div class="card-body">
                    <?php if ($hasPendingWd): ?>
                    <div class="alert alert-warning">Ada penarikan yang sedang diproses. Tunggu hingga selesai.</div>
                    <?php elseif ($saldoTersedia < 100000): ?>
                    <div class="alert alert-info">
                        Saldo tersedia minimum penarikan <strong>Rp 100.000</strong>.<br>
                        Saldo dapat ditarik Anda: <strong><?= formatRupiah($saldoTersedia) ?></strong>
                        <?php if ($totalPending > 0): ?>
                        <br><small class="text-muted">Masih ada <?= formatRupiah($totalPending) ?> komisi yang belum dibayar admin.</small>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <form method="POST" action="<?= APP_URL ?>/php/user_actions.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="request_withdrawal">
                        <div class="form-group">
                            <label class="form-label">Jumlah <span class="required">*</span></label>
                            <input type="number" name="amount" class="form-input"
                                   min="100000" max="<?= $saldoTersedia ?>"
                                   step="50000" placeholder="Min. Rp 100.000" required>
                            <small class="form-hint">Saldo tersedia: <?= formatRupiah($saldoTersedia) ?></small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rekening Tujuan <span class="required">*</span></label>
                            <?php if (empty($userBanks)): ?>
                            <div class="form-group">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" name="bank_name" class="form-input" placeholder="e.g., BCA, Mandiri, BNI" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nomor Rekening</label>
                                <input type="text" name="bank_account" class="form-input" placeholder="Nomor rekening Anda" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nama Pemilik Rekening</label>
                                <input type="text" name="bank_holder" class="form-input" placeholder="Nama sesuai buku tabungan" required>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-input form-textarea" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Ajukan Penarikan</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Withdrawal History -->
            <div class="card">
                <div class="card-header"><h3 class="card-title">Riwayat Penarikan</h3></div>
                <div class="card-body p-0">
                    <?php if (empty($withdrawals)): ?>
                    <div class="empty-state"><div class="empty-icon">📭</div><p>Belum ada riwayat</p></div>
                    <?php else: ?>
                    <table class="table">
                        <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Bank</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($withdrawals as $w):
                            $wBadge = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                            $wLabel = ['pending'=>'Menunggu','approved'=>'Disetujui','rejected'=>'Ditolak'];
                            ?>
                            <tr>
                                <td><?= formatDate($w['created_at'], 'd/m/Y') ?></td>
                                <td><?= formatRupiah((float)$w['amount']) ?></td>
                                <td><small><?= e($w['bank_name'] ?? '-') ?></small></td>
                                <td><span class="badge badge-<?= $wBadge[$w['status']]??'secondary' ?>"><?= $wLabel[$w['status']]??$w['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Commission History Table -->
        <div class="card" style="margin-top:2rem;">
            <div class="card-header">
                <h3 class="card-title">Riwayat Komisi</h3>
                <span class="badge badge-primary"><?= $totalTrx ?> transaksi</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($commissions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🏆</div>
                    <p>Belum ada komisi. Bagikan kode referral Anda!</p>
                </div>
                <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr><th>#</th><th>Dari Referral</th><th>Jumlah</th><th>Status</th><th>Tanggal</th><th>Dibayar</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commissions as $i => $c):
                        $cBadge = ['pending'=>'warning','paid'=>'success','cancelled'=>'danger','approved'=>'info'];
                        $cLabel = ['pending'=>'Pending','paid'=>'Dibayar','cancelled'=>'Batal','approved'=>'Disetujui'];
                        ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><strong><?= e($c['from_name'] ?? '-') ?></strong><br><small class="text-muted">@<?= e($c['from_username'] ?? '') ?></small></td>
                            <td class="font-bold" style="color:var(--gold);"><?= formatRupiah((float)$c['amount']) ?></td>
                            <td>
                                <span class="badge badge-<?= $cBadge[$c['status']]??'secondary' ?>">
                                    <?= $cLabel[$c['status']]??$c['status'] ?>
                                </span>
                            </td>
                            <td><small><?= formatDate($c['created_at'], 'd/m/Y') ?></small></td>
                            <td><small><?= $c['paid_at'] ? formatDate($c['paid_at'], 'd/m/Y') : '—' ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($totalTrx > $perPage): ?>
                <div class="pagination-wrapper">
                    <?= renderPagination($totalTrx, $perPage, $page, APP_URL . '/dashboard/komisi.php') ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /dashboard-content -->
</div><!-- /dashboard-main -->

<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>