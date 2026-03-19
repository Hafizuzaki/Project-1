<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Token tidak valid']);
    exit;
}

$name    = sanitize($_POST['name'] ?? '');
$email   = sanitize($_POST['email'] ?? '');
$phone   = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(['success' => false, 'message' => 'Nama, email, dan pesan wajib diisi']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
    exit;
}

// Store in DB (optional — add contact_messages table if needed)
// For now just log the activity
$db = Database::getInstance();
$db->execute(
    "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (NULL, 'contact_form', ?, ?)",
    [json_encode(compact('name', 'email', 'phone', 'subject', 'message')), $_SERVER['REMOTE_ADDR'] ?? '']
);

// Notify all admins
$admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
foreach ($admins as $admin) {
    addNotification(
        $admin['id'],
        'Pesan Kontak Baru',
        "Ada pesan baru dari $name ($email): $subject",
        'info'
    );
}

echo json_encode(['success' => true, 'message' => 'Pesan berhasil dikirim! Tim kami akan segera menghubungi Anda.']);
