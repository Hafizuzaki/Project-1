<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';
require_once __DIR__ . '/../php/mlm.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$flash = getFlash();

$search = sanitize($_GET['q'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where  = "WHERE u.role = 'member'";
$params = [];
if ($search) {
    $where .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.referral_code LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($status) {
    $where .= " AND u.status = ?";
    $params[] = $status;
}

$total = (int)$db->fetchOne("SELECT COUNT(*) as c FROM users u $where", $params)['c'];
$users = $db->fetchAll(
    "SELECT u.*, ref.full_name AS referrer_name, ref.referral_code AS referrer_code
     FROM users u
     LEFT JOIN users ref ON ref.id = u.referred_by
     $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);

// Referrers for new user form
$referrers = $db->fetchAll("SELECT id, full_name, username, referral_code FROM users WHERE status = 'active' ORDER BY full_name");

$pageTitle = 'Kelola Member';
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
            <h1 class="page-title">Kelola Member</h1>
            <button class="btn btn-primary" onclick="openModal('createUserModal')">+ Tambah Member</button>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
            <?= e($flash['message']) ?> <button class="alert-close">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-body" style="padding:1rem;">
                <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                    <input type="text" name="q" class="form-input" style="flex:1;min-width:200px;" placeholder="Cari nama/username/kode..." value="<?= e($search) ?>">
                    <select name="status" class="form-input form-select" style="width:160px;">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspend</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search || $status): ?>
                    <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Member</h3>
                <span class="badge badge-primary"><?= $total ?> member</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Kode Referral</th>
                            <th>Referrer</th>
                            <th>Posisi</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <strong><?= e($u['full_name']) ?></strong><br>
                                <small class="text-muted">@<?= e($u['username']) ?></small>
                            </td>
                            <td>
                                <code><?= e($u['referral_code']) ?></code>
                            </td>
                            <td>
                                <?php if ($u['referrer_name']): ?>
                                <small><?= e($u['referrer_name']) ?></small><br>
                                <small class="text-muted"><?= e($u['referrer_code']) ?></small>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['position']): ?>
                                <span class="badge badge-<?= $u['position'] === 'left' ? 'info' : 'purple' ?>">
                                    <?= ucfirst($u['position']) ?>
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $sb = ['active'=>'success','pending'=>'warning','suspended'=>'danger'];
                                $sl = ['active'=>'Aktif','pending'=>'Pending','suspended'=>'Suspend'];
                                ?>
                                <span class="badge badge-<?= $sb[$u['status']] ?? 'secondary' ?>">
                                    <?= $sl[$u['status']] ?? $u['status'] ?>
                                </span>
                            </td>
                            <td><small><?= formatDate($u['created_at'], 'd/m/Y') ?></small></td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($u['status'] === 'active'): ?>
                                    <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="suspend_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" data-confirm="Suspend member ini?">Suspend</button>
                                    </form>
                                    <?php elseif ($u['status'] === 'suspended'): ?>
                                    <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="activate_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Aktifkan</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total > $perPage): ?>
                <div class="pagination-wrapper">
                    <?= renderPagination($total, $perPage, $page, APP_URL . '/admin/users.php?' . http_build_query(['q'=>$search,'status'=>$status])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal modal--lg">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Member Baru</h3>
            <button class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/php/admin_actions.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create_user">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="full_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-input" required pattern="[a-zA-Z0-9_]+">
                        <small class="form-hint">Hanya huruf, angka, underscore</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <input type="password" name="password" class="form-input" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nomor HP</label>
                        <input type="tel" name="phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Referrer (Upline) <span class="required">*</span></label>
                        <select name="referrer_id" class="form-input form-select" required>
                            <option value="">— Pilih Referrer —</option>
                            <?php foreach ($referrers as $ref): ?>
                            <option value="<?= $ref['id'] ?>">
                                <?= e($ref['full_name']) ?> (<?= e($ref['referral_code']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Member baru akan ditempatkan di bawah referrer ini secara otomatis (BFS)</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('createUserModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Buat Akun</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>
