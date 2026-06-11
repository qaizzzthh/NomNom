<?php
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDB();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = sanitize($_POST['phone'] ?? '');

    $errors = [];
    if (strlen($name) < 3) $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';

    // Check duplicate email (excluding current user)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[] = 'Email sudah digunakan oleh akun lain.';
    $stmt->close();

    // Handle avatar upload
    $avatar_path = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $upload = uploadFile($_FILES['avatar'], 'avatars');
        if ($upload) {
            $avatar_path = $upload;
        } else {
            $errors[] = 'Gagal upload foto profil. Gunakan format JPG/PNG/WebP maks 5MB.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, avatar = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $avatar_path, $user['id']);
        if ($stmt->execute()) {
            // Update session user details
            $updated = $db->query("SELECT * FROM users WHERE id = {$user['id']}")->fetch_assoc();
            $_SESSION['user'] = $updated;
            $user = $updated;
            flash('success', 'Profil Anda berhasil diperbarui!');
        } else {
            flash('error', 'Gagal memperbarui profil.');
        }
        $stmt->close();
        redirect(BASE_URL . '/views/buyer/profile.php');
    } else {
        $_SESSION['flash']['error'] = implode('<br>', $errors);
    }
}

$title = 'Profil Saya';
$role  = $user['role'];
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">👤 Profil Saya</h2>
    <p class="page-subtitle">Kelola informasi pribadi dan pengaturan akun Anda</p>
  </div>
</div>

<div class="card" style="max-width:600px; margin:0 auto">
  <form action="" method="POST" enctype="multipart/form-data">
    <div class="card-body">
      
      <!-- Avatar Section -->
      <div style="display:flex; flex-direction:column; align-items:center; gap:12px; margin-bottom:28px">
        <div style="width:100px; height:100px; border-radius:50%; overflow:hidden; border:3px solid var(--primary); box-shadow:var(--shadow)">
          <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= $user['avatar'] ?>" style="width:100%; height:100%; object-fit:cover">
          <?php else: ?>
          <div style="width:100%; height:100%; background:var(--primary-bg); color:var(--primary); font-size:36px; font-weight:800; display:flex; align-items:center; justify-content:center">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="upload-area" style="padding:10px; width:100%; max-width:240px; border-radius:var(--radius-sm)">
          <div style="font-size:12px; color:var(--text-muted)">Ganti Foto Profil</div>
          <input type="file" name="avatar" accept="image/*" style="font-size:11px; margin-top:6px; max-width:100%">
        </div>
      </div>

      <!-- Form Inputs -->
      <div class="form-group">
        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-user input-icon"></i>
          <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required minlength="3">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Alamat Email <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Nomor Handphone</label>
        <div class="input-group">
          <i class="fa fa-phone input-icon"></i>
          <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
        </div>
      </div>

    </div>
    <div class="card-footer" style="display:flex; justify-content:flex-end">
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
  </form>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
