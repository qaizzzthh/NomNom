<?php
$user = currentUser();
$role = $user['role'] ?? 'buyer';
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-role-label"><?= ucfirst($role) ?> Panel</div>
  </div>

  <nav class="sidebar-nav">
    <?php if ($role === 'seller'): ?>
    <a href="<?= BASE_URL ?>/views/seller/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-chart-bar"></i> <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>/views/seller/restaurant.php" class="<?= $current === 'restaurant.php' ? 'active' : '' ?>">
      <i class="fa fa-store"></i> <span>Restoran Saya</span>
    </a>
    <a href="<?= BASE_URL ?>/views/seller/products.php" class="<?= $current === 'products.php' ? 'active' : '' ?>">
      <i class="fa fa-utensils"></i> <span>Kelola Menu</span>
    </a>
    <a href="<?= BASE_URL ?>/views/seller/orders.php" class="<?= $current === 'orders.php' ? 'active' : '' ?>">
      <i class="fa fa-clipboard-list"></i> <span>Pesanan Masuk</span>
      <?php
      $db = getDB();
      $pq = $db->prepare("SELECT COUNT(*) as c FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE r.seller_id = ? AND o.status = 'pending'");
      $pq->execute([$user['id']]);
      $pending = $pq->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
      if ($pending > 0): ?><span class="sidebar-badge"><?= $pending ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/views/seller/income.php" class="<?= $current === 'income.php' ? 'active' : '' ?>">
      <i class="fa fa-wallet"></i> <span>Laporan Pendapatan</span>
    </a>
    <a href="<?= BASE_URL ?>/views/seller/reviews.php" class="<?= $current === 'reviews.php' ? 'active' : '' ?>">
      <i class="fa fa-star"></i> <span>Review Menu</span>
    </a>

    <?php elseif ($role === 'admin'): ?>
    <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-tachometer-alt"></i> <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/users.php" class="<?= $current === 'users.php' ? 'active' : '' ?>">
      <i class="fa fa-users"></i> <span>Kelola User</span>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/restaurants.php" class="<?= $current === 'restaurants.php' ? 'active' : '' ?>">
      <i class="fa fa-store"></i> <span>Kelola Restoran</span>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/categories.php" class="<?= $current === 'categories.php' ? 'active' : '' ?>">
      <i class="fa fa-tags"></i> <span>Kategori</span>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/orders.php" class="<?= $current === 'orders.php' ? 'active' : '' ?>">
      <i class="fa fa-receipt"></i> <span>Semua Pesanan</span>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/payments.php" class="<?= $current === 'payments.php' ? 'active' : '' ?>">
      <i class="fa fa-credit-card"></i> <span>Verifikasi Bayar</span>
      <?php
      $db = getDB();
      $ppq = $db->prepare("SELECT COUNT(*) as c FROM payments WHERE status = 'pending'");
      $ppq->execute([]);
      $pend_pay = $ppq->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
      if ($pend_pay > 0): ?><span class="sidebar-badge"><?= $pend_pay ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/vouchers.php" class="<?= $current === 'vouchers.php' ? 'active' : '' ?>">
      <i class="fa fa-ticket-alt"></i> <span>Voucher</span>
    </a>
    <a href="<?= BASE_URL ?>/views/admin/analytics.php" class="<?= $current === 'analytics.php' ? 'active' : '' ?>">
      <i class="fa fa-chart-pie"></i> <span>Analytics</span>
    </a>

    <?php elseif ($role === 'driver'): ?>
    <a href="<?= BASE_URL ?>/views/driver/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa fa-home"></i> <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>/views/driver/available_orders.php" class="<?= $current === 'available_orders.php' ? 'active' : '' ?>">
      <i class="fa fa-motorcycle"></i> <span>Order Tersedia</span>
    </a>
    <a href="<?= BASE_URL ?>/views/driver/my_deliveries.php" class="<?= $current === 'my_deliveries.php' ? 'active' : '' ?>">
      <i class="fa fa-map-marked-alt"></i> <span>Pengiriman Saya</span>
    </a>
    <a href="<?= BASE_URL ?>/views/driver/history.php" class="<?= $current === 'history.php' ? 'active' : '' ?>">
      <i class="fa fa-history"></i> <span>Riwayat</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/controllers/AuthController.php?action=logout" class="sidebar-logout">
      <i class="fa fa-sign-out-alt"></i> <span>Keluar</span>
    </a>
  </div>
</aside>
