<?php
/**
 * Mr ECU - File Manager Class (GUID System)
 * GUID tabanlı dosya yönetimi sınıfı
 */

class FileManager {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Dosya yükle (GUID ID ile)
    public function uploadFile($userId, $fileData, $vehicleData, $notes = '') {
        try {
            // GUID format kontrolü
            if (!isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı.'];
            }
            
            if (!isValidUUID($vehicleData['brand_id'])) {
                return ['success' => false, 'message' => 'Geçersiz marka ID formatı.'];
            }
            
            if (!isValidUUID($vehicleData['model_id'])) {
                return ['success' => false, 'message' => 'Geçersiz model ID formatı.'];
            }
            
            // Dosya kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası.'];
            }
            
            // Dosya boyut kontrolü
            if ($fileData['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB olabilir.'];
            }
            
            // Dosya uzantı kontrolü
            $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
                return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı. İzin verilen formatlar: ' . implode(', ', ALLOWED_EXTENSIONS)];
            }
            
            // Benzersiz dosya adı oluştur
            $fileName = $this->generateUniqueFileName($fileExtension);
            $uploadPath = UPLOAD_DIR . 'user_files/' . $fileName;
            
            // Upload dizinini oluştur
            $uploadDir = dirname($uploadPath);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Dosyayı taşı
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            }
            
            // UUID oluştur
            $uploadId = generateUUID();
            
            // Veritabanına kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO file_uploads 
                (id, user_id, brand_id, model_id, year, ecu_type, engine_code, gearbox_type, fuel_type, hp_power, nm_torque, filename, original_name, file_size, file_type, upload_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $uploadId,
                $userId,
                $vehicleData['brand_id'],
                $vehicleData['model_id'],
                $vehicleData['year'],
                $vehicleData['ecu_type'] ?? '',
                $vehicleData['engine_code'] ?? '',
                $vehicleData['gearbox_type'] ?? 'Manual',
                $vehicleData['fuel_type'] ?? 'Benzin',
                $vehicleData['hp_power'] ?? null,
                $vehicleData['nm_torque'] ?? null,
                $fileName,
                $fileData['name'],
                $fileData['size'],
                $fileData['type'],
                $notes
            ]);
            
            if ($result) {
                // Admin'e email gönder
                $this->sendAdminNotification($uploadId, $userId);
                
                // Log kaydı
                $user = new User($this->pdo);
                $user->logAction($userId, 'file_upload', 'Dosya yüklendi: ' . $fileData['name']);
                
                return ['success' => true, 'message' => 'Dosya başarıyla yüklendi. En kısa sürede işleme alınacaktır.', 'upload_id' => $uploadId];
            }
            
            // Veritabanı hatası durumunda dosyayı sil
            unlink($uploadPath);
            return ['success' => false, 'message' => 'Dosya kaydı sırasında hata oluştu.'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // Admin yanıt dosyası yükle (GUID ID ile)
    public function uploadResponseFile($uploadId, $adminId, $fileData, $creditsCharged, $notes = '') {
        try {
            // Debug başlangıç
            error_log("uploadResponseFile START - Upload ID: $uploadId, Admin ID: $adminId, Credits: $creditsCharged");
            
            // GUID format kontrolleri
            if (!isValidUUID($uploadId)) {
                error_log("uploadResponseFile ERROR: Invalid upload ID format: $uploadId");
                return ['success' => false, 'message' => 'Geçersiz upload ID formatı.'];
            }
            
            if (!isValidUUID($adminId)) {
                error_log("uploadResponseFile ERROR: Invalid admin ID format: $adminId");
                return ['success' => false, 'message' => 'Geçersiz admin ID formatı.'];
            }
            
            // Admin ID'nin veritabanında var olduğunu kontrol et
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE id = ? AND role = 'admin' AND status = 'active'");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                error_log("uploadResponseFile ERROR: Admin not found or not active - ID: $adminId");
                return ['success' => false, 'message' => 'Admin kullanıcısı bulunamadı veya aktif değil. Lütfen tekrar giriş yapın.'];
            }
            
            error_log("uploadResponseFile: Admin found - " . $admin['username']);
            
            // Orijinal yükleme kaydını kontrol et
            $upload = $this->getUploadById($uploadId);
            if (!$upload) {
                error_log("uploadResponseFile ERROR: Original upload not found - ID: $uploadId");
                return ['success' => false, 'message' => 'Orijinal dosya bulunamadı.'];
            }
            
            error_log("uploadResponseFile: Original upload found - User: {$upload['username']}");
            
            // Dosya kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası.'];
            }
            
            // Benzersiz dosya adı oluştur
            $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $fileName = $this->generateUniqueFileName($fileExtension);
            $uploadPath = UPLOAD_DIR . 'response_files/' . $fileName;
            
            // Upload dizinini oluştur
            $uploadDir = dirname($uploadPath);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Dosyayı taşı
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                error_log("uploadResponseFile ERROR: Failed to move uploaded file to $uploadPath");
                return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            }
            
            error_log("uploadResponseFile: File moved successfully to $uploadPath");
            
            $this->pdo->beginTransaction();
            error_log("uploadResponseFile: Transaction started");
            
            // UUID oluştur
            $responseId = generateUUID();
            error_log("uploadResponseFile: Response ID generated - $responseId");
            
            // Yanıt dosyasını kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO file_responses 
                (id, upload_id, admin_id, filename, original_name, file_size, file_type, credits_charged, admin_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insertParams = [
                $responseId,
                $uploadId,
                $adminId,
                $fileName,
                $fileData['name'],
                $fileData['size'],
                $fileData['type'],
                $creditsCharged,
                $notes
            ];
            
            error_log("uploadResponseFile: Attempting to insert file_responses - Params: " . json_encode($insertParams));
            
            $result = $stmt->execute($insertParams);
            
            if (!$result) {
                error_log("uploadResponseFile ERROR: Failed to insert file_responses - Error: " . json_encode($stmt->errorInfo()));
                throw new Exception('Yanıt dosyası kaydedilemedi.');
            }
            
            error_log("uploadResponseFile: file_responses inserted successfully");
            
            // Kullanıcıdan kredi düş (admin yanıt dosyası yüklerken)
            if ($creditsCharged > 0) {
                error_log("uploadResponseFile: Attempting to charge credits - Amount: $creditsCharged, User: {$upload['user_id']}");
                
                $user = new User($this->pdo);
                $creditResult = $user->addCreditDirectSimple($upload['user_id'], $creditsCharged, 'file_charge', 'Dosya işleme ücreti: ' . $fileData['name'], $responseId, 'file_response', $adminId);
                
                if (!$creditResult) {
                    error_log("uploadResponseFile ERROR: Credit charge failed for user {$upload['user_id']}, amount: $creditsCharged");
                    throw new Exception('Kullanıcının yeterli kredisi yok.');
                }
                
                error_log("uploadResponseFile: Credit charged successfully");
            } else {
                error_log("uploadResponseFile: No credits to charge (amount: $creditsCharged)");
            }
            
            // Orijinal yükleme durumunu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE file_uploads 
                SET status = 'completed', processed_date = CURRENT_TIMESTAMP, admin_notes = ? 
                WHERE id = ?
            ");
            
            error_log("uploadResponseFile: Updating original upload status to completed");
            
            $updateResult = $stmt->execute([$notes, $uploadId]);
            
            if (!$updateResult) {
                error_log("uploadResponseFile ERROR: Failed to update file_uploads status - Error: " . json_encode($stmt->errorInfo()));
                throw new Exception('Dosya durumu güncellenemedi.');
            }
            
            error_log("uploadResponseFile: Original upload status updated successfully");
            
            $this->pdo->commit();
            error_log("uploadResponseFile: Transaction committed successfully");
            
            // Kullanıcıya email gönder
            $this->sendUserNotification($upload['user_id'], $uploadId, $responseId);
            
            // Log kaydı
            $user = new User($this->pdo);
            $user->logAction($adminId, 'response_upload', 'Yanıt dosyası yüklendi: ' . $fileData['name']);
            
            error_log("uploadResponseFile SUCCESS: Response file uploaded successfully - ID: $responseId");
            
            return ['success' => true, 'message' => 'Yanıt dosyası başarıyla yüklendi.', 'response_id' => $responseId];
            
        } catch(PDOException $e) {
            // Sadece aktif transaction varsa rollback yap
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
                error_log("uploadResponseFile: Transaction rolled back due to PDO error");
            }
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
                error_log("uploadResponseFile: Uploaded file deleted due to error");
            }
            
            // Hata koduna göre özel mesajlar
            $errorMessage = 'Veritabanı hatası oluştu.';
            
            if ($e->getCode() == 23000) {
                // Foreign key constraint hatası
                if (strpos($e->getMessage(), 'admin_id') !== false) {
                    $errorMessage = 'Admin kullanıcısı geçersiz. Lütfen çıkış yapıp tekrar giriş yapın.';
                } elseif (strpos($e->getMessage(), 'upload_id') !== false) {
                    $errorMessage = 'Orijinal dosya bulunamadı.';
                } else {
                    $errorMessage = 'Veritabanı ilişki hatası: ' . $e->getMessage();
                }
            }
            
            error_log("uploadResponseFile PDO Error: " . $e->getMessage() . " - Admin ID: $adminId - Upload ID: $uploadId");
            return ['success' => false, 'message' => $errorMessage];
            
        } catch(Exception $e) {
            // Sadece aktif transaction varsa rollback yap
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
                error_log("uploadResponseFile: Transaction rolled back due to general error");
            }
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
                error_log("uploadResponseFile: Uploaded file deleted due to error");
            }
            
            error_log("uploadResponseFile General Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Dosya indir (GUID ID ile)
    public function downloadFile($fileId, $userId, $type = 'response') {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($fileId)) {
                return ['success' => false, 'message' => 'Geçersiz dosya ID formatı.'];
            }
            
            if (!isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı.'];
            }
            
            if ($type === 'response') {
                // Yanıt dosyası indirme
                $stmt = $this->pdo->prepare("
                    SELECT fr.*, fu.user_id, fu.original_name as upload_original_name
                    FROM file_responses fr
                    JOIN file_uploads fu ON fr.upload_id = fu.id
                    WHERE fr.id = ? AND fu.user_id = ?
                ");
                $stmt->execute([$fileId, $userId]);
                $file = $stmt->fetch();
                
                if (!$file) {
                    return ['success' => false, 'message' => 'Dosya bulunamadı veya erişim yetkiniz yok.'];
                }
                
                $filePath = UPLOAD_DIR . 'response_files/' . $file['filename'];
                
                // İndirme kaydını güncelle (kredi düşürmeden)
                $stmt = $this->pdo->prepare("UPDATE file_responses SET downloaded = TRUE, download_date = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$fileId]);
                
                // Log kaydı
                $user = new User($this->pdo);
                $user->logAction($userId, 'file_download', 'Dosya indirildi: ' . $file['original_name']);
                
            } else {
                // Orijinal dosya indirme (Admin)
                $stmt = $this->pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
                $stmt->execute([$fileId]);
                $file = $stmt->fetch();
                
                if (!$file) {
                    return ['success' => false, 'message' => 'Dosya bulunamadı.'];
                }
                
                $filePath = UPLOAD_DIR . 'user_files/' . $file['filename'];
            }
            
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'Dosya sistemde bulunamadı.'];
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'original_name' => $file['original_name'],
                'file_size' => filesize($filePath)
            ];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // Kullanıcının dosyalarını listele (GUID ID ile)
    public function getUserUploads($userId, $page = 1, $limit = 20, $status = '', $dateFrom = '', $dateTo = '') {
        try {
            // GUID format kontrolü
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // WHERE koşullarını oluştur
            $whereConditions = ['fu.user_id = ?'];
            $params = [$userId];
            
            // Durum filtresi
            if ($status) {
                $whereConditions[] = 'fu.status = ?';
                $params[] = $status;
            }
            
            // Tarih filtreleri
            if ($dateFrom) {
                $whereConditions[] = 'DATE(fu.upload_date) >= ?';
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereConditions[] = 'DATE(fu.upload_date) <= ?';
                $params[] = $dateTo;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // LIMIT ve OFFSET'i direkt SQL'e koyarak string sorunu çözüyoruz
            $stmt = $this->pdo->prepare("
                SELECT fu.*, b.name as brand_name, m.name as model_name,
                       (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as has_response,
                       (SELECT fr.id FROM file_responses fr WHERE fr.upload_id = fu.id LIMIT 1) as response_id
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                WHERE $whereClause
                ORDER BY fu.upload_date DESC
                LIMIT $limit OFFSET $offset
            ");
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("getUserUploads SQL hatası: " . json_encode($stmt->errorInfo()));
                return [];
            }
            
            $uploads = $stmt->fetchAll();
            return $uploads;
            
        } catch(PDOException $e) {
            error_log("getUserUploads PDO hatası: " . $e->getMessage());
            return [];
        }
    }
    
    // Tüm dosyaları listele (Admin) - GUID VERSİYON
    public function getAllUploads($page = 1, $limit = 50, $status = null, $dateFrom = '', $dateTo = '') {
        try {
            // WHERE koşullarını oluştur
            $whereConditions = [];
            $params = [];
            
            // Durum filtresi
            if ($status) {
                $whereConditions[] = 'fu.status = ?';
                $params[] = $status;
            }
            
            // Tarih filtreleri
            if ($dateFrom) {
                $whereConditions[] = 'DATE(fu.upload_date) >= ?';
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereConditions[] = 'DATE(fu.upload_date) <= ?';
                $params[] = $dateTo;
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fu.*, u.username, u.email, u.phone, b.name as brand_name, m.name as model_name,
                       0 as response_count
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                $whereClause
                ORDER BY fu.upload_date DESC
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            // Debug için hatayı göster
            error_log("getAllUploads error: " . $e->getMessage());
            return [];
        }
    }
    
    // Dosya detaylarını getir (GUID ID ile)
    public function getUploadById($id) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($id)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fu.*, u.username, u.email, u.phone, b.name as brand_name, m.name as model_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                WHERE fu.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Yanıt dosyalarını listele (GUID ID ile)
    public function getResponsesByUploadId($uploadId) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($uploadId)) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fr.*, u.username as admin_username
                FROM file_responses fr
                LEFT JOIN users u ON fr.admin_id = u.id
                WHERE fr.upload_id = ?
                ORDER BY fr.upload_date DESC
            ");
            $stmt->execute([$uploadId]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Araç markalarını getir
    public function getBrands() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Marka modelerini getir (GUID ID ile)
    public function getModelsByBrand($brandId) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($brandId)) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT id, name, year_start, year_end,
                       CASE 
                           WHEN year_start IS NOT NULL AND year_end IS NOT NULL THEN CONCAT(name, ' (', year_start, '-', year_end, ')')
                           WHEN year_start IS NOT NULL THEN CONCAT(name, ' (', year_start, '+')
                           ELSE name
                       END as display_name
                FROM models 
                WHERE brand_id = ? AND status = 'active' 
                ORDER BY name, year_start
            ");
            $stmt->execute([$brandId]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Dosya durumunu güncelle (GUID ID ile)
    public function updateUploadStatus($uploadId, $status, $adminNotes = '') {
        try {
            // GUID format kontrolü
            if (!isValidUUID($uploadId)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE file_uploads 
                SET status = ?, admin_notes = ?, processed_date = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $adminNotes, $uploadId]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Benzersiz dosya adı oluştur
    private function generateUniqueFileName($extension) {
        return date('Y/m/d/') . uniqid() . '_' . time() . '.' . $extension;
    }
    
    // Admin'e bildirim gönder (GUID ID ile)
    private function sendAdminNotification($uploadId, $userId) {
        try {
            $upload = $this->getUploadById($uploadId);
            if (!$upload) return false;
            
            $subject = SITE_NAME . ' - Yeni Dosya Yüklendi';
            
            $message = "
            <h2>Yeni Dosya Yüklendi</h2>
            <p><strong>Kullanıcı:</strong> {$upload['username']} ({$upload['email']})</p>
            <p><strong>Telefon:</strong> {$upload['phone']}</p>
            <p><strong>Araç:</strong> {$upload['brand_name']} {$upload['model_name']} ({$upload['year']})</p>
            <p><strong>ECU:</strong> {$upload['ecu_type']}</p>
            <p><strong>Motor Kodu:</strong> {$upload['engine_code']}</p>
            <p><strong>Dosya:</strong> {$upload['original_name']}</p>
            <p><strong>Yükleme Tarihi:</strong> " . formatDate($upload['upload_date']) . "</p>
            <p><strong>Not:</strong> {$upload['upload_notes']}</p>
            <p><a href='" . SITE_URL . "admin/uploads.php?id={$uploadId}'>Dosyayı İncele</a></p>
            ";
            
            return sendEmail(SITE_EMAIL, $subject, $message);
            
        } catch(Exception $e) {
            return false;
        }
    }
    
    // Kullanıcıya bildirim gönder (GUID ID ile)
    private function sendUserNotification($userId, $uploadId, $responseId) {
        try {
            $user = new User($this->pdo);
            $userData = $user->getUserById($userId);
            
            if (!$userData) return false;
            
            $subject = SITE_NAME . ' - Dosyanız Hazır';
            
            $message = "
            <h2>Dosyanız İşlendi</h2>
            <p>Merhaba {$userData['first_name']},</p>
            <p>Yüklemiş olduğunuz dosya işlenmiştir. İşlenmiş dosyayı panel üzerinden indirebilirsiniz.</p>
            <p><a href='" . SITE_URL . "user/files.php'>Dosyalarımı Görüntüle</a></p>
            <p>Teşekkürler,<br>" . SITE_NAME . " Ekibi</p>
            ";
            
            return sendEmail($userData['email'], $subject, $message);
            
        } catch(Exception $e) {
            return false;
        }
    }
    
    // Dosya sayıları (istatistik)
    public function getFileStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_files,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_files,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_files,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_files,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_files
                FROM file_uploads
            ");
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return [
                'total_files' => 0,
                'pending_files' => 0,
                'processing_files' => 0,
                'completed_files' => 0,
                'rejected_files' => 0
            ];
        }
    }
    
    // ==== REVİZE SİSTEMİ (GUID VERSİYON) ====
    
    // Revize talep et (GUID ID ile)
    public function requestRevision($uploadId, $userId, $notes) {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($uploadId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Dosyanın kullanıcıya ait ve tamamlanmış olduğunu kontrol et
            $stmt = $this->pdo->prepare("SELECT status FROM file_uploads WHERE id = ? AND user_id = ?");
            $stmt->execute([$uploadId, $userId]);
            $upload = $stmt->fetch();
            
            if (!$upload) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya size ait değil.'];
            }
            
            if ($upload['status'] !== 'completed') {
                return ['success' => false, 'message' => 'Sadece tamamlanmış dosyalar için revize talep edilebilir.'];
            }
            
            // Bekleyen revize talebi var mı kontrol et
            $stmt = $this->pdo->prepare("SELECT id FROM revisions WHERE upload_id = ? AND status = 'pending'");
            $stmt->execute([$uploadId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Bu dosya için zaten bekleyen bir revize talebi var.'];
            }
            
            // UUID oluştur
            $revisionId = generateUUID();
            
            // Revize talebi oluştur
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (id, upload_id, user_id, request_notes) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$revisionId, $uploadId, $userId, $notes]);
            
            // Upload'a revize sayısını güncelle
            $stmt = $this->pdo->prepare("UPDATE file_uploads SET revision_count = revision_count + 1 WHERE id = ?");
            $stmt->execute([$uploadId]);
            
            // Log kaydı
            $user = new User($this->pdo);
            $user->logAction($userId, 'revision_request', "Revize talep edildi: Upload #{$uploadId}");
            
            // Admin'e bildirim gönder
            $this->sendRevisionRequestNotification($uploadId, $userId);
            
            return ['success' => true, 'message' => 'Revize talebi başarıyla gönderildi.', 'revision_id' => $revisionId];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // Dosyanın revize geçmişini getir (GUID ID ile)
    public function getRevisionsByUploadId($uploadId) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($uploadId)) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT r.*, u.username, a.username as admin_username
                FROM revisions r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN users a ON r.admin_id = a.id
                WHERE r.upload_id = ?
                ORDER BY r.requested_at DESC
            ");
            $stmt->execute([$uploadId]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Tüm revize taleplerini getir (Admin) - GUID VERSİYON
    public function getAllRevisions($page = 1, $limit = 50, $status = null, $dateFrom = '', $dateTo = '') {
        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // WHERE koşullarını oluştur
            $whereConditions = [];
            $params = [];
            
            // Durum filtresi
            if ($status) {
                $whereConditions[] = 'r.status = ?';
                $params[] = $status;
            }
            
            // Tarih filtreleri
            if ($dateFrom) {
                $whereConditions[] = 'DATE(r.requested_at) >= ?';
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereConditions[] = 'DATE(r.requested_at) <= ?';
                $params[] = $dateTo;
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // LIMIT ve OFFSET'i direkt SQL'e koyarak string sorunu çözüyoruz
            $stmt = $this->pdo->prepare("
                SELECT r.*, u.username, u.email, fu.original_name, b.name as brand_name, m.name as model_name
                FROM revisions r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                $whereClause
                ORDER BY r.requested_at DESC
                LIMIT $limit OFFSET $offset
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("getAllRevisions PDO hatası: " . $e->getMessage());
            return [];
        }
    }
    
    // Revize talebini işle (Admin) - GUID VERSİYON
    public function processRevision($revisionId, $adminId, $status, $adminNotes = '', $creditsCharged = 0) {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($revisionId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            $this->pdo->beginTransaction();
            
            // Revize talebini güncelle
            $stmt = $this->pdo->prepare("
                UPDATE revisions 
                SET status = ?, admin_id = ?, admin_notes = ?, credits_charged = ?, completed_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminId, $adminNotes, $creditsCharged, $revisionId]);
            
            // Revize bilgilerini al
            $stmt = $this->pdo->prepare("SELECT upload_id, user_id FROM revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch();
            
            if ($revision && $status === 'completed' && $creditsCharged > 0) {
                // Kullanıcıdan kredi düş (transaction içinde)
                $user = new User($this->pdo);
                
                // Debug için kullanıcı kredi bilgisini al
                $userCredits = $user->getUserCredits($revision['user_id']);
                error_log("ProcessRevision - User ID: {$revision['user_id']}, User Credits: $userCredits, Credits Charged: $creditsCharged");
                
                $creditResult = $user->addCreditDirectSimple($revision['user_id'], $creditsCharged, 'file_charge', 'Revize ücreti', $revisionId, 'revision', $adminId);
                
                if (!$creditResult) {
                    throw new Exception("Kullanıcının yeterli kredisi yok. Mevcut kredi: $userCredits, Gerekli kredi: $creditsCharged");
                }
            }
            
            $this->pdo->commit();
            
            // Log kaydı
            $user = new User($this->pdo);
            $user->logAction($adminId, 'revision_process', "Revize talebi işlendi: #{$revisionId} - {$status}");
            
            // Kullanıcıya bildirim gönder
            $this->sendRevisionCompletedNotification($revisionId, $revision['user_id'], $status);
            
            return ['success' => true, 'message' => 'Revize talebi başarıyla işlendi.'];
            
        } catch(Exception $e) {
            // Sadece aktif transaction varsa rollback yap
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Kullanıcının revize taleplerini getir (GUID ID ile)
    public function getUserRevisions($userId, $page = 1, $limit = 20, $dateFrom = '', $dateTo = '') {
        try {
            // GUID format kontrolü
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // WHERE koşullarını oluştur
            $whereConditions = ['r.user_id = ?'];
            $params = [$userId];
            
            // Tarih filtreleri
            if ($dateFrom) {
                $whereConditions[] = 'DATE(r.requested_at) >= ?';
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereConditions[] = 'DATE(r.requested_at) <= ?';
                $params[] = $dateTo;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // LIMIT ve OFFSET'i direkt SQL'e koyarak string sorunu çözüyoruz
            $stmt = $this->pdo->prepare("
                SELECT r.*, fu.original_name, a.username as admin_username
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users a ON r.admin_id = a.id
                WHERE $whereClause
                ORDER BY r.requested_at DESC
                LIMIT $limit OFFSET $offset
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            error_log("getUserRevisions PDO hatası: " . $e->getMessage());
            return [];
        }
    }
    
    // ==== REVİZE DOSYA SİSTEMİ (GUID VERSİYON) ====
    
    // Revize dosyası yükle (Admin) - GUID VERSİYON
    public function uploadRevisionFile($revisionId, $adminId, $fileData, $adminNotes = '') {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($revisionId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Revize talebini kontrol et
            $stmt = $this->pdo->prepare("SELECT * FROM revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch();
            
            if (!$revision) {
                return ['success' => false, 'message' => 'Revize talebi bulunamadı.'];
            }
            
            // Dosya kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası.'];
            }
            
            // Benzersiz dosya adı oluştur
            $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $fileName = $this->generateUniqueFileName($fileExtension);
            $uploadPath = UPLOAD_DIR . 'revision_files/' . $fileName;
            
            // Upload dizinini oluştur
            $uploadDir = dirname($uploadPath);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Dosyayı taşı
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            }
            
            // UUID oluştur
            $fileId = generateUUID();
            
            // Veritabanına kaydet
            $stmt = $this->pdo->prepare("
                INSERT INTO revision_files 
                (id, revision_id, upload_id, admin_id, filename, original_name, file_size, file_type, admin_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $fileId,
                $revisionId,
                $revision['upload_id'],
                $adminId,
                $fileName,
                $fileData['name'],
                $fileData['size'],
                $fileData['type'],
                $adminNotes
            ]);
            
            if ($result) {
                // Log kaydı
                $user = new User($this->pdo);
                $user->logAction($adminId, 'revision_file_upload', 'Revize dosyası yüklendi: ' . $fileData['name']);
                
                return ['success' => true, 'message' => 'Revize dosyası başarıyla yüklendi.', 'file_id' => $fileId];
            }
            
            // Veritabanı hatası durumunda dosyayı sil
            unlink($uploadPath);
            return ['success' => false, 'message' => 'Dosya kaydı sırasında hata oluştu.'];
            
        } catch(PDOException $e) {
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // Upload için revize dosyalarını getir (GUID ID ile)
    public function getRevisionFilesByUploadId($uploadId) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($uploadId)) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT rf.*, r.status as revision_status, u.username as admin_username
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                LEFT JOIN users u ON rf.admin_id = u.id
                WHERE rf.upload_id = ?
                ORDER BY rf.upload_date DESC
            ");
            $stmt->execute([$uploadId]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Revize talebi için dosyaları getir (GUID ID ile)
    public function getRevisionFilesByRevisionId($revisionId) {
        try {
            // GUID format kontrolü
            if (!isValidUUID($revisionId)) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT rf.*, u.username as admin_username
                FROM revision_files rf
                LEFT JOIN users u ON rf.admin_id = u.id
                WHERE rf.revision_id = ?
                ORDER BY rf.upload_date DESC
            ");
            $stmt->execute([$revisionId]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
    
    // Revize dosyası indir (GUID ID ile)
    public function downloadRevisionFile($fileId, $userId) {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($fileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Dosyanın kullanıcıya ait olduğunu kontrol et
            $stmt = $this->pdo->prepare("
                SELECT rf.*, fu.user_id
                FROM revision_files rf
                JOIN file_uploads fu ON rf.upload_id = fu.id
                WHERE rf.id = ? AND fu.user_id = ?
            ");
            $stmt->execute([$fileId, $userId]);
            $file = $stmt->fetch();
            
            if (!$file) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya erişim yetkiniz yok.'];
            }
            
            $filePath = UPLOAD_DIR . 'revision_files/' . $file['filename'];
            
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'Dosya sistemde bulunamadı.'];
            }
            
            // İndirme kaydını güncelle
            $stmt = $this->pdo->prepare("UPDATE revision_files SET downloaded = TRUE, download_date = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$fileId]);
            
            // Log kaydı
            $user = new User($this->pdo);
            $user->logAction($userId, 'revision_file_download', 'Revize dosyası indirildi: ' . $file['original_name']);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'original_name' => $file['original_name'],
                'file_size' => filesize($filePath)
            ];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // Admin için revize dosyası indir (GUID ID ile)
    public function downloadRevisionFileAdmin($fileId, $adminId) {
        try {
            // GUID format kontrolleri
            if (!isValidUUID($fileId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Admin kontrolü
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || $admin['role'] !== 'admin') {
                return ['success' => false, 'message' => 'Admin yetkisi gereklidir.'];
            }
            
            // Revize dosyasını getir
            $stmt = $this->pdo->prepare("SELECT * FROM revision_files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();
            
            if (!$file) {
                return ['success' => false, 'message' => 'Revize dosyası bulunamadı.'];
            }
            
            $filePath = UPLOAD_DIR . 'revision_files/' . $file['filename'];
            
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'Dosya sistemde bulunamadı.'];
            }
            
            // Admin log kaydı
            $user = new User($this->pdo);
            $user->logAction($adminId, 'admin_revision_file_download', 'Admin revize dosyası indirdi: ' . $file['original_name']);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'original_name' => $file['original_name'],
                'file_size' => filesize($filePath)
            ];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
    
    // ==== BİLDİRİM SİSTEMİ ====
    
    // Revize talebi admin bildirim
    private function sendRevisionRequestNotification($uploadId, $userId) {
        try {
            $upload = $this->getUploadById($uploadId);
            if (!$upload) return false;
            
            $subject = SITE_NAME . ' - Yeni Revize Talebi';
            
            $message = "
            <h2>🔍 Yeni Revize Talebi</h2>
            <p><strong>Kullanıcı:</strong> {$upload['username']} ({$upload['email']})</p>
            <p><strong>Telefon:</strong> {$upload['phone']}</p>
            <p><strong>Araç:</strong> {$upload['brand_name']} {$upload['model_name']} ({$upload['year']})</p>
            <p><strong>Dosya:</strong> {$upload['original_name']}</p>
            <p><strong>Talep Tarihi:</strong> " . formatDate(date('Y-m-d H:i:s')) . "</p>
            <p><a href='" . SITE_URL . "admin/revisions.php'>Revize Taleplerini Görüntüle</a></p>
            <hr>
            <p style='color: #666; font-size: 12px;'>Bu email otomatik olarak gönderilmiştir.</p>
            ";
            
            return sendEmail(SITE_EMAIL, $subject, $message);
            
        } catch(Exception $e) {
            error_log("Revize talebi bildirim hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // Revize tamamlanma kullanıcı bildirim
    private function sendRevisionCompletedNotification($revisionId, $userId, $status) {
        try {
            $user = new User($this->pdo);
            $userData = $user->getUserById($userId);
            
            if (!$userData) return false;
            
            // Revize detaylarını al
            $stmt = $this->pdo->prepare("
                SELECT r.*, fu.original_name, b.name as brand_name, m.name as model_name
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                WHERE r.id = ?
            ");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch();
            
            if (!$revision) return false;
            
            $statusText = $status === 'completed' ? 'Tamamlandı' : 'Reddedildi';
            $statusIcon = $status === 'completed' ? '✅' : '❌';
            $statusColor = $status === 'completed' ? '#28a745' : '#dc3545';
            
            $subject = SITE_NAME . ' - Revize Talebi ' . $statusText;
            
            $message = "
            <h2>{$statusIcon} Revize Talebi {$statusText}</h2>
            <p>Merhaba {$userData['first_name']},</p>
            <p>Revize talebiniz işleme alınmıştır.</p>
            
            <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid {$statusColor}; margin: 20px 0;'>
                <p><strong>Dosya:</strong> {$revision['original_name']}</p>
                <p><strong>Araç:</strong> {$revision['brand_name']} {$revision['model_name']}</p>
                <p><strong>Durum:</strong> <span style='color: {$statusColor};'>{$statusText}</span></p>
                " . ($revision['credits_charged'] > 0 ? "<p><strong>Ücreti:</strong> {$revision['credits_charged']} Kredi</p>" : "") . "
                " . ($revision['admin_notes'] ? "<p><strong>Admin Notu:</strong> {$revision['admin_notes']}</p>" : "") . "
            </div>
            
            " . ($status === 'completed' ? 
                "<p><a href='" . SITE_URL . "user/files.php?id={$revision['upload_id']}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Dosyayı Görüntüle</a></p>" 
                : 
                "<p>Revize talebiniz reddedilmiştir. Lütfen detaylar için admin notlarını kontrol edin.</p>"
            ) . "
            
            <p>Teşekkürler,<br>" . SITE_NAME . " Ekibi</p>
            <hr>
            <p style='color: #666; font-size: 12px;'>Bu email otomatik olarak gönderilmiştir.</p>
            ";
            
            return sendEmail($userData['email'], $subject, $message);
            
        } catch(Exception $e) {
            error_log("Revize tamamlanma bildirim hatası: " . $e->getMessage());
            return false;
        }
    }
}
?>
