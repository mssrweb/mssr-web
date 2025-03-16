<?php
require_once 'includes/config.php';
checkSession();

// Sayfa başlığı
$page_title = "Teklif İstekleri";

// Durum güncelleme
if (isset($_POST['update_status']) && isset($_POST['id']) && isset($_POST['status'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $id = (int)$_POST['id'];
    $status = clean($_POST['status']);
    
    try {
        $stmt = $db->prepare("UPDATE quote_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($_SESSION['admin_id'], 'quote_status_update', "Teklif durumu güncellendi (ID: $id, Durum: $status)");
            header('Location: quotes.php?success=updated');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Teklif durumu güncellenirken bir hata oluştu!';
    }
}

// Teklif silme
if (isset($_POST['delete']) && isset($_POST['id'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $id = (int)$_POST['id'];
    try {
        $stmt = $db->prepare("DELETE FROM quote_requests WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($_SESSION['admin_id'], 'quote_delete', "Teklif silindi (ID: $id)");
            header('Location: quotes.php?success=deleted');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Teklif silinirken bir hata oluştu!';
    }
}

// Filtreleme ve sıralama
$where = [];
$params = [];

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where[] = "qr.status = ?";
    $params[] = clean($_GET['status']);
}

if (isset($_GET['service_type']) && !empty($_GET['service_type'])) {
    $where[] = "sp.service_type = ?";
    $params[] = clean($_GET['service_type']);
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = clean($_GET['search']);
    $where[] = "(qr.client_name LIKE ? OR qr.client_email LIKE ? OR qr.client_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$order_by = "qr.created_at DESC";
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'date_asc':
            $order_by = "qr.created_at ASC";
            break;
        case 'date_desc':
            $order_by = "qr.created_at DESC";
            break;
        case 'name_asc':
            $order_by = "qr.client_name ASC";
            break;
        case 'name_desc':
            $order_by = "qr.client_name DESC";
            break;
    }
}

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Toplam kayıt sayısı
try {
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM quote_requests qr
        LEFT JOIN service_packages sp ON qr.package_id = sp.id
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);
} catch (PDOException $e) {
    $error = 'Kayıt sayısı hesaplanırken bir hata oluştu!';
    $total_records = 0;
    $total_pages = 1;
}

// Teklifleri getir
try {
    $sql = "
        SELECT qr.*, sp.service_type, sp.package_name 
        FROM quote_requests qr
        LEFT JOIN service_packages sp ON qr.package_id = sp.id
        $where_clause
        ORDER BY $order_by
        LIMIT $offset, $per_page
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Teklifler getirilirken bir hata oluştu!';
    $quotes = [];
}

// CSRF token
$csrf_token = generateToken();

// Durum metinleri ve sınıfları
$status_text = [
    'pending' => 'Bekliyor',
    'contacted' => 'İletişime Geçildi',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi'
];

$status_class = [
    'pending' => 'warning',
    'contacted' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];
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
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .sort-link {
            color: inherit;
            text-decoration: none;
        }
        .sort-link:hover {
            color: var(--bs-primary);
        }
        .sort-link i {
            opacity: 0.3;
        }
        .sort-link.active i {
            opacity: 1;
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
            <a class="nav-link" href="packages.php">
                <i class="fas fa-box"></i> Hizmet Paketleri
            </a>
            <a class="nav-link active" href="quotes.php">
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
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <strong>Toplam:</strong> <?php echo number_format($total_records); ?> teklif
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'deleted':
                        echo 'Teklif başarıyla silindi!';
                        break;
                    case 'updated':
                        echo 'Teklif durumu başarıyla güncellendi!';
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

        <!-- Filtreler -->
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tümü</option>
                        <?php foreach ($status_text as $key => $text): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($_GET['status']) && $_GET['status'] === $key ? 'selected' : ''; ?>>
                                <?php echo $text; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="service_type" class="form-label">Hizmet Türü</label>
                    <select class="form-select" id="service_type" name="service_type">
                        <option value="">Tümü</option>
                        <option value="web_design" <?php echo isset($_GET['service_type']) && $_GET['service_type'] === 'web_design' ? 'selected' : ''; ?>>
                            Web Tasarım
                        </option>
                        <option value="web_development" <?php echo isset($_GET['service_type']) && $_GET['service_type'] === 'web_development' ? 'selected' : ''; ?>>
                            Web Geliştirme
                        </option>
                        <option value="seo" <?php echo isset($_GET['service_type']) && $_GET['service_type'] === 'seo' ? 'selected' : ''; ?>>
                            SEO
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Arama</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Müşteri adı, e-posta veya telefon..."
                           value="<?php echo isset($_GET['search']) ? escape($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrele
                    </button>
                </div>
            </form>
        </div>

        <!-- Teklif Listesi -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => isset($_GET['sort']) && $_GET['sort'] === 'date_asc' ? 'date_desc' : 'date_asc'])); ?>" 
                                   class="sort-link <?php echo isset($_GET['sort']) && strpos($_GET['sort'], 'date_') === 0 ? 'active' : ''; ?>">
                                    Tarih
                                    <i class="fas fa-sort<?php echo isset($_GET['sort']) ? ($_GET['sort'] === 'date_asc' ? '-up' : '-down') : ''; ?>"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => isset($_GET['sort']) && $_GET['sort'] === 'name_asc' ? 'name_desc' : 'name_asc'])); ?>" 
                                   class="sort-link <?php echo isset($_GET['sort']) && strpos($_GET['sort'], 'name_') === 0 ? 'active' : ''; ?>">
                                    Müşteri
                                    <i class="fas fa-sort<?php echo isset($_GET['sort']) ? ($_GET['sort'] === 'name_asc' ? '-up' : '-down') : ''; ?>"></i>
                                </a>
                            </th>
                            <th>İletişim</th>
                            <th>Paket</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($quote['created_at'])); ?></td>
                                <td><?php echo escape($quote['client_name']); ?></td>
                                <td>
                                    <div><?php echo escape($quote['client_email']); ?></div>
                                    <?php if ($quote['client_phone']): ?>
                                        <div class="text-muted"><?php echo escape($quote['client_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($quote['service_type'] && $quote['package_name']): ?>
                                        <div><?php echo escape($quote['service_type']); ?></div>
                                        <div class="text-muted"><?php echo escape($quote['package_name']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">Paket silinmiş</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class[$quote['status']]; ?>">
                                        <?php echo $status_text[$quote['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Durum Güncelle
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php foreach ($status_text as $key => $text): ?>
                                                <?php if ($key !== $quote['status']): ?>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $key; ?>">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <?php echo $text; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <a href="quote_details.php?id=<?php echo $quote['id']; ?>" 
                                       class="btn btn-sm btn-outline-info ms-1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Bu teklifi silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="id" value="<?php echo $quote['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-outline-danger ms-1">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($quotes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x mb-3 text-muted"></i>
                                    <p class="mb-0 text-muted">Henüz teklif isteği bulunmuyor.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sayfalama -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfalama" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 