<?php
require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$restaurant_id = (int)($_GET['id'] ?? 0);

// Get restaurant info
$rsq = $db->prepare("SELECT r.*, u.name as seller_name,
    (SELECT COALESCE(AVG(rev.rating), 0) FROM review rev JOIN products prod ON rev.product_id = prod.id WHERE prod.restaurant_id = r.id) as rating_avg,
    (SELECT COUNT(*) FROM review rev JOIN products prod ON rev.product_id = prod.id WHERE prod.restaurant_id = r.id) as review_count
    FROM restaurants r
    JOIN users u ON r.seller_id = u.id
    WHERE r.id = ? AND r.status = 'active'");
$rsq->execute([$restaurant_id]);
$resto = $rsq->fetch(PDO::FETCH_ASSOC);

if (!$resto) {
    flash('error', 'Restoran tidak ditemukan atau tidak aktif.');
    redirect(BASE_URL . '/index.php');
}

// Get categories present in this restaurant's products
$cq = $db->prepare("SELECT DISTINCT c.* FROM categories c JOIN products p ON p.category_id = c.id WHERE p.restaurant_id = ? AND p.is_available = TRUE");
$cq->execute([$restaurant_id]);
$categories = $cq->fetchAll(PDO::FETCH_ASSOC);

$pq = $db->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.restaurant_id = ? AND p.is_available = TRUE ORDER BY p.is_featured DESC, p.name ASC");
$pq->execute([$restaurant_id]);
$products = $pq->fetchAll(PDO::FETCH_ASSOC);

// Check if open
$now_time = date('H:i:s');
$is_open = ($now_time >= $resto['open_time'] && $now_time <= $resto['close_time']);

$title = $resto['name'];
$role  = currentUser()['role'] ?? 'public';
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">

<!-- RESTAURANT PROFILE HERO -->
<div class="resto-hero" style="margin: -28px -28px 24px; position:relative; background: var(--bg-dark); color:white; overflow:hidden">
  <?php if ($resto['banner']): ?>
  <div style="background: url('<?= BASE_URL ?>/uploads/<?= $resto['banner'] ?>') center/cover no-repeat; position:absolute; inset:0; opacity:0.3; filter: blur(5px)"></div>
  <?php endif; ?>
  <div style="position:relative; padding: 60px 40px; display:flex; align-items:center; gap:24px; flex-wrap:wrap">
    <div class="restaurant-logo" style="width:96px; height:96px; border-radius:20px; font-size:48px; background:white; display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow-lg)">
      <?php if ($resto['logo']): ?>
      <img src="<?= BASE_URL ?>/uploads/<?= $resto['logo'] ?>" style="width:100%; height:100%; border-radius:20px; object-fit:cover">
      <?php else: ?>
      🍜
      <?php endif; ?>
    </div>
    <div style="flex:1; min-width:280px">
      <h1 style="font-family:var(--font-display); font-size:36px; margin-bottom:8px; text-shadow:0 2px 4px rgba(0,0,0,0.5)"><?= sanitize($resto['name']) ?></h1>
      <p style="color:rgba(255,255,255,0.8); margin-bottom:12px; font-size:14px"><?= sanitize($resto['description']) ?></p>
      <div style="display:flex; flex-wrap:wrap; gap:16px; font-size:13px; color:rgba(255,255,255,0.7)">
        <span>📍 <?= sanitize($resto['address']) ?></span>
        <span>⭐ <?= number_format($resto['rating_avg'], 1) ?> (<?= $resto['review_count'] ?> ulasan)</span>
        <span>🕐 Jam Operasional: <?= date('H:i', strtotime($resto['open_time'])) ?> - <?= date('H:i', strtotime($resto['close_time'])) ?></span>
      </div>
    </div>
    <div style="flex-shrink:0">
      <span class="status-badge <?= $is_open ? 'status-active' : 'status-suspended' ?>" style="font-size:14px; padding:6px 16px">
        <?= $is_open ? '🟢 Buka' : '🔴 Tutup' ?>
      </span>
    </div>
  </div>
</div>

<div class="section" style="padding-top:0">
  <!-- CATEGORY FILTERS -->
  <div class="category-chips" style="margin-bottom:30px">
    <button class="category-chip active" data-category="all">🍽️ Semua Menu</button>
    <?php foreach ($categories as $cat): ?>
    <button class="category-chip" data-category="<?= $cat['id'] ?>">
      <span class="emoji"><?= $cat['icon'] ?? '🍴' ?></span> <?= sanitize($cat['name']) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- MENU LISTING -->
  <h3 style="margin-bottom:20px; font-weight:700">Daftar Menu Makanan</h3>
  
  <?php if (empty($products)): ?>
  <div class="empty-state">
    <div class="empty-icon">🍽️</div>
    <h3>Menu belum tersedia</h3>
    <p>Restoran ini belum menambahkan menu makanan.</p>
  </div>
  <?php else: ?>
  <div class="products-grid">
    <?php foreach ($products as $p): ?>
    <div class="product-card-wrap" data-category="<?= $p['category_id'] ?>">
      <div class="product-card <?= $p['stock'] <= 0 ? 'product-stock-out' : '' ?>">
        <?php if ($p['image']): ?>
        <img src="<?= BASE_URL ?>/uploads/<?= $p['image'] ?>" class="product-card-img" alt="<?= sanitize($p['name']) ?>">
        <?php else: ?>
        <div class="product-card-img-placeholder">🍴</div>
        <?php endif; ?>
        <div class="product-card-body">
          <?php if ($p['is_featured']): ?>
          <span style="font-size:10px; background:#fff3cd; color:#856404; font-weight:700; padding:2px 8px; border-radius:10px; text-transform:uppercase; margin-bottom:6px; display:inline-block">⭐ Rekomendasi</span>
          <?php endif; ?>
          <div class="product-card-name"><?= sanitize($p['name']) ?></div>
          <div class="product-card-desc"><?= sanitize($p['description']) ?></div>
          <div style="font-size:12px; color:var(--text-muted); margin-bottom:4px">
            Stok: <strong><?= $p['stock'] ?> porsi</strong>
          </div>
        </div>
        <div class="product-card-footer">
          <span class="product-price"><?= formatRupiah($p['price']) ?></span>
          <?php if ($p['stock'] <= 0): ?>
          <span style="font-size:12px; color:var(--danger); font-weight:700">Habis</span>
          <?php elseif (!$is_open): ?>
          <span style="font-size:11px; color:var(--text-muted); font-weight:600">🔴 Tutup</span>
          <?php elseif (isLoggedIn() && $role === 'buyer'): ?>
          <button class="btn-add-cart" data-id="<?= $p['id'] ?>" title="Tambah ke Keranjang">+</button>
          <?php elseif (!isLoggedIn()): ?>
          <a href="<?= BASE_URL ?>/views/public/login.php" class="btn btn-primary btn-sm" style="font-size:11px; padding:5px 12px">Login</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
