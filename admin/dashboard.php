<?php
require_once 'includes/config.php';
check_admin_session();

// İstatistikleri al
try {
    // Toplam teklif sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM quote_requests");
    $total_quotes = $stmt->fetchColumn();
    
    // Bekleyen teklif sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'pending'");
    $pending_quotes = $stmt->fetchColumn();
    
    // Son 7 günlük teklif sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_quotes = $stmt->fetchColumn();
    
    // Servis paketleri sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM service_packages WHERE is_active = 1");
    $active_packages = $stmt->fetchColumn();
    
    // Son aktiviteler
    $stmt = $pdo->prepare("
        SELECT a.*, admin.username 
        FROM activity_logs a 
        LEFT JOIN admins admin ON a.admin_id = admin.id 
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();
    
    // Son teklifler
    $stmt = $pdo->query("
        SELECT * FROM quote_requests 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_quotes_list = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MSSR Web Admin</title>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .stat-card p {
            color: #7f8c8d;
            margin: 0;
        }
        .stat-card i {
            font-size: 2.5rem;
            color: #3498db;
            opacity: 0.2;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
        }
        .activity-list {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item .time {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .quote-status {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .quote-status.pending { background: #ffeaa7; color: #d35400; }
        .quote-status.contacted { background: #81ecec; color: #00b894; }
        .quote-status.completed { background: #55efc4; color: #00b894; }
        .quote-status.canceled { background: #fab1a0; color: #d63031; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4 class="mb-0">MSSR Web Admin</h4>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home me-2"></i> Dashboard</a></li>
            <li><a href="packages.php"><i class="fas fa-box me-2"></i> Paketler</a></li>
            <li><a href="quotes.php"><i class="fas fa-quote-right me-2"></i> Teklifler</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Çıkış</a></li>
        </ul>
    </nav>

    <!-- Ana İçerik -->
    <main class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Dashboard</h1>
            
            <!-- İstatistik Kartları -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card position-relative">
                        <i class="fas fa-quote-right"></i>
                        <h3><?php echo $total_quotes; ?></h3>
                        <p>Toplam Teklif</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card position-relative">
                        <i class="fas fa-clock"></i>
                        <h3><?php echo $pending_quotes; ?></h3>
                        <p>Bekleyen Teklif</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card position-relative">
                        <i class="fas fa-calendar-alt"></i>
                        <h3><?php echo $recent_quotes; ?></h3>
                        <p>Son 7 Gün</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card position-relative">
                        <i class="fas fa-box"></i>
                        <h3><?php echo $active_packages; ?></h3>
                        <p>Aktif Paket</p>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <!-- Son Teklifler -->
                <div class="col-md-6">
                    <div class="activity-list">
                        <h5 class="mb-4">Son Teklifler</h5>
                        <?php foreach ($recent_quotes_list as $quote): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo escape_html($quote['name']); ?></strong>
                                        <div class="text-muted"><?php echo escape_html($quote['email']); ?></div>
                                    </div>
                                    <span class="quote-status <?php echo $quote['status']; ?>">
                                        <?php echo ucfirst($quote['status']); ?>
                                    </span>
                                </div>
                                <div class="time mt-2">
                                    <?php echo date('d.m.Y H:i', strtotime($quote['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Son Aktiviteler -->
                <div class="col-md-6">
                    <div class="activity-list">
                        <h5 class="mb-4">Son Aktiviteler</h5>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div>
                                    <strong><?php echo escape_html($activity['username'] ?? 'Sistem'); ?></strong>
                                    <span class="text-muted"><?php echo escape_html($activity['action']); ?></span>
                                </div>
                                <?php if ($activity['details']): ?>
                                    <div class="text-muted small"><?php echo escape_html($activity['details']); ?></div>
                                <?php endif; ?>
                                <div class="time">
                                    <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 