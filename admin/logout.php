<?php
require_once 'includes/config.php';

// Aktiviteyi logla
if (isset($_SESSION['admin_id'])) {
    logActivity($_SESSION['admin_id'], 'logout', 'Başarılı çıkış');
}

// Oturumu sonlandır
session_destroy();

// Çıkış sonrası giriş sayfasına yönlendir
header('Location: login.php');
exit();
?> 