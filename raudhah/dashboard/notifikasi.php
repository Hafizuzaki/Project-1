<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/session.php';
require_once __DIR__ . '/../php/helpers.php';

startSession();
requireLogin();

$user = getCurrentUser();
$db   = Database::getInstance();

// Mark all as read
$db->execute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$user['id']]);

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$total = (int)$db->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id = ?", [$user['id']])['c'];
$notifications = $db->fetchAll(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$user['id'], $perPage, $offset]
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi — <?= APP_NAME ?></title>
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
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">Semua pemberitahuan untuk akun Anda</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Semua Notifikasi</h3>
                <span class="badge badge-primary"><?= $total ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔔</div>
                    <p>Tidak ada notifikasi</p>
                </div>
                <?php else: ?>
                <div class="notif-list">
                    <?php
                    $typeIcon = ['info'=>'ℹ️','success'=>'✅','warning'=>'⚠️','error'=>'❌'];
                    foreach ($notifications as $n):
                    ?>
                    <div class="notif-item notif-item--read">
                        <div class="notif-icon"><?= $typeIcon[$n['type']] ?? 'ℹ️' ?></div>
                        <div class="notif-body">
                            <strong><?= e($n['title']) ?></strong>
                            <p><?= e($n['message']) ?></p>
                            <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total > $perPage): ?>
                <div class="pagination-wrapper">
                    <?= renderPagination($total, $perPage, $page, APP_URL . '/dashboard/notifikasi.php') ?>
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
