<?php
/**
 * Mr ECU - File Cancellation Manager Class (CLEAN VERSION)
 * Dosya İptal Yönetimi Sınıfı - Temizlenmiş versiyon
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
     * Kullanıcının iptal taleplerini getir
     * @param string $userId - Kullanıcı ID
     * @param int $page - Sayfa
     * @param int $limit - Limit
     * @return array - İptal talepleri
     */
    public function getUserCancellations($userId, $page = 1, $limit = 10) {
        try {
            if (!isValidUUID($userId)) {
                error_log('getUserCancellations: Invalid UUID - ' . $userId);
                return [];
            }
            
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // PDO'da LIMIT/OFFSET integer olarak bind edilemez, direkt SQL'de yazmalı
            $sql = "
                SELECT fc.*, a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM file_cancellations fc
                LEFT JOIN users a ON fc.admin_id = a.id
                WHERE fc.user_id = ?
                ORDER BY fc.requested_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
            
            error_log('getUserCancellations SQL: ' . $sql);
            error_log('getUserCancellations userId: ' . $userId);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('getUserCancellations: Found ' . count($result) . ' cancellations for user ' . $userId);
            
            if (empty($result)) {
                // Debug için direkt sorgu da deneyelim
                error_log('Debug: Trying direct query without LIMIT...');
                $debugStmt = $this->pdo->prepare("
                    SELECT fc.*, a.username as admin_username 
                    FROM file_cancellations fc
                    LEFT JOIN users a ON fc.admin_id = a.id
                    WHERE fc.user_id = ?
                ");
                $debugStmt->execute([$userId]);
                $debugResult = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Debug direct query result count: ' . count($debugResult));
                
                if (!empty($debugResult)) {
                    error_log('Debug: Found data with direct query, LIMIT/OFFSET issue confirmed');
                    return $debugResult; // LIMIT sorununu geçici olarak atla
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('getUserCancellations error: ' . $e->getMessage());
            error_log('getUserCancellations trace: ' . $e->getTraceAsString());
            return [];
        }
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
            
            // Dosya sahiplik kontrolü
            $ownershipCheck = false;
            try {
                switch ($fileType) {
                    case 'upload':
                        // Ana dosya sahiplik kontrolü
                        $stmt = $this->pdo->prepare("SELECT user_id FROM file_uploads WHERE id = ?");
                        $stmt->execute([$fileId]);
                        $owner = $stmt->fetchColumn();
                        $ownershipCheck = ($owner === $userId);
                        break;
                        
                    case 'response':
                        // Yanıt dosyası sahiplik kontrolü (ana dosya sahibi İptal edebilir)
                        $stmt = $this->pdo->prepare("
                            SELECT fu.user_id 
                            FROM file_responses fr
                            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                            WHERE fr.id = ?
                        ");
                        $stmt->execute([$fileId]);
                        $owner = $stmt->fetchColumn();
                        $ownershipCheck = ($owner === $userId);
                        break;
                        
                    case 'revision':
                        // Revizyon dosyası sahiplik kontrolü
                        $stmt = $this->pdo->prepare("
                            SELECT rf.upload_id
                            FROM revision_files rf
                            WHERE rf.id = ?
                        ");
                        $stmt->execute([$fileId]);
                        $uploadId = $stmt->fetchColumn();
                        
                        if ($uploadId) {
                            $stmt = $this->pdo->prepare("SELECT user_id FROM file_uploads WHERE id = ?");
                            $stmt->execute([$uploadId]);
                            $owner = $stmt->fetchColumn();
                            $ownershipCheck = ($owner === $userId);
                        }
                        break;
                        
                    case 'additional':
                        // Ek dosya sahiplik kontrolü (receiver_id kullanıcı İptal edebilir)
                        $stmt = $this->pdo->prepare("SELECT receiver_id FROM additional_files WHERE id = ?");
                        $stmt->execute([$fileId]);
                        $receiver = $stmt->fetchColumn();
                        $ownershipCheck = ($receiver === $userId);
                        break;
                }
                
                if (!$ownershipCheck) {
                    return ['success' => false, 'message' => 'Bu dosyayı iptal etme yetkiniz yok.'];
                }
                
            } catch (PDOException $e) {
                error_log('Sahiplik kontrol hatası: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Dosya sahiplik kontrolü yapılamadı.'];
            }
            
            // Kredi hesaplaması yap - file-detail.php'deki aynı mantık
            $creditsToRefund = 0.00;
            
            try {
                if ($fileType === 'upload') {
                    // Ana dosya için tüm harcamaları hesapla
                    
                    // 1. Ana dosya için yanıt dosyalarında harcanan krediler
                    $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM file_responses WHERE upload_id = ?");
                    $stmt->execute([$fileId]);
                    $responseCredits = $stmt->fetchColumn() ?: 0;
                    $creditsToRefund += $responseCredits;
                    
                    // 2. Ana dosya için revizyon talepleri ve cevaplarında harcanan krediler
                    $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM revisions WHERE upload_id = ? AND user_id = ?");
                    $stmt->execute([$fileId, $userId]);
                    $revisionCredits = $stmt->fetchColumn() ?: 0;
                    $creditsToRefund += $revisionCredits;
                    
                    // 3. Ana dosya ile ilişkili yanıt dosyalarının revizyon talepleri için harcanan krediler
                    $stmt = $this->pdo->prepare("
                        SELECT COALESCE(SUM(r.credits_charged), 0) as total_credits 
                        FROM revisions r 
                        INNER JOIN file_responses fr ON r.response_id = fr.id 
                        WHERE fr.upload_id = ? AND r.user_id = ?
                    ");
                    $stmt->execute([$fileId, $userId]);
                    $responseRevisionCredits = $stmt->fetchColumn() ?: 0;
                    $creditsToRefund += $responseRevisionCredits;
                    
                    // 4. Ana dosya ile ilişkili ek dosyalar için harcanan krediler
                    $stmt = $this->pdo->prepare("
                        SELECT COALESCE(SUM(credits), 0) as total_credits 
                        FROM additional_files 
                        WHERE related_file_id = ? AND related_file_type = 'upload' AND receiver_id = ?
                    ");
                    $stmt->execute([$fileId, $userId]);
                    $additionalFileCredits = $stmt->fetchColumn() ?: 0;
                    $creditsToRefund += $additionalFileCredits;
                    
                } elseif ($fileType === 'response') {
                    // YANIT DOSYASI İPTALİ: Sadece yanıt dosyasının kendi ücreti iade edilir
                    // Yanıta bağlı revizyon dosyaları iptal edilmez!
                    $stmt = $this->pdo->prepare("SELECT COALESCE(credits_charged, 0) as credits FROM file_responses WHERE id = ?");
                    $stmt->execute([$fileId]);
                    $responseCredits = $stmt->fetchColumn() ?: 0;
                    $creditsToRefund += $responseCredits;
                    
                    // LOG: Yanıt dosyası iptal edildiğinde revizyon ücretleri iade edilmez
                    error_log("Response cancellation: Only response file credit ({$responseCredits}) will be refunded, NOT revision credits.");
                    
                } elseif ($fileType === 'revision') {
                    // Revizyon dosyası için - hangi revizyon talebine ait olduğunu bulup kredi bilgisini al
                    $stmt = $this->pdo->prepare("
                        SELECT rf.revision_id 
                        FROM revision_files rf 
                        WHERE rf.id = ?
                    ");
                    $stmt->execute([$fileId]);
                    $revisionId = $stmt->fetchColumn();
                    
                    if ($revisionId) {
                        // Bu revizyon talebi için harcanan kredileri al
                        $stmt = $this->pdo->prepare("SELECT COALESCE(credits_charged, 0) as credits FROM revisions WHERE id = ? AND user_id = ?");
                        $stmt->execute([$revisionId, $userId]);
                        $revisionCredits = $stmt->fetchColumn() ?: 0;
                        $creditsToRefund += $revisionCredits;
                    }
                    
                } elseif ($fileType === 'additional') {
                    // Ek dosya için harcanan kredileri al
                    $stmt = $this->pdo->prepare("SELECT COALESCE(credits, 0) as credits FROM additional_files WHERE id = ? AND receiver_id = ?");
                    $stmt->execute([$fileId, $userId]);
                    $additionalCredits = $stmt->fetchColumn() ?: 0;
                    $creditsToRefund += $additionalCredits;
                }
                
            } catch (PDOException $e) {
                error_log('FileCancellationManager kredi hesaplama hatası: ' . $e->getMessage());
                $creditsToRefund = 0.00;
            }
            
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
                // Log ekle
                error_log("İptal talebi oluşturuldu: FileID: {$fileId}, Type: {$fileType}, User: {$userId}, Kredi İadesi: {$creditsToRefund}");
                
                return [
                    'success' => true,
                    'message' => 'İptal talebi gönderildi.' . ($creditsToRefund > 0 ? " (İade edilecek kredi: {$creditsToRefund})" : ''),
                    'cancellation_id' => $cancellationId
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
     * Admin tarafından iptal talebini onayla - Dosyayı gizle ve kredi iadesi yap
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
            
            // İptal talebi bilgilerini al
            $stmt = $this->pdo->prepare("
                SELECT fc.*, fc.credits_to_refund, fc.user_id as request_user_id
                FROM file_cancellations fc
                WHERE fc.id = ? AND fc.status = 'pending'
            ");
            $stmt->execute([$cancellationId]);
            $cancellation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cancellation) {
                return ['success' => false, 'message' => 'İptal talebi bulunamadı veya zaten işlenmiş.'];
            }
            
            $this->pdo->beginTransaction();
            
            try {
                // 1. İptal talebini onayla
                $updateStmt = $this->pdo->prepare("
                    UPDATE file_cancellations 
                    SET status = 'approved', admin_id = ?, admin_notes = ?, processed_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$adminId, $adminNotes, $cancellationId]);
                
                // 2. Dosya tipine göre dosyayı gizle
                $fileType = $cancellation['file_type'];
                $fileId = $cancellation['file_id'];
                
                switch ($fileType) {
                    case 'upload':
                        // Ana dosyayı gizle
                        $hideStmt = $this->pdo->prepare("
                            UPDATE file_uploads 
                            SET is_cancelled = 1, cancelled_at = NOW(), cancelled_by = ?
                            WHERE id = ?
                        ");
                        $hideStmt->execute([$adminId, $fileId]);
                        break;
                        
                    case 'response':
                        // Yanıt dosyasını gizle
                        $hideStmt = $this->pdo->prepare("
                            UPDATE file_responses 
                            SET is_cancelled = 1, cancelled_at = NOW(), cancelled_by = ?
                            WHERE id = ?
                        ");
                        $hideStmt->execute([$adminId, $fileId]);
                        break;
                        
                    case 'revision':
                        // Revizyon dosyasını gizle
                        $hideStmt = $this->pdo->prepare("
                            UPDATE revision_files 
                            SET is_cancelled = 1, cancelled_at = NOW(), cancelled_by = ?
                            WHERE id = ?
                        ");
                        $hideStmt->execute([$adminId, $fileId]);
                        break;
                        
                    case 'additional':
                        // Ek dosyayı gizle
                        $hideStmt = $this->pdo->prepare("
                            UPDATE additional_files 
                            SET is_cancelled = 1, cancelled_at = NOW(), cancelled_by = ?
                            WHERE id = ?
                        ");
                        $hideStmt->execute([$adminId, $fileId]);
                        break;
                }
                
                // 3. Kredi iadesi yap (eğer ücretli dosya ise) - credits.php'deki aynı mantık
                $creditsToRefund = floatval($cancellation['credits_to_refund'] ?? 0);
                if ($creditsToRefund > 0 && !empty($cancellation['request_user_id'])) {
                    try {
                        // Kullanıcının mevcut kredi durumunu al
                        $userStmt = $this->pdo->prepare("SELECT credit_quota, credit_used FROM users WHERE id = ?");
                        $userStmt->execute([$cancellation['request_user_id']]);
                        $userCredits = $userStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($userCredits && $userCredits['credit_used'] >= $creditsToRefund) {
                            // TERS KREDİ SİSTEMİ: Kullanılan krediyi azalt (kredi iadesi)
                            $newCreditUsed = $userCredits['credit_used'] - $creditsToRefund;
                            
                            $creditUpdateStmt = $this->pdo->prepare("
                                UPDATE users 
                                SET credit_used = ?
                                WHERE id = ?
                            ");
                            $creditUpdateStmt->execute([$newCreditUsed, $cancellation['request_user_id']]);
                            
                            // Credit transactions tablosuna kaydet
                            $transactionId = generateUUID();
                            $transactionStmt = $this->pdo->prepare("
                                INSERT INTO credit_transactions (id, user_id, admin_id, transaction_type, type, amount, description, created_at) 
                                VALUES (?, ?, ?, 'withdraw', 'refund', ?, ?, NOW())
                            ");
                            $transactionStmt->execute([
                                $transactionId,
                                $cancellation['request_user_id'],
                                $adminId,
                                $creditsToRefund,
                                "Dosya iptal iadesi: {$cancellation['file_type']} dosyası için kredi iadesi"
                            ]);
                            
                            // Log için güncelle
                            error_log("Kredi iadesi: User ID {$cancellation['request_user_id']} - Eski kullanım: {$userCredits['credit_used']}, Yeni kullanım: {$newCreditUsed}, İade: {$creditsToRefund}");
                            
                            // Yeni kullanılabilir kredi hesapla
                            $availableCredits = $userCredits['credit_quota'] - $newCreditUsed;
                            error_log("Yeni kullanılabilir kredi: {$availableCredits}");
                            
                        } else {
                            error_log("Kredi iadesi yapılamadı: Yetersiz kullanılan kredi. Mevcut: " . ($userCredits['credit_used'] ?? 0) . ", İstenen: {$creditsToRefund}");
                            // Kredi iadesi yapılamazsa bile iptal işlemini tamamla
                        }
                        
                    } catch (Exception $creditError) {
                        error_log('Kredi iadesi hatası: ' . $creditError->getMessage());
                        // Kredi iadesi başarısız olsa bile iptal işlemini tamamla
                    }
                }
                
                // 4. Kullanıcıya bildirim gönder
                if ($this->notificationManager) {
                    $notificationTitle = 'İptal Talebiniz Onaylandı';
                    $notificationMessage = "Dosya iptal talebiniz onaylanmıştır. Dosya artık görünmeyecektir.";
                    
                    if ($creditsToRefund > 0) {
                        $notificationMessage .= " {$creditsToRefund} kredi hesabınıza iade edilmiştir.";
                    }
                    
                    $this->notificationManager->createNotification(
                        $cancellation['request_user_id'],
                        $notificationTitle,
                        $notificationMessage,
                        'file_cancellation',
                        $fileId
                    );
                }
                
                $this->pdo->commit();
                
                $message = 'İptal talebi onaylandı ve dosya gizlendi.';
                if ($creditsToRefund > 0) {
                    $message .= " {$creditsToRefund} kredi kullanıcıya iade edildi.";
                }
                
                return ['success' => true, 'message' => $message];
                
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
     * Admin tarafından iptal talebini reddet (basit versiyon)
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
            
            $stmt = $this->pdo->prepare("
                UPDATE file_cancellations 
                SET status = 'rejected', admin_id = ?, admin_notes = ?, processed_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            
            $result = $stmt->execute([$adminId, $adminNotes, $cancellationId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'İptal talebi reddedildi.'];
            } else {
                return ['success' => false, 'message' => 'İptal talebi bulunamadı veya zaten işlenmiş.'];
            }
            
        } catch (Exception $e) {
            error_log('FileCancellationManager rejectCancellation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Red işlemi sırasında hata oluştu.'];
        }
    }
    
    /**
     * Admin için tüm iptal taleplerini getir (gelişmiş versiyon)
     * @param int $page - Sayfa
     * @param int $limit - Limit
     * @param string $status - Durum filtresi
     * @param string $fileType - Dosya tipi filtresi
     * @param string $search - Arama terimi
     * @return array - İptal talepleri
     */
    public function getAllCancellations($page = 1, $limit = 20, $status = '', $fileType = '', $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = 'WHERE 1=1';
            $params = [];
            
            // Durum filtresi
            if (!empty($status)) {
                $whereClause .= ' AND fc.status = ?';
                $params[] = $status;
            }
            
            // Dosya tipi filtresi
            if (!empty($fileType)) {
                $whereClause .= ' AND fc.file_type = ?';
                $params[] = $fileType;
            }
            
            // Arama filtresi
            if (!empty($search)) {
                $whereClause .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR'
                              . ' fu.original_name LIKE ? OR fu.plate LIKE ?'
                              . ' OR fr.original_name LIKE ?'
                              . ' OR rf.original_name LIKE ?'
                              . ' OR af.original_name LIKE ?)';
                $searchTerm = '%' . $search . '%';
                for ($i = 0; $i < 9; $i++) {
                    $params[] = $searchTerm;
                }
            }
            
            $sql = "
                SELECT fc.*, 
                       u.username, u.first_name, u.last_name, u.email,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       
                       -- Ana dosya bilgileri
                       fu.original_name as upload_file_name,
                       fu.plate as upload_plate,
                       fu.upload_date as upload_date,
                       
                       -- Yanıt dosyası bilgileri
                       fr.original_name as response_file_name,
                       fr.upload_date as response_date,
                       fu_main.original_name as response_main_file_name,
                       fu_main.plate as response_main_plate,
                       
                       -- Revizyon dosyası bilgileri
                       rf.original_name as revision_file_name,
                       rf.upload_date as revision_date,
                       fu_rev.original_name as revision_main_file_name,
                       fu_rev.plate as revision_main_plate,
                       
                       -- Ek dosya bilgileri
                       af.original_name as additional_file_name,
                       af.upload_date as additional_date,
                       fu_add.original_name as additional_main_file_name,
                       fu_add.plate as additional_main_plate
                       
                FROM file_cancellations fc
                LEFT JOIN users u ON fc.user_id = u.id
                LEFT JOIN users a ON fc.admin_id = a.id
                
                -- Ana dosya tablosu (upload tipi için)
                LEFT JOIN file_uploads fu ON fc.file_type = 'upload' AND fc.file_id = fu.id
                
                -- Yanıt dosyası tablosu (response tipi için)
                LEFT JOIN file_responses fr ON fc.file_type = 'response' AND fc.file_id = fr.id
                LEFT JOIN file_uploads fu_main ON fr.upload_id = fu_main.id
                
                -- Revizyon dosyası tablosu (revision tipi için)
                LEFT JOIN revision_files rf ON fc.file_type = 'revision' AND fc.file_id = rf.id
                LEFT JOIN revisions rev ON rf.revision_id = rev.id
                LEFT JOIN file_uploads fu_rev ON rev.upload_id = fu_rev.id
                
                -- Ek dosya tablosu (additional tipi için)
                LEFT JOIN additional_files af ON fc.file_type = 'additional' AND fc.file_id = af.id
                LEFT JOIN file_uploads fu_add ON af.related_file_type = 'upload' AND af.related_file_id = fu_add.id
                
                {$whereClause}
                ORDER BY fc.requested_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
            
            error_log('getAllCancellations SQL: ' . $sql);
            error_log('getAllCancellations params: ' . print_r($params, true));
            
            if (empty($params)) {
                $stmt = $this->pdo->query($sql);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            error_log('getAllCancellations: Found ' . count($result) . ' cancellations');
            
            // Debug: İlk sonucun dosya bilgilerini kontrol et
            if (!empty($result)) {
                $first = $result[0];
                error_log('First result debug - FileType: ' . $first['file_type'] . ', FileName fields: upload=' . ($first['upload_file_name'] ?? 'NULL') . ', response=' . ($first['response_file_name'] ?? 'NULL') . ', plate=' . ($first['upload_plate'] ?? 'NULL'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('getAllCancellations error: ' . $e->getMessage());
            error_log('getAllCancellations trace: ' . $e->getTraceAsString());
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
