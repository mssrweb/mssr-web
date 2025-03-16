<?php
require_once 'includes/config.php';
checkSession();

// Sayfa başlığı
$page_title = "Hizmet Paketleri";

// Paket silme işlemi
if (isset($_POST['delete']) && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $id = (int)$_POST['id'];
    try {
        $stmt = $db->prepare("DELETE FROM service_packages WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($_SESSION['admin_id'], 'package_delete', "Paket silindi (ID: $id)");
            header('Location: packages.php?success=deleted');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Paket silinirken bir hata oluştu!';
    }
}

// Paket durumunu güncelle
if (isset($_POST['toggle_status']) && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $id = (int)$_POST['id'];
    try {
        $stmt = $db->prepare("UPDATE service_packages SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($_SESSION['admin_id'], 'package_status_update', "Paket durumu güncellendi (ID: $id)");
            header('Location: packages.php?success=updated');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Paket durumu güncellenirken bir hata oluştu!';
    }
}

// Öne çıkan paket güncelle
if (isset($_POST['toggle_featured']) && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $id = (int)$_POST['id'];
    try {
        // Önce tüm paketlerin öne çıkarma durumunu false yap
        $stmt = $db->prepare("UPDATE service_packages SET is_featured = FALSE WHERE service_type = (SELECT service_type FROM service_packages WHERE id = ?)");
        $stmt->execute([$id]);
        
        // Seçilen paketi öne çıkar
        $stmt = $db->prepare("UPDATE service_packages SET is_featured = TRUE WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity($_SESSION['admin_id'], 'package_featured_update', "Öne çıkan paket güncellendi (ID: $id)");
        header('Location: packages.php?success=featured');
        exit();
    } catch (PDOException $e) {
        $error = 'Öne çıkan paket güncellenirken bir hata oluştu!';
    }
}

// Paketleri getir
try {
    $stmt = $db->query("SELECT * FROM service_packages ORDER BY service_type, price");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Paketler getirilirken bir hata oluştu!';
    $packages = [];
}

// CSRF token
$csrf_token = generateToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MSSR Web Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
            z-index: 1000;
        }
        .sidebar-logo {
            text-align: center;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .sidebar-logo img {
            max-width: 150px;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-link i {
            width: 25px;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .package-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .package-card:hover {
            transform: translateY(-5px);
        }
        .package-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .package-body {
            padding: 1.5rem;
        }
        .package-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 10px 10px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .badge-featured {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../img/logo.png" alt="MSSR Web Logo">
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link active" href="packages.php">
                <i class="fas fa-box"></i> Hizmet Paketleri
            </a>
            <a class="nav-link" href="quotes.php">
                <i class="fas fa-quote-right"></i> Teklif İstekleri
            </a>
            <a class="nav-link" href="stats.php">
                <i class="fas fa-chart-line"></i> İstatistikler
            </a>
            <a class="nav-link" href="admins.php">
                <i class="fas fa-users-cog"></i> Yöneticiler
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i> Ayarlar
            </a>
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </nav>
    </div>

    <!-- Ana İçerik -->
    <div class="main-content">
        <!-- Üst Bar -->
        <div class="top-bar d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
            <a href="package_edit.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni Paket Ekle
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'deleted':
                        echo 'Paket başarıyla silindi!';
                        break;
                    case 'updated':
                        echo 'Paket durumu başarıyla güncellendi!';
                        break;
                    case 'featured':
                        echo 'Öne çıkan paket başarıyla güncellendi!';
                        break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <!-- Paketler -->
        <?php
        $current_type = '';
        foreach ($packages as $package):
            if ($package['service_type'] !== $current_type):
                if ($current_type !== '') echo '</div>'; // Önceki row'u kapat
                $current_type = $package['service_type'];
                $type_titles = [
                    'web_design' => 'Web Tasarım Paketleri',
                    'web_development' => 'Web Geliştirme Paketleri',
                    'seo' => 'SEO Paketleri'
                ];
        ?>
            <h2 class="h4 mb-4 mt-5"><?php echo $type_titles[$current_type]; ?></h2>
            <div class="row g-4">
        <?php endif; ?>
                
                <div class="col-md-4">
                    <div class="package-card position-relative">
                        <?php if ($package['is_featured']): ?>
                            <div class="badge-featured">
                                <i class="fas fa-star"></i> Öne Çıkan
                            </div>
                        <?php endif; ?>
                        
                        <div class="package-header">
                            <h3 class="h5 mb-3"><?php echo escape($package['package_name']); ?></h3>
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="h2 mb-0"><?php echo number_format($package['price'], 2); ?> ₺</h4>
                                <span class="badge bg-<?php echo $package['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $package['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="package-body">
                            <p class="text-muted mb-4"><?php echo escape($package['description']); ?></p>
                            <ul class="feature-list">
                                <?php foreach (json_decode($package['features']) as $feature): ?>
                                    <li><i class="fas fa-check text-success me-2"></i><?php echo escape($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="package-footer">
                            <div class="btn-group w-100">
                                <a href="package_edit.php?id=<?php echo $package['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i> Düzenle
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Bu paketi silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i> Sil
                                    </button>
                                </form>
                            </div>
                            <div class="btn-group w-100 mt-2">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                    <button type="submit" name="toggle_status" 
                                            class="btn btn-outline-<?php echo $package['is_active'] ? 'warning' : 'success'; ?>">
                                        <i class="fas fa-<?php echo $package['is_active'] ? 'times' : 'check'; ?>"></i>
                                        <?php echo $package['is_active'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                    <button type="submit" name="toggle_featured" 
                                            class="btn btn-outline-primary"
                                            <?php echo $package['is_featured'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-star"></i>
                                        <?php echo $package['is_featured'] ? 'Öne Çıkarıldı' : 'Öne Çıkar'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
        <?php endforeach; ?>
        <?php if ($current_type !== '') echo '</div>'; // Son row'u kapat ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 