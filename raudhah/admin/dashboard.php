<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireAdmin();

$db = Database::getInstance();

// Stats - Count users (user role = member), with error handling
try {
    $totalMembers    = (int)($db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'user'", [])['c'] ?? 0);
    $activeMembers   = (int)($db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'user' AND status = 'active'", [])['c'] ?? 0);
    $pendingMembers  = (int)($db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'user' AND status = 'pending'", [])['c'] ?? 0);
    $pendingPayments = (int)($db->fetchOne("SELECT COUNT(*) as c FROM registrations WHERE payment_status = 'pending_verification'", [])['c'] ?? 0);
    $pendingWithdrawals = (int)($db->fetchOne("SELECT COUNT(*) as c FROM withdrawals WHERE status = 'pending'", [])['c'] ?? 0);
    $totalRevenue    = (float)($db->fetchOne("SELECT SUM(payment_amount) as s FROM registrations WHERE payment_status = 'verified'", [])['s'] ?? 0);
    $totalCommissions = (float)($db->fetchOne("SELECT SUM(amount) as s FROM commissions WHERE status = 'paid'", [])['s'] ?? 0);
    
    // Recent registrations with null checks
    $recentRegs = $db->fetchAll(
        "SELECT r.*, u.full_name, u.username, p.name AS package_name
         FROM registrations r
         LEFT JOIN users u ON u.id = r.user_id
         LEFT JOIN packages p ON p.id = r.package_id
         ORDER BY r.created_at DESC LIMIT 8"
    ) ?? [];
    
    // Recent members with null checks
    $recentMembers = $db->fetchAll(
        "SELECT u.*, ref.full_name AS referrer_name
         FROM users u
         LEFT JOIN users ref ON ref.id = u.referred_by
         WHERE u.role = 'user'
         ORDER BY u.created_at DESC LIMIT 8"
    ) ?? [];
} catch (Exception $e) {
    logActivity($_SESSION['user_id'] ?? null, 'DASHBOARD_ERROR', 'Error loading stats: ' . $e->getMessage());
    // Set default values if error
    $totalMembers = $activeMembers = $pendingMembers = $pendingPayments = $pendingWithdrawals = 0;
    $totalRevenue = $totalCommissions = 0.0;
    $recentRegs = $recentMembers = [];
}

$pageTitle = 'Dashboard Admin';
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
            <h1 class="page-title">Dashboard Admin</h1>
            <p class="page-subtitle">Selamat datang kembali, <?= e(explode(' ', getCurrentUser()['full_name'])[0]) ?>!</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card stat-card--gold">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <span class="stat-label">Total Member</span>
                    <span class="stat-value"><?= $totalMembers ?></span>
                </div>
            </div>
            <div class="stat-card stat-card--green">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-label">Member Aktif</span>
                    <span class="stat-value"><?= $activeMembers ?></span>
                </div>
            </div>
            <div class="stat-card stat-card--warning">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-label">Menunggu Verifikasi</span>
                    <span class="stat-value"><?= $pendingPayments ?></span>
                </div>
            </div>
            <div class="stat-card stat-card--purple">
                <div class="stat-icon">💸</div>
                <div class="stat-info">
                    <span class="stat-label">Penarikan Pending</span>
                    <span class="stat-value"><?= $pendingWithdrawals ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <span class="stat-label">Total Pendapatan</span>
                    <span class="stat-value" style="font-size:1rem;"><?= formatRupiah($totalRevenue) ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-info">
                    <span class="stat-label">Total Komisi Dibayar</span>
                    <span class="stat-value" style="font-size:1rem;"><?= formatRupiah($totalCommissions) ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if ($pendingPayments > 0 || $pendingWithdrawals > 0): ?>
        <div class="alert alert-warning" style="margin: 1.5rem 0;">
            <strong>Tindakan Diperlukan:</strong>
            <?php if ($pendingPayments > 0): ?>
            <a href="<?= APP_URL ?>/admin/payments.php" style="margin-left:1rem;">
                📋 <?= $pendingPayments ?> pembayaran menunggu verifikasi
            </a>
            <?php endif; ?>
            <?php if ($pendingWithdrawals > 0): ?>
            <a href="<?= APP_URL ?>/admin/withdrawals.php" style="margin-left:1rem;">
                💸 <?= $pendingWithdrawals ?> penarikan menunggu proses
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-grid-2">
            <!-- Recent Registrations -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pendaftaran Terbaru</h3>
                    <a href="<?= APP_URL ?>/admin/payments.php" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr><th>Member</th><th>Paket</th><th>Bayar</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRegs as $reg): ?>
                            <tr>
                                <td><?= e($reg['full_name']) ?><br><small>@<?= e($reg['username']) ?></small></td>
                                <td><?= e($reg['package_name']) ?></td>
                                <td><?= formatRupiah((float)$reg['amount_paid']) ?></td>
                                <td>
                                    <?php
                                    $badge = ['pending'=>'warning','verified'=>'success','rejected'=>'danger'];
                                    $label = ['pending'=>'Pending','verified'=>'Verified','rejected'=>'Ditolak'];
                                    ?>
                                    <span class="badge badge-<?= $badge[$reg['status']] ?? 'secondary' ?>">
                                        <?= $label[$reg['status']] ?? $reg['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Members -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Member Terbaru</h3>
                    <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-sm btn-outline">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr><th>Member</th><th>Referral</th><th>Status</th><th>Tgl</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMembers as $m): ?>
                            <tr>
                                <td><?= e($m['full_name']) ?><br><small class="text-muted"><?= e($m['referral_code']) ?></small></td>
                                <td><small><?= e($m['referrer_name'] ?? '-') ?></small></td>
                                <td>
                                    <?php
                                    $sb = ['active'=>'success','pending'=>'warning','suspended'=>'danger'];
                                    $sl = ['active'=>'Aktif','pending'=>'Pending','suspended'=>'Suspend'];
                                    ?>
                                    <span class="badge badge-<?= $sb[$m['status']] ?? 'secondary' ?>">
                                        <?= $sl[$m['status']] ?? $m['status'] ?>
                                    </span>
                                </td>
                                <td><small><?= formatDate($m['created_at'], 'd/m/Y') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>
