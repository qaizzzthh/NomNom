<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('seller');

$db = getDB();
$user = currentUser();

// Fetch restaurant
$resto = $db->query("SELECT * FROM restaurants WHERE seller_id = {$user['id']}")->fetch_assoc();

if (!$resto) {
    flash('error', 'Silakan daftarkan restoran terlebih dahulu.');
    redirect(BASE_URL . '/views/seller/restaurant.php');
}

$resto_id = $resto['id'];

// Query reviews
$reviews = $db->query("SELECT r.*, u.name as buyer_name, u.avatar as buyer_avatar, p.name as pname, p.image as pimage
                       FROM review r
                       JOIN users u ON r.user_id = u.id
                       JOIN products p ON r.product_id = p.id
                       WHERE p.restaurant_id = $resto_id
                       ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$title = 'Review Menu';
$role  = 'seller';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">⭐ Review Menu</h2>
    <p class="page-subtitle">Baca tanggapan dan masukan pelanggan tentang menu makanan Anda</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($reviews)): ?>
    <div class="empty-state" style="padding:40px 20px">
      <div class="empty-icon">⭐</div>
      <h3>Belum ada review masuk</h3>
      <p>Ulasan rasa makanan dari pelanggan akan tampil di sini.</p>
    </div>
    <?php else: ?>
    <div style="display:flex; flex-direction:column">
      <?php foreach ($reviews as $rev): ?>
      <div style="padding:20px 24px; border-bottom:1px solid var(--border); display:flex; gap:16px; align-items:start">
        <!-- User avatar -->
        <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-bg); color:var(--primary); font-size:16px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden">
          <?php if ($rev['buyer_avatar']): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= $rev['buyer_avatar'] ?>" style="width:100%; height:100%; object-fit:cover">
          <?php else: ?>
          <?= strtoupper(substr($rev['buyer_name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        
        <div style="flex:1">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px">
            <div>
              <strong><?= sanitize($rev['buyer_name']) ?></strong>
              <div style="color:var(--text-light); font-size:11px"><?= timeAgo($rev['created_at']) ?></div>
            </div>
            <div>
              <span style="font-size:14px; font-weight:700; color:#f59e0b">
                <?= str_repeat('⭐', $rev['rating']) ?>
              </span>
            </div>
          </div>
          
          <div style="margin-top:10px; font-size:13px; color:var(--text)">
            Menu: <strong style="color:var(--primary)"><?= sanitize($rev['pname']) ?></strong>
            <p style="margin-top:6px; line-height:1.5; font-style:italic">"<?= sanitize($rev['comment'] ?: 'Tidak ada komentar.') ?>"</p>
          </div>
          
          <?php if ($rev['photo']): ?>
          <div style="margin-top:10px">
            <a href="<?= BASE_URL ?>/uploads/<?= $rev['photo'] ?>" target="_blank">
              <img src="<?= BASE_URL ?>/uploads/<?= $rev['photo'] ?>" style="max-height:120px; border-radius:6px; border:1px solid var(--border)">
            </a>
          </div>
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
