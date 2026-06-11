<?php
$user = currentUser();
$role = $user['role'] ?? 'public';
?>
<footer>
  <div class="footer-wrapper">

    <div class="footer-top">
      <!-- Brand -->
      <div class="footer-brand">
        <div class="footer-logo">
          <span class="footer-logo-icon">🍜</span>
          <span class="footer-logo-name">NomNom</span>
        </div>
        <p class="footer-tagline">
          Platform pesan antar makanan terpercaya. Ratusan restoran siap mengantarkan makanan lezat ke pintu Anda.
        </p>
        <div class="footer-socials">
          <a href="#" class="social-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="social-btn" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          <a href="#" class="social-btn" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
          <a href="#" class="social-btn" title="Twitter / X"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
      </div>

      <!-- Links: Layanan -->
      <div class="footer-col">
        <div class="footer-col-title">Layanan</div>
        <ul>
          <li><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
          <li><a href="<?= BASE_URL ?>/views/public/restaurants.php">Semua Restoran</a></li>
          <li><a href="<?= BASE_URL ?>/views/public/search.php">Cari Menu</a></li>
          <?php if (isLoggedIn() && $role === 'buyer'): ?>
          <li><a href="<?= BASE_URL ?>/views/buyer/orders.php">Pesanan Saya</a></li>
          <li><a href="<?= BASE_URL ?>/views/buyer/cart.php">Keranjang</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Links: Bergabung -->
      <div class="footer-col">
        <div class="footer-col-title">Bergabung</div>
        <ul>
          <?php if (!isLoggedIn()): ?>
          <li><a href="<?= BASE_URL ?>/views/public/register.php">Daftar sebagai Pembeli</a></li>
          <li><a href="<?= BASE_URL ?>/views/public/register.php">Daftar sebagai Penjual</a></li>
          <li><a href="<?= BASE_URL ?>/views/public/register.php">Daftar sebagai Driver</a></li>
          <li><a href="<?= BASE_URL ?>/views/public/login.php">Masuk</a></li>
          <?php else: ?>
          <li><a href="<?= BASE_URL ?>/views/<?= $role ?>/dashboard.php">Dashboard</a></li>
          <li><a href="<?= BASE_URL ?>/views/public/profile.php">Profil Saya</a></li>
          <li><a href="<?= BASE_URL ?>/controllers/AuthController.php?action=logout">Keluar</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Links: Informasi -->
      <div class="footer-col">
        <div class="footer-col-title">Informasi</div>
        <ul>
          <li><a href="#">Tentang Kami</a></li>
          <li><a href="#">Kebijakan Privasi</a></li>
          <li><a href="#">Syarat & Ketentuan</a></li>
          <li><a href="#">Pusat Bantuan</a></li>
          <li><a href="#">Hubungi Kami</a></li>
        </ul>
      </div>

      <!-- Kontak -->
      <div class="footer-col">
        <div class="footer-col-title">Kontak</div>
        <ul class="footer-contact">
          <li>
            <i class="fa fa-map-marker-alt"></i>
            <span>Jl. Raya Perjuangan No.1,<br>Bekasi, Jawa Barat</span>
          </li>
          <li>
            <i class="fa fa-envelope"></i>
            <span>support@nomnom.id</span>
          </li>
          <li>
            <i class="fa fa-phone"></i>
            <span>+62 811-0000-1234</span>
          </li>
          <li>
            <i class="fa fa-clock"></i>
            <span>Senin–Minggu, 07.00–22.00 WIB</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <div class="footer-bottom-left">
        &copy; <?= date('Y') ?> <strong>NomNom Food Delivery</strong>. Semua hak dilindungi.
      </div>
      <div class="footer-bottom-right">
        Dibuat dengan ❤️ untuk Tugas Besar Pemrograman Web
      </div>
    </div>

  </div>
</footer>

<style>
footer {
  background: var(--bg-dark);
  color: rgba(255,255,255,0.55);
  font-size: 13px;
  margin-top: auto;
}

.footer-wrapper {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 32px;
}

/* ── Footer Top ── */
.footer-top {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1.4fr;
  gap: 40px;
  padding: 52px 0 40px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

/* Brand */
.footer-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.footer-logo-icon { font-size: 28px; }
.footer-logo-name {
  font-family: 'Fraunces', Georgia, serif;
  font-size: 24px;
  font-weight: 700;
  color: var(--accent);
}
.footer-tagline {
  font-size: 13px;
  line-height: 1.7;
  color: rgba(255,255,255,0.45);
  margin-bottom: 20px;
  max-width: 280px;
}
.footer-socials {
  display: flex;
  gap: 10px;
}
.social-btn {
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.12);
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,0.5);
  font-size: 15px;
  transition: all 0.2s;
}
.social-btn:hover {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
  transform: translateY(-2px);
}

/* Columns */
.footer-col-title {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: white;
  margin-bottom: 16px;
}
.footer-col ul {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.footer-col ul li a {
  color: rgba(255,255,255,0.5);
  font-size: 13px;
  transition: color 0.2s;
}
.footer-col ul li a:hover {
  color: var(--accent);
}

/* Contact list */
.footer-contact li {
  display: flex !important;
  align-items: flex-start;
  gap: 10px;
  color: rgba(255,255,255,0.5);
}
.footer-contact li i {
  color: var(--primary);
  margin-top: 3px;
  font-size: 13px;
  flex-shrink: 0;
}

/* ── Footer Bottom ── */
.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 0;
  font-size: 12px;
  color: rgba(255,255,255,0.3);
  flex-wrap: wrap;
  gap: 8px;
}
.footer-bottom strong { color: rgba(255,255,255,0.55); }

/* Responsive */
@media (max-width: 1024px) {
  .footer-top {
    grid-template-columns: 1fr 1fr 1fr;
  }
  .footer-brand {
    grid-column: 1 / -1;
  }
}
@media (max-width: 640px) {
  .footer-top {
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    padding: 36px 0 28px;
  }
  .footer-brand { grid-column: 1 / -1; }
  .footer-bottom { flex-direction: column; text-align: center; }
  .footer-wrapper { padding: 0 16px; }
}
</style>
