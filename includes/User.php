<?php
/**
 * Mr ECU - User Class (GUID System) - CLEAN VERSION
 * GUID tabanlı kullanıcı işlemleri sınıfı - Duplicate metotlar temizlendi
 */

class User {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Kullanıcıyı ID ile getir (GUID ID ile)
    public function getUserById($userId) {
        try {
            if (!isValidUUID($userId)) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserById error: ' . $e->getMessage());
            return null;
        }
    }
    
    // Kullanıcı kredisini al (GUID ID ile)
    public function getUserCredits($userId) {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $stmt = $this->pdo->prepare("SELECT credits FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result ? (float)$result['credits'] : 0;
            
        } catch(PDOException $e) {
            error_log('getUserCredits error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Log işlemi (GUID ID ile)
    public function logAction($userId, $action, $description = '', $ipAddress = null) {
        try {
            if (!isValidUUID($userId)) {
                return false;
            }
            
            if (!$ipAddress) {
                $ipAddress = getRealIP();
            }
            
            $logId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO system_logs (id, user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $logId,
                $userId,
                $action,
                $description,
                $ipAddress,
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
        } catch(PDOException $e) {
            error_log('logAction error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Kredi çıkarma (GUID ID ile)
    public function deductCredits($userId, $amount, $description = '') {
        try {
            if (!isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı.'];
            }
            
            $currentCredits = $this->getUserCredits($userId);
            
            if ($currentCredits < $amount) {
                return ['success' => false, 'message' => 'Yetersiz kredi bakiyesi.'];
            }
            
            $result = $this->addCreditDirectSimple($userId, $amount, 'withdraw', $description);
            
            if ($result) {
                return ['success' => true, 'message' => 'Kredi başarıyla düşürüldü.'];
            } else {
                return ['success' => false, 'message' => 'Kredi düşürme işlemi başarısız.'];
            }
            
        } catch(PDOException $e) {
            error_log('deductCredits error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    // Kullanıcı giriş
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_admin'] = ($user['role'] === 'admin') ? 1 : 0;
                $_SESSION['credits'] = $user['credits'];
                
                // Son giriş zamanını güncelle
                $this->updateLastLogin($user['id']);
                
                // Log kaydı
                $this->logAction($user['id'], 'login', 'Kullanıcı sisteme giriş yaptı');
                
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Kullanıcı kayıt
    public function register($data, $isAdmin = false) {
        try {
            // Email ve kullanıcı adı kontrolü
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Bu email adresi zaten kullanılıyor.'];
            }
            
            if ($this->usernameExists($data['username'])) {
                return ['success' => false, 'message' => 'Bu kullanıcı adı zaten kullanılıyor.'];
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $verificationToken = generateToken();
            $userId = generateUUID();
            
            $role = $isAdmin && isset($data['role']) ? $data['role'] : 'user';
            $credits = $isAdmin && isset($data['credits']) ? $data['credits'] : DEFAULT_CREDITS;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (id, username, email, password, first_name, last_name, phone, role, credits, verification_token, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $userId,
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? '',
                $role,
                $credits,
                $verificationToken
            ]);
            
            if ($result) {
                // Log kaydı
                $this->logAction($userId, 'register', 'Yeni kullanıcı kaydı');
                
                return ['success' => true, 'message' => 'Kayıt başarılı.', 'user_id' => $userId];
            }
            
            return ['success' => false, 'message' => 'Kayıt sırasında bir hata oluştu.'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // Email doğrulama
    public function verifyEmail($token) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE verification_token = ?");
            $result = $stmt->execute([$token]);
            
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Şifre sıfırlama isteği
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $resetToken = generateToken();
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $this->pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $stmt->execute([$resetToken, $expiresAt, $user['id']]);
                
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Şifre sıfırlama
    public function resetPassword($token, $newPassword) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM users 
                WHERE reset_token = ? AND reset_token_expires > NOW() AND status = 'active'
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET password = ?, reset_token = NULL, reset_token_expires = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$hashedPassword, $user['id']]);
                
                return true;
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Kullanıcı bilgilerini güncelle
    public function updateUser($id, $data) {
        try {
            if (!isValidUUID($id)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['first_name'],
                $data['last_name'], 
                $data['phone'],
                $id
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Kredi ekle/çıkar (basit versiyon)
    public function addCreditDirectSimple($userId, $amount, $type = 'deposit', $description = '', $referenceId = null, $referenceType = null, $adminId = null) {
        try {
            if (!isValidUUID($userId)) {
                return false;
            }
            
            if ($adminId && !isValidUUID($adminId)) {
                return false;
            }
            
            if ($referenceId && !isValidUUID($referenceId)) {
                return false;
            }
            
            // Kullanıcının mevcut kredi bakiyesini güncelle
            if ($type === 'withdraw' || $type === 'file_charge') {
                $currentCredits = $this->getUserCredits($userId);
                
                if ($currentCredits < $amount) {
                    return false;
                }
                
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
                $result = $stmt->execute([$amount, $userId]);
                
                if (!$result) {
                    return false;
                }
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$amount, $userId]);
            }
            
            // İşlem kaydı ekle
            $transactionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, amount, type, description, reference_id, reference_type, admin_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$transactionId, $userId, $amount, $type, $description, $referenceId, $referenceType, $adminId]);
            
            // Session'daki kredi bilgisini güncelle
            $this->updateUserCreditsInSession($userId);
            
            return true;
            
        } catch(PDOException $e) {
            error_log("addCreditDirectSimple error: " . $e->getMessage());
            return false;
        }
    }
    
    // Kullanıcı çıkış
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAction($_SESSION['user_id'], 'logout', 'Kullanıcı sistemden çıkış yaptı');
        }
        
        session_destroy();
        return true;
    }
    
    // Tüm kullanıcıları listele (Admin)
    public function getAllUsers($page = 1, $limit = 50) {
        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // LIMIT ve OFFSET için direkt sayısal değerler kullan
            $query = "
                SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at, last_login,
                       (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
                FROM users 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("getAllUsers error: " . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcı sayısı
    public function getUserCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn();
        } catch(PDOException $e) {
            return 0;
        }
    }
    
    // Session'daki kredi bilgisini güncelle
    public function updateUserCreditsInSession($userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if ($userId && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['credits'] = $this->getUserCredits($userId);
        }
    }
    
    // Email var mı kontrol et
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    // Kullanıcı adı var mı kontrol et
    private function usernameExists($username) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }
    
    // Son giriş zamanını güncelle
    private function updateLastLogin($userId) {
        if (!isValidUUID($userId)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
}
?>
