<?php
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'place':    placeOrder();       break;
    case 'cancel':   cancelOrder();      break;
    case 'confirm':  sellerConfirm();    break;
    case 'dispatch': driverDispatch();   break;
    case 'deliver':  markDelivered();    break;
    case 'status':   getStatus();        break;
    default: redirect(BASE_URL . '/index.php');
}

// ─── PLACE ORDER ─────────────────────────────────────
function placeOrder() {
    requireRole('buyer');
    $db   = getDB();
    $user = currentUser();

    $address_id  = (int)($_POST['address_id'] ?? 0);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $notes       = sanitize($_POST['notes'] ?? '');
    $voucher_id  = (int)($_POST['voucher_id'] ?? 0) ?: null;

    // Ambil cart items
    $cart_items = $db->query("SELECT c.*, p.price, p.stock, p.name as pname, p.restaurant_id, p.seller_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = {$user['id']}")->fetch_all(MYSQLI_ASSOC);
    if (empty($cart_items)) {
        flash('error', 'Keranjang kosong.');
        redirect(BASE_URL . '/views/buyer/cart.php');
        return;
    }

    // Validasi alamat
    $addr = $db->query("SELECT * FROM buyer_addresses WHERE id = $address_id AND user_id = {$user['id']}")->fetch_assoc();
    if (!$addr) {
        flash('error', 'Alamat tidak valid.');
        redirect(BASE_URL . '/views/buyer/checkout.php');
        return;
    }

    $restaurant_id = $cart_items[0]['restaurant_id'];
    $seller_id     = $cart_items[0]['seller_id'];

    // Hitung subtotal
    $subtotal = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $cart_items));

    // Hitung ongkir sederhana (flat Rp 5.000 per km, min Rp 5.000)
    $resto = $db->query("SELECT latitude, longitude FROM restaurants WHERE id = $restaurant_id")->fetch_assoc();
    $delivery_fee = calculateDeliveryFee($resto, $addr);

    // Hitung voucher
    $discount = 0;
    if ($voucher_id) {
        $v = $db->query("SELECT * FROM vouchers WHERE id = $voucher_id AND is_active = 1 AND (expired_at IS NULL OR expired_at > NOW()) AND (usage_limit IS NULL OR used_count < usage_limit)")->fetch_assoc();
        if ($v && $subtotal >= $v['min_order']) {
            $discount = $v['discount_type'] === 'percentage'
                ? min($subtotal * $v['discount_value'] / 100, $v['max_discount'] ?? PHP_INT_MAX)
                : $v['discount_value'];
        }
    }

    $total_amount = max(0, $subtotal + $delivery_fee - $discount);

    // Cek minimum order restoran
    $min_order = $db->query("SELECT min_order FROM restaurants WHERE id = $restaurant_id")->fetch_assoc()['min_order'];
    if ($subtotal < $min_order) {
        flash('error', 'Belum memenuhi minimum order ' . formatRupiah($min_order));
        redirect(BASE_URL . '/views/buyer/checkout.php');
        return;
    }

    $db->begin_transaction();
    try {
        $order_code = generateOrderCode();

        // Insert order
        $stmt = $db->prepare("INSERT INTO orders (order_code, buyer_id, restaurant_id, address_id, voucher_id, subtotal, delivery_fee, discount, total_amount, notes) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("siiiidddds", $order_code, $user['id'], $restaurant_id, $address_id, $voucher_id, $subtotal, $delivery_fee, $discount, $total_amount, $notes);
        $stmt->execute();
        $order_id = $db->insert_id;
        $stmt->close();

        // Insert order_items + update stok
        foreach ($cart_items as $item) {
            if ($item['stock'] < $item['qty']) throw new Exception("Stok {$item['pname']} tidak cukup.");
            $sub = $item['qty'] * $item['price'];
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, qty, price, subtotal, notes) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("iiidds", $order_id, $item['product_id'], $item['qty'], $item['price'], $sub, $item['notes']);
            $stmt->execute();
            $stmt->close();

            // AUTO: kurangi stok
            $db->query("UPDATE products SET stock = stock - {$item['qty']} WHERE id = {$item['product_id']}");
            // AUTO: set is_available = 0 jika stok habis
            $db->query("UPDATE products SET is_available = 0 WHERE id = {$item['product_id']} AND stock <= 0");
        }

        // Insert payment record
        $stmt = $db->prepare("INSERT INTO payments (order_id, payment_method, amount) VALUES (?,?,?)");
        $stmt->bind_param("isd", $order_id, $payment_method, $total_amount);
        $stmt->execute();
        $stmt->close();

        // AUTO: tracking log
        insertTracking($order_id, 'pending', 'Pesanan berhasil dibuat', $user['id']);

        // AUTO: update voucher used_count
        if ($voucher_id) {
            $db->query("UPDATE vouchers SET used_count = used_count + 1 WHERE id = $voucher_id");
        }

        // Clear cart
        $db->query("DELETE FROM cart WHERE user_id = {$user['id']}");

        // AUTO: notifikasi buyer
        sendNotif($user['id'], "Pesanan $order_code Dibuat 📦", "Pesanan Anda sedang menunggu konfirmasi restoran.", 'order', $order_id);

        // AUTO: notifikasi seller
        $seller_id_row = $db->query("SELECT seller_id FROM restaurants WHERE id = $restaurant_id")->fetch_assoc();
        if ($seller_id_row) {
            sendNotif($seller_id_row['seller_id'], "Pesanan Baru Masuk! 🔔", "Ada pesanan baru $order_code dari {$user['name']}.", 'order', $order_id);
        }

        $db->commit();
        flash('success', 'Pesanan berhasil dibuat!');
        redirect(BASE_URL . '/views/buyer/order_detail.php?id=' . $order_id);

    } catch (Exception $e) {
        $db->rollback();
        flash('error', 'Gagal membuat pesanan: ' . $e->getMessage());
        redirect(BASE_URL . '/views/buyer/checkout.php');
    }
}

