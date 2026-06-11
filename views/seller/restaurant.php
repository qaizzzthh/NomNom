<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('seller');

$db = getDB();
$user = currentUser();

// Fetch restaurant
$rq = $db->prepare("SELECT * FROM restaurants WHERE seller_id = ?");
$rq->execute([$user['id']]);
$resto = $rq->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $latitude = (float)($_POST['latitude'] ?? 0.0) ?: -6.17539240;
    $longitude = (float)($_POST['longitude'] ?? 0.0) ?: 106.82715280;
    $open_time = $_POST['open_time'] ?? '08:00';
    $close_time = $_POST['close_time'] ?? '22:00';
    $min_order = (float)($_POST['min_order'] ?? 0.0);

    // File Uploads
    $logo = $resto['logo'] ?? null;
    $banner = $resto['banner'] ?? null;

    if (!empty($_FILES['logo']['name'])) {
        $uploaded_logo = uploadFile($_FILES['logo'], 'restaurants');
        if ($uploaded_logo) $logo = $uploaded_logo;
    }
    if (!empty($_FILES['banner']['name'])) {
        $uploaded_banner = uploadFile($_FILES['banner'], 'restaurants');
        if ($uploaded_banner) $banner = $uploaded_banner;
    }

    if ($resto) {
        // Update
        $stmt = $db->prepare("UPDATE restaurants SET name = ?, description = ?, address = ?, latitude = ?, longitude = ?, open_time = ?, close_time = ?, min_order = ?, logo = ?, banner = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $address, $latitude, $longitude, $open_time, $close_time, $min_order, $logo, $banner, $resto['id']])) {
            flash('success', 'Detail restoran berhasil diperbarui!');
        } else {
            flash('error', 'Gagal memperbarui detail restoran.');
        }
    } else {
        // Insert
        $status = 'pending'; // admin verification required
        $stmt = $db->prepare("INSERT INTO restaurants (seller_id, name, description, address, latitude, longitude, logo, banner, open_time, close_time, min_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user['id'], $name, $description, $address, $latitude, $longitude, $logo, $banner, $open_time, $close_time, $min_order, $status])) {
            flash('success', 'Restoran Anda berhasil didaftarkan! Menunggu verifikasi admin.');
        } else {
            flash('error', 'Gagal mendaftarkan restoran.');
        }
    }
    redirect(BASE_URL . '/views/seller/restaurant.php');
}

$title = 'Restoran Saya';
$role  = 'seller';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🏪 Restoran Saya</h2>
    <p class="page-subtitle">Kelola identitas, logo, jam operasional, dan lokasi fisik restoran Anda</p>
  </div>
</div>

<div class="card" style="max-width:800px; margin:0 auto">
  <div class="card-header">
    <h3>Pengaturan Profil Restoran</h3>
    <?php if ($resto): ?>
    <span class="status-badge status-<?= $resto['status'] ?>">Status: <?= strtoupper($resto['status']) ?></span>
    <?php endif; ?>
  </div>
  <form action="" method="POST" enctype="multipart/form-data">
    <div class="card-body" style="display:grid; gap:20px">
      
      <!-- LOGO & BANNER PREVIEWS -->
      <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px">
        <div>
          <label class="form-label">Logo Restoran</label>
          <div style="width:100px; height:100px; border-radius:12px; background:var(--bg); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:10px">
            <?php if ($resto && $resto['logo']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= $resto['logo'] ?>" style="width:100%; height:100%; object-fit:cover">
            <?php else: ?>
            🍴
            <?php endif; ?>
          </div>
          <input type="file" name="logo" accept="image/*" style="font-size:11px">
        </div>
        <div>
          <label class="form-label">Banner Restoran</label>
          <div style="height:100px; border-radius:12px; background:var(--bg); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:10px">
            <?php if ($resto && $resto['banner']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= $resto['banner'] ?>" style="width:100%; height:100%; object-fit:cover">
            <?php else: ?>
            🖼️ Banner
            <?php endif; ?>
          </div>
          <input type="file" name="banner" accept="image/*" style="font-size:11px">
        </div>
      </div>

      <div class="form-group" style="margin:0">
        <label class="form-label">Nama Restoran <span class="required">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= sanitize($resto['name'] ?? '') ?>" required placeholder="Contoh: RM Padang Sederhana">
      </div>

      <div class="form-group" style="margin:0">
        <label class="form-label">Deskripsi Singkat</label>
        <textarea name="description" class="form-control" placeholder="Tuliskan keistimewaan restoran Anda..." style="min-height:80px"><?= sanitize($resto['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group" style="margin:0">
        <label class="form-label">Alamat Lengkap <span class="required">*</span></label>
        <textarea name="address" class="form-control" placeholder="Jl. Sudirman No. 12, Jakarta" style="min-height:80px" required><?= sanitize($resto['address'] ?? '') ?></textarea>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Latitude</label>
          <input type="number" step="any" name="latitude" class="form-control" value="<?= $resto['latitude'] ?? '' ?>" placeholder="-6.17539">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Longitude</label>
          <input type="number" step="any" name="longitude" class="form-control" value="<?= $resto['longitude'] ?? '' ?>" placeholder="106.8271">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Jam Buka <span class="required">*</span></label>
          <input type="time" name="open_time" class="form-control" value="<?= $resto ? date('H:i', strtotime($resto['open_time'])) : '08:00' ?>" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Jam Tutup <span class="required">*</span></label>
          <input type="time" name="close_time" class="form-control" value="<?= $resto ? date('H:i', strtotime($resto['close_time'])) : '22:00' ?>" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Min. Pemesanan (Rp) <span class="required">*</span></label>
          <input type="number" name="min_order" class="form-control" value="<?= $resto['min_order'] ?? 0 ?>" required>
        </div>
      </div>

    </div>
    <div class="card-footer" style="display:flex; justify-content:flex-end">
      <button type="submit" class="btn btn-primary"><?= $resto ? 'Perbarui Profil Restoran' : 'Daftarkan Restoran' ?></button>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
