<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
requireLogin();

$db     = getDB();
$action = $_GET['action'] ?? '';

if ($action === 'check') {
    $code  = sanitize($_GET['code'] ?? '');
    $total = (float)($_GET['total'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1 AND (expired_at IS NULL OR expired_at > NOW()) AND (usage_limit IS NULL OR used_count < usage_limit)");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $v = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$v) {
        echo json_encode(['success' => false, 'message' => 'Voucher tidak valid atau sudah kadaluarsa.']);
        exit;
    }
    if ($total < $v['min_order']) {
        echo json_encode(['success' => false, 'message' => 'Minimum order ' . formatRupiah($v['min_order']) . ' untuk memakai voucher ini.']);
        exit;
    }

    $discount = $v['discount_type'] === 'percentage'
        ? min($total * $v['discount_value'] / 100, $v['max_discount'] ?? PHP_INT_MAX)
        : (float)$v['discount_value'];

    $final = max(0, $total - $discount);

    echo json_encode([
        'success'         => true,
        'voucher_id'      => $v['id'],
        'discount'        => $discount,
        'discount_display'=> formatRupiah($discount),
        'final'           => $final,
        'final_display'   => formatRupiah($final),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
