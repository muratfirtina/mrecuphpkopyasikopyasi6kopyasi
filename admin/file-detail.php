<?php
/**
 * Mr ECU - File Detail Page (Updated for Response Files)
 * Yanıt dosyaları desteği ile güncellenmiş dosya detay sayfası
 */

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

try {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    // PDO kontrolü
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Gerekli fonksiyonları kontrol et ve eksikse tanımla
    if (!function_exists('isValidUUID')) {
        function isValidUUID($uuid) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
        }
    }
    
    if (!function_exists('sanitize')) {
        function sanitize($data) {
            if (is_array($data)) {
                return array_map('sanitize', $data);
            }
            return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    if (!function_exists('redirect')) {
        function redirect($url) {
            if (headers_sent()) {
                echo "<script>window.location.href = '$url';</script>";
                echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
            } else {
                header("Location: " . $url);
            }
            exit();
        }
    }
    
    if (!function_exists('isLoggedIn')) {
        function isLoggedIn() {
            return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        }
    }
    
    if (!function_exists('isAdmin')) {
        function isAdmin() {
            if (isset($_SESSION['role'])) {
                return $_SESSION['role'] === 'admin';
            }
            return isset($_SESSION['is_admin']) && ((int)$_SESSION['is_admin'] === 1);
        }
    }
    
    if (!function_exists('formatFileSize')) {
        function formatFileSize($bytes) {
            if ($bytes === 0) return '0 B';
            $k = 1024;
            $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = floor(log($bytes) / log($k));
            return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
        }
    }
    
    if (!function_exists('formatDate')) {
        function formatDate($date) {
            return date('d.m.Y H:i', strtotime($date));
        }
    }
    
    // Class includes
    require_once '../includes/FileManager.php';
    require_once '../includes/User.php';
    
    // Session kontrolü
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Admin kontrolü
    if (!isLoggedIn() || !isAdmin()) {
        redirect('../login.php?error=access_denied');
    }
    
} catch (Exception $e) {
    error_log('File detail init error: ' . $e->getMessage());
    echo "<div class='alert alert-danger'>Sistem hatası: " . $e->getMessage() . "</div>";
    echo "<p><a href='../login.php'>Giriş sayfasına dön</a></p>";
    exit;
}
$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Upload ID kontrolü
if (!isset($_GET['id']) || !isValidUUID($_GET['id'])) {
    redirect('uploads.php');
}

$uploadId = $_GET['id'];
$fileType = isset($_GET['type']) ? sanitize($_GET['type']) : 'upload'; // 'upload' or 'response'
$error = '';
$success = '';

// URL'den success mesajını al
if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

// URL'den error mesajını al
if (isset($_GET['error'])) {
    $error = sanitize($_GET['error']);
}

// İşlem kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received on file-detail.php");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    try {
        // Durum güncelleme
        if (isset($_POST['update_status'])) {
            error_log("Status update request");
            $status = sanitize($_POST['status']);
            $adminNotes = sanitize($_POST['admin_notes']);
            
            if ($fileManager->updateUploadStatus($uploadId, $status, $adminNotes)) {
                $success = 'Dosya durumu başarıyla güncellendi.';
                $user->logAction($_SESSION['user_id'], 'status_update', "Dosya #{$uploadId} durumu {$status} olarak güncellendi");
            } else {
                $error = 'Durum güncellenirken hata oluştu.';
            }
        }
        
        // Yanıt dosyası yükleme
        if (isset($_FILES['response_file']) && isset($_POST['upload_response'])) {
            error_log("Response file upload request started");
            
            // Dosya yükleme hatası kontrolü
            if (!isset($_FILES['response_file']) || $_FILES['response_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Dosya çok büyük (php.ini limit)',
                    UPLOAD_ERR_FORM_SIZE => 'Dosya çok büyük (form limit)',
                    UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
                    UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi',
                    UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin yok',
                    UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı',
                    UPLOAD_ERR_EXTENSION => 'Uzantı yüklemeyi durdurdu'
                ];
                
                $fileError = $_FILES['response_file']['error'] ?? UPLOAD_ERR_NO_FILE;
                $error = 'Dosya yükleme hatası: ' . ($errorMessages[$fileError] ?? 'Bilinmeyen hata (' . $fileError . ')');
                error_log("File upload error: " . $error);
            } else {
                $creditsCharged = floatval($_POST['credits_charged'] ?? 0);
                $responseNotes = sanitize($_POST['response_notes'] ?? '');
                
                error_log("Processing upload - Credits: $creditsCharged, Notes: $responseNotes");
                
                if ($creditsCharged < 0) {
                    $error = 'Kredi miktarı negatif olamaz.';
                    error_log("Negative credits error");
                } else {
                    error_log("Calling uploadResponseFile method");
                    
                    // Session kontrolü
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception("User session not found");
                    }
                    
                    $result = $fileManager->uploadResponseFile($uploadId, $_FILES['response_file'], $creditsCharged, $responseNotes);
                    
                    error_log("Upload result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $success = $result['message'];
                        $user->logAction($_SESSION['user_id'], 'response_upload', "Yanıt dosyası yüklendi: {$uploadId}");
                        
                        // Başarılı yükleme sonrası redirect
                        header("Location: file-detail.php?id={$uploadId}&success=" . urlencode($success));
                        exit;
                    } else {
                        $error = $result['message'];
                        error_log("Upload failed: " . $error);
                    }
                }
            }
        }
        
        // Revize yanıt dosyası yükleme (response dosyası için yeni yanıt dosyası)
        if (isset($_FILES['revised_response_file']) && isset($_POST['upload_revised_response'])) {
            error_log("Revised response file upload request");
            
            if (!isset($_FILES['revised_response_file']) || $_FILES['revised_response_file']['error'] !== UPLOAD_ERR_OK) {
                $fileError = $_FILES['revised_response_file']['error'] ?? UPLOAD_ERR_NO_FILE;
                $error = 'Revize dosyası yükleme hatası: ' . $fileError;
                error_log("Revised file upload error: " . $error);
            } else {
                $creditsCharged = floatval($_POST['revised_credits_charged'] ?? 0);
                $responseNotes = sanitize($_POST['revised_response_notes'] ?? '');
                
                if ($creditsCharged < 0) {
                    $error = 'Kredi miktarı negatif olamaz.';
                } else {
                    $result = $fileManager->uploadResponseFile($uploadId, $_FILES['revised_response_file'], $creditsCharged, $responseNotes);
                    
                    if ($result['success']) {
                        $success = 'Revize edilmiş yanıt dosyası başarıyla yüklendi.';
                        
                        // Revize durumunu completed yap
                        try {
                            $stmt = $pdo->prepare("
                                UPDATE revisions 
                                SET status = 'completed', completed_at = NOW()
                                WHERE upload_id = ? AND response_id IS NOT NULL AND status = 'in_progress'
                                ORDER BY requested_at DESC
                                LIMIT 1
                            ");
                            $stmt->execute([$uploadId]);
                        } catch(PDOException $e) {
                            error_log('Revize durumu güncellenemedi: ' . $e->getMessage());
                        }
                        
                        $user->logAction($_SESSION['user_id'], 'response_revision_upload', "Revize yanıt dosyası yüklendi: {$uploadId}");
                        
                        // Başarılı yükleme sonrası redirect
                        header("Location: file-detail.php?id={$uploadId}&type={$fileType}&success=" . urlencode($success));
                        exit;
                    } else {
                        $error = $result['message'];
                    }
                }
            }
        }
        
        // Revize talebini onaylama (işleme alma)
        if (isset($_POST['approve_revision_direct'])) {
            error_log("Direct revision approval started");
            $revisionId = sanitize($_POST['revision_id']);
            
            if (!isValidUUID($revisionId)) {
                $error = 'Geçersiz revize ID formatı.';
                error_log("Invalid revision ID format: " . $revisionId);
            } else {
                $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'in_progress', 'Revize talebi işleme alındı.', 0);
                
                if ($result['success']) {
                    $success = 'Revize talebi işleme alındı. Şimdi revize edilmiş dosyayı yükleyebilirsiniz.';
                    $user->logAction($_SESSION['user_id'], 'revision_approved', "Revize talebi işleme alındı: {$revisionId}");
                    
                    // Başarılı işlem sonrası redirect
                    header("Location: file-detail.php?id={$uploadId}&type={$fileType}&success=" . urlencode($success));
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        // Revize talebini reddetme
        if (isset($_POST['reject_revision_direct'])) {
            error_log("Direct revision rejection started");
            $revisionId = sanitize($_POST['revision_id']);
            $adminNotes = sanitize($_POST['admin_notes']) ?: 'Revize talebi reddedildi.';
            
            if (!isValidUUID($revisionId)) {
                $error = 'Geçersiz revize ID formatı.';
                error_log("Invalid revision ID format: " . $revisionId);
            } else {
                $result = $fileManager->updateRevisionStatus($revisionId, $_SESSION['user_id'], 'rejected', $adminNotes, 0);
                
                if ($result['success']) {
                    $success = 'Revize talebi reddedildi.';
                    $user->logAction($_SESSION['user_id'], 'revision_rejected', "Revize talebi reddedildi: {$revisionId}");
                    
                    // Başarılı işlem sonrası redirect
                    header("Location: file-detail.php?id={$uploadId}&type={$fileType}&success=" . urlencode($success));
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
        
        // Revizyon dosyası yükleme (yeni eklenen)
        if (isset($_FILES['revision_file']) && isset($_POST['upload_revision'])) {
            error_log("Revision file upload request started");
            $revisionId = sanitize($_POST['revision_id']);
            $adminNotes = sanitize($_POST['revision_notes'] ?? '');
            
            if (!isValidUUID($revisionId)) {
                $error = 'Geçersiz revizyon ID formatı.';
                error_log("Invalid revision ID format: " . $revisionId);
            } else {
                // Dosya yükleme hatası kontrolü
                if (!isset($_FILES['revision_file']) || $_FILES['revision_file']['error'] !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'Dosya çok büyük (php.ini limit)',
                        UPLOAD_ERR_FORM_SIZE => 'Dosya çok büyük (form limit)',
                        UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
                        UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi',
                        UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin yok',
                        UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı',
                        UPLOAD_ERR_EXTENSION => 'Uzantı yüklemeyi durdurdu'
                    ];
                    
                    $fileError = $_FILES['revision_file']['error'] ?? UPLOAD_ERR_NO_FILE;
                    $error = 'Revizyon dosyası yükleme hatası: ' . ($errorMessages[$fileError] ?? 'Bilinmeyen hata (' . $fileError . ')');
                    error_log("Revision file upload error: " . $error);
                } else {
                    error_log("Processing revision file upload - Notes: " . $adminNotes);
                    
                    // Session kontrolü
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception("User session not found");
                    }
                    
                    $result = $fileManager->uploadRevisionFile($revisionId, $_FILES['revision_file'], $adminNotes);
                    
                    error_log("Revision upload result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $success = $result['message'];
                        $user->logAction($_SESSION['user_id'], 'revision_file_upload', "Revizyon dosyası yüklendi: {$revisionId}");
                        
                        // Başarılı yükleme sonrası redirect
                        header("Location: file-detail.php?id={$uploadId}&type={$fileType}&success=" . urlencode($success));
                        exit;
                    } else {
                        $error = $result['message'];
                        error_log("Revision upload failed: " . $error);
                    }
                }
            }
        }
        
    
// Revizyon dosyası yükleme (yeni eklenen)
if (isset($_FILES['revision_file']) && isset($_POST['upload_revision'])) {
    error_log("Revision file upload request started");
    $revisionId = sanitize($_POST['revision_id']);
    $adminNotes = sanitize($_POST['revision_notes'] ?? '');
    
    if (!isValidUUID($revisionId)) {
        $error = 'Geçersiz revizyon ID formatı.';
        error_log("Invalid revision ID format: " . $revisionId);
    } else {
        // Dosya yükleme hatası kontrolü
        if (!isset($_FILES['revision_file']) || $_FILES['revision_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Dosya çok büyük (php.ini limit)',
                UPLOAD_ERR_FORM_SIZE => 'Dosya çok büyük (form limit)',
                UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
                UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin yok',
                UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı',
                UPLOAD_ERR_EXTENSION => 'Uzantı yüklemeyi durdurdu'
            ];
            
            $fileError = $_FILES['revision_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $error = 'Revizyon dosyası yükleme hatası: ' . ($errorMessages[$fileError] ?? 'Bilinmeyen hata (' . $fileError . ')');
            error_log("Revision file upload error: " . $error);
        } else {
            error_log("Processing revision file upload - Notes: " . $adminNotes);
            
            // Session kontrolü
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("User session not found");
            }
            
            $result = $fileManager->uploadRevisionFile($revisionId, $_FILES['revision_file'], $adminNotes);
            
            error_log("Revision upload result: " . print_r($result, true));
            
            if ($result['success']) {
                $success = $result['message'];
                $user->logAction($_SESSION['user_id'], 'revision_file_upload', "Revizyon dosyası yüklendi: {$revisionId}");
                
                // Başarılı yükleme sonrası redirect
                header("Location: file-detail.php?id={$uploadId}&type={$fileType}&success=" . urlencode($success));
                exit;
            } else {
                $error = $result['message'];
                error_log("Revision upload failed: " . $error);
            }
        }
    }
}
        
        } catch (Exception $e) {
        $error = 'İşlem sırasında hata oluştu: ' . $e->getMessage();
        error_log('POST processing error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
    }
    
    error_log("POST processing completed - Error: " . ($error ?: 'None') . ", Success: " . ($success ?: 'None'));
}

// Dosya detaylarını al
try {
    if ($fileType === 'response') {
        // Response dosyası detaylarını al
        $responseId = isset($_GET['response_id']) ? sanitize($_GET['response_id']) : null;
        
        if ($responseId) {
            // Spesifik response dosyasını al
            $stmt = $pdo->prepare("
                SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
                       fu.brand_id, fu.model_id, fu.year, fu.plate, fu.ecu_type, fu.engine_code,
                       fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                       b.name as brand_name, m.name as model_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       u.username, u.email, u.first_name, u.last_name
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN users a ON fr.admin_id = a.id
                LEFT JOIN users u ON fu.user_id = u.id
                WHERE fr.id = ? AND fr.upload_id = ?
            ");
            $stmt->execute([$responseId, $uploadId]);
        } else {
            // En son response dosyasını al (eski davranış)
            $stmt = $pdo->prepare("
                SELECT fr.*, fu.user_id, fu.original_name as original_upload_name,
                       fu.brand_id, fu.model_id, fu.year, fu.plate, fu.ecu_type, fu.engine_code,
                       fu.gearbox_type, fu.fuel_type, fu.hp_power, fu.nm_torque,
                       b.name as brand_name, m.name as model_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       u.username, u.email, u.first_name, u.last_name
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                LEFT JOIN users a ON fr.admin_id = a.id
                LEFT JOIN users u ON fu.user_id = u.id
                WHERE fr.upload_id = ?
                ORDER BY fr.upload_date DESC
                LIMIT 1
            ");
            $stmt->execute([$uploadId]);
        }
        $upload = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$upload) {
            $_SESSION['error'] = 'Response dosyası bulunamadı.';
            redirect('uploads.php');
        }
        
        // Response dosyası için file_path ayarla
        if (!empty($upload['filename'])) {
            $upload['file_path'] = '../uploads/response_files/' . $upload['filename'];
        }
        
        // Response dosyası ID'sini kaydet (indir butonu için) - eğer zaten set edilmemişse
        if (!$responseId) {
            $responseId = $upload['id'];
        }
        
        // Response dosyası için responseFiles'i boş bırak
        $responseFiles = [];
        
        // Bu response dosyası için onaylanmış revize talebi var mı?
        $stmt = $pdo->prepare("
            SELECT * FROM revisions 
            WHERE response_id = ? AND status = 'in_progress'
            ORDER BY requested_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$responseId]);
        $approvedRevision = $stmt->fetch();
        
    } else {
        // Normal upload dosyası detaylarını al
        $upload = $fileManager->getUploadById($uploadId);
        
        if (!$upload) {
            redirect('uploads.php');
        }
        
        $responseId = null;
        $approvedRevision = null;
        
        // file_responses tablosundan yanıt dosyalarını al
        $stmt = $pdo->prepare("
            SELECT fr.*, u.username as admin_username, u.first_name, u.last_name
            FROM file_responses fr
            LEFT JOIN users u ON fr.admin_id = u.id
            WHERE fr.upload_id = ?
            ORDER BY fr.upload_date DESC
        ");
        $stmt->execute([$uploadId]);
        $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Response dosyalarına file_path ekle
        foreach ($responseFiles as &$response) {
            if (!empty($response['filename'])) {
                $response['file_path'] = '../uploads/response_files/' . $response['filename'];
                $response['response_file'] = $response['filename']; // Uyumluluk için
            }
        }
    }
    
    // Kullanıcıyla olan tüm iletişim geçmişini topla (kronolojik sırada)
    $communicationHistory = [];
    $revisionRequests = [];
    
    try {
        // Önce revize taleplerini çek
        if ($fileType === 'response') {
            // Response dosyası için revize taleplerini al
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.first_name, u.last_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name
                FROM revisions r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN users a ON r.admin_id = a.id
                WHERE r.response_id = ?
                ORDER BY r.requested_at DESC
            ");
            $stmt->execute([$responseId]);
        } else {
            // Normal upload dosyası için TÜM revize taleplerini al (ana dosya + response dosyaları)
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.first_name, u.last_name,
                       a.username as admin_username, a.first_name as admin_first_name, a.last_name as admin_last_name,
                       fr.original_name as response_file_name
                FROM revisions r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN users a ON r.admin_id = a.id
                LEFT JOIN file_responses fr ON r.response_id = fr.id
                WHERE (r.upload_id = ? OR (r.response_id IS NOT NULL AND fr.upload_id = ?))
                ORDER BY r.requested_at DESC
            ");
            $stmt->execute([$uploadId, $uploadId]);
        }
        $revisionRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // DEBUG: Revize taleplerini logla
        error_log('DEBUG: Revision Requests for upload_id: ' . $uploadId);
        foreach ($revisionRequests as $rev) {
            error_log('DEBUG: Revision ID: ' . $rev['id'] . ', Request Notes: ' . $rev['request_notes']);
        }
        
    } catch (Exception $e) {
        error_log('Revision requests query error: ' . $e->getMessage());
        $revisionRequests = [];
    }
    
    try {
        // 1. Ana dosya yükleme notları
        if (!empty($upload['upload_notes'])) {
            $communicationHistory[] = [
                'type' => 'user_upload',
                'date' => $upload['upload_date'],
                'user_notes' => $upload['upload_notes'],
                'admin_notes' => '',
                'status' => 'info',
                'file_name' => $upload['original_name'],
                'is_main_file' => true
            ];
        }
        
        // 2. Yanıt dosyaları ve admin notları (sadece normal dosyalar için)
        if ($fileType !== 'response') {
            $stmt = $pdo->prepare("
                SELECT fr.*, u.username as admin_username, u.first_name, u.last_name
                FROM file_responses fr
                LEFT JOIN users u ON fr.admin_id = u.id
                WHERE fr.upload_id = ?
                ORDER BY fr.upload_date ASC
            ");
            $stmt->execute([$uploadId]);
            $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($responseFiles as $response) {
                if (!empty($response['admin_notes']) && filterAdminNotes($response['admin_notes'])) {
                    $communicationHistory[] = [
                        'type' => 'admin_response',
                        'date' => $response['upload_date'],
                        'user_notes' => '',
                        'admin_notes' => $response['admin_notes'],
                        'admin_username' => $response['admin_username'],
                        'admin_name' => ($response['first_name'] ?? '') . ' ' . ($response['last_name'] ?? ''),
                        'status' => 'success',
                        'file_name' => $response['original_name'],
                        'credits_charged' => $response['credits_charged'] ?? 0,
                        'response_id' => $response['id']
                    ];
                }
            }
        }
        
        // 3. Revize talepleri (detaylı olanlar) - User sayfasındaki mantıkla
        foreach ($revisionRequests as $revision) {
            // DEBUG: Her revize talebi için user notes'u logla
            error_log('DEBUG: Adding revision to communication history - ID: ' . $revision['id'] . ', User Notes: "' . $revision['request_notes'] . '"');
            
            // Kullanıcının revize talebi
            $communicationHistory[] = [
                'type' => 'user_revision_request',
                'date' => $revision['requested_at'],
                'user_notes' => $revision['request_notes'],
                'admin_notes' => '',
                'status' => $revision['status'],
                'revision_id' => $revision['id'],
                'revision_status' => $revision['status'],
                'response_file_name' => $revision['response_file_name'] ?? null // Hangi dosya için revize talebi
            ];
            
            // Admin'in revize cevabı (varsa ve geçerli ise)
            if (!empty($revision['admin_notes'])) {
                $communicationHistory[] = [
                    'type' => 'admin_revision_response',
                    'date' => $revision['completed_at'] ?: $revision['updated_at'] ?: $revision['requested_at'],
                    'user_notes' => '',
                    'admin_notes' => $revision['admin_notes'],
                    'admin_username' => $revision['admin_username'] ?? '',
                    'admin_name' => ($revision['admin_first_name'] ?? '') . ' ' . ($revision['admin_last_name'] ?? ''),
                    'status' => $revision['status'] === 'completed' ? 'success' : ($revision['status'] === 'rejected' ? 'danger' : 'info'),
                    'revision_id' => $revision['id'],
                    'revision_status' => $revision['status'],
                    'credits_charged' => $revision['credits_charged'] ?? 0,
                    'response_file_name' => $revision['response_file_name'] ?? null // Hangi dosya için revize cevabı
                ];
            }
        }
        
        // 4. Yanıt dosyası durumunda, sadece o yanıt için admin notlarını ekle
        if ($fileType === 'response') {
            if (!empty($upload['admin_notes']) && filterAdminNotes($upload['admin_notes'])) {
                $communicationHistory[] = [
                    'type' => 'admin_response',
                    'date' => $upload['upload_date'],
                    'user_notes' => '',
                    'admin_notes' => $upload['admin_notes'],
                    'admin_username' => $upload['admin_username'] ?? '',
                    'admin_name' => ($upload['admin_first_name'] ?? '') . ' ' . ($upload['admin_last_name'] ?? ''),
                    'status' => 'success',
                    'file_name' => $upload['original_name'],
                    'credits_charged' => $upload['credits_charged'] ?? 0
                ];
            }
        }
        
        // Tarihe göre sırala (en eskiden yeniye)
        usort($communicationHistory, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
    } catch (Exception $e) {
        error_log('Communication history error: ' . $e->getMessage());
        $communicationHistory = [];
    }
    
   
    
    // Kredi geçmişini al
    $stmt = $pdo->prepare("
        SELECT * FROM credit_transactions 
        WHERE user_id = ? AND description LIKE ?
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$upload['user_id'], '%' . $uploadId . '%']);
    $creditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcının diğer dosyalarını al
    $stmt = $pdo->prepare("
        SELECT id, original_name, status, upload_date 
        FROM file_uploads 
        WHERE user_id = ? AND id != ? 
        ORDER BY upload_date DESC 
        LIMIT 10
    ");
    $stmt->execute([$upload['user_id'], $uploadId]);
    $otherFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // System logs - Kolon adını kontrol et
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM system_logs LIKE 'details'");
        $hasDetailsColumn = $stmt->fetch();
        
        if ($hasDetailsColumn) {
            $stmt = $pdo->prepare("
                SELECT * FROM system_logs 
                WHERE (details LIKE ? OR details LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute(['%' . $uploadId . '%', '%' . $upload['original_name'] . '%']);
        } else {
            // 'details' kolonu yok, 'description' kullan
            $stmt = $pdo->prepare("
                SELECT * FROM system_logs 
                WHERE (description LIKE ? OR description LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute(['%' . $uploadId . '%', '%' . $upload['original_name'] . '%']);
        }
        $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('System logs query error: ' . $e->getMessage());
        $systemLogs = [];
    }
    
} catch (Exception $e) {
    error_log('File detail error: ' . $e->getMessage());
    redirect('uploads.php');
}

// Dosya path kontrolü
function checkFilePath($filePath) {
    if (empty($filePath)) return ['exists' => false, 'path' => ''];
    
    $fullPath = $filePath;
    
    // Path düzeltmeleri
    if (strpos($fullPath, '../uploads/') === 0) {
        $fullPath = str_replace('../uploads/', $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/', $fullPath);
    } elseif (strpos($fullPath, $_SERVER['DOCUMENT_ROOT']) !== 0) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . ltrim($fullPath, '/');
    }
    
    return [
        'exists' => file_exists($fullPath),
        'path' => $fullPath,
        'size' => file_exists($fullPath) ? filesize($fullPath) : 0
    ];
}

// Filename'den path oluştur
function checkFileByName($filename, $type = 'user') {
    if (empty($filename)) {
        error_log('checkFileByName: Filename boş');
        return ['exists' => false, 'path' => ''];
    }
    
    $subdir = $type === 'response' ? 'response_files' : 'user_files';
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $subdir . '/' . $filename;
    
    $exists = file_exists($fullPath);
    
    // Debug info
    error_log('checkFileByName: filename=' . $filename . ', type=' . $type . ', path=' . $fullPath . ', exists=' . ($exists ? 'true' : 'false'));
    
    return [
        'exists' => $exists,
        'path' => $fullPath,
        'size' => $exists ? filesize($fullPath) : 0
    ];
}

// Admin notlarını filtreleyen fonksiyon - sadece kesin sistem mesajlarını filtreler
function filterAdminNotes($adminNotes) {
    if (empty($adminNotes)) {
        return false;
    }
    
    $trimmedNotes = trim($adminNotes);
    
    // Sadece kesin sistem mesajlarını filtrele
    $exactSystemMessages = [
        'Revize talebi işleme alındı.',
        'Dosya işleme alındı',
        'Dosya başarıyla yüklendi.'
    ];
    
    // Eğer tam olarak bu mesajlardan biriyse filtrele
    if (in_array($trimmedNotes, $exactSystemMessages)) {
        return false;
    }
    
    // "Yanıt dosyası yüklendi:" ile başlayıp dosya adı içeren otomatik mesajları filtrele
    if (strpos($trimmedNotes, 'Yanıt dosyası yüklendi:') === 0 && strpos($trimmedNotes, '.zip') !== false) {
        return false;
    }
    
    // Diğer her şey gerçek admin notu
    return true;
}

// Filtrelenmiş admin notlarını gösteren fonksiyon
function displayAdminNotes($adminNotes, $emptyMessage = 'Henüz admin notu eklenmedi.') {
    if (empty($adminNotes)) {
        return '<em class="text-muted">' . $emptyMessage . '</em>';
    }
    
    if (filterAdminNotes($adminNotes)) {
        return nl2br(htmlspecialchars($adminNotes));
    } else {
        return '<em class="text-muted">' . $emptyMessage . '</em>';
    }
}

// Güvenli HTML output fonksiyonu
function safeHtml($value) {
    return $value !== null ? htmlspecialchars($value) : '<em style="color: #999;">Belirtilmemiş</em>';
}

if ($fileType === 'response') {
    $originalFileCheck = checkFileByName($upload['filename'], 'response');
    $pageTitle = 'Yanıt Dosyası Detayları - ' . htmlspecialchars($upload['original_name']);
    $pageDescription = 'Yanıt dosyası detaylarını görüntüleyin ve yönetin';
    
    // Debug info
    error_log('Response file debug - ResponseId: ' . $responseId . ', Filename: ' . $upload['filename']);
} else {
    $originalFileCheck = checkFileByName($upload['filename'], 'user');
    $pageTitle = 'Dosya Detayları - ' . htmlspecialchars($upload['original_name']);
    $pageDescription = 'Dosya detaylarını görüntüleyin ve yönetin';
}

$pageIcon = 'fas fa-file-alt';

// Header ve Sidebar include
include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="uploads.php">Dosyalar</a></li>
        <li class="breadcrumb-item active" aria-current="page">
            <?php if ($fileType === 'response'): ?>
                Yanıt Dosyası: <?php echo htmlspecialchars($upload['original_name']); ?>
            <?php else: ?>
                Dosya Detayı
            <?php endif; ?>
        </li>
    </ol>
</nav>

<!-- Hata/Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Dosya Detay Kartı -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $fileType === 'response' ? 'reply' : 'file-alt'; ?> me-2"></i>
                <?php echo $fileType === 'response' ? 'Yanıt Dosyası' : 'Dosya'; ?> Detayları
            </h5>
            <div class="d-flex gap-2">
                <?php if ($fileType === 'response'): ?>
                    <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-file-alt me-1"></i>Orijinal Dosyayı Görüntüle
                    </a>
                    <a href="file-detail.php?id=<?php echo $uploadId; ?>#response-files" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list me-1"></i>Tüm Yanıtları Görüntüle
                    </a>
                <?php endif; ?>
                
                <?php if ($originalFileCheck['exists']): ?>
                    <?php if ($fileType === 'response'): ?>
                        <a href="download-file.php?id=<?php echo $responseId; ?>&type=response" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i>İndir
                        </a>
                    <?php else: ?>
                        <a href="download-file.php?id=<?php echo $uploadId; ?>&type=upload" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i>İndir
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- revision-form-html.php dosyasının içeriğini buraya kopyala -->
     <!-- ==================== REVİZYON DOSYASI YÜKLEMESİ ==================== -->
<?php
// Bu dosya için işlemdeki revizyon taleplerini kontrol et
try {
    $stmt = $pdo->prepare("
        SELECT r.*, fu.original_name, u.username, u.first_name, u.last_name
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id  
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.upload_id = ? AND r.status = 'in_progress'
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([$uploadId]);
    $activeRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $activeRevisions = [];
    error_log('Revizyon sorgusu hatası: ' . $e->getMessage());
}
?>

<?php if (!empty($activeRevisions)): ?>
    <div class="card admin-card mb-4">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0">
                <i class="fas fa-cogs me-2"></i>
                İşlemdeki Revizyon Talepleri (<?php echo count($activeRevisions); ?> adet)
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Bu dosya için işlemdeki revizyon talepleri bulundu.</strong><br>
                Aşağıdaki formları kullanarak revizyon dosyalarını yükleyebilirsiniz.
            </div>
            
            <?php foreach ($activeRevisions as $revision): ?>
                <div class="revision-upload-section mb-4 p-3 border rounded bg-light">
                    <div class="revision-info mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($revision['first_name'] . ' ' . $revision['last_name']); ?> 
                            (@<?php echo htmlspecialchars($revision['username']); ?>)
                        </h6>
                        <div class="revision-details">
                            <p><strong>Talep Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($revision['requested_at'])); ?></p>
                            <p><strong>Revizyon ID:</strong> <code><?php echo substr($revision['id'], 0, 8); ?>...</code></p>
                            <div class="bg-white p-3 rounded border">
                                <strong>Kullanıcının Revizyon Talebi:</strong><br>
                                <?php echo nl2br(htmlspecialchars($revision['request_notes'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Revizyon Dosyası Yükleme Formu -->
                    <form method="POST" enctype="multipart/form-data" class="revision-upload-form">
                        <input type="hidden" name="revision_id" value="<?php echo $revision['id']; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="revision_file_<?php echo $revision['id']; ?>" class="form-label">
                                    <i class="fas fa-file-upload me-1"></i>
                                    Revizyon Dosyası <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" 
                                       id="revision_file_<?php echo $revision['id']; ?>" 
                                       name="revision_file" required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Revize edilmiş dosyayı seçin (Max: <?php echo ini_get('upload_max_filesize'); ?>)
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="revision_notes_<?php echo $revision['id']; ?>" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>
                                    Admin Notları
                                </label>
                                <textarea class="form-control" 
                                          id="revision_notes_<?php echo $revision['id']; ?>" 
                                          name="revision_notes" rows="3"
                                          placeholder="Revizyon hakkında notlarınızı buraya yazın..."></textarea>
                                <div class="form-text">Yapılan değişiklikler ve açıklamalar</div>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" name="upload_revision" class="btn btn-success btn-lg">
                                        <i class="fas fa-upload me-2"></i>Revizyon Dosyasını Yükle
                                    </button>
                                    
                                    <div class="revision-actions">
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="showRejectModal('<?php echo $revision['id']; ?>')">
                                            <i class="fas fa-times me-1"></i>Revizyon Talebini Reddet
                                        </button>
                                    </div>
                                </div>
                                
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    <strong>Önemli:</strong> Dosya yüklendikten sonra revizyon talebi otomatik olarak "Tamamlandı" durumuna geçecek ve kullanıcı dosyayı indirebilecek.
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (false): // Removed loop check ?>
                    <hr class="my-4">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- İşlemdeki revizyon talebi yok -->
    <?php
    // Bu dosya için herhangi bir revizyon talebi var mı kontrol et
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE upload_id = ?");
        $stmt->execute([$uploadId]);
        $totalRevisions = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE upload_id = ? AND status = 'pending'");
        $stmt->execute([$uploadId]);
        $pendingRevisions = $stmt->fetchColumn();
        
    } catch(PDOException $e) {
        $totalRevisions = 0;
        $pendingRevisions = 0;
    }
    ?>
    
    <?php if ($pendingRevisions > 0): ?>
        <div class="card admin-card mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Bekleyen Revizyon Talepleri (<?php echo $pendingRevisions; ?> adet)
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Bu dosya için bekleyen revizyon talepleri var.</strong><br>
                    Revizyon taleplerini işleme almak için <a href="revisions.php" class="alert-link">Revizyon Yönetimi</a> sayfasını ziyaret edin.
                </div>
                
                <div class="d-flex gap-2">
                    <a href="revisions.php?search=<?php echo urlencode($upload['original_name']); ?>" 
                       class="btn btn-warning">
                        <i class="fas fa-list me-1"></i>Revizyon Taleplerini Görüntüle
                    </a>
                </div>
            </div>
        </div>
    <?php elseif ($totalRevisions > 0): ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Revizyon Geçmişi
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Bu dosya için toplam <?php echo $totalRevisions; ?> revizyon talebi bulunuyor.
                </p>
                
                <a href="revisions.php?search=<?php echo urlencode($upload['original_name']); ?>" 
                   class="btn btn-outline-primary">
                    <i class="fas fa-history me-1"></i>Revizyon Geçmişini Görüntüle
                </a>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<!-- ==================== REVİZYON DOSYASI YÜKLEMESİ SON ==================== -->

    
    <div class="card-body">
        <div class="row">
            <!-- Dosya Bilgileri -->
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Dosya Adı</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['original_name']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Dosya Boyutu</label>
                        <div class="form-control-plaintext">
                            <?php echo formatFileSize($upload['file_size'] ?? 0); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Yükleme Tarihi</label>
                        <div class="form-control-plaintext">
                            <?php echo date('d.m.Y H:i', strtotime($upload['upload_date'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($fileType === 'response'): ?>
                        <div class="col-sm-6">
                            <label class="form-label">Oluşturan Admin</label>
                            <div class="form-control-plaintext">
                                <?php echo safeHtml($upload['admin_first_name'] . ' ' . $upload['admin_last_name'] . ' (@' . $upload['admin_username'] . ')'); ?>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="form-label">Orijinal Dosya</label>
                            <div class="form-control-plaintext">
                                <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="text-primary">
                                    <?php echo safeHtml($upload['original_upload_name']); ?>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-sm-6">
                            <label class="form-label">Durum</label>
                            <div class="form-control-plaintext">
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'rejected' => 'danger'
                                ];
                                $statusText = [
                                    'pending' => 'Bekliyor',
                                    'processing' => 'İşleniyor',
                                    'completed' => 'Tamamlandı',
                                    'rejected' => 'Reddedildi'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Araç Bilgileri -->
                    <div class="col-12">
                        <hr>
                        <h6 class="mb-3">Araç Bilgileri</h6>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Marka</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['brand_name']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Model</label>
                        <div class="form-control-plaintext">
                            <?php 
                            $modelDisplay = safeHtml($upload['model_name']);
                            if (!empty($upload['year'])) {
                                $modelDisplay = safeHtml($upload['model_name'] . ' (' . $upload['year'] . ')');
                            }
                            echo $modelDisplay;
                            ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($upload['plate'])): ?>
                        <div class="col-sm-6">
                            <label class="form-label">Plaka</label>
                            <div class="form-control-plaintext">
                                <span class="text-primary fw-bold">
                                    <i class="fas fa-id-card me-1"></i><?php echo strtoupper(htmlspecialchars($upload['plate'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-sm-6">
                        <label class="form-label">ECU Tipi</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['ecu_type']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Motor Kodu</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['engine_code']); ?>
                        </div>
                    </div>
                    
                    <div class="col-sm-6">
                        <label class="form-label">Güç (HP)</label>
                        <div class="form-control-plaintext">
                            <?php echo safeHtml($upload['hp_power']); ?>
                        </div>
                    </div>
                    
                    <!-- Kullanıcı Notları -->
                    <?php if (!empty($upload['upload_notes'])): ?>
                        <div class="col-12">
                            <label class="form-label">Kullanıcı Notları</label>
                            <div class="form-control-plaintext">
                                <div class="alert-light border" style="position: relative;
    padding: 1rem 1rem;
    margin-bottom: 1rem;">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    <strong>Kullanıcının yükleme sırasında yaztığı notlar:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($upload['upload_notes'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Admin Notları -->
                    <?php if (!empty($upload['admin_notes'])): ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Kullanıcı Bilgileri -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Kullanıcı Bilgileri</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle me-3">
                        <?php 
                            $firstName = $upload['first_name'] ?? '';
                            $lastName = $upload['last_name'] ?? '';
                        echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); 
                        ?>
                        </div>
                            <div>
                                <h6 class="mb-0"><?php echo safeHtml(($upload['first_name'] ?? '') . ' ' . ($upload['last_name'] ?? '')); ?></h6>
                                <small class="text-muted">@<?php echo safeHtml($upload['username'] ?? 'Bilinmiyor'); ?></small>
                        </div>
                        </div>
                        
                            <div class="mb-2">
                                <small class="text-muted">E-posta:</small><br>
                                <a href="mailto:<?php echo $upload['email'] ?? ''; ?>"><?php echo safeHtml($upload['email'] ?? 'Belirtilmemiş'); ?></a>
                            </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">Dosya Durumu:</small><br>
                            <?php if ($fileType === 'response'): ?>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-reply me-1"></i>Yanıt Dosyası
                                </span>
                            <?php else: ?>
                                <span class="badge bg-<?php echo $statusClass[$upload['status']] ?? 'secondary'; ?> fs-6">
                                    <?php echo $statusText[$upload['status']] ?? 'Bilinmiyor'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <a href="users.php?user_id=<?php echo $upload['user_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user me-1"></i>Kullanıcı Profili
                            </a>
                            <a href="uploads.php?user_id=<?php echo $upload['user_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-files me-1"></i>Diğer Dosyalar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yanıt Dosyaları Listesi (sadece normal dosyalar için) -->
<?php if ($fileType !== 'response' && !empty($responseFiles)): ?>
    <div class="card admin-card mb-4" id="response-files">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-reply me-2"></i>Yanıt Dosyaları (<?php echo count($responseFiles); ?>)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dosya Adı</th>
                            <th>Boyut</th>
                            <th>Yükleme Tarihi</th>
                            <th>Admin</th>
                            <th>Kredi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responseFiles as $responseFile): ?>
                            <tr>
                                <td>
                                    <strong><?php echo safeHtml($responseFile['original_name']); ?></strong>
                                </td>
                                <td><?php echo formatFileSize($responseFile['file_size']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($responseFile['upload_date'])); ?></td>
                                <td>
                                    <?php if ($responseFile['admin_username']): ?>
                                        <?php echo safeHtml($responseFile['first_name'] . ' ' . $responseFile['last_name']); ?>
                                        <small class="text-muted d-block">@<?php echo $responseFile['admin_username']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Bilinmiyor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $responseFile['credits_charged']; ?> kredi
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="file-detail.php?id=<?php echo $uploadId; ?>&type=response&response_id=<?php echo $responseFile['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm" title="Detayları Görüntüle">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </a>
                                        <a href="download-file.php?id=<?php echo $responseFile['id']; ?>&type=response" 
                                           class="btn btn-success btn-sm" title="Dosyayı İndir">
                                            <i class="fas fa-download me-1"></i>İndir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- İletişim Geçmişi (Tüm Kullanıcı Admin Etkileşimleri) -->  
<?php if (!empty($communicationHistory)): ?>
<div class="card admin-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-comments me-2 text-primary"></i>İletişim Geçmişi 
                <span class="badge bg-secondary ms-2"><?php echo count($communicationHistory); ?> mesaj</span>
            </h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">Kronolojik Sırada</span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Bu kullanıcıyla olan tüm iletişim geçmişi:</strong> 
            Kullanıcının dosya yükleme notları, revize talepleri ve admin cevapları kronolojik sırada listelenmektedir.
        </div>
        
        <div class="communication-timeline">
            <?php foreach ($communicationHistory as $index => $comm): ?>
                <div class="timeline-item communication-item <?php echo $comm['status']; ?>">
                    <div class="timeline-marker">
                        <?php 
                        $typeConfig = [
                            'user_upload' => ['icon' => 'fas fa-upload text-primary', 'color' => 'primary'],
                            'admin_response' => ['icon' => 'fas fa-reply text-success', 'color' => 'success'],
                            'user_revision_request' => ['icon' => 'fas fa-edit text-warning', 'color' => 'warning'],
                            'admin_revision_response' => ['icon' => 'fas fa-user-shield text-info', 'color' => 'info']
                        ];
                        $config = $typeConfig[$comm['type']] ?? ['icon' => 'fas fa-comment text-secondary', 'color' => 'secondary'];
                        ?>
                        <i class="<?php echo $config['icon']; ?>"></i>
                    </div>
                    <div class="timeline-content <?php echo ($comm['type'] === 'user_revision_request') ? 'revision-request-highlight' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <?php if ($comm['type'] === 'user_upload'): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-user me-1"></i>Kullanıcının Yükleme Notu
                                        </span>
                                    <?php elseif ($comm['type'] === 'admin_response'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-user-shield me-1"></i>Admin'in Yanıt Dosyası Notu
                                        </span>
                                    <?php elseif ($comm['type'] === 'user_revision_request'): ?>
                                        <span class="badge bg-warning" style="background-color: #ffc107 !important; color: #212529 !important; font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px;">
                                            <i class="fas fa-edit me-1"></i>Kullanıcının Revize Talebi
                                        </span>
                                        <?php if (isset($comm['revision_status'])): ?>
                                            <span class="badge bg-<?php echo 
                                                $comm['revision_status'] === 'pending' ? 'warning' : 
                                                ($comm['revision_status'] === 'in_progress' ? 'info' : 
                                                ($comm['revision_status'] === 'completed' ? 'success' : 'danger')); ?> ms-1">
                                                <?php echo 
                                                    $comm['revision_status'] === 'pending' ? 'Bekliyor' : 
                                                    ($comm['revision_status'] === 'in_progress' ? 'İşleniyor' : 
                                                    ($comm['revision_status'] === 'completed' ? 'Tamamlandı' : 'Reddedildi')); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($comm['type'] === 'admin_revision_response'): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-reply me-1"></i>Admin'in Revize Cevabı
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($comm['file_name'])): ?>
                                        <span class="badge bg-secondary ms-2">
                                            <i class="fas fa-file me-1"></i><?php echo htmlspecialchars(substr($comm['file_name'], 0, 20)) . (strlen($comm['file_name']) > 20 ? '...' : ''); ?>
                                        </span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo formatDate($comm['date']); ?>
                                    
                                    <?php if (isset($comm['admin_username']) && !empty($comm['admin_username'])): ?>
                                        <span class="ms-2">
                                            <i class="fas fa-user-shield me-1"></i>
                                            Admin: <?php echo htmlspecialchars($comm['admin_username']); ?>
                                        </span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Hangi dosya için revize talebi olduğunu belirt (sadece revize talepleri için) -->
                        <?php if ($comm['type'] === 'user_revision_request' || $comm['type'] === 'admin_revision_response'): ?>
                            <?php if (!empty($comm['response_file_name'])): ?>
                                <div class="mb-3">
                                    <div class="file-reference">
                                        <i class="fas fa-arrow-right text-primary me-2"></i>
                                        <strong>Revize edilen dosya:</strong> 
                                        <span class="text-primary"><?php echo htmlspecialchars($comm['response_file_name']); ?></span>
                                        <small class="text-muted ms-2">(Yanıt Dosyası)</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <div class="file-reference">
                                        <i class="fas fa-arrow-right text-success me-2"></i>
                                        <strong>Revize edilen dosya:</strong> 
                                        <span class="text-success">Ana Dosya</span>
                                        <small class="text-muted ms-2">(Orijinal Yüklenen Dosya)</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Kullanıcı Notları -->
                        <?php if (!empty($comm['user_notes'])): ?>

                            <div class="revision-note user-note mb-3">
                                <div class="note-header">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    <strong>
                                        <?php if ($comm['type'] === 'user_upload'): ?>
                                            Kullanıcının yükleme sırasında yazdığı notlar:
                                        <?php else: ?>
                                            Kullanıcının revize talebi:
                                        <?php endif; ?>
                                    </strong>
                                </div>
                                <div class="note-content">
                                    <?php echo nl2br(htmlspecialchars($comm['user_notes'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- DEBUG: No user notes for type: <?php echo $comm['type']; ?> -->
                        <?php endif; ?>
                        
                        <!-- Admin Cevabı -->
                        <?php if (!empty($comm['admin_notes'])): ?>
                            <div class="revision-note admin-note mb-2">
                                <div class="note-header">
                                    <i class="fas fa-user-shield me-2 text-success"></i>
                                    <strong>
                                        <?php if ($comm['type'] === 'admin_response'): ?>
                                            Admin'in yanıt dosyası notları:
                                        <?php else: ?>
                                            Admin'in cevabı:
                                        <?php endif; ?>
                                    </strong>
                                    <?php if (!empty($comm['admin_username'])): ?>
                                        <small class="text-muted">
                                            - <?php echo htmlspecialchars($comm['admin_username']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="note-content">
                                    <?php echo nl2br(htmlspecialchars($comm['admin_notes'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($comm['type'] === 'user_revision_request' && $comm['status'] === 'pending'): ?>
                                <div class="revision-note admin-note mb-2 pending-response">
                                    <div class="note-header">
                                        <i class="fas fa-hourglass-half me-2 text-muted"></i>
                                        <strong>Admin Cevabı:</strong>
                                    </div>
                                    <div class="note-content">
                                        <em class="text-muted">
                                            Kullanıcının talebi inceleniyor, admin cevabı bekleniyor...
                                        </em>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Ek Bilgiler -->
                        <div class="communication-meta">
                            <?php if (isset($comm['credits_charged']) && $comm['credits_charged'] > 0): ?>
                                <span class="meta-item text-warning">
                                    <i class="fas fa-coins me-1"></i>
                                    <?php echo $comm['credits_charged']; ?> kredi düşürüldü
                                </span>
                            <?php endif; ?>
                            
                            <?php if (isset($comm['response_id'])): ?>
                                <a href="file-detail.php?id=<?php echo $uploadId; ?>&type=response&response_id=<?php echo $comm['response_id']; ?>" 
                                   class="meta-item text-primary" style="text-decoration: none;">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    Yanıt Dosyasını Görüntüle
                                </a>
                            <?php endif; ?>
                            
                            <?php if (isset($comm['revision_id'])): ?>
                                <span class="meta-item text-info">
                                    <i class="fas fa-hashtag me-1"></i>
                                    Revize #<?php echo substr($comm['revision_id'], 0, 8); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (isset($comm['revision_id']) && $comm['type'] === 'user_revision_request'): ?>
                                <?php if ($comm['revision_status'] === 'pending'): ?>
                                    <div class="meta-item">
                                        <div class="d-flex gap-1">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="revision_id" value="<?php echo $comm['revision_id']; ?>">
                                                <button type="submit" name="approve_revision_direct" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-check me-1"></i>İşleme Al
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="showRejectModal('<?php echo $comm['revision_id']; ?>')">
                                                <i class="fas fa-times me-1"></i>Reddet
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($index < count($communicationHistory) - 1): ?>
                    <div class="timeline-divider"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- İletişim Özeti -->
        <div class="communication-summary mt-4 p-3 bg-light rounded">
            <h6 class="mb-2">
                <i class="fas fa-chart-line me-2 text-info"></i>İletişim Özeti
            </h6>
            <div class="row text-center">
                <?php 
                $typeCounts = array_count_values(array_column($communicationHistory, 'type'));
                ?>
                <div class="col-3">
                    <span class="badge bg-primary fs-6"><?php echo $typeCounts['user_upload'] ?? 0; ?></span>
                    <br><small>Kullanıcı Notu</small>
                </div>
                <div class="col-3">
                    <span class="badge bg-success fs-6"><?php echo $typeCounts['admin_response'] ?? 0; ?></span>
                    <br><small>Admin Yanıt</small>
                </div>
                <div class="col-3">
                    <span class="badge bg-warning fs-6"><?php echo $typeCounts['user_revision_request'] ?? 0; ?></span>
                    <br><small>Revize Talebi</small>
                </div>
                <div class="col-3">
                    <span class="badge bg-info fs-6"><?php echo $typeCounts['admin_revision_response'] ?? 0; ?></span>
                    <br><small>Admin Cevap</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Kullanıcının Tüm Revize Geçmişi -->
<?php if (!empty($userAllRevisions) && count($userAllRevisions) > count($revisionRequests)): ?>
<div class="card admin-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-history me-2 text-info"></i>Kullanıcının Tüm Revize Geçmişi 
                <span class="badge bg-secondary ms-2"><?php echo count($userAllRevisions); ?> toplam talep</span>
            </h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">Diğer Dosyalar Dahil</span>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#userHistoryCollapse" aria-expanded="false">
                    <i class="fas fa-eye me-1"></i>Geçmişi Göster/Gizle
                </button>
            </div>
        </div>
    </div>
    <div class="collapse" id="userHistoryCollapse">
        <div class="card-body">
            <div class="alert-info" style="position: relative; padding: 1rem 1rem; margin-bottom: 1rem;">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Bu kullanıcının tüm dosyalarındaki revize talep geçmişi:</strong> 
                Bu listeyi kullanarak kullanıcıyla daha önce nelerin konuşulduğunu görebilir, 
                tutarlı hizmet verebilirsiniz.
            </div>
            
            <div class="timeline">
                <?php foreach ($userAllRevisions as $index => $revision): ?>
                    <div class="timeline-item <?php echo $revision['status']; ?> <?php echo $revision['upload_id'] === $uploadId ? 'current-file' : 'other-file'; ?>">
                        <div class="timeline-marker">
                            <?php 
                            $iconClass = [
                                'pending' => 'fas fa-clock text-warning',
                                'in_progress' => 'fas fa-cogs text-info', 
                                'completed' => 'fas fa-check-circle text-success',
                                'rejected' => 'fas fa-times-circle text-danger'
                            ];
                            $statusText = [
                                'pending' => 'Bekliyor',
                                'in_progress' => 'İşleniyor', 
                                'completed' => 'Tamamlandı',
                                'rejected' => 'Reddedildi'
                            ];
                            ?>
                            <i class="<?php echo $iconClass[$revision['status']] ?? 'fas fa-question-circle text-secondary'; ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <span class="badge bg-<?php echo 
                                            $revision['status'] === 'pending' ? 'warning' : 
                                            ($revision['status'] === 'in_progress' ? 'info' : 
                                            ($revision['status'] === 'completed' ? 'success' : 'danger')); ?>">
                                            <?php echo $statusText[$revision['status']] ?? 'Bilinmiyor'; ?>
                                        </span>
                                        
                                        <?php if ($revision['upload_id'] === $uploadId): ?>
                                            <span class="badge bg-primary ms-2">
                                                <i class="fas fa-star me-1"></i>Bu Dosya
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="fas fa-external-link-alt me-1"></i>Diğer Dosya
                                            </span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($revision['requested_at']); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Hangi dosya için revize talebi -->
                            <div class="mb-3">
                                <div class="file-reference-extended">
                                    <?php if (!empty($revision['response_file_name'])): ?>
                                        <i class="fas fa-reply text-success me-2"></i>
                                        <strong>Yanıt Dosyası:</strong> 
                                        <span class="text-success"><?php echo safeHtml($revision['response_file_name']); ?></span>
                                        <br>
                                        <i class="fas fa-level-up-alt text-muted me-2 ms-3"></i>
                                        <small class="text-muted">Ana Dosya: <?php echo safeHtml($revision['upload_file_name']); ?></small>
                                    <?php else: ?>
                                        <i class="fas fa-file text-primary me-2"></i>
                                        <strong>Ana Dosya:</strong> 
                                        <span class="text-primary"><?php echo safeHtml($revision['upload_file_name']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($revision['upload_id'] !== $uploadId): ?>
                                        <a href="file-detail.php?id=<?php echo $revision['upload_id']; ?>" 
                                           class="btn btn-outline-primary btn-xs ms-2" title="Bu dosyayı görüntüle">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Kullanıcının Revize Talep Notu -->
                            <div class="revision-note user-note mb-3">
                                <div class="note-header">
                                    <i class="fas fa-comment-dots me-2 text-primary"></i>
                                    <strong>Kullanıcının Talebi:</strong>
                                </div>
                                <div class="note-content">
                                    <?php echo nl2br(htmlspecialchars($revision['request_notes'])); ?>
                                </div>
                            </div>
                            
                            <!-- Admin Cevabı -->
                            <?php if (!empty($revision['admin_notes']) && filterAdminNotes($revision['admin_notes'])): ?>
                                <div class="revision-note admin-note mb-2">
                                    <div class="note-header">
                                        <i class="fas fa-user-shield me-2 text-success"></i>
                                        <strong>Admin Cevabı:</strong>
                                        <?php if (!empty($revision['admin_username'])): ?>
                                            <small class="text-muted">
                                                - <?php echo safeHtml(($revision['admin_first_name'] ?? '') . ' ' . ($revision['admin_last_name'] ?? '') . ' (@' . $revision['admin_username'] . ')'); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="note-content">
                                        <?php echo nl2br(htmlspecialchars($revision['admin_notes'])); ?>
                                    </div>
                                </div>
                            <?php elseif (!empty($revision['admin_notes'])): ?>
                                <div class="revision-note pending-response mb-2">
                                    <div class="note-header">
                                        <i class="fas fa-cogs me-2 text-info"></i>
                                        <strong>Durum:</strong>
                                    </div>
                                    <div class="note-content">
                                        <em class="text-muted"><?php echo htmlspecialchars($revision['admin_notes']); ?></em>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Kredi ve Tarih Bilgileri -->
                            <div class="revision-meta">
                                <?php if ($revision['credits_charged'] > 0): ?>
                                    <span class="meta-item text-warning">
                                        <i class="fas fa-coins me-1"></i>
                                        <?php echo $revision['credits_charged']; ?> kredi düşürüldü
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($revision['completed_at']): ?>
                                    <span class="meta-item text-success">
                                        <i class="fas fa-check me-1"></i>
                                        Tamamlandı: <?php echo formatDate($revision['completed_at']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($index < count($userAllRevisions) - 1): ?>
                        <div class="timeline-divider"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Yanıt Dosyası Yükleme (sadece normal dosyalar için) -->
<?php if ($fileType !== 'response' && ($upload['status'] === 'pending' || $upload['status'] === 'processing')): ?>
    <div class="card admin-card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-reply me-2"></i>Yanıt Dosyası Yükle
                </h6>
                <?php if ($upload['status'] === 'processing'): ?>
                    <span class="badge bg-info">
                        <i class="fas fa-cogs me-1"></i>Dosya İşleme Alındı
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if ($upload['status'] === 'processing'): ?>
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <i class="fas fa-info-circle me-3 mt-1"></i>
                        <div>
                            <strong>Dosya işleme alındı!</strong><br>
                            <small class="text-muted">
                                Bu dosya indirildi ve düzenleme aşamasında. Düzenleme tamamlandıktan sonra 
                                buradan yanıt dosyasını yükleyebilirsiniz.
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="response_file" class="form-label">Yanıt Dosyası</label>
                        <input type="file" class="form-control" id="response_file" name="response_file" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="credits_charged" class="form-label">Düşürülecek Kredi</label>
                        <input type="number" class="form-control" id="credits_charged" name="credits_charged" 
                               value="0" min="0" step="0.01">
                    </div>
                    
                    <div class="col-12">
                        <label for="response_notes" class="form-label">Yanıt Notları</label>
                        <textarea class="form-control" id="response_notes" name="response_notes" rows="3"
                                  placeholder="Yanıt ile ilgili notlarınızı buraya yazın..."></textarea>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="upload_response" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i>Yanıt Dosyasını Yükle
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Revize Yanıt Dosyası Yükleme (response dosyası için) -->
<?php if ($fileType === 'response'): ?>
    <?php if ($approvedRevision): ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-redo me-2 text-warning"></i>Revize Edilmiş Yanıt Dosyası Yükle
                    </h6>
                    <span class="badge bg-info">Revize Talebi İşleme Alındı</span>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <i class="fas fa-info-circle me-3 mt-1"></i>
                        <div>
                            <strong>Revize Talebi İşleme Alındı:</strong><br>
                            <span class="text-muted"><?php echo nl2br(htmlspecialchars($approvedRevision['request_notes'])); ?></span>
                            <br><small class="text-muted">Tarih: <?php echo formatDate($approvedRevision['requested_at']); ?></small>
                        </div>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="revised_response_file" class="form-label">Revize Edilmiş Yanıt Dosyası <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="revised_response_file" name="revised_response_file" required>
                            <div class="form-text">Revize edilen yanıt dosyasını seçin</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="revised_credits_charged" class="form-label">Düşürülecek Kredi</label>
                            <input type="number" class="form-control" id="revised_credits_charged" name="revised_credits_charged" 
                                   value="0" min="0" step="0.01">
                            <div class="form-text">Revize için kullanıcıdan düşürülecek kredi miktarı</div>
                        </div>
                        
                        <div class="col-12">
                            <label for="revised_response_notes" class="form-label">Revize Notları</label>
                            <textarea class="form-control" id="revised_response_notes" name="revised_response_notes" rows="3"
                                      placeholder="Revize edilen dosya ile ilgili notlarınızı buraya yazın..."></textarea>
                            <div class="form-text">Revize detayları ve değişiklikler hakkında bilgi verin</div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="upload_revised_response" class="btn btn-warning btn-lg">
                                <i class="fas fa-upload me-2"></i>Revize Edilmiş Dosyayı Yükle
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Yeni dosya yüklendikten sonra kullanıcı bunu indirebilecek
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card admin-card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2 text-info"></i>Revize Durumu
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <div class="d-flex">
                        <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                        <div>
                            <strong>Bu yanıt dosyası için henüz onaylanmış revize talebi bulunmuyor.</strong><br>
                            <small class="text-muted">
                                Kullanıcı bu yanıt dosyası için revize talebi gönderdiyse ve siz onu onaylandıysanız, 
                                burada revize dosyası yükleme formu görünecektir.
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="revisions.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i>Revize Taleplerini Görüntüle
                    </a>
                    <a href="file-detail.php?id=<?php echo $uploadId; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-alt me-1"></i>Orijinal Dosyaya Dön
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Revize Reddetme Modal -->
<div class="modal fade" id="rejectRevisionModal" tabindex="-1" aria-labelledby="rejectRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectRevisionModalLabel">
                    <i class="fas fa-times-circle text-danger me-2"></i>Revize Talebini Reddet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="rejectRevisionForm">
                <div class="modal-body">
                    <input type="hidden" name="revision_id" id="rejectRevisionId">
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Reddetme Sebebi <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" required
                                  placeholder="Revize talebinin neden reddedildiğini açıklayın..."></textarea>
                        <div class="form-text">Bu mesaj kullanıcıya gönderilecektir.</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dikkat:</strong> Revize talebi reddedildikten sonra geri alınamaz.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" name="reject_revision_direct" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i>Revize Talebini Reddet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}

.communication-timeline {
    position: relative;
    padding: 0;
}

.communication-timeline .timeline-item {
    position: relative;
    display: flex;
    margin-bottom: 2rem;
}

.communication-timeline .timeline-marker {
    flex: 0 0 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 3px solid #e9ecef;
    border-radius: 50%;
    margin-right: 1rem;
    z-index: 2;
}

.communication-timeline .timeline-item.pending .timeline-marker {
    border-color: #ffc107;
    background: #fff3cd;
}

.communication-timeline .timeline-item.in_progress .timeline-marker {
    border-color: #0dcaf0;
    background: #cff4fc;
}

.communication-timeline .timeline-item.completed .timeline-marker {
    border-color: #198754;
    background: #d1e7dd;
}

.communication-timeline .timeline-item.rejected .timeline-marker {
    border-color: #dc3545;
    background: #f8d7da;
}

.communication-timeline .timeline-content {
    flex: 1;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1.25rem;
    position: relative;
}

.communication-timeline .timeline-content::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 20px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #e9ecef transparent transparent;
}

.communication-timeline .timeline-content::after {
    content: '';
    position: absolute;
    left: -7px;
    top: 20px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #f8f9fa transparent transparent;
}

.communication-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    margin-top: 0.75rem;
}

.communication-meta .meta-item {
    display: flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: rgba(0,0,0,0.05);
    border-radius: 0.25rem;
    text-decoration: none;
}

.communication-meta .meta-item:hover {
    background: rgba(0,0,0,0.1);
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding: 0;
}

.timeline-item {
    position: relative;
    display: flex;
    margin-bottom: 2rem;
}

.timeline-marker {
    flex: 0 0 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 3px solid #e9ecef;
    border-radius: 50%;
    margin-right: 1rem;
    z-index: 2;
}

.timeline-item.pending .timeline-marker {
    border-color: #ffc107;
    background: #fff3cd;
}

.timeline-item.in_progress .timeline-marker {
    border-color: #0dcaf0;
    background: #cff4fc;
}

.timeline-item.completed .timeline-marker {
    border-color: #198754;
    background: #d1e7dd;
}

.timeline-item.rejected .timeline-marker {
    border-color: #dc3545;
    background: #f8d7da;
}

.timeline-content {
    flex: 1;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1.25rem;
    position: relative;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 20px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #e9ecef transparent transparent;
}

.timeline-content::after {
    content: '';
    position: absolute;
    left: -7px;
    top: 20px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #f8f9fa transparent transparent;
}

.timeline-divider {
    position: absolute;
    left: 19px;
    width: 2px;
    height: 2rem;
    background: #e9ecef;
    margin-top: -2rem;
    margin-bottom: 0;
}

.revision-note {
    border-radius: 0.375rem;
    overflow: hidden;
}

.revision-note.user-note {
    background: #e7f3ff;
    border: 1px solid #b8daff;
}

/* Kullanıcının revize talepleri için özel stil */
.communication-timeline .timeline-item .timeline-content:has(.badge:contains("Kullanıcının Revize Talebi")),
.communication-timeline .timeline-item:has(.badge[class*="bg-warning"]:contains("Revize Talebi")) .timeline-content {
    background: #fff8e1 !important;
    border: 2px solid #ffc107 !important;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

/* Revize talebi vurgusu */
.revision-request-highlight {
    background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%) !important;
    border-left: 4px solid #ffc107 !important;
    position: relative;
}

.revision-request-highlight::before {
    content: '⚠️ REVIZE TALEBİ';
    position: absolute;
    top: -10px;
    right: 10px;
    background: #ffc107;
    color: #212529;
    font-size: 0.7rem;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 4px;
    z-index: 10;
}

.revision-note.admin-note {
    background: #d4edda;
    border: 1px solid #c3e6cb;
}

.revision-note.pending-response {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
}

.revision-note .note-header {
    background: rgba(0,0,0,0.05);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.revision-note .note-content {
    padding: 0.75rem;
    white-space: normal;
    word-wrap: break-word;
}

.user-note .note-header {
    background: rgba(13, 110, 253, 0.1);
}

.admin-note .note-header {
    background: rgba(25, 135, 84, 0.1);
}

.pending-response .note-header {
    background: rgba(255, 193, 7, 0.1);
}

.communication-summary {
    border: 1px solid #dee2e6;
}

.communication-summary .badge {
    min-width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .timeline-item {
        flex-direction: column;
    }
    
    .timeline-marker {
        margin-right: 0;
        margin-bottom: 0.5rem;
        align-self: flex-start;
    }
    
    .timeline-content::before,
    .timeline-content::after {
        display: none;
    }
    
    .timeline-divider {
        display: none;
    }
}

/* User History Styles */
.timeline-item.current-file {
    border-left: 3px solid #007bff;
    padding-left: 1rem;
    margin-left: -1rem;
}

.timeline-item.other-file {
    opacity: 0.8;
}

.timeline-item.other-file:hover {
    opacity: 1;
}

.file-reference-extended {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    font-size: 0.9rem;
    line-height: 1.6;
}

.revision-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    margin-top: 0.75rem;
}

.revision-meta .meta-item {
    display: flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: rgba(0,0,0,0.05);
    border-radius: 0.25rem;
}

.user-stats {
    border: 1px solid #dee2e6;
}

.stat-item {
    padding: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    display: block;
}

.btn-xs {
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

.file-reference {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    font-size: 0.9rem;
}
</style>

<script>
function showRejectModal(revisionId) {
    document.getElementById('rejectRevisionId').value = revisionId;
    document.getElementById('admin_notes').value = '';
    new bootstrap.Modal(document.getElementById('rejectRevisionModal')).show();
}
</script>

<?php
// Footer include
include '../includes/admin_footer.php';
?>