// ─── SELLER CONFIRM ──────────────────────────────────
function sellerConfirm() {
    requireRole('seller');
    $db   = getDB();
    $user = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = sanitize($_POST['new_status'] ?? '');
    $allowed = ['confirmed', 'preparing', 'on_delivery', 'cancelled'];

    if (!in_array($new_status, $allowed)) {
        flash('error', 'Status tidak valid.');
        redirect(BASE_URL . '/views/seller/orders.php');
        return;
    }

    // Pastikan order milik restoran seller ini
    $order = $db->query("SELECT o.*, r.seller_id FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.id = $order_id AND r.seller_id = {$user['id']}")->fetch_assoc();
    if (!$order) {
        flash('error', 'Pesanan tidak ditemukan.');
        redirect(BASE_URL . '/views/seller/orders.php');
        return;
    }

    $db->query("UPDATE orders SET status = '$new_status' WHERE id = $order_id");
    insertTracking($order_id, $new_status, statusMessage($new_status), $user['id']);
    sendNotif($order['buyer_id'], "Status Pesanan Diperbarui", "Pesanan {$order['order_code']} " . statusMessage($new_status), 'order', $order_id);

    flash('success', 'Status pesanan berhasil diperbarui.');
    redirect(BASE_URL . '/views/seller/orders.php');
}

// ─── DRIVER DISPATCH ─────────────────────────────────
function driverDispatch() {
    requireRole('driver');
    $db   = getDB();
    $user = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);

    $order = $db->query("SELECT * FROM orders WHERE id = $order_id AND status = 'preparing' AND driver_id IS NULL")->fetch_assoc();
    if (!$order) {
        flash('error', 'Pesanan tidak tersedia untuk diambil.');
        redirect(BASE_URL . '/views/driver/available_orders.php');
        return;
    }

    $db->query("UPDATE orders SET driver_id = {$user['id']}, status = 'on_delivery' WHERE id = $order_id");
    insertTracking($order_id, 'on_delivery', 'Driver sedang menuju lokasi Anda', $user['id']);
    sendNotif($order['buyer_id'], "Pesanan Sedang Dikirim 🛵", "Driver {$user['name']} sedang mengantar pesanan Anda.", 'order', $order_id);

    flash('success', 'Anda berhasil mengambil pesanan ini.');
    redirect(BASE_URL . '/views/driver/my_deliveries.php');
}

