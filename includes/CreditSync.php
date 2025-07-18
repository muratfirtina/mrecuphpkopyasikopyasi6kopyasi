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
     * TERS KREDİ SİSTEMİ: Kullanıcının güncel kredi durumunu veritabanından al ve session'ı güncelle
     */
    public function refreshUserCredits($userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if (!$userId) {
            return false;
        }
        
        try {
            // TERS KREDİ SİSTEMİ: Kota ve kullanılan krediyi al
            $stmt = $this->pdo->prepare("SELECT credit_quota, credit_used FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                // Kullanılabilir kredi hesapla
                $availableCredits = (float)$result['credit_quota'] - (float)$result['credit_used'];
                
                // Session'a kullanılabilir kredi bilgisini kaydet (eski sistemle uyumluluk için)
                $_SESSION['credits'] = $availableCredits;
                $_SESSION['credit_quota'] = (float)$result['credit_quota'];
                $_SESSION['credit_used'] = (float)$result['credit_used'];
                
                return $availableCredits;
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