<?php
/**
 * Test Bildirimleri Oluşturucu
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/notification_config.php';
require_once '../includes/NotificationManager.php';

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Admin yetkisi gerekli');
}

$notificationManager = new NotificationManager($pdo);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Bildirimleri Oluştur</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Test Bildirimleri Oluşturucu</h1>";

// Test bildirimlerini oluştur
$testNotifications = [
    [
        'type' => 'file_upload',
        'title' => 'Yeni Dosya Yüklendi',
        'message' => 'test_file.bin dosyası sisteme yüklendi ve işleme alındı.',
        'action_url' => 'uploads.php?id=test-uuid-1'
    ],
    [
        'type' => 'file_status_update',
        'title' => 'Dosya İşleme Tamamlandı',
        'message' => 'BMW_320d_2018.bin dosyasının işlenmesi tamamlandı. Sonuç dosyası indirilebilir.',
        'action_url' => 'file-detail.php?id=test-uuid-2'
    ],
    [
        'type' => 'revision_request',
        'title' => 'Yeni Revize Talebi',
        'message' => 'Kullanıcı test@test.com tarafından Audi_A4.bin dosyası için revize talebi oluşturuldu.',
        'action_url' => 'revisions.php?id=test-uuid-3'
    ],
    [
        'type' => 'revision_response',
        'title' => 'Revize Talebi Onaylandı',
        'message' => 'Mercedes_C200.bin dosyası için revize talebiniz onaylandı ve işleme alındı.',
        'action_url' => 'revisions.php?id=test-uuid-4'
    ],
    [
        'type' => 'system_warning',
        'title' => 'Sistem Bakım Uyarısı',
        'message' => 'Sistem 25.07.2025 tarihinde 02:00-04:00 saatleri arasında bakım nedeniyle erişilemeyecektir.',
        'action_url' => null
    ],
    [
        'type' => 'user_registration',
        'title' => 'Yeni Kullanıcı Kaydı',
        'message' => 'newuser@example.com email adresi ile yeni bir kullanıcı sisteme kaydoldu.',
        'action_url' => 'users.php?search=newuser'
    ],
    [
        'type' => 'credit_update',
        'title' => 'Kredi Bakiyesi Güncellendi',
        'message' => 'Kullanıcı kredi bakiyesi 150 TL olarak güncellendi. İşlem türü: Manuel ekleme.',
        'action_url' => 'credits.php'
    ],
    [
        'type' => 'admin_message',
        'title' => 'Önemli Duyuru',
        'message' => 'Yeni ECU modelleri sisteme eklendi. Desteklenen araç listesi güncellendi.',
        'action_url' => 'categories.php'
    ]
];

$successCount = 0;
$errorCount = 0;

foreach ($testNotifications as $notification) {
    $result = $notificationManager->createNotification(
        $_SESSION['user_id'], // Admin kullanıcısına bildirim gönder
        $notification['type'],
        $notification['title'],
        $notification['message'],
        null, // related_id
        null, // related_type
        $notification['action_url']
    );
    
    if ($result) {
        echo "<div class='success'>✅ {$notification['title']} - bildirim oluşturuldu</div>";
        $successCount++;
    } else {
        echo "<div class='error'>❌ {$notification['title']} - hata oluştu</div>";
        $errorCount++;
    }
}

echo "<br><hr><br>";
echo "<div><strong>Özet:</strong> {$successCount} başarılı, {$errorCount} hatalı</div>";
echo "<br>";
echo "<a href='notifications.php' class='btn'>Bildirimleri Görüntüle</a>";
echo "<a href='../config/check-notification-system.php' class='btn'>Sistem Durumu</a>";
echo "<a href='index.php' class='btn'>Admin Panel</a>";

echo "    </div>
</body>
</html>";
?>
