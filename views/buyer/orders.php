<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('buyer');

$db = getDB();
$user = currentUser();

$oq = $db->prepare("SELECT o.*, r.name as resto_name, r.logo as resto_logo FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.buyer_id = ? ORDER BY o.created_at DESC");
$oq->execute([$user['id']]);
$orders = $oq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Pesanan Saya';
$role  = 'buyer';
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">📦 Riwayat Pesanan</h2>
    <p class="page-subtitle">Daftar semua pesanan makanan yang pernah Anda buat</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($orders)): ?>
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon">📦</div>
      <h3>Belum ada pesanan dibuat</h3>
      <p>Pesan makanan favorit Anda dari restoran partner terbaik kami.</p>
      <div style="margin-top:20px">
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Pesan Sekarang</a>
      </div>
    </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column">
        <?php foreach ($orders as $o): ?>
        <div style="display:flex; align-items:center; justify-content:space-between; gap:20px; padding:20px 24px; border-bottom:1px solid var(--border); transition:background 0.15s">
          <div style="display:flex; align-items:center; gap:16px; flex:1; min-width:0">
            <div style="width:48px; height:48px; border-radius:10px; background:var(--primary-bg); font-size:24px; display:flex; align-items:center; justify-content:center; flex-shrink:0">
              <?php if ($o['resto_logo']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= $o['resto_logo'] ?>" style="width:100%; height:100%; object-fit:cover; border-radius:10px">
              <?php else: ?>
              🏪
              <?php endif; ?>
            </div>
            <div style="min-width:0">
              <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap">
                <span style="font-weight:700; font-size:15px; color:var(--text)" class="text-truncate"><?= sanitize($o['resto_name']) ?></span>
                <span style="font-size:12px; color:var(--text-light)">#<?= $o['order_code'] ?></span>
              </div>
              <p style="font-size:12px; color:var(--text-muted); margin-top:4px">
                <?= date('d M Y H:i', strtotime($o['created_at'])) ?>
              </p>
              <p style="font-size:13px; font-weight:700; color:var(--primary); margin-top:2px">
                Total: <?= formatRupiah($o['total_amount']) ?>
              </p>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:12px; flex-shrink:0">
            <span class="status-badge status-<?= $o['status'] ?>"><?= statusLabel($o['status']) ?></span>
            <a href="<?= BASE_URL ?>/views/buyer/order_detail.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
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
