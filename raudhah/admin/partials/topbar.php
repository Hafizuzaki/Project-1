<?php
$currentAdmin = getCurrentUser();
$db = Database::getInstance();
$currentAdmin = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$currentAdmin['id']]);
$adminUnread = (int)$db->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0", [$currentAdmin['id']])['c'];
$adminNotifs = $db->fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$currentAdmin['id']]);
?>
<header class="topbar topbar--admin">
    <button class="topbar-menu-btn" id="sidebarOpen">☰</button>
    <div class="topbar-title"><?= $pageTitle ?? 'Admin Panel' ?></div>
    <div class="topbar-actions">
        <div class="notif-wrapper" id="notifWrapper">
            <button class="topbar-btn" id="notifBtn">
                🔔
                <?php if ($adminUnread > 0): ?>
                <span class="topbar-badge"><?= $adminUnread ?></span>
                <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header"><span>Notifikasi</span></div>
                <?php if (empty($adminNotifs)): ?>
                <div class="notif-empty">Tidak ada notifikasi</div>
                <?php else: ?>
                <?php foreach ($adminNotifs as $n): ?>
                <div class="notif-item <?= $n['is_read'] ? '' : 'notif-item--unread' ?>">
                    <span class="notif-icon"><?= ['info'=>'ℹ️','success'=>'✅','warning'=>'⚠️','error'=>'❌'][$n['type']] ?? 'ℹ️' ?></span>
                    <div>
                        <strong><?= e($n['title']) ?></strong>
                        <p><?= e(substr($n['message'], 0, 50)) ?>...</p>
                        <small><?= timeAgo($n['created_at']) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <span class="topbar-user">
            <div class="topbar-avatar" style="background:var(--gold);color:#000;">A</div>
            <span class="topbar-username"><?= e(explode(' ', $currentAdmin['full_name'])[0]) ?></span>
        </span>
        <a href="<?= APP_URL ?>/php/auth.php?action=logout" class="topbar-btn" title="Keluar">🚪</a>
    </div>
</header>
