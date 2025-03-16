<?php
require_once 'includes/config.php';
check_admin_session();

$error_message = '';
$success_message = '';

// Filtreleme parametreleri
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$package_filter = $_GET['package'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Durum güncelleme işlemi
if (isset($_POST['update_status']) && isset($_POST['quote_id']) && isset($_POST['new_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Güvenlik doğrulaması başarısız!';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE quote_requests 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_POST['new_status'], $_POST['quote_id']]);
            
            log_activity($_SESSION['admin_id'], 'quote_status_update', 
                "Teklif durumu güncellendi: ID {$_POST['quote_id']} -> {$_POST['new_status']}");
            $success_message = 'Teklif durumu başarıyla güncellendi.';
        } catch(PDOException $e) {
            $error_message = 'Durum güncellenirken bir hata oluştu.';
            error_log("Teklif durum güncelleme hatası: " . $e->getMessage());
        }
    }
}

// Teklif silme işlemi
if (isset($_POST['delete']) && isset($_POST['quote_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Güvenlik doğrulaması başarısız!';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM quote_requests WHERE id = ?");
            $stmt->execute([$_POST['quote_id']]);
            
            log_activity($_SESSION['admin_id'], 'quote_delete', 
                "Teklif silindi: ID " . $_POST['quote_id']);
            $success_message = 'Teklif başarıyla silindi.';
        } catch(PDOException $e) {
            $error_message = 'Teklif silinirken bir hata oluştu.';
            error_log("Teklif silme hatası: " . $e->getMessage());
        }
    }
}

// Sorgu oluşturma
$query = "
    SELECT qr.*, sp.name as package_name, sp.category
    FROM quote_requests qr
    LEFT JOIN service_packages sp ON qr.package_id = sp.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all') {
    $query .= " AND qr.status = ?";
    $params[] = $status_filter;
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $query .= " AND DATE(qr.created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND qr.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND qr.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

if ($package_filter !== 'all') {
    $query .= " AND sp.category = ?";
    $params[] = $package_filter;
}

if ($search_term) {
    $query .= " AND (qr.name LIKE ? OR qr.email LIKE ? OR qr.phone LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY qr.created_at DESC";

// Teklifleri getir
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = 'Teklifler listelenirken bir hata oluştu.';
    error_log("Teklif listeleme hatası: " . $e->getMessage());
    $quotes = [];
}

// Durum seçenekleri
$statuses = [
    'pending' => 'Beklemede',
    'reviewing' => 'İnceleniyor',
    'approved' => 'Onaylandı',
    'rejected' => 'Reddedildi',
    'canceled' => 'İptal Edildi'
];

// Durum badge renkleri
$status_colors = [
    'pending' => 'warning',
    'reviewing' => 'info',
    'approved' => 'success',
    'rejected' => 'danger',
    'canceled' => 'secondary'
];

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
    <title>Teklif Yönetimi - MSSR Web Admin</title>
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
        .quote-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .quote-card .client-info {
            margin-bottom: 1rem;
        }
        .quote-card .package-info {
            color: #666;
            font-size: 0.9rem;
        }
        .quote-card .date-info {
            color: #999;
            font-size: 0.8rem;
        }
        .filters {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
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
            <li><a href="packages.php"><i class="fas fa-box me-2"></i> Paketler</a></li>
            <li><a href="quotes.php" class="active"><i class="fas fa-quote-right me-2"></i> Teklifler</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Çıkış</a></li>
        </ul>
    </nav>

    <!-- Ana İçerik -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Teklif Yönetimi</h1>
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

            <!-- Filtreler -->
            <div class="filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Durum</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all">Tümü</option>
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                        <?php echo $status_filter === $value ? 'selected' : ''; ?>>
                                    <?php echo escape_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="date" class="form-label">Tarih</label>
                        <select class="form-select" id="date" name="date">
                            <option value="all">Tümü</option>
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Bugün</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Son 1 Hafta</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Son 1 Ay</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="package" class="form-label">Kategori</label>
                        <select class="form-select" id="package" name="package">
                            <option value="all">Tümü</option>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                        <?php echo $package_filter === $value ? 'selected' : ''; ?>>
                                    <?php echo escape_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="search" class="form-label">Arama</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo escape_html($search_term); ?>" 
                               placeholder="İsim, e-posta veya telefon">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i> Filtrele
                        </button>
                        <a href="quotes.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i> Temizle
                        </a>
                    </div>
                </form>
            </div>

            <!-- Teklif Listesi -->
            <?php if (empty($quotes)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i> Henüz teklif bulunmuyor.
                </div>
            <?php else: ?>
                <?php foreach ($quotes as $quote): ?>
                    <div class="quote-card">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="client-info">
                                    <h5 class="mb-1"><?php echo escape_html($quote['name']); ?></h5>
                                    <div>
                                        <i class="fas fa-envelope me-2"></i>
                                        <a href="mailto:<?php echo escape_html($quote['email']); ?>">
                                            <?php echo escape_html($quote['email']); ?>
                                        </a>
                                    </div>
                                    <?php if ($quote['phone']): ?>
                                        <div>
                                            <i class="fas fa-phone me-2"></i>
                                            <a href="tel:<?php echo escape_html($quote['phone']); ?>">
                                                <?php echo escape_html($quote['phone']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="package-info">
                                    <div class="mb-2">
                                        <strong>Paket:</strong> 
                                        <?php echo escape_html($quote['package_name'] ?? 'Belirtilmemiş'); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Kategori:</strong> 
                                        <?php echo escape_html($categories[$quote['category']] ?? $quote['category'] ?? 'Belirtilmemiş'); ?>
                                    </div>
                                    <div>
                                        <span class="status-badge bg-<?php echo $status_colors[$quote['status']]; ?>">
                                            <?php echo escape_html($statuses[$quote['status']]); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="date-info">
                                        <div>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($quote['created_at'])); ?>
                                        </div>
                                        <?php if ($quote['updated_at']): ?>
                                            <div>
                                                <i class="fas fa-clock me-1"></i>
                                                Son güncelleme: <?php echo date('d.m.Y H:i', strtotime($quote['updated_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="btn-group">
                                        <a href="quote_details.php?id=<?php echo $quote['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Detaylar">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#statusModal<?php echo $quote['id']; ?>"
                                                title="Durum Güncelle">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Bu teklifi silmek istediğinizden emin misiniz?');">
                                            <input type="hidden" name="csrf_token" 
                                                   value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="quote_id" 
                                                   value="<?php echo $quote['id']; ?>">
                                            <button type="submit" name="delete" 
                                                    class="btn btn-sm btn-danger" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Durum Güncelleme Modal -->
                    <div class="modal fade" id="statusModal<?php echo $quote['id']; ?>" 
                         tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" 
                                           value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="quote_id" 
                                           value="<?php echo $quote['id']; ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title">Durum Güncelle</h5>
                                        <button type="button" class="btn-close" 
                                                data-bs-dismiss="modal" aria-label="Kapat"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="new_status<?php echo $quote['id']; ?>" 
                                                   class="form-label">Yeni Durum</label>
                                            <select class="form-select" 
                                                    id="new_status<?php echo $quote['id']; ?>" 
                                                    name="new_status" required>
                                                <?php foreach ($statuses as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" 
                                                            <?php echo $quote['status'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo escape_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" 
                                                data-bs-dismiss="modal">İptal</button>
                                        <button type="submit" name="update_status" 
                                                class="btn btn-primary">Güncelle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 