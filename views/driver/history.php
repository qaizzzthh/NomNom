<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('driver');

$db = getDB();
$user = currentUser();

// Fetch completed deliveries
$deliveries = $db->query("SELECT o.*, r.name as resto_name, a.address as buyer_addr, a.recipient_name 
                          FROM orders o 
                          JOIN restaurants r ON o.restaurant_id = r.id 
                          JOIN buyer_addresses a ON o.address_id = a.id
                          WHERE o.driver_id = {$user['id']} AND o.status = 'delivered' 
                          ORDER BY o.id DESC")->fetch_all(MYSQLI_ASSOC);

$title = 'Riwayat Pengiriman';
$role  = 'driver';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title"> Riwayat Pengantaran</h2>
    <p class="page-subtitle">Daftar semua tugas pengiriman makanan yang berhasil Anda selesaikan</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($deliveries)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon"></div>
      <h3>Belum ada riwayat pengiriman</h3>
      <p>Selesaikan pengantaran aktif Anda untuk melihat riwayat di sini.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Kode Pesanan</th>
          <th>Restoran</th>
          <th>Penerima</th>
          <th>Total Belanja</th>
          <th>Pendapatan (Ongkir)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deliveries as $d): ?>
        <tr>
          <td><?= date('d M Y H:i', strtotime($d['created_at'])) ?></td>
          <td><strong>#<?= $d['order_code'] ?></strong></td>
          <td><?= sanitize($d['resto_name']) ?></td>
          <td>
            <strong><?= sanitize($d['recipient_name']) ?></strong>
            <p style="font-size:11px; color:var(--text-muted); line-height:1.2; margin-top:2px"><?= sanitize($d['buyer_addr']) ?></p>
          </td>
          <td><?= formatRupiah($d['total_amount']) ?></td>
          <td><strong style="color:var(--success)">+<?= formatRupiah($d['delivery_fee']) ?></strong></td>
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
