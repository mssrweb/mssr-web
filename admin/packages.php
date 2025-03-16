<?php
require_once 'includes/config.php';
check_admin_session();

$success_message = '';
$error_message = '';

// Paket silme işlemi
if (isset($_POST['delete']) && isset($_POST['package_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Güvenlik doğrulaması başarısız!';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM service_packages WHERE id = ?");
            $stmt->execute([$_POST['package_id']]);
            
            log_activity($_SESSION['admin_id'], 'package_delete', "Paket silindi: ID " . $_POST['package_id']);
            $success_message = 'Paket başarıyla silindi.';
        } catch(PDOException $e) {
            $error_message = 'Paket silinirken bir hata oluştu.';
            error_log("Paket silme hatası: " . $e->getMessage());
        }
    }
}

// Durum güncelleme işlemi
if (isset($_POST['toggle_status']) && isset($_POST['package_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Güvenlik doğrulaması başarısız!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE service_packages SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$_POST['package_id']]);
            
            log_activity($_SESSION['admin_id'], 'package_status_update', "Paket durumu güncellendi: ID " . $_POST['package_id']);
            $success_message = 'Paket durumu güncellendi.';
        } catch(PDOException $e) {
            $error_message = 'Durum güncellenirken bir hata oluştu.';
            error_log("Paket durum güncelleme hatası: " . $e->getMessage());
        }
    }
}

// Paketleri listele
try {
    $stmt = $pdo->query("
        SELECT * FROM service_packages 
        ORDER BY category, name
    ");
    $packages = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = 'Paketler listelenirken bir hata oluştu.';
    error_log("Paket listeleme hatası: " . $e->getMessage());
    $packages = [];
}

// Kategori isimleri
$categories = [
    'web_design' => 'Web Tasarım',
    'web_development' => 'Web Geliştirme',
    'seo' => 'SEO'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket Yönetimi - MSSR Web Admin</title>
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
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: #2c3e50;
            padding: 1rem;
            color: white;
        }
        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar-menu a.active {
            background: #3498db;
            color: white;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        .package-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .package-card.inactive {
            opacity: 0.7;
        }
        .package-card .category-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            background: #e9ecef;
        }
        .package-card h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .package-card .price {
            font-size: 1.5rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .package-features {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        .package-features li {
            margin-bottom: 0.5rem;
            color: #7f8c8d;
        }
        .package-features li i {
            color: #2ecc71;
            margin-right: 0.5rem;
        }
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">MSSR Web Admin</h4>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a></li>
            <li><a href="packages.php" class="active"><i class="fas fa-box me-2"></i> Paketler</a></li>
            <li><a href="quotes.php"><i class="fas fa-quote-right me-2"></i> Teklifler</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Çıkış</a></li>
        </ul>
    </nav>

    <!-- Ana İçerik -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Paket Yönetimi</h1>
                <a href="package_edit.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Yeni Paket
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo escape_html($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo escape_html($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($packages as $package): ?>
                    <div class="col-md-4">
                        <div class="package-card <?php echo $package['is_active'] ? '' : 'inactive'; ?>">
                            <span class="category-badge">
                                <?php echo escape_html($categories[$package['category']] ?? $package['category']); ?>
                            </span>
                            
                            <h3><?php echo escape_html($package['name']); ?></h3>
                            
                            <div class="price">
                                ₺<?php echo number_format($package['price'], 2, ',', '.'); ?>
                            </div>
                            
                            <?php if ($package['features']): ?>
                                <ul class="package-features">
                                    <?php foreach (json_decode($package['features'], true) as $feature): ?>
                                        <li>
                                            <i class="fas fa-check"></i>
                                            <?php echo escape_html($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="status-badge <?php echo $package['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $package['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                </span>
                                
                                <div class="btn-group">
                                    <a href="package_edit.php?id=<?php echo $package['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bu paketi silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-warning">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <?php if ($package['description']): ?>
                                <div class="text-muted small">
                                    <?php echo escape_html($package['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 