<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$db     = getDB();
$user   = currentUser();
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

function addToCart(): void {
    global $db, $user;
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = max(1, (int)($_POST['qty'] ?? 1));
    $notes      = sanitize($_POST['notes'] ?? '');

    // Cek produk ada dan available
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_available = TRUE AND stock > 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak tersedia.']);
        return;
    }

    // Cek apakah cart punya produk dari restoran lain
    $stmt2 = $db->prepare("SELECT p.restaurant_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? LIMIT 1");
    $stmt2->execute([$user['id']]);
    $existing_resto = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($existing_resto && $existing_resto['restaurant_id'] != $product['restaurant_id']) {
        echo json_encode(['success' => false, 'message' => 'Keranjang sudah berisi produk dari restoran lain. Kosongkan dulu.']);
        return;
    }

    // Insert atau update qty (PostgreSQL ON CONFLICT)
    $stmt3 = $db->prepare("
        INSERT INTO cart (user_id, product_id, qty, notes)
        VALUES (?, ?, ?, ?)
        ON CONFLICT (user_id, product_id)
        DO UPDATE SET qty = cart.qty + EXCLUDED.qty, notes = EXCLUDED.notes
    ");
    $stmt3->execute([$user['id'], $product_id, $qty, $notes]);

    $cart_count = getCartCount();
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
}

function updateQty(int $delta): void {
    global $db, $user;
    $cart_id = (int)($_POST['cart_id'] ?? 0);

    $stmt = $db->prepare("SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->execute([$cart_id, $user['id']]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cart) {
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
        return;
    }

    $new_qty = $cart['qty'] + $delta;

    if ($new_qty <= 0) {
        $del = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $del->execute([$cart_id, $user['id']]);
        $qty = 0;
    } elseif ($new_qty > $cart['stock']) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi.']);
        return;
    } else {
        $upd = $db->prepare("UPDATE cart SET qty = ? WHERE id = ?");
        $upd->execute([$new_qty, $cart_id]);
        $qty = $new_qty;
    }

    echo json_encode([
        'success'    => true,
        'qty'        => $qty,
        'total'      => getCartTotal(),
        'cart_count' => getCartCount()
    ]);
}

function removeItem(): void {
    global $db, $user;
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user['id']]);
    echo json_encode(['success' => true, 'total' => getCartTotal(), 'cart_count' => getCartCount()]);
}

function clearCart(): void {
    global $db, $user;
    $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    echo json_encode(['success' => true]);
}

function getCount(): void {
    echo json_encode(['count' => getCartCount()]);
}

function getCartCount(): int {
    global $db, $user;
    $stmt = $db->prepare("SELECT COALESCE(SUM(qty),0) as t FROM cart WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    return (int)($stmt->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
}

function getCartTotal(): float {
    global $db, $user;
    $stmt = $db->prepare("SELECT COALESCE(SUM(c.qty * p.price),0) as t FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user['id']]);
    return (float)($stmt->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
}
