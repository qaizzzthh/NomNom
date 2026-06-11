<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Fetch all orders
$orders = $db->query("SELECT o.*, r.name as resto_name, u.name as buyer_name, d.name as driver_name 
                      FROM orders o 
                      JOIN restaurants r ON o.restaurant_id = r.id 
                      JOIN users u ON o.buyer_id = u.id 
                      LEFT JOIN users d ON o.driver_id = d.id 
                      ORDER BY o.id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);

$title = 'Semua Pesanan';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🧾 Semua Pesanan Sistem</h2>
    <p class="page-subtitle">Daftar monitoring seluruh pesanan pembeli yang masuk ke sistem NomNom</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($orders)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">🧾</div>
      <h3>Belum ada pesanan</h3>
      <p>Pesanan sistem akan dicatat di sini.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Kode Pesanan</th>
          <th>Restoran</th>
          <th>Pembeli</th>
          <th>Driver</th>
          <th>Total Bayar</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
          <td><strong>#<?= $o['order_code'] ?></strong></td>
          <td><?= sanitize($o['resto_name']) ?></td>
          <td><?= sanitize($o['buyer_name']) ?></td>
          <td><?= $o['driver_name'] ? sanitize($o['driver_name']) : '<span style="color:var(--text-light)">Belum ada</span>' ?></td>
          <td><strong><?= formatRupiah($o['total_amount']) ?></strong></td>
          <td>
            <span class="status-badge status-<?= $o['status'] ?>"><?= statusLabel($o['status']) ?></span>
          </td>
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
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
