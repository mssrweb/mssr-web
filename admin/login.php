<?php
require_once 'includes/config.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || !validateToken($_POST['csrf_token'])) {
        die('CSRF token doğrulama hatası!');
    }

    $username = clean($_POST['username']);
    $password = $_POST['password'];
    $ip = getIP();

    // Giriş denemelerini kontrol et
    $attempts = checkLoginAttempts($ip);
    if ($attempts['attempts'] >= 5) {
        $wait_time = strtotime($attempts['last_attempt']) + 900 - time(); // 15 dakika
        if ($wait_time > 0) {
            $error = 'Çok fazla başarısız giriş denemesi. Lütfen ' . ceil($wait_time / 60) . ' dakika sonra tekrar deneyin.';
            logLoginAttempt($ip, false);
        }
    } else {
        // Kullanıcı bilgilerini kontrol et
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND is_active = TRUE LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && verifyPassword($password, $admin['password'])) {
            // Başarılı giriş
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];

            // Son giriş zamanını güncelle
            $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);

            // Aktiviteyi logla
            logActivity($admin['id'], 'login', 'Başarılı giriş');
            logLoginAttempt($ip, true);

            header('Location: dashboard.php');
            exit();
        } else {
            // Başarısız giriş
            $error = 'Geçersiz kullanıcı adı veya şifre!';
            logLoginAttempt($ip, false);
        }
    }
}

// Yeni CSRF token oluştur
$csrf_token = generateToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - MSSR Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            max-width: 150px;
            margin-bottom: 1rem;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            width: 100%;
            padding: 0.8rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 5px;
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../img/logo.png" alt="MSSR Web Logo">
            <h2>Admin Girişi</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo escape($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Kullanıcı Adı" value="<?php echo escape($username); ?>" required>
                <label for="username">Kullanıcı Adı</label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Şifre" required>
                <label for="password">Şifre</label>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Giriş Yap
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 