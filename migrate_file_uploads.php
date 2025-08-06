<?php
/**
 * File Uploads Tablosu Migration Script
 * GUID tabanlı sisteme geçiş için database güncellemesi
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>File Uploads Tablosu Migration</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .step { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

try {
    // Step 1: Mevcut tablo yapısını kontrol et
    echo "<div class='step'>";
    echo "<h3>Adım 1: Mevcut tablo yapısını kontrol et</h3>";
    
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    $existingColumns = array_column($columns, 'Field');
    echo "<p class='info'>Mevcut kolonlar: " . implode(', ', $existingColumns) . "</p>";
    
    // Yeni kolonları kontrol et
    $requiredColumns = ['series_id', 'engine_id', 'device_id', 'ecu_id'];
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "<p class='success'>Tüm gerekli kolonlar mevcut!</p>";
    } else {
        echo "<p class='info'>Eklenecek kolonlar: " . implode(', ', $missingColumns) . "</p>";
    }
    echo "</div>";
    
    // Step 2: Yeni kolonları ekle
    if (!empty($missingColumns)) {
        echo "<div class='step'>";
        echo "<h3>Adım 2: Yeni kolonları ekle</h3>";
        
        if (in_array('series_id', $missingColumns)) {
            $pdo->exec("ALTER TABLE file_uploads ADD COLUMN series_id CHAR(36) NULL AFTER model_id");
            echo "<p class='success'>series_id kolonu eklendi</p>";
        }
        
        if (in_array('engine_id', $missingColumns)) {
            $pdo->exec("ALTER TABLE file_uploads ADD COLUMN engine_id CHAR(36) NULL AFTER series_id");
            echo "<p class='success'>engine_id kolonu eklendi</p>";
        }
        
        if (in_array('device_id', $missingColumns)) {
            $pdo->exec("ALTER TABLE file_uploads ADD COLUMN device_id CHAR(36) NULL AFTER engine_id");
            echo "<p class='success'>device_id kolonu eklendi</p>";
        }
        
        if (in_array('ecu_id', $missingColumns)) {
            $pdo->exec("ALTER TABLE file_uploads ADD COLUMN ecu_id CHAR(36) NULL AFTER device_id");
            echo "<p class='success'>ecu_id kolonu eklendi</p>";
        }
        echo "</div>";
    }
    
    // Step 3: İndeksleri ekle
    echo "<div class='step'>";
    echo "<h3>Adım 3: İndeksleri ekle</h3>";
    
    $indexes = [
        'idx_file_uploads_series' => 'CREATE INDEX idx_file_uploads_series ON file_uploads(series_id)',
        'idx_file_uploads_engine' => 'CREATE INDEX idx_file_uploads_engine ON file_uploads(engine_id)',
        'idx_file_uploads_device' => 'CREATE INDEX idx_file_uploads_device ON file_uploads(device_id)',
        'idx_file_uploads_ecu' => 'CREATE INDEX idx_file_uploads_ecu ON file_uploads(ecu_id)'
    ];
    
    foreach ($indexes as $indexName => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='success'>İndeks eklendi: $indexName</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p class='info'>İndeks zaten mevcut: $indexName</p>";
            } else {
                echo "<p class='error'>İndeks ekleme hatası ($indexName): " . $e->getMessage() . "</p>";
            }
        }
    }
    echo "</div>";
    
    // Step 4: Foreign key constraint'leri ekle (opsiyonel)
    echo "<div class='step'>";
    echo "<h3>Adım 4: Foreign Key Constraint'leri (Opsiyonel)</h3>";
    
    $constraints = [
        'fk_file_uploads_series' => 'ALTER TABLE file_uploads ADD CONSTRAINT fk_file_uploads_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_file_uploads_engine' => 'ALTER TABLE file_uploads ADD CONSTRAINT fk_file_uploads_engine FOREIGN KEY (engine_id) REFERENCES engines(id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_file_uploads_device' => 'ALTER TABLE file_uploads ADD CONSTRAINT fk_file_uploads_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_file_uploads_ecu' => 'ALTER TABLE file_uploads ADD CONSTRAINT fk_file_uploads_ecu FOREIGN KEY (ecu_id) REFERENCES ecus(id) ON DELETE SET NULL ON UPDATE CASCADE'
    ];
    
    foreach ($constraints as $constraintName => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='success'>Foreign key eklendi: $constraintName</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate foreign key constraint name') !== false) {
                echo "<p class='info'>Foreign key zaten mevcut: $constraintName</p>";
            } else {
                echo "<p class='error'>Foreign key ekleme hatası ($constraintName): " . $e->getMessage() . "</p>";
                echo "<p class='info'>Not: Bu hata normal olabilir. Foreign key constraint'leri opsiyoneldir.</p>";
            }
        }
    }
    echo "</div>";
    
    // Step 5: ecu_type kolonu düzenle
    echo "<div class='step'>";
    echo "<h3>Adım 5: ecu_type kolonunu opsiyonel yap</h3>";
    
    try {
        $pdo->exec("ALTER TABLE file_uploads MODIFY COLUMN ecu_type VARCHAR(100) NULL");
        echo "<p class='success'>ecu_type kolonu opsiyonel yapıldı</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>ecu_type kolonu düzenleme hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Step 6: Final kontrol
    echo "<div class='step'>";
    echo "<h3>Adım 6: Final kontrol</h3>";
    
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $finalColumns = $stmt->fetchAll();
    
    echo "<h4>Güncellenmiş Tablo Yapısı:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Kolon Adı</th><th>Veri Tipi</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th></tr>";
    
    foreach ($finalColumns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Kayıt sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM file_uploads");
    $count = $stmt->fetch();
    echo "<p class='info'>Toplam dosya kaydı: " . $count['total'] . "</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3 class='success'>✅ Migration Tamamlandı!</h3>";
    echo "<p>file_uploads tablosu GUID tabanlı sisteme başarıyla güncellenmiştir.</p>";
    echo "<p><strong>Sonraki adımlar:</strong></p>";
    echo "<ul>";
    echo "<li>Upload.php sayfası artık yeni alanları kullanacak</li>";
    echo "<li>Admin panelinde dosya detayları series, engine, device ve ecu bilgilerini gösterecek</li>";
    echo "<li>Eski device_type, motor, type kolonları kullanılmamak üzere bırakıldı (silinmedi)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step'>";
    echo "<h3 class='error'>❌ Migration Hatası</h3>";
    echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='user/upload.php'>Upload sayfasını test et</a> | <a href='admin/'>Admin paneline git</a></p>";
?>
