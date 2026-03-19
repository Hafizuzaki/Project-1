<?php
// dashboard/index.php - Dashboard User
require_once '../php/config.php';
require_once '../php/database.php';
require_once '../php/session.php';
require_once '../php/helpers.php';
require_once '../php/mlm.php';

startSession();
requireLogin();

$db   = Database::getInstance();
$user = getCurrentUser();
if (!$user) { logoutUser(); redirect(APP_URL . '/login.php'); }

$mlm  = new MLMTree();
$uid  = $user['id'];

// Statistik
$totalComm    = $user['total_commission'];
$availComm    = $totalComm - $user['withdrawn_commission'];
$treeStats    = $mlm->getTreeStats($uid);
$directCount  = $mlm->countDirectDownline($uid);
$regStatus    = $db->fetchOne("SELECT * FROM registrations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [$uid]);
$notifications = $db->fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$uid]);
$unreadCount   = $db->count("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$uid]);
$commissions   = $db->fetchAll(
    "SELECT c.*, u.full_name as from_name FROM commissions c
     JOIN users u ON c.from_user_id = u.id
     WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 5",
    [$uid]
);

$banks = $db->fetchAll("SELECT * FROM bank_accounts WHERE is_active = 1");
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PT Raudhah Amanah Wisata</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">

    <!-- Sidebar overlay (mobile) -->
    <div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:199;" class=""></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><img src="assets/images/logo.jpg" alt="Logo" style="max-width: 100%; height: auto;"></div>
            <div>
                <div class="logo-text">Raudhah Amanah</div>
                <div class="logo-sub">Member Portal</div>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?php if ($user['profile_photo']): ?>
                    <img src="<?= getUploadUrl($user['profile_photo']) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
                <div class="sidebar-user-role"><i class="fas fa-circle" style="font-size:0.5rem;color:#4ade80;"></i> Member Aktif</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu Utama</div>
            <a href="index.php"        class="sidebar-link active"><i class="fas fa-home"></i> Beranda</a>
            <a href="pohon.php"        class="sidebar-link"><i class="fas fa-sitemap"></i> Pohon Referral</a>
            <a href="komisi.php"       class="sidebar-link">
                <i class="fas fa-coins"></i> Komisi Saya
                <?php if ($availComm > 0): ?>
                    <span class="sidebar-badge" style="background:var(--gold);">Rp</span>
                <?php endif; ?>
            </a>
            <a href="payment.php"      class="sidebar-link"><i class="fas fa-upload"></i> Unggah Pembayaran</a>
            <a href="profil.php"       class="sidebar-link"><i class="fas fa-user-cog"></i> Profil Saya</a>

            <div class="nav-section-title" style="margin-top:1rem;">Info</div>
            <a href="notifikasi.php"   class="sidebar-link">
                <i class="fas fa-bell"></i> Notifikasi
                <?php if ($unreadCount > 0): ?>
                    <span class="sidebar-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="../index.php"     class="sidebar-link"><i class="fas fa-globe"></i> Website</a>
            <a href="../php/auth.php?action=logout" class="sidebar-link" data-confirm="Yakin ingin keluar?">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-center gap-2">
                <button id="sidebar-toggle" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--gray-600);padding:0.3rem;">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-title">Dashboard</div>
            </div>
            <div class="topbar-actions">
                <div style="position:relative;">
                    <button class="notif-btn" id="notif-btn">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                    </button>
                    <!-- Notif Dropdown -->
                    <div id="notif-dropdown" style="display:none;position:absolute;right:0;top:calc(100%+0.5rem);background:var(--white);border-radius:var(--radius-md);box-shadow:var(--shadow-xl);width:320px;z-index:500;border:1px solid var(--gray-200);overflow:hidden;">
                        <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;">
                            <strong style="font-size:0.9rem;color:var(--green-dark);">Notifikasi</strong>
                            <a href="notifikasi.php" style="font-size:0.8rem;color:var(--gold);">Lihat Semua</a>
                        </div>
                        <?php if ($notifications): ?>
                            <?php foreach ($notifications as $n): ?>
                                <div style="padding:0.9rem 1.2rem;border-bottom:1px solid var(--gray-100);background:<?= $n['is_read'] ? 'transparent' : 'var(--gold-pale)' ?>;">
                                    <div style="font-size:0.88rem;font-weight:600;color:var(--green-dark);"><?= e($n['title']) ?></div>
                                    <div style="font-size:0.82rem;color:var(--gray-600);margin-top:0.2rem;"><?= e($n['message']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--gray-400);margin-top:0.3rem;"><?= timeAgo($n['created_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:2rem;text-align:center;color:var(--gray-400);font-size:0.9rem;">Tidak ada notifikasi</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.6rem;font-size:0.9rem;color:var(--gray-700);">
                    <div class="sidebar-avatar" style="width:32px;height:32px;font-size:0.85rem;">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <?= e($user['full_name']) ?>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-auto-hide" data-dismissible data-timeout="6000">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Status Akun Warning -->
            <?php if (!$regStatus || $regStatus['payment_status'] !== 'verified'): ?>
                <div class="alert alert-warning" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <i class="fas fa-exclamation-triangle" style="font-size:1.3rem;"></i>
                    <div style="flex:1;">
                        <strong>Pembayaran Belum Diverifikasi</strong><br>
                        <small>Upload bukti transfer min. Rp 2.000.000 ke rekening PT agar akun Anda diaktifkan penuh.</small>
                    </div>
                    <a href="payment.php" class="btn btn-warning btn-sm" style="background:var(--warning);color:var(--white);border-color:var(--warning);">
                        Upload Bukti
                    </a>
                </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div>
                        <div class="stat-value"><?= formatRupiah($availComm) ?></div>
                        <div class="stat-label">Saldo Komisi Tersedia</div>
                        <div class="stat-change up"><i class="fas fa-arrow-up"></i> Total: <?= formatRupiah($totalComm) ?></div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="stat-value"><?= $directCount ?></div>
                        <div class="stat-label">Referral Langsung</div>
                        <div class="stat-change"><i class="fas fa-sitemap"></i> Total downline: <?= $treeStats['total'] ?></div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                    <div>
                        <div class="stat-value"><?= $treeStats['left_count'] ?> / <?= $treeStats['right_count'] ?></div>
                        <div class="stat-label">Kaki Kiri / Kanan</div>
                        <div class="stat-change"><i class="fas fa-info-circle"></i> Maks. 2 kaki langsung</div>
                    </div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div>
                        <div class="stat-value"><?= formatRupiah($user['withdrawn_commission']) ?></div>
                        <div class="stat-label">Total Dicairkan</div>
                        <div class="stat-change"><i class="fas fa-check-circle"></i> Komisi tersalurkan</div>
                    </div>
                </div>
            </div>

            <!-- Referral Code Widget -->
            <div class="referral-widget">
                <div class="row align-center gap-3 flex-wrap" style="position:relative;z-index:1;">
                    <div style="flex:1;min-width:200px;">
                        <div style="font-size:0.82rem;color:rgba(255,255,255,0.65);letter-spacing:0.05em;text-transform:uppercase;margin-bottom:0.3rem;">Kode Referral Anda</div>
                        <div class="referral-code-display">
                            <span class="referral-code-text"><?= e($user['referral_code']) ?></span>
                            <button class="copy-btn" data-copy="<?= e($user['referral_code']) ?>">
                                <i class="fas fa-copy"></i> Salin
                            </button>
                        </div>
                        <div style="font-size:0.82rem;color:rgba(255,255,255,0.65);">
                            Bagikan kode ini dan dapatkan komisi <strong style="color:var(--gold-light);">Rp 500.000</strong> per pendaftar baru
                        </div>
                    </div>
                    <div style="flex:0 0 auto;">
                        <a href="pohon.php" class="btn btn-outline-gold">
                            <i class="fas fa-sitemap"></i> Lihat Pohon Referral
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Commissions -->
            <div class="row gap-3">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-between align-center">
                            <h4><i class="fas fa-history"></i> Riwayat Komisi Terbaru</h4>
                            <a href="komisi.php" style="color:var(--gold-light);font-size:0.85rem;">Lihat Semua</a>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php if ($commissions): ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Dari</th>
                                                <th>Jumlah</th>
                                                <th>Status</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($commissions as $c): ?>
                                                <tr>
                                                    <td><strong><?= e($c['from_name']) ?></strong></td>
                                                    <td style="color:var(--gold);font-weight:700;"><?= formatRupiah($c['amount']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $c['status'] === 'paid' ? 'success' : ($c['status'] === 'approved' ? 'info' : 'warning') ?>">
                                                            <?= ucfirst($c['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td style="font-size:0.85rem;color:var(--gray-600);"><?= timeAgo($c['created_at']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-coins"></i>
                                    <h3>Belum ada komisi</h3>
                                    <p>Bagikan kode referral Anda untuk mendapatkan komisi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Rekening Bank PT -->
                <div class="col" style="flex:0 0 320px;">
                    <div class="card">
                        <div class="card-header card-gold">
                            <h4><i class="fas fa-university"></i> Rekening PT Raudhah</h4>
                        </div>
                        <div class="card-body">
                            <p style="font-size:0.85rem;color:var(--gray-600);margin-bottom:1rem;">
                                Transfer min. <strong>Rp 2.000.000</strong> ke salah satu rekening berikut:
                            </p>
                            <?php foreach ($banks as $b): ?>
                                <div style="background:var(--gray-100);border-radius:var(--radius-sm);padding:0.9rem;margin-bottom:0.75rem;">
                                    <div style="font-weight:700;color:var(--green-dark);font-size:0.9rem;"><?= e($b['bank_name']) ?></div>
                                    <div style="font-family:monospace;font-size:1.05rem;color:var(--gold);font-weight:700;margin:0.2rem 0;"><?= e($b['account_number']) ?></div>
                                    <div style="font-size:0.82rem;color:var(--gray-600);"><?= e($b['account_holder']) ?></div>
                                    <button class="copy-btn mt-1" data-copy="<?= e($b['account_number']) ?>" style="margin-top:0.5rem;">
                                        <i class="fas fa-copy"></i> Salin No. Rekening
                                    </button>
                                </div>
                            <?php endforeach; ?>
                            <a href="payment.php" class="btn btn-primary w-100 mt-2">
                                <i class="fas fa-upload"></i> Upload Bukti Transfer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end page-content -->
    </div><!-- end main-content -->
</div><!-- end dashboard-layout -->

<script src="../js/main.js"></script>
<script>
// Notif dropdown toggle
const notifBtn = document.getElementById('notif-btn');
const notifDd  = document.getElementById('notif-dropdown');
if (notifBtn && notifDd) {
    notifBtn.addEventListener('click', e => {
        e.stopPropagation();
        notifDd.style.display = notifDd.style.display === 'none' ? 'block' : 'none';
    });
    document.addEventListener('click', () => { notifDd.style.display = 'none'; });
}

// Sidebar toggle mobile
const sidebarToggle  = document.getElementById('sidebar-toggle');
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');

sidebarToggle?.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    sidebarOverlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
});
sidebarOverlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    sidebarOverlay.style.display = 'none';
});
</script>
</body>
</html>
