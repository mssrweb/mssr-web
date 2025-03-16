<?php
require_once 'includes/config.php';
checkSession();

// İstatistikleri al
$stats = [];

// Toplam teklif istekleri
$stmt = $db->query("SELECT COUNT(*) as total FROM quote_requests");
$stats['total_quotes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Bekleyen teklif istekleri
$stmt = $db->query("SELECT COUNT(*) as pending FROM quote_requests WHERE status = 'pending'");
$stats['pending_quotes'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// Son 30 günlük ziyaretçi sayısı
$stmt = $db->query("SELECT COUNT(DISTINCT visitor_ip) as visitors FROM visitor_stats WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['monthly_visitors'] = $stmt->fetch(PDO::FETCH_ASSOC)['visitors'];

// Son teklif istekleri
$stmt = $db->query("
    SELECT qr.*, sp.service_type, sp.package_name 
    FROM quote_requests qr 
    LEFT JOIN service_packages sp ON qr.package_id = sp.id 
    ORDER BY qr.created_at DESC 
    LIMIT 5
");
$recent_quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ziyaretçi grafiği için son 7 günlük veriler
$stmt = $db->query("
    SELECT DATE(visit_time) as date, COUNT(DISTINCT visitor_ip) as visitors
    FROM visitor_stats 
    WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(visit_time)
    ORDER BY date ASC
");
$visitor_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktivite logları
$stmt = $db->query("
    SELECT al.*, a.username 
    FROM admin_activity_log al
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.created_at DESC 
    LIMIT 5
");
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sayfa başlığı
$page_title = "Dashboard";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MSSR Web Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .recent-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
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
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="packages.php">
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
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <?php echo escape($_SESSION['admin_username']); ?>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-primary text-white">
                        <i class="fas fa-quote-right"></i>
                    </div>
                    <h3 class="h5 mb-2">Toplam Teklif İstekleri</h3>
                    <h2 class="mb-0"><?php echo number_format($stats['total_quotes']); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-warning text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="h5 mb-2">Bekleyen Teklifler</h3>
                    <h2 class="mb-0"><?php echo number_format($stats['pending_quotes']); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-success text-white">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="h5 mb-2">Aylık Ziyaretçiler</h3>
                    <h2 class="mb-0"><?php echo number_format($stats['monthly_visitors']); ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Ziyaretçi Grafiği -->
            <div class="col-md-8">
                <div class="chart-container">
                    <h3 class="h5 mb-4">Ziyaretçi İstatistikleri</h3>
                    <canvas id="visitorChart"></canvas>
                </div>
            </div>

            <!-- Son Aktiviteler -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Son Aktiviteler</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="recent-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo escape($activity['username']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo escape($activity['action']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Son Teklif İstekleri -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Son Teklif İstekleri</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Müşteri</th>
                                        <th>Paket</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_quotes as $quote): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($quote['created_at'])); ?></td>
                                            <td><?php echo escape($quote['client_name']); ?></td>
                                            <td>
                                                <?php 
                                                echo escape($quote['service_type']) . ' - ' . 
                                                     escape($quote['package_name']); 
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'contacted' => 'info',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $status_text = [
                                                    'pending' => 'Bekliyor',
                                                    'contacted' => 'İletişime Geçildi',
                                                    'completed' => 'Tamamlandı',
                                                    'cancelled' => 'İptal Edildi'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $status_class[$quote['status']]; ?>">
                                                    <?php echo $status_text[$quote['status']]; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="quote_details.php?id=<?php echo $quote['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Ziyaretçi grafiği
        const visitorData = <?php echo json_encode($visitor_data); ?>;
        const ctx = document.getElementById('visitorChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: visitorData.map(item => item.date),
                datasets: [{
                    label: 'Günlük Ziyaretçiler',
                    data: visitorData.map(item => item.visitors),
                    fill: true,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 