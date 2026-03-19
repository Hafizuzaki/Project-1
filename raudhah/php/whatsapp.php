<?php
// =====================================================
// whatsapp.php - Kirim Notifikasi WhatsApp
// PT RAUDHAH AMANAH WISATA
//
// Mendukung:
//   1. Fonnte API  (https://fonnte.com)
//   2. Wablas API  (https://wablas.com)
//   3. Fallback: link wa.me (tanpa API)
// =====================================================

/**
 * Kirim pesan WhatsApp ke nomor tertentu.
 *
 * @param string $phone  Nomor tujuan, format: 628xxx (tanpa + atau spasi)
 * @param string $message Teks pesan
 * @return bool
 */
function sendWhatsApp(string $phone, string $message): bool {
    if (!WA_NOTIFICATION_ENABLED) return false;

    // Normalisasi nomor
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($phone, '0')) {
        $phone = '62' . substr($phone, 1);
    }
    if (!$phone) return false;

    // Coba kirim via API jika token tersedia
    if (WA_API_TOKEN !== 'ISI_TOKEN_FONNTE_DISINI' && WA_API_TOKEN !== '') {
        return _sendViaFonnte($phone, $message);
    }

    // Fallback: log saja (tidak bisa kirim tanpa API)
    _logWaMessage($phone, $message);
    return false;
}

/**
 * Kirim via Fonnte API
 */
function _sendViaFonnte(string $phone, string $message): bool {
    $data = [
        'target'  => $phone,
        'message' => $message,
    ];

    $ch = curl_init(WA_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . WA_API_TOKEN,
        ],
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        _logWaMessage($phone, $message, 'CURL ERROR: ' . $error);
        return false;
    }

    $result = json_decode($response, true);
    $success = isset($result['status']) && $result['status'] === true;
    _logWaMessage($phone, $message, $response);
    return $success;
}

/**
 * Log pesan WA ke activity_logs (untuk debugging)
 */
function _logWaMessage(string $phone, string $message, string $response = ''): void {
    try {
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (NULL, 'WA_NOTIFICATION', ?, 'system')",
            [json_encode(['phone' => $phone, 'message' => $message, 'response' => $response])]
        );
    } catch (Exception $e) {
        // Silent fail
    }
}

/**
 * Generate link wa.me untuk membuka chat WA manual
 */
function waLink(string $phone, string $message): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($phone, '0')) {
        $phone = '62' . substr($phone, 1);
    }
    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
}

// =====================================================
// Template Pesan WA
// =====================================================

/**
 * Notif ke referrer: ada yang pakai kode referralnya
 */
function waNotifReferralUsed(string $referrerPhone, string $referrerName, string $newMemberName, float $commission): bool {
    $msg = "🌙 *" . APP_NAME . "*\n\n"
         . "Assalamu'alaikum *$referrerName*,\n\n"
         . "Kabar gembira! 🎉\n"
         . "Kode referral Anda baru saja digunakan oleh:\n"
         . "👤 *$newMemberName*\n\n"
         . "Komisi Anda sebesar *" . formatRupiah($commission) . "* sedang diproses.\n\n"
         . "Pantau komisi Anda di:\n"
         . APP_URL . "/dashboard/komisi.php\n\n"
         . "Jazakallah khair 🤲";

    return sendWhatsApp($referrerPhone, $msg);
}

/**
 * Notif ke user: komisi sudah dikirim/dibayar admin
 */
function waNotifCommissionPaid(string $userPhone, string $userName, float $amount, string $bankInfo): bool {
    $msg = "🌙 *" . APP_NAME . "*\n\n"
         . "Assalamu'alaikum *$userName*,\n\n"
         . "✅ Komisi Anda telah dikirim!\n\n"
         . "💰 Jumlah: *" . formatRupiah($amount) . "*\n"
         . "🏦 Ke rekening: *$bankInfo*\n\n"
         . "Silakan cek rekening Anda.\n"
         . "Detail di: " . APP_URL . "/dashboard/komisi.php\n\n"
         . "Jazakallah khair 🤲";

    return sendWhatsApp($userPhone, $msg);
}

/**
 * Notif ke user: pembayaran diverifikasi
 */
function waNotifPaymentVerified(string $userPhone, string $userName, string $packageName): bool {
    $msg = "🌙 *" . APP_NAME . "*\n\n"
         . "Assalamu'alaikum *$userName*,\n\n"
         . "✅ Pembayaran Anda telah *TERVERIFIKASI*!\n\n"
         . "📦 Paket: *$packageName*\n"
         . "Akun Anda kini aktif.\n\n"
         . "Masuk ke dashboard:\n"
         . APP_URL . "/dashboard/index.php\n\n"
         . "Barakallahu fiik 🤲";

    return sendWhatsApp($userPhone, $msg);
}
