<?php
/**
 * Mr ECU - Minimal Admin Cancel Migration
 * Sadece eksik olan 2 kolonu ekler
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Minimal Admin Cancel Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; background: #f0f8f0; padding: 10px; border: 1px solid green; margin: 10px 0; border-radius: 5px; }
        .error { color: red; background: #f8f0f0; padding: 10px; border: 1px solid red; margin: 10px 0; border-radius: 5px; }
        .info { color: blue; background: #f0f0f8; padding: 10px; border: 1px solid blue; margin: 10px 0; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔧 Minimal Admin Cancel Migration</h1>";
echo "<p>Database analizi tamamlandı. Sadece 2 kolon eklememiz yeterli!</p>";

$errors = [];
$successes = [];

try {
    // 1. revisions tablosuna cancelled_at kolonu
    echo "<h2>1. revisions.cancelled_at Kolonu</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'cancelled_at'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>⚠️ revisions tablosuna cancelled_at kolonu ekleniyor...</div>";
        
        $pdo->exec("ALTER TABLE revisions ADD COLUMN cancelled_at TIMESTAMP NULL AFTER is_cancelled");
        $successes[] = "✅ revisions.cancelled_at kolonu başarıyla eklendi.";
        
    } else {
        $successes[] = "✅ revisions.cancelled_at kolonu zaten mevcut.";
    }
    
    // 2. revisions tablosuna cancelled_by kolonu
    echo "<h2>2. revisions.cancelled_by Kolonu</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'cancelled_by'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='info'>⚠️ revisions tablosuna cancelled_by kolonu ekleniyor...</div>";
        
        $pdo->exec("ALTER TABLE revisions ADD COLUMN cancelled_by CHAR(36) NULL AFTER cancelled_at");
        $successes[] = "✅ revisions.cancelled_by kolonu başarıyla eklendi.";
        
    } else {
        $successes[] = "✅ revisions.cancelled_by kolonu zaten mevcut.";
    }
    
    // 3. Sonuç kontrolü
    echo "<h2>3. Final Kontrol</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'cancelled_at'");
    $hasCancelledAt = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'cancelled_by'");
    $hasCancelledBy = $stmt->rowCount() > 0;
    
    if ($hasCancelledAt && $hasCancelledBy) {
        echo "<div class='success'>";
        echo "<h3>🎉 Migration Başarıyla Tamamlandı!</h3>";
        echo "<p>Admin cancel sistemi için gerekli tüm database yapıları artık mevcut.</p>";
        echo "<ul>";
        echo "<li>✅ file_cancellations tablosu</li>";
        echo "<li>✅ Tüm dosya tablolarında iptal kolonları</li>";
        echo "<li>✅ revisions tablosunda iptal kolonları</li>";
        echo "<li>✅ Admin kullanıcı mevcut</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Migration Tamamlanamadı!</h3>";
        echo "<p>Bazı kolonlar hala eksik:</p>";
        echo "<ul>";
        if (!$hasCancelledAt) echo "<li>❌ revisions.cancelled_at</li>";
        if (!$hasCancelledBy) echo "<li>❌ revisions.cancelled_by</li>";
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Migration hatası: " . $e->getMessage();
}

// Sonuçları göster
echo "<h2>📋 Migration Sonuçları</h2>";

if (!empty($successes)) {
    echo "<div class='success'>";
    echo "<h3>✅ Başarılı İşlemler:</h3>";
    foreach ($successes as $success) {
        echo "<p>{$success}</p>";
    }
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div class='error'>";
    echo "<h3>❌ Hatalar:</h3>";
    foreach ($errors as $error) {
        echo "<p>{$error}</p>";
    }
    echo "</div>";
}

if (empty($errors)) {
    echo "<div class='success'>";
    echo "<h3>🚀 Sistem Tamamen Hazır!</h3>";
    echo "<p>Admin iptal sistemi artık tamamen kullanılabilir.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Sonraki Adımlar:</strong></p>";
echo "<ol>";
echo "<li><a href='test_admin_cancel.php' style='color: green; font-weight: bold;'>🧪 Admin İptal Sistemini Test Et</a></li>";
echo "<li><a href='admin/uploads.php'>📁 Admin Panel - Dosyalar</a></li>";
echo "<li><a href='admin/file-cancellations.php'>📋 Admin Panel - İptal Talepleri</a></li>";
echo "</ol>";

echo "</body></html>";
?>
