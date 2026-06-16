<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('buyer');

$db = getDB();
$user = currentUser();

$cq = $db->prepare("SELECT c.*, p.name as pname, p.price, p.image, p.stock, r.name as resto_name, r.id as resto_id FROM cart c JOIN products p ON c.product_id = p.id JOIN restaurants r ON p.restaurant_id = r.id WHERE c.user_id = ?");
$cq->execute([$user['id']]);
$cart_items = $cq->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['qty'] * $item['price'];
}

$title = 'Keranjang Belanja';
$role  = 'buyer';
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="page-header">
  <div>
    <h2 class="page-title">🛒 Keranjang Belanja</h2>
    <p class="page-subtitle">Periksa menu pilihanmu sebelum checkout</p>
  </div>
  <?php if (!empty($cart_items)): ?>
  <div>
    <button class="btn btn-outline btn-sm" onclick="clearCart()">Kosongkan Keranjang</button>
  </div>
  <?php endif; ?>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:28px; align-items:start">
  <!-- CART ITEMS -->
  <div class="card">
    <div class="card-body" style="padding: 0 20px">
      <?php if (empty($cart_items)): ?>
      <div class="empty-state" style="padding: 60px 20px">
        <div class="empty-icon">🛒</div>
        <h3>Keranjang belanja kosong</h3>
        <p>Ayo cari makanan lezat dan tambahkan ke keranjang belanjaanmu!</p>
        <div style="margin-top:20px">
          <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Cari Makanan</a>
        </div>
      </div>
      <?php else: ?>
        <div style="font-size:13px; color:var(--text-muted); padding:16px 0; border-bottom:1px solid var(--border)">
          Pemesanan dari: <strong>🏢 <?= sanitize($cart_items[0]['resto_name']) ?></strong>
        </div>
        <?php foreach ($cart_items as $item): ?>
        <div class="cart-item" data-cart="<?= $item['id'] ?>">
          <div class="cart-item-img">
            <?php if ($item['image']): ?>
            <img src="<?= BASE_URL ?>/uploads/<?= $item['image'] ?>" style="width:100%; height:100%; object-fit:cover; border-radius:var(--radius-sm)">
            <?php else: ?>
            🍴
            <?php endif; ?>
          </div>
          <div class="cart-item-info">
            <div class="cart-item-name"><?= sanitize($item['pname']) ?></div>
            <div class="cart-item-price"><?= formatRupiah($item['price']) ?></div>
            <?php if ($item['notes']): ?>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px">📝 Catatan: <?= sanitize($item['notes']) ?></div>
            <?php endif; ?>
          </div>
          <div class="qty-control">
            <button class="qty-btn" data-cart="<?= $item['id'] ?>" data-action="dec">-</button>
            <span class="qty-value" data-cart="<?= $item['id'] ?>"><?= $item['qty'] ?></span>
            <button class="qty-btn" data-cart="<?= $item['id'] ?>" data-action="inc">+</button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- SUMMARY -->
  <?php if (!empty($cart_items)): ?>
  <div class="card">
    <div class="card-header">
      <h3>Ringkasan Belanja</h3>
    </div>
    <div class="card-body">
      <div style="display:flex; justify-content:space-between; margin-bottom:16px">
        <span style="color:var(--text-muted)">Subtotal</span>
        <strong id="cart-total"><?= formatRupiah($subtotal) ?></strong>
      </div>
      <p style="font-size:12px; color:var(--text-muted); margin-bottom:20px">
        * Ongkos kirim dan diskon voucher akan dihitung pada halaman checkout.
      </p>
      <a href="<?= BASE_URL ?>/views/buyer/checkout.php" class="btn btn-primary btn-block btn-lg" style="text-align:center">Lanjut ke Pembayaran ➔</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function clearCart() {
  if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    fetch(`${baseUrl}/controllers/CartController.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=clear'
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      }
    });
  }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
