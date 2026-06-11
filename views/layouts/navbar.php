<?php
$user = currentUser();
$role = $user['role'] ?? 'public';
?>
<nav class="navbar">
  <div class="navbar-brand">
    <a href="<?= BASE_URL ?>/index.php">
      <span class="brand-logo">🍜</span>
      <span class="brand-name">NomNom</span>
    </a>
  </div>

  <div class="navbar-search">
    <form action="<?= BASE_URL ?>/views/public/search.php" method="GET">
      <div class="search-box">
        <i class="fa fa-search"></i>
        <input type="text" name="q" placeholder="Cari restoran atau menu..." value="<?= sanitize($_GET['q'] ?? '') ?>">
      </div>
    </form>
  </div>

  <div class="navbar-actions">
    <?php if (isLoggedIn()): ?>
      <?php if ($role === 'buyer'): ?>
      <a href="<?= BASE_URL ?>/views/buyer/cart.php" class="btn-icon" title="Keranjang">
        <i class="fa fa-shopping-cart"></i>
        <?php
        $db = getDB();
        $ccq = $db->prepare("SELECT SUM(qty) as total FROM cart WHERE user_id = ?");
        $ccq->execute([$user['id']]);
        $cart_count = $ccq->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        ?>
        <?php if ($cart_count > 0): ?><span class="badge"><?= $cart_count ?></span><?php endif; ?>
      </a>
      <?php endif; ?>

      <!-- Notifikasi -->
      <div class="notif-wrapper">
        <button class="btn-icon" id="notifBtn" title="Notifikasi">
          <i class="fa fa-bell"></i>
          <?php
          $ucq = $db->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
          $ucq->execute([$user['id']]);
          $unread = $ucq->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
          ?>
          <?php if ($unread > 0): ?><span class="badge"><?= $unread ?></span><?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-header">
            <span>Notifikasi</span>
            <a href="<?= BASE_URL ?>/controllers/NotificationController.php?action=read_all">Tandai semua dibaca</a>
          </div>
          <div class="notif-list">
            <?php
            $nq = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $nq->execute([$user['id']]);
            while ($n = $nq->fetch(PDO::FETCH_ASSOC)):
            ?>
            <a href="<?= BASE_URL ?>/controllers/NotificationController.php?action=read&id=<?= $n['id'] ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
              <div class="notif-icon type-<?= $n['type'] ?>">
                <?= $n['type'] === 'order' ? '📦' : ($n['type'] === 'payment' ? '💳' : ($n['type'] === 'promo' ? '🎟️' : '🔔')) ?>
              </div>
              <div>
                <div class="notif-title"><?= sanitize($n['title']) ?></div>
                <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
              </div>
            </a>
            <?php endwhile; ?>
          </div>
        </div>
      </div>

      <!-- User menu -->
      <div class="user-menu-wrapper">
        <button class="user-avatar-btn" id="userMenuBtn">
          <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= $user['avatar'] ?>" alt="avatar">
          <?php else: ?>
          <div class="avatar-initials"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
          <?php endif; ?>
          <span><?= sanitize(explode(' ', $user['name'])[0]) ?></span>
          <i class="fa fa-chevron-down"></i>
        </button>
        <div class="user-dropdown" id="userDropdown">
          <div class="user-info">
            <strong><?= sanitize($user['name']) ?></strong>
            <span class="role-badge role-<?= $role ?>"><?= ucfirst($role) ?></span>
          </div>
          <hr>
          <?php if ($role === 'buyer'): ?>
          <a href="<?= BASE_URL ?>/views/buyer/profile.php"><i class="fa fa-user"></i> Profil Saya</a>
          <a href="<?= BASE_URL ?>/views/buyer/orders.php"><i class="fa fa-box"></i> Pesanan Saya</a>
          <a href="<?= BASE_URL ?>/views/buyer/addresses.php"><i class="fa fa-map-marker"></i> Alamat Saya</a>
          <?php elseif ($role === 'seller'): ?>
          <a href="<?= BASE_URL ?>/views/seller/dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a>
          <a href="<?= BASE_URL ?>/views/seller/products.php"><i class="fa fa-utensils"></i> Kelola Menu</a>
          <?php elseif ($role === 'admin'): ?>
          <a href="<?= BASE_URL ?>/views/admin/dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard Admin</a>
          <?php elseif ($role === 'driver'): ?>
          <a href="<?= BASE_URL ?>/views/driver/dashboard.php"><i class="fa fa-motorcycle"></i> Dashboard Driver</a>
          <?php endif; ?>
          <hr>
          <a href="<?= BASE_URL ?>/controllers/AuthController.php?action=logout" class="logout-link"><i class="fa fa-sign-out-alt"></i> Keluar</a>
        </div>
      </div>

    <?php else: ?>
      <a href="<?= BASE_URL ?>/views/public/login.php" class="btn btn-outline">Masuk</a>
      <a href="<?= BASE_URL ?>/views/public/register.php" class="btn btn-primary">Daftar</a>
    <?php endif; ?>

    <button class="hamburger" id="hamburger"><i class="fa fa-bars"></i></button>
  </div>
</nav>
