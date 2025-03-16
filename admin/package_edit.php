<?php
require_once 'includes/config.php';
checkSession();

// Sayfa başlığı
$page_title = "Paket Düzenle";
$is_edit = false;
$package = [
    'id' => '',
    'service_type' => '',
    'package_name' => '',
    'price' => '',
    'description' => '',
    'features' => '[]',
    'is_featured' => false,
    'is_active' => true
];

// Paket ID'si varsa mevcut paketi getir
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("SELECT * FROM service_packages WHERE id = ?");
        $stmt->execute([$id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $package = $row;
            $is_edit = true;
            $page_title = "Paket Düzenle: " . $package['package_name'];
        }
    } catch (PDOException $e) {
        $error = 'Paket bilgileri getirilirken bir hata oluştu!';
    }
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    // Form verilerini al
    $package['service_type'] = clean($_POST['service_type']);
    $package['package_name'] = clean($_POST['package_name']);
    $package['price'] = (float)$_POST['price'];
    $package['description'] = clean($_POST['description']);
    $package['features'] = json_encode(array_map('clean', explode("\n", trim($_POST['features']))));
    $package['is_featured'] = isset($_POST['is_featured']);
    $package['is_active'] = isset($_POST['is_active']);

    // Validasyon
    $errors = [];
    if (empty($package['package_name'])) {
        $errors[] = 'Paket adı boş bırakılamaz!';
    }
    if ($package['price'] <= 0) {
        $errors[] = 'Geçerli bir fiyat giriniz!';
    }
    if (empty($package['description'])) {
        $errors[] = 'Paket açıklaması boş bırakılamaz!';
    }
    if (empty($_POST['features'])) {
        $errors[] = 'En az bir özellik giriniz!';
    }

    // Hata yoksa kaydet
    if (empty($errors)) {
        try {
            if ($is_edit) {
                // Mevcut paketi güncelle
                $stmt = $db->prepare("
                    UPDATE service_packages 
                    SET service_type = ?, package_name = ?, price = ?, description = ?, 
                        features = ?, is_featured = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $package['service_type'],
                    $package['package_name'],
                    $package['price'],
                    $package['description'],
                    $package['features'],
                    $package['is_featured'],
                    $package['is_active'],
                    $package['id']
                ]);

                logActivity($_SESSION['admin_id'], 'package_update', "Paket güncellendi (ID: {$package['id']})");
            } else {
                // Yeni paket ekle
                $stmt = $db->prepare("
                    INSERT INTO service_packages 
                    (service_type, package_name, price, description, features, is_featured, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $package['service_type'],
                    $package['package_name'],
                    $package['price'],
                    $package['description'],
                    $package['features'],
                    $package['is_featured'],
                    $package['is_active']
                ]);

                $package['id'] = $db->lastInsertId();
                logActivity($_SESSION['admin_id'], 'package_create', "Yeni paket oluşturuldu (ID: {$package['id']})");
            }

            // Başarılı mesajıyla listele sayfasına yönlendir
            header('Location: packages.php?success=' . ($is_edit ? 'updated' : 'created'));
            exit();
        } catch (PDOException $e) {
            $error = 'Paket kaydedilirken bir hata oluştu!';
        }
    }
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
        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
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
            <a href="packages.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Paketlere Dön
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] . ($is_edit ? "?id={$package['id']}" : ''); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="service_type" class="form-label">Hizmet Türü</label>
                            <select class="form-select" id="service_type" name="service_type" required>
                                <option value="web_design" <?php echo $package['service_type'] === 'web_design' ? 'selected' : ''; ?>>
                                    Web Tasarım
                                </option>
                                <option value="web_development" <?php echo $package['service_type'] === 'web_development' ? 'selected' : ''; ?>>
                                    Web Geliştirme
                                </option>
                                <option value="seo" <?php echo $package['service_type'] === 'seo' ? 'selected' : ''; ?>>
                                    SEO
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="package_name" class="form-label">Paket Adı</label>
                            <input type="text" class="form-control" id="package_name" name="package_name" 
                                   value="<?php echo escape($package['package_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Fiyat (₺)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo $package['price']; ?>" step="0.01" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" required><?php echo escape($package['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="features" class="form-label">Özellikler (Her satıra bir özellik)</label>
                            <textarea class="form-control" id="features" name="features" rows="10" required><?php 
                                $features = json_decode($package['features']);
                                echo escape(implode("\n", $features));
                            ?></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                                       <?php echo $package['is_featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">Öne Çıkan Paket</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                       <?php echo $package['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="packages.php" class="btn btn-outline-secondary me-2">İptal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 