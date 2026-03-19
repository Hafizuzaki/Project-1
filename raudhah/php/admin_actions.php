<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mlm.php';

startSession();
requireAdmin();

$db     = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create_user') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token CSRF tidak valid');
        redirect(APP_URL . '/admin/users.php');
    }
    $fullName   = sanitize($_POST['full_name'] ?? '');
    $username   = sanitize($_POST['username'] ?? '');
    $email      = sanitize($_POST['email'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $referrerId = (int)($_POST['referrer_id'] ?? 0);

    if (!$fullName || !$username || !$password || !$referrerId) {
        setFlash('error', 'Nama, username, password, dan referrer wajib diisi.');
        redirect(APP_URL . '/admin/users.php');
    }
    $exists = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($exists) {
        setFlash('error', 'Username sudah digunakan.');
        redirect(APP_URL . '/admin/users.php');
    }
    $referralCode = generateReferralCode($username);
    $hashedPass   = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $mlm      = new MLMTree();
    $pos      = $mlm->findNextPosition($referrerId);
    $parentId = $pos['parent_id'] ?? null;
    $position = $pos['position'] ?? null;

    $db->beginTransaction();
    try {
        $userId = $db->insert(
            "INSERT INTO users (username, full_name, email, phone, password, referral_code, referred_by, parent_id, position, status)
             VALUES (?,?,?,?,?,?,?,?,?,'pending')",
            [$username, $fullName, $email ?: null, $phone ?: null, $hashedPass,
             $referralCode, $referrerId, $parentId, $position]
        );
        logActivity($_SESSION['user_id'], 'CREATE_USER', "Buat user: $username (ID: $userId)");
        $db->commit();
        setFlash('success', "Akun berhasil dibuat untuk $fullName. Kode referral: $referralCode");
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Gagal membuat akun: ' . $e->getMessage());
    }
    redirect(APP_URL . '/admin/users.php');
}

if ($action === 'verify_payment') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token CSRF tidak valid');
        redirect(APP_URL . '/admin/payments.php');
    }
    $regId        = (int)($_POST['registration_id'] ?? 0);
    $verifyAction = sanitize($_POST['verify_action'] ?? '');
    $reason       = sanitize($_POST['rejection_reason'] ?? '');

    if (!$regId || !in_array($verifyAction, ['verify', 'reject'])) {
        setFlash('error', 'Data tidak valid.');
        redirect(APP_URL . '/admin/payments.php');
    }
    $reg = $db->fetchOne("SELECT * FROM registrations WHERE id = ?", [$regId]);
    if (!$reg) {
        setFlash('error', 'Data registrasi tidak ditemukan.');
        redirect(APP_URL . '/admin/payments.php');
    }
    $newStatus = $verifyAction === 'verify' ? 'verified' : 'rejected';

    $db->beginTransaction();
    try {
        $db->execute(
            "UPDATE registrations SET payment_status = ?, verified_by = ?, verified_at = NOW(), rejection_reason = ? WHERE id = ?",
            [$newStatus, $_SESSION['user_id'], $reason ?: null, $regId]
        );
        if ($verifyAction === 'verify') {
            $db->execute("UPDATE users SET status = 'active' WHERE id = ?", [$reg['user_id']]);
            $mlm = new MLMTree();
            $mlm->processCommission($reg['user_id'], $regId);
            addNotification($reg['user_id'], 'Pembayaran Terverifikasi!',
                'Pembayaran Anda telah diverifikasi. Akun Anda kini aktif.', 'success',
                APP_URL . '/dashboard/index.php');
            logActivity($_SESSION['user_id'], 'VERIFY_PAYMENT', "Verifikasi pembayaran ID: $regId");

            // WA notification ke user
            require_once __DIR__ . '/whatsapp.php';
            $verifiedUser = $db->fetchOne("SELECT u.*, p.name AS package_name FROM users u JOIN registrations r ON r.user_id = u.id JOIN packages p ON p.id = r.package_id WHERE r.id = ?", [$regId]);
            if ($verifiedUser && $verifiedUser['phone']) {
                waNotifPaymentVerified($verifiedUser['phone'], $verifiedUser['full_name'], $verifiedUser['package_name']);
            }
        } else {
            addNotification($reg['user_id'], 'Pembayaran Ditolak',
                'Pembayaran Anda ditolak. Alasan: ' . ($reason ?: '-'),
                'error', APP_URL . '/dashboard/payment.php');
            logActivity($_SESSION['user_id'], 'REJECT_PAYMENT', "Tolak pembayaran ID: $regId");
        }
        $db->commit();
        setFlash('success', 'Status pembayaran berhasil diperbarui.');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Gagal: ' . $e->getMessage());
    }
    redirect(APP_URL . '/admin/payments.php');
}

