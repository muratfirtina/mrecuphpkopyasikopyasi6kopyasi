<?php
/**
 * Kredi İşlemleri Tablosu Kurulum ve Onarım Scripti
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Credit Transactions Kurulum</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff8e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>💰 Credit Transactions Kurulum</h1>";

try {
    // 1. Tabloyu kontrol et
    echo "<h2>1. Tablo Kontrolü</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'credit_transactions'")->fetchAll();
    
    if (empty($tables)) {
        echo "<div class='warning'>⚠️ credit_transactions tablosu bulunamadı. Oluşturuluyor...</div>";
        
        // Tabloyu oluştur
        $createTable = "
        CREATE TABLE IF NOT EXISTS credit_transactions (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            transaction_type ENUM('add', 'deduct', 'deposit', 'purchase', 'withdraw', 'file_charge') NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            description TEXT NULL,
            reference_id VARCHAR(36) NULL,
            reference_type VARCHAR(50) NULL,
            admin_id VARCHAR(36) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_type (transaction_type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($pdo->exec($createTable)) {
            echo "<div class='success'>✅ credit_transactions tablosu başarıyla oluşturuldu</div>";
        } else {
            echo "<div class='error'>❌ Tablo oluşturulamadı</div>";
            exit;
        }
    } else {
        echo "<div class='success'>✅ credit_transactions tablosu mevcut</div>";
        
        // Tablo yapısını kontrol et
        $structure = $pdo->query("DESCRIBE credit_transactions")->fetchAll();
        $hasTransactionType = false;
        $hasType = false;
        
        foreach ($structure as $column) {
            if ($column['Field'] === 'transaction_type') {
                $hasTransactionType = true;
            }
            if ($column['Field'] === 'type') {
                $hasType = true;
            }
        }
        
        // Eğer transaction_type yoksa ekle
        if (!$hasTransactionType) {
            echo "<div class='info'>🔧 transaction_type sütunu ekleniyor...</div>";
            
            if ($hasType) {
                // type sütunu varsa, verilerini transaction_type'a kopyala
                $pdo->exec("ALTER TABLE credit_transactions ADD COLUMN transaction_type ENUM('add', 'deduct', 'deposit', 'purchase', 'withdraw', 'file_charge') NULL AFTER user_id");
                $pdo->exec("UPDATE credit_transactions SET transaction_type = type WHERE transaction_type IS NULL");
                $pdo->exec("ALTER TABLE credit_transactions MODIFY transaction_type ENUM('add', 'deduct', 'deposit', 'purchase', 'withdraw', 'file_charge') NOT NULL");
                echo "<div class='success'>✅ transaction_type sütunu eklendi ve veriler kopyalandı</div>";
            } else {
                $pdo->exec("ALTER TABLE credit_transactions ADD COLUMN transaction_type ENUM('add', 'deduct', 'deposit', 'purchase', 'withdraw', 'file_charge') NOT NULL DEFAULT 'add' AFTER user_id");
                echo "<div class='success'>✅ transaction_type sütunu eklendi</div>";
            }
        }
        
        // Eksik sütunları kontrol et ve ekle
        $requiredColumns = [
            'reference_id' => 'VARCHAR(36) NULL',
            'reference_type' => 'VARCHAR(50) NULL',
            'admin_id' => 'VARCHAR(36) NULL'
        ];
        
        foreach ($requiredColumns as $columnName => $columnDef) {
            $hasColumn = false;
            foreach ($structure as $column) {
                if ($column['Field'] === $columnName) {
                    $hasColumn = true;
                    break;
                }
            }
            
            if (!$hasColumn) {
                echo "<div class='info'>🔧 $columnName sütunu ekleniyor...</div>";
                $pdo->exec("ALTER TABLE credit_transactions ADD COLUMN $columnName $columnDef");
                echo "<div class='success'>✅ $columnName sütunu eklendi</div>";
            }
        }
    }
    
    // 2. Test verisi oluştur
    echo "<h2>2. Test Verisi Kontrolü</h2>";
    
    $count = $pdo->query("SELECT COUNT(*) FROM credit_transactions")->fetchColumn();
    echo "<div class='info'>📊 Mevcut işlem sayısı: $count</div>";
    
    if ($count == 0) {
        echo "<div class='info'>📝 Test verileri oluşturuluyor...</div>";
        
        // Test kullanıcısı al
        $testUser = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1")->fetch();
        
        if ($testUser) {
            $testTransactions = [
                [
                    'id' => generateUUID(),
                    'type' => 'deposit',
                    'amount' => 100.00,
                    'description' => 'İlk kredi yüklemesi - Hoş geldin bonusu',
                    'days_ago' => 30
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deduct',
                    'amount' => 15.00,
                    'description' => 'Dosya indirme - BMW_E46_320d.bin',
                    'days_ago' => 25
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deposit',
                    'amount' => 50.00,
                    'description' => 'Kredi yeniden yükleme',
                    'days_ago' => 20
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deduct',
                    'amount' => 20.00,
                    'description' => 'Dosya indirme - AUDI_A4_2.0TDI.bin',
                    'days_ago' => 15
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deposit',
                    'amount' => 75.00,
                    'description' => 'Aylık kredi paketi',
                    'days_ago' => 10
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deduct',
                    'amount' => 12.00,
                    'description' => 'Dosya indirme - MERCEDES_C220.bin',
                    'days_ago' => 5
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deduct',
                    'amount' => 18.00,
                    'description' => 'Dosya indirme - VW_GOLF_1.9TDI.bin',
                    'days_ago' => 2
                ]
            ];
            
            foreach ($testTransactions as $transaction) {
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, transaction_type, amount, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
                ");
                $stmt->execute([
                    $transaction['id'],
                    $testUser['id'],
                    $transaction['type'],
                    $transaction['amount'],
                    $transaction['description'],
                    $transaction['days_ago']
                ]);
            }
            
            echo "<div class='success'>✅ " . count($testTransactions) . " test işlemi oluşturuldu</div>";
            
            // Kullanıcının kredi bakiyesini güncelle
            $totalCredits = $pdo->prepare("
                SELECT 
                    (SELECT COALESCE(SUM(amount), 0) FROM credit_transactions WHERE user_id = ? AND transaction_type IN ('add', 'deposit')) -
                    (SELECT COALESCE(SUM(amount), 0) FROM credit_transactions WHERE user_id = ? AND transaction_type IN ('deduct', 'purchase', 'withdraw', 'file_charge'))
                AS balance
            ");
            $totalCredits->execute([$testUser['id'], $testUser['id']]);
            $balance = $totalCredits->fetchColumn();
            
            $updateUser = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
            $updateUser->execute([$balance, $testUser['id']]);
            
            echo "<div class='info'>💰 Kullanıcı bakiyesi güncellendi: " . number_format($balance, 2) . " TL</div>";
            
        } else {
            echo "<div class='warning'>⚠️ Test kullanıcısı bulunamadı</div>";
        }
    } else {
        echo "<div class='success'>✅ İşlem verileri mevcut</div>";
    }
    
    // 3. Tablo yapısını göster
    echo "<h2>3. Final Tablo Yapısı</h2>";
    $finalStructure = $pdo->query("DESCRIBE credit_transactions")->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($finalStructure as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>✅ Kurulum tamamlandı!</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<a href='transactions.php'>🔄 Transactions sayfasına git</a> | ";
echo "<a href='transactions-debug.php'>🔍 Debug sayfasına git</a> | ";
echo "<a href='index.php'>🏠 Dashboard'a git</a>";
echo "</body></html>";
?>