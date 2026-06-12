<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Handle Form POST Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? '🍴');
        
        $stmt = $db->prepare("INSERT INTO categories (name, description, icon, is_active) VALUES (?, ?, ?, TRUE)");
        if ($stmt->execute([$name, $description, $icon])) {
            flash('success', 'Kategori baru berhasil ditambahkan!');
        } else {
            flash('error', 'Gagal menambahkan kategori.');
        }
        redirect(BASE_URL . '/views/admin/categories.php');
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? '🍴');
        $is_active = isset($_POST['is_active']) ? true : false;
        
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, icon = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $icon, $is_active, $id])) {
            flash('success', 'Kategori berhasil diperbarui!');
        } else {
            flash('error', 'Gagal memperbarui kategori.');
        }
        redirect(BASE_URL . '/views/admin/categories.php');
    }
}

// Fetch all categories
$catq = $db->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $catq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Kelola Kategori';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🏷️ Kelola Kategori</h2>
    <p class="page-subtitle">Atur kategori klasifikasi menu hidangan untuk mempermudah pencarian pembeli</p>
  </div>
  <div>
    <button class="btn btn-primary" data-modal="addCatModal">+ Tambah Kategori Baru</button>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($categories)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">🏷️</div>
      <h3>Kategori kosong</h3>
      <p>Mulai tambahkan kategori makanan/minuman.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Icon</th>
          <th>Nama Kategori</th>
          <th>Deskripsi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
        <tr>
          <td style="font-size:24px; text-align:center; width:60px"><?= $c['icon'] ?></td>
          <td><strong><?= sanitize($c['name']) ?></strong></td>
          <td><?= sanitize($c['description'] ?? '—') ?></td>
          <td>
            <span class="status-badge <?= $c['is_active'] ? 'status-active' : 'status-suspended' ?>">
              <?= $c['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </span>
          </td>
          <td>
            <button class="btn btn-outline btn-sm" data-modal="editCatModal-<?= $c['id'] ?>">Edit</button>
          </td>
        </tr>

        <!-- EDIT CATEGORY MODAL -->
        <div class="modal-overlay" id="editCatModal-<?= $c['id'] ?>">
          <div class="modal">
            <div class="modal-header">
              <h3>Edit Kategori: <?= sanitize($c['name']) ?></h3>
              <button class="modal-close" data-modal="editCatModal-<?= $c['id'] ?>">&times;</button>
            </div>
            <form action="" method="POST">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              
              <div class="modal-body" style="display:grid; gap:16px">
                <div class="form-group" style="margin:0">
                  <label class="form-label">Nama Kategori <span class="required">*</span></label>
                  <input type="text" name="name" class="form-control" value="<?= sanitize($c['name']) ?>" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 3fr; gap:12px">
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Icon Emoji <span class="required">*</span></label>
                    <input type="text" name="icon" class="form-control" value="<?= sanitize($c['icon']) ?>" required>
                  </div>
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Deskripsi</label>
                    <input type="text" name="description" class="form-control" value="<?= sanitize($c['description']) ?>">
                  </div>
                </div>
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
                  <input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?> style="accent-color:var(--primary)"> Aktifkan Kategori
                </label>
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

<!-- ADD CATEGORY MODAL -->
<div class="modal-overlay" id="addCatModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Tambah Kategori Baru</h3>
      <button class="modal-close" data-modal="addCatModal">&times;</button>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body" style="display:grid; gap:16px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Nama Kategori <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="Contoh: Makanan Penutup" required>
        </div>
        <div style="display:grid; grid-template-columns:1fr 3fr; gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Icon Emoji <span class="required">*</span></label>
            <input type="text" name="icon" class="form-control" placeholder="🍰" required>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Deskripsi</label>
            <input type="text" name="description" class="form-control" placeholder="Keterangan kategori">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline modal-close">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Kategori</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
