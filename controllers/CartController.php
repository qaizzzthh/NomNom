<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$db   = getDB();
$user = currentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':    addToCart();    break;
    case 'inc':    updateQty(1);  break;
    case 'dec':    updateQty(-1); break;
    case 'remove': removeItem();  break;
    case 'clear':  clearCart();   break;
    case 'count':  getCount();    break;
    default: echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
}

function addToCart() {
    global $db, $user;
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = max(1, (int)($_POST['qty'] ?? 1));
    $notes      = sanitize($_POST['notes'] ?? '');

    // Cek produk ada dan available
    $product = $db->query("SELECT * FROM products WHERE id = $product_id AND is_available = 1 AND stock > 0")->fetch_assoc();
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak tersedia.']);
        return;
    }

    // Cek apakah cart punya produk dari restoran lain
    $existing_resto = $db->query("SELECT p.restaurant_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = {$user['id']} LIMIT 1")->fetch_assoc();
    if ($existing_resto && $existing_resto['restaurant_id'] != $product['restaurant_id']) {
        echo json_encode(['success' => false, 'message' => 'Keranjang sudah berisi produk dari restoran lain. Kosongkan dulu.']);
        return;
    }

    // Insert atau update qty
    $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, qty, notes) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), notes = VALUES(notes)");
    $stmt->bind_param("iiis", $user['id'], $product_id, $qty, $notes);
    $stmt->execute();
    $stmt->close();

    $cart_count = getCartCount();
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
}

function updateQty($delta) {
    global $db, $user;
    $cart_id = (int)($_POST['cart_id'] ?? 0);

    $cart = $db->query("SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = $cart_id AND c.user_id = {$user['id']}")->fetch_assoc();
    if (!$cart) {
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
        return;
    }

    $new_qty = $cart['qty'] + $delta;

    if ($new_qty <= 0) {
        $db->query("DELETE FROM cart WHERE id = $cart_id AND user_id = {$user['id']}");
        $qty = 0;
    } elseif ($new_qty > $cart['stock']) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi.']);
        return;
    } else {
        $db->query("UPDATE cart SET qty = $new_qty WHERE id = $cart_id");
        $qty = $new_qty;
    }

    echo json_encode([
        'success' => true,
        'qty' => $qty,
        'total' => getCartTotal(),
        'cart_count' => getCartCount()
    ]);
}

function removeItem() {
    global $db, $user;
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $db->query("DELETE FROM cart WHERE id = $cart_id AND user_id = {$user['id']}");
    echo json_encode(['success' => true, 'total' => getCartTotal(), 'cart_count' => getCartCount()]);
}

function clearCart() {
    global $db, $user;
    $db->query("DELETE FROM cart WHERE user_id = {$user['id']}");
    echo json_encode(['success' => true]);
}

function getCount() {
    echo json_encode(['count' => getCartCount()]);
}

function getCartCount() {
    global $db, $user;
    return $db->query("SELECT COALESCE(SUM(qty),0) as t FROM cart WHERE user_id = {$user['id']}")->fetch_assoc()['t'] ?? 0;
}

function getCartTotal() {
    global $db, $user;
    return $db->query("SELECT COALESCE(SUM(c.qty * p.price),0) as t FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = {$user['id']}")->fetch_assoc()['t'] ?? 0;
}
