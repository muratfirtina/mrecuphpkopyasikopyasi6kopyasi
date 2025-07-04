<?php
/**
 * Mr ECU - Session Validation Helper
 * Session doğrulama ve user ID kontrol sınıfı
 */

class SessionValidator {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    /**
     * Aktif session'daki user_id'nin veritabanında var olup olmadığını kontrol eder
     */
    public function validateSessionUser() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return [
                'valid' => false,
                'message' => 'Session bilgisi bulunamadı.',
                'action' => 'logout'
            ];
        }
        
        $userId = $_SESSION['user_id'];
        
        // GUID format kontrolü
        if (!isValidUUID($userId)) {
            return [
                'valid' => false,
                'message' => 'Geçersiz kullanıcı ID formatı.',
                'action' => 'logout'
            ];
        }
        
        try {
            // Kullanıcının veritabanında var olup olmadığını kontrol et
            $stmt = $this->pdo->prepare("SELECT id, username, is_admin, status FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'valid' => false,
                    'message' => 'Kullanıcı bulunamadı. Lütfen tekrar giriş yapın.',
                    'action' => 'logout'
                ];
            }
            
            if ($user['status'] !== 'active') {
                return [
                    'valid' => false,
                    'message' => 'Hesabınız aktif değil. Lütfen yöneticiye başvurun.',
                    'action' => 'logout'
                ];
            }
            
            // Session bilgilerini güncelle (gerekirse)
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            return [
                'valid' => true,
                'user' => $user,
                'message' => 'Session geçerli.'
            ];
            
        } catch(PDOException $e) {
            error_log("SessionValidator DB Error: " . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Veritabanı hatası oluştu.',
                'action' => 'logout'
            ];
        }
    }
    
    /**
     * Admin yetkisini kontrol eder
     */
    public function validateAdminAccess() {
        $validation = $this->validateSessionUser();
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        if (!$validation['user']['is_admin']) {
            return [
                'valid' => false,
                'message' => 'Admin yetkisi gerekli.',
                'action' => 'redirect_dashboard'
            ];
        }
        
        return $validation;
    }
    
    /**
     * Session temizle ve logout yap
     */
    public function forceLogout() {
        session_destroy();
        session_start();
        $_SESSION = [];
        
        // Cookie temizle
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        header('Location: ../login.php?error=session_invalid');
        exit;
    }
}
?>
