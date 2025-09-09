<?php
/**
 * Mr ECU - Email Bounce Handler
 * Geri dönen email'leri (bounce) işler ve kullanıcı email adreslerini günceller
 */

require_once '../config/config.php';
require_once '../config/database.php';

class EmailBounceHandler {
    private $pdo;
    private $bounceTypes = [
        'hard_bounce' => [
            'patterns' => [
                'user unknown',
                'mailbox unavailable',
                'invalid recipient',
                'address not found',
                'no such user',
                'account disabled',
                'recipient address rejected'
            ],
            'action' => 'disable'
        ],
        'soft_bounce' => [
            'patterns' => [
                'mailbox full',
                'message too large',
                'temporary failure',
                'server busy',
                'rate limit exceeded'
            ],
            'action' => 'retry'
        ],
        'spam_complaint' => [
            'patterns' => [
                'spam complaint',
                'abuse report',
                'blacklisted',
                'blocked by policy'
            ],
            'action' => 'block'
        ]
    ];
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    /**
     * Bounce mesajını analiz et ve işle
     * @param string $bounceMessage - Geri dönen email içeriği
     * @param string $originalRecipient - Orijinal alıcı email
     * @return array - İşlem sonucu
     */
    public function processBounce($bounceMessage, $originalRecipient) {
        try {
            // Bounce tipini belirle
            $bounceType = $this->identifyBounceType($bounceMessage);
            
            // Kullanıcıyı bul
            $user = $this->findUserByEmail($originalRecipient);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı: ' . $originalRecipient
                ];
            }
            
            // Bounce kaydı oluştur
            $bounceId = $this->createBounceRecord($user['id'], $originalRecipient, $bounceType, $bounceMessage);
            
            // Bounce tipine göre aksiyon al
            $actionResult = $this->takeBounceAction($user['id'], $bounceType, $originalRecipient);
            