if ($action === 'process_withdrawal') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token CSRF tidak valid');
        redirect(APP_URL . '/admin/withdrawals.php');
    }
    $wdId             = (int)($_POST['withdrawal_id'] ?? 0);
    $withdrawalAction = sanitize($_POST['withdrawal_action'] ?? '');
    $wd = $db->fetchOne("SELECT * FROM withdrawals WHERE id = ?", [$wdId]);
    if (!$wd || $wd['status'] !== 'pending') {
        setFlash('error', 'Data penarikan tidak ditemukan atau sudah diproses.');
        redirect(APP_URL . '/admin/withdrawals.php');
    }
    $newStatus = $withdrawalAction === 'approve' ? 'approved' : 'rejected';

    $db->beginTransaction();
    try {
        $db->execute(
            "UPDATE withdrawals SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?",
            [$newStatus, $_SESSION['user_id'], $wdId]
        );
        if ($withdrawalAction === 'reject') {
            $db->execute(
                "UPDATE users SET withdrawn_commission = withdrawn_commission - ? WHERE id = ?",
                [$wd['amount'], $wd['user_id']]
            );
            addNotification($wd['user_id'], 'Penarikan Ditolak',
                'Penarikan ' . formatRupiah((float)$wd['amount']) . ' ditolak. Dana dikembalikan.',
                'error', APP_URL . '/dashboard/komisi.php');
        } else {
            addNotification($wd['user_id'], 'Dana Telah Ditransfer!',
                'Penarikan ' . formatRupiah((float)$wd['amount']) . ' telah disetujui.',
                'success', APP_URL . '/dashboard/komisi.php');
        }
        logActivity($_SESSION['user_id'], 'PROCESS_WITHDRAWAL', "Withdrawal $wdId: $newStatus");
        $db->commit();
        setFlash('success', 'Status penarikan berhasil diperbarui.');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Gagal: ' . $e->getMessage());
    }
    redirect(APP_URL . '/admin/withdrawals.php');
}

if ($action === 'suspend_user') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { redirect(APP_URL . '/admin/users.php'); }
    $userId = (int)($_POST['user_id'] ?? 0);
    $db->execute("UPDATE users SET status = 'suspended' WHERE id = ? AND role = 'member'", [$userId]);
    logActivity($_SESSION['user_id'], 'SUSPEND_USER', "Suspend user ID: $userId");
    setFlash('success', 'Member berhasil disuspend.');
    redirect(APP_URL . '/admin/users.php');
}

if ($action === 'activate_user') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) { redirect(APP_URL . '/admin/users.php'); }
    $userId = (int)($_POST['user_id'] ?? 0);
    $db->execute("UPDATE users SET status = 'active' WHERE id = ? AND role = 'member'", [$userId]);
    logActivity($_SESSION['user_id'], 'ACTIVATE_USER', "Aktifkan user ID: $userId");
    setFlash('success', 'Member berhasil diaktifkan.');
    redirect(APP_URL . '/admin/users.php');
}

redirect(APP_URL . '/admin/dashboard.php');

// -------------------------------------------------------
// Bayar / Approve Komisi (admin tandai sudah dikirim)
// -------------------------------------------------------
if ($action === 'pay_commission') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlash('error', 'Token tidak valid');
        redirect(APP_URL . '/admin/commissions.php');
    }

    $commissionIds = $_POST['commission_ids'] ?? [];
    if (!is_array($commissionIds) || empty($commissionIds)) {
        setFlash('error', 'Pilih minimal satu komisi.');
        redirect(APP_URL . '/admin/commissions.php');
    }

    require_once __DIR__ . '/whatsapp.php';

    $paid  = 0;
    $errors = 0;
    foreach ($commissionIds as $cid) {
        $cid = (int)$cid;
        $comm = $db->fetchOne(
            "SELECT c.*, u.full_name, u.phone, u.id AS uid
             FROM commissions c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ? AND c.status = 'pending'",
            [$cid]
        );
        if (!$comm) { $errors++; continue; }

        $db->beginTransaction();
        try {
            $db->execute(
                "UPDATE commissions SET status = 'paid', paid_at = NOW(), notes = ? WHERE id = ?",
                ['Dibayar oleh admin ID ' . $_SESSION['user_id'], $cid]
            );

            addNotification(
                $comm['uid'],
                '💸 Komisi Telah Dikirim!',
                'Komisi ' . formatRupiah((float)$comm['amount']) . ' dari referral Anda telah dikirim ke rekening Anda.',
                'success',
                APP_URL . '/dashboard/komisi.php'
            );

            logActivity($_SESSION['user_id'], 'PAY_COMMISSION', "Bayar komisi ID: $cid untuk user ID: " . $comm['uid']);
            $db->commit();
            $paid++;

            // WA notification
            if ($comm['phone']) {
                $bankInfo = 'rekening terdaftar';
                waNotifCommissionPaid($comm['phone'], $comm['full_name'], (float)$comm['amount'], $bankInfo);
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errors++;
        }
    }

    $msg = "$paid komisi berhasil ditandai dibayar.";
    if ($errors) $msg .= " $errors gagal diproses.";
    setFlash($errors ? 'warning' : 'success', $msg);
    redirect(APP_URL . '/admin/commissions.php');
}
