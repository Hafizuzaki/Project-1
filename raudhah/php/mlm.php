<?php
// =====================================================
// mlm.php - Logika Sistem MLM Binary Tree
// =====================================================

// Prevent direct access to this file
if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

class MLMTree {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Cari posisi kaki kosong berikutnya (breadth-first, kiri dulu)
     * Return: ['parent_id' => x, 'position' => 'left'|'right']
     */
    public function findNextPosition(int $referrerId): ?array {
        $queue = [$referrerId];

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            $children  = $this->getChildren($currentId);

            $hasLeft  = false;
            $hasRight = false;

            foreach ($children as $child) {
                if ($child['position'] === 'left')  $hasLeft  = true;
                if ($child['position'] === 'right') $hasRight = true;
                $queue[] = $child['id'];
            }

            if (!$hasLeft)  return ['parent_id' => $currentId, 'position' => 'left'];
            if (!$hasRight) return ['parent_id' => $currentId, 'position' => 'right'];
        }

        return null; // Seharusnya tidak terjadi pada binary tree tanpa batas
    }

    /**
     * Dapatkan anak langsung (kiri & kanan)
     */
    public function getChildren(int $userId): array {
        return $this->db->fetchAll(
            "SELECT id, username, full_name, position, status, referral_code, created_at 
             FROM users WHERE parent_id = ? ORDER BY position ASC",
            [$userId]
        );
    }

    /**
     * Bangun pohon referral dari user ke bawah (untuk tampilan)
     * $viewerId: ID user yang sedang melihat (tidak tampilkan di atasnya)
     */
    public function buildTree(int $rootId, int $depth = 0, int $maxDepth = 10): array {
        if ($depth >= $maxDepth) return [];

        $user = $this->db->fetchOne(
            "SELECT id, username, full_name, status, referral_code, position, created_at FROM users WHERE id = ?",
            [$rootId]
        );

        if (!$user) return [];

        $children = $this->getChildren($rootId);
        $user['children'] = [];
        $user['depth']    = $depth;

        $left  = null;
        $right = null;

        foreach ($children as $child) {
            if ($child['position'] === 'left')  $left  = $child['id'];
            if ($child['position'] === 'right') $right = $child['id'];
        }

        if ($left)  $user['children']['left']  = $this->buildTree($left,  $depth + 1, $maxDepth);
        if ($right) $user['children']['right'] = $this->buildTree($right, $depth + 1, $maxDepth);

        // Statistik downline
        $user['total_downline'] = $this->countDownline($rootId);

        return $user;
    }

    /**
     * Hitung total downline
     */
    public function countDownline(int $userId): int {
        return $this->db->count(
            "SELECT COUNT(*) FROM users WHERE parent_id = ?",
            [$userId]
        ) + $this->db->count(
            "WITH RECURSIVE tree AS (
                SELECT id FROM users WHERE parent_id = ?
                UNION ALL
                SELECT u.id FROM users u INNER JOIN tree t ON u.parent_id = t.id
            )
            SELECT COUNT(*) FROM tree",
            [$userId]
        );
    }

    /**
     * Hitung downline langsung (level 1)
     */
    public function countDirectDownline(int $userId): int {
        return $this->db->count("SELECT COUNT(*) FROM users WHERE parent_id = ?", [$userId]);
    }

    /**
     * Proses komisi setelah verifikasi pembayaran
     */
    public function processCommission(int $newUserId, int $registrationId): void {
        $newUser  = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$newUserId]);
        if (!$newUser || !$newUser['referred_by']) return;

        $referrerId = $newUser['referred_by'];
        $referrer   = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$referrerId]);
        if (!$referrer) return;

        $this->db->beginTransaction();
        try {
            // Komisi langsung untuk referrer (status pending — belum dibayar)
            $this->db->execute(
                "INSERT INTO commissions (user_id, from_user_id, registration_id, amount, level, position, status)
                 VALUES (?, ?, ?, ?, 1, ?, 'pending')",
                [$referrerId, $newUserId, $registrationId, REFERRAL_COMMISSION, $newUser['position']]
            );

            // Update total_commission (total yang dijanjikan, belum tentu sudah dibayar)
            $this->db->execute(
                "UPDATE users SET total_commission = total_commission + ? WHERE id = ?",
                [REFERRAL_COMMISSION, $referrerId]
            );

            // Notifikasi in-app ke referrer
            addNotification(
                $referrerId,
                '🎉 Komisi Referral Baru!',
                $newUser['full_name'] . ' mendaftar menggunakan kode Anda. Komisi ' . formatRupiah(REFERRAL_COMMISSION) . ' sedang diproses.',
                'success',
                APP_URL . '/dashboard/komisi.php'
            );

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        // Notifikasi WhatsApp ke referrer (di luar transaksi DB)
        if ($referrer['phone']) {
            require_once __DIR__ . '/whatsapp.php';
            waNotifReferralUsed(
                $referrer['phone'],
                $referrer['full_name'],
                $newUser['full_name'],
                REFERRAL_COMMISSION
            );
        }
    }

    /**
     * Dapatkan semua downline IDs (rekursif)
     */
    public function getAllDownlineIds(int $userId): array {
        $result = $this->db->fetchAll(
            "WITH RECURSIVE tree AS (
                SELECT id FROM users WHERE parent_id = ?
                UNION ALL
                SELECT u.id FROM users u INNER JOIN tree t ON u.parent_id = t.id
            )
            SELECT id FROM tree",
            [$userId]
        );
        return array_column($result, 'id');
    }

    /**
     * Statistik pohon untuk dashboard
     */
    public function getTreeStats(int $userId): array {
        $leftChild  = $this->db->fetchOne("SELECT id FROM users WHERE parent_id = ? AND position = 'left'", [$userId]);
        $rightChild = $this->db->fetchOne("SELECT id FROM users WHERE parent_id = ? AND position = 'right'", [$userId]);

        $leftCount  = $leftChild  ? $this->db->count(
            "WITH RECURSIVE t AS (SELECT id FROM users WHERE id = ? UNION ALL SELECT u.id FROM users u JOIN t ON u.parent_id = t.id) SELECT COUNT(*) FROM t",
            [$leftChild['id']]
        ) : 0;

        $rightCount = $rightChild ? $this->db->count(
            "WITH RECURSIVE t AS (SELECT id FROM users WHERE id = ? UNION ALL SELECT u.id FROM users u JOIN t ON u.parent_id = t.id) SELECT COUNT(*) FROM t",
            [$rightChild['id']]
        ) : 0;

        return [
            'left_count'  => $leftCount,
            'right_count' => $rightCount,
            'total'       => $leftCount + $rightCount,
        ];
    }
}
