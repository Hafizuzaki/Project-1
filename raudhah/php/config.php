<?php
// =====================================================
// config.php - Konfigurasi Utama Aplikasi
// PT RAUDHAH AMANAH WISATA
// =====================================================

// Prevent direct access to this file
if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

define('APP_NAME', 'PT Raudhah Amanah Wisata');
define('APP_TAGLINE', 'Perjalanan Suci Menuju Baitullah');
define('APP_URL', 'http://localhost/raudhah');
define('APP_VERSION', '1.0.0');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'raudhah_travel');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session
define('SESSION_NAME', 'raudhah_sess');
define('SESSION_LIFETIME', 7200); // 2 jam

// Upload
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', APP_URL . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);

// MLM
define('REFERRAL_COMMISSION', 500000);
define('MIN_REGISTRATION_FEE', 2000000);
define('MAX_CHILDREN', 2); // Maksimal 2 kaki (kiri & kanan)

// WhatsApp Notification (via WhatsApp API / wa.me link)
// Ganti dengan nomor WA Anda (format internasional tanpa +, contoh: 6281234567890)
// Untuk notifikasi otomatis, gunakan Fonnte / Wablas / Zenziva API
define('WA_NOTIFICATION_ENABLED', true);
define('WA_API_URL', 'https://api.fonnte.com/send'); // Fonnte API (ganti sesuai provider)
define('WA_API_TOKEN', 'Q1ujHpshY9a7zseDVKKB');   // Token API dari provider
define('WA_FALLBACK_LINK', true); // Jika true, generate link wa.me sebagai fallback

// Security - Login Rate Limiting
define('LOGIN_RATE_LIMIT_ATTEMPTS', 5); // Max login attempts
define('LOGIN_RATE_LIMIT_WINDOW', 300); // 5 minutes in seconds
define('SESSION_TIMEOUT', 7200); // 2 hours in seconds (match SESSION_LIFETIME)
define('PASSWORD_RESET_RATE_LIMIT', 3); // Max reset attempts per day

// Security
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_NAME', '_csrf_token');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting - DO NOT SHOW ERRORS IN PRODUCTION
// Set IS_PRODUCTION = true when deploying to live server
define('IS_PRODUCTION', false); // CHANGE TO TRUE IN PRODUCTION
if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
