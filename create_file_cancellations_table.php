<?php
/**
 * Create file_cancellations table and test data
 * Dosya iptal talepleri tablosunu oluştur ve test verisi ekle
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>File Cancellations Table Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🗂️ File Cancellations Table Setup</h1>";

try {
    // 1. Check if table exists
    echo "<div class='step'>";
    echo "<h2>1. Tablo Varlığı Kontrolü</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_cancellations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div class='info'>✅ file_cancellations tablosu zaten mevcut.</div>";
    } else {
        echo "<div class='info'>❌ file_cancellations tablosu mevcut değil. Oluşturuluyor...</div>";
        
        // 2. Create table
        $pdo->exec("
        CREATE TABLE file_cancellations (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            file_id CHAR(36) NOT NULL,
            file_type ENUM('upload', 'response', 'revision', 'additional') NOT NULL,
            reason TEXT NOT NULL,
            credits_to_refund DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_id CHAR(36) NULL,
            admin_notes TEXT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_file_id (file_id),
            INDEX idx_status (status),
            INDEX idx_requested_at (requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "<div class='success'>✅ file_cancellations tablosu başarıyla oluşturuldu!</div>";
    }
    echo "</div>";
    
    // 3. Check table structure
    echo "<div class='step'>";
    echo "<h2>2. Tablo Yapısı</h2>";
    
    $stmt = $pdo->query("DESCRIBE file_cancellations");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 4. Check existing data
    echo "<div class='step'>";
    echo "<h2>3. Mevcut Veri Kontrolü</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM file_cancellations");
    $existingCount = $stmt->fetchColumn();
    
    echo "<div class='info'>📊 Mevcut kayıt sayısı: <strong>$existingCount</strong></div>";
    
    if ($existingCount == 0) {
        echo "<h3>Test Verisi Oluşturuluyor...</h3>";
        
        // Get a sample user and file for test data
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
        $sampleUser = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT id FROM file_uploads LIMIT 1");
        $sampleFile = $stmt->fetch();
        
        if ($sampleUser && $sampleFile) {
            // Create test cancellation requests
            $testData = [
                [
                    'id' => generateUUID(),
                    'user_id' => $sampleUser['id'],
                    'file_id' => $sampleFile['id'],
                    'file_type' => 'upload',
                    'reason' => 'Test iptal talebi - Dosya yanlış yüklendi',
                    'credits_to_refund' => 50.00,
                    'status' => 'pending'
                ],
                [
                    'id' => generateUUID(),
                    'user_id' => $sampleUser['id'],
                    'file_id' => generateUUID(), // Fake file ID for demo
                    'file_type' => 'response',
                    'reason' => 'Test iptal talebi - Sonuç dosyası beklediğim gibi değil',
                    'credits_to_refund' => 75.00,
                    'status' => 'approved',
                    'admin_notes' => 'İptal talebi onaylandı, kredi iadesi yapıldı.',
                    'processed_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ],
                [
                    'id' => generateUUID(),
                    'user_id' => $sampleUser['id'],
                    'file_id' => generateUUID(), // Fake file ID for demo
                    'file_type' => 'revision',
                    'reason' => 'Test iptal talebi - Revize isteği geçersiz',
                    'credits_to_refund' => 0.00,
                    'status' => 'rejected',
                    'admin_notes' => 'İptal talebi reddedildi. Dosya işlemi geçerli.',
                    'processed_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ];
            
            foreach ($testData as $data) {
                $stmt = $pdo->prepare("
                    INSERT INTO file_cancellations (
                        id, user_id, file_id, file_type, reason, credits_to_refund, 
                        status, admin_notes, requested_at, processed_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $data['id'],
                    $data['user_id'],
                    $data['file_id'],
                    $data['file_type'],
                    $data['reason'],
                    $data['credits_to_refund'],
                    $data['status'],
                    $data['admin_notes'] ?? null,
                    $data['processed_at'] ?? null
                ]);
            }
            
            echo "<div class='success'>✅ " . count($testData) . " test verisi başarıyla eklendi!</div>";
            
        } else {
            echo "<div class='error'>❌ Test verisi için gerekli kullanıcı veya dosya bulunamadı.</div>";
            echo "<div class='info'>💡 Önce bazı kullanıcılar oluşturun ve dosya yükleyin.</div>";
        }
    }
    echo "</div>";
    
    // 5. Show sample data
    echo "<div class='step'>";
    echo "<h2>4. Örnek Veriler</h2>";
    
    $stmt = $pdo->query("
        SELECT fc.*, u.username, u.email 
        FROM file_cancellations fc
        LEFT JOIN users u ON fc.user_id = u.id
        ORDER BY fc.requested_at DESC 
        LIMIT 5
    ");
    $sampleData = $stmt->fetchAll();
    
    if (!empty($sampleData)) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>ID</th><th>Kullanıcı</th><th>Dosya Tipi</th><th>Sebep</th><th>Kredi İadesi</th><th>Durum</th><th>Talep Tarihi</th>";
        echo "</tr>";
        
        foreach ($sampleData as $row) {
            $statusColors = [
                'pending' => '#ffc107',
                'approved' => '#28a745',
                'rejected' => '#dc3545'
            ];
            $statusTexts = [
                'pending' => 'Bekleyen',
                'approved' => 'Onaylandı',
                'rejected' => 'Reddedildi'
            ];
            
            echo "<tr>";
            echo "<td title='{$row['id']}'>" . substr($row['id'], 0, 8) . "...</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>" . strtoupper($row['file_type']) . "</td>";
            echo "<td>" . substr($row['reason'], 0, 50) . "...</td>";
            echo "<td>{$row['credits_to_refund']} TL</td>";
            echo "<td style='background: {$statusColors[$row['status']]}; color: white; text-align: center;'>";
            echo $statusTexts[$row['status']];
            echo "</td>";
            echo "<td>{$row['requested_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>📝 Henüz iptal talebi bulunmuyor.</div>";
    }
    echo "</div>";
    
    // 6. Test the cancellation page
    echo "<div class='step'>";
    echo "<h2>5. Test Bağlantıları</h2>";
    echo "<p><a href='user/cancellations.php' target='_blank' style='color: #007bff; text-decoration: none;'>➡️ İptal Taleplerim Sayfasını Test Et</a></p>";
    echo "<p><small>Not: Giriş yapmış olmanız gerekiyor.</small></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";
echo "</body></html>";
?>
