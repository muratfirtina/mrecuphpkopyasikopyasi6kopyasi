<?php
/**
 * Mr ECU - User Class (GUID System)
 * GUID tabanlı kullanıcı işlemleri sınıfı
 */

class User {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Kullanıcı giriş
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Debug için role değerini logla
                error_log("Login Debug - User ID: {$user['id']}, role DB value: {$user['role']}, type: " . gettype($user['role']));
                
                $_SESSION['user_id'] = $user['id']; // GUID format
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role']; // role alanını kullan
                $_SESSION['is_admin'] = ($user['role'] === 'admin') ? 1 : 0; // is_admin değeri türet
                $_SESSION['credits'] = $user['credits'];
                
                // Debug için session değerini logla
                error_log("Login Debug - Session role: {$_SESSION['role']}, is_admin: {$_SESSION['is_admin']}");
                
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
    public function register($data) {
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
            $userId = generateUUID(); // UUID oluştur
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (id, username, email, password, first_name, last_name, phone, verification_token, credits) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $userId,
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['phone'],
                $verificationToken,
                DEFAULT_CREDITS
            ]);
            
            if ($result) {
                // Hoş geldin kredisi ekle
                if (DEFAULT_CREDITS > 0) {
                    $this->addCreditDirect($userId, DEFAULT_CREDITS, 'deposit', 'Hoş geldin bonusu');
                }
                
                // Doğrulama emaili gönder
                $this->sendVerificationEmail($data['email'], $verificationToken);
                
                // Log kaydı
                $this->logAction($userId, 'register', 'Yeni kullanıcı kaydı');
                
                return ['success' => true, 'message' => 'Kayıt başarılı. Email adresinizi doğrulayın.', 'user_id' => $userId];
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
                
                // Şifre sıfırlama emaili gönder
                $this->sendPasswordResetEmail($email, $resetToken);
                
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
    
    // Kullanıcı bilgilerini getir (GUID ID ile)
    public function getUserById($id) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($id)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Kullanıcı kredi bakiyesi (GUID ID ile)
    public function getUserCredits($userId) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $stmt = $this->pdo->prepare("SELECT credits FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result ? $result['credits'] : 0;
        } catch(PDOException $e) {
            return 0;
        }
    }
    
    // Kredi ekle/çıkar (dış transaction ile kullanım için) - GUID VERSİYON
    public function addCreditDirect($userId, $amount, $type = 'deposit', $description = '', $referenceId = null, $referenceType = null, $adminId = null) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($userId)) {
                error_log("AddCreditDirect - Invalid User ID format: $userId");
                return false;
            }
            
            if ($adminId && !isValidUUID($adminId)) {
                error_log("AddCreditDirect - Invalid Admin ID format: $adminId");
                return false;
            }
            
            if ($referenceId && !isValidUUID($referenceId)) {
                error_log("AddCreditDirect - Invalid Reference ID format: $referenceId");
                return false;
            }
            
            // Debug için mevcut krediyi kontrol et
            $currentCredits = $this->getUserCredits($userId);
            error_log("AddCreditDirect - User ID: $userId, Current Credits: $currentCredits, Amount: $amount, Type: $type");
            
            // Kullanıcının mevcut kredi bakiyesini güncelle
            if ($type === 'withdraw' || $type === 'file_charge') {
                // Manual debug - veritabanından direkt kontrol
                $stmt_debug = $this->pdo->prepare("SELECT id, credits FROM users WHERE id = ?");
                $stmt_debug->execute([$userId]);
                $user_debug = $stmt_debug->fetch();
                error_log("AddCreditDirect - Debug - DB User ID: {$user_debug['id']}, DB Credits: {$user_debug['credits']}, Type: " . gettype($user_debug['credits']));
                error_log("AddCreditDirect - Debug - Amount: $amount, Type: " . gettype($amount));
                error_log("AddCreditDirect - Debug - Comparison: {$user_debug['credits']} >= $amount = " . ($user_debug['credits'] >= $amount ? 'true' : 'false'));
                
                // Test UPDATE query
                $test_stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id = ? AND credits >= ?");
                $test_stmt->execute([$userId, $amount]);
                $test_result = $test_stmt->fetch();
                error_log("AddCreditDirect - Test query result: " . $test_result['count']);
                
                // Gerçek UPDATE - sayısal karşılaştırma zorlama
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ? AND CAST(credits AS DECIMAL(10,2)) >= CAST(? AS DECIMAL(10,2))");
                $result = $stmt->execute([$amount, $userId, $amount]);
                
                error_log("AddCreditDirect - UPDATE result: " . ($result ? 'true' : 'false') . ", Affected rows: " . $stmt->rowCount());
                
                if (!$result || $stmt->rowCount() == 0) {
                    // Tekrar mevcut krediyi kontrol et
                    $newCurrentCredits = $this->getUserCredits($userId);
                    error_log("AddCreditDirect - UPDATE başarısız. Yeni kredi kontrolü: $newCurrentCredits");
                    return false;
                }
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$amount, $userId]);
            }
            
            // İşlem kaydı ekle (GUID ID ile)
            $transactionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, amount, type, description, reference_id, reference_type, admin_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$transactionId, $userId, $amount, $type, $description, $referenceId, $referenceType, $adminId]);
            
            // Session'daki kredi bilgisini güncelle
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['credits'] = $this->getUserCredits($userId);
            }
            
            error_log("AddCreditDirect - Başarılı");
            return true;
            
        } catch(PDOException $e) {
            error_log("AddCreditDirect - PDO Hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // Kredi ekle/çıkar (transaction ile) - GUID VERSİYON
    public function addCredit($userId, $amount, $type = 'deposit', $description = '', $referenceId = null, $referenceType = null, $adminId = null) {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($userId)) {
                return false;
            }
            
            if ($adminId && !isValidUUID($adminId)) {
                return false;
            }
            
            if ($referenceId && !isValidUUID($referenceId)) {
                return false;
            }
            
            $this->pdo->beginTransaction();
            
            // Kullanıcının mevcut kredi bakiyesini güncelle
            if ($type === 'withdraw' || $type === 'file_charge') {
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?");
                $result = $stmt->execute([$amount, $userId, $amount]);
                
                if (!$result || $stmt->rowCount() == 0) {
                    $this->pdo->rollBack();
                    return false; // Yetersiz bakiye
                }
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$amount, $userId]);
            }
            
            // İşlem kaydı ekle (GUID ID ile)
            $transactionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, amount, type, description, reference_id, reference_type, admin_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$transactionId, $userId, $amount, $type, $description, $referenceId, $referenceType, $adminId]);
            
            $this->pdo->commit();
            
            // Session'daki kredi bilgisini güncelle
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['credits'] = $this->getUserCredits($userId);
            }
            
            return true;
            
        } catch(PDOException $e) {
            $this->pdo->rollBack();
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
    
    // Son giriş zamanını güncelle (GUID ID ile)
    private function updateLastLogin($userId) {
        if (!isValidUUID($userId)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    // Email doğrulama emaili gönder
    private function sendVerificationEmail($email, $token) {
        $subject = SITE_NAME . ' - Email Doğrulama';
        $verifyUrl = SITE_URL . 'verify.php?token=' . $token;
        
        $message = "
        <h2>Email Adresinizi Doğrulayın</h2>
        <p>Hesabınızı aktifleştirmek için aşağıdaki linke tıklayın:</p>
        <p><a href='{$verifyUrl}'>Email Adresimi Doğrula</a></p>
        <p>Bu link 24 saat geçerlidir.</p>
        ";
        
        return sendEmail($email, $subject, $message);
    }
    
    // Şifre sıfırlama emaili gönder
    private function sendPasswordResetEmail($email, $token) {
        $subject = SITE_NAME . ' - Şifre Sıfırlama';
        $resetUrl = SITE_URL . 'reset-password.php?token=' . $token;
        
        $message = "
        <h2>Şifre Sıfırlama İsteği</h2>
        <p>Şifrenizi sıfırlamak için aşağıdaki linke tıklayın:</p>
        <p><a href='{$resetUrl}'>Şifremi Sıfırla</a></p>
        <p>Bu link 1 saat geçerlidir.</p>
        ";
        
        return sendEmail($email, $subject, $message);
    }
    
    // Sistem logları (GUID ID ile)
    public function logAction($userId, $action, $description = '') {
        try {
            // GUID format kontrolü
            if ($userId && !isValidUUID($userId)) {
                return false;
            }
            
            $logId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO system_logs (id, user_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $logId,
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch(PDOException $e) {
            // Log hatası sessizce göz ardı edilir
        }
    }
    
    // Tüm kullanıcıları listele (Admin) - GUID VERSİYON
    public function getAllUsers($page = 1, $limit = 50) {
        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // LIMIT ve OFFSET'i direkt SQL'e koyarak string sorunu çözüyoruz
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
                       (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
                FROM users 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
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
    
    // Kredi ekle/çıkar (dış transaction ile kullanım için) - BASİT VERSİYON GUID
    public function addCreditDirectSimple($userId, $amount, $type = 'deposit', $description = '', $referenceId = null, $referenceType = null, $adminId = null) {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($userId)) {
                error_log("AddCreditDirectSimple - Invalid User ID: $userId");
                return false;
            }
            
            if ($adminId && !isValidUUID($adminId)) {
                error_log("AddCreditDirectSimple - Invalid Admin ID: $adminId");
                return false;
            }
            
            if ($referenceId && !isValidUUID($referenceId)) {
                error_log("AddCreditDirectSimple - Invalid Reference ID: $referenceId");
                return false;
            }
            
            // Kullanıcının mevcut kredi bakiyesini güncelle
            if ($type === 'withdraw' || $type === 'file_charge') {
                // Basit çözüm: Önce mevcut krediyi al, sonra güncelle
                $currentCredits = (float)$this->getUserCredits($userId);
                $amount = (float)$amount;
                
                error_log("AddCreditDirectSimple - Current: $currentCredits, Amount: $amount, Sufficient: " . ($currentCredits >= $amount ? 'YES' : 'NO'));
                
                if ($currentCredits < $amount) {
                    return false;
                }
                
                // Direkt UPDATE
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
                $result = $stmt->execute([$amount, $userId]);
                
                if (!$result) {
                    return false;
                }
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$amount, $userId]);
            }
            
            // İşlem kaydı ekle (GUID ID ile)
            $transactionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, amount, type, description, reference_id, reference_type, admin_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$transactionId, $userId, $amount, $type, $description, $referenceId, $referenceType, $adminId]);
            
            // SESSION'daki kredi bilgisini güncelle
            $this->updateUserCreditsInSession($userId);
            
            return true;
            
        } catch(PDOException $e) {
            error_log("AddCreditDirectSimple - PDO Hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // Session'daki kredi bilgisini güncelle (GUID ID ile)
    public function updateUserCreditsInSession($userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        if ($userId && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['credits'] = $this->getUserCredits($userId);
            error_log("Session credits updated to: " . $_SESSION['credits']);
        }
    }
}
?>
