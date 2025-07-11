<?php
/**
 * Mr ECU - Email Queue Processor (Cron Job)
 * Email Kuyruk İşleyici - Otomatik Email Gönderimi
 * 
 * Bu dosya cron job olarak çalıştırılır.
 * Örnek cron job: * * * * * /usr/bin/php /path/to/process_email_queue.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Console çıktısı için
$isCommandLine = php_sapi_name() === 'cli';

if (!$isCommandLine) {
    // Web'den erişim kontrolü - sadece admin erişimi
    if (!isLoggedIn() || !isAdmin()) {
        echo json_encode(['error' => 'Yetkisiz erişim']);
        exit;
    }
}

try {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Email Queue Processor başlatıldı\n";
    
    if ($isCommandLine) {
        echo $logMessage;
    } else {
        echo "<pre>$logMessage</pre>";
    }
    
    // EmailManager başlat
    $emailManager = new EmailManager($pdo);
    
    // Email kuyruğunu işle (her seferinde maksimum 10 email)
    $processed = $emailManager->processEmailQueue(10);
    
    $logMessage = "İşlenen email sayısı: $processed\n";
    
    if ($isCommandLine) {
        echo $logMessage;
    } else {
        echo "<pre>$logMessage</pre>";
    }
    
    // Eski emailları temizle (30 günden eski)
    $cleanResult = $emailManager->cleanOldEmails();
    $notificationManager = new NotificationManager($pdo);
    $cleanNotifications = $notificationManager->cleanOldNotifications();
    
    $logMessage = "Eski kayıtlar temizlendi\n";
    
    if ($isCommandLine) {
        echo $logMessage;
    } else {
        echo "<pre>$logMessage</pre>";
    }
    
    // İstatistikler
    $stats = $emailManager->getEmailStats();
    $statsMessage = "Email İstatistikleri:\n";
    $statsMessage .= "- Toplam: {$stats['total']}\n";
    $statsMessage .= "- Bekleyen: {$stats['pending']}\n";
    $statsMessage .= "- Gönderilen: {$stats['sent']}\n";
    $statsMessage .= "- Başarısız: {$stats['failed']}\n";
    
    if ($isCommandLine) {
        echo $statsMessage;
    } else {
        echo "<pre>$statsMessage</pre>";
    }
    
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Email Queue Processor tamamlandı\n\n";
    
    if ($isCommandLine) {
        echo $logMessage;
    } else {
        echo "<pre>$logMessage</pre>";
    }
    
    // Log dosyasına yaz
    $logFile = __DIR__ . '/logs/email_queue.log';
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    $fullLog = "[" . date('Y-m-d H:i:s') . "] Processed: $processed emails, Stats: " . json_encode($stats) . "\n";
    file_put_contents($logFile, $fullLog, FILE_APPEND | LOCK_EX);
    
    // Web'den çağrıldıysa JSON response
    if (!$isCommandLine) {
        echo json_encode([
            'success' => true,
            'processed' => $processed,
            'stats' => $stats
        ]);
    }
    
} catch (Exception $e) {
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] Email Queue Processor Error: " . $e->getMessage() . "\n";
    
    if ($isCommandLine) {
        echo $errorMessage;
    } else {
        echo "<pre>$errorMessage</pre>";
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    // Error log
    error_log($errorMessage);
    
    // Log dosyasına yaz
    $logFile = __DIR__ . '/logs/email_queue.log';
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
}
?>
