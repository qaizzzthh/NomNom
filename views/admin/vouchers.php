<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Handle Form POST Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $code = strtoupper(trim(sanitize($_POST['code'] ?? '')));
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = (float)($_POST['discount_value'] ?? 0.0);
        $min_order = (float)($_POST['min_order'] ?? 0.0);
        $max_discount = (float)($_POST['max_discount'] ?? 0.0) ?: null;
        $usage_limit = (int)($_POST['usage_limit'] ?? 0) ?: null;
        $expired_at = $_POST['expired_at'] ? date('Y-m-d H:i:s', strtotime($_POST['expired_at'])) : null;
        
        $stmt = $db->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_order, max_discount, usage_limit, expired_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        if ($stmt->execute([$code, $discount_type, $discount_value, $min_order, $max_discount, $usage_limit, $expired_at])) {
            flash('success', 'Voucher baru berhasil dibuat!');
        } else {
            flash('error', 'Gagal membuat voucher.');
        }
        redirect(BASE_URL . '/views/admin/vouchers.php');
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = strtoupper(trim(sanitize($_POST['code'] ?? '')));
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = (float)($_POST['discount_value'] ?? 0.0);
        $min_order = (float)($_POST['min_order'] ?? 0.0);
        $max_discount = (float)($_POST['max_discount'] ?? 0.0) ?: null;
        $usage_limit = (int)($_POST['usage_limit'] ?? 0) ?: null;
        $expired_at = $_POST['expired_at'] ? date('Y-m-d H:i:s', strtotime($_POST['expired_at'])) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE vouchers SET code = ?, discount_type = ?, discount_value = ?, min_order = ?, max_discount = ?, usage_limit = ?, expired_at = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$code, $discount_type, $discount_value, $min_order, $max_discount, $usage_limit, $expired_at, $is_active, $id])) {
            flash('success', 'Voucher berhasil diperbarui!');
        } else {
            flash('error', 'Gagal memperbarui voucher.');
        }
        redirect(BASE_URL . '/views/admin/vouchers.php');
    }
}

