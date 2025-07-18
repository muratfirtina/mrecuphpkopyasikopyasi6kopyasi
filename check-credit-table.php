<?php
/**
 * Credit Transactions Tablo Yapısı Kontrolü
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
    
    echo "<h2>Credit Transactions Tablo Kontrolü</h2>";
    
    // 1. Tablo var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'credit_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ credit_transactions tablosu bulundu</p>";
        
        // 2. Tablo yapısını göster
        echo "<h3>Tablo Yapısı:</h3>";
        $stmt = $pdo->query("DESCRIBE credit_transactions");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            $bgColor = $column['Field'] === 'type' ? 'background: yellow;' : '';
            echo "<tr style='$bgColor'>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 3. Mevcut type değerlerini kontrol et
        echo "<h3>Mevcut Type Değerleri:</h3>";
        try {
            $stmt = $pdo->query("SELECT DISTINCT type, LENGTH(type) as length, COUNT(*) as count FROM credit_transactions GROUP BY type ORDER BY count DESC");
            $types = $stmt->fetchAll();
            
            if (!empty($types)) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background: #f0f0f0;'><th>Type Value</th><th>Character Length</th><th>Count</th></tr>";
                foreach ($types as $type) {
                    echo "<tr>";
                    echo "<td>{$type['type']}</td>";
                    echo "<td>{$type['length']}</td>";
                    echo "<td>{$type['count']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Henüz veri yok</p>";
            }
        } catch (Exception $e) {
            echo "<p>Type değerleri alınırken hata: " . $e->getMessage() . "</p>";
        }
        
        // 4. Toplam kayıt sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM credit_transactions");
        $total = $stmt->fetch()['total'];
        echo "<p><strong>Toplam kayıt sayısı:</strong> $total</p>";
        
        // 5. Type sütunu için önerilen düzeltme
        echo "<h3>Önerilen Düzeltme:</h3>";
        echo "<p>Eğer 'type' sütunu çok kısaysa, aşağıdaki SQL ile genişletebilirsiniz:</p>";
        echo "<code>ALTER TABLE credit_transactions MODIFY type VARCHAR(50) NOT NULL;</code>";
        
    } else {
        echo "<p>❌ credit_transactions tablosu bulunamadı</p>";
        
        // Tablo yoksa oluşturalım
        echo "<h3>Credit Transactions Tablosu Oluşturuluyor...</h3>";
        $createSQL = "
        CREATE TABLE credit_transactions (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            admin_id CHAR(36) NULL,
            transaction_type ENUM('add', 'deduct') NOT NULL,
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Bağlantı hatası: " . $e->getMessage() . "</p>";
}
?>
