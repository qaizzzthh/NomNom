<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('buyer');

$db = getDB();
$user = currentUser();

// Get cart items
$cart_items = $db->query("SELECT c.*, p.name as pname, p.price, p.stock, r.name as resto_name, r.id as resto_id, r.latitude as r_lat, r.longitude as r_lon FROM cart c JOIN products p ON c.product_id = p.id JOIN restaurants r ON p.restaurant_id = r.id WHERE c.user_id = {$user['id']}")->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    flash('error', 'Keranjang belanja Anda kosong.');
    redirect(BASE_URL . '/views/buyer/cart.php');
}

// Get addresses
$addresses = $db->query("SELECT * FROM buyer_addresses WHERE user_id = {$user['id']} ORDER BY is_default DESC")->fetch_all(MYSQLI_ASSOC);

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['qty'] * $item['price'];
}

$title = 'Checkout Pembayaran';
$role  = 'buyer';
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="checkout-steps">
  <div class="checkout-step done">
    <span class="step-num">1</span> Keranjang
  </div>
  <div class="step-line done"></div>
  <div class="checkout-step active">
    <span class="step-num">2</span> Pembayaran
  </div>
  <div class="step-line"></div>
  <div class="checkout-step">
    <span class="step-num">3</span> Status Kirim
  </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:28px; align-items:start">
  <!-- DETAILS -->
  <form action="<?= BASE_URL ?>/controllers/OrderController.php?action=place" method="POST" id="checkoutForm">
    <div style="display:grid; gap:24px">
      <!-- ADDRESS -->
      <div class="card">
        <div class="card-header">
          <h3>📍 Alamat Pengiriman</h3>
          <a href="<?= BASE_URL ?>/views/buyer/addresses.php" class="btn btn-outline btn-sm">Kelola Alamat</a>
        </div>
        <div class="card-body">
          <?php if (empty($addresses)): ?>
          <div class="alert alert-error" style="margin:0">
            Anda belum menambahkan alamat pengiriman! Silakan tambahkan alamat terlebih dahulu.
          </div>
          <?php else: ?>
          <div style="display:grid; gap:12px">
            <?php foreach ($addresses as $addr): ?>
            <label style="display:flex; align-items:start; gap:12px; padding:12px; border:1.5px solid var(--border); border-radius:var(--radius-sm); cursor:pointer">
              <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> style="margin-top:4px" required>
              <div>
                <strong>[<?= sanitize($addr['label']) ?>] <?= sanitize($addr['recipient_name']) ?></strong> (<?= sanitize($addr['phone']) ?>)
                <p style="font-size:12px; color:var(--text-muted); margin-top:2px"><?= sanitize($addr['address']) ?></p>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- METHOD -->
      <div class="card">
        <div class="card-header">
          <h3>💳 Metode Pembayaran</h3>
        </div>
        <div class="card-body">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
            <label style="display:flex; align-items:center; gap:10px; padding:14px; border:1.5px solid var(--border); border-radius:var(--radius-sm); cursor:pointer">
              <input type="radio" name="payment_method" value="cod" checked required>
              <div>
                <strong>💵 Bayar di Tempat (COD)</strong>
              </div>
            </label>
            <label style="display:flex; align-items:center; gap:10px; padding:14px; border:1.5px solid var(--border); border-radius:var(--radius-sm); cursor:pointer">
              <input type="radio" name="payment_method" value="transfer" required>
              <div>
                <strong>🏦 Transfer Bank</strong>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- NOTES -->
      <div class="card">
        <div class="card-header">
          <h3>📝 Catatan Tambahan</h3>
        </div>
        <div class="card-body">
          <textarea name="notes" class="form-control" placeholder="Contoh: Titip di pos satpam, sambal dipisah, dll." style="min-height:80px"></textarea>
        </div>
      </div>
    </div>

    <!-- Hidden Voucher Input -->
    <input type="hidden" name="voucher_id" id="voucherIdInput" value="">
  </form>

  <!-- SIDEBAR SUMMARY -->
  <div style="display:grid; gap:20px">
    <!-- VOUCHER -->
    <div class="card">
      <div class="card-header">
        <h3>🎟️ Pakai Voucher</h3>
      </div>
      <div class="card-body">
        <div style="display:flex; gap:8px">
          <input type="text" id="voucherCode" class="form-control" placeholder="Kode Voucher" style="text-transform:uppercase">
          <button type="button" id="applyVoucher" class="btn btn-dark">Pakai</button>
        </div>
        <div id="voucherResult" style="margin-top:12px"></div>
      </div>
    </div>

    <!-- BILLING -->
    <div class="card">
      <div class="card-header">
        <h3>Rincian Pembayaran</h3>
      </div>
      <div class="card-body">
        <div style="display:grid; gap:12px; font-size:13px">
          <div style="display:flex; justify-content:space-between">
            <span style="color:var(--text-muted)">Subtotal</span>
            <span><?= formatRupiah($subtotal) ?></span>
          </div>
          <div style="display:flex; justify-content:space-between">
            <span style="color:var(--text-muted)">Ongkos Kirim (Flat/Estimasi)</span>
            <span>Rp 10.000</span>
          </div>
          <div style="display:flex; justify-content:space-between; color:var(--success)">
            <span>Potongan Diskon</span>
            <span>-<span id="discountAmount">Rp 0</span></span>
          </div>
          <hr style="border:none; border-top:1px solid var(--border)">
          <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:800">
            <span>Total Bayar</span>
            <strong id="finalTotal" style="color:var(--primary)"><?= formatRupiah($subtotal + 10000) ?></strong>
          </div>
        </div>
        
        <!-- Total stored as raw attribute for JS computation -->
        <span id="checkoutTotal" data-total="<?= $subtotal ?>" style="display:none"></span>

        <div style="margin-top:20px">
          <?php if (empty($addresses)): ?>
          <button type="button" class="btn btn-primary btn-block btn-lg" disabled>Alamat Belum Diisi</button>
          <?php else: ?>
          <button type="submit" form="checkoutForm" class="btn btn-primary btn-block btn-lg">Buat Pesanan Sekarang</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
