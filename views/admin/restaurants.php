<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('admin');

$db = getDB();

// Handle status updates
if (isset($_GET['approve_id'])) {
    $approve_id = (int)$_GET['approve_id'];
    $upd = $db->prepare("UPDATE restaurants SET status = 'active' WHERE id = ?");
    $upd->execute([$approve_id]);

    $sr = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
    $sr->execute([$approve_id]);
    $resto = $sr->fetch(PDO::FETCH_ASSOC);
    if ($resto) {
        $sn = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Restoran Disetujui! 🏪', ?, 'system')");
        $sn->execute([$resto['seller_id'], "Restoran Anda {$resto['name']} telah disetujui oleh admin dan sekarang aktif!"]);
    }
    flash('success', 'Restoran berhasil disetujui.');
    redirect(BASE_URL . '/views/admin/restaurants.php');
}

if (isset($_GET['suspend_id'])) {
    $suspend_id = (int)$_GET['suspend_id'];
    $upd = $db->prepare("UPDATE restaurants SET status = 'suspended' WHERE id = ?");
    $upd->execute([$suspend_id]);
    flash('success', 'Restoran berhasil ditangguhkan (suspend).');
    redirect(BASE_URL . '/views/admin/restaurants.php');
}

// Fetch all restaurants
$rq = $db->query("SELECT r.*, u.name as seller_name FROM restaurants r JOIN users u ON r.seller_id = u.id ORDER BY r.id DESC");
$restaurants = $rq->fetchAll(PDO::FETCH_ASSOC);

$title   = 'Kelola Restoran';
$role    = 'admin';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🏪 Kelola Restoran partner</h2>
    <p class="page-subtitle">Setujui pendaftaran mitra restoran baru dan monitor operasional merchant</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($restaurants)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">🏪</div>
      <h3>Belum ada restoran terdaftar</h3>
      <p>Mitra restoran baru akan muncul di sini.</p>
    </div>
    <?php else: ?>
    <table style="width:100%; border-collapse:collapse">
      <thead>
        <tr>
          <th>Logo</th>
          <th>Nama Restoran</th>
          <th>Pemilik</th>
          <th>Alamat</th>
          <th>Jam Buka</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($restaurants as $r): ?>
        <tr>
          <td>
            <div style="width:50px; height:50px; border-radius:10px; overflow:hidden; background:var(--bg); border:1px solid var(--border); display:flex; align-items:center; justify-content:center">
              <?php if ($r['logo']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= $r['logo'] ?>" style="width:100%; height:100%; object-fit:cover">
              <?php else: ?>
              🏪
              <?php endif; ?>
            </div>
          </td>
          <td>
            <strong><?= sanitize($r['name']) ?></strong>
            <p style="font-size:11px; color:var(--text-muted); line-height:1.2; margin-top:2px"><?= sanitize($r['description']) ?></p>
          </td>
          <td><?= sanitize($r['seller_name']) ?></td>
          <td><?= sanitize($r['address']) ?></td>
          <td><?= date('H:i', strtotime($r['open_time'])) ?>–<?= date('H:i', strtotime($r['close_time'])) ?></td>
          <td>
            <span class="status-badge status-<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span>
          </td>
          <td>
            <div style="display:flex; gap:6px">
              <?php if ($r['status'] !== 'active'): ?>
              <a href="?approve_id=<?= $r['id'] ?>" class="btn btn-success btn-sm">Setujui</a>
              <?php endif; ?>
              <?php if ($r['status'] !== 'suspended'): ?>
              <a href="?suspend_id=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menangguhkan restoran ini?')">Suspend</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
