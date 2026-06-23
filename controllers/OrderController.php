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
function placeOrder(): void {
    requireRole('buyer');
    $db   = getDB();
    $user = currentUser();

    $address_id     = (int)($_POST['address_id'] ?? 0);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $notes          = sanitize($_POST['notes'] ?? '');
    $voucher_id     = (int)($_POST['voucher_id'] ?? 0) ?: null;

    // Ambil cart items
    $stmt = $db->prepare("SELECT c.*, p.price, p.stock, p.name as pname, p.restaurant_id, p.seller_id FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$user['id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($cart_items)) {
        flash('error', 'Keranjang kosong.');
        redirect(BASE_URL . '/views/buyer/cart.php');
        return;
    }

    // Validasi alamat
    $stmt2 = $db->prepare("SELECT * FROM buyer_addresses WHERE id = ? AND user_id = ?");
    $stmt2->execute([$address_id, $user['id']]);
    $addr = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (!$addr) {
        flash('error', 'Alamat tidak valid.');
        redirect(BASE_URL . '/views/buyer/checkout.php');
        return;
    }

    $restaurant_id = $cart_items[0]['restaurant_id'];
    $seller_id     = $cart_items[0]['seller_id'];

    // Hitung subtotal
    $subtotal = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $cart_items));

    // Hitung ongkir
    $stmt3 = $db->prepare("SELECT latitude, longitude FROM restaurants WHERE id = ?");
    $stmt3->execute([$restaurant_id]);
    $resto        = $stmt3->fetch(PDO::FETCH_ASSOC);
    $delivery_fee = calculateDeliveryFee($resto, $addr);

    // Hitung voucher
    $discount = 0;
    if ($voucher_id) {
        $stmt4 = $db->prepare("SELECT * FROM vouchers WHERE id = ? AND is_active = TRUE AND (expired_at IS NULL OR expired_at > NOW()) AND (usage_limit IS NULL OR used_count < usage_limit)");
        $stmt4->execute([$voucher_id]);
        $v = $stmt4->fetch(PDO::FETCH_ASSOC);
        if ($v && $subtotal >= $v['min_order']) {
            $discount = $v['discount_type'] === 'percentage'
                ? min($subtotal * $v['discount_value'] / 100, $v['max_discount'] ?? PHP_INT_MAX)
                : $v['discount_value'];
        }
    }

    $total_amount = max(0, $subtotal + $delivery_fee - $discount);

    // Cek minimum order restoran
    $stmt5 = $db->prepare("SELECT min_order FROM restaurants WHERE id = ?");
    $stmt5->execute([$restaurant_id]);
    $min_order = $stmt5->fetch(PDO::FETCH_ASSOC)['min_order'];
    if ($subtotal < $min_order) {
        flash('error', 'Belum memenuhi minimum order ' . formatRupiah($min_order));
        redirect(BASE_URL . '/views/buyer/checkout.php');
        return;
    }

    $db->beginTransaction();
    try {
        $order_code = generateOrderCode();

        // Insert order
        $stmt6 = $db->prepare("INSERT INTO orders (order_code, buyer_id, restaurant_id, address_id, voucher_id, subtotal, delivery_fee, discount, total_amount, notes) VALUES (?,?,?,?,?,?,?,?,?,?) RETURNING id");
        $stmt6->execute([$order_code, $user['id'], $restaurant_id, $address_id, $voucher_id, $subtotal, $delivery_fee, $discount, $total_amount, $notes]);
        $order_id = (int)$stmt6->fetch(PDO::FETCH_ASSOC)['id'];

        // Insert order_items + update stok
        foreach ($cart_items as $item) {
            if ($item['stock'] < $item['qty']) throw new Exception("Stok {$item['pname']} tidak cukup.");
            $sub = $item['qty'] * $item['price'];

            $si = $db->prepare("INSERT INTO order_items (order_id, product_id, qty, price, subtotal, notes) VALUES (?,?,?,?,?,?)");
            $si->execute([$order_id, $item['product_id'], $item['qty'], $item['price'], $sub, $item['notes']]);

            $us = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $us->execute([$item['qty'], $item['product_id']]);

            $ua = $db->prepare("UPDATE products SET is_available = FALSE WHERE id = ? AND stock <= 0");
            $ua->execute([$item['product_id']]);
        }

        // Insert payment record
        $sp = $db->prepare("INSERT INTO payments (order_id, payment_method, amount) VALUES (?,?,?)");
        $sp->execute([$order_id, $payment_method, $total_amount]);

        // AUTO: tracking log
        insertTracking($order_id, 'pending', 'Pesanan berhasil dibuat', $user['id']);

        // AUTO: update voucher used_count
        if ($voucher_id) {
            $sv = $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
            $sv->execute([$voucher_id]);
        }

        // Clear cart
        $sc = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $sc->execute([$user['id']]);

        // Notifikasi buyer
        sendNotif($user['id'], "Pesanan $order_code Dibuat 📦", "Pesanan Anda sedang menunggu konfirmasi restoran.", 'order', $order_id);

        // Notifikasi seller
        $ss = $db->prepare("SELECT seller_id FROM restaurants WHERE id = ?");
        $ss->execute([$restaurant_id]);
        $seller_id_row = $ss->fetch(PDO::FETCH_ASSOC);
        if ($seller_id_row) {
            sendNotif($seller_id_row['seller_id'], "Pesanan Baru Masuk! 🔔", "Ada pesanan baru $order_code dari {$user['name']}.", 'order', $order_id);
        }

        $db->commit();
        flash('success', 'Pesanan berhasil dibuat!');
        redirect(BASE_URL . '/views/buyer/order_detail.php?id=' . $order_id);

    } catch (Exception $e) {
        $db->rollBack();
        flash('error', 'Gagal membuat pesanan: ' . $e->getMessage());
        redirect(BASE_URL . '/views/buyer/checkout.php');
    }
}

