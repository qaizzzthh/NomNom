<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('seller');

$db = getDB();
$user = currentUser();

// Fetch restaurant
$resto = $db->query("SELECT * FROM restaurants WHERE seller_id = {$user['id']}")->fetch_assoc();

if (!$resto) {
    flash('error', 'Silakan daftarkan restoran terlebih dahulu.');
    redirect(BASE_URL . '/views/seller/restaurant.php');
}

$resto_id = $resto['id'];

// Aggregate earnings
$income_data = $db->query("SELECT SUM(total_amount) as total, COUNT(*) as c FROM orders WHERE restaurant_id = $resto_id AND status = 'delivered'")->fetch_assoc();
$total_income = $income_data['total'] ?? 0;
$completed_orders = $income_data['c'] ?? 0;

// Query income history (delivered orders)
$history = $db->query("SELECT o.*, u.name as buyer_name 
                       FROM orders o 
                       JOIN users u ON o.buyer_id = u.id 
                       WHERE o.restaurant_id = $resto_id AND o.status = 'delivered' 
                       ORDER BY o.id DESC")->fetch_all(MYSQLI_ASSOC);

$title = 'Laporan Pendapatan';
$role  = 'seller';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">💰 Laporan Pendapatan</h2>
    <p class="page-subtitle">Pantau performa penjualan dan pendapatan bersih restoran Anda</p>
  </div>
</div>

<!-- STATS SUMMARY -->
<div class="stats-grid" style="grid-template-columns:1fr 1fr; max-width:600px; margin-bottom:28px">
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-wallet" style="color:var(--primary)"></i></div>
    <div>
      <div class="stat-value"><?= formatRupiah($total_income) ?></div>
      <div class="stat-label">Total Saldo Masuk</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-check-circle" style="color:var(--success)"></i></div>
    <div>
      <div class="stat-value"><?= $completed_orders ?></div>
      <div class="stat-label">Pesanan Sukses</div>
    </div>
  </div>
</div>

<!-- INCOME DETAILS -->
<div class="card">
  <div class="card-header">
    <h3>Riwayat Transaksi Masuk</h3>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($history)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">💰</div>
      <h3>Belum ada transaksi selesai</h3>
      <p>Pendapatan akan tercatat otomatis saat pesanan selesai diantar.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Kode Pesanan</th>
          <th>Pelanggan</th>
          <th>Subtotal</th>
          <th>Ongkir</th>
          <th>Diskon</th>
          <th>Pendapatan Bersih</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $h): ?>
        <tr>
          <td><?= date('d M Y H:i', strtotime($h['created_at'])) ?></td>
          <td><strong>#<?= $h['order_code'] ?></strong></td>
          <td><?= sanitize($h['buyer_name']) ?></td>
          <td><?= formatRupiah($h['subtotal']) ?></td>
          <td><?= formatRupiah($h['delivery_fee']) ?></td>
          <td>-<?= formatRupiah($h['discount']) ?></td>
          <td><strong style="color:var(--success)">+<?= formatRupiah($h['total_amount']) ?></strong></td>
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