// Fetch all vouchers
$vq = $db->query("SELECT * FROM vouchers ORDER BY id DESC");
$vouchers = $vq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Kelola Voucher';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🎟️ Kelola Voucher Promo</h2>
    <p class="page-subtitle">Buat dan atur kode voucher potongan belanja bagi para pelanggan</p>
  </div>
  <div>
    <button class="btn btn-primary" data-modal="addVoucherModal">+ Buat Voucher Baru</button>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($vouchers)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">🎟️</div>
      <h3>Belum ada voucher aktif</h3>
      <p>Mulai tambahkan promo kode diskon belanja.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Tipe Diskon</th>
          <th>Potongan</th>
          <th>Min. Belanja</th>
          <th>Maks. Diskon</th>
          <th>Pemakaian (Limit)</th>
          <th>Exp Date</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vouchers as $v): ?>
        <tr>
          <td><strong><?= sanitize($v['code']) ?></strong></td>
          <td><?= $v['discount_type'] === 'percentage' ? 'Persentase (%)' : 'Flat Tunai' ?></td>
          <td><?= $v['discount_type'] === 'percentage' ? $v['discount_value'] . '%' : formatRupiah($v['discount_value']) ?></td>
          <td><?= formatRupiah($v['min_order']) ?></td>
          <td><?= $v['max_discount'] ? formatRupiah($v['max_discount']) : 'Unlimit' ?></td>
          <td><?= $v['used_count'] ?> / <?= $v['usage_limit'] ?? '∞' ?></td>
          <td><?= $v['expired_at'] ? date('d M Y', strtotime($v['expired_at'])) : 'Selamanya' ?></td>
          <td>
            <span class="status-badge <?= $v['is_active'] ? 'status-active' : 'status-suspended' ?>">
              <?= $v['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </span>
          </td>
          <td>
            <button class="btn btn-outline btn-sm" data-modal="editVoucherModal-<?= $v['id'] ?>">Edit</button>
          </td>
        </tr>

        <!-- EDIT VOUCHER MODAL -->
        <div class="modal-overlay" id="editVoucherModal-<?= $v['id'] ?>">
          <div class="modal">
            <div class="modal-header">
              <h3>Edit Voucher: <?= sanitize($v['code']) ?></h3>
              <button class="modal-close" data-modal="editVoucherModal-<?= $v['id'] ?>">&times;</button>
            </div>
            <form action="" method="POST">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $v['id'] ?>">
              
              <div class="modal-body" style="display:grid; gap:16px">
                <div class="form-group" style="margin:0">
                  <label class="form-label">Kode Voucher <span class="required">*</span></label>
                  <input type="text" name="code" class="form-control" value="<?= sanitize($v['code']) ?>" required style="text-transform:uppercase">
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Tipe Diskon <span class="required">*</span></label>
                    <select name="discount_type" class="form-control" required>
                      <option value="percentage" <?= $v['discount_type'] === 'percentage' ? 'selected' : '' ?>>Persentase (%)</option>
                      <option value="fixed" <?= $v['discount_type'] === 'fixed' ? 'selected' : '' ?>>Nilai Tetap (Rp)</option>
                    </select>
                  </div>
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Nilai Diskon <span class="required">*</span></label>
                    <input type="number" step="any" name="discount_value" class="form-control" value="<?= $v['discount_value'] ?>" required>
                  </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Min Order (Rp) <span class="required">*</span></label>
                    <input type="number" name="min_order" class="form-control" value="<?= $v['min_order'] ?>" required>
                  </div>
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Maks Diskon (Rp)</label>
                    <input type="number" name="max_discount" class="form-control" value="<?= $v['max_discount'] ?>">
                  </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Limit Penggunaan</label>
                    <input type="number" name="usage_limit" class="form-control" value="<?= $v['usage_limit'] ?>">
                  </div>
                  <div class="form-group" style="margin:0">
                    <label class="form-label">Tanggal Kadaluarsa</label>
                    <input type="date" name="expired_at" class="form-control" value="<?= $v['expired_at'] ? date('Y-m-d', strtotime($v['expired_at'])) : '' ?>">
                  </div>
                </div>

                <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer">
                  <input type="checkbox" name="is_active" value="1" <?= $v['is_active'] ? 'checked' : '' ?> style="accent-color:var(--primary)"> Aktifkan Voucher
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

<!-- ADD VOUCHER MODAL -->
<div class="modal-overlay" id="addVoucherModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Buat Voucher Baru</h3>
      <button class="modal-close" data-modal="addVoucherModal">&times;</button>
    </div>
    <form action="" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body" style="display:grid; gap:16px">
        <div class="form-group" style="margin:0">
          <label class="form-label">Kode Voucher <span class="required">*</span></label>
          <input type="text" name="code" class="form-control" placeholder="Contoh: NOMNOMHEMAT" required style="text-transform:uppercase">
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Tipe Diskon <span class="required">*</span></label>
            <select name="discount_type" class="form-control" required>
              <option value="percentage">Persentase (%)</option>
              <option value="fixed">Nilai Tetap (Rp)</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Nilai Diskon <span class="required">*</span></label>
            <input type="number" step="any" name="discount_value" class="form-control" placeholder="Contoh: 10 atau 15000" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Min Order (Rp) <span class="required">*</span></label>
            <input type="number" name="min_order" class="form-control" placeholder="Contoh: 20000" required>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Maks Diskon (Rp)</label>
            <input type="number" name="max_discount" class="form-control" placeholder="Kosongkan jika unlimit">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div class="form-group" style="margin:0">
            <label class="form-label">Limit Penggunaan</label>
            <input type="number" name="usage_limit" class="form-control" placeholder="Kosongkan jika unlimit">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Tanggal Kadaluarsa</label>
            <input type="date" name="expired_at" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline modal-close">Batal</button>
        <button type="submit" class="btn btn-primary">Buat Voucher</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
