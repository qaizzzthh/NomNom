<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('seller');

$db = getDB();
$user = currentUser();

// Fetch restaurant
$rq = $db->prepare("SELECT * FROM restaurants WHERE seller_id = ?");
$rq->execute([$user['id']]);
$resto = $rq->fetch(PDO::FETCH_ASSOC);

if (!$resto) {
    flash('error', 'Silakan daftarkan restoran terlebih dahulu.');
    redirect(BASE_URL . '/views/seller/restaurant.php');
}

$resto_id = $resto['id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? true : false;
        $is_available = isset($_POST['is_available']) ? true : false;

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadFile($_FILES['image'], 'products');
        }

        $stmt = $db->prepare("INSERT INTO products (seller_id, restaurant_id, category_id, name, description, price, stock, image, is_available, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user['id'], $resto_id, $category_id, $name, $description, $price, $stock, $image, $is_available ? 'true' : 'false', $is_featured ? 'true' : 'false'])) {
            flash('success', 'Menu baru berhasil ditambahkan!');
        } else {
            flash('error', 'Gagal menambahkan menu.');
        }
        redirect(BASE_URL . '/views/seller/products.php');
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0.0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? true : false;
        $is_available = isset($_POST['is_available']) ? true : false;

        // Fetch current image
        $pq = $db->prepare("SELECT image FROM products WHERE id = ? AND restaurant_id = ?");
        $pq->execute([$id, $resto_id]);
        $prod = $pq->fetch(PDO::FETCH_ASSOC);
        $image = $prod['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $uploaded = uploadFile($_FILES['image'], 'products');
            if ($uploaded) $image = $uploaded;
        }

        $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image = ?, is_available = ?, is_featured = ? WHERE id = ? AND restaurant_id = ?");
        if ($stmt->execute([$name, $description, $price, $stock, $category_id, $image, $is_available ? 'true' : 'false', $is_featured ? 'true' : 'false', $id, $resto_id])) {
            flash('success', 'Detail menu berhasil diperbarui!');
        } else {
            flash('error', 'Gagal memperbarui menu.');
        }
        redirect(BASE_URL . '/views/seller/products.php');
    }
}

// Handle GET delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $dq = $db->prepare("DELETE FROM products WHERE id = ? AND restaurant_id = ?");
    $dq->execute([$delete_id, $resto_id]);
    flash('success', 'Menu berhasil dihapus.');
    redirect(BASE_URL . '/views/seller/products.php');
}

// Fetch all categories
$cq = $db->query("SELECT * FROM categories WHERE is_active = TRUE ORDER BY name");
$categories = $cq->fetchAll(PDO::FETCH_ASSOC);

$pq2 = $db->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.restaurant_id = ? ORDER BY p.id DESC");
$pq2->execute([$resto_id]);
$products = $pq2->fetchAll(PDO::FETCH_ASSOC);

$title = 'Kelola Menu';
$role  = 'seller';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🍴 Kelola Menu</h2>
    <p class="page-subtitle">Tambah, edit, dan atur ketersediaan menu makanan di restoran Anda</p>
  </div>
  <div>
    <button class="btn btn-primary" data-modal="addProductModal">+ Tambah Menu Baru</button>
  </div>
</div>

