<?php
/**
 * Mr ECU - File Manager Class (GUID System) - COMPLETE VERSION
 * GUID tabanlı dosya yönetimi sınıfı - Tüm metodlar dahil
 */

class FileManager {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Araç markalarını getir
    public function getBrands() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, name 
                FROM brands 
                ORDER BY name ASC
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('getBrands error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Markaya göre modelleri getir
    public function getModelsByBrand($brandId) {
        try {
            if (!isValidUUID($brandId)) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT id, name, CONCAT(name, ' (', year_start, '-', COALESCE(year_end, 'Devam'), ')') as display_name
                FROM models 
                WHERE brand_id = ? 
                ORDER BY name ASC
            ");
            
            $stmt->execute([$brandId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log('getModelsByBrand error: ' . $e->getMessage());
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
            
            // LIMIT ve OFFSET'i direkt sorguya ekle
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
            
            $query = "
                SELECT fu.*, 
                       u.username, u.email, u.first_name, u.last_name,
                       b.name as brand_name, m.name as model_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                {$whereClause}
                ORDER BY fu.upload_date DESC
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
            error_log('getAllUploads error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Dosya yükle (GUID ID ile)
    public function uploadFile($userId, $fileData, $vehicleData, $notes = '') {
        try {
            // GUID format kontrolü - sadece GUID ise kontrol et
            if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $userId) && !isValidUUID($userId)) {
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
            
            // Dosya uzantısı kontrolü
            $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı.'];
            }
            
            // UUID oluştur
            $uploadId = generateUUID();
            $fileName = generateUUID() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileData['name']));
            
            // user_files klasörü yoksa oluştur
            $userFilesDir = UPLOAD_PATH . 'user_files/';
            if (!is_dir($userFilesDir)) {
                mkdir($userFilesDir, 0755, true);
            }
            
            $uploadPath = $userFilesDir . $fileName;
            
            // Dosyayı taşı
            if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                $this->pdo->beginTransaction();
                
                // Veritabanına kaydet - vehicleData dahil
                $stmt = $this->pdo->prepare("
                    INSERT INTO file_uploads (
                        id, user_id, original_name, filename, file_size, 
                        brand_id, model_id, year, ecu_type, engine_code, 
                        gearbox_type, fuel_type, hp_power, nm_torque,
                        status, upload_notes, upload_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $uploadId,
                    $userId,
                    $fileData['name'],
                    $fileName,
                    $fileData['size'],
                    $vehicleData['brand_id'] ?? null,
                    $vehicleData['model_id'] ?? null,
                    $vehicleData['year'] ?? null,
                    $vehicleData['ecu_type'] ?? null,
                    $vehicleData['engine_code'] ?? null,
                    $vehicleData['gearbox_type'] ?? null,
                    $vehicleData['fuel_type'] ?? null,
                    $vehicleData['hp_power'] ?? null,
                    $vehicleData['nm_torque'] ?? null,
                    $notes
                ]);
                
                if ($result) {
                    $this->pdo->commit();
                    return ['success' => true, 'message' => 'Dosya başarıyla yüklendi.', 'upload_id' => $uploadId];
                } else {
                    $this->pdo->rollBack();
                    // Dosyayı sil
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                    return ['success' => false, 'message' => 'Veritabanı kaydı başarısız.'];
                }
            }
            
            return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu.'];
            
        } catch(PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Dosyayı sil
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            error_log('uploadFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu: ' . $e->getMessage()];
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
                SET status = ?, admin_notes = ?, processed_date = NOW() 
                WHERE id = ?
            ");
            
            return $stmt->execute([$status, $adminNotes, $uploadId]);
            
        } catch(PDOException $e) {
            error_log('updateUploadStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Kullanıcının yanıt dosyalarını getir (admin tarafından gönderilen dosyalar)
    public function getUserResponseFiles($userId, $page = 1, $limit = 10, $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            $whereClause = "WHERE fu.user_id = ?";
            $params = [$userId];
            
            if ($search) {
                $whereClause .= " AND (fr.original_name LIKE ? OR fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT fr.*, fu.original_name as original_upload_name, fu.upload_date as original_upload_date,
                       fu.brand_id, fu.model_id, fu.year, fu.ecu_type, fu.engine_code,
                       fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                       b.name as brand_name, m.name as model_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       'response' as file_type
                FROM file_responses fr
                INNER JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN users a ON fr.admin_id = a.id
                {$whereClause}
                ORDER BY fr.upload_date DESC 
                LIMIT $limit OFFSET $offset
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserResponseFiles error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının yanıt dosya sayısını getir
    public function getUserResponseFileCount($userId, $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $whereClause = "WHERE fu.user_id = ?";
            $params = [$userId];
            
            if ($search) {
                $whereClause .= " AND (fr.original_name LIKE ? OR fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM file_responses fr
                INNER JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                {$whereClause}
            ");
            
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch(PDOException $e) {
            error_log('getUserResponseFileCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Kullanıcının tüm dosyalarını getir (yüklenen + yanıt dosyaları birleşik)
    public function getUserAllFiles($userId, $page = 1, $limit = 10, $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            
            // Ana yüklenen dosyalar sorgusu
            $uploadQuery = "
                SELECT fu.id, fu.original_name, fu.filename, fu.file_size, fu.status, fu.upload_date,
                       fu.admin_notes, fu.upload_notes as notes, fu.processed_date, fu.credits_charged,
                       fu.brand_id, fu.model_id, fu.year, fu.ecu_type, fu.engine_code,
                       fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                       b.name as brand_name, m.name as model_name,
                       'upload' as file_type, NULL as admin_username, NULL as admin_first_name, NULL as admin_last_name,
                       NULL as original_upload_name, NULL as original_upload_date
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                WHERE fu.user_id = ?";
            
            $params = [$userId];
            
            if ($status && $status !== 'response') {
                $uploadQuery .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $uploadQuery .= " AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Yanıt dosyaları sorgusu (sadece status = 'response' değilse)
            $responseQuery = "";
            if ($status !== 'pending' && $status !== 'processing' && $status !== 'completed' && $status !== 'rejected') {
                $responseQuery = "
                    UNION ALL
                    SELECT fr.id, fr.original_name, fr.filename, fr.file_size, 'response' as status, fr.upload_date,
                           fr.admin_notes, NULL as notes, fr.upload_date as processed_date, fr.credits_charged,
                           fu.brand_id, fu.model_id, fu.year, fu.ecu_type, fu.engine_code,
                           fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                           b.name as brand_name, m.name as model_name,
                           'response' as file_type, a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                           fu.original_name as original_upload_name, fu.upload_date as original_upload_date
                    FROM file_responses fr
                    INNER JOIN file_uploads fu ON fr.upload_id = fu.id
                    LEFT JOIN brands b ON fu.brand_id = b.id
                    LEFT JOIN models m ON fu.model_id = m.id
                    LEFT JOIN users a ON fr.admin_id = a.id
                    WHERE fu.user_id = ?";
                
                $params[] = $userId; // response query için user_id
                
                if ($status === 'response') {
                    // Sadece yanıt dosyalarını göster, upload dosyalarını gösterme
                    $uploadQuery = $responseQuery;
                    $responseQuery = "";
                    // params dizisini düzenle - ilk userId'yi kaldır
                    array_shift($params);
                }
                
                if ($search && $status !== 'response') {
                    $responseQuery .= " AND (fr.original_name LIKE ? OR fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
            }
            
            $fullQuery = "(
                {$uploadQuery}
                {$responseQuery}
            ) ORDER BY upload_date DESC
            LIMIT $limit OFFSET $offset";
            
            $stmt = $this->pdo->prepare($fullQuery);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserAllFiles error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının tüm dosya sayısını getir (yüklenen + yanıt dosyaları)
    public function getUserAllFileCount($userId, $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            // Ana yüklenen dosyalar sayısı
            $uploadCount = 0;
            $uploadQuery = "SELECT COUNT(*) as count FROM file_uploads fu LEFT JOIN brands b ON fu.brand_id = b.id LEFT JOIN models m ON fu.model_id = m.id WHERE fu.user_id = ?";
            $params = [$userId];
            
            if ($status && $status !== 'response') {
                $uploadQuery .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $uploadQuery .= " AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($status !== 'response') {
                $stmt = $this->pdo->prepare($uploadQuery);
                $stmt->execute($params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $uploadCount = $result['count'] ?? 0;
            }
            
            // Yanıt dosyaları sayısı
            $responseCount = 0;
            if ($status !== 'pending' && $status !== 'processing' && $status !== 'completed' && $status !== 'rejected') {
                $responseParams = [$userId];
                $responseQuery = "SELECT COUNT(*) as count FROM file_responses fr INNER JOIN file_uploads fu ON fr.upload_id = fu.id LEFT JOIN brands b ON fu.brand_id = b.id LEFT JOIN models m ON fu.model_id = m.id WHERE fu.user_id = ?";
                
                if ($search) {
                    $responseQuery .= " AND (fr.original_name LIKE ? OR fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                    $searchTerm = "%$search%";
                    $responseParams[] = $searchTerm;
                    $responseParams[] = $searchTerm;
                    $responseParams[] = $searchTerm;
                    $responseParams[] = $searchTerm;
                }
                
                $stmt = $this->pdo->prepare($responseQuery);
                $stmt->execute($responseParams);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $responseCount = $result['count'] ?? 0;
            }
            
            if ($status === 'response') {
                return $responseCount;
            }
            
            return $uploadCount + $responseCount;
            
        } catch(PDOException $e) {
            error_log('getUserAllFileCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Kullanıcının dosya sayısını getir
    public function getUserUploadCount($userId, $status = '', $search = '') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $whereClause = "WHERE fu.user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $whereClause .= " AND fu.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $whereClause .= " AND (fu.original_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
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
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserFileStats error: ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
        }
    }
    
    // Kullanıcının revize taleplerini getir
    public function getUserRevisions($userId, $page = 1, $limit = 10, $dateFrom = '', $dateTo = '', $status = '') {
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
            
            if ($dateFrom && $dateTo) {
                $whereClause .= " AND DATE(r.requested_at) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            } elseif ($dateFrom) {
                $whereClause .= " AND DATE(r.requested_at) >= ?";
                $params[] = $dateFrom;
            } elseif ($dateTo) {
                $whereClause .= " AND DATE(r.requested_at) <= ?";
                $params[] = $dateTo;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT r.*, fu.original_name, 
                       a.username as admin_username
                FROM revisions r
                LEFT JOIN file_uploads fu ON r.upload_id = fu.id
                LEFT JOIN users a ON r.admin_id = a.id
                {$whereClause}
                ORDER BY r.requested_at DESC
                LIMIT $limit OFFSET $offset
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log('getUserRevisions error: ' . $e->getMessage());
            return [];
        }
    }
    
    // Kullanıcının revize talep sayısını getir
    public function getUserRevisionCount($userId, $dateFrom = '', $dateTo = '', $status = '') {
        try {
            if (!isValidUUID($userId)) {
                return 0;
            }
            
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $whereClause .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($dateFrom && $dateTo) {
                $whereClause .= " AND DATE(requested_at) BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            } elseif ($dateFrom) {
                $whereClause .= " AND DATE(requested_at) >= ?";
                $params[] = $dateFrom;
            } elseif ($dateTo) {
                $whereClause .= " AND DATE(requested_at) <= ?";
                $params[] = $dateTo;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM revisions
                {$whereClause}
            ");
            
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
            
        } catch(PDOException $e) {
            error_log('getUserRevisionCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    // Revize talebi gönder (Upload dosyası için)
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
                INSERT INTO revisions (id, upload_id, user_id, request_notes, request_type, status, requested_at)
                VALUES (?, ?, ?, ?, 'upload', 'pending', NOW())
            ");
            
            $result = $stmt->execute([$revisionId, $uploadId, $userId, $revisionNotes]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Revize talebi başarıyla gönderildi.', 'revision_id' => $revisionId];
            }
            
            return ['success' => false, 'message' => 'Revize talebi gönderilemedi.'];
            
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
            
            error_log('requestResponseRevision - responseId: ' . $responseId . ', userId: ' . $userId);
            
            // Response dosyası kontrolü
            $stmt = $this->pdo->prepare("
                SELECT fr.*, fu.user_id 
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                WHERE fr.id = ?
            ");
            $stmt->execute([$responseId]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log('requestResponseRevision - response data: ' . print_r($response, true));
            
            if (!$response) {
                return ['success' => false, 'message' => 'Yanıt dosyası bulunamadı.'];
            }
            
            if ($response['user_id'] !== $userId) {
                return ['success' => false, 'message' => 'Yanıt dosyası size ait değil.'];
            }
            
            // Daha önce bekleyen revize talebi var mı kontrol et (bu response dosyası için)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM revisions 
                WHERE upload_id = ? AND status = 'pending' AND request_notes LIKE '[YANIT DOSYASI REVİZE]%'
            ");
            $stmt->execute([$response['upload_id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log('requestResponseRevision - existing count: ' . $existing['count']);
            
            if ($existing['count'] > 0) {
                return ['success' => false, 'message' => 'Bu yanıt dosyası için zaten bekleyen bir revize talebi bulunuyor.'];
            }
            
            // Revize talebi oluştur - upload_id ekle
            $revisionId = generateUUID();
            error_log('requestResponseRevision - attempting to insert with revisionId: ' . $revisionId);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (id, upload_id, user_id, request_notes, status, requested_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            // Yanıt dosyası revize talebi olduğunu belirt
            $prefixedNotes = "[YANIT DOSYASI REVİZE] " . $revisionNotes;
            $result = $stmt->execute([$revisionId, $response['upload_id'], $userId, $prefixedNotes]);
            error_log('requestResponseRevision - insert result: ' . ($result ? 'true' : 'false'));
            
            if ($result) {
                return ['success' => true, 'message' => 'Yanıt dosyası için revize talebi başarıyla gönderildi.', 'revision_id' => $revisionId];
            }
            
            return ['success' => false, 'message' => 'Revize talebi gönderilemedi.'];
            
        } catch(PDOException $e) {
            error_log('requestResponseRevision PDOException: ' . $e->getMessage());
            error_log('requestResponseRevision SQL Error: ' . $e->getCode());
            return ['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()];
        } catch(Exception $e) {
            error_log('requestResponseRevision Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Genel hata: ' . $e->getMessage()];
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // filename'den file_path oluştur
            if ($result && !empty($result['filename'])) {
                // Dosya path'ini uploads/user_files/ yapısına göre oluştur
                $result['file_path'] = '../uploads/user_files/' . $result['filename'];
            }
            
            return $result;
            
        } catch(PDOException $e) {
            error_log('getUploadById error: ' . $e->getMessage());
            return null;
        }
    }
    
    // Yanıt dosyası yükle (file_responses tablosuna kaydet)
    public function uploadResponseFile($uploadId, $fileData, $creditsCharged, $responseNotes = '') {
        try {
            if (!isValidUUID($uploadId)) {
                return ['success' => false, 'message' => 'Geçersiz dosya ID formatı.'];
            }
            
            // Dosya kontrolü
            if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
                return ['success' => false, 'message' => 'Yanıt dosyası yüklenmedi.'];
            }
            
            // Original upload var mı kontrol et
            $upload = $this->getUploadById($uploadId);
            if (!$upload) {
                return ['success' => false, 'message' => 'Orijinal dosya bulunamadı.'];
            }
            
            $fileName = generateUUID() . '_response_' . basename($fileData['name']);
            $uploadPath = UPLOAD_PATH . 'response_files/' . $fileName;
            
            // Dizin yoksa oluştur
            if (!is_dir(UPLOAD_PATH . 'response_files/')) {
                mkdir(UPLOAD_PATH . 'response_files/', 0755, true);
            }
            
            if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
                $this->pdo->beginTransaction();
                
                // file_responses tablosuna kaydet
                $responseId = generateUUID();
                $stmt = $this->pdo->prepare("
                    INSERT INTO file_responses (id, upload_id, admin_id, filename, original_name, file_size, credits_charged, admin_notes, upload_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
                
                if ($result) {
                    // file_uploads tablosunu güncelle
                    $stmt = $this->pdo->prepare("
                        UPDATE file_uploads 
                        SET status = 'completed', credits_charged = ?, processed_date = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$creditsCharged, $uploadId]);
                    
                    // Kredi düş (eğer belirtilmişse)
                    if ($creditsCharged > 0) {
                        $user = new User($this->pdo);
                        $creditResult = $user->addCreditDirectSimple(
                            $upload['user_id'], 
                            $creditsCharged, 
                            'file_charge', 
                            'Dosya işleme ücreti: ' . $upload['original_name']
                        );
                        
                        if (!$creditResult) {
                            $this->pdo->rollBack();
                            return ['success' => false, 'message' => 'Yetersiz kredi. İşlem iptal edildi.'];
                        }
                    }
                    
                    $this->pdo->commit();
                    return ['success' => true, 'message' => 'Yanıt dosyası başarıyla yüklendi.', 'response_id' => $responseId];
                } else {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Veritabanı kaydı başarısız.'];
                }
            }
            
            return ['success' => false, 'message' => 'Yanıt dosyası yüklenemedi.'];
            
        } catch(PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('uploadResponseFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı hatası oluştu.'];
        } catch(Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('uploadResponseFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Dosya yükleme hatası: ' . $e->getMessage()];
        }
    }
    
    // Dosya silme
    public function deleteUpload($uploadId) {
        try {
            if (!isValidUUID($uploadId)) {
                return false;
            }
            
            // Önce dosya bilgisini al
            $upload = $this->getUploadById($uploadId);
            if (!$upload) {
                return false;
            }
            
            // Fiziksel dosyayı sil
            if (!empty($upload['filename'])) {
                $filePath = UPLOAD_PATH . 'user_files/' . $upload['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Veritabanından sil
            $stmt = $this->pdo->prepare("DELETE FROM file_uploads WHERE id = ?");
            return $stmt->execute([$uploadId]);
            
        } catch(PDOException $e) {
            error_log('deleteUpload error: ' . $e->getMessage());
            return false;
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
    
    // Dosya indirme işlemi
    public function downloadFile($fileId, $userId, $type = 'response') {
        try {
            if (!isValidUUID($fileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            if ($type === 'response') {
                // Yanıt dosyası indirme
                $stmt = $this->pdo->prepare("
                    SELECT fr.*, fu.user_id, fu.original_name as upload_filename
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
}
?>
