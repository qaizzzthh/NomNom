<?php
require_once __DIR__ . '/../../config/database.php';
$title = 'Akses Ditolak';
$role  = 'public';
ob_start();
?>
<div class="empty-state" style="padding: 100px 20px">
  <div class="empty-icon" style="color: var(--danger)">🚫</div>
  <h3>Akses Ditolak</h3>
  <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Halaman ini memerlukan hak akses khusus.</p>
  <div style="margin-top: 20px">
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Kembali ke Beranda</a>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
