<?php
/**
 * Mr ECU - Web Tabanlı Email Queue İşleyici
 * Hosting ortamları için cron job alternatifi
 */

// Güvenlik anahtarı kontrolü
$securityKey = isset($_GET['key']) ? $_GET['key'] : '';
$validKeys = [
    md5('mrecu_email_cron_2024'),
    md5('email_queue_processor'),
    'email_cron_secure_key'
];

// IP bazlı erişim kontrolü (opsiyonel)
$allowedIPs = [
    '127.0.0.1',
    '::1',
    // Hosting IP'nizi buraya ekleyebilirsiniz
];

// Güvenlik kontrolü
if (empty($securityKey) && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    http_response_code(403);
    die('Erişim reddedildi. Güvenlik anahtarı gerekli.');
}

// Başlık ayarları
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output buffering'i kapat (gerçek zamanlı çıktı için)
if (ob_get_level()) ob_end_clean();

echo "=================================================\n";
echo "Mr ECU Email Queue Processor - Web Edition\n";
echo "=================================================\n";
echo "Başlangıç Zamanı: " . date('Y-m-d H:i:s') . "\n\n";

// Script başlangıç zamanı
$startTime = microtime(true);
$processedCount = 0;
$errorCount = 0;

try {
    // Proje dosyalarını include et
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/EmailManager.php';
    
    echo "✓ Konfigürasyon dosyaları yüklendi\n";
    
    // EmailManager'ı başlat
    $emailManager = new EmailManager($pdo);
    echo "✓ EmailManager başlatıldı\n";
    
    // Eski/takılan email'leri temizle
    cleanupStaleEmails($pdo);
    
    // Bekleyen email'leri getir
    $pendingEmails = getPendingEmails($pdo);
    
    if (empty($pendingEmails)) {
        echo "ℹ Bekleyen email bulunamadı\n";
    } else {
        echo "📧 " . count($pendingEmails) . " bekleyen email bulundu\n\n";
        
        foreach ($pendingEmails as $email) {
            try {
                // Email'i işleme alma
                if (!lockEmailForProcessing($pdo, $email['id'])) {
                    echo "⏭ Email zaten işleniyor: " . $email['id'] . "\n";
                    continue;
                }
                
                echo "📤 Email gönderiliyor: " . $email['to_email'] . " - " . $email['subject'] . "\n";
                
                // Email gönderme
                $result = $emailManager->sendQueuedEmail($email);
                
                if ($result) {
                    // Başarılı gönderim
                    markEmailAsSent($pdo, $email['id']);
                    $processedCount++;
                    echo "✅ Başarıyla gönderildi: " . $email['to_email'] . "\n";
                } else {
                    // Başarısız gönderim
                    $attempts = ($email['attempts'] ?? 0) + 1;
                    
                    if ($attempts >= ($email['max_attempts'] ?? 3)) {
                        markEmailAsFailed($pdo, $email['id'], 'Maximum attempts reached', $attempts);
                        echo "❌ Maksimum deneme aşıldı: " . $email['to_email'] . "\n";
                    } else {
                        incrementEmailAttempts($pdo, $email['id'], $attempts);
                        echo "⚠️ Başarısız (deneme " . $attempts . "): " . $email['to_email'] . "\n";
                    }
                    $errorCount++;
                }
                
                // Rate limiting
                usleep(500000); // 0.5 saniye bekleme
                
            } catch (Exception $e) {
                echo "❌ Email gönderim hatası: " . $e->getMessage() . "\n";
                markEmailAsFailed($pdo, $email['id'], $e->getMessage(), ($email['attempts'] ?? 0) + 1);
                $errorCount++;
            }
        }
    }
    
    // İstatistik güncelle
    updateEmailStatistics($pdo, $processedCount, $errorCount);
    
} catch (Exception $e) {
    echo "🚨 Kritik hata: " . $e->getMessage() . "\n";
    error_log("Web Email Queue Processor kritik hata: " . $e->getMessage());
}

// Özet rapor
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n=================================================\n";
echo "Email Queue İşleme Tamamlandı\n";
echo "=================================================\n";
echo "Toplam işlenen: $processedCount\n";
echo "Toplam hata: $errorCount\n";
echo "Çalışma süresi: {$executionTime} saniye\n";
echo "Bitiş zamanı: " . date('Y-m-d H:i:s') . "\n";

// Web tabanlı özel raporlama
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'processed' => $processedCount,
        'errors' => $errorCount,
        'execution_time' => $executionTime,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Yardımcı fonksiyonlar
 */
function getPendingEmails($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'pending' 
            AND (processing_started_at IS NULL OR processing_started_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
            AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
            ORDER BY 
                CASE priority 
                    WHEN 'high' THEN 1 
                    WHEN 'normal' THEN 2 
                    WHEN 'low' THEN 3 
                    ELSE 2 
                END, 
                created_at ASC
            LIMIT 20
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "❌ Bekleyen email'ler alınamadı: " . $e->getMessage() . "\n";
        return [];
    }
}

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
        echo "❌ Email kilitlenemedi: " . $e->getMessage() . "\n";
        return false;
    }
}

function markEmailAsSent($pdo, $emailId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET status = 'sent', sent_at = NOW(), processing_started_at = NULL, error_message = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$emailId]);
    } catch (PDOException $e) {
        echo "❌ Email durumu güncellenemedi: " . $e->getMessage() . "\n";
    }
}

function markEmailAsFailed($pdo, $emailId, $errorMessage, $attempts) {
    try {
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET status = 'failed', attempts = ?, error_message = ?, processing_started_at = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $errorMessage, $emailId]);
    } catch (PDOException $e) {
        echo "❌ Email hata durumu güncellenemedi: " . $e->getMessage() . "\n";
    }
}

function incrementEmailAttempts($pdo, $emailId, $attempts) {
    try {
        $nextAttemptDelay = pow(2, $attempts) * 5; // Exponential backoff
        
        $stmt = $pdo->prepare("
            UPDATE email_queue 
            SET attempts = ?, processing_started_at = NULL, 
                next_attempt_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $nextAttemptDelay, $emailId]);
    } catch (PDOException $e) {
        echo "❌ Email deneme sayısı güncellenemedi: " . $e->getMessage() . "\n";
    }
}

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
            echo "🧹 $clearedCount takılan email temizlendi\n";
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
            echo "🗑️ $deletedCount eski başarısız email silindi\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Email temizlik hatası: " . $e->getMessage() . "\n";
    }
}

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
        echo "❌ İstatistik güncelleme hatası: " . $e->getMessage() . "\n";
    }
}

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
