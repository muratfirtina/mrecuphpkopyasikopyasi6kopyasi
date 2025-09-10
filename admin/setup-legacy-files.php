<?php
/**
 * Mr ECU - Legacy Files System Setup
 * Eski dosyalar sistemini kurulum dosyası
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
if (isset($_POST['setup_legacy_files'])) {
    try {
        // legacy_files tablosunu oluştur
        $sql = "
        CREATE TABLE IF NOT EXISTS `legacy_files` (
            `id` varchar(36) NOT NULL PRIMARY KEY,
            `user_id` varchar(36) NOT NULL,
            `plate_number` varchar(50) NOT NULL,
            `original_filename` varchar(255) NOT NULL,
            `stored_filename` varchar(255) NOT NULL,
            `file_path` varchar(500) NOT NULL,
            `file_size` bigint NOT NULL,
            `file_type` varchar(100) NOT NULL,
            `uploaded_by_admin` varchar(36) NOT NULL,
            `upload_date` timestamp DEFAULT CURRENT_TIMESTAMP,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_plate_number` (`plate_number`),
            INDEX `idx_uploaded_by` (`uploaded_by_admin`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`uploaded_by_admin`) REFERENCES `users`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        
        // Upload klasörünü oluştur
        $uploadDir = '../uploads/legacy_files/';
        if (!file_exists($uploadDir)) {
            if (mkdir($uploadDir, 0755, true)) {
                $message .= 'Upload klasörü oluşturuldu: ' . $uploadDir . '<br>';
            } else {
                $message .= 'Upload klasörü oluşturulamadı: ' . $uploadDir . '<br>';
                $messageType = 'error';
            }
        } else {
            $message .= 'Upload klasörü zaten mevcut: ' . $uploadDir . '<br>';
        }
        
        $message .= 'Legacy Files sistemi başarıyla kuruldu!';
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
$uploadDirExists = false;
$recordCount = 0;

try {
    // Tablo var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'legacy_files'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Kayıt sayısını al
        $stmt = $pdo->query("SELECT COUNT(*) FROM legacy_files");
        $recordCount = $stmt->fetchColumn();
    }
    
    // Upload klasörü var mı kontrol et
    $uploadDirExists = file_exists('../uploads/legacy_files/') && is_writable('../uploads/legacy_files/');
    
} catch (PDOException $e) {
    // Hata durumunda false kalacak
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legacy Files Setup - Mr ECU</title>
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
                            <i class="bi bi-gear me-2"></i>
                            Legacy Files System Setup
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
                            <div class="col-md-6">
                                <div class="card <?php echo $tableExists ? 'border-success' : 'border-warning'; ?>">
                                    <div class="card-body text-center">
                                        <i class="bi bi-database <?php echo $tableExists ? 'text-success' : 'text-warning'; ?> fa-2x mb-2"></i>
                                        <h6>Veritabanı Tablosu</h6>
                                        <span class="badge bg-<?php echo $tableExists ? 'success' : 'warning'; ?>">
                                            <?php echo $tableExists ? 'Kurulu' : 'Kurulu Değil'; ?>
                                        </span>
                                        <?php if ($tableExists): ?>
                                            <p class="mt-2 mb-0 text-muted"><?php echo $recordCount; ?> kayıt</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card <?php echo $uploadDirExists ? 'border-success' : 'border-warning'; ?>">
                                    <div class="card-body text-center">
                                        <i class="bi bi-folder <?php echo $uploadDirExists ? 'text-success' : 'text-warning'; ?> fa-2x mb-2"></i>
                                        <h6>Upload Klasörü</h6>
                                        <span class="badge bg-<?php echo $uploadDirExists ? 'success' : 'warning'; ?>">
                                            <?php echo $uploadDirExists ? 'Hazır' : 'Hazır Değil'; ?>
                                        </span>
                                        <p class="mt-2 mb-0 text-muted">/uploads/legacy_files/</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kurulum Bilgileri -->
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Legacy Files System Hakkında</h6>
                            <ul class="mb-0">
                                <li>Eski projelerden gelen kullanıcı dosyalarını yönetir</li>
                                <li>Admin panelinden kullanıcılara plaka bazında dosya yüklenebilir</li>
                                <li>Kullanıcılar kendi eski dosyalarını görüntüleyip indirebilir</li>
                                <li>Tüm dosya tiplerini destekler (PDF, resim, Word, Excel vb.)</li>
                                <li>Güvenli dosya erişimi ve log sistemi</li>
                            </ul>
                        </div>

                        <!-- Kurulum Butonu -->
                        <?php if (!$tableExists || !$uploadDirExists): ?>
                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" name="setup_legacy_files" class="btn btn-primary btn-lg">
                                        <i class="bi bi-download me-2"></i>
                                        Legacy Files Sistemini Kur
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-success text-center">
                                <i class="bi bi-check-circle-fill fa-2x mb-2"></i>
                                <h5>Sistem Hazır!</h5>
                                <p class="mb-3">Legacy Files sistemi başarıyla kurulmuş ve kullanıma hazır.</p>
                                
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="legacy-files.php" class="btn btn-success">
                                        <i class="bi bi-folder-open me-1"></i>
                                        Dosya Yönetimine Git
                                    </a>
                                    <a href="../user/legacy-files.php" class="btn btn-info">
                                        <i class="bi bi-eye me-1"></i>
                                        Kullanıcı Görünümü
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Teknik Bilgiler -->
                        <div class="mt-4">
                            <h6>Sistem Gereksinimleri</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>
                                        PHP <?php echo PHP_VERSION; ?>
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>
                                        MySQL Bağlantısı
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">
                                        <i class="bi bi-<?php echo is_writable('../uploads/') ? 'check-circle text-success' : 'x-circle text-danger'; ?> me-1"></i>
                                        Upload İzinleri
                                    </small>
                                </div>
                            </div>
                        </div>

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
