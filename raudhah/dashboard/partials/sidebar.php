<?php
$currentUser = getCurrentUser();
$db = Database::getInstance();
$currentUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$currentUser['id']]);
$unread = (int)$db->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0", [$currentUser['id']])['c'];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= APP_URL ?>" class="sidebar-brand">
            <span class="brand-icon">☪️</span>
            <span class="brand-text">Raudhah</span>
        </a>
        <button class="sidebar-toggle" id="sidebarClose">✕</button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php if ($currentUser['profile_photo']): ?>
            <img src="<?= getUploadUrl($currentUser['profile_photo']) ?>" alt="Foto">
            <?php else: ?>
            <span><?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?></span>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <strong><?= e($currentUser['full_name']) ?></strong>
            <span><?= e($currentUser['referral_code']) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= APP_URL ?>/dashboard/index.php" class="sidebar-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="<?= APP_URL ?>/dashboard/pohon.php" class="sidebar-link <?= $currentPage === 'pohon.php' ? 'active' : '' ?>">
            <span class="nav-icon">🌳</span> Pohon Referral
        </a>
        <a href="<?= APP_URL ?>/dashboard/komisi.php" class="sidebar-link <?= $currentPage === 'komisi.php' ? 'active' : '' ?>">
            <span class="nav-icon">💰</span> Komisi & Penarikan
        </a>
        <a href="<?= APP_URL ?>/dashboard/payment.php" class="sidebar-link <?= $currentPage === 'payment.php' ? 'active' : '' ?>">
            <span class="nav-icon">💳</span> Upload Pembayaran
        </a>
        <a href="<?= APP_URL ?>/dashboard/notifikasi.php" class="sidebar-link <?= $currentPage === 'notifikasi.php' ? 'active' : '' ?>">
            <span class="nav-icon">🔔</span> Notifikasi
            <?php if ($unread > 0): ?>
            <span class="nav-badge"><?= $unread ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/dashboard/profil.php" class="sidebar-link <?= $currentPage === 'profil.php' ? 'active' : '' ?>">
            <span class="nav-icon">👤</span> Profil Saya
        </a>
        <div class="sidebar-divider"></div>
        <a href="<?= APP_URL ?>" class="sidebar-link" target="_blank">
            <span class="nav-icon">🌐</span> Halaman Utama
        </a>
        <a href="<?= APP_URL ?>/php/auth.php?action=logout" class="sidebar-link sidebar-link--danger">
            <span class="nav-icon">🚪</span> Keluar
        </a>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
