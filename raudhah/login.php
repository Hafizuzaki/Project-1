<?php
// login.php - Halaman Login
require_once 'php/config.php';
require_once 'php/database.php';
require_once 'php/session.php';
require_once 'php/helpers.php';

startSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect(isAdmin() ? APP_URL . '/admin/dashboard.php' : APP_URL . '/dashboard/index.php');
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - PT Raudhah Amanah Wisata</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <!-- Left Side -->
    <div class="auth-left">
        <div style="text-align:center;position:relative;z-index:1;">
            <div style="margin-bottom:1.5rem;"><img src="assets/images/logo.jpg" alt="Logo" style="height: 120px; width: auto; position: center;"></div>
            <h2 style="color:var(--white);font-family:var(--font-serif);font-size:2rem;margin-bottom:0.75rem;">
                Bismillahirrahmanirrahim
            </h2>
            <p style="color:rgba(255,255,255,0.7);font-size:1.05rem;line-height:1.8;max-width:380px;">
                Selamat datang kembali di portal member PT Raudhah Amanah Wisata.
                Kelola perjalanan umroh dan jaringan referral Anda.
            </p>
            <hr style="border-color:rgba(255,255,255,0.1);margin:2rem 0;">
            <div style="display:flex;justify-content:center;gap:2.5rem;">
                <?php foreach ([['5200+','Jamaah'],['14+','Tahun'],['98%','Kepuasan']] as $s): ?>
                    <div>
                        <div style="font-family:var(--font-serif);font-size:1.8rem;color:var(--gold-light);font-weight:700;"><?= $s[0] ?></div>
                        <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><?= $s[1] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Side -->
    <div class="auth-right">
        <div class="auth-form-wrapper">
            <a href="index.php" style="display:flex;align-items:center;gap:0.5rem;color:var(--gray-600);font-size:0.9rem;margin-bottom:2rem;">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>

            <h2 class="auth-form-title">Selamat Datang</h2>
            <p class="auth-form-subtitle">Masuk ke akun Anda untuk melanjutkan</p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-auto-hide" data-dismissible data-timeout="6000">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form action="php/auth.php" method="POST" id="login-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label class="form-label">Username atau Email</label>
                    <div style="position:relative;">
                        <i class="fas fa-user" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--gray-400);"></i>
                        <input type="text" name="username" class="form-control" style="padding-left:2.5rem;"
                               placeholder="Masukkan username atau email" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;justify-content:space-between;">
                        Password
                        <a href="lupa-password.php" style="font-size:0.82rem;color:var(--gold);">Lupa password?</a>
                    </label>
                    <div style="position:relative;">
                        <i class="fas fa-lock" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--gray-400);"></i>
                        <input type="password" name="password" id="password-input" class="form-control" style="padding-left:2.5rem;padding-right:3rem;"
                               placeholder="Masukkan password" required>
                        <button type="button" id="toggle-pass" style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray-400);cursor:pointer;">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" style="margin-top:0.5rem;padding:0.85rem;">
                    <i class="fas fa-sign-in-alt"></i> Masuk ke Akun
                </button>
            </form>

            <hr class="divider">

            <div style="background:var(--gold-pale);border-radius:var(--radius-md);padding:1.2rem;border:1px solid var(--gold-pale);">
                <div style="font-size:0.9rem;color:var(--gray-600);font-weight:600;margin-bottom:0.5rem;">
                    <i class="fas fa-info-circle" style="color:var(--gold);"></i> Cara Membuat Akun
                </div>
                <p style="font-size:0.85rem;color:var(--gray-600);line-height:1.7;margin:0;">
                    Akun tidak dapat dibuat mandiri. Anda harus dihubungi/mendaftarkan diri melalui
                    <strong>anggota aktif</strong> yang memiliki kode referral, atau langsung menghubungi
                    <strong>admin</strong> kami untuk proses pendaftaran.
                </p>
                <div style="margin-top:0.75rem;">
                    <?php $wa = 'https://wa.me/' . getSiteSetting('site_whatsapp', '6281234567890') . '?text=Saya ingin mendaftar member PT Raudhah Amanah Wisata'; ?>
                    <a href="<?= $wa ?>" target="_blank" class="btn btn-outline btn-sm" style="font-size:0.83rem;">
                        <i class="fab fa-whatsapp" style="color:#25D366;"></i> Hubungi Admin via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/main.js"></script>
<script>
// Toggle password visibility
document.getElementById('toggle-pass')?.addEventListener('click', function () {
    const input = document.getElementById('password-input');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>
</body>
</html>
