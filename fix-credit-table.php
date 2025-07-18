<?php
/**
 * Credit Transactions Tablo Düzeltme Script'i
 */

try {
    // Database connection
    $host = '127.0.0.1';
    $port = '8889';
    $db_name = 'mrecu_db_guid';
    $username = 'root';
    $password = 'root';
    $charset = 'utf8mb4';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Credit Transactions Tablo Düzeltme</h2>";
    
    // 1. Tablo var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'credit_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ credit_transactions tablosu bulundu</p>";
        
        // 2. Mevcut tablo yapısını kontrol et
        $stmt = $pdo->query("DESCRIBE credit_transactions");
        $columns = $stmt->fetchAll();
        
        echo "<h3>Mevcut Tablo Yapısı:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        
        $typeColumn = null;
        $hasTransactionType = false;
        
        foreach ($columns as $column) {
            $bgColor = $column['Field'] === 'type' ? 'background: yellow;' : '';
            echo "<tr style='$bgColor'>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
            
            if ($column['Field'] === 'type') {
                $typeColumn = $column;
            }
            if ($column['Field'] === 'transaction_type') {
                $hasTransactionType = true;
            }
        }
        echo "</table>";
        
        // 3. Gerekli düzeltmeleri yap
        $needsUpdate = false;
        $updateSQL = [];
        
        // Type sütunu çok kısaysa genişlet
        if ($typeColumn && (strpos($typeColumn['Type'], 'varchar') !== false || strpos($typeColumn['Type'], 'char') !== false)) {
            // VARCHAR uzunluğunu kontrol et
            preg_match('/\((\d+)\)/', $typeColumn['Type'], $matches);
            $currentLength = isset($matches[1]) ? intval($matches[1]) : 0;
            
            if ($currentLength < 50) {
                $updateSQL[] = "MODIFY type VARCHAR(50) NOT NULL DEFAULT 'manual'";
                $needsUpdate = true;
                echo "<p>⚠️ Type sütunu çok kısa ($currentLength karakter), 50 karaktere genişletilecek</p>";
            }
        }
        
        // transaction_type sütunu yoksa ekle
        if (!$hasTransactionType) {
            $updateSQL[] = "ADD COLUMN transaction_type ENUM('add', 'deduct') NOT NULL DEFAULT 'add' AFTER admin_id";
            $needsUpdate = true;
            echo "<p>⚠️ transaction_type sütunu eksik, eklenecek</p>";
        }
        
        // Reference sütunları yoksa ekle
        $hasReference = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'reference_id') {
                $hasReference = true;
                break;
            }
        }
        
        if (!$hasReference) {
            $updateSQL[] = "ADD COLUMN reference_id CHAR(36) NULL AFTER description";
            $updateSQL[] = "ADD COLUMN reference_type VARCHAR(50) NULL AFTER reference_id";
            $needsUpdate = true;
            echo "<p>⚠️ Reference sütunları eksik, eklenecek</p>";
        }
        
        // Düzeltmeleri uygula
        if ($needsUpdate) {
            echo "<h3>Tablo Güncellemeleri Uygulanıyor...</h3>";
            
            foreach ($updateSQL as $sql) {
                try {
                    $fullSQL = "ALTER TABLE credit_transactions $sql";
                    echo "<p>🔧 Executing: <code>$fullSQL</code></p>";
                    $pdo->exec($fullSQL);
                    echo "<p>✅ Başarıyla uygulandı</p>";
                } catch (Exception $e) {
                    echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<h3>✅ Tablo güncellemeleri tamamlandı!</h3>";
        } else {
            echo "<p>✅ Tablo yapısı zaten uygun, güncelleme gerekmiyor</p>";
        }
        
        // 4. Test verisi ekle
        echo "<h3>Test Verisi Ekleniyor...</h3>";
        
        // Admin kullanıcısını bul
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        
        // Normal kullanıcı bul
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
        $testUser = $stmt->fetch();
        
        if ($admin && $testUser) {
            // Test verisi ekle
            $testId = bin2hex(random_bytes(16));
            $testId = substr($testId, 0, 8) . '-' . substr($testId, 8, 4) . '-' . substr($testId, 12, 4) . '-' . substr($testId, 16, 4) . '-' . substr($testId, 20, 12);
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions 
                    (id, user_id, admin_id, transaction_type, type, amount, description, created_at) 
                    VALUES (?, ?, ?, 'add', 'test_data', 10.00, 'Test kredi işlemi - tablo düzeltme sonrası', NOW())
                ");
                $stmt->execute([$testId, $testUser['id'], $admin['id']]);
                echo "<p>✅ Test verisi başarıyla eklendi</p>";
            } catch (Exception $e) {
                echo "<p>❌ Test verisi eklenirken hata: " . $e->getMessage() . "</p>";
            }
        }
        
        // 5. Son durum kontrolü
        echo "<h3>Son Durum:</h3>";
        $stmt = $pdo->query("DESCRIBE credit_transactions");
        $newColumns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #e8f5e8;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($newColumns as $column) {
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>❌ credit_transactions tablosu bulunamadı</p>";
        
        // Tabloyu oluştur
        echo "<h3>Credit Transactions Tablosu Oluşturuluyor...</h3>";
        $createSQL = "
        CREATE TABLE credit_transactions (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            admin_id CHAR(36) NULL,
            transaction_type ENUM('add', 'deduct') NOT NULL DEFAULT 'add',
            type VARCHAR(50) NOT NULL DEFAULT 'manual',
            amount DECIMAL(10,2) NOT NULL,
            description TEXT NULL,
            reference_id CHAR(36) NULL,
            reference_type VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $pdo->exec($createSQL);
            echo "<p>✅ credit_transactions tablosu başarıyla oluşturuldu!</p>";
        } catch (Exception $e) {
            echo "<p>❌ Tablo oluşturulurken hata: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🎉 İşlem Tamamlandı!</h3>";
    echo "<p>Artık kredi işlemlerini <a href='admin/credits.php'>credits.php</a> sayfasından test edebilirsiniz.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Bağlantı hatası: " . $e->getMessage() . "</p>";
}
?>