// ─── SELLER CONFIRM ──────────────────────────────────
function sellerConfirm(): void {
    requireRole('seller');
    $db       = getDB();
    $user     = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = sanitize($_POST['new_status'] ?? '');
    $allowed  = ['confirmed', 'preparing', 'on_delivery', 'cancelled'];

    if (!in_array($new_status, $allowed)) {
        flash('error', 'Status tidak valid.');
        redirect(BASE_URL . '/views/seller/orders.php');
        return;
    }

    $stmt = $db->prepare("SELECT o.*, r.seller_id FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.id = ? AND r.seller_id = ?");
    $stmt->execute([$order_id, $user['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        flash('error', 'Pesanan tidak ditemukan.');
        redirect(BASE_URL . '/views/seller/orders.php');
        return;
    }

    $db->beginTransaction();
    try {
        if ($new_status === 'cancelled') {
            // Kembalikan stok
            $si = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $si->execute([$order_id]);
            $items = $si->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                $us = $db->prepare("UPDATE products SET stock = stock + ?, is_available = TRUE WHERE id = ?");
                $us->execute([$item['qty'], $item['product_id']]);
            }
        }

        $upd = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $order_id]);
        
        insertTracking($order_id, $new_status, statusMessage($new_status), $user['id']);
        sendNotif($order['buyer_id'], "Status Pesanan Diperbarui", "Pesanan {$order['order_code']} " . statusMessage($new_status), 'order', $order_id);
        
        $db->commit();
        flash('success', 'Status pesanan berhasil diperbarui.');
    } catch (Exception $e) {
        $db->rollBack();
        flash('error', 'Gagal memperbarui status pesanan: ' . $e->getMessage());
    }
    
    redirect(BASE_URL . '/views/seller/orders.php');
}

// ─── DRIVER DISPATCH ─────────────────────────────────
function driverDispatch(): void {
    requireRole('driver');
    $db       = getDB();
    $user     = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND status = 'preparing' AND driver_id IS NULL");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        flash('error', 'Pesanan tidak tersedia untuk diambil.');
        redirect(BASE_URL . '/views/driver/available_orders.php');
        return;
    }

    $upd = $db->prepare("UPDATE orders SET driver_id = ?, status = 'on_delivery' WHERE id = ?");
    $upd->execute([$user['id'], $order_id]);
    insertTracking($order_id, 'on_delivery', 'Driver sedang menuju lokasi Anda', $user['id']);
    sendNotif($order['buyer_id'], "Pesanan Sedang Dikirim 🛵", "Driver {$user['name']} sedang mengantar pesanan Anda.", 'order', $order_id);

    flash('success', 'Anda berhasil mengambil pesanan ini.');
    redirect(BASE_URL . '/views/driver/my_deliveries.php');
}

