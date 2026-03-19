<?php
// =====================================================
// session.php - Manajemen Session & Autentikasi
// =====================================================

// Prevent direct access to this file
if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function isOperator(): bool {
    // Alias untuk isAdmin() - admin adalah operator
    return isAdmin();
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM users WHERE id = ? AND status = 'active'", [$_SESSION['user_id']]);
}

function loginUser(array $user): void {
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['logged_at'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

function logoutUser(): void {
    startSession();
    $_SESSION = [];
    session_destroy();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    // Check session timeout
    if (isset($_SESSION['logged_at'])) {
        $sessionAge = time() - $_SESSION['logged_at'];
        if ($sessionAge > SESSION_TIMEOUT) {
            logoutUser();
            setFlash('warning', 'Sesi Anda telah expired. Silakan login kembali.');
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }
    // Check IP change (prevent session hijacking)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        logoutUser();
        setFlash('danger', 'IP address berubah. Silakan login kembali.');
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

// CSRF Token
function generateCsrfToken(): string {
    startSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($_SESSION['_csrf_token_time'])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken(string $token): bool {
    startSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCsrfToken() . '">';
}

// Flash messages
function setFlash(string $type, string $message): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
