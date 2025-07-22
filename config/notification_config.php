<?php
/**
 * Mr ECU - Notification Configuration
 * Bildirim Sistemi Konfigürasyon Dosyası
 */

// Güvenlik kontrolü - Sadece config.php üzerinden dahil edilmelidir
if (!defined('SITE_NAME')) {
    exit('Direct access denied');
}

// Bildirim Sistemi Ayarları
define('NOTIFICATION_ENABLED', true);
define('NOTIFICATION_PAGE_LIMIT', 25); // Sayfa başına bildirim sayısı
define('NOTIFICATION_AUTO_CLEANUP_DAYS', 90); // 90 gün sonra eski bildirimleri temizle
define('NOTIFICATION_MAX_PER_USER', 1000); // Kullanıcı başına maksimum bildirim sayısı

// Bildirim Türleri
define('NOTIFICATION_TYPES', [
    'file_upload' => 'Dosya Yükleme',
    'file_status_update' => 'Dosya Durum Güncelleme',
    'revision_request' => 'Revizyon Talebi',
    'revision_approved' => 'Revizyon Onaylandı',
    'revision_rejected' => 'Revizyon Reddedildi',
    'user_registration' => 'Kullanıcı Kaydı',
    'credit_update' => 'Kredi Güncelleme',
    'system_warning' => 'Sistem Uyarısı',
    'system_maintenance' => 'Sistem Bakımı',
    'admin_message' => 'Admin Mesajı'
]);

// Bildirim Durumları
define('NOTIFICATION_STATUS', [
    'unread' => 'Okunmamış',
    'read' => 'Okunmuş',
    'archived' => 'Arşivlenmiş'
]);

// Bildirim Seviyeleri (CSS class'ları için)
define('NOTIFICATION_LEVELS', [
    'info' => 'primary',
    'success' => 'success', 
    'warning' => 'warning',
    'error' => 'danger',
    'critical' => 'dark'
]);

// E-posta Bildirimleri
define('EMAIL_NOTIFICATIONS_ENABLED', true);
define('EMAIL_NOTIFICATION_DELAY', 300); // 5 dakika gecikme (spam önlemi)

// Real-time Bildirimler (WebSocket/SSE için)
define('REALTIME_NOTIFICATIONS_ENABLED', false);
define('NOTIFICATION_WEBSOCKET_PORT', 8080);

// Bildirim Template'leri
define('NOTIFICATION_TEMPLATES', [
    'file_upload' => [
        'title' => 'Yeni Dosya Yüklendi',
        'message' => '{filename} dosyası başarıyla yüklendi.',
        'icon' => 'fas fa-upload',
        'level' => 'success'
    ],
    'file_status_update' => [
        'title' => 'Dosya Durumu Güncellendi',
        'message' => '{filename} dosyasının durumu {status} olarak güncellendi.',
        'icon' => 'fas fa-file-alt',
        'level' => 'info'
    ],
    'revision_request' => [
        'title' => 'Yeni Revizyon Talebi',
        'message' => '{filename} için revizyon talebi oluşturuldu.',
        'icon' => 'fas fa-edit',
        'level' => 'warning'
    ],
    'revision_approved' => [
        'title' => 'Revizyon Onaylandı',
        'message' => '{filename} revizyonu onaylandı.',
        'icon' => 'fas fa-check-circle',
        'level' => 'success'
    ],
    'revision_rejected' => [
        'title' => 'Revizyon Reddedildi',
        'message' => '{filename} revizyonu reddedildi. Sebep: {reason}',
        'icon' => 'fas fa-times-circle',
        'level' => 'error'
    ],
    'user_registration' => [
        'title' => 'Yeni Kullanıcı Kaydı',
        'message' => '{username} kullanıcısı sisteme kaydoldu.',
        'icon' => 'fas fa-user-plus',
        'level' => 'info'
    ],
    'credit_update' => [
        'title' => 'Kredi Güncelleme',
        'message' => 'Kredi bakiyeniz güncellendi. Yeni bakiye: {amount}',
        'icon' => 'fas fa-coins',
        'level' => 'info'
    ],
    'system_warning' => [
        'title' => 'Sistem Uyarısı',
        'message' => '{message}',
        'icon' => 'fas fa-exclamation-triangle',
        'level' => 'warning'
    ],
    'system_maintenance' => [
        'title' => 'Sistem Bakımı',
        'message' => 'Sistem bakımı planlandı: {date}',
        'icon' => 'fas fa-tools',
        'level' => 'warning'
    ],
    'admin_message' => [
        'title' => 'Admin Mesajı',
        'message' => '{message}',
        'icon' => 'fas fa-envelope',
        'level' => 'info'
    ]
]);

// Bildirim Cache Ayarları
define('NOTIFICATION_CACHE_ENABLED', true);
define('NOTIFICATION_CACHE_TTL', 300); // 5 dakika cache süresi

// Debugging
define('NOTIFICATION_DEBUG', false);
define('NOTIFICATION_LOG_ENABLED', true);
define('NOTIFICATION_LOG_FILE', '../logs/notifications.log');

/**
 * Bildirim ayarlarını doğrula
 */
function validateNotificationConfig() {
    $errors = [];
    
    if (!defined('NOTIFICATION_PAGE_LIMIT') || NOTIFICATION_PAGE_LIMIT < 1) {
        $errors[] = 'NOTIFICATION_PAGE_LIMIT geçerli bir değer olmalı';
    }
    
    if (!defined('NOTIFICATION_AUTO_CLEANUP_DAYS') || NOTIFICATION_AUTO_CLEANUP_DAYS < 1) {
        $errors[] = 'NOTIFICATION_AUTO_CLEANUP_DAYS geçerli bir değer olmalı';
    }
    
    if (!is_array(NOTIFICATION_TYPES) || empty(NOTIFICATION_TYPES)) {
        $errors[] = 'NOTIFICATION_TYPES geçerli bir dizi olmalı';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Bildirim türü geçerli mi kontrol et
 */
function isValidNotificationType($type) {
    return array_key_exists($type, NOTIFICATION_TYPES);
}

/**
 * Bildirim template'i al
 */
function getNotificationTemplate($type) {
    return NOTIFICATION_TEMPLATES[$type] ?? [
        'title' => 'Bildirim',
        'message' => 'Yeni bir bildiriminiz var.',
        'icon' => 'fas fa-bell',
        'level' => 'info'
    ];
}

/**
 * Bildirim debug log
 */
function notificationLog($message, $level = 'info') {
    if (!NOTIFICATION_LOG_ENABLED) return;
    
    $logMessage = date('Y-m-d H:i:s') . " [{$level}] " . $message . PHP_EOL;
    
    $logDir = dirname(NOTIFICATION_LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(NOTIFICATION_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

// Konfigürasyon doğrulaması
if (NOTIFICATION_ENABLED) {
    $validation = validateNotificationConfig();
    if ($validation !== true) {
        notificationLog('Notification config validation failed: ' . implode(', ', $validation), 'error');
        if (NOTIFICATION_DEBUG) {
            throw new Exception('Notification configuration validation failed: ' . implode(', ', $validation));
        }
    }
}

// Konfigürasyon yüklendiğini belirt
define('NOTIFICATION_CONFIG_LOADED', true);

if (NOTIFICATION_DEBUG) {
    notificationLog('Notification config loaded successfully', 'debug');
}
?>
