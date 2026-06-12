<?php
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$db = getDB();
$user = currentUser();
$order_id = (int)($_GET['id'] ?? 0);

// Fetch order
$soq = $db->prepare("SELECT o.*, r.name as resto_name, r.address as resto_addr, u.phone as resto_phone, d.name as driver_name, d.phone as driver_phone
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON r.seller_id = u.id
    LEFT JOIN users d ON o.driver_id = d.id
    WHERE o.id = ?");
$soq->execute([$order_id]);
$order = $soq->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash('error', 'Pesanan tidak ditemukan.');
    redirect(BASE_URL . '/index.php');
}

// Security: buyers can only see their own orders, sellers/drivers/admin can view if authorized
if ($user['role'] === 'buyer' && $order['buyer_id'] != $user['id']) {
    redirect(BASE_URL . '/views/public/unauthorized.php');
}

// Handle upload payment proof
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    if (!empty($_FILES['proof']['name'])) {
        $proof = uploadFile($_FILES['proof'], 'payments');
        if ($proof) {
            $up = $db->prepare("UPDATE payments SET proof = ?, status = 'pending' WHERE order_id = ?");
            $up->execute([$proof, $order_id]);

            $stmt = $db->prepare("INSERT INTO order_tracking (order_id, status, description, changed_by) VALUES (?, 'pending', 'Bukti pembayaran diunggah oleh pembeli', ?)");
            $stmt->execute([$order_id, $user['id']]);
            
            flash('success', 'Bukti pembayaran berhasil diunggah! Menunggu verifikasi admin.');
        } else {
            flash('error', 'Gagal mengunggah bukti pembayaran.');
        }
    } else {
        flash('error', 'Bukti pembayaran kosong.');
    }
    redirect(BASE_URL . '/views/buyer/order_detail.php?id=' . $order_id);
}

