<?php
/**
 * user_credits Tablosu Oluşturma
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>user_credits Tablosu Oluşturma</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .sql-code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
    </style>
</head>
<body>";

echo "<h1>🔧 user_credits Tablosu Oluşturma</h1>";

try {
    // 1. user_credits tablosu var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_credits'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ user_credits tablosu zaten mevcut</div>";
    } else {
        echo "<div class='info'>user_credits tablosu oluşturuluyor...</div>";
        
        $createTableSQL = "
        CREATE TABLE user_credits (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            transaction_type ENUM('credit_purchase', 'file_charge', 'refund', 'bonus', 'admin_adjustment') NOT NULL,
            description TEXT,
            reference_id INT NULL,
            reference_type VARCHAR(50) NULL,
            admin_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_transaction_type (transaction_type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        
        echo "<div class='sql-code'>" . htmlspecialchars($createTableSQL) . "</div>";
        
        $pdo->exec($createTableSQL);
        echo "<div class='success'>✅ user_credits tablosu başarıyla oluşturuldu!</div>";
    }
    
    // 2. credit_transactions tablosundan veri kopyala (eğer varsa)
    $stmt = $pdo->query("SHOW TABLES LIKE 'credit_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "<h2>📋 credit_transactions Tablosu Analizi</h2>";
        
        // credit_transactions tablo yapısını göster
        $stmt = $pdo->query("DESCRIBE credit_transactions");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
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
        
        // Veri sayısını kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM credit_transactions");
        $creditTransCount = $stmt->fetch()['count'];
        echo "<div class='info'>credit_transactions tablosunda $creditTransCount kayıt var</div>";
        
        if ($creditTransCount > 0) {
            // Örnek veri göster
            $stmt = $pdo->query("SELECT * FROM credit_transactions LIMIT 5");
            $sampleData = $stmt->fetchAll();
            
            echo "<h3>Örnek Veriler:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            if (!empty($sampleData)) {
                // Header
                echo "<tr>";
                foreach (array_keys($sampleData[0]) as $column) {
                    echo "<th>$column</th>";
                }
                echo "</tr>";
                
                // Data
                foreach ($sampleData as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
            
            // Migration önerisi
            echo "<div class='info'>";
            echo "<h3>📝 Veri Migration Önerisi:</h3>";
            echo "<p>credit_transactions tablosundaki verileri user_credits'e kopyalamak için aşağıdaki adımları izleyin:</p>";
            echo "<ol>";
            echo "<li>credit_transactions tablosundaki sütun yapısını analiz edin</li>";
            echo "<li>user_credits tablosuna uygun mapping yapın</li>";
            echo "<li>Migration script çalıştırın</li>";
            echo "</ol>";
            echo "</div>";
        }
    } else {
        echo "<div class='info'>credit_transactions tablosu bulunamadı</div>";
    }
    
    // 3. Test veri ekleme
    echo "<h2>🧪 Test Verisi Ekleme</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_credits");
    $userCreditsCount = $stmt->fetch()['count'];
    
    if ($userCreditsCount == 0) {
        echo "<div class='info'>Test kredi işlemleri ekleniyor...</div>";
        
        // Normal kullanıcıyı bul
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
        $user = $stmt->fetch();
        
        if ($user) {
            $userId = $user['id'];
            
            // Test işlemleri ekle
            $testTransactions = [
                [$userId, 50.00, 'credit_purchase', 'Test kredi satın alma', null, null],
                [$userId, -5.00, 'file_charge', 'Test dosya ücreti', 1, 'file_upload'],
                [$userId, 25.00, 'bonus', 'Yeni kullanıcı bonusu', null, null]
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO user_credits (user_id, amount, transaction_type, description, reference_id, reference_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($testTransactions as $transaction) {
                $stmt->execute($transaction);
            }
            
            echo "<div class='success'>✅ Test verileri eklendi!</div>";
            
            // Users tablosundaki kredileri güncelle
            $stmt = $pdo->prepare("
                UPDATE users 
                SET credits = (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM user_credits 
                    WHERE user_id = ?
                ) 
                WHERE id = ?
            ");
            $stmt->execute([$userId, $userId]);
            
            echo "<div class='success'>✅ Users tablosundaki kredi bakiyesi güncellendi!</div>";
        }
    } else {
        echo "<div class='success'>✅ user_credits tablosunda $userCreditsCount kayıt mevcut</div>";
    }
    
    // 4. Sonuç özeti
    echo "<h2>📊 Sonuç Özeti</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_credits");
    $finalCount = $stmt->fetch()['count'];
    echo "<div class='success'>user_credits tablosunda $finalCount kayıt var</div>";
    
    if ($finalCount > 0) {
        $stmt = $pdo->query("
            SELECT 
                transaction_type, 
                COUNT(*) as count, 
                SUM(amount) as total 
            FROM user_credits 
            GROUP BY transaction_type
        ");
        $summary = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Transaction Type</th><th>Count</th><th>Total Amount</th></tr>";
        foreach ($summary as $row) {
            echo "<tr>";
            echo "<td>{$row['transaction_type']}</td>";
            echo "<td>{$row['count']}</td>";
            echo "<td>{$row['total']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<p><a href='debug-database.php'>🔍 Database debug sayfasına dön</a></p>";
echo "<p><a href='reports.php'>📊 Reports sayfasını test et</a></p>";
echo "</body></html>";
?>
