<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_delivery');
// Dynamic BASE_URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

if (!empty($docRoot) && strpos($projectRoot, $docRoot) === 0) {
    $relativePath = substr($projectRoot, strlen($docRoot));
    $baseUrl = $protocol . $host . '/' . trim($relativePath, '/');
} else {
    $baseUrl = $protocol . $host . '/UAS_INFO2425_202410715117_QAISY-AL-QATTHAN-JAKARIA';
}
$baseUrl = rtrim($baseUrl, '/');
define('BASE_URL', $baseUrl);

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('APP_NAME', 'NomNom');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/views/public/login.php');
        exit;
    }
}

function requireRole(string|array $roles) {
    requireLogin();
    $user = currentUser();
    $roles = (array)$roles;
    if (!in_array($user['role'], $roles)) {
        header('Location: ' . BASE_URL . '/views/public/unauthorized.php');
        exit;
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
    } else {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

function generateOrderCode() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function uploadFile($file, $folder = 'products') {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > 5 * 1024 * 1024) return false;
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $path = UPLOAD_PATH . $folder . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $folder . '/' . $filename;
    }
    return false;
}