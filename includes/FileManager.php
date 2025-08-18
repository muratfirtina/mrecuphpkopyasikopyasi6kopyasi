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
            $whereClause = "WHERE fu.user_id = ? AND (fu.is_cancelled IS NULL OR fu.is_cancelled = 0)";
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
            
            $whereClause = "WHERE fu.user_id = ? AND (fu.is_cancelled IS NULL OR fu.is_cancelled = 0)";
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
                WHERE user_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
            
        } catch(PDOException $e) {
            error_log('getUserFileStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
        }
    }

    public function getUserAllFiles($userId, $page = 1, $limit = 15, $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE fu.user_id = ? AND (fu.is_cancelled IS NULL OR fu.is_cancelled = 0)";
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
            
            // Yanıt dosyalarını getir (İptal edilmiş dosyaları hariç tut)
            $stmt = $this->pdo->prepare("
                SELECT fr.*, 
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       'response' as file_type
                FROM file_responses fr
                LEFT JOIN users a ON fr.admin_id = a.id
                WHERE fr.upload_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
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
    
    /**
     * Revizyon talebi oluştur (Bildirim Entegrasyonu ile)
     * @param string $uploadId - Upload ID
     * @param string $userId - Kullanıcı ID
     * @param string $requestNotes - Talep notları
     * @param string $responseId - Yanıt dosyası ID (opsiyonel)
     * @return array - Başarı durumu ve mesaj
     */
    public function requestRevision($uploadId, $userId, $requestNotes, $responseId = null) {
        try {
            if (!isValidUUID($uploadId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            if (empty(trim($requestNotes))) {
                return ['success' => false, 'message' => 'Revize talebi notları gereklidir.'];
            }
            
            // Upload'ın kullanıcıya ait olup olmadığını kontrol et
            $stmt = $this->pdo->prepare("SELECT * FROM file_uploads WHERE id = ? AND user_id = ?");
            $stmt->execute([$uploadId, $userId]);
            $upload = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$upload) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya yetkiniz yok.'];
            }
            
            // Mevcut bekleyen revizyon var mı kontrol et
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM revisions WHERE upload_id = ? AND status = 'pending'");
            $stmt->execute([$uploadId]);
            $pendingCount = $stmt->fetchColumn();
            
            if ($pendingCount > 0) {
                return ['success' => false, 'message' => 'Bu dosya için zaten bekleyen bir revizyon talebi bulunuyor.'];
            }
            
            // Yeni revizyon talebi oluştur
            $revisionId = generateUUID();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (id, upload_id, user_id, request_notes, response_id, status, requested_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $result = $stmt->execute([
                $revisionId,
                $uploadId,
                $userId,
                $requestNotes,
                $responseId
            ]);
            
            if ($result) {
                // Admin'lere bildirim gönder
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    $notificationResult = $notificationManager->notifyRevisionRequest(
                        $revisionId, 
                        $userId, 
                        $uploadId, 
                        $upload['original_name'], 
                        $requestNotes
                    );
                    
                    if ($notificationResult) {
                        error_log("Revizyon talebi bildirimi gönderildi: $revisionId");
                    } else {
                        error_log("Revizyon talebi bildirimi gönderilemedi: $revisionId");
                    }
                } catch(Exception $e) {
                    error_log('Revizyon bildirim hatası: ' . $e->getMessage());
                    // Bildirim hatası revizyon oluşturmayı etkilemesin
                }
                
                return [
                    'success' => true, 
                    'message' => 'Revizyon talebi başarıyla gönderildi. Admin tarafından incelenecektir.',
                    'revision_id' => $revisionId
                ];
            } else {
                return ['success' => false, 'message' => 'Revizyon talebi oluşturulamadı.'];
            }
            
        } catch(PDOException $e) {
            error_log('requestRevision error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    /**
     * Yanıt dosyası için revizyon talebi oluştur
     * @param string $responseId - Yanıt dosyası ID
     * @param string $userId - Kullanıcı ID
     * @param string $requestNotes - Talep notları
     * @return array - Başarı durumu ve mesaj
     */
    public function requestResponseRevision($responseId, $userId, $requestNotes) {
        try {
            if (!isValidUUID($responseId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Response dosyasının kullanıcıya ait olup olmadığını kontrol et
            $stmt = $this->pdo->prepare("
                SELECT fr.*, fu.id as upload_id, fu.original_name 
                FROM file_responses fr 
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id 
                WHERE fr.id = ? AND fu.user_id = ?
            ");
            $stmt->execute([$responseId, $userId]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$response) {
                return ['success' => false, 'message' => 'Yanıt dosyası bulunamadı veya yetkiniz yok.'];
            }
            
            // Ana upload için revizyon talebi oluştur ve response_id'yi belirt
            return $this->requestRevision($response['upload_id'], $userId, $requestNotes, $responseId);
            
        } catch(PDOException $e) {
            error_log('requestResponseRevision error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    /**
     * Revizyon dosyası için revizyon talebi oluştur
     * @param string $revisionFileId - Revizyon dosyası ID
     * @param string $userId - Kullanıcı ID
     * @param string $requestNotes - Talep notları
     * @return array - Başarı durumu ve mesaj
     */
    public function requestRevisionFileRevision($revisionFileId, $userId, $requestNotes) {
        try {
            if (!isValidUUID($revisionFileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Revizyon dosyasının kullanıcıya ait olup olmadığını kontrol et
            $stmt = $this->pdo->prepare("
                SELECT rf.*, r.user_id, r.upload_id, fu.original_name 
                FROM revision_files rf 
                LEFT JOIN revisions r ON rf.revision_id = r.id 
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id 
                WHERE rf.id = ? AND r.user_id = ?
            ");
            $stmt->execute([$revisionFileId, $userId]);
            $revisionFile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$revisionFile) {
                return ['success' => false, 'message' => 'Revizyon dosyası bulunamadı veya yetkiniz yok.'];
            }
            
            // Ana upload için revizyon talebi oluştur
            return $this->requestRevision($revisionFile['upload_id'], $userId, $requestNotes);
            
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
                       s.name as series_name, e.name as engine_name,
                       fr.original_name as response_original_name
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN series s ON fu.series_id = s.id
                LEFT JOIN engines e ON fu.engine_id = e.id
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
    
    /**
     * Admin tarafından yanıt dosyası yükle (Gelişmiş Bildirim Sistemi ile)
     * @param string $uploadId - Ana dosya ID'si
     * @param array $fileData - $_FILES['response_file'] verisi
     * @param float $creditsCharged - Düşürülecek kredi miktarı
     * @param string $responseNotes - Admin yanıt notları
     * @return array - Başarı durumu ve mesaj
     */
    public function uploadResponseFile($uploadId, $fileData, $creditsCharged = 0, $responseNotes = '') {
        try {
            error_log('uploadResponseFile started - UploadId: ' . $uploadId . ', Credits: ' . $creditsCharged);
            
            if (!isValidUUID($uploadId)) {
                return ['success' => false, 'message' => 'Geçersiz dosya ID formatı.'];
            }
            
            // Ana dosya kontrolü
            $upload = $this->getUploadById($uploadId);
            if (!$upload) {
                return ['success' => false, 'message' => 'Ana dosya bulunamadı.'];
            }
            
            // Dosya yükleme kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Yanıt dosyası yüklenmedi.'];
            }
            
            if ($fileData['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $fileData['error']];
            }
            
            // Dosya boyutu kontrolü
            if ($fileData['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum ' . formatFileSize(MAX_FILE_SIZE) . ' olabilir.'];
            }
            
            // Benzersiz dosya adı oluştur
            $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $fileName = generateUUID() . '_response.' . $fileExtension;
            $uploadDir = UPLOAD_PATH . 'response_files/';
            $uploadPath = $uploadDir . $fileName;
            
            // Upload dizinini oluştur
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    return ['success' => false, 'message' => 'Upload dizini oluşturulamadı.'];
                }
            }
            
            // Dosyayı taşı
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                return ['success' => false, 'message' => 'Dosya upload edilemedi.'];
            }
            
            error_log('uploadResponseFile: File moved successfully to ' . $uploadPath);
            
            // Transaction başlat
            $this->pdo->beginTransaction();
            
            try {
                // file_responses tablosuna kaydet
                $responseId = generateUUID();
                $stmt = $this->pdo->prepare("
                    INSERT INTO file_responses (
                        id, upload_id, admin_id, filename, original_name, 
                        file_size, credits_charged, admin_notes, upload_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $responseId,
                    $uploadId,
                    $_SESSION['user_id'] ?? null,
                    $fileName,
                    $fileData['name'],
                    $fileData['size'],
                    $creditsCharged,
                    $responseNotes
                ]);
                
                if (!$result) {
                    throw new Exception('Response dosyası kaydı oluşturulamadı.');
                }
                
                error_log('uploadResponseFile: Response record created with ID: ' . $responseId);
                
                // Ana dosya durumunu 'completed' olarak güncelle (BİLDİRİM GÖNDERMEDEn)
                // updateUploadStatus metodu kullan ama sendNotification = false
                $statusUpdateResult = $this->updateUploadStatus($uploadId, 'completed', $responseNotes, false);
                
                if (!$statusUpdateResult) {
                    throw new Exception('Ana dosya durumu güncellenemedi.');
                }
                
                error_log('uploadResponseFile: Main file status updated to completed');
                
                // Kredi düşürme işlemi (eğer belirtilmişse)
                if ($creditsCharged > 0) {
                    // User sınıfını dahil et
                    if (!class_exists('User')) {
                        require_once __DIR__ . '/User.php';
                    }
                    
                    $user = new User($this->pdo);
                    
                    // Ters kredi sistemi - kredi_used'ı artır
                    $creditResult = $user->addCreditDirectSimple(
                        $upload['user_id'], 
                        $creditsCharged, 
                        'file_charge', 
                        'Dosya işleme ücreti: ' . $upload['original_name'] . ' (Yanıt: ' . $fileData['name'] . ')'
                    );
                    
                    if (!$creditResult) {
                        throw new Exception('Kredi düşürme işlemi başarısız.');
                    }
                    
                    error_log('uploadResponseFile: Credits charged successfully: ' . $creditsCharged);
                }
                
                // Bildirim sistemi entegrasyonu
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    
                    // Yanıt dosyası yüklendiği için kullanıcıya bildirim gönder
                    $notificationTitle = "Dosya yanıtlandı";
                    $notificationMessage = $upload['original_name'] . " dosyanız için yanıt dosyası yüklendi: " . $fileData['name'];
                    
                    if ($responseNotes) {
                        $notificationMessage .= " Admin notu: " . $responseNotes;
                    }
                    
                    $actionUrl = "files.php?id=" . $uploadId;
                    
                    $notificationResult = $notificationManager->createNotification(
                        $upload['user_id'],
                        'file_response_uploaded',
                        $notificationTitle,
                        $notificationMessage,
                        $uploadId,
                        'file_upload',
                        $actionUrl
                    );
                    
                    if ($notificationResult) {
                        error_log('uploadResponseFile: Notification sent successfully to user: ' . $upload['user_id']);
                    } else {
                        error_log('uploadResponseFile: Notification failed to send to user: ' . $upload['user_id']);
                    }
                    
                } catch(Exception $e) {
                    error_log('uploadResponseFile: Notification send error: ' . $e->getMessage());
                    // Bildirim hatası ana işlemi etkilemesin
                }
                
                // Transaction commit
                $this->pdo->commit();
                
                error_log('uploadResponseFile: Transaction committed successfully');
                
                return [
                    'success' => true, 
                    'message' => 'Yanıt dosyası başarıyla yüklendi ve kullanıcıya bildirim gönderildi.',
                    'response_id' => $responseId
                ];
                
            } catch(Exception $e) {
                // Transaction rollback
                $this->pdo->rollBack();
                
                // Yüklenen dosyayı sil
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                
                error_log('uploadResponseFile: Transaction rolled back: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Yanıt dosyası yükleme hatası: ' . $e->getMessage()];
            }
            
        } catch(Exception $e) {
            error_log('uploadResponseFile general error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()];
        }
    }
    
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
            AND (rf.is_cancelled IS NULL OR rf.is_cancelled = 0)
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
                AND (rf.is_cancelled IS NULL OR rf.is_cancelled = 0)
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
            
            // Revizyon dosyasını ve yetki kontrolünü yap (iptal edilmemiş dosyalar)
            $stmt = $this->pdo->prepare("
                SELECT rf.*, r.user_id as revision_user_id, r.status as revision_status
                FROM revision_files rf
                LEFT JOIN revisions r ON rf.revision_id = r.id
                WHERE rf.id = ? AND r.user_id = ?
                AND (rf.is_cancelled IS NULL OR rf.is_cancelled = 0)
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
     * @param bool $sendNotification - Bildirim gönderilsin mi?
     * @return bool - Başarı durumu
     */
    public function updateUploadStatus($uploadId, $status, $adminNotes = '', $sendNotification = true) {
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
                // Sadece bildirim gönderilmesi isteniyorsa gönder
                if ($sendNotification) {
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
    
    /**
     * Ek dosya yükleme (Admin veya User tarafından)
     * @param string $relatedFileId - İlgili dosya ID (upload, response veya revision)
     * @param string $relatedFileType - İlgili dosya tipi
     * @param string $senderId - Gönderen ID
     * @param string $senderType - Gönderen tipi (user/admin)
     * @param string $receiverId - Alıcı ID
     * @param string $receiverType - Alıcı tipi (user/admin)
     * @param array $fileData - Dosya bilgileri ($_FILES['file'])
     * @param string $notes - Notlar
     * @param float $credits - Ücret (sadece admin için)
     * @return array - Başarı durumu ve mesaj
     */
    public function uploadAdditionalFile($relatedFileId, $relatedFileType, $senderId, $senderType, $receiverId, $receiverType, $fileData, $notes = '', $credits = 0) {
        try {
            // ID kontrolleri
            if (!isValidUUID($relatedFileId) || !isValidUUID($senderId) || !isValidUUID($receiverId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
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
            $fileName = generateUUID() . '_additional.' . $fileExtension;
            $uploadDir = UPLOAD_PATH . 'additional_files/';
            $uploadPath = $uploadDir . $fileName;
            
            // Upload dizinini oluştur
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    return ['success' => false, 'message' => 'Upload dizini oluşturulamadı.'];
                }
            }
            
            // Dosyayı taşı
            if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            }
            
            // Transaction başlat
            $this->pdo->beginTransaction();
            
            try {
                // Veritabanına kaydet
                $additionalFileId = generateUUID();
                $stmt = $this->pdo->prepare("
                    INSERT INTO additional_files (
                        id, related_file_id, related_file_type, sender_id, sender_type,
                        receiver_id, receiver_type, original_name, file_name, file_path,
                        file_size, file_type, notes, credits, upload_date, is_read
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)
                ");
                
                $result = $stmt->execute([
                    $additionalFileId,
                    $relatedFileId,
                    $relatedFileType,
                    $senderId,
                    $senderType,
                    $receiverId,
                    $receiverType,
                    $fileData['name'],
                    $fileName,
                    $uploadPath,
                    $fileData['size'],
                    $fileExtension,
                    $notes,
                    $credits
                ]);
                
                if (!$result) {
                    throw new Exception('Veritabanı kaydı oluşturulamadı.');
                }
                
                // Eğer admin user'a dosya gönderdiyse ve ücret belirlenmişse kredi düşür
                if ($senderType === 'admin' && $receiverType === 'user' && $credits > 0) {
                    if (!class_exists('User')) {
                        require_once __DIR__ . '/User.php';
                    }
                    
                    $user = new User($this->pdo);
                    
                    // Ters kredi sistemi - kredi_used'ı artır
                    $creditResult = $user->addCreditDirectSimple(
                        $receiverId,
                        $credits,
                        'additional_file_charge',
                        'Ek dosya ücreti: ' . $fileData['name'] . ($notes ? ' - ' . $notes : '')
                    );
                    
                    if (!$creditResult) {
                        throw new Exception('Kredi düşürme işlemi başarısız.');
                    }
                }
                
                // Bildirim gönder
                try {
                    if (!class_exists('NotificationManager')) {
                        require_once __DIR__ . '/NotificationManager.php';
                    }
                    
                    $notificationManager = new NotificationManager($this->pdo);
                    
                    $notificationTitle = $senderType === 'admin' ? 'Admin size dosya gönderdi' : 'Kullanıcı size dosya gönderdi';
                    $notificationMessage = 'Yeni bir dosya aldınız: ' . $fileData['name'];
                    if ($notes) {
                        $notificationMessage .= ' - Not: ' . $notes;
                    }
                    if ($credits > 0) {
                        $notificationMessage .= ' (Ücret: ' . $credits . ' kredi)';
                    }
                    
                    $actionUrl = $receiverType === 'admin' ? 
                        'admin/file-detail.php?id=' . $relatedFileId : 
                        'user/file-detail.php?id=' . $relatedFileId;
                    
                    $notificationManager->createNotification(
                        $receiverId,
                        'additional_file_received',
                        $notificationTitle,
                        $notificationMessage,
                        $relatedFileId,
                        'additional_file',
                        $actionUrl
                    );
                } catch(Exception $e) {
                    error_log('Additional file notification error: ' . $e->getMessage());
                }
                
                // Transaction commit
                $this->pdo->commit();
                
                return [
                    'success' => true,
                    'message' => 'Dosya başarıyla gönderildi.',
                    'file_id' => $additionalFileId
                ];
                
            } catch(Exception $e) {
                // Transaction rollback
                $this->pdo->rollBack();
                
                // Yüklenen dosyayı sil
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                }
                
                return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $e->getMessage()];
            }
            
        } catch(Exception $e) {
            error_log('uploadAdditionalFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * İlgili dosyaya ait ek dosyaları getir
     * @param string $relatedFileId - İlgili dosya ID
     * @param string $userId - Kullanıcı ID (yetki kontrolü için)
     * @param string $userType - Kullanıcı tipi (user/admin)
     * @return array - Ek dosyalar listesi
     */
    public function getAdditionalFiles($relatedFileId, $userId, $userType = 'user') {
        try {
            if (!isValidUUID($relatedFileId) || !isValidUUID($userId)) {
                return [];
            }
            
            // Hem gönderilen hem alınan dosyaları getir (iptal edilmemiş olanlar)
            $stmt = $this->pdo->prepare("
                SELECT af.*, 
                       sender.username as sender_username, sender.first_name as sender_first_name, sender.last_name as sender_last_name,
                       receiver.username as receiver_username, receiver.first_name as receiver_first_name, receiver.last_name as receiver_last_name
                FROM additional_files af
                LEFT JOIN users sender ON af.sender_id = sender.id
                LEFT JOIN users receiver ON af.receiver_id = receiver.id
                WHERE af.related_file_id = ?
                AND ((af.sender_id = ? AND af.sender_type = ?) OR (af.receiver_id = ? AND af.receiver_type = ?))
                AND (af.is_cancelled IS NULL OR af.is_cancelled = 0)
                ORDER BY af.upload_date DESC
            ");
            
            $stmt->execute([$relatedFileId, $userId, $userType, $userId, $userType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getAdditionalFiles error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ek dosya indirme
     * @param string $fileId - Ek dosya ID
     * @param string $userId - Kullanıcı ID (yetki kontrolü için)
     * @param string $userType - Kullanıcı tipi (user/admin)
     * @return array - Dosya bilgileri veya hata
     */
    public function downloadAdditionalFile($fileId, $userId, $userType = 'user') {
        try {
            if (!isValidUUID($fileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Dosya bilgilerini ve yetki kontrolünü yap (iptal edilmemiş dosyalar)
            $stmt = $this->pdo->prepare("
                SELECT * FROM additional_files
                WHERE id = ?
                AND ((sender_id = ? AND sender_type = ?) OR (receiver_id = ? AND receiver_type = ?))
                AND (is_cancelled IS NULL OR is_cancelled = 0)
            ");
            $stmt->execute([$fileId, $userId, $userType, $userId, $userType]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya yetkiniz yok.'];
            }
            
            // Fiziksel dosya kontrolü
            if (!file_exists($file['file_path'])) {
                return ['success' => false, 'message' => 'Fiziksel dosya bulunamadı.'];
            }
            
            // Eğer alıcıysa ve okumadıysa, okundu olarak işaretle
            if ($file['receiver_id'] === $userId && !$file['is_read']) {
                $this->pdo->prepare("
                    UPDATE additional_files 
                    SET is_read = 1, read_date = NOW() 
                    WHERE id = ?
                ")->execute([$fileId]);
            }
            
            return [
                'success' => true,
                'file_path' => $file['file_path'],
                'original_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'file_type' => $file['file_type']
            ];
            
        } catch(PDOException $e) {
            error_log('downloadAdditionalFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        }
    }
    
    /**
     * Okunmamış ek dosya sayısını getir
     * @param string $userId - Kullanıcı ID
     * @param string $userType - Kullanıcı tipi (user/admin)
     * @return int - Okunmamış dosya sayısı
     */
    public function getUnreadAdditionalFilesCount($userId, $userType = 'user') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM additional_files
                WHERE receiver_id = ? AND receiver_type = ? AND is_read = 0
                AND (is_cancelled IS NULL OR is_cancelled = 0)
            ");
            
            $stmt->execute([$userId, $userType]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch(PDOException $e) {
            error_log('getUnreadAdditionalFilesCount error: ' . $e->getMessage());
            return 0;
        }
    }
}
?>
