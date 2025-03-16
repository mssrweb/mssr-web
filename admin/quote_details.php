<?php
require_once 'includes/config.php';
checkSession();

// Teklif ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: quotes.php');
    exit();
}

$id = (int)$_GET['id'];

// Teklif bilgilerini getir
try {
    $stmt = $db->prepare("
        SELECT qr.*, sp.service_type, sp.package_name, sp.price, sp.features
        FROM quote_requests qr
        LEFT JOIN service_packages sp ON qr.package_id = sp.id
        WHERE qr.id = ?
    ");
    $stmt->execute([$id]);
    if (!$quote = $stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: quotes.php');
        exit();
    }
} catch (PDOException $e) {
    $error = 'Teklif bilgileri getirilirken bir hata oluştu!';
}

// Durum güncelleme
if (isset($_POST['update_status']) && isset($_POST['status'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $status = clean($_POST['status']);
    try {
        $stmt = $db->prepare("UPDATE quote_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($_SESSION['admin_id'], 'quote_status_update', "Teklif durumu güncellendi (ID: $id, Durum: $status)");
            header('Location: quote_details.php?id=' . $id . '&success=updated');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Teklif durumu güncellenirken bir hata oluştu!';
    }
}

// Not ekleme
if (isset($_POST['add_note']) && !empty($_POST['note'])) {
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $note = clean($_POST['note']);
    try {
        $stmt = $db->prepare("
            INSERT INTO quote_notes (quote_id, admin_id, note) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id, $_SESSION['admin_id'], $note]);
        
        logActivity($_SESSION['admin_id'], 'quote_note_add', "Teklife not eklendi (ID: $id)");
        header('Location: quote_details.php?id=' . $id . '&success=note_added');
        exit();
    } catch (PDOException $e) {
        $error = 'Not eklenirken bir hata oluştu!';
    }
}

// Notları getir
try {
    $stmt = $db->prepare("
        SELECT qn.*, a.username
        FROM quote_notes qn
        LEFT JOIN admins a ON qn.admin_id = a.id
        WHERE qn.quote_id = ?
        ORDER BY qn.created_at DESC
    ");
    $stmt->execute([$id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Notlar getirilirken bir hata oluştu!';
    $notes = [];
}

// Sayfa başlığı
$page_title = "Teklif Detayları: #" . $id;

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
        .detail-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .note-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        <div class="top-bar d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
            <a href="quotes.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Tekliflere Dön
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'updated':
                        echo 'Teklif durumu başarıyla güncellendi!';
                        break;
                    case 'note_added':
                        echo 'Not başarıyla eklendi!';
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

        <div class="row">
            <!-- Teklif Detayları -->
            <div class="col-md-8">
                <div class="detail-card">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h2 class="h4 mb-1">Müşteri Bilgileri</h2>
                            <p class="text-muted mb-0">
                                Teklif tarihi: <?php echo date('d.m.Y H:i', strtotime($quote['created_at'])); ?>
                            </p>
                        </div>
                        <span class="badge bg-<?php echo $status_class[$quote['status']]; ?> fs-6">
                            <?php echo $status_text[$quote['status']]; ?>
                        </span>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <h3 class="h5 mb-3">İletişim Bilgileri</h3>
                            <p class="mb-2">
                                <strong>Ad Soyad:</strong><br>
                                <?php echo escape($quote['client_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>E-posta:</strong><br>
                                <a href="mailto:<?php echo escape($quote['client_email']); ?>">
                                    <?php echo escape($quote['client_email']); ?>
                                </a>
                            </p>
                            <?php if ($quote['client_phone']): ?>
                                <p class="mb-2">
                                    <strong>Telefon:</strong><br>
                                    <a href="tel:<?php echo escape($quote['client_phone']); ?>">
                                        <?php echo escape($quote['client_phone']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($quote['service_type'] && $quote['package_name']): ?>
                            <div class="col-md-6">
                                <h3 class="h5 mb-3">Paket Bilgileri</h3>
                                <p class="mb-2">
                                    <strong>Hizmet Türü:</strong><br>
                                    <?php echo escape($quote['service_type']); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Paket:</strong><br>
                                    <?php echo escape($quote['package_name']); ?>
                                </p>
                                <p class="mb-2">
                                    <strong>Fiyat:</strong><br>
                                    <?php echo number_format($quote['price'], 2); ?> ₺
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($quote['message']): ?>
                            <div class="col-12">
                                <h3 class="h5 mb-3">Müşteri Mesajı</h3>
                                <div class="p-3 bg-light rounded">
                                    <?php echo nl2br(escape($quote['message'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($quote['features']): ?>
                            <div class="col-12">
                                <h3 class="h5 mb-3">Paket Özellikleri</h3>
                                <ul class="feature-list">
                                    <?php foreach (json_decode($quote['features']) as $feature): ?>
                                        <li>
                                            <i class="fas fa-check text-success me-2"></i>
                                            <?php echo escape($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Durum Güncelleme -->
                <div class="detail-card">
                    <h3 class="h5 mb-3">Durum Güncelle</h3>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="col-md-8">
                            <select class="form-select" name="status" required>
                                <?php foreach ($status_text as $key => $text): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $quote['status'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $text; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notlar -->
            <div class="col-md-4">
                <div class="detail-card">
                    <h3 class="h5 mb-3">Not Ekle</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <textarea class="form-control" name="note" rows="3" 
                                      placeholder="Notunuzu buraya yazın..." required></textarea>
                        </div>
                        
                        <button type="submit" name="add_note" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Not Ekle
                        </button>
                    </form>
                </div>

                <?php if (!empty($notes)): ?>
                    <div class="detail-card">
                        <h3 class="h5 mb-3">Notlar</h3>
                        <?php foreach ($notes as $note): ?>
                            <div class="note-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo escape($note['username']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($note['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(escape($note['note'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 