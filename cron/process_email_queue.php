<?php
/**
 * Mr ECU - Email Queue Processor
 * Bu script email kuyruğundaki pending email'leri gönderir
 * 
 * Kullanım:
 * 1. Manuel: php /path/to/process_email_queue.php
 * 2. Cron: * * * * * /usr/bin/php /path/to/process_email_queue.php >> /path/to/logs/email_queue.log 2>&1
 */

// Script başlangıç zamanı
$startTime = microtime(true);
$processedCount = 0;
$errorCount = 0;

echo "[" . date('Y-m-d H:i:s') . "] Email Queue Processor başlatıldı\n";

// CLI modunda çalışıyor olup olmadığını kontrol et
if (php_sapi_name() !== 'cli') {
    die("Bu script sadece CLI modunda çalıştırılabilir.\n");
}

// Proje kök dizinini belirle (script'in konumuna göre)
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Gerekli dosyaları dahil et
require_once $projectRoot . '/config/config.php';
require_once $projectRoot . '/config/database.php';
require_once $projectRoot . '/includes/EmailManager.php';

try {
    // EmailManager'ı başlat
    $emailManager = new EmailManager($pdo);
    
    // İşlemde olan veya çok eski pending email'leri temizle
    cleanupStaleEmails($pdo);
    
    // Bekleyen email'leri getir (öncelik sırasına göre)
    $pendingEmails = getPendingEmails($pdo);
    
    if (empty($pendingEmails)) {
        echo "[" . date('Y-m-d H:i:s') . "] Bekleyen email bulunamadı\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] " . count($pendingEmails) . " bekleyen email bulundu\n";
        
        foreach ($pendingEmails as $email) {
            try {
                // Email'i işleme alma durumunu işaretle (race condition önlemek için)
                if (!lockEmailForProcessing($pdo, $email['id'])) {
                    echo "[" . date('Y-m-d H:i:s') . "] Email zaten işleniyor: " . $email['id'] . "\n";
                    continue;
                }
                
                echo "[" . date('Y-m-d H:i:s') . "] Email gönderiliyor: " . $email['to_email'] . " - " . $email['subject'] . "\n";
                
                // Email gönderme
                $result = $emailManager->sendQueuedEmail($email);
                
                if ($result) {
                    // Başarılı gönderim
                    markEmailAsSent($pdo, $email['id']);
                    $processedCount++;
                    echo "[" . date('Y-m-d H:i:s') . "] ✓ Email başarıyla gönderildi: " . $email['to_email'] . "\n";
                } else {
                    // Başarısız gönderim
                    $attempts = ($email['attempts'] ?? 0) + 1;
                    
                    if ($attempts >= ($email['max_attempts'] ?? 3)) {
                        markEmailAsFailed($pdo, $email['id'], 'Maximum attempts reached', $attempts);
                        echo "[" . date('Y-m-d H:i:s') . "] ✗ Email başarısız (maksimum deneme): " . $email['to_email'] . "\n";
                    } else {
                        incrementEmailAttempts($pdo, $email['id'], $attempts);
                        echo "[" . date('Y-m-d H:i:s') . "] ⚠ Email başarısız (deneme " . $attempts . "): " . $email['to_email'] . "\n";
                    }
                    $errorCount++;
                }
                
                // İşlemler arası kısa bekleme (SMTP rate limit için)
                usleep(500000); // 0.5 saniye
                
            } catch (Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] ✗ Email gönderim hatası: " . $e->getMessage() . "\n";
                markEmailAsFailed($pdo, $email['id'], $e->getMessage(), ($email['attempts'] ?? 0) + 1);
                $errorCount++;
            }
        }
    }
    
    // İstatistik güncelle
    updateEmailStatistics($pdo, $processedCount, $errorCount);
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Kritik hata: " . $e->getMessage() . "\n";
    error_log("Email Queue Processor kritik hata: " . $e->getMessage());
}

