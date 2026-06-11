<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('seller');

$db = getDB();
$user = currentUser();

// Get restaurant info
$srq = $db->prepare("SELECT * FROM restaurants WHERE seller_id = ?");
$srq->execute([$user['id']]);
$resto = $srq->fetch(PDO::FETCH_ASSOC);

$resto_id = $resto ? $resto['id'] : 0;

// Stats
$total_income = 0;
$total_orders = 0;
$menu_count = 0;
$rating_avg = 0.0;

if ($resto_id) {
    $ir = $db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE restaurant_id = ? AND status = 'delivered'");
    $ir->execute([$resto_id]); $income_row = $ir->fetch(PDO::FETCH_ASSOC);
    $total_income = $income_row['total'] ?? 0;

    $or = $db->prepare("SELECT COUNT(*) as c FROM orders WHERE restaurant_id = ?");
    $or->execute([$resto_id]); $orders_row = $or->fetch(PDO::FETCH_ASSOC);
    $total_orders = $orders_row['c'] ?? 0;

    $mr = $db->prepare("SELECT COUNT(*) as c FROM products WHERE restaurant_id = ?");
    $mr->execute([$resto_id]); $menu_row = $mr->fetch(PDO::FETCH_ASSOC);
    $menu_count = $menu_row['c'] ?? 0;

    $rr = $db->prepare("SELECT COALESCE(AVG(rev.rating), 0) as avg FROM review rev JOIN products prod ON rev.product_id = prod.id WHERE prod.restaurant_id = ?");
    $rr->execute([$resto_id]); $rating_row = $rr->fetch(PDO::FETCH_ASSOC);
    $rating_avg = $rating_row['avg'] ?? 0;

    $ro = $db->prepare("SELECT o.*, u.name as buyer_name FROM orders o JOIN users u ON o.buyer_id = u.id WHERE o.restaurant_id = ? ORDER BY o.created_at DESC LIMIT 5");
    $ro->execute([$resto_id]);
    $recent_orders = $ro->fetchAll(PDO::FETCH_ASSOC);
} else {
    $recent_orders = [];
}

$title = 'Seller Dashboard';
$role  = 'seller';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">👨‍🍳 Seller Dashboard</h2>
    <p class="page-subtitle">Kelola restoran dan pantau pesanan pelanggan Anda</p>
  </div>
</div>

<?php if (!$resto): ?>
<div class="alert alert-warning">
  <i class="fa fa-exclamation-triangle"></i> Anda belum mendaftarkan restoran! Silakan daftarkan restoran Anda terlebih dahulu di menu <strong>Restoran Saya</strong> agar dapat mulai berjualan.
</div>
<?php else: ?>
  <!-- STATS GRID -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fa fa-wallet" style="color:var(--primary)"></i></div>
      <div>
        <div class="stat-value"><?= formatRupiah($total_income) ?></div>
        <div class="stat-label">Pendapatan Bersih</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fa fa-shopping-bag" style="color:var(--success)"></i></div>
      <div>
        <div class="stat-value"><?= $total_orders ?></div>
        <div class="stat-label">Total Pesanan</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fa fa-utensils" style="color:var(--info)"></i></div>
      <div>
        <div class="stat-value"><?= $menu_count ?></div>
        <div class="stat-label">Jumlah Menu</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fa fa-star" style="color:#a855f7"></i></div>
      <div>
        <div class="stat-value">⭐ <?= number_format($rating_avg, 1) ?></div>
        <div class="stat-label">Rata-rata Rating</div>
      </div>
    </div>
  </div>

  <!-- RECENT ORDERS -->
  <div class="card">
    <div class="card-header">
      <h3>📋 Pesanan Terbaru</h3>
      <a href="<?= BASE_URL ?>/views/seller/orders.php" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($recent_orders)): ?>
      <div class="empty-state" style="padding:40px 20px">
        <div class="empty-icon">📋</div>
        <h3>Belum ada pesanan masuk</h3>
        <p>Pesanan dari pelanggan Anda akan muncul di sini.</p>
      </div>
      <?php else: ?>
      <table style="width:100%; border-collapse:collapse">
        <thead>
          <tr>
            <th>Kode Pesanan</th>
            <th>Pelanggan</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_orders as $o): ?>
          <tr>
            <td><strong>#<?= $o['order_code'] ?></strong></td>
            <td><?= sanitize($o['buyer_name']) ?></td>
            <td><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
            <td><?= formatRupiah($o['total_amount']) ?></td>
            <td><span class="status-badge status-<?= $o['status'] ?>"><?= statusLabel($o['status']) ?></span></td>
            <td>
              <a href="<?= BASE_URL ?>/views/buyer/order_detail.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
