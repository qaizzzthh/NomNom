<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('driver');

$db = getDB();
$user = currentUser();

// Aggregate stats
$stats = $db->query("SELECT COUNT(*) as total, SUM(delivery_fee) as earnings 
                     FROM orders 
                     WHERE driver_id = {$user['id']} AND status = 'delivered'")->fetch_assoc();
$total_deliveries = $stats['total'] ?? 0;
$total_earnings = $stats['earnings'] ?? 0;

// Active delivery
$active_order = $db->query("SELECT o.*, r.name as resto_name, a.address as buyer_addr 
                            FROM orders o 
                            JOIN restaurants r ON o.restaurant_id = r.id 
                            JOIN buyer_addresses a ON o.address_id = a.id
                            WHERE o.driver_id = {$user['id']} AND o.status = 'on_delivery' 
                            LIMIT 1")->fetch_assoc();

$title = 'Driver Dashboard';
$role  = 'driver';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🛵 Driver Dashboard</h2>
    <p class="page-subtitle">Pantau status pengantaran dan pendapatan harian Anda</p>
  </div>
</div>

<?php if (!$user['is_verified']): ?>
<div class="alert alert-warning">
  <i class="fa fa-exclamation-triangle"></i> Akun Anda masih <strong>menunggu verifikasi dari admin</strong>. Anda baru bisa mengambil order setelah admin memverifikasi akun driver Anda.
</div>
<?php endif; ?>

<!-- STATS GRID -->
<div class="stats-grid" style="grid-template-columns: 1fr 1fr; max-width:600px; margin-bottom:28px">
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-motorcycle" style="color:var(--primary)"></i></div>
    <div>
      <div class="stat-value"><?= $total_deliveries ?></div>
      <div class="stat-label">Total Pengantaran</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-wallet" style="color:var(--success)"></i></div>
    <div>
      <div class="stat-value"><?= formatRupiah($total_earnings) ?></div>
      <div class="stat-label">Total Pendapatan</div>
    </div>
  </div>
</div>

<!-- ACTIVE DELIVERY -->
<div class="card">
  <div class="card-header">
    <h3>🛵 Pengiriman Aktif</h3>
  </div>
  <div class="card-body">
    <?php if (!$active_order): ?>
    <div class="empty-state" style="padding:30px 20px">
      <div class="empty-icon">🛵</div>
      <h3>Tidak ada pengiriman aktif</h3>
      <p>Ayo cari order makanan yang siap diantar sekarang!</p>
      <?php if ($user['is_verified']): ?>
      <div style="margin-top:16px">
        <a href="<?= BASE_URL ?>/views/driver/available_orders.php" class="btn btn-primary">Cari Order</a>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px">
      <div>
        <strong>Pesanan #<?= $active_order['order_code'] ?></strong>
        <p style="font-size:13px; color:var(--text-muted); margin-top:4px">Dari: <strong><?= sanitize($active_order['resto_name']) ?></strong></p>
        <p style="font-size:13px; color:var(--text-muted)">Ke: <strong><?= sanitize($active_order['buyer_addr']) ?></strong></p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/views/driver/my_deliveries.php" class="btn btn-primary">Detail Pengiriman</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
