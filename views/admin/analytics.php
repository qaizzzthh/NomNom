<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Aggregates
$sq = $db->query("SELECT SUM(total_amount) as total, COUNT(*) as c, AVG(total_amount) as avg_val FROM orders WHERE status = 'delivered'");
$sales_data = $sq->fetch(PDO::FETCH_ASSOC);
$total_sales = $sales_data['total'] ?? 0;
$total_orders = $sales_data['c'] ?? 0;
$avg_order = $sales_data['avg_val'] ?? 0;

// Sales by restaurant
$srq = $db->query("SELECT r.name as resto_name, u.name as seller_name, SUM(o.total_amount) as total, COUNT(o.id) as orders_count
                                FROM restaurants r
                                JOIN users u ON r.seller_id = u.id
                                LEFT JOIN orders o ON o.restaurant_id = r.id AND o.status = 'delivered'
                                GROUP BY r.id, r.name, u.name
                                ORDER BY total DESC");
$restaurant_sales = $srq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Analitik Sistem';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">📈 Analitik Penjualan</h2>
    <p class="page-subtitle">Analisis metrik bisnis utama dan kinerja merchant partner NomNom</p>
  </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-wallet" style="color:var(--primary)"></i></div>
    <div>
      <div class="stat-value"><?= formatRupiah($total_sales) ?></div>
      <div class="stat-label">Total Omset Penjualan</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-shopping-bag" style="color:var(--success)"></i></div>
    <div>
      <div class="stat-value"><?= $total_orders ?></div>
      <div class="stat-label">Jumlah Order Sukses</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa fa-chart-line" style="color:var(--info)"></i></div>
    <div>
      <div class="stat-value"><?= formatRupiah($avg_order) ?></div>
      <div class="stat-label">Rata-rata Nilai Order</div>
    </div>
  </div>
</div>

<!-- SALES BY RESTAURANT -->
<div class="card" style="margin-top:28px">
  <div class="card-header">
    <h3>🏪 Performa Penjualan Partner Restoran</h3>
  </div>
  <div class="card-body" style="padding:0">
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Nama Restoran</th>
          <th>Nama Penjual</th>
          <th>Jumlah Order Sukses</th>
          <th>Total Penjualan Bersih</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($restaurant_sales as $rs): ?>
        <tr>
          <td><strong><?= sanitize($rs['resto_name']) ?></strong></td>
          <td><?= sanitize($rs['seller_name']) ?></td>
          <td><?= $rs['orders_count'] ?> order</td>
          <td><strong style="color:var(--success)"><?= formatRupiah($rs['total'] ?? 0) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
