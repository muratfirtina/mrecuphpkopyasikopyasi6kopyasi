<?php
/**
 * Upload Debug - Dosya yükleme kontrol debug
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h1>🔍 Upload Debug</h1>";

try {
    // 1. Veritabanı bağlantısı kontrolü
    echo "<h2>1. Veritabanı Bağlantısı:</h2>";
    if ($pdo) {
        echo "<p style='color:green;'>✅ PDO bağlantısı başarılı</p>";
    } else {
        echo "<p style='color:red;'>❌ PDO bağlantısı yok</p>";
        exit;
    }
    
    // 2. Tabloları kontrol et
    echo "<h2>2. Tablo Kontrolü:</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_uploads'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✅ file_uploads tablosu var</p>";
        
        // Tablodaki kayıt sayısı
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 Toplam file_uploads kayıtları: <strong>$count</strong></p>";
        
        // Son 5 kaydı göster
        $stmt = $pdo->query("SELECT id, user_id, original_name, status, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 5");
        $recent = $stmt->fetchAll();
        
        if (!empty($recent)) {
            echo "<h3>Son Yüklenen Dosyalar:</h3>";
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th></tr>";
            foreach ($recent as $r) {
                echo "<tr>";
                echo "<td>{$r['id']}</td>";
                echo "<td>{$r['user_id']}</td>";
                echo "<td>{$r['original_name']}</td>";
                echo "<td>{$r['status']}</td>";
                echo "<td>{$r['upload_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'>⚠️ Tablo boş</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ file_uploads tablosu yok</p>";
    }
    
    // 3. FileManager sınıfı kontrolü
    echo "<h2>3. FileManager Sınıfı:</h2>";
    if (class_exists('FileManager')) {
        echo "<p style='color:green;'>✅ FileManager sınıfı yüklenmiş</p>";
        
        $fileManager = new FileManager($pdo);
        echo "<p style='color:green;'>✅ FileManager instance oluşturuldu</p>";
        
        // getAllUploads metodunu test et
        echo "<h3>getAllUploads() Test:</h3>";
        $uploads = $fileManager->getAllUploads(1, 50);
        echo "<p>Dönen sonuç tipi: " . gettype($uploads) . "</p>";
        echo "<p>Sonuç sayısı: " . count($uploads) . "</p>";
        
        if (!empty($uploads)) {
            echo "<h4>İlk upload detayı:</h4>";
            echo "<pre>" . print_r($uploads[0], true) . "</pre>";
        } else {
            echo "<p style='color:red;'>❌ getAllUploads() boş array döndü</p>";
            
            // Direkt SQL ile kontrol et
            echo "<h4>Direkt SQL Kontrolü:</h4>";
            $stmt = $pdo->query("
                SELECT fu.*, u.username, u.email, b.name as brand_name, m.name as model_name
                FROM file_uploads fu
                LEFT JOIN users u ON fu.user_id = u.id
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                ORDER BY fu.upload_date DESC
                LIMIT 5
            ");
            $directResults = $stmt->fetchAll();
            
            if (!empty($directResults)) {
                echo "<p style='color:green;'>✅ Direkt SQL çalışıyor</p>";
                echo "<pre>" . print_r($directResults[0], true) . "</pre>";
            } else {
                echo "<p style='color:red;'>❌ Direkt SQL de boş</p>";
            }
        }
        
        // getFileStats metodunu test et
        echo "<h3>getFileStats() Test:</h3>";
        $stats = $fileManager->getFileStats();
        echo "<pre>" . print_r($stats, true) . "</pre>";
        
    } else {
        echo "<p style='color:red;'>❌ FileManager sınıfı yüklenemedi</p>";
    }
    
    // 4. User sınıfı kontrolü
    echo "<h2>4. User Sınıfı:</h2>";
    if (class_exists('User')) {
        echo "<p style='color:green;'>✅ User sınıfı yüklenmiş</p>";
        
        $user = new User($pdo);
        $userCount = $user->getUserCount();
        echo "<p>👥 Toplam kullanıcı sayısı: <strong>$userCount</strong></p>";
    } else {
        echo "<p style='color:red;'>❌ User sınıfı yüklenemedi</p>";
    }
    
    // 5. Session kontrolü
    echo "<h2>5. Session Kontrolü:</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color:green;'>✅ Kullanıcı giriş yapmış</p>";
        echo "<p>👤 User ID: {$_SESSION['user_id']}</p>";
        echo "<p>👤 Username: {$_SESSION['username']}</p>";
        echo "<p>🔑 Role: {$_SESSION['role']}</p>";
    } else {
        echo "<p style='color:red;'>❌ Kullanıcı giriş yapmamış</p>";
    }
    
    // 6. Joins kontrolü
    echo "<h2>6. Join Kontrolleri:</h2>";
    
    // Users tablosu
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>👥 Users tablosu: $userCount kayıt</p>";
    
    // Brands tablosu
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM brands");
    $brandCount = $stmt->fetch()['count'];
    echo "<p>🚗 Brands tablosu: $brandCount kayıt</p>";
    
    // Models tablosu
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM models");
    $modelCount = $stmt->fetch()['count'];
    echo "<p>🚙 Models tablosu: $modelCount kayıt</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><hr><br>";
echo "<a href='uploads.php'>📁 Uploads sayfasına git</a><br>";
echo "<a href='index.php'>🏠 Dashboard'a git</a>";
?>