// Fetch items
$siq = $db->prepare("SELECT oi.*, p.name as pname, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$siq->execute([$order_id]);
$items = $siq->fetchAll(PDO::FETCH_ASSOC);

// Fetch address
$saa = $db->prepare("SELECT * FROM buyer_addresses WHERE id = ?");
$saa->execute([$order['address_id']]);
$address = $saa->fetch(PDO::FETCH_ASSOC);

$spa = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
$spa->execute([$order_id]);
$payment = $spa->fetch(PDO::FETCH_ASSOC);

// Fetch tracking logs
$stq = $db->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at DESC");
$stq->execute([$order_id]);
$tracking = $stq->fetchAll(PDO::FETCH_ASSOC);

$srq = $db->prepare("SELECT product_id FROM review WHERE order_id = ? AND user_id = ?");
$srq->execute([$order_id, $user['id']]);
$reviewed_res = $srq->fetchAll(PDO::FETCH_ASSOC);
$reviewed_ids = array_column($reviewed_res, 'product_id');

$title = 'Detail Pesanan #' . $order['order_code'];
$role  = $user['role'];
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">

<div class="page-header">
  <div>
    <h2 class="page-title">📦 Detail Pesanan #<?= $order['order_code'] ?></h2>
    <p class="page-subtitle">Dibuat pada: <?= date('d M Y H:i', strtotime($order['created_at'])) ?></p>
  </div>
  <div>
    <span class="status-badge status-<?= $order['status'] ?>" id="order-status-badge" style="font-size:14px; padding:6px 16px">
      <?= statusLabel($order['status']) ?>
    </span>
  </div>
</div>

<!-- LIVE TRACKING IDENTIFIER FOR APP.JS -->
<div id="order-tracking-live" data-order="<?= $order_id ?>" style="display:none"></div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:28px; align-items:start">
  
  <div style="display:grid; gap:24px">
    <!-- RESTO AND BUYER DETAILS -->
    <div class="card">
      <div class="card-body" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; font-size:13px">
        <div>
          <h4 style="margin-bottom:8px; font-weight:700">🏪 Dari Restoran Partner</h4>
          <strong><?= sanitize($order['resto_name']) ?></strong>
          <p style="color:var(--text-muted); margin-top:2px"><?= sanitize($order['resto_addr']) ?></p>
          <p style="color:var(--text-muted)">📞 <?= sanitize($order['resto_phone']) ?></p>
        </div>
        <div>
          <h4 style="margin-bottom:8px; font-weight:700">📍 Alamat Pengiriman</h4>
          <?php if ($address): ?>
          <strong>[<?= sanitize($address['label']) ?>] <?= sanitize($address['recipient_name']) ?></strong>
          <p style="color:var(--text-muted); margin-top:2px"><?= sanitize($address['address']) ?></p>
          <p style="color:var(--text-muted)">📞 <?= sanitize($address['phone']) ?></p>
          <?php else: ?>
          <p style="color:var(--danger)">Alamat tidak ditemukan.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ORDER ITEMS -->
    <div class="card">
      <div class="card-header">
        <h3>Daftar Item Menu</h3>
      </div>
      <div class="card-body" style="padding:0">
        <?php foreach ($items as $item): ?>
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border)">
          <div style="display:flex; align-items:center; gap:12px">
            <div style="width:48px; height:48px; border-radius:8px; background:var(--bg); display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0">
              <?php if ($item['image']): ?>
              <img src="<?= BASE_URL ?>/uploads/<?= $item['image'] ?>" style="width:100%; height:100%; object-fit:cover; border-radius:8px">
              <?php else: ?>
              🍴
              <?php endif; ?>
            </div>
            <div>
              <strong style="font-size:14px"><?= sanitize($item['pname']) ?></strong>
              <p style="font-size:12px; color:var(--text-muted)"><?= $item['qty'] ?> x <?= formatRupiah($item['price']) ?></p>
              <?php if ($item['notes']): ?>
              <small style="color:var(--text-muted)">📝 Catatan: <?= sanitize($item['notes']) ?></small>
              <?php endif; ?>
            </div>
          </div>
          <div>
            <strong style="color:var(--primary)"><?= formatRupiah($item['subtotal']) ?></strong>
          </div>
        </div>
        <?php endforeach; ?>
        
        <div style="padding:16px 20px; display:grid; gap:8px; font-size:13px; background:var(--bg)">
          <div style="display:flex; justify-content:space-between">
            <span style="color:var(--text-muted)">Subtotal</span>
            <span><?= formatRupiah($order['subtotal']) ?></span>
          </div>
          <div style="display:flex; justify-content:space-between">
            <span style="color:var(--text-muted)">Ongkos Kirim</span>
            <span><?= formatRupiah($order['delivery_fee']) ?></span>
          </div>
          <?php if ($order['discount'] > 0): ?>
          <div style="display:flex; justify-content:space-between; color:var(--success)">
            <span>Diskon Voucher</span>
            <span>-<?= formatRupiah($order['discount']) ?></span>
          </div>
          <?php endif; ?>
          <hr style="border:none; border-top:1px solid var(--border)">
          <div style="display:flex; justify-content:space-between; font-weight:800; font-size:15px">
            <span>Total Pembayaran</span>
            <span style="color:var(--primary)"><?= formatRupiah($order['total_amount']) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- REVIEWS SECTION -->
    <?php if ($order['status'] === 'delivered' && $user['role'] === 'buyer'): ?>
    <div class="card">
      <div class="card-header">
        <h3>⭐ Tulis Ulasan Menu</h3>
      </div>
      <div class="card-body" style="display:grid; gap:20px">
        <?php foreach ($items as $item): ?>
        <div style="padding:14px; border:1px solid var(--border); border-radius:var(--radius-sm)">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px">
            <strong><?= sanitize($item['pname']) ?></strong>
            <?php if (in_array($item['product_id'], $reviewed_ids)): ?>
            <span class="status-badge status-verified">Selesai Direview</span>
            <?php else: ?>
            <button class="btn btn-primary btn-sm" data-modal="reviewModal-<?= $item['product_id'] ?>">Beri Ulasan</button>
            <?php endif; ?>
          </div>
        </div>

        <!-- REVIEW MODAL -->
        <?php if (!in_array($item['product_id'], $reviewed_ids)): ?>
        <div class="modal-overlay" id="reviewModal-<?= $item['product_id'] ?>">
          <div class="modal">
            <div class="modal-header">
              <h3>Ulas <?= sanitize($item['pname']) ?></h3>
              <button class="modal-close" data-modal="reviewModal-<?= $item['product_id'] ?>">&times;</button>
            </div>
            <form action="<?= BASE_URL ?>/controllers/ReviewController.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="order_id" value="<?= $order_id ?>">
              <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
              
              <div class="modal-body" style="display:grid; gap:16px">
                <div class="form-group" style="margin:0">
                  <label class="form-label">Rating <span class="required">*</span></label>
                  <div class="star-rating">
                    <input type="hidden" name="rating" value="5" required>
                    <i class="fa fa-star star active"></i>
                    <i class="fa fa-star star active"></i>
                    <i class="fa fa-star star active"></i>
                    <i class="fa fa-star star active"></i>
                    <i class="fa fa-star star active"></i>
                  </div>
                </div>
                <div class="form-group" style="margin:0">
                  <label class="form-label">Komentar / Ulasan</label>
                  <textarea name="comment" class="form-control" placeholder="Tuliskan pendapatmu tentang rasa makanan ini..." style="min-height:80px"></textarea>
                </div>
                <div class="form-group" style="margin:0">
                  <label class="form-label">Foto Makanan (Opsional)</label>
                  <input type="file" name="photo" accept="image/*" class="form-control">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline modal-close">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- SIDEBAR PROGRESS AND PAYMENT -->
  <div style="display:grid; gap:24px">
    
    <!-- DRIVER INFO -->
    <?php if ($order['driver_id']): ?>
    <div class="card">
      <div class="card-header">
        <h3>🛵 Informasi Driver</h3>
      </div>
      <div class="card-body" style="font-size:13px">
        <div style="display:flex; align-items:center; gap:12px">
          <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-bg); color:var(--primary); font-size:18px; font-weight:800; display:flex; align-items:center; justify-content:center">
            🛵
          </div>
          <div>
            <strong><?= sanitize($order['driver_name']) ?></strong>
            <p style="color:var(--text-muted)">📞 <?= sanitize($order['driver_phone']) ?></p>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- PAYMENT STATE -->
    <div class="card">
      <div class="card-header">
        <h3>💳 Status Pembayaran</h3>
      </div>
      <div class="card-body" style="font-size:13px">
        <div style="display:grid; gap:8px">
          <div>Metode: <strong><?= strtoupper($payment['payment_method']) ?></strong></div>
          <div>Status: <span class="status-badge status-<?= $payment['status'] ?>"><?= strtoupper($payment['status']) ?></span></div>
          
          <?php if ($payment['payment_method'] === 'transfer'): ?>
            <hr style="border:none; border-top:1px solid var(--border); margin:8px 0">
            <?php if (empty($payment['proof'])): ?>
              <p style="color:var(--danger); font-size:12px; margin-bottom:8px">⚠️ Anda harus mengunggah bukti transfer bank.</p>
              <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_payment">
                <input type="file" name="proof" class="form-control" style="font-size:11px" required>
                <button type="submit" class="btn btn-primary btn-block btn-sm" style="margin-top:8px">Unggah Bukti</button>
              </form>
            <?php else: ?>
              <div style="font-size:12px; color:var(--success)">✅ Bukti pembayaran telah diunggah. Menunggu verifikasi admin.</div>
              <a href="<?= BASE_URL ?>/uploads/<?= $payment['proof'] ?>" target="_blank" style="display:block; text-align:center; font-size:12px; margin-top:8px">Lihat Bukti Unggahan</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CANCEL BUTTON -->
    <?php if ($order['status'] === 'pending' && $user['role'] === 'buyer'): ?>
    <form action="<?= BASE_URL ?>/controllers/OrderController.php?action=cancel" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
      <input type="hidden" name="order_id" value="<?= $order_id ?>">
      <button type="submit" class="btn btn-danger btn-block btn-lg">Batalkan Pesanan</button>
    </form>
    <?php endif; ?>

    <!-- TRACKING TIMELINE -->
    <div class="card">
      <div class="card-header">
        <h3>📈 Status Pengiriman</h3>
      </div>
      <div class="card-body">
        <div class="tracking-timeline">
          <?php foreach ($tracking as $t): ?>
          <div class="tracking-step done">
            <div class="tracking-step-title"><?= sanitize($t['description']) ?></div>
            <div class="tracking-step-time"><?= date('H:i, d M Y', strtotime($t['created_at'])) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
