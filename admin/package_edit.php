<?php
require_once 'includes/config.php';
check_admin_session();

$error_message = '';
$success_message = '';
$package = [
    'id' => null,
    'name' => '',
    'description' => '',
    'price' => '',
    'features' => [],
    'category' => '',
    'is_active' => true
];

// Düzenleme modunda ise mevcut paketi getir
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM service_packages WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $result = $stmt->fetch();
        
        if ($result) {
            $package = array_merge($package, $result);
            $package['features'] = json_decode($package['features'], true) ?? [];
        } else {
            header('Location: packages.php');
            exit();
        }
    } catch(PDOException $e) {
        $error_message = 'Paket bilgileri alınırken bir hata oluştu.';
        error_log("Paket getirme hatası: " . $e->getMessage());
    }
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Güvenlik doğrulaması başarısız!';
    } else {
        // Form verilerini al
        $package['name'] = $_POST['name'] ?? '';
        $package['description'] = $_POST['description'] ?? '';
        $package['price'] = str_replace(',', '.', $_POST['price'] ?? '');
        $package['category'] = $_POST['category'] ?? '';
        $package['is_active'] = isset($_POST['is_active']);
        $package['features'] = array_filter(array_map('trim', explode("\n", $_POST['features'] ?? '')));
        
        // Validasyon
        $errors = [];
        if (empty($package['name'])) {
            $errors[] = 'Paket adı gereklidir.';
        }
        if (!is_numeric($package['price']) || $package['price'] <= 0) {
            $errors[] = 'Geçerli bir fiyat giriniz.';
        }
        if (empty($package['category'])) {
            $errors[] = 'Kategori seçiniz.';
        }
        if (empty($package['features'])) {
            $errors[] = 'En az bir özellik ekleyiniz.';
        }
        
        if (empty($errors)) {
            try {
                if ($package['id']) {
                    // Güncelleme
                    $stmt = $pdo->prepare("
                        UPDATE service_packages 
                        SET name = ?, description = ?, price = ?, features = ?, category = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $package['name'],
                        $package['description'],
                        $package['price'],
                        json_encode($package['features'], JSON_UNESCAPED_UNICODE),
                        $package['category'],
                        $package['is_active'],
                        $package['id']
                    ]);
                    
                    log_activity($_SESSION['admin_id'], 'package_update', "Paket güncellendi: {$package['name']}");
                    $success_message = 'Paket başarıyla güncellendi.';
                    
                } else {
                    // Yeni ekle
                    $stmt = $pdo->prepare("
                        INSERT INTO service_packages (name, description, price, features, category, is_active)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $package['name'],
                        $package['description'],
                        $package['price'],
                        json_encode($package['features'], JSON_UNESCAPED_UNICODE),
                        $package['category'],
                        $package['is_active']
                    ]);
                    
                    log_activity($_SESSION['admin_id'], 'package_create', "Yeni paket oluşturuldu: {$package['name']}");
                    $success_message = 'Paket başarıyla oluşturuldu.';
                    
                    // Formu temizle
                    $package = [
                        'id' => null,
                        'name' => '',
                        'description' => '',
                        'price' => '',
                        'features' => [],
                        'category' => '',
                        'is_active' => true
                    ];
                }
            } catch(PDOException $e) {
                $error_message = 'Paket kaydedilirken bir hata oluştu.';
                error_log("Paket kaydetme hatası: " . $e->getMessage());
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

// Kategori listesi
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
    <title><?php echo $package['id'] ? 'Paketi Düzenle' : 'Yeni Paket'; ?> - MSSR Web Admin</title>
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
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
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
                <h1><?php echo $package['id'] ? 'Paketi Düzenle' : 'Yeni Paket'; ?></h1>
                <a href="packages.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Geri Dön
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

            <div class="form-card">
                <form method="POST" action="<?php echo escape_html($_SERVER['PHP_SELF'] . ($package['id'] ? "?id={$package['id']}" : '')); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Paket Adı</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo escape_html($package['name']); ?>" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="price" class="form-label">Fiyat (₺)</label>
                            <input type="text" class="form-control" id="price" name="price" 
                                   value="<?php echo escape_html($package['price']); ?>" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="category" class="form-label">Kategori</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo $package['category'] === $value ? 'selected' : ''; ?>>
                                        <?php echo escape_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php 
                            echo escape_html($package['description']); 
                        ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="features" class="form-label">Özellikler</label>
                        <small class="text-muted d-block mb-2">Her satıra bir özellik yazın</small>
                        <textarea class="form-control" id="features" name="features" rows="5" required><?php 
                            echo escape_html(implode("\n", $package['features'])); 
                        ?></textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                   <?php echo $package['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 