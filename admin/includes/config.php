<?php
// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Oturum başlat
session_start();

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mssr_web');

// PDO bağlantısı oluştur
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Temel URL ve yollar
define('BASE_URL', '/mssr-web/admin');
define('ADMIN_PATH', __DIR__ . '/..');

// CSRF token fonksiyonları
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Oturum kontrolü
function check_admin_session() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

// XSS koruma fonksiyonu
function escape_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Aktivite log fonksiyonu
function log_activity($admin_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (admin_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$admin_id, $action, $details]);
    } catch(PDOException $e) {
        error_log("Aktivite log hatası: " . $e->getMessage());
    }
}

// Güvenlik fonksiyonları
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// CSRF token oluştur
function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function validateToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token doğrulama hatası!');
    }
    return true;
}

// Oturum kontrolü
function checkSession() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// XSS koruması için çıktı temizleme
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Güvenli şifreleme
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Şifre doğrulama
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// IP adresi al
function getIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Giriş denemelerini kontrol et
function checkLoginAttempts($ip) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Giriş denemesi kaydet
function logLoginAttempt($ip, $success) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, success) VALUES (?, ?)");
    $stmt->execute([$ip, $success]);
}

// Aktivite logla
function logActivity($admin_id, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO admin_activity_log (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$admin_id, $action, $details, getIP()]);
}
?> 