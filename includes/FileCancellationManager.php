<?php
/**
 * Mr ECU - File Cancellation Manager Class
 * Dosya İptal Yönetimi Sınıfı
 */

class FileCancellationManager {
    private $pdo;
    private $notificationManager;
    
    public function __construct($database) {
        $this->pdo = $database;
        
        // NotificationManager'ı yükle
        if (!class_exists('NotificationManager')) {
            require_once __DIR__ . '/NotificationManager.php';
        }
        $this->notificationManager = new NotificationManager($this->pdo);
    }
    
    /**
     * Dosya iptal talebi oluştur
     * @param string $userId - Kullanıcı ID
     * @param string $fileId - Dosya ID
     * @param string $fileType - Dosya tipi (upload, response, revision, additional)
     * @param string $reason - İptal sebebi
     * @return array - Başarı durumu ve mesaj
     */
    public function requestCancellation($userId, $fileId, $fileType, $reason) {
        try {
            // ID formatı kontrolü
            if (!isValidUUID($userId) || !isValidUUID($fileId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // Dosya tipi kontrolü
            $allowedTypes = ['upload', 'response', 'revision', 'additional'];
            if (!in_array($fileType, $allowedTypes)) {
                return ['success' => false, 'message' => 'Geçersiz dosya tipi.'];
            }
            
            // Sebep kontrolü
            if (empty(trim($reason))) {
                return ['success' => false, 'message' => 'İptal sebebi gereklidir.'];
            }
            
            // Dosya varlığı ve sahiplik kontrolü
            $fileInfo = $this->getFileInfo($fileId, $fileType, $userId);
            if (!$fileInfo['exists']) {
                return ['success' => false, 'message' => 'Dosya bulunamadı veya size ait değil.'];
            }
            
            // Önceki bekleyen talep kontrolü
            $stmt = $this->pdo->prepare("
                SELECT id FROM file_cancellations 
                WHERE file_id = ? AND file_type = ? AND status = 'pending'
            ");
            $stmt->execute([$fileId, $fileType]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Bu dosya için zaten bekleyen bir iptal talebi bulunuyor.'];
            }
            
            // İade edilecek kredi miktarını hesapla
            $creditsToRefund = $this->calculateRefundAmount($fileId, $fileType, $fileInfo);
            
            // İptal talebi oluştur
            $cancellationId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO file_cancellations (
                    id, user_id, file_id, file_type, reason, 
                    credits_to_refund, requested_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $cancellationId,
                $userId,
                $fileId,
                $fileType,
                $reason,
                $creditsToRefund
            ]);
            
            if ($result) {
                // Admin'lere bildirim gönder
                $this->notifyAdminsForCancellation($cancellationId, $userId, $fileInfo, $reason, $creditsToRefund);
                
                return [
                    'success' => true,
                    'message' => 'İptal talebi gönderildi. Admin onayından sonra işleme alınacaktır.',
                    'cancellation_id' => $cancellationId,
                    'credits_to_refund' => $creditsToRefund
                ];
            } else {
                return ['success' => false, 'message' => 'İptal talebi oluşturulamadı.'];
            }
            
        } catch (Exception $e) {
            error_log('FileCancellationManager requestCancellation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Sistem hatası oluştu.'];
        }
    }
    
    /**
     * Admin tarafından iptal talebini onayla
     * @param string $cancellationId - İptal talebi ID
     * @param string $adminId - Admin ID
     * @param string $adminNotes - Admin notları
     * @return array - Başarı durumu ve mesaj
     */
    public function approveCancellation($cancellationId, $adminId, $adminNotes = '') {
        try {
            if (!isValidUUID($cancellationId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            // İptal talebini getir
            $stmt = $this->pdo->prepare("
                SELECT * FROM file_cancellations 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$cancellationId]);
            $cancellation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cancellation) {
                return ['success' => false, 'message' => 'İptal talebi bulunamadı veya zaten işlenmiş.'];
            }
            
            // Transaction başlat
            $this->pdo->beginTransaction();
            
            try {
                // 1. İptal talebini onayla
                $stmt = $this->pdo->prepare("
                    UPDATE file_cancellations 
                    SET status = 'approved', admin_id = ?, admin_notes = ?, processed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$adminId, $adminNotes, $cancellationId]);
                
                // 2. Dosyayı sil
                $deleteResult = $this->deleteFile($cancellation['file_id'], $cancellation['file_type']);
                if (!$deleteResult['success']) {
                    throw new Exception('Dosya silinirken hata: ' . $deleteResult['message']);
                }
                
                // 3. Kredi iadesi (eğer varsa)
                if ($cancellation['credits_to_refund'] > 0) {
                    $refundResult = $this->processRefund($cancellation['user_id'], $cancellation['credits_to_refund'], $cancellationId);
                    if (!$refundResult['success']) {
                        throw new Exception('Kredi iadesi yapılamadı: ' . $refundResult['message']);
                    }
                }
                
                // 4. Kullanıcıya bildirim gönder
                $this->notifyUserApproval($cancellation, $adminNotes);
                
                // Transaction commit
                $this->pdo->commit();
                
                return [
                    'success' => true,
                    'message' => 'İptal talebi onaylandı, dosya silindi ve kredi iadesi yapıldı.',
                    'refund_amount' => $cancellation['credits_to_refund']
                ];
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('FileCancellationManager approveCancellation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'İptal onaylanırken hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Admin tarafından iptal talebini reddet
     * @param string $cancellationId - İptal talebi ID
     * @param string $adminId - Admin ID
     * @param string $adminNotes - Red sebebi
     * @return array - Başarı durumu ve mesaj
     */
    public function rejectCancellation($cancellationId, $adminId, $adminNotes) {
        try {
            if (!isValidUUID($cancellationId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı.'];
            }
            
            if (empty(trim($adminNotes))) {
                return ['success' => false, 'message' => 'Red sebebi gereklidir.'];
            }
            
            // İptal talebini getir
            $stmt = $this->pdo->prepare("
                SELECT * FROM file_cancellations 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$cancellationId]);
            $cancellation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cancellation) {
                return ['success' => false, 'message' => 'İptal talebi bulunamadı veya zaten işlenmiş.'];
            }
            
            // İptal talebini reddet
            $stmt = $this->pdo->prepare("
                UPDATE file_cancellations 
                SET status = 'rejected', admin_id = ?, admin_notes = ?, processed_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$adminId, $adminNotes, $cancellationId]);
            
            if ($result) {
                // Kullanıcıya bildirim gönder
                $this->notifyUserRejection($cancellation, $adminNotes);
                
                return [
                    'success' => true,
                    'message' => 'İptal talebi reddedildi ve kullanıcıya bildirim gönderildi.'
                ];
            } else {
                return ['success' => false, 'message' => 'İptal talebi reddedilemedi.'];
            }
            
        } catch (Exception $e) {
            error_log('FileCancellationManager rejectCancellation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Red işlemi sırasında hata oluştu.'];
        }
    }
    
    /**
     * Dosya bilgilerini ve sahiplik kontrolünü yap
     * @param string $fileId - Dosya ID
     * @param string $fileType - Dosya tipi
     * @param string $userId - Kullanıcı ID
     * @return array - Dosya bilgileri
     */
    private function getFileInfo($fileId, $fileType, $userId) {
        try {
            $fileInfo = [
                'exists' => false,
                'name' => '',
                'size' => 0,
                'upload_date' => null,
                'status' => '',
                'credits_charged' => 0
            ];
            
            switch ($fileType) {
                case 'upload':
                    $stmt = $this->pdo->prepare("
                        SELECT original_name, file_size, upload_date, status, credits_charged
                        FROM file_uploads 
                        WHERE id = ? AND user_id = ?
                    ");
                    break;
                    
                case 'response':
                    $stmt = $this->pdo->prepare("
                        SELECT fr.original_name, fr.file_size, fr.upload_date, 'completed' as status, fr.credits_charged
                        FROM file_responses fr
                        JOIN file_uploads fu ON fr.upload_id = fu.id
                        WHERE fr.id = ? AND fu.user_id = ?
                    ");
                    break;
                    
                case 'revision':
                    $stmt = $this->pdo->prepare("
                        SELECT rf.original_name, rf.file_size, rf.upload_date, 'completed' as status, 0 as credits_charged
                        FROM revision_files rf
                        JOIN revisions r ON rf.revision_id = r.id
                        WHERE rf.id = ? AND r.user_id = ?
                    ");
                    break;
                    
                case 'additional':
                    $stmt = $this->pdo->prepare("
                        SELECT original_name, file_size, upload_date, 'completed' as status, credits
                        FROM additional_files 
                        WHERE id = ? AND ((sender_id = ? AND sender_type = 'user') OR (receiver_id = ? AND receiver_type = 'user'))
                    ");
                    $stmt->execute([$fileId, $userId, $userId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $fileInfo['exists'] = true;
                        $fileInfo['name'] = $result['original_name'];
                        $fileInfo['size'] = $result['file_size'];
                        $fileInfo['upload_date'] = $result['upload_date'];
                        $fileInfo['status'] = $result['status'];
                        $fileInfo['credits_charged'] = $result['credits'];
                    }
                    return $fileInfo;
                    
                default:
                    return $fileInfo;
            }
            
            if ($fileType !== 'additional') {
                $stmt->execute([$fileId, $userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $fileInfo['exists'] = true;
                    $fileInfo['name'] = $result['original_name'];
                    $fileInfo['size'] = $result['file_size'];
                    $fileInfo['upload_date'] = $result['upload_date'];
                    $fileInfo['status'] = $result['status'];
                    $fileInfo['credits_charged'] = $result['credits_charged'] ?? 0;
                }
            }
            
            return $fileInfo;
            
        } catch (Exception $e) {
            error_log('FileCancellationManager getFileInfo error: ' . $e->getMessage());
            return ['exists' => false, 'name' => '', 'size' => 0, 'upload_date' => null, 'status' => '', 'credits_charged' => 0];
        }
    }
    
    /**
     * İade edilecek kredi miktarını hesapla
     * @param string $fileId - Dosya ID
     * @param string $fileType - Dosya tipi
     * @param array $fileInfo - Dosya bilgileri
     * @return float - İade edilecek kredi
     */
    private function calculateRefundAmount($fileId, $fileType, $fileInfo) {
        // Sadece ücretli dosyalar için iade yapılır
        if ($fileInfo['credits_charged'] > 0) {
            return $fileInfo['credits_charged'];
        }
        
        // Response dosyaları için özel kontrol
        if ($fileType === 'response') {
            try {
                $stmt = $this->pdo->prepare("SELECT credits_charged FROM file_responses WHERE id = ?");
                $stmt->execute([$fileId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['credits_charged'] ?? 0;
            } catch (Exception $e) {
                error_log('calculateRefundAmount error: ' . $e->getMessage());
                return 0;
            }
        }
        
        return 0;
    }
    
    /**
     * Dosyayı fiziksel olarak sil
     * @param string $fileId - Dosya ID
     * @param string $fileType - Dosya tipi
     * @return array - Başarı durumu
     */
    private function deleteFile($fileId, $fileType) {
        try {
            $deleted = false;
            $filePath = '';
            
            switch ($fileType) {
                case 'upload':
                    $stmt = $this->pdo->prepare("SELECT filename FROM file_uploads WHERE id = ?");
                    $stmt->execute([$fileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $filePath = UPLOAD_PATH . 'user_files/' . $result['filename'];
                        // Dosyayı fiziksel olarak sil
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        // Veritabanından sil
                        $this->pdo->prepare("DELETE FROM file_uploads WHERE id = ?")->execute([$fileId]);
                        $deleted = true;
                    }
                    break;
                    
                case 'response':
                    $stmt = $this->pdo->prepare("SELECT filename FROM file_responses WHERE id = ?");
                    $stmt->execute([$fileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $filePath = UPLOAD_PATH . 'response_files/' . $result['filename'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        $this->pdo->prepare("DELETE FROM file_responses WHERE id = ?")->execute([$fileId]);
                        $deleted = true;
                    }
                    break;
                    
                case 'revision':
                    $stmt = $this->pdo->prepare("SELECT filename FROM revision_files WHERE id = ?");
                    $stmt->execute([$fileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $filePath = UPLOAD_PATH . 'revision_files/' . $result['filename'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        $this->pdo->prepare("DELETE FROM revision_files WHERE id = ?")->execute([$fileId]);
                        $deleted = true;
                    }
                    break;
                    
                case 'additional':
                    $stmt = $this->pdo->prepare("SELECT file_path FROM additional_files WHERE id = ?");
                    $stmt->execute([$fileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $filePath = $result['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        $this->pdo->prepare("DELETE FROM additional_files WHERE id = ?")->execute([$fileId]);
                        $deleted = true;
                    }
                    break;
            }
            
            if ($deleted) {
                return ['success' => true, 'message' => 'Dosya başarıyla silindi.'];
            } else {
                return ['success' => false, 'message' => 'Dosya silinemedi.'];
            }
            
        } catch (Exception $e) {
            error_log('FileCancellationManager deleteFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Dosya silme hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Kredi iadesini işle
     * @param string $userId - Kullanıcı ID
     * @param float $amount - İade miktarı
     * @param string $cancellationId - İptal talebi ID
     * @return array - Başarı durumu
     */
    private function processRefund($userId, $amount, $cancellationId) {
        try {
            if (!class_exists('User')) {
                require_once __DIR__ . '/User.php';
            }
            
            $user = new User($this->pdo);
            
            // Ters kredi sistemi - kredi_used'ı azalt (iade)
            $result = $user->addCreditDirectSimple(
                $userId,
                -$amount, // Negatif değer ile used_credits'i azalt
                'file_cancellation_refund',
                'Dosya iptal iadesi - İptal ID: ' . $cancellationId
            );
            
            if ($result) {
                return ['success' => true, 'message' => 'Kredi iadesi yapıldı.'];
            } else {
                return ['success' => false, 'message' => 'Kredi iadesi yapılamadı.'];
            }
            
        } catch (Exception $e) {
            error_log('FileCancellationManager processRefund error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Kredi iadesi hatası: ' . $e->getMessage()];
        }
    }
    
    /**
     * Admin'lere iptal talebi bildirimi gönder
     */
    private function notifyAdminsForCancellation($cancellationId, $userId, $fileInfo, $reason, $creditsToRefund) {
        try {
            // Admin kullanıcıları getir
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $title = "Yeni Dosya İptal Talebi";
            $message = "Kullanıcı bir dosya iptal talebi gönderdi.\n\n";
            $message .= "Dosya: " . $fileInfo['name'] . "\n";
            $message .= "Sebep: " . $reason . "\n";
            if ($creditsToRefund > 0) {
                $message .= "İade edilecek kredi: " . $creditsToRefund . "\n";
            }
            $actionUrl = "admin/file-cancellations.php?id=" . $cancellationId;
            
            foreach ($admins as $adminId) {
                $this->notificationManager->createNotification(
                    $adminId,
                    'file_cancellation_request',
                    $title,
                    $message,
                    $cancellationId,
                    'file_cancellation',
                    $actionUrl
                );
            }
            
        } catch (Exception $e) {
            error_log('notifyAdminsForCancellation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcıya onay bildirimi gönder
     */
    private function notifyUserApproval($cancellation, $adminNotes) {
        try {
            $title = "Dosya İptal Talebi Onaylandı";
            $message = "Dosya iptal talebiniz onaylandı ve dosya silindi.\n\n";
            if ($cancellation['credits_to_refund'] > 0) {
                $message .= "İade edilen kredi: " . $cancellation['credits_to_refund'] . "\n";
            }
            if (!empty($adminNotes)) {
                $message .= "Admin notu: " . $adminNotes;
            }
            
            $this->notificationManager->createNotification(
                $cancellation['user_id'],
                'file_cancellation_approved',
                $title,
                $message,
                $cancellation['id'],
                'file_cancellation',
                'user/files.php'
            );
            
        } catch (Exception $e) {
            error_log('notifyUserApproval error: ' . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcıya red bildirimi gönder
     */
    private function notifyUserRejection($cancellation, $adminNotes) {
        try {
            $title = "Dosya İptal Talebi Reddedildi";
            $message = "Dosya iptal talebiniz reddedildi.\n\n";
            $message .= "Red sebebi: " . $adminNotes;
            
            $this->notificationManager->createNotification(
                $cancellation['user_id'],
                'file_cancellation_rejected',
                $title,
                $message,
                $cancellation['id'],
                'file_cancellation',
                'user/files.php'
            );
            
        } catch (Exception $e) {
            error_log('notifyUserRejection error: ' . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcının iptal taleplerini getir
     * @param string $userId - Kullanıcı ID
     * @param int $page - Sayfa
     * @param int $limit - Limit
     * @return array - İptal talepleri
     */
    public function getUserCancellations($userId, $page = 1, $limit = 10) {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }
            
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT fc.*, a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM file_cancellations fc
                LEFT JOIN users a ON fc.admin_id = a.id
                WHERE fc.user_id = ?
                ORDER BY fc.requested_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('getUserCancellations error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Admin için tüm iptal taleplerini getir
     * @param int $page - Sayfa
     * @param int $limit - Limit
     * @param string $status - Durum filtresi
     * @param string $fileType - Dosya tipi filtresi
     * @param string $search - Arama kelimesi
     * @return array - İptal talepleri
     */
    public function getAllCancellations($page = 1, $limit = 20, $status = '', $fileType = '', $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = [];
            $params = [];
            
            // Status filtresi varsa ekle
            if (!empty($status)) {
                $whereConditions[] = "fc.status = ?";
                $params[] = $status;
            }
            
            // Dosya tipi filtresi varsa ekle
            if (!empty($fileType)) {
                $whereConditions[] = "fc.file_type = ?";
                $params[] = $fileType;
            }
            
            // Arama filtresi varsa ekle
            if (!empty($search)) {
                $whereConditions[] = "(
                    u.username LIKE ? OR 
                    u.first_name LIKE ? OR 
                    u.last_name LIKE ? OR 
                    u.email LIKE ? OR
                    COALESCE(fu.original_name, fr.original_name, rf.original_name, af.original_name) LIKE ? OR
                    COALESCE(fu.plate, fr_upload.plate, rf_upload.plate, af_upload.plate) LIKE ? OR
                    fc.reason LIKE ?
                )";
                $searchTerm = "%{$search}%";
                for ($i = 0; $i < 7; $i++) {
                    $params[] = $searchTerm;
                }
            }
            
            // WHERE clause'ı oluştur
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            $sql = "
                SELECT fc.*, 
                       u.username, u.first_name, u.last_name, u.email,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       
                       -- Upload dosyası bilgileri
                       fu.original_name as upload_file_name,
                       fu.plate as upload_plate,
                       fu.status as upload_status,
                       fu.upload_date as upload_date,
                       
                       -- Response dosyası bilgileri  
                       fr.original_name as response_file_name,
                       fr.upload_date as response_date,
                       fr_upload.original_name as response_main_file_name,
                       fr_upload.plate as response_main_plate,
                       
                       -- Revision dosyası bilgileri
                       rf.original_name as revision_file_name,
                       rf.upload_date as revision_date,
                       rf_upload.original_name as revision_main_file_name,
                       rf_upload.plate as revision_main_plate,
                       
                       -- Additional dosyası bilgileri
                       af.original_name as additional_file_name,
                       af.upload_date as additional_date,
                       af_upload.original_name as additional_main_file_name,
                       af_upload.plate as additional_main_plate
                       
                FROM file_cancellations fc
                LEFT JOIN users u ON fc.user_id = u.id
                LEFT JOIN users a ON fc.admin_id = a.id
                
                -- Upload dosyası için JOIN
                LEFT JOIN file_uploads fu ON fc.file_id = fu.id AND fc.file_type = 'upload'
                
                -- Response dosyası için JOIN
                LEFT JOIN file_responses fr ON fc.file_id = fr.id AND fc.file_type = 'response'
                LEFT JOIN file_uploads fr_upload ON fr.upload_id = fr_upload.id
                
                -- Revision dosyası için JOIN  
                LEFT JOIN revision_files rf ON fc.file_id = rf.id AND fc.file_type = 'revision'
                LEFT JOIN revisions rev ON rf.revision_id = rev.id
                LEFT JOIN file_uploads rf_upload ON rev.upload_id = rf_upload.id
                
                -- Additional dosyası için JOIN
                LEFT JOIN additional_files af ON fc.file_id = af.id AND fc.file_type = 'additional'
                LEFT JOIN file_uploads af_upload ON af.related_file_id = af_upload.id AND af.related_file_type = 'upload'
                
                {$whereClause}
                ORDER BY fc.requested_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
            
            // Debug için SQL sorgusunu logla
            if (isset($_GET['debug'])) {
                error_log('getAllCancellations SQL: ' . $sql);
                error_log('getAllCancellations Params: ' . print_r($params, true));
                error_log('getAllCancellations Page: ' . $page . ', Limit: ' . $limit . ', Offset: ' . $offset);
            }
            
            if (empty($params)) {
                // Parametre yoksa direkt query çalıştır
                $stmt = $this->pdo->query($sql);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Parametre varsa prepare + execute
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Debug için sonuçları logla
            if (isset($_GET['debug'])) {
                error_log('getAllCancellations Result Count: ' . count($result));
                if (!empty($result)) {
                    error_log('getAllCancellations First Result: ' . print_r($result[0], true));
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('getAllCancellations error: ' . $e->getMessage());
            if (isset($_GET['debug'])) {
                error_log('getAllCancellations Exception: ' . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * İptal talebi istatistiklerini getir
     * @return array - İstatistikler
     */
    public function getCancellationStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'approved' THEN credits_to_refund ELSE 0 END) as total_refunded
                FROM file_cancellations
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('getCancellationStats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total_refunded' => 0
            ];
        }
    }
}
?>
