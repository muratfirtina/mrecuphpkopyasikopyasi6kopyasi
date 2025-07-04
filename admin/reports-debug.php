<?php
/**
 * Mr ECU - Admin Raporlar (Debug Versiyonu)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Reports Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .query { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>";

echo "<h1>🔍 Reports Debug</h1>";

try {
    echo "<h2>1. User İstatistikleri</h2>";
    
    // Basit user count
    echo "<div class='query'>SELECT COUNT(*) as total FROM users</div>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];
    echo "<div class='success'>Toplam kullanıcı: $total_users</div>";
    
    // Role'e göre
    echo "<div class='query'>SELECT role, COUNT(*) as count FROM users GROUP BY role</div>";
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $role_counts = $stmt->fetchAll();
    foreach ($role_counts as $role) {
        echo "<div class='success'>{$role['role']}: {$role['count']}</div>";
    }
    
    // Credits toplamı (users tablosundan)
    echo "<div class='query'>SELECT SUM(credits) as total FROM users WHERE role = 'user'</div>";
    try {
        $stmt = $pdo->query("SELECT SUM(credits) as total FROM users WHERE role = 'user'");
        $total_credits = $stmt->fetch()['total'];
        echo "<div class='success'>Toplam krediler: $total_credits</div>";
    } catch (Exception $e) {
        echo "<div class='error'>Credits sütunu hatası: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>2. Dosya İstatistikleri</h2>";
    
    // Toplam dosya
    echo "<div class='query'>SELECT COUNT(*) as total FROM file_uploads</div>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM file_uploads");
    $total_files = $stmt->fetch()['total'];
    echo "<div class='success'>Toplam dosya: $total_files</div>";
    
    if ($total_files > 0) {
        // Status'a göre
        echo "<div class='query'>SELECT status, COUNT(*) as count FROM file_uploads GROUP BY status</div>";
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM file_uploads GROUP BY status");
        $status_counts = $stmt->fetchAll();
        foreach ($status_counts as $status) {
            echo "<div class='success'>{$status['status']}: {$status['count']}</div>";
        }
        
        // Son 30 gün
        echo "<div class='query'>SELECT COUNT(*) as count FROM file_uploads WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)</div>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $monthly_uploads = $stmt->fetch()['count'];
            echo "<div class='success'>Son 30 günde yüklenen: $monthly_uploads</div>";
        } catch (Exception $e) {
            echo "<div class='error'>Tarih sorgusu hatası: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>3. Marka İstatistikleri</h2>";
    
    // Markalar
    echo "<div class='query'>SELECT COUNT(*) as total FROM brands</div>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM brands");
    $total_brands = $stmt->fetch()['total'];
    echo "<div class='success'>Toplam marka: $total_brands</div>";
    
    if ($total_brands > 0 && $total_files > 0) {
        echo "<div class='query'>
        SELECT b.name, COUNT(fu.id) as upload_count 
        FROM brands b 
        LEFT JOIN file_uploads fu ON b.id = fu.brand_id 
        GROUP BY b.id, b.name 
        HAVING upload_count > 0 
        ORDER BY upload_count DESC 
        LIMIT 5
        </div>";
        
        try {
            $stmt = $pdo->query("
                SELECT b.name, COUNT(fu.id) as upload_count 
                FROM brands b 
                LEFT JOIN file_uploads fu ON b.id = fu.brand_id 
                GROUP BY b.id, b.name 
                HAVING upload_count > 0 
                ORDER BY upload_count DESC 
                LIMIT 5
            ");
            $popular_brands = $stmt->fetchAll();
            
            echo "<div class='success'>Popüler markalar:</div>";
            foreach ($popular_brands as $brand) {
                echo "<div class='success'>- {$brand['name']}: {$brand['upload_count']} dosya</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Marka sorgusu hatası: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>4. Günlük Aktivite</h2>";
    
    if ($total_files > 0) {
        echo "<div class='query'>
        SELECT DATE(upload_date) as date, COUNT(*) as count 
        FROM file_uploads 
        WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(upload_date) 
        ORDER BY date DESC
        </div>";
        
        try {
            $stmt = $pdo->query("
                SELECT DATE(upload_date) as date, COUNT(*) as count 
                FROM file_uploads 
                WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY DATE(upload_date) 
                ORDER BY date DESC
            ");
            $daily_activity = $stmt->fetchAll();
            
            echo "<div class='success'>Son 7 günlük aktivite:</div>";
            foreach ($daily_activity as $day) {
                echo "<div class='success'>- {$day['date']}: {$day['count']} dosya</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Günlük aktivite sorgusu hatası: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>5. User Credits Tablosu Kontrolü</h2>";
    
    // user_credits tablosu var mı?
    echo "<div class='query'>SHOW TABLES LIKE 'user_credits'</div>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_credits'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>user_credits tablosu mevcut</div>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_credits");
        $credit_transactions = $stmt->fetch()['total'];
        echo "<div class='success'>Toplam kredi işlemi: $credit_transactions</div>";
        
        if ($credit_transactions > 0) {
            $stmt = $pdo->query("SELECT transaction_type, COUNT(*) as count, SUM(amount) as total FROM user_credits GROUP BY transaction_type");
            $credit_types = $stmt->fetchAll();
            
            foreach ($credit_types as $type) {
                echo "<div class='success'>- {$type['transaction_type']}: {$type['count']} işlem, {$type['total']} toplam</div>";
            }
        }
    } else {
        echo "<div class='error'>user_credits tablosu yok - krediler users tablosunda tutuluyor</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Genel hata: " . $e->getMessage() . "</div>";
}

echo "<br><br>";
echo "<p><a href='reports.php'>← Reports sayfasına geri dön</a></p>";
echo "<p><a href='debug-database.php'>📋 Detaylı database debug</a></p>";
echo "</body></html>";
?>
