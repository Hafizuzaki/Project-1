<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

startSession();
requireLogin();

$db     = Database::getInstance();
$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

// Upload bukti pembayaran
if ($action === 'upload_payment') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token tidak valid');
        redirect(APP_URL . '/dashboard/payment.php');
    }
    $packageId  = (int)($_POST['package_id'] ?? 0);
    $amountPaid = (float)($_POST['amount_paid'] ?? 0);

    if (!$packageId || $amountPaid < MIN_REGISTRATION_FEE) {
        setFlash('error', 'Pilih paket dan jumlah transfer minimum Rp ' . number_format(MIN_REGISTRATION_FEE, 0, ',', '.'));
        redirect(APP_URL . '/dashboard/payment.php');
    }

    $package = $db->fetchOne("SELECT * FROM packages WHERE id = ? AND is_active = 1", [$packageId]);
    if (!$package) {
        setFlash('error', 'Paket tidak ditemukan.');
        redirect(APP_URL . '/dashboard/payment.php');
    }

    $proofImage = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = uploadFile($_FILES['proof_image'], 'payments');
        if (!$uploaded) {
            setFlash('error', 'Gagal mengupload bukti pembayaran. Pastikan format JPG/PNG dan ukuran max 5MB.');
            redirect(APP_URL . '/dashboard/payment.php');
        }
        $proofImage = $uploaded;
    } else {
        setFlash('error', 'Bukti pembayaran wajib diupload.');
        redirect(APP_URL . '/dashboard/payment.php');
    }

    $regNumber = generateRegistrationNumber();

    // Update existing unpaid or insert new
    $existing = $db->fetchOne("SELECT id FROM registrations WHERE user_id = ? AND payment_status = 'unpaid'", [$userId]);
    if ($existing) {
        $db->execute(
            "UPDATE registrations SET package_id=?, payment_amount=?, payment_proof=?, registration_number=?, updated_at=NOW() WHERE id=?",
            [$packageId, $amountPaid, $proofImage, $regNumber, $existing['id']]
        );
    } else {
        $db->execute(
            "INSERT INTO registrations (user_id, package_id, payment_amount, payment_proof, registration_number, payment_status)
             VALUES (?,?,?,?,?,'unpaid')",
            [$userId, $packageId, $amountPaid, $proofImage, $regNumber]
        );
    }

    // Notify admins
    $admins = $db->fetchAll("SELECT id FROM users WHERE role='admin' AND status='active'");
    $user   = $db->fetchOne("SELECT full_name FROM users WHERE id=?", [$userId]);
    foreach ($admins as $admin) {
        addNotification($admin['id'], 'Bukti Pembayaran Baru',
            $user['full_name'] . ' mengupload bukti pembayaran. Mohon verifikasi.',
            'warning', APP_URL . '/admin/payments.php');
    }

    logActivity($userId, 'UPLOAD_PAYMENT', "Upload bukti pembayaran, paket ID: $packageId, jumlah: $amountPaid");
    setFlash('success', 'Bukti pembayaran berhasil diupload. Admin akan memverifikasi dalam 1x24 jam.');
    redirect(APP_URL . '/dashboard/payment.php');
}

