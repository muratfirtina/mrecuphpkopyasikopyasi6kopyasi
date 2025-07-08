<?php
/**
 * Transactions Debug - İşlem Geçmişi Sorun Tespiti
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Transactions Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff8e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Transactions Debug</h1>";

// Giriş kontrolü
if (!isLoggedIn()) {
    echo "<div class='error'>❌ Giriş yapmanız gerekiyor!</div>";
    echo "<a href='../login.php'>Giriş Yap</a>";
    echo "</body></html>";
    exit;
}

$userId = $_SESSION['user_id'];
echo "<div class='info'>👤 Kullanıcı ID: $userId</div>";

try {
    // 1. Tabloların varlığını kontrol et
    echo "<h2>1. Tablo Kontrolü</h2>";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('credit_transactions', $tables)) {
        echo "<div class='success'>✅ credit_transactions tablosu mevcut</div>";
        
        // Tablo yapısını kontrol et
        $structure = $pdo->query("DESCRIBE credit_transactions")->fetchAll();
        echo "<h3>Tablo Yapısı:</h3>";
        echo "<table>";
        echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($structure as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<div class='error'>❌ credit_transactions tablosu bulunamadı!</div>";
        
        // Tabloyu oluştur
        echo "<div class='info'>📝 Tablo oluşturuluyor...</div>";
        
        $createTable = "
        CREATE TABLE IF NOT EXISTS credit_transactions (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            transaction_type ENUM('add', 'deduct', 'deposit', 'purchase') NOT NULL,
            type ENUM('add', 'deduct', 'deposit', 'purchase') NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            description TEXT NULL,
            admin_id VARCHAR(36) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_type (transaction_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($pdo->exec($createTable)) {
            echo "<div class='success'>✅ credit_transactions tablosu oluşturuldu</div>";
        } else {
            echo "<div class='error'>❌ Tablo oluşturulamadı</div>";
        }
    }
    
    // 2. Kayıt sayısını kontrol et
    echo "<h2>2. Veri Kontrolü</h2>";
    
    if (in_array('credit_transactions', $tables) || $pdo->query("SHOW TABLES LIKE 'credit_transactions'")->rowCount() > 0) {
        $totalCount = $pdo->query("SELECT COUNT(*) FROM credit_transactions")->fetchColumn();
        $userCount = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
        $userCount->execute([$userId]);
        $userTransactions = $userCount->fetchColumn();
        
        echo "<div class='info'>📊 Toplam işlem sayısı: $totalCount</div>";
        echo "<div class='info'>👤 Bu kullanıcının işlem sayısı: $userTransactions</div>";
        
        if ($totalCount == 0) {
            echo "<div class='warning'>⚠️ Hiç kredi işlemi yok! Test verileri oluşturuluyor...</div>";
            
            // Test verisi oluştur
            $testTransactions = [
                [
                    'id' => generateUUID(),
                    'type' => 'add',
                    'amount' => 100.00,
                    'description' => 'İlk kredi yüklemesi'
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'deduct',
                    'amount' => 25.00,
                    'description' => 'Dosya indirme - BMW_E46.bin'
                ],
                [
                    'id' => generateUUID(),
                    'type' => 'add',
                    'amount' => 50.00,
                    'description' => 'Kredi yeniden yükleme'
                ]
            ];
            
            foreach ($testTransactions as $transaction) {
                $stmt = $pdo->prepare("
                    INSERT INTO credit_transactions (id, user_id, transaction_type, type, amount, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)
                ");
                $randomDays = rand(1, 30);
                $stmt->execute([
                    $transaction['id'],
                    $userId,
                    $transaction['type'],
                    $transaction['type'], // type sütunu için de aynı değeri kullan
                    $transaction['amount'],
                    $transaction['description'],
                    $randomDays
                ]);
            }
            
            echo "<div class='success'>✅ " . count($testTransactions) . " test işlemi oluşturuldu</div>";
        }
        
        // 3. Gerçek sorguyu test et
        echo "<h2>3. Sorgu Testi</h2>";
        
        echo "<h3>Original Query (transactions.php'den):</h3>";
        $originalQuery = "
            SELECT ct.*, u.username as admin_username 
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.admin_id = u.id
            WHERE ct.user_id = ?
            ORDER BY ct.created_at DESC
            LIMIT 10
        ";
        
        echo "<pre>" . htmlspecialchars($originalQuery) . "</pre>";
        
        try {
            $stmt = $pdo->prepare($originalQuery);
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll();
            
            echo "<div class='success'>✅ Sorgu başarıyla çalıştı</div>";
            echo "<div class='info'>📊 Bulunan kayıt sayısı: " . count($results) . "</div>";
            
            if (!empty($results)) {
                echo "<h3>Sonuçlar:</h3>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Type</th><th>Transaction Type</th><th>Amount</th><th>Description</th><th>Created</th></tr>";
                foreach ($results as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars(substr($row['id'], 0, 8)) . "...</td>";
                    echo "<td>" . htmlspecialchars($row['type'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($row['transaction_type'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ Bu kullanıcı için sonuç bulunamadı</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Sorgu hatası: " . $e->getMessage() . "</div>";
        }
        
        // 4. Alternative query test
        echo "<h3>Alternative Query Test:</h3>";
        $altQuery = "SELECT * FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
        
        try {
            $stmt = $pdo->prepare($altQuery);
            $stmt->execute([$userId]);
            $altResults = $stmt->fetchAll();
            
            echo "<div class='success'>✅ Alternative sorgu başarıyla çalıştı</div>";
            echo "<div class='info'>📊 Bulunan kayıt sayısı: " . count($altResults) . "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Alternative sorgu hatası: " . $e->getMessage() . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ credit_transactions tablosu hala mevcut değil</div>";
    }
    
    // 5. Session bilgileri
    echo "<h2>4. Session Bilgileri</h2>";
    echo "<div class='info'>Session User ID: " . ($_SESSION['user_id'] ?? 'YOK') . "</div>";
    echo "<div class='info'>Session Username: " . ($_SESSION['username'] ?? 'YOK') . "</div>";
    echo "<div class='info'>Session Role: " . ($_SESSION['role'] ?? 'YOK') . "</div>";
    
    // 6. User tablosu kontrolü
    echo "<h2>5. User Tablosu Kontrolü</h2>";
    $userInfo = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userInfo->execute([$userId]);
    $user = $userInfo->fetch();
    
    if ($user) {
        echo "<div class='success'>✅ Kullanıcı bulundu</div>";
        echo "<div class='info'>Username: " . htmlspecialchars($user['username']) . "</div>";
        echo "<div class='info'>Email: " . htmlspecialchars($user['email']) . "</div>";
        echo "<div class='info'>Role: " . htmlspecialchars($user['role'] ?? 'NULL') . "</div>";
        echo "<div class='info'>Credits: " . htmlspecialchars($user['credits'] ?? '0') . "</div>";
    } else {
        echo "<div class='error'>❌ Kullanıcı bulunamadı!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Genel Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<a href='transactions.php'>🔄 Transactions sayfasına git</a> | ";
echo "<a href='index.php'>🏠 Dashboard'a git</a>";
echo "</body></html>";
?>