<!-- PRODUCTS TABLE -->
<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($products)): ?>
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon">🍴</div>
      <h3>Menu masih kosong</h3>
      <p>Mulai tambahkan makanan lezat ke daftar menu Anda.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Gambar</th>
          <th>Nama Menu</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Status</th>
          <th>Unggulan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <div style="width:60px; height:60px; border-radius:8px; overflow:hidden; background:var(--bg); border:1px solid var(--border); display:flex; align-items:center; justify-content:center">
              <?php if ($p['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= $p['image'] ?>" style="width:100%; height:100%; object-fit:cover">
              <?php else: ?>
              🍴
              <?php endif; ?>
            </div>
          </td>
          <td>
            <strong><?= sanitize($p['name']) ?></strong>
            <p style="font-size:11px; color:var(--text-muted); line-height:1.2; margin-top:2px"><?= sanitize(substr($p['description'], 0, 50)) ?>...</p>
          </td>
          <td><?= sanitize($p['category_name']) ?></td>
          <td><strong><?= formatRupiah($p['price']) ?></strong></td>
          <td><?= $p['stock'] ?> porsi</td>
          <td>
            <span class="status-badge <?= $p['is_available'] ? 'status-active' : 'status-suspended' ?>">
              <?= $p['is_available'] ? 'Tersedia' : 'Habis' ?>
            </span>
          </td>
          <td><?= $p['is_featured'] ? '🌟 Ya' : 'Tidak' ?></td>
          <td>
            <div style="display:flex; gap:6px">
              <button class="btn btn-outline btn-sm" data-modal="editProductModal-<?= $p['id'] ?>">Edit</button>
              <a href="<?= BASE_URL ?>/views/seller/products.php?delete_id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?')">Hapus</a>
            </div>
          </td>
        </tr>

        <!-- EDIT PRODUCT MODAL -->
        <div class="modal-overlay" id="editProductModal-<?= $p['id'] ?>">
          <div class="modal">
            <div class="modal-header">
              <h3>Edit Menu: <?= sanitize($p['name']) ?></h3>
              <button class="modal-close" data-modal="editProductModal-<?= $p['id'] ?>">&times;</button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              
              <div class="modal-body" style="display:grid; gap:16px">
                <div class="form-group" style="margin:0">
                  <label class="form-label">Nama Menu <span class="required">*</span></label>
                  <input type="text" name="name" class="form-control" value="<?= sanitize($p['name']) ?>" required>
                </div>
                
                <div class="form-group" style="margin:0">
                  <label class="form-label">Kategori <span class="required">*</span></label>
                  <select name="category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $p['category_id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Harga (Rp) <span class="required">*</span></label>
                    <input type="number" name="price" class="form-control" value="<?= (int)$p['price'] ?>" required>
                  </div>
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Stok <span class="required">*</span></label>
                    <input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" required>
                  </div>
                </div>

                <div class="form-group" style="margin:0">
                  <label class="form-label">Deskripsi Menu</label>
                  <textarea name="description" class="form-control" style="min-height:80px"><?= sanitize($p['description']) ?></textarea>
                </div>

                <div class="form-group" style="margin:0">
                  <label class="form-label">Gambar Menu (Maks 5MB)</label>
                  <input type="file" name="image" accept="image/*" class="form-control">
                </div>

                <div style="display:flex; gap:20px">
                  <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
                    <input type="checkbox" name="is_available" value="1" <?= $p['is_available'] ? 'checked' : '' ?> style="accent-color:var(--primary)"> Aktifkan Menu
                  </label>
                  <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
                    <input type="checkbox" name="is_featured" value="1" <?= $p['is_featured'] ? 'checked' : '' ?> style="accent-color:var(--primary)"> Menu Unggulan (Featured)
                  </label>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline modal-close">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
              </div>
            </form>
          </div>
        </div>

        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ADD PRODUCT MODAL -->
<div class="modal-overlay" id="addProductModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Tambah Menu Baru</h3>
      <button class="modal-close" data-modal="addProductModal">&times;</button>
    </div>
    <form action="" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">
      
      <div class="modal-body" style="display:grid; gap:16px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Nama Menu <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="Contoh: Nasi Goreng Gila" required>
        </div>
        
        <div class="form-group" style="margin:0">
          <label class="form-label">Kategori <span class="required">*</span></label>
          <select name="category_id" class="form-control" required>
            <option value="" disabled selected>Pilih Kategori</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Harga (Rp) <span class="required">*</span></label>
            <input type="number" name="price" class="form-control" placeholder="Contoh: 18000" required>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Stok <span class="required">*</span></label>
            <input type="number" name="stock" class="form-control" placeholder="Porsi tersedia" required>
          </div>
        </div>

        <div class="form-group" style="margin:0">
          <label class="form-label">Deskripsi Menu</label>
          <textarea name="description" class="form-control" placeholder="Tuliskan isi menu, rasa, atau bahan..." style="min-height:80px"></textarea>
        </div>

        <div class="form-group" style="margin:0">
          <label class="form-label">Gambar Menu (Maks 5MB)</label>
          <input type="file" name="image" accept="image/*" class="form-control">
        </div>

        <div style="display:flex; gap:20px">
          <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
            <input type="checkbox" name="is_available" value="1" checked style="accent-color:var(--primary)"> Aktifkan Menu
          </label>
          <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
            <input type="checkbox" name="is_featured" value="1" style="accent-color:var(--primary)"> Menu Unggulan (Featured)
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline modal-close">Batal</button>
        <button type="submit" class="btn btn-primary">Tambah Menu</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
