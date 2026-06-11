<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('driver');

$db = getDB();
$user = currentUser();

if (!$user['is_verified']) {
    flash('error', 'Akun Anda belum diverifikasi admin.');
    redirect(BASE_URL . '/views/driver/dashboard.php');
}

// Fetch orders that are ready/preparing for pick-up and have no driver assigned
$oq = $db->query("SELECT o.*, r.name as resto_name, r.address as resto_addr, a.address as buyer_addr, u.name as buyer_name 
                      FROM orders o 
                      JOIN restaurants r ON o.restaurant_id = r.id 
                      JOIN buyer_addresses a ON o.address_id = a.id
                      JOIN users u ON o.buyer_id = u.id
                      WHERE o.status = 'preparing' AND o.driver_id IS NULL 
                      ORDER BY o.id DESC");
$orders = $oq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Order Tersedia';
$role  = 'driver';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🛵 Order Makanan Tersedia</h2>
    <p class="page-subtitle">Ambil pesanan makanan terdekat untuk segera diantar ke pelanggan</p>
  </div>
</div>

<div style="display:grid; gap:20px">
  <?php if (empty($orders)): ?>
  <div class="card">
    <div class="card-body">
      <div class="empty-state" style="padding:40px 20px">
        <div class="empty-icon">🛵</div>
        <h3>Belum ada order tersedia</h3>
        <p>Mohon tunggu beberapa saat hingga ada pesanan siap diantar.</p>
      </div>
    </div>
  </div>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
    <div class="card">
      <div class="card-header" style="background:var(--bg)">
        <strong>Pesanan #<?= $o['order_code'] ?></strong>
        <span style="font-size:12px; color:var(--text-muted)">Ongkir: <strong><?= formatRupiah($o['delivery_fee']) ?></strong></span>
      </div>
      <div class="card-body" style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; font-size:13px">
        <div>
          <div style="margin-bottom:8px">
            <strong>🏬 Toko/Restoran (Ambil di):</strong>
            <p style="font-weight:700"><?= sanitize($o['resto_name']) ?></p>
            <p style="color:var(--text-muted)"><?= sanitize($o['resto_addr']) ?></p>
          </div>
          <div>
            <strong>📍 Pelanggan (Antar ke):</strong>
            <p style="font-weight:700"><?= sanitize($o['buyer_name']) ?></p>
            <p style="color:var(--text-muted)"><?= sanitize($o['buyer_addr']) ?></p>
          </div>
        </div>
        
        <div style="display:flex; flex-direction:column; justify-content:space-between; align-items:flex-end; border-left:1px solid var(--border); padding-left:20px">
          <div style="text-align:right">
            <div style="color:var(--text-muted)">Total Tagihan:</div>
            <strong style="font-size:16px; color:var(--primary)"><?= formatRupiah($o['total_amount']) ?></strong>
          </div>
          <form action="<?= BASE_URL ?>/controllers/OrderController.php?action=dispatch" method="POST">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <button type="submit" class="btn btn-primary btn-sm">Ambil Order Ini ➔</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
