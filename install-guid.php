<?php
/**
 * MR.ECU Tuning - GUID MySQL Database Installation
 * Otomatik veritabanı kurulum scripti
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Başlık
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MR.ECU Tuning - Database Kurulumu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .install-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .success-icon {
            color: #28a745;
            font-size: 24px;
        }
        .error-icon {
            color: #dc3545;
            font-size: 24px;
        }
        .step-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .step-success {
            border-left: 4px solid #28a745;
        }
        .step-error {
            border-left: 4px solid #dc3545;
        }
        .step-warning {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="card">
            <div class="card-header text-center">
                <h2 class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    MR.ECU Tuning Database Kurulumu
                </h2>
                <p class="mb-0 mt-2">GUID Tabanlı MySQL Veritabanı</p>
            </div>
            <div class="card-body p-4">
<?php

// Config dosyasını yükle
require_once __DIR__ . '/config/database.php';

$installSteps = [];
$hasError = false;

try {
    // Adım 1: Database bağlantısı kontrolü
    $installSteps[] = [
        'title' => 'Database Bağlantısı',
        'status' => 'success',
        'message' => 'Database bağlantısı başarılı ✓'
    ];

    // Adım 2: Tabloları oluştur
    $sqlFile = __DIR__ . '/sql/database_structure.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        
        $installSteps[] = [
            'title' => 'Database Yapısı',
            'status' => 'success',
            'message' => 'Tüm tablolar başarıyla oluşturuldu ✓'
        ];
    } else {
        // Manuel tablo oluşturma
        
        // Users tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id CHAR(36) PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                phone VARCHAR(20),
                role ENUM('admin', 'user', 'design') DEFAULT 'user',
                credits DECIMAL(10,2) DEFAULT 0,
                credit_quota DECIMAL(10,2) DEFAULT 0,
                credit_used DECIMAL(10,2) DEFAULT 0,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                email_verified BOOLEAN DEFAULT FALSE,
                verification_token VARCHAR(100),
                reset_token VARCHAR(100),
                reset_token_expires DATETIME,
                remember_token VARCHAR(100),
                terms_accepted BOOLEAN DEFAULT FALSE,
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_username (username),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Brands tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS brands (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                logo_url VARCHAR(255),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Models tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS models (
                id CHAR(36) PRIMARY KEY,
                brand_id CHAR(36) NOT NULL,
                name VARCHAR(100) NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
                INDEX idx_brand (brand_id),
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Categories tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                description TEXT,
                parent_id CHAR(36),
                image_url VARCHAR(255),
                sort_order INT DEFAULT 0,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
                INDEX idx_slug (slug),
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // File uploads tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS file_uploads (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                brand_id CHAR(36),
                model_id CHAR(36),
                category_id CHAR(36),
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size BIGINT,
                file_type VARCHAR(50),
                device_type VARCHAR(100),
                kilometer VARCHAR(50),
                plate VARCHAR(20),
                type VARCHAR(100),
                motor VARCHAR(100),
                code VARCHAR(50),
                price DECIMAL(10,2) DEFAULT 0,
                status VARCHAR(50) DEFAULT 'pending',
                status_text VARCHAR(100),
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
                FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE SET NULL,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                INDEX idx_user (user_id),
                INDEX idx_brand (brand_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Credit transactions tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS credit_transactions (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                transaction_type ENUM('deposit', 'withdraw', 'file_charge', 'quota_increase', 'usage_remove', 'additional_file_charge') NOT NULL,
                description TEXT,
                reference_id CHAR(36),
                reference_type VARCHAR(50),
                admin_id CHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_type (transaction_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // System logs tablosu
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_logs (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36),
                action VARCHAR(100) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_user (user_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $installSteps[] = [
            'title' => 'Database Yapısı',
            'status' => 'success',
            'message' => 'Tüm tablolar başarıyla oluşturuldu ✓'
        ];
    }

    // Adım 3: Admin kullanıcısı oluştur
    $adminId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    
    if ($checkAdmin == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password, first_name, last_name, role, credits, credit_quota, email_verified, terms_accepted) 
            VALUES (?, 'admin', 'admin@mrecutuning.com', ?, 'Admin', 'User', 'admin', 10000, 10000, 1, 1)
        ");
        $stmt->execute([$adminId, $hashedPassword]);
        
        $installSteps[] = [
            'title' => 'Admin Kullanıcısı',
            'status' => 'success',
            'message' => 'Varsayılan admin hesabı oluşturuldu<br><strong>Email:</strong> admin@mrecutuning.com<br><strong>Şifre:</strong> admin123'
        ];
    } else {
        $installSteps[] = [
            'title' => 'Admin Kullanıcısı',
            'status' => 'warning',
            'message' => 'Admin kullanıcısı zaten mevcut'
        ];
    }

    // Adım 4: Örnek marka ve modeller ekle
    $brandCount = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
    
    if ($brandCount == 0) {
        $brands = [
            'Audi' => ['A3', 'A4', 'A5', 'A6', 'Q3', 'Q5', 'Q7'],
            'BMW' => ['1 Serisi', '3 Serisi', '5 Serisi', 'X1', 'X3', 'X5'],
            'Mercedes' => ['A Class', 'C Class', 'E Class', 'GLA', 'GLC', 'GLE'],
            'Volkswagen' => ['Golf', 'Passat', 'Polo', 'Tiguan', 'Touareg'],
            'Ford' => ['Focus', 'Fiesta', 'Mondeo', 'Kuga', 'Ranger']
        ];

        foreach ($brands as $brandName => $models) {
            $brandId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $stmt = $pdo->prepare("INSERT INTO brands (id, name) VALUES (?, ?)");
            $stmt->execute([$brandId, $brandName]);
            
            foreach ($models as $modelName) {
                $modelId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name) VALUES (?, ?, ?)");
                $stmt->execute([$modelId, $brandId, $modelName]);
            }
        }
        
        $installSteps[] = [
            'title' => 'Örnek Veriler',
            'status' => 'success',
            'message' => 'Örnek marka ve modeller eklendi (5 marka, 34 model) ✓'
        ];
    } else {
        $installSteps[] = [
            'title' => 'Örnek Veriler',
            'status' => 'warning',
            'message' => 'Marka ve modeller zaten mevcut'
        ];
    }

    // Adım 5: Kategoriler ekle
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    
    if ($categoryCount == 0) {
        $categories = [
            'Arıza Tespit Cihazları' => 'ariza-tespit-cihazlari',
            'ECU Programlama' => 'ecu-programlama',
            'Chiptuning' => 'chiptuning',
            'DPF Çözümleri' => 'dpf-cozumleri',
            'Egzoz Sistemleri' => 'egzoz-sistemleri'
        ];

        foreach ($categories as $catName => $catSlug) {
            $categoryId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $stmt = $pdo->prepare("INSERT INTO categories (id, name, slug) VALUES (?, ?, ?)");
            $stmt->execute([$categoryId, $catName, $catSlug]);
        }
        
        $installSteps[] = [
            'title' => 'Kategoriler',
            'status' => 'success',
            'message' => 'Örnek kategoriler eklendi (5 kategori) ✓'
        ];
    } else {
        $installSteps[] = [
            'title' => 'Kategoriler',
            'status' => 'warning',
            'message' => 'Kategoriler zaten mevcut'
        ];
    }

} catch (PDOException $e) {
    $hasError = true;
    $installSteps[] = [
        'title' => 'Kurulum Hatası',
        'status' => 'error',
        'message' => 'Hata: ' . $e->getMessage()
    ];
}

// Adımları göster
foreach ($installSteps as $step) {
    $iconClass = '';
    $stepClass = '';
    
    switch ($step['status']) {
        case 'success':
            $iconClass = 'fa-check-circle success-icon';
            $stepClass = 'step-success';
            break;
        case 'error':
            $iconClass = 'fa-times-circle error-icon';
            $stepClass = 'step-error';
            break;
        case 'warning':
            $iconClass = 'fa-exclamation-triangle';
            $stepClass = 'step-warning';
            break;
    }
    
    echo "<div class='step-item {$stepClass}'>";
    echo "<div class='d-flex align-items-start'>";
    echo "<i class='fas {$iconClass} me-3 mt-1'></i>";
    echo "<div class='flex-grow-1'>";
    echo "<h5 class='mb-1'>{$step['title']}</h5>";
    echo "<p class='mb-0'>{$step['message']}</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

if (!$hasError) {
    echo "
    <div class='alert alert-success mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-check-circle me-2'></i>
            Kurulum Başarılı!
        </h4>
        <p class='mb-0'>MR.ECU Tuning veritabanı başarıyla kuruldu. Artık sistemi kullanmaya başlayabilirsiniz.</p>
    </div>
    
    <div class='d-grid gap-2 mt-4'>
        <a href='login.php' class='btn btn-primary btn-lg'>
            <i class='fas fa-sign-in-alt me-2'></i>
            Giriş Yap
        </a>
        <a href='index.php' class='btn btn-outline-primary btn-lg'>
            <i class='fas fa-home me-2'></i>
            Ana Sayfaya Git
        </a>
    </div>
    ";
} else {
    echo "
    <div class='alert alert-danger mt-4'>
        <h4 class='alert-heading'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Kurulum Hatası
        </h4>
        <p class='mb-0'>Kurulum sırasında hatalar oluştu. Lütfen hataları kontrol edin ve tekrar deneyin.</p>
    </div>
    
    <div class='d-grid gap-2 mt-4'>
        <a href='install-guid.php' class='btn btn-warning btn-lg'>
            <i class='fas fa-redo me-2'></i>
            Tekrar Dene
        </a>
    </div>
    ";
}
?>
            </div>
        </div>

        <div class="text-center mt-4 text-white">
            <p class="mb-1">
                <i class="fas fa-info-circle me-1"></i>
                MR.ECU Tuning v2.0
            </p>
            <p class="mb-0">
                <small>GUID Tabanlı MySQL Veritabanı Sistemi</small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
