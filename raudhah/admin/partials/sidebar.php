<?php
$currentAdmin = getCurrentUser();
$db = Database::getInstance();
$currentAdmin = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$currentAdmin['id']]);
$adminUnread = (int)$db->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0", [$currentAdmin['id']])['c'];
$pendingPayments = (int)$db->fetchOne("SELECT COUNT(*) as c FROM registrations WHERE payment_status = 'pending_verification'", [])['c'];
$pendingWithdrawals = (int)$db->fetchOne("SELECT COUNT(*) as c FROM withdrawals WHERE status = 'pending'", [])['c'];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="sidebar-brand">
            <span class="brand-icon">⚙️</span>
            <span class="brand-text">Admin Panel</span>
        </a>
        <button class="sidebar-toggle" id="sidebarClose">✕</button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar" style="background:var(--gold);">
            <span style="color:#000;"><?= strtoupper(substr($currentAdmin['full_name'], 0, 1)) ?></span>
        </div>
        <div class="sidebar-user-info">
            <strong><?= e($currentAdmin['full_name']) ?></strong>
            <span>Administrator</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <a href="<?= APP_URL ?>/admin/users.php" class="sidebar-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Kelola Member
        </a>
        <a href="<?= APP_URL ?>/admin/payments.php" class="sidebar-link <?= $currentPage === 'payments.php' ? 'active' : '' ?>">
            <span class="nav-icon">💳</span> Verifikasi Pembayaran
            <?php if ($pendingPayments > 0): ?>
            <span class="nav-badge"><?= $pendingPayments ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/admin/commissions.php" class="sidebar-link <?= $currentPage === 'commissions.php' ? 'active' : '' ?>">
            <span class="nav-icon">💰</span> Kelola Komisi
            <?php
            $pendingComm = (int)$db->fetchOne("SELECT COUNT(*) as c FROM commissions WHERE status='pending'", [])['c'];
            if ($pendingComm > 0): ?>
            <span class="nav-badge"><?= $pendingComm ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/admin/withdrawals.php" class="sidebar-link <?= $currentPage === 'withdrawals.php' ? 'active' : '' ?>">
            <span class="nav-icon">💸</span> Penarikan Dana
            <?php if ($pendingWithdrawals > 0): ?>
            <span class="nav-badge"><?= $pendingWithdrawals ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/admin/packages.php" class="sidebar-link <?= $currentPage === 'packages.php' ? 'active' : '' ?>">
            <span class="nav-icon">📦</span> Paket Umroh
        </a>
        <div class="sidebar-divider"></div>
        <a href="<?= APP_URL ?>" class="sidebar-link" target="_blank">
            <span class="nav-icon">🌐</span> Lihat Website
        </a>
        <a href="<?= APP_URL ?>/php/auth.php?action=logout" class="sidebar-link sidebar-link--danger">
            <span class="nav-icon">🚪</span> Keluar
        </a>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
