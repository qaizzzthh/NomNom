<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();
$user = currentUser();

// Handle Verify
if (isset($_GET['verify_payment_id'])) {
    $payment_id = (int)$_GET['verify_payment_id'];
    $payment = $db->query("SELECT * FROM payments WHERE id = $payment_id")->fetch_assoc();
    
    if ($payment) {
        $db->query("UPDATE payments SET status = 'verified', verified_at = NOW() WHERE id = $payment_id");
        $order_id = $payment['order_id'];
        
        $order = $db->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
        if ($order && $order['status'] === 'pending') {
            $db->query("UPDATE orders SET status = 'confirmed' WHERE id = $order_id");
            
            // Log tracking
            $desc = 'Pembayaran transfer diverifikasi oleh admin';
            $stmt = $db->prepare("INSERT INTO order_tracking (order_id, status, description, changed_by) VALUES (?, 'confirmed', ?, ?)");
            $stmt->bind_param("isi", $order_id, $desc, $user['id']);
            $stmt->execute();
            $stmt->close();
            
            // Notify buyer
            $buyer_title = 'Pembayaran Terverifikasi! 💳';
            $buyer_msg = "Pembayaran transfer untuk pesanan {$order['order_code']} berhasil diverifikasi. Pesanan sedang diproses oleh restoran.";
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, 'payment', ?)");
            $stmt->bind_param("issi", $order['buyer_id'], $buyer_title, $buyer_msg, $order_id);
            $stmt->execute();
            $stmt->close();
            
            // Notify seller
            $resto = $db->query("SELECT seller_id FROM restaurants WHERE id = {$order['restaurant_id']}")->fetch_assoc();
            if ($resto) {
                $seller_title = 'Pesanan Baru Terbayar 🔔';
                $seller_msg = "Pesanan {$order['order_code']} telah dibayar oleh pembeli dan dikonfirmasi oleh admin. Silakan mulai memasak.";
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, 'order', ?)");
                $stmt->bind_param("issi", $resto['seller_id'], $seller_title, $seller_msg, $order_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        flash('success', 'Pembayaran berhasil diverifikasi!');
    }
    redirect(BASE_URL . '/views/admin/payments.php');
}

// Handle Reject
if (isset($_GET['reject_payment_id'])) {
    $payment_id = (int)$_GET['reject_payment_id'];
    $payment = $db->query("SELECT * FROM payments WHERE id = $payment_id")->fetch_assoc();
    
    if ($payment) {
        $db->query("UPDATE payments SET status = 'rejected' WHERE id = $payment_id");
        $order_id = $payment['order_id'];
        
        $order = $db->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
        if ($order) {
            // Notify buyer
            $buyer_title = 'Pembayaran Ditolak ❌';
            $buyer_msg = "Bukti transfer pembayaran untuk pesanan {$order['order_code']} ditolak karena tidak valid. Silakan unggah kembali bukti pembayaran yang benar.";
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, 'payment', ?)");
            $stmt->bind_param("issi", $order['buyer_id'], $buyer_title, $buyer_msg, $order_id);
            $stmt->execute();
            $stmt->close();
        }
        flash('success', 'Pembayaran ditolak.');
    }
    redirect(BASE_URL . '/views/admin/payments.php');
}

// Query pending and history bank transfer payments
$payments = $db->query("SELECT p.*, o.order_code, o.total_amount, u.name as buyer_name 
                        FROM payments p 
                        JOIN orders o ON p.order_id = o.id 
                        JOIN users u ON o.buyer_id = u.id
                        WHERE p.payment_method = 'transfer'
                        ORDER BY p.status DESC, p.id DESC")->fetch_all(MYSQLI_ASSOC);

$title = 'Verifikasi Pembayaran';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">💳 Verifikasi Pembayaran Transfer</h2>
    <p class="page-subtitle">Periksa unggahan screenshot bukti transfer bank pembeli dan lakukan verifikasi</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($payments)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">💳</div>
      <h3>Belum ada pembayaran transfer masuk</h3>
      <p>Pembayaran transfer yang dilakukan pembeli akan tercatat di sini.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Bukti Bayar</th>
          <th>Kode Pesanan</th>
          <th>Nama Pembeli</th>
          <th>Total Jumlah</th>
          <th>Tanggal</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td>
            <?php if ($p['proof']): ?>
            <a href="<?= BASE_URL ?>/uploads/<?= $p['proof'] ?>" target="_blank">
              <img src="<?= BASE_URL ?>/uploads/<?= $p['proof'] ?>" style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid var(--border)">
            </a>
            <?php else: ?>
            <span style="color:var(--danger); font-size:11px">Belum diunggah</span>
            <?php endif; ?>
          </td>
          <td><strong>#<?= $p['order_code'] ?></strong></td>
          <td><?= sanitize($p['buyer_name']) ?></td>
          <td><strong><?= formatRupiah($p['amount']) ?></strong></td>
          <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
          <td>
            <span class="status-badge status-<?= $p['status'] ?>"><?= strtoupper($p['status']) ?></span>
          </td>
          <td>
            <div style="display:flex; gap:6px">
              <?php if ($p['status'] === 'pending' && $p['proof']): ?>
              <a href="?verify_payment_id=<?= $p['id'] ?>" class="btn btn-success btn-sm">Verifikasi</a>
              <a href="?reject_payment_id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menolak pembayaran ini?')">Tolak</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
