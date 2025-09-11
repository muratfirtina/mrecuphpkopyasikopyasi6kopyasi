<?php
/**
 * Mr ECU - Testimonials System Setup
 * Müşteri yorumları sistemini kurulum dosyası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    die('Bu işlem için admin yetkisi gerekiyor.');
}

$message = '';
$messageType = '';

// Setup işlemi
if (isset($_POST['setup_testimonials'])) {
    try {
        // testimonials tablosunu oluştur
        $sql = "
        CREATE TABLE IF NOT EXISTS `testimonials` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(100) NOT NULL,
            `position` varchar(150) NOT NULL,
            `comment` text NOT NULL,
            `rating` tinyint(1) NOT NULL DEFAULT 5,
            `avatar_url` varchar(500) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `display_order` int(11) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL,
            INDEX `idx_active` (`is_active`),
            INDEX `idx_order` (`display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        
        // Örnek veriler ekle
        $stmt = $pdo->prepare("
            INSERT INTO testimonials (name, position, comment, rating, display_order, created_at) VALUES
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?)
        ");
        
        $currentDateTime = date('Y-m-d H:i:s');
        
        $stmt->execute([
            'Mehmet Yılmaz',
            'Mobilya Atölyesi',
            'Zemin koruyucu keçeleri ve yapışkanlı tapalar konusunda piyasadaki en kaliteli ürünleri sundukları rahat ıkla söyleyebilirim. Müşteri memnuniyeti bizim için kritik ve Ecedekor ürünleriyle bunu kolaylıkla sağlıyoruz.',
            5,
            1,
            $currentDateTime,
            
            'Ayşe Kaya',
            'İç Mimar',
            'Ecedekor\'un sunduğu tamir macunları ve yapışkanlı keçeler, projelerimizin hem kalite hem de estetik standartlarını artırdı. Ayrıca müşteri temsilcileri her zaman çok ilgili ve destekleyici.',
            5,
            2,
            $currentDateTime,
            
            'Ahmet Demir',
            'Mobilya Fabrikası',
            'Ecedekor ile çalışmaya başladığımızdan beri üretim süreçlerimiz çok daha sorunsuz ilerliyor. Ürün kalitesi ve teslimat hızı beklentilerimizin çok üzerinde. Her zaman çözüm odaklı bir iş ortağımız oldukları için teşekkür ederiz.',
            5,
            3,
            $currentDateTime
        ]);
        
        $message = 'Testimonials sistemi başarıyla kuruldu! Örnek veriler eklendi.';
        $messageType = 'success';
        
    } catch (PDOException $e) {
        $message = 'Veritabanı hatası: ' . $e->getMessage();
        $messageType = 'error';
    } catch (Exception $e) {
        $message = 'Genel hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Mevcut durum kontrolü
$tableExists = false;
$recordCount = 0;

try {
    // Tablo var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'testimonials'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Kayıt sayısını al
        $stmt = $pdo->query("SELECT COUNT(*) FROM testimonials");
        $recordCount = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    // Hata durumunda false kalacak
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Setup - Mr ECU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-chat-quote me-2"></i>
                            Testimonials System Setup
                        </h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Mevcut Durum -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card <?php echo $tableExists ? 'border-success' : 'border-warning'; ?>">
                                    <div class="card-body text-center">
                                        <i class="bi bi-database <?php echo $tableExists ? 'text-success' : 'text-warning'; ?> fa-2x mb-2"></i>
                                        <h6>Testimonials Tablosu</h6>
                                        <span class="badge bg-<?php echo $tableExists ? 'success' : 'warning'; ?>">
                                            <?php echo $tableExists ? 'Kurulu' : 'Kurulu Değil'; ?>
                                        </span>
                                        <?php if ($tableExists): ?>
                                            <p class="mt-2 mb-0 text-muted"><?php echo $recordCount; ?> yorum kayıtlı</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kurulum Bilgileri -->
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Testimonials System Hakkında</h6>
                            <ul class="mb-0">
                                <li>Müşteri yorumlarını ve değerlendirmelerini yönetir</li>
                                <li>Design panelinden yorum ekleme, düzenleme, silme</li>
                                <li>Ana sayfada otomatik olarak gösterilir</li>
                                <li>5 yıldız rating sistemi</li>
                                <li>Sıralama ve aktif/pasif durumu kontrolü</li>
                                <li>Avatar resmi desteği</li>
                            </ul>
                        </div>

                        <!-- Kurulum Butonu -->
                        <?php if (!$tableExists): ?>
                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" name="setup_testimonials" class="btn btn-primary btn-lg">
                                        <i class="bi bi-download me-2"></i>
                                        Testimonials Sistemini Kur
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle-fill fa-2x mb-2"></i>
                                <h5>Sistem Hazır!</h5>
                                <p class="mb-3">Testimonials sistemi başarıyla kurulmuş ve kullanıma hazır.</p>
                                
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="../design/testimonials.php" class="btn btn-success">
                                        <i class="bi bi-gear me-1"></i>
                                        Testimonials Yönetimine Git
                                    </a>
                                    <a href="../index.php" class="btn btn-info">
                                        <i class="bi bi-eye me-1"></i>
                                        Ana Sayfa Görünümü
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 text-center">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>
                                Admin Panel'e Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
