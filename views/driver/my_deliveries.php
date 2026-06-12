<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('driver');

$db = getDB();
$user = currentUser();

// Fetch active order
$oq = $db->prepare("SELECT o.*, r.name as resto_name, r.address as resto_addr, u.phone as resto_phone, a.address as buyer_addr, a.recipient_name, a.phone as buyer_phone, p.payment_method, p.status as payment_status 
                     FROM orders o 
                     JOIN restaurants r ON o.restaurant_id = r.id 
                     JOIN users u ON r.seller_id = u.id
                     JOIN buyer_addresses a ON o.address_id = a.id
                     JOIN payments p ON p.order_id = o.id
                     WHERE o.driver_id = ? AND o.status = 'on_delivery' 
                     LIMIT 1");
$oq->execute([$user['id']]);
$order = $oq->fetch(PDO::FETCH_ASSOC);

$title = 'Pengiriman Saya';
$role  = 'driver';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🛵 Pengiriman Aktif Saya</h2>
    <p class="page-subtitle">Antarkan makanan ke lokasi pembeli dengan selamat dan tepat waktu</p>
  </div>
</div>

<?php if (!$order): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">🛵</div>
      <h3>Tidak ada pengiriman aktif</h3>
      <p>Ambil pesanan baru terlebih dahulu untuk dikirim.</p>
      <div style="margin-top:16px">
        <a href="<?= BASE_URL ?>/views/driver/available_orders.php" class="btn btn-primary">Cari Order</a>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
  <div style="display:grid; grid-template-columns: 2fr 1fr; gap:28px; align-items:start">
    <div style="display:grid; gap:24px">
      <!-- LOCATIONS -->
      <div class="card">
        <div class="card-header">
          <h3>📍 Rute Pengiriman</h3>
        </div>
        <div class="card-body" style="display:grid; gap:20px; font-size:13px">
          <div>
            <strong style="color:var(--primary)">1. AMBIL DI (RESTORAN):</strong>
            <p style="font-size:15px; font-weight:700; margin-top:4px"><?= sanitize($order['resto_name']) ?></p>
            <p style="color:var(--text-muted); margin-top:2px"><?= sanitize($order['resto_addr']) ?></p>
            <p style="color:var(--text-muted)">📞 <?= sanitize($order['resto_phone']) ?></p>
          </div>
          <hr style="border:none; border-top:1px solid var(--border)">
          <div>
            <strong style="color:var(--success)">2. ANTAR KE (PELANGGAN):</strong>
            <p style="font-size:15px; font-weight:700; margin-top:4px"><?= sanitize($order['recipient_name']) ?></p>
            <p style="color:var(--text-muted); margin-top:2px"><?= sanitize($order['buyer_addr']) ?></p>
            <p style="color:var(--text-muted)">📞 <?= sanitize($order['buyer_phone']) ?></p>
          </div>
        </div>
      </div>

      <!-- NOTES -->
      <?php if ($order['notes']): ?>
      <div class="card">
        <div class="card-header">
          <h3>📝 Catatan Pengiriman</h3>
        </div>
        <div class="card-body">
          <p style="font-size:14px; font-style:italic">"<?= sanitize($order['notes']) ?>"</p>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- BILLING AND ACTIONS -->
    <div style="display:grid; gap:20px">
      <div class="card">
        <div class="card-header">
          <h3>Rincian Pembayaran</h3>
        </div>
        <div class="card-body" style="font-size:13px">
          <div style="display:grid; gap:10px">
            <div>Metode Pembayaran: <strong style="color:var(--primary)"><?= strtoupper($order['payment_method']) ?></strong></div>
            <div>Ongkos Kirim Driver: <strong style="color:var(--success)"><?= formatRupiah($order['delivery_fee']) ?></strong></div>
            
            <?php if ($order['payment_method'] === 'cod'): ?>
              <div class="alert alert-warning" style="margin:10px 0 0; padding:10px; font-size:12px">
                ⚠️ <strong>PENTING:</strong> Kumpulkan pembayaran tunai sebesar <strong><?= formatRupiah($order['total_amount']) ?></strong> dari pembeli saat menyerahkan makanan!
              </div>
            <?php else: ?>
              <div style="color:var(--success); font-weight:700; margin-top:10px">✅ Non-Tunai (Sudah Terbayar / Transfer)</div>
            <?php endif; ?>
          </div>

          <form action="<?= BASE_URL ?>/controllers/OrderController.php?action=deliver" method="POST" style="margin-top:20px" onsubmit="return confirm('Apakah pesanan ini benar-benar sudah diserahkan ke pelanggan?')">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <button type="submit" class="btn btn-primary btn-block btn-lg">Tandai Selesai Diantar ✅</button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
