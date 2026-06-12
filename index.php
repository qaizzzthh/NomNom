<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

// Ambil semua kategori
$cq = $db->query("SELECT * FROM categories WHERE is_active = TRUE ORDER BY name");
$categories = $cq->fetchAll(PDO::FETCH_ASSOC);

// Filter kategori
$cat_filter = (int)($_GET['cat'] ?? 0);

// Restoran aktif
$resto_query = "SELECT r.*, u.name as seller_name, COUNT(DISTINCT p.id) as menu_count,
    (SELECT COALESCE(AVG(rev.rating), 0) FROM review rev JOIN products prod ON rev.product_id = prod.id WHERE prod.restaurant_id = r.id) as rating_avg
    FROM restaurants r
    JOIN users u ON r.seller_id = u.id
    LEFT JOIN products p ON p.restaurant_id = r.id AND p.is_available = TRUE
    WHERE r.status = 'active'
    GROUP BY r.id, u.name
    ORDER BY rating_avg DESC, r.created_at DESC
    LIMIT 12";
$rq = $db->query($resto_query);
$restaurants = $rq->fetchAll(PDO::FETCH_ASSOC);

// Produk featured
$featured_q = $cat_filter
    ? "SELECT p.*, r.name as resto_name, c.name as cat_name FROM products p JOIN restaurants r ON p.restaurant_id = r.id JOIN categories c ON p.category_id = c.id WHERE p.is_featured = TRUE AND p.is_available = TRUE AND r.status = 'active' AND p.category_id = $cat_filter ORDER BY p.id DESC LIMIT 12"
    : "SELECT p.*, r.name as resto_name, c.name as cat_name FROM products p JOIN restaurants r ON p.restaurant_id = r.id JOIN categories c ON p.category_id = c.id WHERE p.is_featured = TRUE AND p.is_available = TRUE AND r.status = 'active' ORDER BY p.id DESC LIMIT 12";
$fq = $db->query($featured_q);
$featured = $fq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Beranda';
$role  = currentUser()['role'] ?? 'public';
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">

<!-- HERO -->
<div class="hero" style="margin: -28px -28px 0">
  <div class="hero-content">
    <div class="hero-eyebrow">🍜 Pesan Sekarang, Makan Sekarang</div>
    <h1>Makanan <em>Lezat</em>,<br>Sampai di Pintu Anda</h1>
    <p>Ratusan restoran dan warung terbaik siap mengantarkan makanan favorit Anda dalam hitungan menit.</p>
    <div class="hero-actions">
      <a href="#restaurants" class="btn btn-primary btn-lg">🍽️ Lihat Restoran</a>
      <?php if (!isLoggedIn()): ?>
      <a href="<?= BASE_URL ?>/views/public/register.php" class="btn btn-outline btn-lg" style="border-color:white;color:white">Daftar Gratis</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div style="padding: 0 0 40px">

<!-- KATEGORI -->
<div class="section" style="padding-bottom: 0">
  <div class="section-header">
    <div>
      <div class="section-title">Kategori Menu</div>
      <div class="section-subtitle">Temukan makanan sesuai seleramu</div>
    </div>
  </div>
  <div class="category-chips">
    <a href="<?= BASE_URL ?>/index.php" class="category-chip <?= !$cat_filter ? 'active' : '' ?>">
      <span class="emoji">🍽️</span> Semua
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= BASE_URL ?>/index.php?cat=<?= $cat['id'] ?>" class="category-chip <?= $cat_filter == $cat['id'] ? 'active' : '' ?>">
      <span class="emoji"><?= $cat['icon'] ?? '🍴' ?></span> <?= sanitize($cat['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- MENU UNGGULAN -->
<?php if ($featured): ?>
<div class="section">
  <div class="section-header">
    <div>
      <div class="section-title">⭐ Menu Unggulan</div>
      <div class="section-subtitle">Pilihan menu paling populer</div>
    </div>
  </div>
  <div class="products-grid">
    <?php foreach ($featured as $p): ?>
    <div class="product-card-wrap product-card" data-category="<?= $p['category_id'] ?>">
      <a href="<?= BASE_URL ?>/views/public/restaurant.php?id=<?= $p['restaurant_id'] ?>" style="display:block">
        <?php if ($p['image']): ?>
        <img src="<?= BASE_URL ?>/uploads/<?= $p['image'] ?>" class="product-card-img" alt="<?= sanitize($p['name']) ?>">
        <?php else: ?>
        <div class="product-card-img-placeholder">🍴</div>
        <?php endif; ?>
      </a>
      <div class="product-card-body">
        <div class="product-card-name"><?= sanitize($p['name']) ?></div>
        <div class="product-card-desc"><?= sanitize(substr($p['description'] ?? '—', 0, 60)) ?>...</div>
        <small style="color:var(--text-muted)">📍 <?= sanitize($p['resto_name']) ?></small>
      </div>
      <div class="product-card-footer">
        <span class="product-price"><?= formatRupiah($p['price']) ?></span>
        <?php if (isLoggedIn() && currentUser()['role'] === 'buyer'): ?>
        <button class="btn-add-cart" data-id="<?= $p['id'] ?>" title="Tambah ke keranjang">+</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- RESTORAN -->
<div class="section" id="restaurants">
  <div class="section-header">
    <div>
      <div class="section-title">🏪 Restoran Terdekat</div>
      <div class="section-subtitle"><?= count($restaurants) ?> restoran tersedia</div>
    </div>
    <a href="<?= BASE_URL ?>/views/public/restaurants.php" class="btn btn-outline btn-sm">Lihat Semua</a>
  </div>
  <?php if (empty($restaurants)): ?>
  <div class="empty-state">
    <div class="empty-icon">🍽️</div>
    <h3>Belum ada restoran</h3>
    <p>Belum ada restoran yang aktif saat ini.</p>
  </div>
  <?php else: ?>
  <div class="restaurants-grid">
    <?php foreach ($restaurants as $r): 
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
</div>

</div><!-- /wrapper -->

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layouts/main.php';
