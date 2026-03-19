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
$search  = sanitize($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($filter) { $where .= " AND c.status = ?"; $params[] = $filter; }
if ($search) { $where .= " AND (u.full_name LIKE ? OR u.username LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$total = (int)$db->fetchOne("SELECT COUNT(*) as cnt FROM commissions c JOIN users u ON u.id = c.user_id $where", $params)['cnt'];
$commissions = $db->fetchAll(
    "SELECT c.*,
            u.full_name, u.username, u.phone,
            fu.full_name AS from_name, fu.username AS from_username
     FROM commissions c
     JOIN users u ON u.id = c.user_id
     JOIN users fu ON fu.id = c.from_user_id
     $where ORDER BY c.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

// Summary stats
$stats = $db->fetchOne(
    "SELECT
        SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) AS total_pending,
        SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS total_paid,
        COUNT(CASE WHEN status='pending' THEN 1 END) AS count_pending,
        COUNT(CASE WHEN status='paid' THEN 1 END) AS count_paid
     FROM commissions"
);

$pageTitle = 'Kelola Komisi';
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
            <div>
                <h1 class="page-title">Kelola Komisi</h1>
                <p class="page-subtitle">Tandai komisi sudah dikirim → notifikasi otomatis ke WA member</p>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?> <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Stats Summary -->
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:2rem;">
            <div class="stat-card stat-card--warning">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-label">Komisi Pending</span>
                    <span class="stat-value"><?= formatRupiah((float)($stats['total_pending'] ?? 0)) ?></span>
                    <span class="stat-sub"><?= (int)($stats['count_pending'] ?? 0) ?> transaksi</span>
                </div>
            </div>
            <div class="stat-card stat-card--green">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-label">Sudah Dibayar</span>
                    <span class="stat-value"><?= formatRupiah((float)($stats['total_paid'] ?? 0)) ?></span>
                    <span class="stat-sub"><?= (int)($stats['count_paid'] ?? 0) ?> transaksi</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-body" style="padding:1rem;">
                <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                    <input type="text" name="q" class="form-input" style="flex:1;min-width:200px;"
                           placeholder="Cari nama member..." value="<?= e($search) ?>">
                    <select name="status" class="form-input form-select" style="width:160px;">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $filter==='pending'?'selected':'' ?>>Pending</option>
                        <option value="paid" <?= $filter==='paid'?'selected':'' ?>>Dibayar</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search || $filter): ?>
                    <a href="<?= APP_URL ?>/admin/commissions.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Batch Pay Form -->
        <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php" id="batchPayForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="pay_commission">

            <div class="card">
                <div class="card-header">
                    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                        <h3 class="card-title">Daftar Komisi</h3>
                        <span class="badge badge-primary"><?= $total ?></span>
                        <div style="margin-left:auto;display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <?php if ($filter === 'pending' || !$filter): ?>
                            <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline">Pilih Semua</button>
                            <button type="submit" class="btn btn-sm btn-success" id="paySelectedBtn"
                                    data-confirm="Tandai komisi terpilih sebagai SUDAH DIBAYAR dan kirim notifikasi WA?"
                                    disabled>
                                💸 Bayar Terpilih (<span id="selectedCount">0</span>)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($commissions)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💰</div>
                        <p>Tidak ada data komisi</p>
                    </div>
                    <?php else: ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <?php if ($filter === 'pending' || !$filter): ?>
                                <th style="width:40px;"><input type="checkbox" id="checkAll"></th>
                                <?php endif; ?>
                                <th>Penerima Komisi</th>
                                <th>Rekening</th>
                                <th>Dari Referral</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <?php if ($filter === 'paid'): ?><th>Dibayar</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissions as $c): ?>
                            <tr>
                                <?php if ($filter === 'pending' || !$filter): ?>
                                <td>
                                    <?php if ($c['status'] === 'pending'): ?>
                                    <input type="checkbox" name="commission_ids[]"
                                           value="<?= $c['id'] ?>" class="comm-check">
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <strong><?= e($c['full_name']) ?></strong><br>
                                    <small class="text-muted">@<?= e($c['username']) ?></small>
                                    <?php if ($c['phone']): ?>
                                    <br><small>📱 <?= e($c['phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size:0.8rem;">📋 Lihat profil member</span>
                                </td>
                                <td>
                                    <strong><?= e($c['from_name']) ?></strong><br>
                                    <small class="text-muted">@<?= e($c['from_username']) ?></small>
                                </td>
                                <td class="font-bold" style="color:var(--gold);">
                                    <?= formatRupiah((float)$c['amount']) ?>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">⏳ Pending</span>
                                    <?php elseif ($c['status'] === 'paid'): ?>
                                    <span class="badge badge-success">✅ Dibayar</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary"><?= e($c['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= formatDate($c['created_at'], 'd/m/Y H:i') ?></small></td>
                                <?php if ($filter === 'paid'): ?>
                                <td><small><?= $c['paid_at'] ? formatDate($c['paid_at'], 'd/m/Y H:i') : '—' ?></small></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($total > $perPage): ?>
                    <div class="pagination-wrapper">
                        <?= renderPagination($total, $perPage, $page, APP_URL . '/admin/commissions.php?status=' . $filter . '&q=' . urlencode($search)) ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
<script>
// Checkbox logic
const checkAll   = document.getElementById('checkAll');
const checks     = document.querySelectorAll('.comm-check');
const payBtn     = document.getElementById('paySelectedBtn');
const countSpan  = document.getElementById('selectedCount');
const selectAllBtn = document.getElementById('selectAllBtn');

function updatePayBtn() {
    const checked = document.querySelectorAll('.comm-check:checked').length;
    if (countSpan) countSpan.textContent = checked;
    if (payBtn) payBtn.disabled = checked === 0;
}

checkAll?.addEventListener('change', function() {
    checks.forEach(c => c.checked = this.checked);
    updatePayBtn();
});

checks.forEach(c => c.addEventListener('change', function() {
    if (checkAll) checkAll.checked = [...checks].every(ch => ch.checked);
    updatePayBtn();
}));

selectAllBtn?.addEventListener('click', function() {
    checks.forEach(c => c.checked = true);
    if (checkAll) checkAll.checked = true;
    updatePayBtn();
});

// Confirm before submit
document.getElementById('batchPayForm')?.addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.comm-check:checked').length;
    if (checked === 0) { e.preventDefault(); return; }
    const msg = payBtn?.dataset.confirm || 'Tandai komisi sebagai dibayar?';
    if (!confirm(msg)) e.preventDefault();
});
</script>
</body>
</html>
