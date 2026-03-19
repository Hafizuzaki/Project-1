<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mlm.php';

header('Content-Type: application/json');
startSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$nodeId  = isset($_GET['node_id']) ? (int)$_GET['node_id'] : 0;
$viewerId = (int)$_GET['viewer'];

if (!$nodeId) {
    echo json_encode(['success' => false, 'message' => 'Node ID required']);
    exit;
}

$currentUser = getCurrentUser();

// Security: viewers can only see nodes within their subtree (or themselves)
// Admin can see anyone
if (!isAdmin()) {
    $mlm = new MLMTree();
    $allowedIds = $mlm->getAllDownlineIds($currentUser['id']);
    $allowedIds[] = (int)$currentUser['id'];

    if (!in_array($nodeId, $allowedIds)) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
}

$db   = Database::getInstance();
$node = $db->fetchOne(
    "SELECT u.id, u.full_name, u.username, u.referral_code, u.status, u.created_at,
            u.total_commission, u.position,
            (SELECT COUNT(*) FROM users WHERE parent_id = u.id) AS direct_children,
            r.package_id, p.name AS package_name, r.payment_status AS reg_status, r.created_at AS reg_date
     FROM users u
     LEFT JOIN registrations r ON r.user_id = u.id
     LEFT JOIN packages p ON p.id = r.package_id
     WHERE u.id = ?",
    [$nodeId]
);

if (!$node) {
    echo json_encode(['success' => false, 'message' => 'Node not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'node'    => [
        'id'           => $node['id'],
        'full_name'    => e($node['full_name']),
        'username'     => e($node['username']),
        'referral_code'=> e($node['referral_code']),
        'status'       => $node['status'],
        'position'     => $node['position'],
        'package'      => $node['package_name'] ? e($node['package_name']) : '-',
        'reg_status'   => $node['reg_status'] ?? '-',
        'joined'       => $node['created_at'] ? formatDate($node['created_at']) : '-',
        'direct_children' => (int)$node['direct_children'],
        'total_commission' => formatRupiah((float)$node['total_commission']),
    ]
]);
