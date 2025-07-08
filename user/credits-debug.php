<?php
/**
 * Credits Sayfası İşlem Geçmişi Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

$userId = $_SESSION['user_id'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Credits Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🐛 Credits İşlem Geçmişi Debug</h1>";
echo "<div class='info'>👤 Kullanıcı ID: $userId</div>";

// Filtreleme parametreleri
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

echo "<div class='info'>🔍 Filtre Parametreleri:</div>";
echo "<ul>";
echo "<li>Type: " . ($type ? htmlspecialchars($type) : 'YOK') . "</li>";
echo "<li>Date From: " . ($dateFrom ? htmlspecialchars($dateFrom) : 'YOK') . "</li>";
echo "<li>Date To: " . ($dateTo ? htmlspecialchars($dateTo) : 'YOK') . "</li>";
echo "</ul>";

try {
    // 1. Önce basit sorgu ile veri var mı kontrol et
    echo "<h2>1. Basit Veri Kontrolü</h2>";
    
    $simpleStmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
    $simpleStmt->execute([$userId]);
    $totalUserTransactions = $simpleStmt->fetchColumn();
    
    echo "<div class='info'>📊 Bu kullanıcının toplam işlem sayısı: $totalUserTransactions</div>";
    
    if ($totalUserTransactions == 0) {
        echo "<div class='error'>❌ Bu kullanıcının hiç kredi işlemi yok!</div>";
        
        // Tüm işlemleri kontrol et
        $allStmt = $pdo->query("SELECT COUNT(*) FROM credit_transactions");
        $allCount = $allStmt->fetchColumn();
        echo "<div class='info'>📊 Sistemdeki toplam işlem sayısı: $allCount</div>";
        
        if ($allCount > 0) {
            echo "<div class='info'>Son 3 işlem (diğer kullanıcılardan):</div>";
            $sampleStmt = $pdo->query("SELECT id, user_id, amount, description, created_at FROM credit_transactions ORDER BY created_at DESC LIMIT 3");
            $samples = $sampleStmt->fetchAll();
            
            echo "<table>";
            echo "<tr><th>ID</th><th>User ID</th><th>Amount</th><th>Description</th><th>Date</th></tr>";
            foreach ($samples as $sample) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars(substr($sample['id'], 0, 8)) . "...</td>";
                echo "<td>" . htmlspecialchars(substr($sample['user_id'], 0, 8)) . "...</td>";
                echo "<td>" . htmlspecialchars($sample['amount']) . "</td>";
                echo "<td>" . htmlspecialchars($sample['description']) . "</td>";
                echo "<td>" . htmlspecialchars($sample['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "</body></html>";
        exit;
    }
    
    // 2. Filtreleme sorgusunu test et
    echo "<h2>2. Filtreleme Sorgusu Test</h2>";
    
    $whereClause = 'WHERE ct.user_id = ?';
    $params = [$userId];
    
    if ($type) {
        $whereClause .= ' AND COALESCE(ct.transaction_type, ct.type) = ?';
        $params[] = $type;
    }
    
    if ($dateFrom) {
        $whereClause .= ' AND DATE(ct.created_at) >= ?';
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereClause .= ' AND DATE(ct.created_at) <= ?';
        $params[] = $dateTo;
    }
    
    echo "<div class='info'>📝 WHERE Clause: $whereClause</div>";
    echo "<div class='info'>📋 Parameters: " . implode(', ', $params) . "</div>";
    
    $testQuery = "
        SELECT ct.*, u.username as admin_username,
               COALESCE(ct.transaction_type, ct.type) as effective_type
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        {$whereClause}
        ORDER BY ct.created_at DESC
        LIMIT 10
    ";
    
    echo "<h3>Test Sorgusu:</h3>";
    echo "<pre>" . htmlspecialchars($testQuery) . "</pre>";
    
    $testStmt = $pdo->prepare($testQuery);
    $testStmt->execute($params);
    $testResults = $testStmt->fetchAll();
    
    echo "<div class='success'>✅ Sorgu başarıyla çalıştı</div>";
    echo "<div class='info'>📊 Bulunan kayıt sayısı: " . count($testResults) . "</div>";
    
    if (!empty($testResults)) {
        echo "<h3>Sonuçlar:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Transaction Type</th><th>Type</th><th>Effective Type</th><th>Amount</th><th>Description</th><th>Date</th></tr>";
        foreach ($testResults as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($row['id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['transaction_type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['effective_type'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Hiç sonuç bulunamadı!</div>";
        
        // Filtresiz test yapalım
        echo "<h3>Filtresiz Test:</h3>";
        $noFilterQuery = "
            SELECT ct.*, COALESCE(ct.transaction_type, ct.type) as effective_type
            FROM credit_transactions ct
            WHERE ct.user_id = ?
            ORDER BY ct.created_at DESC
            LIMIT 5
        ";
        
        $noFilterStmt = $pdo->prepare($noFilterQuery);
        $noFilterStmt->execute([$userId]);
        $noFilterResults = $noFilterStmt->fetchAll();
        
        echo "<div class='info'>📊 Filtresiz bulunan kayıt sayısı: " . count($noFilterResults) . "</div>";
        
        if (!empty($noFilterResults)) {
            echo "<table>";
            echo "<tr><th>Transaction Type</th><th>Type</th><th>Effective Type</th><th>Amount</th><th>Date</th></tr>";
            foreach ($noFilterResults as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['transaction_type'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['type'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['effective_type'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // 3. Eski sorguyu test et (değişiklikten önceki)
    echo "<h2>3. Eski Sorgu Test (Değişiklikten Önceki)</h2>";
    
    $oldQuery = "
        SELECT ct.*, u.username as admin_username 
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        WHERE ct.user_id = ?
        ORDER BY ct.created_at DESC
        LIMIT 10
    ";
    
    $oldStmt = $pdo->prepare($oldQuery);
    $oldStmt->execute([$userId]);
    $oldResults = $oldStmt->fetchAll();
    
    echo "<div class='info'>📊 Eski sorgu ile bulunan kayıt sayısı: " . count($oldResults) . "</div>";
    
    if (!empty($oldResults)) {
        echo "<div class='success'>✅ Eski sorgu çalışıyor! Sorun filtreleme sisteminde.</div>";
    } else {
        echo "<div class='error'>❌ Eski sorgu da çalışmıyor! Veri problemi var.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<a href='credits.php'>🔄 Credits Sayfasına Git</a> | ";
echo "<a href='transactions.php'>📊 Transactions Sayfasına Git</a>";
echo "</body></html>";
?>