// Özet rapor
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "[" . date('Y-m-d H:i:s') . "] Email Queue Processor tamamlandı\n";
echo "Toplam işlenen: $processedCount\n";
echo "Toplam hata: $errorCount\n";
echo "Çalışma süresi: {$executionTime} saniye\n";
echo "----------------------------------------\n";

/**
 * Bekleyen email'leri getir
 */
function getPendingEmails($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'pending' 
            AND (processing_started_at IS NULL OR processing_started_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
            ORDER BY 
                CASE priority 
                    WHEN 'high' THEN 1 
                    WHEN 'normal' THEN 2 
                    WHEN 'low' THEN 3 
                    ELSE 2 
                END, 
                created_at ASC
            LIMIT 50
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Bekleyen email'ler alınamadı: " . $e->getMessage() . "\n";
        return [];
    }
}

/**
 * Email'i işleme alma için kilitle
 */
function lockEmailForProcessing($pdo, $emailId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET processing_started_at = NOW() 
            WHERE id = ? 
            AND status = 'pending' 
            AND (processing_started_at IS NULL OR processing_started_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
        ");
        $stmt->execute([$emailId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Email kilitlenemedi: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Email'i gönderildi olarak işaretle
 */
function markEmailAsSent($pdo, $emailId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET status = 'sent', sent_at = NOW(), processing_started_at = NULL, error_message = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$emailId]);
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Email durumu güncellenemedi: " . $e->getMessage() . "\n";
    }
}

/**
 * Email'i başarısız olarak işaretle
 */
function markEmailAsFailed($pdo, $emailId, $errorMessage, $attempts) {
    try {
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET status = 'failed', attempts = ?, error_message = ?, processing_started_at = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $errorMessage, $emailId]);
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Email hata durumu güncellenemedi: " . $e->getMessage() . "\n";
    }
}

/**
 * Email deneme sayısını artır
 */
function incrementEmailAttempts($pdo, $emailId, $attempts) {
    try {
        // Exponential backoff için sonraki denemeyi geciktir
        $nextAttemptDelay = pow(2, $attempts) * 5; // 10, 20, 40 dakika
        
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET attempts = ?, processing_started_at = NULL, 
                next_attempt_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $nextAttemptDelay, $emailId]);
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Email deneme sayısı güncellenemedi: " . $e->getMessage() . "\n";
    }
}

/**
 * Eski/takılan email'leri temizle
 */
function cleanupStaleEmails($pdo) {
    try {
        // 30 dakikadan fazla işlemde olan email'leri serbest bırak
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET processing_started_at = NULL 
            WHERE processing_started_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE) 
            AND status = 'pending'
        ");
        $stmt->execute();
        
        $clearedCount = $stmt->rowCount();
        if ($clearedCount > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] $clearedCount takılan email temizlendi\n";
        }
        
        // 7 günden eski başarısız email'leri sil
        $stmt = $pdo->prepare("
            DELETE FROM email_queue 
            WHERE status = 'failed' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        
        $deletedCount = $stmt->rowCount();
        if ($deletedCount > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] $deletedCount eski başarısız email silindi\n";
        }
        
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Email temizlik hatası: " . $e->getMessage() . "\n";
    }
}

/**
 * Email istatistiklerini güncelle
 */
function updateEmailStatistics($pdo, $processedCount, $errorCount) {
    try {
        $today = date('Y-m-d');
        
        $stmt = $pdo->prepare("
            INSERT INTO email_statistics (id, date, total_sent, total_failed) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                total_sent = total_sent + VALUES(total_sent),
                total_failed = total_failed + VALUES(total_failed),
                updated_at = NOW()
        ");
        $stmt->execute([generateUUID(), $today, $processedCount, $errorCount]);
        
    } catch (PDOException $e) {
        echo "[" . date('Y-m-d H:i:s') . "] İstatistik güncelleme hatası: " . $e->getMessage() . "\n";
    }
}

/**
 * UUID oluştur (basit versiyon)
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>
