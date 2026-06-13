<?php
require_once __DIR__ . '/../../config/database.php';
if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$title = 'Masuk';
$role  = 'public';
ob_start();
?>
<meta name="base-url" content="<?= BASE_URL ?>">
<style>
.auth-wrapper {
  min-height: calc(100vh - 68px);
  display: flex; align-items: center; justify-content: center;
  padding: 40px 16px;
  background: linear-gradient(135deg, #fff4ef 0%, #fff9f5 100%);
}
.auth-card {
  background: white; border-radius: 24px;
  padding: 48px 40px; width: 100%; max-width: 440px;
  box-shadow: 0 20px 60px rgba(255,107,43,0.12);
}
.auth-logo { text-align: center; margin-bottom: 28px; }
.auth-logo .logo-icon { height: 72px; width: auto; display: block; margin: 0 auto 12px; }
.auth-logo h2 { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
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
.auth-subtitle { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
.auth-divider { text-align: center; color: var(--text-muted); font-size: 13px; margin: 20px 0; position: relative; }
.auth-divider::before, .auth-divider::after {
  content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: var(--border);
}
.auth-divider::before { left: 0; }
.auth-divider::after { right: 0; }
</style>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <img class="logo-icon" src="<?= BASE_URL ?>/assets/images/NomNom.id-Logo.svg" alt="NomNom.id Logo">
      <h2>Masuk ke <span>NomNom.id</span></h2>
      <div class="auth-subtitle">Pesan makanan favoritmu sekarang</div>
    </div>

    <?php $err = flash('error'); if ($err): ?>
    <div class="alert alert-error"><?= $err ?></div>
    <?php endif; ?>
    <?php $suc = flash('success'); if ($suc): ?>
    <div class="alert alert-success"><?= $suc ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/controllers/AuthController.php?action=login" method="POST">
      <div class="form-group">
        <label class="form-label">Email <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-control" placeholder="email@kamu.com" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password <span class="required">*</span></label>
        <div class="input-group">
          <i class="fa fa-lock input-icon"></i>
          <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
          <button type="button" onclick="togglePwd()" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
            <i class="fa fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
          <input type="checkbox" name="remember" style="accent-color:var(--primary)"> Ingat saya
        </label>
        <a href="#" style="font-size:13px;color:var(--text-muted)">Lupa password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk Sekarang</button>
    </form>

    <div class="auth-divider">atau</div>

    <p style="text-align:center;font-size:14px;color:var(--text-muted)">
      Belum punya akun?
      <a href="<?= BASE_URL ?>/views/public/register.php" style="font-weight:700">Daftar Gratis →</a>
    </p>

    <!-- Demo accounts -->
    <div style="margin-top:24px;padding:16px;background:var(--bg);border-radius:12px;font-size:12px">
      <div style="font-weight:700;margin-bottom:8px;color:var(--text-muted)">🔑 Akun Demo:</div>
      <div style="display:grid;gap:4px">
        <div><strong>Admin:</strong> admin@nomnom.id / password</div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePwd() {
  const input = document.getElementById('passwordInput');
  const icon = document.getElementById('eyeIcon');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fa fa-eye-slash'; }
  else { input.type = 'password'; icon.className = 'fa fa-eye'; }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
