<?php
require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$resto_query = "SELECT r.*, u.name as seller_name, COUNT(DISTINCT p.id) as menu_count,
    (SELECT COALESCE(AVG(rev.rating), 0) FROM review rev JOIN products prod ON rev.product_id = prod.id WHERE prod.restaurant_id = r.id) as rating_avg
    FROM restaurants r
    JOIN users u ON r.seller_id = u.id
    LEFT JOIN products p ON p.restaurant_id = r.id AND p.is_available = 1
    WHERE r.status = 'active'
    GROUP BY r.id
    ORDER BY rating_avg DESC, r.created_at DESC";
$rq = $db->query($resto_query);
$restaurants = $rq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Semua Restoran';
$role  = currentUser()['role'] ?? 'public';
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🏪 Semua Restoran Partner</h2>
    <p class="page-subtitle">Pesan kuliner terbaik langsung dari penjual terpercaya kami</p>
  </div>
</div>

<?php if (empty($restaurants)): ?>
<div class="empty-state">
  <div class="empty-icon">🍽️</div>
  <h3>Belum ada restoran partner</h3>
  <p>Mohon kembali beberapa saat lagi saat para partner kami telah bergabung.</p>
</div>
<?php else: ?>
<div class="restaurants-grid">
  <?php foreach ($restaurants as $r): 
      // check if open
      $now_time = date('H:i:s');
      $is_open = ($now_time >= $r['open_time'] && $now_time <= $r['close_time']);
  ?>
  <a href="<?= BASE_URL ?>/views/public/restaurant.php?id=<?= $r['id'] ?>" style="color:inherit">
    <div class="restaurant-card">
      <?php if ($r['banner']): ?>
      <img src="<?= BASE_URL ?>/uploads/<?= $r['banner'] ?>" class="restaurant-banner" alt="<?= sanitize($r['name']) ?>">
      <?php else: ?>
      <div class="restaurant-banner-placeholder">🍜</div>
      <?php endif; ?>
      <div class="restaurant-card-body">
        <div class="restaurant-header">
          <div class="restaurant-logo">
            <?php if ($r['logo']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= $r['logo'] ?>" style="width:48px;height:48px;border-radius:10px;object-fit:cover">
            <?php else: ?>
            🍴
            <?php endif; ?>
          </div>
          <div>
            <div class="restaurant-name"><?= sanitize($r['name']) ?></div>
            <div class="restaurant-meta">
              <span>⭐ <?= number_format($r['rating_avg'], 1) ?></span>
              <span>📋 <?= $r['menu_count'] ?> menu</span>
            </div>
          </div>
        </div>
        <div class="restaurant-meta" style="margin-bottom:8px">
          <span>🕐 <?= date('H:i', strtotime($r['open_time'])) ?>–<?= date('H:i', strtotime($r['close_time'])) ?></span>
          <span>💰 Min. <?= formatRupiah($r['min_order']) ?></span>
        </div>
        <span class="restaurant-tag <?= $is_open ? 'open-badge' : 'closed-badge' ?>">
          <?= $is_open ? '🟢 Buka' : '🔴 Tutup' ?>
        </span>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
