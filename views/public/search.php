<?php
require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$q = sanitize($_GET['q'] ?? '');

$restaurants = [];
$products = [];

if ($q !== '') {
    // Search restaurants
    $resto_query = "SELECT r.*,
        (SELECT COALESCE(AVG(rev.rating), 0) FROM review rev JOIN products prod ON rev.product_id = prod.id WHERE prod.restaurant_id = r.id) as rating_avg,
        (SELECT COUNT(*) FROM products WHERE restaurant_id = r.id AND is_available = 1) as menu_count
        FROM restaurants r
        WHERE r.status = 'active' AND r.name LIKE ?
        LIMIT 10";
    $stmt = $db->prepare($resto_query);
    $like_q = "%$q%";
    $stmt->bind_param("s", $like_q);
    $stmt->execute();
    $restaurants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Search products
    $prod_query = "SELECT p.*, r.name as resto_name, r.id as resto_id
        FROM products p
        JOIN restaurants r ON p.restaurant_id = r.id
        WHERE p.is_available = 1 AND r.status = 'active' AND p.name LIKE ?
        LIMIT 20";
    $stmt = $db->prepare($prod_query);
    $stmt->bind_param("s", $like_q);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$title = 'Cari "' . $q . '"';
$role  = currentUser()['role'] ?? 'public';
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🔍 Hasil Pencarian: "<?= sanitize($q) ?>"</h2>
    <p class="page-subtitle">Ditemukan <?= count($restaurants) ?> restoran dan <?= count($products) ?> menu makanan</p>
  </div>
</div>

<div style="display:grid; gap:40px">
  <!-- RESTORAN -->
  <div>
    <h3 style="margin-bottom:16px; font-weight:700">🏪 Restoran Partner</h3>
    <?php if (empty($restaurants)): ?>
    <p style="color:var(--text-muted)">Tidak ada restoran yang cocok.</p>
    <?php else: ?>
    <div class="restaurants-grid">
      <?php foreach ($restaurants as $r): ?>
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
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- MENU MAKANAN -->
  <div>
    <h3 style="margin-bottom:16px; font-weight:700">🍜 Menu Makanan</h3>
    <?php if (empty($products)): ?>
    <p style="color:var(--text-muted)">Tidak ada menu makanan yang cocok.</p>
    <?php else: ?>
    <div class="products-grid">
      <?php foreach ($products as $p): ?>
      <div class="product-card-wrap">
        <div class="product-card">
          <a href="<?= BASE_URL ?>/views/public/restaurant.php?id=<?= $p['resto_id'] ?>" style="display:block">
            <?php if ($p['image']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= $p['image'] ?>" class="product-card-img" alt="<?= sanitize($p['name']) ?>">
            <?php else: ?>
            <div class="product-card-img-placeholder">🍴</div>
            <?php endif; ?>
          </a>
          <div class="product-card-body">
            <div class="product-card-name"><?= sanitize($p['name']) ?></div>
            <div class="product-card-desc"><?= sanitize($p['description']) ?></div>
            <small style="color:var(--text-muted)">📍 <?= sanitize($p['resto_name']) ?></small>
          </div>
          <div class="product-card-footer">
            <span class="product-price"><?= formatRupiah($p['price']) ?></span>
            <?php if (isLoggedIn() && $role === 'buyer' && $p['stock'] > 0): ?>
            <button class="btn-add-cart" data-id="<?= $p['id'] ?>" title="Tambah ke keranjang">+</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