// ─── MARK DELIVERED ──────────────────────────────────
function markDelivered(): void {
    requireRole(['driver', 'admin']);
    $db       = getDB();
    $user     = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);

    $upd = $db->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
    $upd->execute([$order_id]);

    // AUTO: verifikasi payment jika COD
    $sp = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
    $sp->execute([$order_id]);
    $payment = $sp->fetch(PDO::FETCH_ASSOC);
    if ($payment && $payment['payment_method'] === 'cod') {
        $sv = $db->prepare("UPDATE payments SET status = 'verified', verified_at = NOW() WHERE order_id = ?");
        $sv->execute([$order_id]);
    }

    $so = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $so->execute([$order_id]);
    $order = $so->fetch(PDO::FETCH_ASSOC);
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
function cancelOrder(): void {
    requireLogin();
    $db       = getDB();
    $user     = currentUser();
    $order_id = (int)($_POST['order_id'] ?? 0);

    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = 'pending'");
    $stmt->execute([$order_id, $user['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        flash('error', 'Pesanan tidak bisa dibatalkan.');
        redirect(BASE_URL . '/views/buyer/orders.php');
        return;
    }

    $db->beginTransaction();
    // Kembalikan stok
    $si = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $si->execute([$order_id]);
    $items = $si->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        $us = $db->prepare("UPDATE products SET stock = stock + ?, is_available = TRUE WHERE id = ?");
        $us->execute([$item['qty'], $item['product_id']]);
    }
    $uo = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $uo->execute([$order_id]);
    insertTracking($order_id, 'cancelled', 'Pesanan dibatalkan oleh pembeli', $user['id']);
    $db->commit();

    flash('success', 'Pesanan berhasil dibatalkan.');
    redirect(BASE_URL . '/views/buyer/orders.php');
}

// ─── GET STATUS (AJAX polling) ────────────────────────
function getStatus(): void {
    header('Content-Type: application/json');
    requireLogin();
    $db       = getDB();
    $order_id = (int)($_GET['id'] ?? 0);
    $stmt     = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status' => $order['status'] ?? null]);
    exit;
}

// ─── HELPERS ─────────────────────────────────────────
function insertTracking(int $order_id, string $status, string $desc, int $changed_by): void {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO order_tracking (order_id, status, description, changed_by) VALUES (?,?,?,?)");
    $stmt->execute([$order_id, $status, $desc, $changed_by]);
}

function sendNotif(int $userId, string $title, string $message, string $type = 'order', ?int $refId = null): void {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $title, $message, $type, $refId]);
}

function calculateDeliveryFee(array $resto, array $addr): float {
    if (!$resto['latitude'] || !$addr['latitude']) return 10000;
    $lat1 = deg2rad($resto['latitude']); $lon1 = deg2rad($resto['longitude']);
    $lat2 = deg2rad($addr['latitude']);  $lon2 = deg2rad($addr['longitude']);
    $dlat = $lat2 - $lat1; $dlon = $lon2 - $lon1;
    $a    = sin($dlat/2)**2 + cos($lat1)*cos($lat2)*sin($dlon/2)**2;
    $km   = 6371 * 2 * asin(sqrt($a));
    return max(5000, round($km * 3000 / 1000) * 1000);
}

function statusMessage(string $status): string {
    $msgs = [
        'confirmed'   => 'dikonfirmasi oleh restoran.',
        'preparing'   => 'sedang dimasak.',
        'on_delivery' => 'sedang dalam pengiriman.',
        'delivered'   => 'telah tiba di tujuan.',
        'cancelled'   => 'dibatalkan.',
    ];
    return $msgs[$status] ?? $status;
}
