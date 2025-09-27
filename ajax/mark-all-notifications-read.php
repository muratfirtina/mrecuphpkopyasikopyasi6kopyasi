<?php
/**
 * Mr ECU - Generic Mark All Notifications Read Handler
 */

// Session path'ini ayarla - TÜM path'ler için geçerli olsun
ini_set('session.cookie_path', '/');
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);

// Session name'ini kontrol et - MRECU_SECURE_SESSION varsa onu kullan
if (isset($_COOKIE['MRECU_SECURE_SESSION'])) {
    session_name('MRECU_SECURE_SESSION');
}

// Session'ı başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug - Session bilgilerini logla
error_log('=== MARK ALL NOTIFICATIONS DEBUG ===');
error_log('Session Name: ' . session_name());
error_log('Session ID: ' . session_id());
error_log('Session Path: ' . ini_get('session.cookie_path'));
error_log('Cookies: ' . print_r($_COOKIE, true));
error_log('Session Data: ' . print_r($_SESSION, true));

// Require files
require_once '../config/config.php';
require_once '../config/database.php';

// Functions.php'yi include et (eğer yoksa)
if (!function_exists('isLoggedIn')) {
    require_once '../includes/functions.php';
}

// JSON response header
header('Content-Type: application/json');

// Debug için session bilgilerini logla
error_log('mark-all-notifications-read.php - Session ID: ' . session_id());
error_log('mark-all-notifications-read.php - User ID: ' . ($_SESSION['user_id'] ?? 'YOK'));
error_log('mark-all-notifications-read.php - isLoggedIn: ' . (isLoggedIn() ? 'true' : 'false'));

// Giriş kontrolü
if (!isLoggedIn()) {
    error_log('mark-all-notifications-read.php - Yetkisiz erişim hatası!');
    echo json_encode([
        'success' => false, 
        'message' => 'Yetkisiz erişim.',
        'debug' => [
            'session_name' => session_name(),
            'session_id' => session_id(),
            'has_user_id' => isset($_SESSION['user_id']),
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_path' => ini_get('session.cookie_path'),
            'all_session_keys' => array_keys($_SESSION),
            'cookies' => array_keys($_COOKIE)
        ]
    ]);
    exit;
}

try {
    // NotificationManager'ı dahil et
    if (!class_exists('NotificationManager')) {
        require_once '../includes/NotificationManager.php';
    }
    
    if (class_exists('NotificationManager')) {
        $userId = $_SESSION['user_id'];
        $notificationManager = new NotificationManager($pdo);
        
        error_log("mark-all-notifications-read.php - Tüm bildirimleri okundu işaretle başlatıldı: $userId");
        
        $result = $notificationManager->markAllAsRead($userId);
        
        if ($result) {
            // Admin ise statik bildirimleri de temizle
            if (isAdmin()) {
                $_SESSION['dismissed_static_notifications'] = [
                    'pending_files' => true,
                    'pending_revisions' => true,
                    'low_credits' => true
                ];
            }
            
            error_log("mark-all-notifications-read.php - Başarılı: $userId");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tüm bildirimler okundu olarak işaretlendi.'
            ]);
        } else {
            error_log("mark-all-notifications-read.php - Başarısız: $userId");
            echo json_encode([
                'success' => false, 
                'message' => 'Bildirimler işaretlenemedi.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Bildirim sistemi bulunamadı.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('mark-all-notifications-read.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}
?>