// ─── MARK DELIVERED ──────────────────────────────────
function markDelivered() {
    requireRole(['driver', 'admin']);
    $db   = getDB();
    $user = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);

    $db->query("UPDATE orders SET status = 'delivered' WHERE id = $order_id");
    // AUTO: verifikasi payment jika COD
    $payment = $db->query("SELECT * FROM payments WHERE order_id = $order_id")->fetch_assoc();
    if ($payment && $payment['payment_method'] === 'cod') {
        $db->query("UPDATE payments SET status = 'verified', verified_at = NOW() WHERE order_id = $order_id");
    }

    $order = $db->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
    insertTracking($order_id, 'delivered', 'Pesanan telah tiba di tujuan', $user['id']);
    if ($order) {
        sendNotif($order['buyer_id'], "Pesanan Tiba! ✅", "Pesanan {$order['order_code']} sudah diterima. Jangan lupa beri ulasan!", 'order', $order_id);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        flash('success', 'Pesanan ditandai selesai.');
        redirect(BASE_URL . '/views/driver/my_deliveries.php');
    }
}

// ─── CANCEL ORDER ────────────────────────────────────
function cancelOrder() {
    requireLogin();
    $db   = getDB();
    $user = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);

    $order = $db->query("SELECT * FROM orders WHERE id = $order_id AND buyer_id = {$user['id']} AND status = 'pending'")->fetch_assoc();
    if (!$order) {
        flash('error', 'Pesanan tidak bisa dibatalkan.');
        redirect(BASE_URL . '/views/buyer/orders.php');
        return;
    }

    $db->begin_transaction();
    // Kembalikan stok
    $items = $db->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $item) {
        $db->query("UPDATE products SET stock = stock + {$item['qty']}, is_available = 1 WHERE id = {$item['product_id']}");
    }
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
    insertTracking($order_id, 'cancelled', 'Pesanan dibatalkan oleh pembeli', $user['id']);
    $db->commit();

    flash('success', 'Pesanan berhasil dibatalkan.');
    redirect(BASE_URL . '/views/buyer/orders.php');
}

// ─── GET STATUS (AJAX polling) ────────────────────────
function getStatus() {
    header('Content-Type: application/json');
    requireLogin();
    $db = getDB();
    $order_id = (int)($_GET['id'] ?? 0);
    $order = $db->query("SELECT status FROM orders WHERE id = $order_id")->fetch_assoc();
    echo json_encode(['status' => $order['status'] ?? null]);
    exit;
}

// ─── HELPERS ─────────────────────────────────────────
function insertTracking($order_id, $status, $desc, $changed_by) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO order_tracking (order_id, status, description, changed_by) VALUES (?,?,?,?)");
    $stmt->bind_param("issi", $order_id, $status, $desc, $changed_by);
    $stmt->execute();
    $stmt->close();
}

function sendNotif($userId, $title, $message, $type = 'order', $refId = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?,?,?,?,?)");
    $stmt->bind_param("isssi", $userId, $title, $message, $type, $refId);
    $stmt->execute();
    $stmt->close();
}

function calculateDeliveryFee($resto, $addr) {
    if (!$resto['latitude'] || !$addr['latitude']) return 10000; // default Rp 10.000
    $lat1 = deg2rad($resto['latitude']); $lon1 = deg2rad($resto['longitude']);
    $lat2 = deg2rad($addr['latitude']);  $lon2 = deg2rad($addr['longitude']);
    $dlat = $lat2 - $lat1; $dlon = $lon2 - $lon1;
    $a = sin($dlat/2)**2 + cos($lat1)*cos($lat2)*sin($dlon/2)**2;
    $km = 6371 * 2 * asin(sqrt($a));
    return max(5000, round($km * 3000 / 1000) * 1000); // Rp 3.000/km, min Rp 5.000
}

function statusMessage($status) {
    $msgs = [
        'confirmed'   => 'dikonfirmasi oleh restoran.',
        'preparing'   => 'sedang dimasak.',
        'on_delivery' => 'sedang dalam pengiriman.',
        'delivered'   => 'telah tiba di tujuan.',
        'cancelled'   => 'dibatalkan.',
    ];
    return $msgs[$status] ?? $status;
}
