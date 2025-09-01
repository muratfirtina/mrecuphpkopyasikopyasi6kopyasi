<?php
/**
 * Services System Installation Script
 * Bu script services tablosunu oluşturur ve örnek verileri ekler
 */

require_once 'config/config.php';
require_once 'config/database.php';

// HTML başlığı
echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Services System Kurulumu - " . SITE_NAME . "</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body style='background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);'>
<div class='container my-5'>
    <div class='row justify-content-center'>
        <div class='col-lg-8'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h3 class='mb-0'><i class='bi bi-concierge-bell me-2'></i>Services System Kurulumu</h3>
                </div>
                <div class='card-body'>
";

$success = true;
$messages = [];

try {
    // Services tablosunu oluştur
    $sql = "
    CREATE TABLE IF NOT EXISTS `services` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(100) NOT NULL,
        `description` text NOT NULL,
        `features` json DEFAULT NULL,
        `image` varchar(255) DEFAULT NULL,
        `icon` varchar(50) DEFAULT NULL,
        `price_from` decimal(10,2) DEFAULT NULL,
        `is_featured` tinyint(1) DEFAULT 0,
        `status` enum('active','inactive') DEFAULT 'active',
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`),
        KEY `idx_status` (`status`),
        KEY `idx_featured` (`is_featured`),
        KEY `idx_sort_order` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'message' => 'Services tablosu başarıyla oluşturuldu.'];
    
    // Service contacts tablosunu oluştur
    $sql = "
    CREATE TABLE IF NOT EXISTS `service_contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `service_id` int(11) NOT NULL,
        `service_name` varchar(255) NOT NULL,
        `contact_name` varchar(100) NOT NULL,
        `contact_email` varchar(150) NOT NULL,
        `contact_phone` varchar(20) DEFAULT NULL,
        `contact_company` varchar(100) DEFAULT NULL,
        `contact_message` text NOT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `status` enum('new','read','replied') DEFAULT 'new',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_service_id` (`service_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    $messages[] = ['type' => 'success', 'message' => 'Service contacts tablosu başarıyla oluşturuldu.'];
    
    // Mevcut services sayısını kontrol et
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    $existingCount = $stmt->fetchColumn();
    
    if ($existingCount == 0) {
        // Örnek hizmet verilerini ekle
        $services = [
            [
                'name' => 'ECU Yazılım Optimizasyonu',
                'slug' => 'ecu-yazilim-optimizasyonu',
                'description' => 'Motor kontrol ünitesi yazılımlarınızı profesyonelce optimize ediyoruz. Performans artışı, yakıt tasarrufu ve motor verimliliği sağlıyoruz.',
                'features' => json_encode(['Performans artışı', 'Yakıt tasarrufu', 'Motor verimliliği', 'Profesyonel yazılım', 'Güvenli optimizasyon']),
                'icon' => 'bi bi-cpu',
                'price_from' => 150.00,
                'is_featured' => 1,
                'sort_order' => 1
            ],
            [
                'name' => 'Hız Limiti İptali',
                'slug' => 'hiz-limiti-iptali',
                'description' => 'Araçlarda elektronik hız limiti kısıtlamalarını güvenli şekilde kaldırıyoruz. Tam performans potansiyelinizi açığa çıkarın.',
                'features' => json_encode(['Elektronik limit kaldırma', 'Güvenli işlem', 'Tam performans', 'Profesyonel destek', 'Hızlı teslimat']),
                'icon' => 'bi bi-tachometer-alt',
                'price_from' => 200.00,
                'is_featured' => 1,
                'sort_order' => 2
            ],
            [
                'name' => 'DPF/EGR İptali',
                'slug' => 'dpf-egr-iptali',
                'description' => 'Dizel araçlarda DPF (partikül filtresi) ve EGR (egzoz gazı resirkülasyonu) sistemlerini güvenli şekilde iptal ediyoruz.',
                'features' => json_encode(['DPF sistemi iptali', 'EGR sistem iptali', 'Motor ömrü uzatma', 'Yakıt tasarrufu', 'Emisyon çözümü']),
                'icon' => 'bi bi-filter',
                'price_from' => 300.00,
                'is_featured' => 1,
                'sort_order' => 3
            ],
            [
                'name' => 'Immobilizer İptali',
                'slug' => 'immobilizer-iptali',
                'description' => 'Arızalı immobilizer sistemlerini güvenli şekilde devre dışı bırakıyoruz. Aracınızı sorunsuz çalıştırın.',
                'features' => json_encode(['İmmobilizer devre dışı', 'Anahtar sorunu çözümü', 'Start sorunu çözümü', 'Güvenli işlem', '7/24 destek']),
                'icon' => 'bi bi-key',
                'price_from' => 100.00,
                'is_featured' => 0,
                'sort_order' => 4
            ],
            [
                'name' => 'TCU Yazılım Optimizasyonu',
                'slug' => 'tcu-yazilim-optimizasyonu',
                'description' => 'Şanzıman kontrol ünitesi yazılımlarını optimize ediyoruz. Daha yumuşak vites geçişleri ve performans artışı sağlıyoruz.',
                'features' => json_encode(['Vites geçiş optimizasyonu', 'Şanzıman performansı', 'Yumuşak çalışma', 'Yakıt ekonomisi', 'Uzun ömür']),
                'icon' => 'bi bi-gear-wide-connected',
                'price_from' => 250.00,
                'is_featured' => 0,
                'sort_order' => 5
            ],
            [
                'name' => 'AdBlue İptali',
                'slug' => 'adblue-iptali',
                'description' => 'AdBlue sistemi sorunlarını kalıcı olarak çözüyoruz. Sistem arızalarından kurtulun.',
                'features' => json_encode(['AdBlue sistem iptali', 'Arıza lambaları çözümü', 'Kalıcı çözüm', 'Maliyet tasarrufu', 'Profesyonel hizmet']),
                'icon' => 'bi bi-tint',
                'price_from' => 180.00,
                'is_featured' => 0,
                'sort_order' => 6
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO services (name, slug, description, features, icon, price_from, is_featured, status, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
        ");
        
        foreach ($services as $service) {
            $stmt->execute([
                $service['name'],
                $service['slug'],
                $service['description'],
                $service['features'],
                $service['icon'],
                $service['price_from'],
                $service['is_featured'],
                $service['sort_order']
            ]);
        }
        
        $messages[] = ['type' => 'success', 'message' => count($services) . ' adet örnek hizmet başarıyla eklendi.'];
    } else {
        $messages[] = ['type' => 'info', 'message' => "Zaten $existingCount adet hizmet mevcut. Örnek veriler eklenmedi."];
    }
    
    // Design panelinde admin kullanıcısının services erişimini kontrol et
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `permission` varchar(50) NOT NULL,
                `granted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_permission` (`user_id`, `permission`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = ['type' => 'success', 'message' => 'User permissions tablosu hazır.'];
    } catch (Exception $e) {
        $messages[] = ['type' => 'warning', 'message' => 'User permissions tablosu oluşturulamadı: ' . $e->getMessage()];
    }
    
} catch (Exception $e) {
    $success = false;
    $messages[] = ['type' => 'danger', 'message' => 'Hata oluştu: ' . $e->getMessage()];
}

// Sonuçları göster
foreach ($messages as $msg) {
    $alertClass = 'alert-' . $msg['type'];
    $icon = match($msg['type']) {
        'success' => 'bi bi-check-circle',
        'danger' => 'bi bi-exclamation-triangle',
        'warning' => 'bi bi-exclamation-circle',
        'info' => 'bi bi-info-circle',
        default => 'bi bi-info-circle'
    };
    
    echo "<div class='alert $alertClass' role='alert'>";
    echo "<i class='$icon me-2'></i>";
    echo htmlspecialchars($msg['message']);
    echo "</div>";
}

if ($success) {
    echo "
    <div class='alert alert-success' role='alert'>
        <h5><i class='bi bi-check-circle me-2'></i>Kurulum Tamamlandı!</h5>
        <p class='mb-0'>Services sistemi başarıyla kuruldu. Artık aşağıdaki özellikleri kullanabilirsiniz:</p>
        <ul class='mt-2 mb-0'>
            <li>Ana sayfada dinamik hizmetler bölümü</li>
            <li>Hizmet detay sayfaları (/hizmet/slug formatında)</li>
            <li>Design panelinde hizmet yönetimi</li>
            <li>Hizmet iletişim formları</li>
        </ul>
    </div>
    
    <div class='d-grid gap-2 d-md-flex justify-content-md-center'>
        <a href='index.php' class='btn btn-primary'>
            <i class='bi bi-home me-2'></i>Ana Sayfaya Git
        </a>
        <a href='services.php' class='btn btn-info'>
            <i class='bi bi-list me-2'></i>Hizmetleri Görüntüle
        </a>
        <a href='design/' class='btn btn-secondary'>
            <i class='bi bi-cog me-2'></i>Design Panel
        </a>
    </div>
    ";
} else {
    echo "
    <div class='alert alert-danger' role='alert'>
        <h5><i class='bi bi-exclamation-triangle me-2'></i>Kurulum Hatası!</h5>
        <p class='mb-0'>Services sistemi kurulumu sırasında hata oluştu. Lütfen hataları düzeltip tekrar deneyin.</p>
    </div>
    ";
}

echo "
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>";
?>
