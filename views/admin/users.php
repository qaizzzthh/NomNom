<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Handle verify
if (isset($_GET['verify_id'])) {
    $verify_id = (int)$_GET['verify_id'];
    $db->query("UPDATE users SET is_verified = 1 WHERE id = $verify_id");
    
    // Auto notification
    $user_row = $db->query("SELECT * FROM users WHERE id = $verify_id")->fetch_assoc();
    if ($user_row) {
        $msg = "Halo {$user_row['name']}! Akun Anda telah diverifikasi oleh admin. Sekarang Anda dapat menggunakan seluruh layanan kami secara penuh.";
        $db->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($verify_id, 'Akun Diverifikasi! ✅', '$msg', 'system')");
    }
    flash('success', 'User berhasil diverifikasi.');
    redirect(BASE_URL . '/views/admin/users.php');
}

// Handle toggle active
if (isset($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    $u = $db->query("SELECT is_active FROM users WHERE id = $toggle_id")->fetch_assoc();
    if ($u) {
        $new_state = $u['is_active'] ? 0 : 1;
        $db->query("UPDATE users SET is_active = $new_state WHERE id = $toggle_id");
        flash('success', 'Status keaktifan user berhasil diperbarui.');
    }
    redirect(BASE_URL . '/views/admin/users.php');
}

// Fetch users
$users = $db->query("SELECT * FROM users ORDER BY role ASC, id DESC")->fetch_all(MYSQLI_ASSOC);

$title = 'Kelola User';
$role  = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">👥 Kelola Pengguna</h2>
    <p class="page-subtitle">Verifikasi pendaftaran driver/seller baru dan kelola status aktif/suspend akun</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>No. HP</th>
          <th>Role</th>
          <th>Verifikasi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:10px">
              <div style="width:32px; height:32px; border-radius:50%; background:var(--primary-bg); color:var(--primary); font-size:13px; font-weight:800; display:flex; align-items:center; justify-content:center; overflow:hidden">
                <?php if ($u['avatar']): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= $u['avatar'] ?>" style="width:100%; height:100%; object-fit:cover">
                <?php else: ?>
                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                <?php endif; ?>
              </div>
              <strong><?= sanitize($u['name']) ?></strong>
            </div>
          </td>
          <td><?= sanitize($u['email']) ?></td>
          <td><?= sanitize($u['phone'] ?? '—') ?></td>
          <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
          <td>
            <span class="status-badge <?= $u['is_verified'] ? 'status-verified' : 'status-pending' ?>">
              <?= $u['is_verified'] ? 'Terverifikasi' : 'Pending' ?>
            </span>
          </td>
          <td>
            <span class="status-badge <?= $u['is_active'] ? 'status-active' : 'status-suspended' ?>">
              <?= $u['is_active'] ? 'Aktif' : 'Suspend' ?>
            </span>
          </td>
          <td>
            <div style="display:flex; gap:6px">
              <?php if (!$u['is_verified'] && in_array($u['role'], ['seller', 'driver'])): ?>
              <a href="?verify_id=<?= $u['id'] ?>" class="btn btn-success btn-sm">Verifikasi</a>
              <?php endif; ?>
              <a href="?toggle_id=<?= $u['id'] ?>" class="btn <?= $u['is_active'] ? 'btn-danger' : 'btn-primary' ?> btn-sm">
                <?= $u['is_active'] ? 'Suspend' : 'Aktifkan' ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
