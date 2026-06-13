<?php
require_once __DIR__ . '/../../config/database.php';
if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$old    = $_SESSION['reg_old'] ?? [];
$errors = $_SESSION['reg_errors'] ?? [];
unset($_SESSION['reg_old'], $_SESSION['reg_errors']);

$title = 'Daftar Akun';
$role  = 'public';
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">
<style>
.auth-wrapper {
  min-height: calc(100vh - 68px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  background: linear-gradient(135deg, #fff4ef 0%, #fff9f5 100%);
}
.auth-card {
  background: white;
  border-radius: 24px;
  padding: 44px 40px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 20px 60px rgba(255,107,43,0.12);
}
.auth-logo {
  text-align: center;
  margin-bottom: 28px;
}
.auth-logo .logo-icon { height: 64px; width: 76px; display: block; margin: 0 auto 12px; object-fit: contain; }
.auth-logo h2 { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
.auth-logo h2 span {
  font-family: 'Montserrat Alternates', sans-serif;
  color: var(--primary-dark);
  -webkit-text-stroke: 1.5px #ffffff;
  text-shadow: 
    -1.5px -1.5px 0 #ffffff,  
     1.5px -1.5px 0 #ffffff,
    -1.5px  1.5px 0 #ffffff,
     1.5px  1.5px 0 #ffffff;
}
.auth-logo p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

/* Role Selector */
.role-selector {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 24px;
}
.role-option { position: relative; }
.role-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0; height: 0;
}
.role-option label {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 10px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}
.role-option label:hover {
  border-color: var(--primary);
  background: var(--primary-bg);
}
.role-option input:checked + label {
  border-color: var(--primary);
  background: var(--primary-bg);
  box-shadow: 0 0 0 3px rgba(255,107,43,0.15);
}
.role-option .role-icon { font-size: 28px; }
.role-option .role-name {
  font-size: 12px;
  font-weight: 700;
  color: var(--text);
}
.role-option .role-desc {
  font-size: 11px;
  color: var(--text-muted);
  line-height: 1.4;
}
.role-option input:checked + label .role-name { color: var(--primary); }

/* Input row 2 col */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

/* Strength meter */
.strength-bar {
  height: 4px;
  border-radius: 4px;
  background: var(--border);
  margin-top: 6px;
  overflow: hidden;
}
.strength-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s, background 0.3s;
  width: 0%;
}
.strength-text {
  font-size: 11px;
  margin-top: 4px;
  font-weight: 600;
}

/* Terms */
.terms-check {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 20px;
  cursor: pointer;
}
.terms-check input { margin-top: 2px; accent-color: var(--primary); flex-shrink: 0; }
.terms-check a { color: var(--primary); font-weight: 600; }

/* Error list */
.error-list {
  background: #fee2e2;
  border-left: 4px solid var(--danger);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  margin-bottom: 20px;
}
.error-list ul {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.error-list li {
  font-size: 13px;
  color: #991b1b;
  display: flex;
  align-items: center;
  gap: 6px;
}
.error-list li::before { content: '•'; font-weight: 700; }

/* Seller/Driver notice */
.role-notice {
  display: none;
  background: #fef3c7;
  border: 1px solid #fcd34d;
  border-radius: var(--radius-sm);
  padding: 12px 14px;
  font-size: 12px;
  color: #92400e;
  margin-bottom: 16px;
  gap: 8px;
  align-items: flex-start;
}
.role-notice.show { display: flex; }
.role-notice i { margin-top: 2px; flex-shrink: 0; }
</style>

<div class="auth-wrapper">
  <div class="auth-card">

    <!-- Logo -->
    <div class="auth-logo">
      <img class="logo-icon" src="<?= BASE_URL ?>/assets/images/NomNom.id-Logo.svg" alt="NomNom.id Logo" width="76" height="64">
      <h2>Daftar di <span>NomNom.id</span></h2>
      <p>Buat akun gratis dan mulai memesan makanan</p>
    </div>

    <!-- Error list -->
    <?php if (!empty($errors)): ?>
    <div class="error-list">
      <ul>
        <?php foreach ($errors as $e): ?>
        <li><?= sanitize($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form action="<?= BASE_URL ?>/controllers/AuthController.php?action=register" method="POST" id="registerForm" novalidate>

      <!-- Pilih Role -->
      <div class="form-group">
        <label class="form-label">Daftar sebagai <span class="required">*</span></label>
        <div class="role-selector">

          <div class="role-option">
            <input type="radio" name="role" id="role_buyer" value="buyer"
              <?= ($old['role'] ?? 'buyer') === 'buyer' ? 'checked' : '' ?>>
            <label for="role_buyer">
              <span class="role-icon">🛒</span>
              <span class="role-name">Pembeli</span>
              <span class="role-desc">Pesan makanan dari restoran favoritmu</span>
            </label>
          </div>

          <div class="role-option">
            <input type="radio" name="role" id="role_seller" value="seller"
              <?= ($old['role'] ?? '') === 'seller' ? 'checked' : '' ?>>
            <label for="role_seller">
              <span class="role-icon">🍽️</span>
              <span class="role-name">Penjual</span>
              <span class="role-desc">Buka restoran dan jual makananmu</span>
            </label>
          </div>

          <div class="role-option">
            <input type="radio" name="role" id="role_driver" value="driver"
              <?= ($old['role'] ?? '') === 'driver' ? 'checked' : '' ?>>
            <label for="role_driver">
              <span class="role-icon">🛵</span>
              <span class="role-name">Driver</span>
              <span class="role-desc">Antar pesanan dan dapatkan penghasilan</span>
            </label>
          </div>

        </div>

        <!-- Notice untuk seller & driver -->
        <div class="role-notice" id="roleNotice">
          <i class="fa fa-info-circle"></i>
          <span id="roleNoticeText">Akun penjual memerlukan verifikasi dari admin sebelum bisa digunakan.</span>
        </div>
      </div>

      <!-- Nama & No HP -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Lengkap <span class="required">*</span></label>
          <div class="input-group">
            <i class="fa fa-user input-icon"></i>
            <input type="text" name="name" class="form-control"
              placeholder="Nama kamu"
              value="<?= sanitize($old['name'] ?? '') ?>"
              minlength="3" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">No. HP <span class="required">*</span></label>
          <div class="input-group">
            <i class="fa fa-phone input-icon"></i>
            <input type="tel" name="phone" class="form-control"
              placeholder="08xxxxxxxxxx"
              value="<?= sanitize($old['phone'] ?? '') ?>"
              pattern="^08[0-9]{8,12}$"
              required>
          </div>
        </div>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label class="form-label">Alamat Email <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-control"
            placeholder="email@kamu.com"
            value="<?= sanitize($old['email'] ?? '') ?>"
            required>
        </div>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label class="form-label">Password <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-lock input-icon"></i>
          <input type="password" name="password" id="passwordInput" class="form-control"
            placeholder="Min. 8 karakter"
            minlength="8" required
            oninput="checkStrength(this.value)">
          <button type="button" onclick="togglePwd('passwordInput','eyeIcon1')"
            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
            <i class="fa fa-eye" id="eyeIcon1"></i>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-text" id="strengthText"></div>
      </div>

      <!-- Konfirmasi Password -->
      <div class="form-group">
        <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-lock input-icon"></i>
          <input type="password" name="confirm_password" id="confirmInput" class="form-control"
            placeholder="Ulangi password"
            required>
          <button type="button" onclick="togglePwd('confirmInput','eyeIcon2')"
            style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
            <i class="fa fa-eye" id="eyeIcon2"></i>
          </button>
        </div>
        <div class="form-error" id="confirmError" style="display:none">Password tidak cocok.</div>
      </div>

      <!-- Terms -->
      <label class="terms-check">
        <input type="checkbox" id="terms" required>
        Saya menyetujui <a href="#">Syarat & Ketentuan</a> serta <a href="#">Kebijakan Privasi</a> NomNom.id.
      </label>

      <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
        Buat Akun Sekarang 🚀
      </button>
    </form>

    <p style="text-align:center;font-size:14px;color:var(--text-muted);margin-top:20px">
      Sudah punya akun?
      <a href="<?= BASE_URL ?>/views/public/login.php" style="font-weight:700">Masuk di sini →</a>
    </p>

  </div>
</div>

<script>
// Toggle password visibility
function togglePwd(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa fa-eye';
  }
}

// Password strength meter
function checkStrength(val) {
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');
  let score = 0;
  if (val.length >= 8)              score++;
  if (/[A-Z]/.test(val))           score++;
  if (/[0-9]/.test(val))           score++;
  if (/[^A-Za-z0-9]/.test(val))   score++;

  const levels = [
    { pct: '0%',   color: '',               label: '' },
    { pct: '25%',  color: '#ef4444',         label: '😟 Lemah' },
    { pct: '50%',  color: '#f59e0b',         label: '🙂 Sedang' },
    { pct: '75%',  color: '#3b82f6',         label: '😊 Kuat' },
    { pct: '100%', color: '#22c55e',         label: '💪 Sangat Kuat' },
  ];
  const level = levels[score] || levels[0];
  fill.style.width      = level.pct;
  fill.style.background = level.color;
  text.textContent      = level.label;
  text.style.color      = level.color;
}

// Confirm password match
document.getElementById('confirmInput').addEventListener('input', function () {
  const pwd  = document.getElementById('passwordInput').value;
  const err  = document.getElementById('confirmError');
  err.style.display = (this.value && this.value !== pwd) ? 'block' : 'none';
});

// Role notice
const notices = {
  buyer:  { show: false, text: '' },
  seller: { show: true,  text: '⚠️ Akun penjual memerlukan verifikasi admin sebelum bisa membuka restoran.' },
  driver: { show: true,  text: '⚠️ Akun driver memerlukan verifikasi admin sebelum bisa menerima pesanan.' },
};
document.querySelectorAll('input[name="role"]').forEach(radio => {
  radio.addEventListener('change', function () {
    const notice = notices[this.value];
    const el     = document.getElementById('roleNotice');
    const txt    = document.getElementById('roleNoticeText');
    if (notice.show) {
      el.classList.add('show');
      txt.textContent = notice.text;
    } else {
      el.classList.remove('show');
    }
  });
});

// Form submit validation
document.getElementById('registerForm').addEventListener('submit', function (e) {
  const pwd   = document.getElementById('passwordInput').value;
  const conf  = document.getElementById('confirmInput').value;
  const terms = document.getElementById('terms').checked;

  if (pwd !== conf) {
    e.preventDefault();
    document.getElementById('confirmError').style.display = 'block';
    document.getElementById('confirmInput').focus();
    return;
  }
  if (!terms) {
    e.preventDefault();
    alert('Anda harus menyetujui Syarat & Ketentuan terlebih dahulu.');
    return;
  }

  // Loading state
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
