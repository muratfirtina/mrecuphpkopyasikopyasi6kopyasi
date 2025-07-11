<?php
/**
 * Mr ECU - Email Manager Class
 * Email Yönetimi ve Gönderme Sınıfı (PHPMailer Olmadan)
 */

class EmailManager {
    private $pdo;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct($database) {
        $this->pdo = $database;
        
        // Email ayarları - mrecu@outlook.com için Outlook SMTP
        $this->smtp_host = 'smtp-mail.outlook.com';
        $this->smtp_port = 587;
        $this->smtp_username = 'mrecu@outlook.com';
        $this->smtp_password = ''; // Bu kısmı güvenlik için boş bırakıyorum, config'den alınacak
        $this->from_email = 'mrecu@outlook.com';
        $this->from_name = 'Mr ECU';
        
        // Config'den SMTP şifresini al
        $this->loadEmailConfig();
    }
    
    /**
     * Email konfigürasyonunu yükle
     */
    private function loadEmailConfig() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT setting_value FROM settings 
                WHERE setting_key = 'smtp_password'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->smtp_password = $result['setting_value'];
            }
        } catch(PDOException $e) {
            error_log('EmailManager loadEmailConfig error: ' . $e->getMessage());
        }
    }
    
    /**
     * Email gönder (PHP mail() fonksiyonu ile + Test Modu)
     */
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        try {
            // Test modu: MAMP'ta çalışmıyorsa log'a yaz
            $testMode = defined('EMAIL_TEST_MODE') ? EMAIL_TEST_MODE : true;
            
            if ($testMode) {
                // Test modu - Email'i log dosyasına yaz
                return $this->logEmailForTesting($to, $subject, $body, $isHTML);
            }
            
            // Gerçek email gönderimi
            // Email başlıkları hazırla
            $headers = array();
            $headers[] = "From: {$this->from_name} <{$this->from_email}>";
            $headers[] = "Reply-To: {$this->from_email}";
            $headers[] = "X-Mailer: Mr ECU Email System";
            $headers[] = "MIME-Version: 1.0";
            
            if ($isHTML) {
                $headers[] = "Content-Type: text/html; charset=UTF-8";
            } else {
                $headers[] = "Content-Type: text/plain; charset=UTF-8";
            }
            
            // Başlıkları birleştir
            $headerString = implode("\r\n", $headers);
            
            // Email gönder
            $result = mail($to, $subject, $body, $headerString);
            
            if ($result) {
                error_log("Email sent successfully to: {$to}");
                return true;
            } else {
                error_log("Email sending failed to: {$to} - Falling back to test mode");
                return $this->logEmailForTesting($to, $subject, $body, $isHTML);
            }
            
        } catch (Exception $e) {
            error_log("EmailManager sendEmail error: {$e->getMessage()}");
            // Hata durumunda test moduna geç
            return $this->logEmailForTesting($to, $subject, $body, $isHTML);
        }
    }
    
    /**
     * Test amaçlı email log'lama
     */
    private function logEmailForTesting($to, $subject, $body, $isHTML) {
        try {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logFile = $logDir . '/email_test.log';
            $timestamp = date('Y-m-d H:i:s');
            
            $logContent = "\n" . str_repeat('=', 80) . "\n";
            $logContent .= "EMAIL TEST LOG - {$timestamp}\n";
            $logContent .= str_repeat('=', 80) . "\n";
            $logContent .= "To: {$to}\n";
            $logContent .= "Subject: {$subject}\n";
            $logContent .= "Type: " . ($isHTML ? 'HTML' : 'Plain Text') . "\n";
            $logContent .= "From: {$this->from_name} <{$this->from_email}>\n";
            $logContent .= str_repeat('-', 80) . "\n";
            $logContent .= "Body:\n{$body}\n";
            $logContent .= str_repeat('=', 80) . "\n\n";
            
            file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
            
            error_log("Email logged for testing: {$to} - Subject: {$subject}");
            return true;
            
        } catch (Exception $e) {
            error_log("Email test logging failed: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Email kuyruğunu işle
     */
    public function processEmailQueue($limit = 5) {
        try {
            // Bekleyen emailları al
            $stmt = $this->pdo->prepare("
                SELECT * FROM email_queue 
                WHERE status = 'pending' AND attempts < max_attempts
                ORDER BY priority DESC, created_at ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            
            foreach ($emails as $email) {
                // Gönderme denemesi sayısını artır
                $this->updateEmailAttempt($email['id']);
                
                // Email gönder
                $success = $this->sendEmail($email['to_email'], $email['subject'], $email['body'], true);
                
                if ($success) {
                    // Başarılı olarak işaretle
                    $this->updateEmailStatus($email['id'], 'sent');
                    $processed++;
                } else {
                    // Maksimum deneme sayısına ulaştıysa başarısız olarak işaretle
                    if ($email['attempts'] + 1 >= $email['max_attempts']) {
                        $this->updateEmailStatus($email['id'], 'failed', 'Max attempts reached');
                    }
                }
            }
            
            return $processed;
            
        } catch(Exception $e) {
            error_log('EmailManager processEmailQueue error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Email durumunu güncelle
     */
    private function updateEmailStatus($emailId, $status, $errorMessage = null) {
        try {
            $sql = "UPDATE email_queue SET status = ?, sent_at = NOW()";
            $params = [$status];
            
            if ($errorMessage) {
                $sql .= ", error_message = ?";
                $params[] = $errorMessage;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $emailId;
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch(PDOException $e) {
            error_log('EmailManager updateEmailStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Email deneme sayısını artır
     */
    private function updateEmailAttempt($emailId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE email_queue SET attempts = attempts + 1 WHERE id = ?
            ");
            return $stmt->execute([$emailId]);
            
        } catch(PDOException $e) {
            error_log('EmailManager updateEmailAttempt error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Email kuyruğuna ekle
     */
    public function queueEmail($to, $subject, $body, $priority = 'normal') {
        try {
            $emailId = generateUUID(); // UUID oluştur
            $stmt = $this->pdo->prepare("
                INSERT INTO email_queue (id, to_email, subject, body, priority) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([$emailId, $to, $subject, $body, $priority]);
            
        } catch(PDOException $e) {
            error_log('EmailManager queueEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hızlı email gönder (doğrudan, kuyruk kullanmadan)
     */
    public function sendDirectEmail($to, $subject, $body) {
        return $this->sendEmail($to, $subject, $body, true);
    }
    
    /**
     * Test email gönder
     */
    public function sendTestEmail($to) {
        $subject = 'Mr ECU - Email Test';
        $body = '
        <html>
        <body>
            <h2>Email Test - Mr ECU</h2>
            <p>Bu bir test emailidir.</p>
            <p>Eğer bu emaili alıyorsanız, email sistemi doğru çalışıyor demektir.</p>
            <p>Tarih: ' . date('d.m.Y H:i:s') . '</p>
            <hr>
            <p><small>Mr ECU - Otomatik Email Sistemi</small></p>
        </body>
        </html>';
        
        return $this->sendEmail($to, $subject, $body, true);
    }
    
    /**
     * Email istatistiklerini getir
     */
    public function getEmailStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM email_queue
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('EmailManager getEmailStats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'sent' => 0,
                'failed' => 0
            ];
        }
    }
    
    /**
     * Eski email kayıtlarını temizle (30 günden eski)
     */
    public function cleanOldEmails() {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM email_queue 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND status IN ('sent', 'failed')
            ");
            
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log('EmailManager cleanOldEmails error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
