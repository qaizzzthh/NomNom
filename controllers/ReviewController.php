<?php
require_once __DIR__ . '/../config/database.php';
requireRole('buyer');

$db   = getDB();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/views/buyer/orders.php');
}

$order_id   = (int)($_POST['order_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$comment    = sanitize($_POST['comment'] ?? '');

if ($rating < 1 || $rating > 5) {
    flash('error', 'Rating harus antara 1–5.');
    redirect(BASE_URL . '/views/buyer/order_detail.php?id=' . $order_id);
    return;
}

// Pastikan order ini milik buyer dan statusnya delivered
$order = $db->query("SELECT * FROM orders WHERE id = $order_id AND buyer_id = {$user['id']} AND status = 'delivered'")->fetch_assoc();
if (!$order) {
    flash('error', 'Tidak bisa membuat review untuk pesanan ini.');
    redirect(BASE_URL . '/views/buyer/orders.php');
    return;
}

// Cek belum pernah review produk ini di order ini
$exists = $db->query("SELECT id FROM review WHERE user_id = {$user['id']} AND product_id = $product_id AND order_id = $order_id")->num_rows;
if ($exists > 0) {
    flash('error', 'Anda sudah memberi review untuk menu ini.');
    redirect(BASE_URL . '/views/buyer/order_detail.php?id=' . $order_id);
    return;
}

$photo = null;
if (!empty($_FILES['photo']['name'])) {
    $photo = uploadFile($_FILES['photo'], 'reviews');
}

$stmt = $db->prepare("INSERT INTO review (product_id, order_id, user_id, rating, comment, photo) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("iiiiss", $product_id, $order_id, $user['id'], $rating, $comment, $photo);
$stmt->execute();
$stmt->close();

flash('success', 'Review berhasil dikirim! Terima kasih. ⭐');
redirect(BASE_URL . '/views/buyer/order_detail.php?id=' . $order_id);