// Request withdrawal
if ($action === 'request_withdrawal') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token tidak valid');
        redirect(APP_URL . '/dashboard/komisi.php');
    }
    $amount      = (float)($_POST['amount'] ?? 0);
    $bankName    = sanitize($_POST['bank_name'] ?? '');
    $bankAccount = sanitize($_POST['bank_account'] ?? '');
    $bankHolder  = sanitize($_POST['bank_holder'] ?? '');
    $notes       = sanitize($_POST['notes'] ?? '');

    $user = $db->fetchOne("SELECT * FROM users WHERE id=?", [$userId]);
    $available = (float)$user['total_commission'] - (float)$user['withdrawn_commission'];

    if ($amount < 100000) {
        setFlash('error', 'Minimum penarikan Rp 100.000');
        redirect(APP_URL . '/dashboard/komisi.php');
    }
    if ($amount > $available) {
        setFlash('error', 'Jumlah melebihi saldo tersedia.');
        redirect(APP_URL . '/dashboard/komisi.php');
    }
    if (!$bankName || !$bankAccount || !$bankHolder) {
        setFlash('error', 'Semua detail bank wajib diisi.');
        redirect(APP_URL . '/dashboard/komisi.php');
    }

    $pending = $db->fetchOne("SELECT id FROM withdrawals WHERE user_id=? AND status='pending'", [$userId]);
    if ($pending) {
        setFlash('error', 'Masih ada permintaan penarikan yang belum diproses.');
        redirect(APP_URL . '/dashboard/komisi.php');
    }

    $db->beginTransaction();
    try {
        $db->execute(
            "INSERT INTO withdrawals (user_id, amount, bank_name, bank_account, bank_holder, notes, status) VALUES (?,?,?,?,?,?,'pending')",
            [$userId, $amount, $bankName, $bankAccount, $bankHolder, $notes]
        );
        $db->execute(
            "UPDATE users SET withdrawn_commission = withdrawn_commission + ? WHERE id=?",
            [$amount, $userId]
        );

        $admins = $db->fetchAll("SELECT id FROM users WHERE role='admin' AND status='active'");
        foreach ($admins as $admin) {
            addNotification($admin['id'], 'Permintaan Penarikan Baru',
                $user['full_name'] . ' mengajukan penarikan ' . formatRupiah($amount),
                'info', APP_URL . '/admin/withdrawals.php');
        }

        logActivity($userId, 'REQUEST_WITHDRAWAL', "Ajukan penarikan: $amount");
        $db->commit();
        setFlash('success', 'Permintaan penarikan berhasil diajukan.');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Gagal: ' . $e->getMessage());
    }
    redirect(APP_URL . '/dashboard/komisi.php');
}

// Update profile
if ($action === 'update_profile') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token tidak valid');
        redirect(APP_URL . '/dashboard/profil.php');
    }
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $address  = sanitize($_POST['address'] ?? '');

    if (!$fullName) {
        setFlash('error', 'Nama lengkap wajib diisi.');
        redirect(APP_URL . '/dashboard/profil.php');
    }

    $fields = ['full_name' => $fullName, 'email' => $email ?: null, 'phone' => $phone ?: null, 'address' => $address ?: null];

    // Photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $uploaded = uploadFile($_FILES['profile_photo'], 'profiles');
        if ($uploaded) $fields['profile_photo'] = $uploaded;
    }

    // Password change
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if ($currentPass || $newPass) {
        $userRow = $db->fetchOne("SELECT password FROM users WHERE id=?", [$userId]);
        if (!password_verify($currentPass, $userRow['password'])) {
            setFlash('error', 'Password lama tidak sesuai.');
            redirect(APP_URL . '/dashboard/profil.php');
        }
        if (strlen($newPass) < 8) {
            setFlash('error', 'Password baru minimal 8 karakter.');
            redirect(APP_URL . '/dashboard/profil.php');
        }
        if ($newPass !== $confirmPass) {
            setFlash('error', 'Konfirmasi password tidak cocok.');
            redirect(APP_URL . '/dashboard/profil.php');
        }
        $fields['password'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    $sets   = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
    $values = array_values($fields);
    $values[] = $userId;
    $db->execute("UPDATE users SET $sets WHERE id = ?", $values);

    logActivity($userId, 'UPDATE_PROFILE', 'Memperbarui profil');
    setFlash('success', 'Profil berhasil diperbarui.');
    redirect(APP_URL . '/dashboard/profil.php');
}

// Add bank account - DISABLED: Requires schema update
// User bank accounts feature will be implemented separately
if ($action === 'add_bank') {
    setFlash('info', 'Fitur manajemen rekening sedang dalam pengembangan.');
    redirect(APP_URL . '/dashboard/profil.php#bank');
}

// Delete bank account - DISABLED: Requires schema update  
if ($action === 'delete_bank') {
    setFlash('info', 'Fitur manajemen rekening sedang dalam pengembangan.');
    redirect(APP_URL . '/dashboard/profil.php#bank');
}

redirect(APP_URL . '/dashboard/index.php');
