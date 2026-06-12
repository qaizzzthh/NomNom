<?php
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'login';

switch ($action) {
    case 'register': handleRegister(); break;
    case 'logout':   handleLogout();   break;
    default:         handleLogin();    break;
}

// ─── REGISTER ────────────────────────────────────────
function handleRegister(): void {
    $db = getDB();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(BASE_URL . '/views/public/register.php');
    }

    $name     = sanitize($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $phone    = sanitize($_POST['phone'] ?? '');
    $role     = $_POST['role'] ?? 'buyer';

    // Validasi
    $errors = [];
    if (strlen($name) < 3)    $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($password !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';
    if (!in_array($role, ['buyer', 'seller', 'driver'])) $errors[] = 'Role tidak valid.';

    // Cek email sudah ada
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) $errors[] = 'Email sudah terdaftar.';

    if ($errors) {
        $_SESSION['reg_errors'] = $errors;
        $_SESSION['reg_old'] = compact('name', 'email', 'phone', 'role');
        redirect(BASE_URL . '/views/public/register.php');
        return;
    }

    $hashed      = password_hash($password, PASSWORD_BCRYPT);
    $is_verified = ($role === 'buyer') ? true : false;

    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_verified, is_active) VALUES (?,?,?,?,?,?,TRUE)");
    $stmt->execute([$name, $email, $hashed, $phone, $role, $is_verified ? 'true' : 'false']);

    if ($stmt->rowCount() > 0) {
        $userId = (int)$db->lastInsertId('users_id_seq');
        sendNotification($userId, 'Selamat Datang di NomNom! 🍜', "Halo $name! Akun Anda berhasil dibuat." . ($role !== 'buyer' ? ' Tunggu verifikasi dari admin.' : ''), 'system');
        flash('success', 'Registrasi berhasil! Silakan login.');
        redirect(BASE_URL . '/views/public/login.php');
    } else {
        flash('error', 'Registrasi gagal, coba lagi.');
        redirect(BASE_URL . '/views/public/register.php');
    }
}

// ─── LOGIN ───────────────────────────────────────────
function handleLogin(): void {
    $db = getDB();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(BASE_URL . '/views/public/login.php');
    }

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$email || !$password) {
        flash('error', 'Email dan password wajib diisi.');
        redirect(BASE_URL . '/views/public/login.php');
        return;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        flash('error', 'Email atau password salah.');
        redirect(BASE_URL . '/views/public/login.php');
        return;
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = $user;

    // Remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $upd   = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $upd->execute([$token, $user['id']]);
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
    }

    // Redirect by role
    $redirects = [
        'admin'  => BASE_URL . '/views/admin/dashboard.php',
        'seller' => BASE_URL . '/views/seller/dashboard.php',
        'driver' => BASE_URL . '/views/driver/dashboard.php',
        'buyer'  => BASE_URL . '/index.php',
    ];
    redirect($redirects[$user['role']] ?? BASE_URL . '/index.php');
}

// ─── LOGOUT ──────────────────────────────────────────
function handleLogout(): void {
    setcookie('remember_token', '', time() - 1, '/');
    session_destroy();
    redirect(BASE_URL . '/views/public/login.php');
}

// ─── Helper: kirim notifikasi ────────────────────────
function sendNotification(int $userId, string $title, string $message, string $type = 'system', ?int $refId = null): void {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $title, $message, $type, $refId]);
}
