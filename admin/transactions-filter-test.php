<?php
/**
 * Transactions Filtre Testi
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Transactions Filtre Testi</h1>";

// Test parametreleri
$test_filters = [
    'all' => 'Tüm İşlemler',
    'pending' => 'Bekleyen Dosyalar',
    'completed' => 'Tamamlanan Dosyalar',
    'processing' => 'İşlenen Dosyalar',
    'rejected' => 'Reddedilen Dosyalar'
];

foreach ($test_filters as $filter => $filter_name) {
    echo "<h2>$filter_name (filter=$filter)</h2>";
    
    try {
        // Base query
        $base_query = "
            SELECT 
                'credit' as type,
                id,
                user_id,
                amount as transaction_amount,
                transaction_type as action_type,
                description,
                reference_id,
                reference_type,
                created_at,
                NULL as admin_id
            FROM user_credits 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            
            UNION ALL
            
            SELECT 
                'file' as type,
                id,
                user_id,
                0 as transaction_amount,
                status as action_type,
                CONCAT('Dosya: ', original_name) as description,
                id as reference_id,
                'file_upload' as reference_type,
                upload_date as created_at,
                NULL as admin_id
            FROM file_uploads 
            WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        ";
        
        $params = [];
        
        if ($filter !== 'all') {
            $final_query = "
                SELECT * FROM (
                    $base_query
                ) as combined_transactions
                WHERE action_type = ?
                ORDER BY created_at DESC
                LIMIT 10
            ";
            $params[] = $filter;
            
            $stmt = $pdo->prepare($final_query);
            $stmt->execute($params);
        } else {
            $final_query = "
                SELECT * FROM (
                    $base_query
                ) as combined_transactions
                ORDER BY created_at DESC
                LIMIT 10
            ";
            
            $stmt = $pdo->query($final_query);
        }
        
        $results = $stmt->fetchAll();
        
        echo "<p>Bulunan kayıt sayısı: <strong>" . count($results) . "</strong></p>";
        
        if (count($results) > 0) {
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>Type</th><th>Action Type</th><th>Description</th><th>Date</th></tr>";
            foreach ($results as $result) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($result['type']) . "</td>";
                echo "<td>" . htmlspecialchars($result['action_type']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($result['description'], 0, 50)) . "</td>";
                echo "<td>" . htmlspecialchars($result['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'>Bu filtre için kayıt bulunamadı.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Veritabanındaki mevcut action_type'ları kontrol et
echo "<h2>Mevcut Action Types</h2>";

try {
    // user_credits tablosundaki transaction_type'lar
    echo "<h3>User Credits Transaction Types:</h3>";
    $stmt = $pdo->query("SELECT DISTINCT transaction_type FROM user_credits ORDER BY transaction_type");
    $credit_types = $stmt->fetchAll();
    echo "<ul>";
    foreach ($credit_types as $type) {
        echo "<li>" . htmlspecialchars($type['transaction_type']) . "</li>";
    }
    echo "</ul>";
    
    // file_uploads tablosundaki status'lar
    echo "<h3>File Upload Statuses:</h3>";
    $stmt = $pdo->query("SELECT DISTINCT status FROM file_uploads ORDER BY status");
    $file_statuses = $stmt->fetchAll();
    echo "<ul>";
    foreach ($file_statuses as $status) {
        echo "<li>" . htmlspecialchars($status['status']) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<h2>Test URL'leri:</h2>";
echo "<p><a href='transactions.php'>transactions.php (tümü)</a></p>";
echo "<p><a href='transactions.php?filter=pending'>transactions.php?filter=pending</a></p>";
echo "<p><a href='transactions.php?filter=completed'>transactions.php?filter=completed</a></p>";
echo "<p><a href='transactions.php?date=" . date('Y-m-d') . "'>transactions.php?date=" . date('Y-m-d') . "</a></p>";

echo "<br><br>";
echo "<a href='transactions.php'>← Transactions sayfasına dön</a>";
?>
