<?php
$currentUser = getCurrentUser();
$db = Database::getInstance();
$currentUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$currentUser['id']]);
$unread = (int)$db->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0", [$currentUser['id']])['c'];
$recentNotifs = $db->fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$currentUser['id']]);
?>
<header class="topbar">
    <button class="topbar-menu-btn" id="sidebarOpen">☰</button>

    <div class="topbar-title" id="pageTitle">
        <?= $pageTitle ?? APP_NAME ?>
    </div>

    <div class="topbar-actions">
        <!-- Notifications -->
        <div class="notif-wrapper" id="notifWrapper">
            <button class="topbar-btn" id="notifBtn">
                🔔
                <?php if ($unread > 0): ?>
                <span class="topbar-badge"><?= $unread ?></span>
                <?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <span>Notifikasi</span>
                    <?php if ($unread > 0): ?>
                    <a href="<?= APP_URL ?>/dashboard/notifikasi.php" style="font-size:0.8rem;">Tandai semua dibaca</a>
                    <?php endif; ?>
                </div>
                <?php if (empty($recentNotifs)): ?>
                <div class="notif-empty">Tidak ada notifikasi</div>
                <?php else: ?>
                <?php
                $typeIcon = ['info'=>'ℹ️','success'=>'✅','warning'=>'⚠️','error'=>'❌'];
                foreach ($recentNotifs as $n):
                ?>
                <a href="<?= APP_URL ?>/dashboard/notifikasi.php" class="notif-item <?= $n['is_read'] ? '' : 'notif-item--unread' ?>">
                    <span class="notif-icon"><?= $typeIcon[$n['type']] ?? 'ℹ️' ?></span>
                    <div>
                        <strong><?= e($n['title']) ?></strong>
                        <p><?= e(substr($n['message'], 0, 60)) ?>...</p>
                        <small><?= timeAgo($n['created_at']) ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
                <a href="<?= APP_URL ?>/dashboard/notifikasi.php" class="notif-view-all">Lihat semua</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- User menu -->
        <a href="<?= APP_URL ?>/dashboard/profil.php" class="topbar-user">
            <div class="topbar-avatar">
                <?php if ($currentUser['profile_photo']): ?>
                <img src="<?= getUploadUrl($currentUser['profile_photo']) ?>" alt="">
                <?php else: ?>
                <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <span class="topbar-username"><?= e(explode(' ', $currentUser['full_name'])[0]) ?></span>
        </a>

        <a href="<?= APP_URL ?>/php/auth.php?action=logout" class="topbar-btn topbar-logout" title="Keluar">🚪</a>
    </div>
</header>
