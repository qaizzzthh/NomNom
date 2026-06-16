<?php
// ─── SUPABASE POSTGRESQL CONFIGURATION ───────────────
// Menggunakan getenv() agar aman saat dideploy ke Render
define('DB_HOST', getenv('DB_HOST') ?: 'aws-1-ap-southeast-1.pooler.supabase.com');
define('DB_PORT', getenv('DB_PORT') ?: '6543');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres.ggcgucplxtyzpsydbsrj');
define('DB_PASS', getenv('DB_PASS') ?: '54iKBBmIuxULKN2q');

// Dynamic BASE_URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
    || ($_SERVER['SERVER_PORT'] ?? 80) == 443 
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
    if (!empty($docRoot) && strpos($projectRoot, $docRoot) === 0) {
        $relativePath = substr($projectRoot, strlen($docRoot));
        $baseUrl = $protocol . $host . '/' . trim($relativePath, '/');
    } else {
        $baseUrl = $protocol . $host . '/NomNom';
    }
} else {
    // Di Render, aplikasi langsung diakses via domain utama tanpa subfolder
    $baseUrl = $protocol . $host;
}

$baseUrl = rtrim($baseUrl, '/');
define('BASE_URL', $baseUrl);

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('APP_NAME', 'NomNom');

function getDB(): PDO {
    static $conn = null;
    if ($conn === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            DB_HOST, DB_PORT, DB_NAME
        );
        try {
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
        }
    }
    return $conn;
}

// ─── SESSION ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── AUTH HELPERS ─────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/views/public/login.php');
        exit;
    }
}

function requireRole(string|array $roles): void {
    requireLogin();
    $user  = currentUser();
    $roles = (array)$roles;
    if (!in_array($user['role'], $roles)) {
        header('Location: ' . BASE_URL . '/views/public/unauthorized.php');
        exit;
    }
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    } else {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
}

function formatRupiah(float|int $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

function generateOrderCode(): string {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function uploadFile(array $file, string $folder = 'products'): string|false {
    $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false;
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $path     = UPLOAD_PATH . $folder . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $folder . '/' . $filename;
    }
    return false;
}

function statusLabel(string $status): string {
    $labels = [
        'pending'     => '⏳ Menunggu',
        'confirmed'   => '✅ Dikonfirmasi',
        'preparing'   => '👨‍🍳 Dimasak',
        'on_delivery' => '🛵 Dikirim',
        'delivered'   => '✅ Selesai',
        'cancelled'   => '❌ Dibatalkan'
    ];
    return $labels[$status] ?? $status;
}