            return [
                'success' => true,
                'bounce_type' => $bounceType,
                'action_taken' => $actionResult,
                'bounce_id' => $bounceId,
                'message' => "Bounce işlendi: $bounceType - {$actionResult['action']}"
            ];
            
        } catch (Exception $e) {
            error_log('EmailBounceHandler processBounce error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Bounce işleme hatası: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Bounce tipini belirle
     * @param string $message - Bounce mesajı
     * @return string - Bounce tipi
     */
    private function identifyBounceType($message) {
        $message = strtolower($message);
        
        foreach ($this->bounceTypes as $type => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (strpos($message, strtolower($pattern)) !== false) {
                    return $type;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Email'e göre kullanıcı bul
     * @param string $email - Email adresi
     * @return array|null - Kullanıcı bilgileri
     */
    private function findUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('findUserByEmail error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Bounce kaydı oluştur
     * @param string $userId - Kullanıcı ID
     * @param string $email - Email adresi
     * @param string $bounceType - Bounce tipi
     * @param string $bounceMessage - Bounce mesajı
     * @return string - Bounce ID
     */
    private function createBounceRecord($userId, $email, $bounceType, $bounceMessage) {
        try {
            $bounceId = $this->generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO email_bounces (
                    id, user_id, email, bounce_type, bounce_message, 
                    bounce_date, processed, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), 1, NOW())
            ");
            
            $stmt->execute([
                $bounceId, $userId, $email, $bounceType, $bounceMessage
            ]);
            
            return $bounceId;
        } catch (PDOException $e) {
            error_log('createBounceRecord error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Bounce tipine göre aksiyon al
     * @param string $userId - Kullanıcı ID
     * @param string $bounceType - Bounce tipi
     * @param string $email - Email adresi
     * @return array - Aksiyon sonucu
     */
    private function takeBounceAction($userId, $bounceType, $email) {
        try {
            $action = $this->bounceTypes[$bounceType]['action'] ?? 'log';
            
            switch ($action) {
                case 'disable':
                    // Hard bounce - Email adresini devre dışı bırak
                    $this->disableUserEmail($userId, $email);
                    return ['action' => 'email_disabled', 'description' => 'Email adresi devre dışı bırakıldı'];
                    
                case 'block':
                    // Spam complaint - Kullanıcıya email göndermeyi durdur
                    $this->blockUserEmails($userId, $email);
                    return ['action' => 'emails_blocked', 'description' => 'Email gönderimi engellendi'];
                    
                case 'retry':
                    // Soft bounce - Geçici hata, tekrar dene
                    $this->markForRetry($userId, $email);
                    return ['action' => 'marked_for_retry', 'description' => 'Tekrar deneme için işaretlendi'];
                    
                default:
                    // Bilinmeyen bounce - Sadece kaydet
                    return ['action' => 'logged_only', 'description' => 'Sadece kaydedildi'];
            }
            
        } catch (Exception $e) {
            error_log('takeBounceAction error: ' . $e->getMessage());
            return ['action' => 'error', 'description' => 'Aksiyon alınırken hata oluştu'];
        }
    }
    
    /**
     * Kullanıcının email adresini devre dışı bırak
     * @param string $userId - Kullanıcı ID
     * @param string $email - Email adresi
     */
    private function disableUserEmail($userId, $email) {
        try {
            // Kullanıcının email_verified durumunu 0 yap
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_verified = 0, email_bounce_status = 'hard_bounce', 
                    email_bounce_date = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Email tercihlerini güncelle - tüm email'leri kapat
            $stmt = $this->pdo->prepare("
                UPDATE user_email_preferences 
                SET file_upload_notifications = 0, file_ready_notifications = 0,
                    revision_notifications = 0, additional_file_notifications = 0,
                    marketing_emails = 0, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            error_log("Email devre dışı bırakıldı: $email (User ID: $userId)");
            
        } catch (PDOException $e) {
            error_log('disableUserEmail error: ' . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcıya email gönderimini engelle
     * @param string $userId - Kullanıcı ID
     * @param string $email - Email adresi
     */
    private function blockUserEmails($userId, $email) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_bounce_status = 'blocked', email_bounce_date = NOW(), 
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Tüm email tercihlerini kapat
            $stmt = $this->pdo->prepare("
                UPDATE user_email_preferences 
                SET file_upload_notifications = 0, file_ready_notifications = 0,
                    revision_notifications = 0, additional_file_notifications = 0,
                    marketing_emails = 0, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            
            error_log("Email gönderimi engellendi: $email (User ID: $userId)");
            
        } catch (PDOException $e) {
            error_log('blockUserEmails error: ' . $e->getMessage());
        }
    }
    
    /**
     * Email'i tekrar deneme için işaretle
     * @param string $userId - Kullanıcı ID
     * @param string $email - Email adresi
     */
    private function markForRetry($userId, $email) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_bounce_status = 'soft_bounce', email_bounce_date = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            error_log("Email tekrar deneme için işaretlendi: $email (User ID: $userId)");
            
        } catch (PDOException $e) {
            error_log('markForRetry error: ' . $e->getMessage());
        }
    }
    
    /**
     * Email gönderim öncesi bounce kontrolü
     * @param string $email - Kontrol edilecek email
     * @return bool - Email gönderilebilir mi?
     */
    public function isEmailSendable($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT email_bounce_status, email_bounce_date 
                FROM users 
                WHERE email = ? AND email_verified = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false; // Kullanıcı bulunamadı veya email doğrulanmamış
            }
            
            $bounceStatus = $user['email_bounce_status'];
            
            // Hard bounce veya bloke edilmiş email'lere gönderme
            if (in_array($bounceStatus, ['hard_bounce', 'blocked'])) {
                return false;
            }
            
            // Soft bounce için 24 saat bekle
            if ($bounceStatus === 'soft_bounce' && $user['email_bounce_date']) {
                $bounceDate = new DateTime($user['email_bounce_date']);
                $now = new DateTime();
                $interval = $now->diff($bounceDate);
                
                if ($interval->h < 24 && $interval->days == 0) {
                    return false; // 24 saat dolmamış
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('isEmailSendable error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bounce istatistiklerini getir
     * @return array - İstatistikler
     */
    public function getBounceStatistics() {
        try {
            $stats = [];
            
            // Bounce tipleri dağılımı
            $stmt = $this->pdo->query("
                SELECT bounce_type, COUNT(*) as count 
                FROM email_bounces 
                WHERE bounce_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY bounce_type
            ");
            $stats['bounce_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Bounce oranları
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(DISTINCT user_id) as affected_users,
                    COUNT(*) as total_bounces
                FROM email_bounces 
                WHERE bounce_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stats['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // En çok bounce alan domainler
            $stmt = $this->pdo->query("
                SELECT 
                    SUBSTRING_INDEX(email, '@', -1) as domain,
                    COUNT(*) as bounce_count
                FROM email_bounces 
                WHERE bounce_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY domain
                ORDER BY bounce_count DESC
                LIMIT 10
            ");
            $stats['top_bounce_domains'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log('getBounceStatistics error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * UUID oluştur
     * @return string - Yeni UUID
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// CLI kullanımı için
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $bounceHandler = new EmailBounceHandler($pdo);
    
    switch ($argv[1]) {
        case 'process':
            // Örnek bounce işleme
            if (isset($argv[2]) && isset($argv[3])) {
                $result = $bounceHandler->processBounce($argv[2], $argv[3]);
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Kullanım: php bounce_handler.php process 'bounce_message' 'recipient_email'\n";
            }
            break;
            
        case 'stats':
            // İstatistikleri göster
            $stats = $bounceHandler->getBounceStatistics();
            echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'check':
            // Email gönderim kontrolü
            if (isset($argv[2])) {
                $sendable = $bounceHandler->isEmailSendable($argv[2]);
                echo "Email gönderim durumu ($argv[2]): " . ($sendable ? "Gönderebilir" : "Gönderemez") . "\n";
            } else {
                echo "Kullanım: php bounce_handler.php check 'email@example.com'\n";
            }
            break;
            
        default:
            echo "Mevcut komutlar: process, stats, check\n";
    }
}
?>
