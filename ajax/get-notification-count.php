<?php
/**
 * Mr ECU - Generic Notification Count Handler
 * Kullanıcı rolüne göre bildirim sayısını getir
 */

// AJAX için clean output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// JSON response header
header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $totalCount = 0;
    
    // NotificationManager'ı dahil et
    if (!class_exists('NotificationManager')) {
        require_once '../includes/NotificationManager.php';
    }
    
    // NotificationManager kullan
    if (class_exists('NotificationManager')) {
        $notificationManager = new NotificationManager($pdo);
        $count = $notificationManager->getUnreadCount($userId);
        $totalCount += $count;
    }
    
    // Admin ise ek kontroller
    if (isAdmin()) {
        // Statik bildirimler sayısını ekle
        $dismissedStatic = $_SESSION['dismissed_static_notifications'] ?? [];
        
        // Bekleyen dosyalar kontrol et
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE status = 'pending'");
            $stmt->execute();
            $pendingFiles = $stmt->fetchColumn();
            if ($pendingFiles > 0 && !isset($dismissedStatic['pending_files'])) {
                $totalCount++;
            }
        } catch(PDOException $e) {
            error_log('Pending files check error: ' . $e->getMessage());
        }
        
        // Bekleyen revize talepleri kontrol et
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM revisions WHERE status = 'pending'");
            $stmt->execute();
            $pendingRevisions = $stmt->fetchColumn();
            if ($pendingRevisions > 0 && !isset($dismissedStatic['pending_revisions'])) {
                $totalCount++;
            }
        } catch(PDOException $e) {
            error_log('Pending revisions check error: ' . $e->getMessage());
        }
        
        // Düşük kredi uyarısı kontrol et
        try {
            $stmt = $pdo->prepare("SELECT SUM(current_credits) FROM user_credits WHERE user_id IN (SELECT id FROM users WHERE role = 'admin')");
            $stmt->execute();
            $totalCredits = $stmt->fetchColumn() ?? 0;
            if ($totalCredits < 10 && !isset($dismissedStatic['low_credits'])) {
                $totalCount++;
            }
        } catch(PDOException $e) {
            error_log('Low credits check error: ' . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true, 
        'count' => $totalCount
    ]);
    
} catch (Exception $e) {
    error_log('Generic notification count error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
