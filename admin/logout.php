<?php
require_once 'includes/config.php';

// Oturum açık mı kontrol et
if (isset($_SESSION['admin_id'])) {
    // Aktiviteyi logla
    log_activity($_SESSION['admin_id'], 'logout', 'Başarılı çıkış');
    
    // Oturumu temizle
    session_unset();
    session_destroy();
}

// Giriş sayfasına yönlendir
header('Location: login.php');
exit();
?> 