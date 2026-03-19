<?php
// =====================================================
// helpers.php - Fungsi-Fungsi Pembantu
// =====================================================

// Prevent direct access to this file
if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function generateReferralCode(string $username): string {
    $prefix = 'RAW';
    $suffix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $username), 0, 4));
    $random = strtoupper(substr(md5(uniqid()), 0, 4));
    return $prefix . '-' . $suffix . $random;
}

function generateRegistrationNumber(): string {
    return 'REG-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

function sanitize(string $str): string {
    return trim(htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8'));
}

function uploadFile(array $file, string $folder = 'general'): ?string {
    try {
        // Validate file upload
        if (empty($file) || !isset($file['error'])) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
        if ($file['size'] > MAX_FILE_SIZE) return null;
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) return null;
        
        // Sanitize filename and prevent directory traversal
        $originalName = basename($file['name']);
        if (empty($originalName)) return null;
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) return null;
        
        $filename = uniqid() . '_' . time() . '.' . strtolower($ext);
        $folder = basename($folder); // Prevent directory traversal
        $dir = UPLOAD_PATH . $folder . '/';
        
        // Create directory with error handling
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) return null;
        }
        
        $fullPath = $dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            @chmod($fullPath, 0644);
            return $folder . '/' . $filename;
        }
    } catch (Exception $e) {
        error_log('Upload error: ' . $e->getMessage());
    }
    return null;
}

function getUploadUrl(string $path): string {
    return UPLOAD_URL . $path;
}

function timeAgo(string $datetime): string {
    try {
        $now  = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);

        if ($diff->y > 0)  return $diff->y . ' tahun lalu';
        if ($diff->m > 0)  return $diff->m . ' bulan lalu';
        if ($diff->d > 0)  return $diff->d . ' hari lalu';
        if ($diff->h > 0)  return $diff->h . ' jam lalu';
        if ($diff->i > 0)  return $diff->i . ' menit lalu';
        return 'Baru saja';
    } catch (Exception $e) {
        return 'tidak diketahui';
    }
}

function formatDate(string $date, string $format = 'd F Y'): string {
    try {
        $timestamp = strtotime($date);
        if ($timestamp === false) return 'tanggal tidak valid';
        
        $bulan = [
            '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
            '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
            '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
        ];
        $d = date('d', $timestamp);
        $m = date('m', $timestamp);
        $y = date('Y', $timestamp);
        return $d . ' ' . ($bulan[$m] ?? 'Bulan?') . ' ' . $y;
    } catch (Exception $e) {
        return 'tanggal tidak valid';
    }
}

function paginate(int $total, int $perPage, int $currentPage, string $url): array {
    $totalPages = (int)ceil($total / $perPage);
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => ($currentPage - 1) * $perPage,
        'has_prev'    => $currentPage > 1,
        'has_next'    => $currentPage < $totalPages,
        'url'         => $url,
    ];
}

function addNotification(int $userId, string $title, string $message, string $type = 'info', string $link = ''): void {
    try {
        if ($userId <= 0 || empty($title) || empty($message)) return;
        
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?,?,?,?,?)",
            [$userId, $title, $message, $type, $link]
        );
    } catch (Exception $e) {
        error_log('Notification error for user ' . $userId . ': ' . $e->getMessage());
    }
}

function logActivity(?int $userId, string $action, string $desc = ''): void {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $db->execute(
        "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?,?,?,?,?)",
        [$userId, $action, $desc, $ip, $ua]
    );
}

function getSiteSetting(string $key, string $default = ''): string {
    $db = Database::getInstance();
    $row = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
    return $row ? ($row['setting_value'] ?? $default) : $default;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function renderPagination(int $total, int $perPage, int $currentPage, string $baseUrl): string {
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    // Strip existing page param from URL
    $baseUrl = preg_replace('/([?&])page=\d+/', '$1', $baseUrl);
    $separator = (str_contains($baseUrl, '?')) ? '&' : '?';

    $html = '<div class="pagination">';

    // Prev
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . $separator . 'page=' . ($currentPage - 1) . '" class="page-btn">&laquo; Sebelumnya</a>';
    }

    // Pages
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = $i === $currentPage ? ' page-btn--active' : '';
        $html .= '<a href="' . $baseUrl . $separator . 'page=' . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . $separator . 'page=' . ($currentPage + 1) . '" class="page-btn">Berikutnya &raquo;</a>';
    }

    $html .= '</div>';
    return $html;
}
