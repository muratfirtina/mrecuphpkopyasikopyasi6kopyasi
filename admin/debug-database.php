<?php
/**
 * Debug: Veritabanı Tablo ve Veri Kontrolü
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Veritabanı Debug Kontrolü</h1>";

try {
    // 1. Tabloları listele
    echo "<div class='section'>";
    echo "<h2>📋 Mevcut Tablolar</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li class='success'>✅ $table</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // 2. Users tablosu kontrol
    echo "<div class='section'>";
    echo "<h2>👥 Users Tablosu</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $userCount = $stmt->fetch()['total'];
    echo "<p><strong>Toplam kullanıcı:</strong> $userCount</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetch()['admin_count'];
    echo "<p><strong>Admin sayısı:</strong> $adminCount</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users WHERE role = 'user'");
    $normalUserCount = $stmt->fetch()['user_count'];
    echo "<p><strong>Normal kullanıcı sayısı:</strong> $normalUserCount</p>";
    
    // Son kullanıcıları göster
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll();
    
    if ($recentUsers) {
        echo "<h3>Son 5 Kullanıcı:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created At</th></tr>";
        foreach ($recentUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 3. File uploads tablosu kontrol
    echo "<div class='section'>";
    echo "<h2>📁 File Uploads Tablosu</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM file_uploads");
    $fileCount = $stmt->fetch()['total'];
    echo "<p><strong>Toplam dosya:</strong> $fileCount</p>";
    
    if ($fileCount > 0) {
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM file_uploads GROUP BY status");
        $statusCounts = $stmt->fetchAll();
        
        echo "<h3>Dosya Durumları:</h3>";
        echo "<table>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($statusCounts as $status) {
            echo "<tr><td>{$status['status']}</td><td>{$status['count']}</td></tr>";
        }
        echo "</table>";
        
        // Son dosyaları göster
        $stmt = $pdo->query("SELECT id, user_id, original_name, status, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 5");
        $recentFiles = $stmt->fetchAll();
        
        echo "<h3>Son 5 Dosya:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>File Name</th><th>Status</th><th>Upload Date</th></tr>";
        foreach ($recentFiles as $file) {
            echo "<tr>";
            echo "<td>{$file['id']}</td>";
            echo "<td>{$file['user_id']}</td>";
            echo "<td>{$file['original_name']}</td>";
            echo "<td>{$file['status']}</td>";
            echo "<td>{$file['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 4. User credits tablosu kontrol
    echo "<div class='section'>";
    echo "<h2>💰 User Credits Tablosu</h2>";
    
    // Tablo var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_credits'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_credits");
        $creditCount = $stmt->fetch()['total'];
        echo "<p><strong>Toplam kredi işlemi:</strong> $creditCount</p>";
        
        if ($creditCount > 0) {
            $stmt = $pdo->query("SELECT transaction_type, COUNT(*) as count, SUM(amount) as total_amount FROM user_credits GROUP BY transaction_type");
            $creditTypes = $stmt->fetchAll();
            
            echo "<h3>Kredi İşlem Türleri:</h3>";
            echo "<table>";
            echo "<tr><th>Transaction Type</th><th>Count</th><th>Total Amount</th></tr>";
            foreach ($creditTypes as $type) {
                echo "<tr>";
                echo "<td>{$type['transaction_type']}</td>";
                echo "<td>{$type['count']}</td>";
                echo "<td>{$type['total_amount']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>❌ user_credits tablosu bulunamadı!</p>";
        
        // Users tablosunda credits sütunu var mı kontrol et
        $stmt = $pdo->query("DESCRIBE users");
        $userColumns = $stmt->fetchAll();
        
        echo "<h3>Users Tablosu Sütunları:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($userColumns as $column) {
            $highlight = $column['Field'] === 'credits' ? 'style="background: yellow;"' : '';
            echo "<tr $highlight>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Users tablosunda credits toplamı
        $stmt = $pdo->query("SELECT SUM(credits) as total_credits FROM users WHERE role = 'user'");
        $totalCredits = $stmt->fetch()['total_credits'];
        echo "<p><strong>Users tablosundaki toplam kredi:</strong> $totalCredits</p>";
    }
    echo "</div>";
    
    // 5. Brands tablosu kontrol
    echo "<div class='section'>";
    echo "<h2>🚗 Brands Tablosu</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM brands");
    $brandCount = $stmt->fetch()['total'];
    echo "<p><strong>Toplam marka:</strong> $brandCount</p>";
    
    if ($brandCount > 0) {
        $stmt = $pdo->query("SELECT id, name FROM brands LIMIT 10");
        $brands = $stmt->fetchAll();
        
        echo "<h3>İlk 10 Marka:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th></tr>";
        foreach ($brands as $brand) {
            echo "<tr><td>{$brand['id']}</td><td>{$brand['name']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // 6. Güvenlik tabloları kontrol
    echo "<div class='section'>";
    echo "<h2>🛡️ Güvenlik Tabloları</h2>";
    
    $security_tables = ['security_logs', 'ip_security', 'failed_logins', 'csrf_tokens', 'rate_limits', 'security_config', 'file_security_scans', 'waf_rules'];
    
    foreach ($security_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p class='success'>✅ $table: $count kayıt</p>";
        } else {
            echo "<p class='error'>❌ $table tablosu yok</p>";
        }
    }
    echo "</div>";
    
    // 7. Test sorguları (reports.php'den)
    echo "<div class='section'>";
    echo "<h2>🧪 Test Sorguları</h2>";
    
    echo "<h3>1. User Stats Query:</h3>";
    try {
        $user_stats_query = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_new_users,
                SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_active_users,
                SUM(credits) as total_credits
            FROM users 
            WHERE role = 'user'
        ";
        $stmt = $pdo->query($user_stats_query);
        $user_stats = $stmt->fetch();
        
        echo "<table>";
        echo "<tr><th>Metric</th><th>Value</th></tr>";
        echo "<tr><td>Total Users</td><td>{$user_stats['total_users']}</td></tr>";
        echo "<tr><td>Monthly New Users</td><td>{$user_stats['monthly_new_users']}</td></tr>";
        echo "<tr><td>Weekly Active Users</td><td>{$user_stats['weekly_active_users']}</td></tr>";
        echo "<tr><td>Total Credits</td><td>{$user_stats['total_credits']}</td></tr>";
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>2. File Stats Query:</h3>";
    try {
        $file_stats_query = "
            SELECT 
                COUNT(*) as total_files,
                SUM(CASE WHEN upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_uploads,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_files,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_files,
                AVG(file_size) as avg_file_size
            FROM file_uploads
        ";
        $stmt = $pdo->query($file_stats_query);
        $file_stats = $stmt->fetch();
        
        echo "<table>";
        echo "<tr><th>Metric</th><th>Value</th></tr>";
        echo "<tr><td>Total Files</td><td>{$file_stats['total_files']}</td></tr>";
        echo "<tr><td>Monthly Uploads</td><td>{$file_stats['monthly_uploads']}</td></tr>";
        echo "<tr><td>Completed Files</td><td>{$file_stats['completed_files']}</td></tr>";
        echo "<tr><td>Pending Files</td><td>{$file_stats['pending_files']}</td></tr>";
        echo "<tr><td>Avg File Size</td><td>" . number_format((float)($file_stats['avg_file_size'] ?? 0)) . " bytes</td></tr>";
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Veritabanı Bağlantı Hatası</h2>";
    echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><br>";
echo "<p><a href='reports.php'>← Reports sayfasına geri dön</a></p>";
echo "</body></html>";
?>
