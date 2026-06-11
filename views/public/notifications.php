<?php
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDB();
$user = currentUser();

$notifs = $db->query("SELECT * FROM notifications WHERE user_id = {$user['id']} ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

$title = 'Notifikasi Saya';
$role  = $user['role'];
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">🔔 Notifikasi Saya</h2>
    <p class="page-subtitle">Daftar semua pemberitahuan sistem dan pesanan Anda</p>
  </div>
  <?php if (!empty($notifs)): ?>
  <div>
    <a href="<?= BASE_URL ?>/controllers/NotificationController.php?action=read_all" class="btn btn-outline btn-sm">Tandai Semua Dibaca</a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($notifs)): ?>
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon">🔔</div>
      <h3>Tidak ada notifikasi</h3>
      <p>Pemberitahuan seputar pesanan Anda akan muncul di sini.</p>
    </div>
    <?php else: ?>
    <div style="display:flex; flex-direction:column">
      <?php foreach ($notifs as $n): ?>
      <div class="notif-row" style="display:flex; align-items:center; gap:16px; padding:18px 24px; border-bottom:1px solid var(--border); transition:background 0.15s; background: <?= $n['is_read'] ? 'white' : '#fff9f5' ?>">
        <div class="notif-icon type-<?= $n['type'] ?>" style="font-size:24px; flex-shrink:0">
          <?= $n['type'] === 'order' ? '📦' : ($n['type'] === 'payment' ? '💳' : ($n['type'] === 'promo' ? '🎟️' : '🔔')) ?>
        </div>
        <div style="flex:1">
          <div style="font-size:14px; font-weight:<?= $n['is_read'] ? '600' : '700' ?>; color:var(--text)"><?= sanitize($n['title']) ?></div>
          <p style="font-size:13px; color:var(--text-muted); margin-top:2px"><?= sanitize($n['message']) ?></p>
          <div style="font-size:11px; color:var(--text-light); margin-top:4px"><?= timeAgo($n['created_at']) ?></div>
        </div>
        <div style="flex-shrink:0">
          <?php if (!$n['is_read']): ?>
          <a href="<?= BASE_URL ?>/controllers/NotificationController.php?action=read&id=<?= $n['id'] ?>" class="btn btn-outline btn-sm" style="border-radius:6px; padding:4px 10px; font-size:11px">Baca</a>
          <?php endif; ?>
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
