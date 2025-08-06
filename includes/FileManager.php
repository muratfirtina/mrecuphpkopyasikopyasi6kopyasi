<?php
/**
 * Mr ECU - File Manager Class (GUID System) - CLEAN VERSION
 * GUID tabanlı dosya yönetimi sınıfı - Duplicate metodlar temizlendi
 */

// UUID oluşturma fonksiyonu
if (!function_exists('generateUUID')) {
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

class FileManager {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Araç markalarını getir
    public function getBrands() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM brands ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getBrands error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Markaya göre modelleri getir
     */
    public function getModelsByBrand($brandId) {
        try {
            if (!isValidUUID($brandId)) {
                error_log("Geçersiz brand_id: " . $brandId);
                return [];
            }
            $stmt = $this->pdo->prepare("SELECT * FROM models WHERE brand_id = ? ORDER BY name ASC");
            $stmt->execute([$brandId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Modeller alınamadı (brand_id: $brandId): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Modele göre serileri getir
     */
    public function getSeriesByModel($modelId) {
        try {
            if (!isValidUUID($modelId)) {
                error_log("Geçersiz model_id: " . $modelId);
                return [];
            }
            $stmt = $this->pdo->prepare("SELECT * FROM series WHERE model_id = ? ORDER BY name ASC");
            $stmt->execute([$modelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Seriler alınamadı (model_id: $modelId): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Serie göre motorları getir
     */
    public function getEnginesBySeries($seriesId) {
        try {
            if (!isValidUUID($seriesId)) {
                error_log("Geçersiz series_id: " . $seriesId);
                return [];
            }
            $stmt = $this->pdo->prepare("SELECT * FROM engines WHERE series_id = ? ORDER BY name ASC");
            $stmt->execute([$seriesId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Motorlar alınamadı (series_id: $seriesId): " . $e->getMessage());
            return [];
        }
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
    
    // Kullanıcının sadece ana dosyalarını getir (yanıt dosyaları hariç)
    public function getUserUploads($userId, $page = 1, $limit = 15, $status = '', $search = '', $filterId = '') {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE fu.user_id = ?";
            $params = [$userId];
            
            // ID ile filtreleme (bildirimden gelen dosya için)
            if ($filterId && isValidUUID($filterId)) {
                $whereClause .= " AND fu.id = ?";
                $params[] = $filterId;
            }
            
            if ($status) {
                $whereClause .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $whereClause .= " AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ? OR fu.plate LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // LIMIT ve OFFSET'i güvenli şekilde string olarak ekle
            $sql = "
                SELECT fu.*, 
                       b.name as brand_name, m.name as model_name,
                       s.name as series_name, e.name as engine_name,
                       d.name as device_name, ecu.name as ecu_name
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                LEFT JOIN devices d ON fu.device_id = d.id
                LEFT JOIN ecus ecu ON fu.ecu_id = ecu.id
                {$whereClause}
                ORDER BY fu.upload_date DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->pdo->prepare($sql);
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserUploads error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının dosya sayısını getir
    public function getUserUploadCount($userId, $status = '', $search = '', $filterId = '') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $whereClause = "WHERE fu.user_id = ?";
            $params = [$userId];
            
            // ID ile filtreleme (bildirimden gelen dosya için)
            if ($filterId && isValidUUID($filterId)) {
                $whereClause .= " AND fu.id = ?";
                $params[] = $filterId;
            }
            
            if ($status) {
                $whereClause .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $whereClause .= " AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ? OR fu.plate LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                LEFT JOIN devices d ON fu.device_id = d.id
                LEFT JOIN ecus ecu ON fu.ecu_id = ecu.id
                {$whereClause}
            ");
            
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch(PDOException $e) {
            error_log('getUserUploadCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Kullanıcının dosya istatistiklerini getir
    public function getUserFileStats($userId) {
        try {
            if (!isValidUUID($userId)) {
                return ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM file_uploads
                WHERE user_id = ?
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
            
        } catch(PDOException $e) {
            error_log('getUserFileStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
        }
    }
    
    // Dosya ID'sine göre upload kaydını getir
    public function getUploadById($uploadId) {
        try {
            if (!isValidUUID($uploadId)) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fu.*, 
                       b.name as brand_name, m.name as model_name,
                       s.name as series_name, e.name as engine_name,
                       d.name as device_name, ecu.name as ecu_name
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                LEFT JOIN devices d ON fu.device_id = d.id
                LEFT JOIN ecus ecu ON fu.ecu_id = ecu.id
                WHERE fu.id = ?
            ");
            
            $stmt->execute([$uploadId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUploadById error: ' . $e->getMessage());
            return null;
        }
    }
    
    // Ana dosyaya ait yanıt dosyalarını getir
    public function getFileResponses($uploadId, $userId) {
        try {
            if (!isValidUUID($uploadId) || !isValidUUID($userId)) {
                return [];
            }
            
            // Önce dosyanın kullanıcıya ait olup olmadığını kontrol et
            $stmt = $this->pdo->prepare("SELECT id FROM file_uploads WHERE id = ? AND user_id = ?");
            $stmt->execute([$uploadId, $userId]);
            if (!$stmt->fetch()) {
                return [];
            }
            
            // Yanıt dosyalarını getir
            $stmt = $this->pdo->prepare("
                SELECT fr.*, 
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       'response' as file_type
                FROM file_responses fr
                LEFT JOIN users a ON fr.admin_id = a.id
                WHERE fr.upload_id = ?
                ORDER BY fr.upload_date DESC
            ");
            
            $stmt->execute([$uploadId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getFileResponses error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Ana dosyaya ait revize taleplerini getir
    public function getFileRevisions($uploadId, $userId) {
        try {
            if (!isValidUUID($uploadId) || !isValidUUID($userId)) {
                return [];
            }
            
            // Önce dosyanın kullanıcıya ait olup olmadığını kontrol et
            $stmt = $this->pdo->prepare("SELECT id FROM file_uploads WHERE id = ? AND user_id = ?");
            $stmt->execute([$uploadId, $userId]);
            if (!$stmt->fetch()) {
                return [];
            }
            
            // Revize taleplerini getir
            $stmt = $this->pdo->prepare("
                SELECT r.*, 
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM revisions r
                LEFT JOIN users a ON r.admin_id = a.id
                WHERE r.upload_id = ?
                ORDER BY r.requested_at DESC
            ");
            
            $stmt->execute([$uploadId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getFileRevisions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Dosya yükle (GUID ID ile) - EKSIK METOD EKLENDİ
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
            
            if ($fileData['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $fileData['error']];
            }
            
            // Dosya boyut kontrolü
            if ($fileData['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum ' . formatFileSize(MAX_FILE_SIZE) . ' olabilir.'];
            }
            
            // Dosya uzantı kontrolü
            $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
                return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı. İzin verilen formatlar: ' . implode(', ', ALLOWED_EXTENSIONS)];
            }
            
            // Benzersiz dosya adı oluştur
            $fileName = $this->generateUniqueFileName($fileExtension);
            $uploadPath = UPLOAD_PATH . 'user_files/' . $fileName;
            
            // Upload dizinini oluştur
            $uploadDir = dirname($uploadPath);
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    return ['success' => false, 'message' => 'Upload dizini oluşturulamadı.'];
                }
            }
            
            // Dosyayı taşı
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            }
            
            // UUID oluştur
            $uploadId = generateUUID();
            
            // Veritabanına kaydet - YENİ GUID ALANLARI İLE
            $stmt = $this->pdo->prepare("
                INSERT INTO file_uploads (
                    id, user_id, brand_id, model_id, series_id, engine_id, device_id, ecu_id,
                    year, plate, kilometer, gearbox_type, fuel_type, 
                    hp_power, nm_torque, original_name, filename, 
                    file_size, status, upload_notes, upload_date, file_path,
                    credits_charged, revision_count, notified
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), ?, 0, 0, 0)
            ");
            
            $result = $stmt->execute([
                $uploadId,
                $userId,
                $vehicleData['brand_id'],
                $vehicleData['model_id'],
                $vehicleData['series_id'],
                $vehicleData['engine_id'],
                $vehicleData['device_id'],
                $vehicleData['ecu_id'],
                $vehicleData['year'],
                $vehicleData['plate'],
                $vehicleData['kilometer'],
                $vehicleData['gearbox_type'],
                $vehicleData['fuel_type'],
                $vehicleData['hp_power'],
                $vehicleData['nm_torque'],
                $fileData['name'],
                $fileName,
                $fileData['size'],
                $notes,
                $uploadPath
            ]);
            
            if ($result) {
                // Bildirim sistemi entegrasyonu
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    $notificationManager->notifyFileUpload($uploadId, $userId, $fileData['name'], $vehicleData);
                } catch(Exception $e) {
                    error_log('Notification send error after file upload: ' . $e->getMessage());
                    // Bildirim hatası dosya yükleme işlemini etkilemesin
                }
                
                return [
                    'success' => true, 
                    'message' => 'Dosya başarıyla yüklendi! Admin ekibimiz en kısa sürede inceleyecektir.',
                    'upload_id' => $uploadId
                ];
            } else {
                // Dosyayı sil
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                return ['success' => false, 'message' => 'Veritabanı kaydı oluşturulamadı.'];
            }
            
        } catch (Exception $e) {
            error_log('uploadFile error: ' . $e->getMessage());
            // Dosyayı sil (eğer oluşturulduysa)
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $e->getMessage()];
        }
    }
    
    // Benzersiz dosya adı oluştur
    private function generateUniqueFileName($extension) {
        return generateUUID() . '.' . $extension;
    }
    
    // Ana dosya için revize talebi gönder
    public function requestRevision($uploadId, $userId, $revisionNotes) {
        try {
            if (!isValidUUID($uploadId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Dosya kontrolü
            $upload = $this->getUploadById($uploadId);
            if (!$upload || $upload['user_id'] !== $userId) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya size ait değil.'];
            }
            
            if ($upload['status'] !== 'completed') {
                return ['success' => false, 'message' => 'Sadece tamamlanmış dosyalar için revize talep edebilirsiniz.'];
            }
            
            // Daha önce bekleyen revize talebi var mı kontrol et
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM revisions 
                WHERE upload_id = ? AND status = 'pending'
            ");
            $stmt->execute([$uploadId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing['count'] > 0) {
                return ['success' => false, 'message' => 'Bu dosya için zaten bekleyen bir revize talebi bulunuyor.'];
            }
            
            // Revize talebi oluştur
            $revisionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (
                    id, upload_id, user_id, request_notes, status, requested_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([$revisionId, $uploadId, $userId, $revisionNotes]);
            
            if ($result) {
                // Bildirim sistemi entegrasyonu
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    $notificationManager->notifyRevisionRequest($revisionId, $userId, $uploadId, $upload['original_name'], $revisionNotes);
                } catch(Exception $e) {
                    error_log('Notification send error after revision request: ' . $e->getMessage());
                    // Bildirim hatası revize talep işlemini etkilemesin
                }
                
                return ['success' => true, 'message' => 'Revize talebi başarıyla gönderildi. Admin ekibimiz en kısa sürede inceleyecektir.'];
            } else {
                return ['success' => false, 'message' => 'Revize talebi oluşturulurken hata oluştu.'];
            }
            
        } catch(PDOException $e) {
            error_log('requestRevision error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    // Yanıt dosyası için revize talebi gönder
    public function requestResponseRevision($responseId, $userId, $revisionNotes) {
        try {
            if (!isValidUUID($responseId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Yanıt dosyası kontrolü ve yetki doğrulama
            $stmt = $this->pdo->prepare("
                SELECT fr.*, fu.user_id 
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                WHERE fr.id = ? AND fu.user_id = ?
            ");
            $stmt->execute([$responseId, $userId]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$response) {
                return ['success' => false, 'message' => 'Yanıt dosyası bulunamadı veya yetkiniz yok.'];
            }
            
            // Daha önce bekleyen revize talebi var mı kontrol et
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM revisions 
                WHERE response_id = ? AND status = 'pending'
            ");
            $stmt->execute([$responseId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing['count'] > 0) {
                return ['success' => false, 'message' => 'Bu yanıt dosyası için zaten bekleyen bir revize talebi bulunuyor.'];
            }
            
            // Revize talebi oluştur
            $revisionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (
                    id, response_id, upload_id, user_id, request_notes, status, requested_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([$revisionId, $responseId, $response['upload_id'], $userId, $revisionNotes]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Yanıt dosyası için revize talebi başarıyla gönderildi. Admin ekibimiz dosyanızı yeniden değerlendirecektir.'];
            } else {
                return ['success' => false, 'message' => 'Revize talebi oluşturulurken hata oluştu.'];
            }
            
        } catch(PDOException $e) {
            error_log('requestResponseRevision error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    // Revizyon dosyası için revize talebi gönder
    public function requestRevisionFileRevision($revisionFileId, $userId, $revisionNotes) {
        try {
            if (!isValidUUID($revisionFileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Revizyon dosyası kontrolü ve yetki doğrulama
            $stmt = $this->pdo->prepare("
                SELECT rf.*, r.user_id, r.upload_id, r.id as original_revision_id
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                WHERE rf.id = ? AND r.user_id = ?
            ");
            $stmt->execute([$revisionFileId, $userId]);
            $revisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revisionFile) {
                return ['success' => false, 'message' => 'Revizyon dosyası bulunamadı veya yetkiniz yok.'];
            }
            
            // Daha önce bu revizyon dosyası için bekleyen revize talebi var mı kontrol et
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM revisions 
                WHERE revision_file_id = ? AND status = 'pending'
            ");
            $stmt->execute([$revisionFileId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing['count'] > 0) {
                return ['success' => false, 'message' => 'Bu revizyon dosyası için zaten bekleyen bir revize talebi bulunuyor.'];
            }
            
            // Yeni revize talebi oluştur
            $newRevisionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (
                    id, upload_id, user_id, revision_file_id, parent_revision_id, 
                    request_notes, status, requested_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $newRevisionId, 
                $revisionFile['upload_id'], 
                $userId, 
                $revisionFileId,
                $revisionFile['original_revision_id'],
                $revisionNotes
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Revizyon dosyası için yeni revize talebi başarıyla gönderildi. Admin ekibimiz dosyanızı yeniden değerlendirecektir.'];
            } else {
                return ['success' => false, 'message' => 'Revize talebi oluşturulurken hata oluştu.'];
            }
            
        } catch(PDOException $e) {
            error_log('requestRevisionFileRevision error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    public function downloadFile($fileId, $userId, $type = 'upload') {
        try {
            if (!isValidUUID($fileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            if ($type === 'response') {
                // Yanıt dosyası indirme
                $stmt = $this->pdo->prepare("
                    SELECT fr.*, fu.user_id
                    FROM file_responses fr
                    LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                    WHERE fr.id = ? AND fu.user_id = ?
                ");
                $stmt->execute([$fileId, $userId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$file) {
                    return ['success' => false, 'message' => 'Dosya bulunamadı veya yetkiniz yok.'];
                }
                
                $filePath = UPLOAD_PATH . 'response_files/' . $file['filename'];
                $originalName = $file['original_name'];
                
            } else {
                // Normal dosya indirme
                $stmt = $this->pdo->prepare("
                    SELECT * FROM file_uploads 
                    WHERE id = ? AND user_id = ? AND status = 'completed'
                ");
                $stmt->execute([$fileId, $userId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$file) {
                    return ['success' => false, 'message' => 'Dosya bulunamadı veya henüz tamamlanmamış.'];
                }
                
                // Dosya yolunu düzelt - user_files klasörü ekle
                $filePath = UPLOAD_PATH . 'user_files/' . $file['filename'];
                $originalName = $file['original_name'];
            }
            
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'Fiziksel dosya bulunamadı.'];
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'original_name' => $originalName,
                'file_size' => filesize($filePath)
            ];
            
        } catch(PDOException $e) {
            error_log('downloadFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    // Admin için tüm yüklemeleri getir
    public function getAllUploads($page = 1, $limit = 20, $status = '', $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($status) {
                $whereClause .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $whereClause .= " AND (fu.original_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // LIMIT ve OFFSET'i güvenli şekilde string olarak ekle
            $sql = "
                SELECT fu.*, u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name,
                       s.name as series_name, e.name as engine_name,
                       d.name as device_name, ecu.name as ecu_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                LEFT JOIN devices d ON fu.device_id = d.id
                LEFT JOIN ecus ecu ON fu.ecu_id = ecu.id
                $whereClause
                ORDER BY fu.upload_date DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getAllUploads error: ' . $e->getMessage());
            return [];
        }
    }
    
    // WORKING VERSION - Admin için tüm yüklemeleri getir (Yedek metod)
    public function getAllUploadsWorking($page = 1, $limit = 20, $status = '', $search = '') {
        try {
            // Basit sorgu ile başla
            $sql = "SELECT fu.*, u.username, u.email, u.first_name, u.last_name, b.name as brand_name, m.name as model_name FROM file_uploads fu LEFT JOIN users u ON fu.user_id = u.id LEFT JOIN brands b ON fu.brand_id = b.id LEFT JOIN models m ON fu.model_id = m.id ORDER BY fu.upload_date DESC";
            
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sayfalama uygula
            $offset = ($page - 1) * $limit;
            return array_slice($results, $offset, $limit);
            
        } catch(PDOException $e) {
            error_log('getAllUploadsWorking error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının tüm dosyalarını getir (user paneli için)
    public function getUserAllFiles($userId, $page = 1, $limit = 15, $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE fu.user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $whereClause .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $whereClause .= " AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ? OR fu.plate LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // LIMIT ve OFFSET'i güvenli şekilde string olarak ekle
            $sql = "
                SELECT fu.*, b.name as brand_name, m.name as model_name,
                       s.name as series_name, e.name as engine_name,
                       d.name as device_name, ecu.name as ecu_name
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
                LEFT JOIN devices d ON fu.device_id = d.id
                LEFT JOIN ecus ecu ON fu.ecu_id = ecu.id
                $whereClause
                ORDER BY fu.upload_date DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserAllFiles error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının revize taleplerini getir (Tüm Tipler - Ana, Yanıt, Revizyon Dosyaları)
    public function getUserRevisions($userId, $page = 1, $limit = 10, $dateFrom = '', $dateTo = '', $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE r.user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $whereClause .= " AND r.status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom) {
                $whereClause .= " AND DATE(r.requested_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND DATE(r.requested_at) <= ?";
                $params[] = $dateTo;
            }
            
            if ($search) {
                $whereClause .= " AND (r.request_notes LIKE ? OR fu.original_name LIKE ? OR fr.original_name LIKE ? OR rf.original_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Tüm revize tiplerini destekleyen gelişmiş sorgu
            $sql = "
                SELECT r.*, 
                       -- Ana dosya bilgisi (upload_id için)
                       fu.original_name, fu.filename, fu.file_size,
                       -- Yanıt dosyası bilgisi (response_id için)
                       fr.original_name as response_original_name, fr.filename as response_filename,
                       -- Revizyon dosyası bilgisi (revision_file_id için)
                       rf.original_name as revision_file_original_name, rf.filename as revision_filename,
                       -- Ana revizyon talebi bilgisi (parent için)
                       parent_r.request_notes as parent_request_notes,
                       -- Admin bilgisi
                       u.username as admin_username, u.first_name as admin_first_name, u.last_name as admin_last_name,
                       -- Revize tipi belirleme
                       CASE 
                           WHEN r.revision_file_id IS NOT NULL THEN 'revision_file'
                           WHEN r.response_id IS NOT NULL THEN 'response'
                           ELSE 'upload'
                       END as revision_type,
                       -- Gösterilecek dosya adı belirleme
                       CASE 
                           WHEN r.revision_file_id IS NOT NULL THEN CONCAT('REV: ', rf.original_name)
                           WHEN r.response_id IS NOT NULL THEN CONCAT('RESP: ', fr.original_name)
                           ELSE fu.original_name
                       END as display_file_name
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN file_responses fr ON r.response_id = fr.id
                LEFT JOIN revision_files rf ON r.revision_file_id = rf.id
                LEFT JOIN revisions parent_r ON r.parent_revision_id = parent_r.id
                LEFT JOIN users u ON r.admin_id = u.id
                $whereClause
                ORDER BY r.requested_at DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Sonuçları işle ve görüntüleme için hazırla
            foreach ($results as &$result) {
                // original_name alanını display_file_name ile güncelle
                $result['original_name'] = $result['display_file_name'];
                
                // Debug bilgisi ekle
                $result['debug_info'] = [
                    'revision_type' => $result['revision_type'],
                    'upload_id' => $result['upload_id'],
                    'response_id' => $result['response_id'],
                    'revision_file_id' => $result['revision_file_id'],
                    'parent_revision_id' => $result['parent_revision_id']
                ];
            }
            
            return $results;
            
        } catch(PDOException $e) {
            error_log('getUserRevisions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının revize talep sayısını getir (Tüm Tipler Dahil)
    public function getUserRevisionCount($userId, $dateFrom = '', $dateTo = '', $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $whereClause = "WHERE r.user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $whereClause .= " AND r.status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom) {
                $whereClause .= " AND DATE(r.requested_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND DATE(r.requested_at) <= ?";
                $params[] = $dateTo;
            }
            
            if ($search) {
                $whereClause .= " AND (r.request_notes LIKE ? OR fu.original_name LIKE ? OR fr.original_name LIKE ? OR rf.original_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN file_responses fr ON r.response_id = fr.id
                LEFT JOIN revision_files rf ON r.revision_file_id = rf.id
                $whereClause
            ");
            
            $stmt->execute($params);
            return $stmt->fetchColumn();
            
        } catch(PDOException $e) {
            error_log('getUserRevisionCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Admin için tüm revize taleplerini getir
    public function getAllRevisions($page = 1, $limit = 20, $status = '', $dateFrom = '', $dateTo = '', $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($status) {
                $whereClause .= " AND r.status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom) {
                $whereClause .= " AND DATE(r.requested_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $whereClause .= " AND DATE(r.requested_at) <= ?";
                $params[] = $dateTo;
            }
            
            if ($search) {
                $whereClause .= " AND (r.request_notes LIKE ? OR fu.original_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR b.name LIKE ? OR m.name LIKE ? OR fu.plate LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // LIMIT ve OFFSET'i güvenli şekilde string olarak ekle
            $sql = "
                SELECT r.*, fu.original_name, fu.filename, fu.file_size, fu.plate, fu.year,
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name,
                       fr.original_name as response_original_name
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN file_responses fr ON r.response_id = fr.id
                $whereClause
                ORDER BY r.requested_at DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getAllRevisions error: ' . $e->getMessage());
            return [];
        }
    }
    

    
    // Yanıt dosyası yükleme metodu
    public function uploadResponseFile($uploadId, $file, $creditsCharged = 0, $responseNotes = '') {
        try {
            if (!isValidUUID($uploadId)) {
                return ['success' => false, 'message' => 'Geçersiz upload ID formatı.'];
            }
            
            // Dosya uploadını kontrol et
            $stmt = $this->pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
            $stmt->execute([$uploadId]);
            $upload = $stmt->fetch();
            
            if (!$upload) {
                return ['success' => false, 'message' => 'Ana dosya bulunamadı.'];
            }
            
            // Dosya kontrolleri
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'message' => 'Geçersiz dosya yüklemesi.'];
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $file['error']];
            }
            
            if ($file['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum: ' . formatFileSize(MAX_FILE_SIZE)];
            }
            
            // Dosya uzantısı kontrolü
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı. İzin verilen: ' . implode(', ', ALLOWED_EXTENSIONS)];
            }
            
            // Yükleme dizinini kontrol et
            $uploadDir = UPLOAD_PATH . 'response_files/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    return ['success' => false, 'message' => 'Yükleme dizini oluşturulamadı.'];
                }
            }
            
            // Benzersiz dosya adı oluştur
            $filename = generateUUID() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            // Dosyayı taşı
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return ['success' => false, 'message' => 'Dosya taşınamadı.'];
            }
            
            // Veritabanına kaydet
            $responseId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO file_responses (
                    id, upload_id, admin_id, original_name, filename, file_size, 
                    credits_charged, admin_notes, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $responseId,
                $uploadId,
                $_SESSION['user_id'],
                $file['name'],
                $filename,
                $file['size'],
                $creditsCharged,
                $responseNotes
            ]);
            
            if ($result) {
                // Ana dosyanın durumunu 'completed' yap
                $stmt = $this->pdo->prepare("UPDATE file_uploads SET status = 'completed' WHERE id = ?");
                $stmt->execute([$uploadId]);
                
                // Kredi düşür
                if ($creditsCharged > 0) {
                    $userClass = new User($this->pdo);
                    $creditResult = $userClass->deductCredits($upload['user_id'], $creditsCharged, "Yanıt dosyası için kredi düşüldü: " . $uploadId);
                    
                    if (!$creditResult['success']) {
                        error_log('Kredi düşme hatası: ' . $creditResult['message']);
                    }
                }
                
                return [
                    'success' => true, 
                    'message' => 'Yanıt dosyası başarıyla yüklendi.',
                    'response_id' => $responseId
                ];
            } else {
                // Dosyayı sil
                unlink($filePath);
                return ['success' => false, 'message' => 'Veritabanı kaydı oluşturulamadı.'];
            }
            
        } catch (Exception $e) {
            error_log('uploadResponseFile error: ' . $e->getMessage());
            // Dosyayı sil (eğer oluşturulduysa)
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $e->getMessage()];
        }
    }
    

    
    // ============ REVİZYON DOSYASI YÖNETİMİ ============
    
    /**
     * Admin tarafından revizyon dosyası yükleme
     * @param string $revisionId - Revizyon talebi ID
     * @param array $file - Yüklenen dosya bilgileri
     * @param string $adminId - Admin kullanıcı ID
     * @param float $creditsCharged - Düşürülecek kredi miktarı
     * @param string $adminNotes - Admin notları
     * @return array - Başarı durumu ve mesaj
     */
    public function uploadRevisionFile($revisionId, $file, $adminId, $creditsCharged = 0, $adminNotes = '') {
        try {
            if (!isValidUUID($revisionId)) {
                return ['success' => false, 'message' => 'Geçersiz revizyon ID formatı.'];
            }
            
            // Revizyon talebini kontrol et
            $stmt = $this->pdo->prepare("SELECT * FROM revisions WHERE id = ?");
            $stmt->execute([$revisionId]);
            $revision = $stmt->fetch();
            
            if (!$revision) {
                return ['success' => false, 'message' => 'Revizyon talebi bulunamadı.'];
            }
            
            if ($revision['status'] !== 'in_progress') {
                return ['success' => false, 'message' => 'Sadece işlemdeki revizyon talepleri için dosya yüklenebilir.'];
            }
            
            // Dosya kontrolleri
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'message' => 'Geçersiz dosya yüklemesi.'];
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $file['error']];
            }
            
            if ($file['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum: ' . formatFileSize(MAX_FILE_SIZE)];
            }
            
            // Dosya uzantısı kontrolü
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı. İzin verilen: ' . implode(', ', ALLOWED_EXTENSIONS)];
            }
            
            // Revizyon dosyaları dizinini kontrol et
            $uploadDir = UPLOAD_PATH . 'revision_files/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    return ['success' => false, 'message' => 'Revizyon dosyaları dizini oluşturulamadı.'];
                }
            }
            
            // Benzersiz dosya adı oluştur
            $filename = generateUUID() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            // Dosyayı taşı
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return ['success' => false, 'message' => 'Dosya taşınamadı.'];
            }
            
            // revision_files tablosuna kaydet
            $revisionFileId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO revision_files (
                    id, revision_id, upload_id, admin_id, original_name, filename, 
                    file_size, file_type, admin_notes, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $revisionFileId,
                $revisionId,
                $revision['upload_id'],
                $adminId,
                $file['name'],
                $filename,
                $file['size'],
                $extension,
                $adminNotes
            ]);
            
            if ($result) {
                // Kredi düşür (eğer belirtilmişse)
                if ($creditsCharged > 0) {
                    $userClass = new User($this->pdo);
                    $creditResult = $userClass->deductCredits($revision['user_id'], $creditsCharged, "Revizyon dosyası için kredi düşüldü: " . $revisionId);
                    
                    if (!$creditResult['success']) {
                        // Kredi düşürülemezse dosyayı ve kayda sil
                        unlink($filePath);
                        $this->pdo->prepare("DELETE FROM revision_files WHERE id = ?")->execute([$revisionFileId]);
                        return ['success' => false, 'message' => 'Kredi düşürülemedi: ' . $creditResult['message']];
                    }
                }
                
                // Revizyon durumunu 'completed' yap
                $updateResult = $this->updateRevisionStatus(
                    $revisionId, 
                    $adminId, 
                    'completed', 
                    'Revizyon dosyası yüklendi: ' . $adminNotes,
                    $creditsCharged
                );
                
                if ($updateResult['success']) {
                    return [
                        'success' => true, 
                        'message' => 'Revizyon dosyası başarıyla yüklendi ve revizyon talebi tamamlandı.',
                        'revision_file_id' => $revisionFileId
                    ];
                } else {
                    // Dosyayı ve kaydı sil
                    unlink($filePath);
                    $this->pdo->prepare("DELETE FROM revision_files WHERE id = ?")->execute([$revisionFileId]);
                    return ['success' => false, 'message' => 'Revizyon durumu güncellenemedi: ' . $updateResult['message']];
                }
            } else {
                // Dosyayı sil
                unlink($filePath);
                return ['success' => false, 'message' => 'Veritabanı kaydı oluşturulamadı.'];
            }
            
        } catch (Exception $e) {
            error_log('uploadRevisionFile error: ' . $e->getMessage());
            // Dosyayı sil (eğer oluşturulduysa)
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            return ['success' => false, 'message' => 'Revizyon dosyası yükleme hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Revizyon talebine ait dosyaları getir
     * @param string $revisionId - Revizyon talebi ID
     * @param string $userId - Kullanıcı ID (yetki kontrolü için)
     * @return array - Revizyon dosyaları listesi
     */
    public function getRevisionFiles($revisionId, $userId = null) {
        try {
            if (!isValidUUID($revisionId)) {
                return [];
            }
            
            // Eğer userId verilmişse, revizyonun kullanıcıya ait olup olmadığını kontrol et
            if ($userId && !isValidUUID($userId)) {
                return [];
            }
            
            $whereClause = "WHERE rf.revision_id = ?";
            $params = [$revisionId];
            
            if ($userId) {
                $whereClause .= " AND r.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT rf.*, 
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       r.status as revision_status, r.requested_at
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                LEFT JOIN users a ON rf.admin_id = a.id
                $whereClause
                ORDER BY rf.upload_date DESC
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getRevisionFiles error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Upload ID'ye göre tüm revizyon dosyalarını getir
     * @param string $uploadId - Ana dosya ID
     * @param string $userId - Kullanıcı ID (yetki kontrolü için)
     * @return array - Revizyon dosyaları listesi
     */
    public function getUploadRevisionFiles($uploadId, $userId = null) {
        try {
            if (!isValidUUID($uploadId)) {
                return [];
            }
            
            // Eğer userId verilmişse, dosyanın kullanıcıya ait olup olmadığını kontrol et
            if ($userId && !isValidUUID($userId)) {
                return [];
            }
            
            $whereClause = "WHERE rf.upload_id = ?";
            $params = [$uploadId];
            
            if ($userId) {
                $whereClause .= " AND r.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT rf.*, r.request_notes, r.status as revision_status, r.requested_at,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                LEFT JOIN users a ON rf.admin_id = a.id
                $whereClause
                ORDER BY rf.upload_date DESC
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUploadRevisionFiles error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Revizyon dosyası indirme kontrolü ve dosya bilgilerini getir
     * @param string $revisionFileId - Revizyon dosya ID
     * @param string $userId - Kullanıcı ID (yetki kontrolü için)
     * @return array - Dosya bilgileri veya hata
     */
    public function downloadRevisionFile($revisionFileId, $userId) {
        try {
            if (!isValidUUID($revisionFileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Revizyon dosyasını ve yetki kontrolünü yap
            $stmt = $this->pdo->prepare("
                SELECT rf.*, r.user_id as revision_user_id, r.status as revision_status
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                WHERE rf.id = ? AND r.user_id = ?
            ");
            $stmt->execute([$revisionFileId, $userId]);
            $revisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revisionFile) {
                return ['success' => false, 'message' => 'Revizyon dosyası bulunamadı veya yetkiniz yok.'];
            }
            
            if ($revisionFile['revision_status'] !== 'completed') {
                return ['success' => false, 'message' => 'Sadece tamamlanan revizyon dosyaları indirilebilir.'];
            }
            
            // Fiziksel dosya kontrolü
            $filePath = UPLOAD_PATH . 'revision_files/' . $revisionFile['filename'];
            
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'Fiziksel dosya bulunamadı.'];
            }
            
            // İndirme kaydını güncelle
            $this->pdo->prepare("
                UPDATE revision_files 
                SET downloaded = TRUE, download_date = NOW() 
                WHERE id = ?
            ")->execute([$revisionFileId]);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'original_name' => $revisionFile['original_name'],
                'file_size' => $revisionFile['file_size'],
                'file_type' => $revisionFile['file_type']
            ];
            
        } catch(PDOException $e) {
            error_log('downloadRevisionFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    /**
     * Revizyon ID'sine göre revizyon detaylarını getir
     * @param string $revisionId - Revizyon ID
     * @param string $userId - Kullanıcı ID (yetki kontrolü için)
     * @return array|null - Revizyon detayları
     */
    public function getRevisionDetail($revisionId, $userId = null) {
        try {
            if (!isValidUUID($revisionId)) {
                return null;
            }
            
            $whereClause = "WHERE r.id = ?";
            $params = [$revisionId];
            
            if ($userId && isValidUUID($userId)) {
                $whereClause .= " AND r.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT r.*, fu.original_name, fu.filename, fu.file_size,
                       u.username, u.first_name, u.last_name, u.email,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       b.name as brand_name, m.name as model_name,
                       fr.original_name as response_original_name, fr.filename as response_filename
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN users a ON r.admin_id = a.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN file_responses fr ON r.response_id = fr.id
                $whereClause
            ");
            
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getRevisionDetail error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Revizyon istatistiklerini getir (Admin Dashboard için)
     * @return array - Revizyon istatistikleri
     */
    public function getRevisionStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM revisions
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('getRevisionStats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'rejected' => 0
            ];
        }
    }
    
    /**
     * Dosya upload durumunu güncelle (Bildirim Entegrasyonu ile)
     * @param string $uploadId - Upload ID
     * @param string $status - Yeni durum
     * @param string $adminNotes - Admin notları
     * @return bool - Başarı durumu
     */
    public function updateUploadStatus($uploadId, $status, $adminNotes = '') {
        try {
            if (!isValidUUID($uploadId)) {
                error_log('updateUploadStatus: Geçersiz UUID - ' . $uploadId);
                return false;
            }
            
            // Önce dosyayı al
            $upload = $this->getUploadById($uploadId);
            if (!$upload) {
                error_log('updateUploadStatus: Dosya bulunamadı - ' . $uploadId);
                return false;
            }
            
            // Durumu güncelle
            $stmt = $this->pdo->prepare("
                UPDATE file_uploads 
                SET status = ?, admin_notes = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$status, $adminNotes, $uploadId]);
            
            if ($result) {
                // Bildirim sistemi entegrasyonu
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    $notificationManager->notifyFileStatusUpdate($uploadId, $upload['user_id'], $upload['original_name'], $status, $adminNotes);
                } catch(Exception $e) {
                    error_log('Notification send error after status update: ' . $e->getMessage());
                    // Bildirim hatası durum güncelleme işlemini etkilemesin
                }
                
                error_log('updateUploadStatus: Başarılı - ' . $uploadId . ' durumu ' . $status . ' olarak güncellendi');
                return true;
            } else {
                error_log('updateUploadStatus: Başarısız - ' . $uploadId . ' durum güncellenemedi');
                return false;
            }
            
        } catch(PDOException $e) {
            error_log('updateUploadStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revizyon durumunu güncelle (Bildirim Entegrasyonu ile)
     * @param string $revisionId - Revizyon ID
     * @param string $adminId - Admin kullanıcı ID
     * @param string $status - Yeni durum
     * @param string $adminNotes - Admin notları
     * @param float $creditsCharged - Düşürülecek kredi miktarı
     * @return array - Başarı durumu ve mesaj
     */
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
            
            // Revize durumunu güncelle
            $updateFields = [];
            $updateParams = [];
            
            $updateFields[] = "status = ?";
            $updateParams[] = $status;
            
            $updateFields[] = "admin_id = ?";
            $updateParams[] = $adminId;
            
            if ($adminNotes) {
                $updateFields[] = "admin_notes = ?";
                $updateParams[] = $adminNotes;
            }
            
            if ($creditsCharged > 0) {
                $updateFields[] = "credits_charged = ?";
                $updateParams[] = $creditsCharged;
            }
            
            if ($status === 'completed') {
                $updateFields[] = "completed_at = NOW()";
            }
            
            // WHERE koşulu için revisionId'yi en sona ekle
            $updateParams[] = $revisionId;
            
            $updateQuery = "UPDATE revisions SET " . implode(", ", $updateFields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($updateQuery);
            $result = $stmt->execute($updateParams);
            
            if ($result) {
                // Eğer kredi düşürülecekse ve status in_progress ise krediyi düşür
                if ($creditsCharged > 0 && $status === 'in_progress') {
                    $userClass = new User($this->pdo);
                    $creditResult = $userClass->deductCredits($revision['user_id'], $creditsCharged, "Revize talebi için kredi düşüldü: " . $revisionId);
                    
                    if (!$creditResult['success']) {
                        // Kredi düşürülemezse revize durumunu geri al
                        $stmt = $this->pdo->prepare("UPDATE revisions SET status = 'pending', admin_id = NULL, admin_notes = NULL, credits_charged = 0 WHERE id = ?");
                        $stmt->execute([$revisionId]);
                        
                        return ['success' => false, 'message' => 'Kredi düşürülemedi: ' . $creditResult['message']];
                    }
                }
                
                // Bildirim sistemi entegrasyonu
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    $notificationManager->notifyRevisionResponse($revisionId, $revision['user_id'], $revision['upload_id'], $status, $adminNotes);
                } catch(Exception $e) {
                    error_log('Notification send error after revision status update: ' . $e->getMessage());
                    // Bildirim hatası revizyon güncelleme işlemini etkilemesin
                }
                
                return ['success' => true, 'message' => 'Revize durumu başarıyla güncellendi.'];
            } else {
                return ['success' => false, 'message' => 'Revize durumu güncellenemedi.'];
            }
            
        } catch(PDOException $e) {
            error_log('updateRevisionStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu: ' . $e->getMessage()];
        }
    }
    
}
?>
