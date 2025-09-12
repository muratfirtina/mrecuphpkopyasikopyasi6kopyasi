<?php
/**
 * Mr ECU - Get Notification Count (Admin Level)
 * Bildirim Sayısını Getir
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// NotificationManager'ı dahil et
if (!class_exists('NotificationManager')) {
    require_once '../../includes/NotificationManager.php';
}

// JSON response header
header('Content-Type: application/json');

// DEBUG: Geçici olarak session kontrolünü kapat
$debug_mode = true; // Test için true yap

// Giriş kontrolü
if (!$debug_mode && !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

try {
    // DEBUG: Test için hardcode user ID kullan
    if ($debug_mode) {
        $userId = 'e3685b9a-e4ce-4f5e-a6d8-8c02d50dbe43'; // maildeneme admin user
    } else {
        $userId = $_SESSION['user_id'];
    }
    
    $totalCount = 0;
    
    // NotificationManager kullan
    if (class_exists('NotificationManager')) {
        $notificationManager = new NotificationManager($pdo);
        $count = $notificationManager->getUnreadCount($userId);
        $totalCount += $count;
    }
    
    // Admin için ek kontroller
    if ($debug_mode || isAdmin()) {
        // Statik bildirimler sayısını ekle
        $dismissedStatic = $_SESSION['dismissed_static_notifications'] ?? [];
        
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
            // Bu hata önemli değil, krediler yoksa 0 say
        }
    }
    
    echo json_encode([
        'success' => true, 
        'count' => $totalCount,
        'debug' => [
            'db_notifications' => $count ?? 0,
            'pending_revisions' => $pendingRevisions ?? 0,
            'dismissed_static' => count($dismissedStatic ?? [])
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Get notification count error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage(),
        'debug' => [
            'user_id' => $userId ?? 'NULL',
            'pdo_exists' => isset($pdo) ? 'yes' : 'no'
        ]
    ]);
}
?>
