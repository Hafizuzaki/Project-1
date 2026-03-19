<?php
// dashboard/pohon.php - Halaman Pohon Referral
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

// Bangun tree hanya dari user ini ke bawah (BUKAN ke atas)
$treeData   = $mlm->buildTree($uid);
$treeStats  = $mlm->getTreeStats($uid);
$treeJson   = json_encode($treeData);
$unreadCount = $db->count("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$uid]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pohon Referral - PT Raudhah Amanah Wisata</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .tree-scroll-container {
            overflow-x: auto;
            overflow-y: auto;
            min-height: 500px;
            padding: 2rem;
            background: repeating-linear-gradient(
                0deg, transparent, transparent 40px, rgba(45,106,79,0.03) 40px, rgba(45,106,79,0.03) 41px
            ), repeating-linear-gradient(
                90deg, transparent, transparent 40px, rgba(45,106,79,0.03) 40px, rgba(45,106,79,0.03) 41px
            );
            border-radius: var(--radius-md);
            border: 1px solid var(--gray-200);
        }

        .tree-node-card { min-width: 130px; }

        /* node detail panel */
        #node-detail-panel {
            display: none;
            position: fixed;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 280px;
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            border: 2px solid var(--gold);
            z-index: 500;
            padding: 1.5rem;
        }
        .upload-area {
            border: 2px dashed var(--gold);
            border-radius: var(--radius-md);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        .upload-area:hover { background: var(--gold-pale); }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <div id="sidebar-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:199;"></div>

    <!-- SIDEBAR (reused) -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><img src="../assets/images/logo.jpg" alt="Logo" style="max-width: 100%; height: auto;"></div>
            <div>
                <div class="logo-text">Raudhah Amanah</div>
                <div class="logo-sub">Member Portal</div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
            <div>
                <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
                <div class="sidebar-user-role"><i class="fas fa-circle" style="font-size:0.5rem;color:#4ade80;"></i> Member Aktif</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu Utama</div>
            <a href="index.php"   class="sidebar-link"><i class="fas fa-home"></i> Beranda</a>
            <a href="pohon.php"   class="sidebar-link active"><i class="fas fa-sitemap"></i> Pohon Referral</a>
            <a href="komisi.php"  class="sidebar-link"><i class="fas fa-coins"></i> Komisi Saya</a>
            <a href="payment.php" class="sidebar-link"><i class="fas fa-upload"></i> Unggah Pembayaran</a>
            <a href="profil.php"  class="sidebar-link"><i class="fas fa-user-cog"></i> Profil Saya</a>
            <div class="nav-section-title" style="margin-top:1rem;">Info</div>
            <a href="notifikasi.php" class="sidebar-link">
                <i class="fas fa-bell"></i> Notifikasi
                <?php if ($unreadCount > 0): ?><span class="sidebar-badge"><?= $unreadCount ?></span><?php endif; ?>
            </a>
            <a href="../index.php" class="sidebar-link"><i class="fas fa-globe"></i> Website</a>
            <a href="../php/auth.php?action=logout" class="sidebar-link" data-confirm="Yakin ingin keluar?"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-center gap-2">
                <button id="sidebar-toggle" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--gray-600);">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-title">Pohon Referral Saya</div>
            </div>
            <div class="topbar-actions">
                <div style="font-size:0.88rem;color:var(--gray-600);">
                    <i class="fas fa-info-circle" style="color:var(--gold);"></i>
                    Anda hanya dapat melihat struktur di bawah Anda
                </div>
            </div>
        </header>

        <div class="page-content">
            <!-- Stats -->
            <div class="stats-grid mb-4">
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="stat-value"><?= $treeStats['total'] ?></div>
                        <div class="stat-label">Total Downline</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fas fa-arrow-left"></i></div>
                    <div>
                        <div class="stat-value"><?= $treeStats['left_count'] ?></div>
                        <div class="stat-label">Total Kaki Kiri</div>
                    </div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="fas fa-arrow-right"></i></div>
                    <div>
                        <div class="stat-value"><?= $treeStats['right_count'] ?></div>
                        <div class="stat-label">Total Kaki Kanan</div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="d-flex gap-3 flex-wrap mb-3">
                <?php $legends = [
                    ['is-root', 'var(--green-dark)', 'Anda (Root)'],
                    ['status-active', 'var(--success)', 'Aktif'],
                    ['status-pending', 'var(--warning)', 'Pending'],
                    ['empty-slot', 'var(--gray-400)', 'Slot Kosong'],
                ]; ?>
                <?php foreach ($legends as $l): ?>
                    <div class="d-flex align-center gap-1" style="font-size:0.82rem;color:var(--gray-600);">
                        <div style="width:12px;height:12px;border-radius:3px;background:<?= $l[1] ?>;"></div>
                        <?= $l[2] ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Tree Container -->
            <div class="card">
                <div class="card-header d-flex justify-between align-center">
                    <h4><i class="fas fa-sitemap"></i> Struktur Jaringan Referral</h4>
                    <div class="d-flex gap-2">
                        <button onclick="zoomIn()" class="btn btn-sm btn-outline"><i class="fas fa-search-plus"></i></button>
                        <button onclick="zoomOut()" class="btn btn-sm btn-outline"><i class="fas fa-search-minus"></i></button>
                        <button onclick="resetZoom()" class="btn btn-sm btn-outline"><i class="fas fa-undo"></i></button>
                    </div>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="tree-scroll-container" id="tree-container">
                        <div id="tree-render" style="transform-origin:top center;transition:transform 0.3s;display:inline-block;min-width:100%;"></div>
                    </div>
                </div>
            </div>

            <!-- Node Detail -->
            <div id="selected-node-info" class="card mt-3" style="display:none;">
                <div class="card-header card-gold">
                    <h4><i class="fas fa-user-circle"></i> Detail Anggota</h4>
                </div>
                <div class="card-body" id="node-detail-content">
                    <p style="color:var(--gray-600);">Klik pada node untuk melihat detail anggota.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/main.js"></script>
