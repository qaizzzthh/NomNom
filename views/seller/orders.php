<?php
require_once __DIR__ . '/../../config/database.php';
requireRole('seller');

$db = getDB();
$user = currentUser();

// Fetch restaurant
$rq = $db->prepare("SELECT * FROM restaurants WHERE seller_id = ?");
$rq->execute([$user['id']]);
$resto = $rq->fetch(PDO::FETCH_ASSOC);

if (!$resto) {
    flash('error', 'Silakan daftarkan restoran terlebih dahulu.');
    redirect(BASE_URL . '/views/seller/restaurant.php');
}

$resto_id = $resto['id'];

// Get tab
$tab = $_GET['tab'] ?? 'pending';

// Query orders
$query = "SELECT o.*, u.name as buyer_name, p.payment_method, p.status as payment_status 
          FROM orders o 
          JOIN users u ON o.buyer_id = u.id 
          JOIN payments p ON p.order_id = o.id
          WHERE o.restaurant_id = $resto_id ";

if ($tab === 'pending') {
    $query .= "AND o.status = 'pending' ";
} elseif ($tab === 'active') {
    $query .= "AND o.status IN ('confirmed', 'preparing', 'on_delivery') ";
} elseif ($tab === 'completed') {
    $query .= "AND o.status = 'delivered' ";
} else {
    $query .= "AND o.status = 'cancelled' ";
}
$query .= "ORDER BY o.id DESC";

$oq = $db->query($query);
$orders = $oq->fetchAll(PDO::FETCH_ASSOC);

$title = 'Pesanan Masuk';
$role  = 'seller';
$sidebar = true;
ob_start();
?>
<div class="page-header">
  <div>
    <h2 class="page-title">📋 Pesanan Masuk</h2>
    <p class="page-subtitle">Kelola pesanan pelanggan dan update status penyiapan makanan</p>
  </div>
</div>

<!-- TABS -->
<div class="category-chips" style="margin-bottom:20px">
  <a href="?tab=pending" class="category-chip <?= $tab === 'pending' ? 'active' : '' ?>">⌛ Menunggu Konfirmasi</a>
  <a href="?tab=active" class="category-chip <?= $tab === 'active' ? 'active' : '' ?>">👨‍🍳 Sedang Diproses</a>
  <a href="?tab=completed" class="category-chip <?= $tab === 'completed' ? 'active' : '' ?>">✅ Selesai</a>
  <a href="?tab=cancelled" class="category-chip <?= $tab === 'cancelled' ? 'active' : '' ?>">❌ Dibatalkan</a>
</div>

<!-- ORDERS LIST -->
<div style="display:grid; gap:20px">
  <?php if (empty($orders)): ?>
  <div class="card">
    <div class="card-body">
      <div class="empty-state" style="padding:40px 20px">
        <div class="empty-icon">📋</div>
        <h3>Tidak ada pesanan</h3>
        <p>Pesanan dalam kategori ini sedang kosong.</p>
      </div>
    </div>
  </div>
  <?php else: ?>
    <?php foreach ($orders as $o): 
        // Get order items
        $iq = $db->prepare("SELECT oi.*, p.name as pname FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $iq->execute([$o['id']]);
        $items = $iq->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="card">
      <div class="card-header" style="background:var(--bg)">
        <div>
          <strong>#<?= $o['order_code'] ?></strong>
          <span style="font-size:12px; color:var(--text-muted); margin-left:12px"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></span>
        </div>
        <div>
          <span class="status-badge status-<?= $o['status'] ?>"><?= statusLabel($o['status']) ?></span>
        </div>
      </div>
      <div class="card-body" style="display:grid; grid-template-columns: 2fr 1fr; gap:20px">
        <div>
          <strong style="font-size:13px; color:var(--text-muted)">Pelanggan:</strong>
          <p style="font-size:14px; font-weight:700; margin-bottom:8px"><?= sanitize($o['buyer_name']) ?></p>
          
          <strong style="font-size:13px; color:var(--text-muted)">Daftar Item:</strong>
          <ul style="margin:6px 0 12px 18px; font-size:13px; display:grid; gap:4px">
            <?php foreach ($items as $item): ?>
            <li><?= $item['qty'] ?>x <strong><?= sanitize($item['pname']) ?></strong> - <?= formatRupiah($item['subtotal']) ?></li>
            <?php endforeach; ?>
          </ul>
          
          <?php if ($o['notes']): ?>
          <div style="font-size:12px; background:var(--bg); padding:10px; border-radius:6px; margin-top:8px">
            📝 <strong>Catatan:</strong> <?= sanitize($o['notes']) ?>
          </div>
          <?php endif; ?>
        </div>
        
        <div style="display:flex; flex-direction:column; justify-content:space-between; border-left:1px solid var(--border); padding-left:20px">
          <div>
            <div style="font-size:12px; color:var(--text-muted)">Metode Bayar: <strong><?= strtoupper($o['payment_method']) ?></strong></div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:2px">Status Bayar: 
              <span class="status-badge status-<?= $o['payment_status'] ?>" style="font-size:10px; padding:1px 6px"><?= strtoupper($o['payment_status']) ?></span>
            </div>
            <div style="font-size:15px; font-weight:800; margin-top:10px; color:var(--primary)">
              Total: <?= formatRupiah($o['total_amount']) ?>
            </div>
          </div>
          
          <!-- Actions Form -->
          <div style="margin-top:16px">
            <form action="<?= BASE_URL ?>/controllers/OrderController.php?action=confirm" method="POST" style="display:flex; gap:8px; flex-wrap:wrap">
              <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
              
              <?php if ($o['status'] === 'pending'): ?>
                <button type="submit" name="new_status" value="confirmed" class="btn btn-primary btn-sm">Terima Pesanan</button>
                <button type="submit" name="new_status" value="cancelled" class="btn btn-danger btn-sm" onclick="return confirm('Batalkan pesanan ini?')">Tolak</button>
              <?php elseif ($o['status'] === 'confirmed'): ?>
                <button type="submit" name="new_status" value="preparing" class="btn btn-primary btn-sm">Mulai Masak</button>
              <?php elseif ($o['status'] === 'preparing'): ?>
                <div style="font-size:12px; color:var(--text-muted)">Menunggu kurir driver mengambil makanan...</div>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
