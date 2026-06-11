<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Aggregations
$users_count = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$restaurants_count = $db->query("SELECT COUNT(*) as c FROM restaurants WHERE status = 'active'")->fetch_assoc()['c'] ?? 0;
$pending_users = $db->query("SELECT COUNT(*) as c FROM users WHERE is_verified = 0 AND role IN ('seller', 'driver')")->fetch_assoc()['c'] ?? 0;
$pending_payments = $db->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending'")->fetch_assoc()['c'] ?? 0;

$title = 'Admin Dashboard';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">⚙️ Admin Control Panel</h2>
    <p class="page-subtitle">Pusat kendali dan administrasi seluruh fitur NomNom Food Delivery</p>
  </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa fa-users" style="color:var(--primary)"></i></div>
    <div>
      <div class="stat-value"><?= $users_count ?></div>
      <div class="stat-label">Total Pengguna</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa fa-store" style="color:var(--success)"></i></div>
    <div>
      <div class="stat-value"><?= $restaurants_count ?></div>
      <div class="stat-label">Restoran Aktif</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa fa-user-check" style="color:var(--info)"></i></div>
    <div>
      <div class="stat-value"><?= $pending_users ?></div>
      <div class="stat-label">Verifikasi Pengguna Pending</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa fa-credit-card" style="color:#a855f7"></i></div>
    <div>
      <div class="stat-value"><?= $pending_payments ?></div>
      <div class="stat-label">Verifikasi Bayar Pending</div>
    </div>
  </div>
</div>

<!-- ACTIONS GRID -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:28px">
  
  <div class="card">
    <div class="card-header">
      <h3>🚀 Pintasan Verifikasi</h3>
    </div>
    <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
      <p style="font-size:13px; color:var(--text-muted)">Gunakan tombol di bawah ini untuk mengakses fitur verifikasi dengan cepat:</p>
      <a href="<?= BASE_URL ?>/views/admin/users.php" class="btn btn-primary" style="justify-content:center">Verifikasi Penjual & Driver (<?= $pending_users ?>)</a>
      <a href="<?= BASE_URL ?>/views/admin/payments.php" class="btn btn-outline" style="justify-content:center">Verifikasi Pembayaran Bank (<?= $pending_payments ?>)</a>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>📋 Pengaturan Sistem</h3>
    </div>
    <div class="card-body" style="display:flex; flex-direction:column; gap:12px">
      <a href="<?= BASE_URL ?>/views/admin/categories.php" class="btn btn-dark" style="justify-content:center">Kelola Kategori Menu</a>
      <a href="<?= BASE_URL ?>/views/admin/vouchers.php" class="btn btn-outline" style="justify-content:center; border-color:var(--bg-dark); color:var(--bg-dark)">Kelola Voucher Promo</a>
    </div>
  </div>

</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
