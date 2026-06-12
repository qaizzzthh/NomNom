<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('buyer');

$db = getDB();
$user = currentUser();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $label = sanitize($_POST['label'] ?? '');
    $recipient_name = sanitize($_POST['recipient_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $lat = (float)($_POST['latitude'] ?? 0.0) ?: -6.17539240; // Jakarta default
    $lon = (float)($_POST['longitude'] ?? 0.0) ?: 106.82715280;
    $is_default = isset($_POST['is_default']) ? true : false;

    if ($is_default) {
        $ud = $db->prepare("UPDATE buyer_addresses SET is_default = FALSE WHERE user_id = ?");
        $ud->execute([$user['id']]);
    }

    $stmt = $db->prepare("INSERT INTO buyer_addresses (user_id, label, recipient_name, phone, address, latitude, longitude, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user['id'], $label, $recipient_name, $phone, $address, $lat, $lon, $is_default ? 'true' : 'false'])) {
        flash('success', 'Alamat berhasil ditambahkan!');
    } else {
        flash('error', 'Gagal menambahkan alamat.');
    }
    redirect(BASE_URL . '/views/buyer/addresses.php');
}

// Handle set default
if (isset($_GET['default_id'])) {
    $default_id = (int)$_GET['default_id'];
    $ud1 = $db->prepare("UPDATE buyer_addresses SET is_default = FALSE WHERE user_id = ?");
    $ud1->execute([$user['id']]);
    $ud2 = $db->prepare("UPDATE buyer_addresses SET is_default = TRUE WHERE id = ? AND user_id = ?");
    $ud2->execute([$default_id, $user['id']]);
    flash('success', 'Alamat utama berhasil diperbarui.');
    redirect(BASE_URL . '/views/buyer/addresses.php');
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $del = $db->prepare("DELETE FROM buyer_addresses WHERE id = ? AND user_id = ?");
    $del->execute([$delete_id, $user['id']]);
    flash('success', 'Alamat berhasil dihapus.');
    redirect(BASE_URL . '/views/buyer/addresses.php');
}

// Fetch all addresses
$aq = $db->prepare("SELECT * FROM buyer_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$aq->execute([$user['id']]);
$addresses = $aq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Kelola Alamat';
$role  = 'buyer';
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">📍 Kelola Alamat Saya</h2>
    <p class="page-subtitle">Simpan alamat pengiriman untuk checkout lebih cepat</p>
  </div>
  <div>
    <button class="btn btn-primary" data-modal="addAddressModal">+ Tambah Alamat Baru</button>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($addresses)): ?>
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon">📍</div>
      <h3>Belum ada alamat tersimpan</h3>
      <p>Tambahkan alamat pengiriman seperti rumah atau kantor Anda sekarang.</p>
    </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column">
        <?php foreach ($addresses as $addr): ?>
        <div style="display:flex; align-items:start; justify-content:space-between; gap:20px; padding:20px 24px; border-bottom:1px solid var(--border)">
          <div style="flex:1">
            <div style="display:flex; align-items:center; gap:10px">
              <span style="font-weight:700; font-size:15px; color:var(--text)"><?= sanitize($addr['label']) ?></span>
              <?php if ($addr['is_default']): ?>
              <span class="status-badge status-verified" style="font-size:10px; padding:2px 8px">Utama</span>
              <?php endif; ?>
            </div>
            <p style="font-size:13px; font-weight:600; color:var(--text); margin-top:6px">
              👨‍💼 <?= sanitize($addr['recipient_name']) ?> (<?= sanitize($addr['phone']) ?>)
            </p>
            <p style="font-size:13px; color:var(--text-muted); margin-top:2px"><?= sanitize($addr['address']) ?></p>
            <small style="color:var(--text-light); font-size:11px; display:block; margin-top:6px">📍 Koordinat: <?= $addr['latitude'] ?>, <?= $addr['longitude'] ?></small>
          </div>
          <div style="display:flex; align-items:center; gap:8px">
            <?php if (!$addr['is_default']): ?>
            <a href="<?= BASE_URL ?>/views/buyer/addresses.php?default_id=<?= $addr['id'] ?>" class="btn btn-outline btn-sm">Set Utama</a>
            <a href="<?= BASE_URL ?>/views/buyer/addresses.php?delete_id=<?= $addr['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus alamat ini?')">Hapus</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ADD ADDRESS MODAL -->
<div class="modal-overlay" id="addAddressModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Tambah Alamat Baru</h3>
      <button class="modal-close" data-modal="addAddressModal">&times;</button>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body" style="display:grid; gap:16px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Label Alamat <span class="required">*</span></label>
          <input type="text" name="label" class="form-control" placeholder="Contoh: Rumah, Kantor, Kosan" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Nama Penerima <span class="required">*</span></label>
          <input type="text" name="recipient_name" class="form-control" placeholder="Nama lengkap penerima" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Nomor HP Penerima <span class="required">*</span></label>
          <input type="tel" name="phone" class="form-control" placeholder="Contoh: 08xxxxxxxxx" required>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">Alamat Lengkap <span class="required">*</span></label>
          <textarea name="address" class="form-control" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan" style="min-height:80px" required></textarea>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Latitude (Opsional)</label>
            <input type="number" step="any" name="latitude" class="form-control" placeholder="-6.17539">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Longitude (Opsional)</label>
            <input type="number" step="any" name="longitude" class="form-control" placeholder="106.8271">
          </div>
        </div>
        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
          <input type="checkbox" name="is_default" style="accent-color:var(--primary)"> Jadikan Alamat Utama
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline modal-close">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Alamat</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
