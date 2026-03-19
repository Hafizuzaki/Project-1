<?php
// =====================================================
// auth.php - Controller Login / Logout
// =====================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

startSession();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
        redirect(APP_URL . '/login.php');
    }

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlash('danger', 'Username dan password harus diisi.');
        redirect(APP_URL . '/login.php');
    }

    // Rate limiting check
    startSession();
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $loginAttemptKey = "login_attempts_{$clientIp}";
    
    if (!isset($_SESSION[$loginAttemptKey])) {
        $_SESSION[$loginAttemptKey] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $timeWindow = time() - $_SESSION[$loginAttemptKey]['first_attempt'];
    if ($timeWindow > LOGIN_RATE_LIMIT_WINDOW) {
        // Reset counter after time window
        $_SESSION[$loginAttemptKey] = ['count' => 0, 'first_attempt' => time()];
    }
    
    if ($_SESSION[$loginAttemptKey]['count'] >= LOGIN_RATE_LIMIT_ATTEMPTS) {
        setFlash('danger', 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.');
        logActivity(null, 'LOGIN_BLOCKED', 'IP ' . $clientIp . ' diblokir karena banyak percobaan gagal');
        redirect(APP_URL . '/login.php');
    }

    $db   = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1",
        [$username, $username]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION[$loginAttemptKey]['count']++;
        setFlash('danger', 'Username atau password salah.');
        logActivity(null, 'LOGIN_FAILED', 'Percobaan login gagal: ' . $username);
        redirect(APP_URL . '/login.php');
    }
    
    // Reset login attempts on success
    $_SESSION[$loginAttemptKey] = ['count' => 0, 'first_attempt' => time()];

    if ($user['status'] === 'pending') {
        setFlash('warning', 'Akun Anda belum diverifikasi. Silakan tunggu konfirmasi admin.');
        redirect(APP_URL . '/login.php');
    }

    if ($user['status'] === 'suspended') {
        setFlash('danger', 'Akun Anda telah dinonaktifkan. Hubungi admin untuk informasi lebih lanjut.');
        redirect(APP_URL . '/login.php');
    }

    loginUser($user);
    logActivity($user['id'], 'LOGIN', 'Login berhasil sebagai ' . $user['role']);

    // Route berdasarkan role
    if ($user['role'] === 'admin') {
        // Admin = Operator yang mengelola sistem
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        // User = Member yang mengakses dashboard user
        redirect(APP_URL . '/dashboard/index.php');
    }
}

if ($action === 'logout') {
    $userId = $_SESSION['user_id'] ?? null;
    logActivity($userId, 'LOGOUT', 'Logout');
    logoutUser();
    setFlash('success', 'Anda telah berhasil keluar.');
    redirect(APP_URL . '/login.php');
}

redirect(APP_URL . '/login.php');