<script>
// Inject tree data from PHP
const TREE_DATA = <?= $treeJson ?>;

// ---- Tree Renderer ----
document.addEventListener('DOMContentLoaded', function () {
    const renderer = new ReferralTreeRenderer('tree-render', TREE_DATA);

    // Node click → show details
    document.addEventListener('nodeSelected', function (e) {
        fetchNodeDetail(e.detail.id);
    });
});

function fetchNodeDetail(nodeId) {
    fetch('../php/api_tree.php?node_id=' + nodeId + '&viewer=<?= $uid ?>')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const node = data.node;
            const panel = document.getElementById('selected-node-info');
            const content = document.getElementById('node-detail-content');
            panel.style.display = 'block';
            content.innerHTML = `
                <div class="d-flex gap-3 align-center mb-3">
                    <div class="sidebar-avatar" style="width:56px;height:56px;font-size:1.4rem;background:linear-gradient(135deg,var(--gold),var(--green));">
                        ${node.full_name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:1.05rem;color:var(--green-dark);">${node.full_name}</div>
                        <div style="font-size:0.85rem;color:var(--gray-600);">@${node.username}</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.88rem;">
                    <div><span style="color:var(--gray-600);">Kode Referral:</span><br><strong style="color:var(--gold);font-family:monospace;">${node.referral_code}</strong></div>
                    <div><span style="color:var(--gray-600);">Status:</span><br><span class="badge badge-${node.status==='active'?'success':'warning'}">${node.status}</span></div>
                    <div><span style="color:var(--gray-600);">Posisi:</span><br><strong>${node.position ? (node.position==='left'?'⬅ Kiri':'➡ Kanan') : 'Root'}</strong></div>
                    <div><span style="color:var(--gray-600);">Bergabung:</span><br><strong>${node.join_date}</strong></div>
                    <div><span style="color:var(--gray-600);">Kaki Kiri:</span><br><strong>${node.left_count || 0}</strong></div>
                    <div><span style="color:var(--gray-600);">Kaki Kanan:</span><br><strong>${node.right_count || 0}</strong></div>
                </div>
            `;
        })
        .catch(() => {});
}

// Zoom
let zoomLevel = 1;
function zoomIn()    { zoomLevel = Math.min(zoomLevel + 0.15, 2);   applyZoom(); }
function zoomOut()   { zoomLevel = Math.max(zoomLevel - 0.15, 0.4); applyZoom(); }
function resetZoom() { zoomLevel = 1; applyZoom(); }
function applyZoom() {
    document.getElementById('tree-render').style.transform = `scale(${zoomLevel})`;
}

// Sidebar mobile
document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
    const s = document.getElementById('sidebar');
    const o = document.getElementById('sidebar-overlay');
    s.classList.toggle('open');
    o.style.display = s.classList.contains('open') ? 'block' : 'none';
});
document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').style.display = 'none';
});
</script>
</body>
</html>
