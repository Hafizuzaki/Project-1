<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$flash = getFlash();

$filter  = sanitize($_GET['status'] ?? 'pending');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = $filter ? "WHERE w.status = '$filter'" : "";
$total = (int)$db->fetchOne("SELECT COUNT(*) as c FROM withdrawals w $where", [])['c'];
$withdrawals = $db->fetchAll(
    "SELECT w.*, u.full_name, u.username
     FROM withdrawals w
     LEFT JOIN users u ON u.id = w.user_id
     $where ORDER BY w.created_at DESC LIMIT $perPage OFFSET $offset"
);

$pageTitle = 'Kelola Penarikan';
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
            <h1 class="page-title">Kelola Penarikan</h1>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?> <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tab-bar" style="margin-bottom:1.5rem;">
            <a href="?status=pending" class="tab-item <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="?status=approved" class="tab-item <?= $filter === 'approved' ? 'active' : '' ?>">Disetujui</a>
            <a href="?status=rejected" class="tab-item <?= $filter === 'rejected' ? 'active' : '' ?>">Ditolak</a>
            <a href="?status=" class="tab-item <?= $filter === '' ? 'active' : '' ?>">Semua</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Penarikan</h3>
                <span class="badge badge-primary"><?= $total ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($withdrawals)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💸</div>
                    <p>Tidak ada permintaan penarikan</p>
                </div>
                <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Jumlah</th>
                            <th>Rekening Tujuan</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $w): ?>
                        <tr>
                            <td>
                                <strong><?= e($w['full_name']) ?></strong><br>
                                <small>@<?= e($w['username']) ?></small>
                            </td>
                            <td class="font-bold"><?= formatRupiah((float)$w['amount']) ?></td>
                            <td>
                                <strong><?= e($w['bank_name'] ?? '-') ?></strong><br>
                                <small><?= e($w['bank_account'] ?? '-') ?></small><br>
                                <small class="text-muted"><?= e($w['bank_holder'] ?? '-') ?></small>
                            </td>
                            <td><small><?= e($w['notes'] ?? '-') ?></small></td>
                            <td>
                                <?php
                                $badge = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                                $label = ['pending'=>'Pending','approved'=>'Disetujui','rejected'=>'Ditolak'];
                                ?>
                                <span class="badge badge-<?= $badge[$w['status']] ?? 'secondary' ?>">
                                    <?= $label[$w['status']] ?? $w['status'] ?>
                                </span>
                                <?php if ($w['processed_at']): ?>
                                <br><small class="text-muted"><?= formatDate($w['processed_at'], 'd/m/Y') ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= formatDate($w['created_at'], 'd/m/Y H:i') ?></small></td>
                            <td>
                                <?php if ($w['status'] === 'pending'): ?>
                                <div class="action-btns">
                                    <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="process_withdrawal">
                                        <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                        <input type="hidden" name="withdrawal_action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success" data-confirm="Setujui penarikan ini?">✅ Setujui</button>
                                    </form>
                                    <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="process_withdrawal">
                                        <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                        <input type="hidden" name="withdrawal_action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger" data-confirm="Tolak penarikan ini?">❌ Tolak</button>
                                    </form>
                                </div>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total > $perPage): ?>
                <div class="pagination-wrapper">
                    <?= renderPagination($total, $perPage, $page, APP_URL . '/admin/withdrawals.php?status=' . $filter) ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>
