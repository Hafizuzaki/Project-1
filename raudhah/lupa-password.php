<?php
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/database.php';
require_once __DIR__ . '/php/session.php';
require_once __DIR__ . '/php/helpers.php';

startSession();

if (isLoggedIn()) redirect(APP_URL . '/dashboard/index.php');

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $message = 'Token tidak valid. Muat ulang halaman.';
        $msgType = 'error';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');
        $newPass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validate input lengths
        if (strlen($username) < 3 || strlen($username) > 50) {
            $message = 'Username tidak valid.';
            $msgType = 'error';
        } elseif (strlen($phone) < 10 || strlen($phone) > 20) {
            $message = 'Nomor HP tidak valid.';
            $msgType = 'error';
        } elseif (!$username || !$phone || !$newPass) {
            $message = 'Semua field wajib diisi.';
            $msgType = 'error';
        } elseif (strlen($newPass) < 8) {
            $message = 'Password baru minimal 8 karakter.';
            $msgType = 'error';
        } elseif (strlen($newPass) > 255) {
            $message = 'Password terlalu panjang (max 255 karakter).';
            $msgType = 'error';
        } elseif ($newPass !== $confirm) {
            $message = 'Konfirmasi password tidak cocok.';
            $msgType = 'error';
        } else {
            // Rate limiting: max 3 reset attempts per day per IP
            startSession();
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $resetAttemptKey = "reset_attempts_{$clientIp}";
            
            if (!isset($_SESSION[$resetAttemptKey])) {
                $_SESSION[$resetAttemptKey] = ['count' => 0, 'first_attempt' => time()];
            }
            
            // Reset counter after 24 hours
            if (time() - $_SESSION[$resetAttemptKey]['first_attempt'] > 86400) {
                $_SESSION[$resetAttemptKey] = ['count' => 0, 'first_attempt' => time()];
            }
            
            if ($_SESSION[$resetAttemptKey]['count'] >= PASSWORD_RESET_RATE_LIMIT) {
                $message = 'Terlalu banyak percobaan reset password. Coba lagi dalam 24 jam.';
                $msgType = 'error';
                logActivity(null, 'RESET_BLOCKED', 'IP ' . $clientIp . ' diblokir karena banyak reset attempts');
            } else {
                $db   = Database::getInstance();
                $user = $db->fetchOne(
                    "SELECT * FROM users WHERE username = ? AND phone = ?",
                    [$username, $phone]
                );

                if (!$user) {
                    $_SESSION[$resetAttemptKey]['count']++;
                    $message = 'Username dan nomor HP tidak cocok.';
                    $msgType = 'error';
                    logActivity(null, 'RESET_FAILED', 'Username: ' . $username);
                } else {
                    try {
                        $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                        $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashed, $user['id']]);
                        logActivity($user['id'], 'RESET_PASSWORD', 'Reset password berhasil');
                        
                        // Reset counter on success
                        $_SESSION[$resetAttemptKey] = ['count' => 0, 'first_attempt' => time()];
                        
                        $message = 'Password berhasil direset! Silakan login dengan password baru Anda.';
                        $msgType = 'success';
                    } catch (Exception $e) {
                        logActivity(null, 'RESET_ERROR', 'Error: ' . $e->getMessage());
                        $message = 'Terjadi kesalahan saat mereset password. Silakan coba lagi nanti.';
                        $msgType = 'error';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <span>☪️</span>
            <h2><?= APP_NAME ?></h2>
        </div>
        <h3 class="auth-title">Reset Password</h3>
        <p class="auth-subtitle">Masukkan username dan nomor HP untuk mereset password Anda.</p>

        <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>">
            <?= e($message) ?>
            <?php if ($msgType === 'success'): ?>
            <br><a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-sm" style="margin-top:0.8rem;">Login Sekarang</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($msgType !== 'success'): ?>
        <form method="POST" class="auth-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label">Username <span class="required">*</span></label>
                <input type="text" name="username" class="form-input" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor HP Terdaftar <span class="required">*</span></label>
                <input type="tel" name="phone" class="form-input" placeholder="Nomor HP yang didaftarkan" required>
                <small class="form-hint">Nomor HP yang Anda berikan saat pendaftaran</small>
            </div>
            <div class="form-group">
                <label class="form-label">Password Baru <span class="required">*</span></label>
                <input type="password" name="new_password" class="form-input" minlength="8" placeholder="Minimal 8 karakter" required>
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
                <input type="password" name="confirm_password" class="form-input" placeholder="Ulangi password baru" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
        <?php endif; ?>

        <p class="auth-footer">
            Sudah ingat password? <a href="<?= APP_URL ?>/login.php">Login di sini</a>
        </p>
    </div>
</div>

<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>
