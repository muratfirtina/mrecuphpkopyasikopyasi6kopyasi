<?php
/**
 * Credit Transactions Sütun Temizlik ve Standardizasyon Scripti
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Credit Transactions Temizlik</title>
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
        .btn { padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>";

echo "<h1>🧹 Credit Transactions Temizlik</h1>";

try {
    // 1. Mevcut durumu analiz et
    echo "<h2>1. Mevcut Durum Analizi</h2>";
    
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
    
    echo "<div class='info'>📊 transaction_type sütunu: " . ($hasTransactionType ? "✅ VAR" : "❌ YOK") . "</div>";
    echo "<div class='info'>📊 type sütunu: " . ($hasType ? "✅ VAR" : "❌ YOK") . "</div>";
    
    if ($hasTransactionType && $hasType) {
        echo "<div class='warning'>⚠️ Her iki sütun da mevcut. Temizlik gerekiyor!</div>";
        
        // 2. Veri uyumsuzluklarını kontrol et
        echo "<h2>2. Veri Uyumsuzluk Kontrolü</h2>";
        
        $inconsistencies = $pdo->query("
            SELECT id, transaction_type, type, description, amount, created_at
            FROM credit_transactions 
            WHERE transaction_type != type OR transaction_type IS NULL OR type IS NULL
            ORDER BY created_at DESC
        ")->fetchAll();
        
        if (!empty($inconsistencies)) {
            echo "<div class='error'>❌ " . count($inconsistencies) . " uyumsuz kayıt bulundu:</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Transaction Type</th><th>Type</th><th>Description</th><th>Amount</th><th>Date</th></tr>";
            foreach ($inconsistencies as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(substr($row['id'], 0, 8)) . "...</td>";
                echo "<td>" . htmlspecialchars($row['transaction_type'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['type'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['description'], 0, 30)) . "...</td>";
                echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='success'>✅ Tüm kayıtlar tutarlı</div>";
        }
        
        // 3. Temizlik işlemleri
        echo "<h2>3. Temizlik İşlemleri</h2>";
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'sync_to_transaction_type':
                    echo "<div class='info'>🔄 type verilerini transaction_type'a kopyalıyor...</div>";
                    
                    // type'dan transaction_type'a kopyala
                    $updated = $pdo->exec("
                        UPDATE credit_transactions 
                        SET transaction_type = type 
                        WHERE transaction_type IS NULL OR transaction_type = ''
                    ");
                    
                    echo "<div class='success'>✅ $updated kayıt güncellendi</div>";
                    break;
                    
                case 'drop_type_column':
                    echo "<div class='info'>🗑️ type sütunu kaldırılıyor...</div>";
                    
                    // Önce tüm verilerin transaction_type'da olduğundan emin ol
                    $pdo->exec("
                        UPDATE credit_transactions 
                        SET transaction_type = COALESCE(transaction_type, type)
                        WHERE transaction_type IS NULL OR transaction_type = ''
                    ");
                    
                    // type sütununu kaldır
                    $pdo->exec("ALTER TABLE credit_transactions DROP COLUMN type");
                    
                    echo "<div class='success'>✅ type sütunu başarıyla kaldırıldı</div>";
                    echo "<div class='info'>🔄 Sayfa yenileniyor...</div>";
                    echo "<meta http-equiv='refresh' content='2'>";
                    break;
                    
                case 'standardize_values':
                    echo "<div class='info'>📝 Değerler standartlaştırılıyor...</div>";
                    
                    // Değer mapping
                    $mappings = [
                        'add' => ['add', 'deposit'],
                        'deduct' => ['deduct', 'withdraw', 'file_charge', 'purchase']
                    ];
                    
                    $totalUpdated = 0;
                    foreach ($mappings as $standardValue => $variants) {
                        foreach ($variants as $variant) {
                            if ($variant !== $standardValue) {
                                $stmt = $pdo->prepare("
                                    UPDATE credit_transactions 
                                    SET transaction_type = ? 
                                    WHERE transaction_type = ?
                                ");
                                $affected = $stmt->execute([$standardValue, $variant]);
                                $totalUpdated += $stmt->rowCount();
                            }
                        }
                    }
                    
                    echo "<div class='success'>✅ $totalUpdated kayıt standartlaştırıldı</div>";
                    break;
            }
        }
        
        // 4. Temizlik seçenekleri
        if ($hasType) {
            echo "<h3>Temizlik Seçenekleri:</h3>";
            echo "<form method='POST'>";
            echo "<button type='submit' name='action' value='sync_to_transaction_type' class='btn'>🔄 type → transaction_type Senkronize Et</button>";
            echo "<button type='submit' name='action' value='standardize_values' class='btn btn-success'>📝 Değerleri Standartlaştır</button>";
            echo "<button type='submit' name='action' value='drop_type_column' class='btn btn-danger' onclick='return confirm(\"type sütununu kalıcı olarak silmek istediğinizden emin misiniz?\")'>🗑️ type Sütununu Kaldır</button>";
            echo "</form>";
        }
        
    } else if ($hasTransactionType && !$hasType) {
        echo "<div class='success'>✅ Tablo yapısı doğru! Yalnızca transaction_type sütunu mevcut</div>";
    }
    
    // 5. Final durum
    echo "<h2>4. Final Tablo Yapısı</h2>";
    $finalStructure = $pdo->query("DESCRIBE credit_transactions")->fetchAll();
    
    echo "<table>";
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
    
    // 6. Test sorgusu
    echo "<h2>5. Test Sorgusu</h2>";
    $testQuery = "SELECT transaction_type, COUNT(*) as count FROM credit_transactions GROUP BY transaction_type";
    $testResults = $pdo->query($testQuery)->fetchAll();
    
    echo "<div class='info'>📊 transaction_type dağılımı:</div>";
    echo "<table>";
    echo "<tr><th>Transaction Type</th><th>Kayıt Sayısı</th></tr>";
    foreach ($testResults as $result) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($result['transaction_type']) . "</td>";
        echo "<td>" . htmlspecialchars($result['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<a href='transactions.php' class='btn'>🔄 Transactions Sayfasına Git</a>";
echo "<a href='transactions-debug.php' class='btn'>🔍 Debug Sayfasına Git</a>";
echo "<a href='index.php' class='btn'>🏠 Dashboard'a Git</a>";
echo "</body></html>";
?>