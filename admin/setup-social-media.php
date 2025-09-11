<?php
/**
 * Mr ECU - Social Media Links Setup
 * Sosyal medya linklerini kurulum dosyası
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
if (isset($_POST['setup_social_media'])) {
    try {
        // social_media_links tablosunu oluştur
        $sql = "
        CREATE TABLE IF NOT EXISTS `social_media_links` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(50) NOT NULL,
            `icon` varchar(50) NOT NULL,
            `url` varchar(500) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `display_order` int(11) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL,
            INDEX `idx_active` (`is_active`),
            INDEX `idx_order` (`display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        
        // Varsayılan sosyal medya platformlarını ekle
        $stmt = $pdo->prepare("
            INSERT INTO social_media_links (name, icon, url, display_order, created_at, updated_at) VALUES
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?),
            (?, ?, ?, ?, ?, ?)
        ");
        
        $currentDateTime = date('Y-m-d H:i:s');
        
        $stmt->execute([
            'Facebook', 'bi-facebook', '', 1, $currentDateTime, $currentDateTime,
            'Instagram', 'bi-instagram', '', 2, $currentDateTime, $currentDateTime,
            'LinkedIn', 'bi-linkedin', '', 3, $currentDateTime, $currentDateTime,
            'Twitter', 'bi-twitter', '', 4, $currentDateTime, $currentDateTime,
            'YouTube', 'bi-youtube', '', 5, $currentDateTime, $currentDateTime,
            'WhatsApp', 'bi-whatsapp', '', 6, $currentDateTime, $currentDateTime
        ]);
        
        $message = 'Sosyal medya linkleri sistemi başarıyla kuruldu! Varsayılan platformlar eklendi.';
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
    $stmt = $pdo->query("SHOW TABLES LIKE 'social_media_links'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Kayıt sayısını al
        $stmt = $pdo->query("SELECT COUNT(*) FROM social_media_links");
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
    <title>Social Media Links Setup - Mr ECU</title>
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
                            <i class="bi bi-share me-2"></i>
                            Social Media Links Setup
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
                                        <h6>Social Media Links Tablosu</h6>
                                        <span class="badge bg-<?php echo $tableExists ? 'success' : 'warning'; ?>">
                                            <?php echo $tableExists ? 'Kurulu' : 'Kurulu Değil'; ?>
                                        </span>
                                        <?php if ($tableExists): ?>
                                            <p class="mt-2 mb-0 text-muted"><?php echo $recordCount; ?> sosyal medya platformu kayıtlı</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kurulum Bilgileri -->
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Social Media Links System Hakkında</h6>
                            <ul class="mb-0">
                                <li>Footer'daki sosyal medya linklerini yönetir</li>
                                <li>Design panelinden link ekleme, düzenleme, silme</li>
                                <li>Facebook, Instagram, LinkedIn, Twitter, YouTube, WhatsApp desteği</li>
                                <li>Bootstrap Icons ile ikon desteği</li>
                                <li>Sıralama ve aktif/pasif durumu kontrolü</li>
                                <li>Responsive footer tasarımı</li>
                            </ul>
                        </div>

                        <!-- Kurulum Butonu -->
                        <?php if (!$tableExists): ?>
                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" name="setup_social_media" class="btn btn-primary btn-lg">
                                        <i class="bi bi-download me-2"></i>
                                        Social Media Links Sistemini Kur
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle-fill fa-2x mb-2"></i>
                                <h5>Sistem Hazır!</h5>
                                <p class="mb-3">Social Media Links sistemi başarıyla kurulmuş ve kullanıma hazır.</p>
                                
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="../design/footer.php" class="btn btn-success">
                                        <i class="bi bi-gear me-1"></i>
                                        Footer Yönetimine Git
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
