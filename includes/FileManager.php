<?php
/**
 * Mr ECU - File Manager Class (GUID System) - FIXED VERSION
 * GUID tabanlı dosya yönetimi sınıfı - Doğru tablo isimleri ile
 */

class FileManager {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Dosya istatistiklerini getir (Admin Dashboard için)
    public function getFileStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM file_uploads
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('getFileStats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'rejected' => 0
            ];
        }
    }
    
    // Revize taleplerini getir
    public function getAllRevisions($page = 1, $limit = 20, $status = '', $dateFrom = '', $dateTo = '') {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "";
            $params = [];
            
            if ($status) {
                $whereClause .= " WHERE r.status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom && $dateTo) {
                $operator = $status ? " AND" : " WHERE";
                $whereClause .= "$operator DATE(r.requested_at) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            } elseif ($dateFrom) {
                $operator = $status ? " AND" : " WHERE";
                $whereClause .= "$operator DATE(r.requested_at) >= ?";
                $params[] = $dateFrom;
            } elseif ($dateTo) {
                $operator = $status ? " AND" : " WHERE";
                $whereClause .= "$operator DATE(r.requested_at) <= ?";
                $params[] = $dateTo;
            }
            
            // LIMIT ve OFFSET için direkt sayısal değerler kullan
            $query = "
                SELECT r.*, fu.original_name, 
                       u.username, u.email, u.first_name, u.last_name,
                       a.username as admin_username,
                       b.name as brand_name, m.name as model_name
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN users a ON r.admin_id = a.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                {$whereClause}
                ORDER BY r.requested_at DESC
                LIMIT $limit OFFSET $offset
            ";
            
            if (!empty($params)) {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
            } else {
                $stmt = $this->pdo->query($query);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getAllRevisions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Tüm yüklenen dosyaları getir (Admin Dashboard için)
    public function getAllUploads($page = 1, $limit = 20, $status = '', $userId = '') {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "";
            $params = [];
            
            if ($status) {
                $whereClause .= " WHERE fu.status = ?";
                $params[] = $status;
            }
            
            if ($userId) {
                $whereClause .= ($status ? " AND" : " WHERE") . " fu.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fu.*, 
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                {$whereClause}
                ORDER BY fu.upload_date DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getAllUploads error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Dosya yükle (GUID ID ile)
    public function uploadFile($userId, $fileData, $vehicleData, $notes = '') {
        try {
            // GUID format kontrolü
            if (!isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı.'];
            }
            
            // Dosya kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Dosya yüklenmedi.'];
            }
            
            // Dosya boyutu kontrolü
            if ($fileData['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Dosya boyutu çok büyük.'];
            }
            
            // UUID oluştur
            $uploadId = generateUUID();
            $fileName = generateUUID() . '_' . basename($fileData['name']);
            $uploadPath = UPLOAD_PATH . $fileName;
            
            // Dosyayı taşı
            if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                // Veritabanına kaydet
                $stmt = $this->pdo->prepare("
                    INSERT INTO file_uploads (id, user_id, original_name, filename, file_size, file_path, status, notes, upload_date)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $uploadId,
                    $userId,
                    $fileData['name'],
                    $fileName,
                    $fileData['size'],
                    $uploadPath,
                    $notes
                ]);
                
                if ($result) {
                    return ['success' => true, 'message' => 'Dosya başarıyla yüklendi.', 'upload_id' => $uploadId];
                }
            }
            
            return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            
        } catch(PDOException $e) {
            error_log('uploadFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    // Dosya durumunu güncelle
    public function updateUploadStatus($uploadId, $status, $adminNotes = '') {
        try {
            if (!isValidUUID($uploadId)) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE file_uploads 
                SET status = ?, admin_notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            return $stmt->execute([$status, $adminNotes, $uploadId]);
            
        } catch(PDOException $e) {
            error_log('updateUploadStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Kullanıcının dosyalarını getir
    public function getUserUploads($userId, $page = 1, $limit = 10) {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM file_uploads 
                WHERE user_id = ? 
                ORDER BY upload_date DESC 
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserUploads error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Dosya detaylarını getir
    public function getUploadById($uploadId) {
        try {
            if (!isValidUUID($uploadId)) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fu.*, u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                WHERE fu.id = ?
            ");
            
            $stmt->execute([$uploadId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUploadById error: ' . $e->getMessage());
            return null;
        }
    }
    
    // Yanıt dosyası yükle
    public function uploadResponseFile($uploadId, $fileData, $creditsCharged, $responseNotes = '') {
        try {
            if (!isValidUUID($uploadId)) {
                return ['success' => false, 'message' => 'Geçersiz dosya ID formatı.'];
            }
            
            // Dosya kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Yanıt dosyası yüklenmedi.'];
            }
            
            $fileName = generateUUID() . '_response_' . basename($fileData['name']);
            $uploadPath = UPLOAD_PATH . 'responses/' . $fileName;
            
            // Dizin yoksa oluştur
            if (!is_dir(UPLOAD_PATH . 'responses/')) {
                mkdir(UPLOAD_PATH . 'responses/', 0755, true);
            }
            
            if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                // Dosya durumunu güncelle
                $stmt = $this->pdo->prepare("
                    UPDATE file_uploads 
                    SET status = 'completed', response_file = ?, response_notes = ?, 
                        credits_charged = ?, completed_at = NOW() 
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$fileName, $responseNotes, $creditsCharged, $uploadId]);
                
                if ($result && $creditsCharged > 0) {
                    // Kredi düş
                    $upload = $this->getUploadById($uploadId);
                    if ($upload) {
                        $user = new User($this->pdo);
                        $user->addCreditDirectSimple($upload['user_id'], $creditsCharged, 'file_charge', 'Dosya işleme ücreti: ' . $upload['original_name']);
                    }
                }
                
                return ['success' => true, 'message' => 'Yanıt dosyası başarıyla yüklendi ve kredi düşüldü.'];
            }
            
            return ['success' => false, 'message' => 'Yanıt dosyası yüklenemedi.'];
            
        } catch(PDOException $e) {
            error_log('uploadResponseFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    

    
    // Revize talebi güncelle
    public function updateRevisionStatus($revisionId, $adminId, $status, $adminNotes = '', $creditsCharged = 0) {
        try {
            if (!isValidUUID($revisionId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Revize talebini getir
            $stmt = $this->pdo->prepare("SELECT * FROM revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch();
            
            if (!$revision) {
                return ['success' => false, 'message' => 'Revize talebi bulunamadı.'];
            }
            
            // Kredi düşür (eğer belirtilmişse)
            if ($status === 'completed' && $creditsCharged > 0) {
                $user = new User($this->pdo);
                $creditResult = $user->addCreditDirectSimple($revision['user_id'], $creditsCharged, 'revision_charge', 'Revize işlemi: ' . $revisionId);
                
                if (!$creditResult) {
                    return ['success' => false, 'message' => 'Yetersiz kredi. İşlem iptal edildi.'];
                }
            }
            
            // Revize durumunu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE revisions 
                SET admin_id = ?, status = ?, admin_notes = ?, credits_charged = ?, 
                    completed_at = CASE WHEN ? IN ('completed', 'rejected') THEN NOW() ELSE completed_at END
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$adminId, $status, $adminNotes, $creditsCharged, $status, $revisionId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Revize durumu başarıyla güncellendi.'];
            }
            
            return ['success' => false, 'message' => 'Revize durumu güncellenemedi.'];
            
        } catch(PDOException $e) {
            error_log('updateRevisionStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
}
?>
