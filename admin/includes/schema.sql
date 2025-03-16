-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS mssr_admin DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mssr_admin;

-- Yöneticiler tablosu
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Hizmet paketleri tablosu
CREATE TABLE IF NOT EXISTS service_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_type ENUM('web_design', 'web_development', 'seo') NOT NULL,
    package_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    features JSON NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Teklif istekleri tablosu
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100) NOT NULL,
    client_phone VARCHAR(20),
    message TEXT,
    status ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES service_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Teklif notları tablosu
CREATE TABLE quote_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    admin_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quote_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ziyaretçi istatistikleri tablosu
CREATE TABLE IF NOT EXISTS visitor_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(255) NOT NULL,
    visitor_ip VARCHAR(45) NOT NULL,
    user_agent TEXT,
    referrer VARCHAR(255),
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visit_time (visit_time)
) ENGINE=InnoDB;

-- Giriş denemeleri tablosu
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB;

-- Yönetici aktivite logu tablosu
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Varsayılan süper admin kullanıcısı oluştur (şifre: Admin123!)
INSERT INTO admins (username, password, email, full_name, role) VALUES 
('admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN.jf.OGWcxYBaQB4.KAi', 'admin@example.com', 'System Administrator', 'super_admin');

-- Örnek hizmet paketleri
INSERT INTO service_packages (service_type, package_name, price, description, features, is_featured) VALUES
('web_design', 'Basic', 1000.00, 'Temel web tasarım paketi', '["Responsive Tasarım", "5 Sayfa", "İletişim Formu", "Sosyal Medya Entegrasyonu"]', FALSE),
('web_design', 'Standard', 3000.00, 'Standart web tasarım paketi', '["Responsive Tasarım", "10 Sayfa", "İletişim Formu", "Sosyal Medya Entegrasyonu", "SEO Optimizasyonu", "Blog Sayfası"]', TRUE),
('web_design', 'Premium', 6000.00, 'Premium web tasarım paketi', '["Responsive Tasarım", "Sınırsız Sayfa", "İletişim Formu", "Sosyal Medya Entegrasyonu", "SEO Optimizasyonu", "Blog Sayfası", "E-posta Pazarlama", "Analytics Entegrasyonu"]', FALSE),

('web_development', 'Basic', 1500.00, 'Temel web geliştirme paketi', '["PHP/MySQL", "Temel CMS", "Responsive Tasarım", "İletişim Formu"]', FALSE),
('web_development', 'Standard', 4000.00, 'Standart web geliştirme paketi', '["PHP/MySQL", "Özel CMS", "Responsive Tasarım", "İletişim Formu", "E-ticaret Entegrasyonu", "API Entegrasyonu"]', TRUE),
('web_development', 'Premium', 8000.00, 'Premium web geliştirme paketi', '["PHP/MySQL", "Özel CMS", "Responsive Tasarım", "İletişim Formu", "E-ticaret Entegrasyonu", "API Entegrasyonu", "Özel Modüller", "Performans Optimizasyonu"]', FALSE),

('seo', 'Basic', 1000.00, 'Temel SEO paketi', '["Anahtar Kelime Analizi", "On-Page SEO", "Temel Raporlama", "Aylık İzleme"]', FALSE),
('seo', 'Standard', 3000.00, 'Standart SEO paketi', '["Anahtar Kelime Analizi", "On-Page SEO", "Off-Page SEO", "İçerik Optimizasyonu", "Detaylı Raporlama", "Aylık İzleme"]', TRUE),
('seo', 'Premium', 5000.00, 'Premium SEO paketi', '["Anahtar Kelime Analizi", "On-Page SEO", "Off-Page SEO", "İçerik Optimizasyonu", "Detaylı Raporlama", "Haftalık İzleme", "Rakip Analizi", "Sosyal Medya Optimizasyonu"]', FALSE); 