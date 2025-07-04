<?php
/**
 * Mr ECU - Kredi Senkronizasyon Yardımcı Sınıfı
 * Kullanıcı kredi bilgilerini session ve veritabanı arasında senkronize eder
 */

class CreditSync {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    /**
     * Kullanıcının güncel kredi bakiyesini veritabanından al ve session'ı güncelle
     */
    public function refreshUserCredits($userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if (!$userId) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT credits FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['credits'] = (float)$result['credits'];
                return $_SESSION['credits'];
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("CreditSync::refreshUserCredits - Hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kredi işleminden sonra ilgili kullanıcıların session bilgilerini güncelle
     */
    public function syncAfterCreditChange($userId) {
        return $this->refreshUserCredits($userId);
    }
    
    /**
     * Global kredi güncelleme fonksiyonu - tüm sistem için
     */
    public static function updateUserSession($pdo, $userId = null) {
        $sync = new self($pdo);
        return $sync->refreshUserCredits($userId);
    }
}

/**
 * Global helper fonksiyon - kolay kullanım için
 */
function refreshUserCredits($userId = null) {
    global $pdo;
    if (isset($pdo)) {
        return CreditSync::updateUserSession($pdo, $userId);
    }
    return false;
}
?>