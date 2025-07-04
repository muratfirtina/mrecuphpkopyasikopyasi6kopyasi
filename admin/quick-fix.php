<?php
/**
 * Hızlı Hata Düzeltme ve Test
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Hızlı Hata Düzeltme ve Test</h1>";

// 1. Veritabanı bağlantısını test et
echo "<h2>1. Veritabanı Bağlantısı</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green;'>✅ Veritabanı bağlantısı başarılı</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Veritabanı hatası: " . $e->getMessage() . "</p>";
    exit;
}

// 2. Users tablosunu kontrol et
echo "<h2>2. Users Tablosu Kontrolü</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>👥 Toplam kullanıcı sayısı: <strong>$userCount</strong></p>";
    
    // Örnek kullanıcıları listele
    $stmt = $pdo->query("SELECT username, email FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    echo "<p>🔍 İlk 5 kullanıcı:</p><ul>";
    foreach ($users as $user) {
        echo "<li>" . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Users tablosu hatası: " . $e->getMessage() . "</p>";
}

// 3. 'murat' kullanıcısını ara
echo "<h2>3. 'Murat' Kullanıcı Araması</h2>";
try {
    // Farklı arama yöntemleri dene
    $searches = [
        "username = 'murat'" => "SELECT * FROM users WHERE username = 'murat'",
        "username LIKE '%murat%'" => "SELECT * FROM users WHERE username LIKE '%murat%'",
        "email LIKE '%murat%'" => "SELECT * FROM users WHERE email LIKE '%murat%'",
        "first_name LIKE '%murat%'" => "SELECT * FROM users WHERE first_name LIKE '%murat%'",
        "Genel arama" => "SELECT * FROM users WHERE username LIKE '%murat%' OR email LIKE '%murat%' OR first_name LIKE '%murat%' OR last_name LIKE '%murat%'"
    ];
    
    foreach ($searches as $desc => $sql) {
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        echo "<p><strong>$desc:</strong> " . count($results) . " sonuç</p>";
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                echo "<p style='margin-left: 20px;'>🎯 Bulundu: " . 
                     htmlspecialchars($result['username']) . " (" . 
                     htmlspecialchars($result['email']) . ")</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Arama hatası: " . $e->getMessage() . "</p>";
}

// 4. Transactions tablosunu kontrol et
echo "<h2>4. Transactions/Credits Kontrolü</h2>";
try {
    // user_credits tablosu var mı?
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_credits'");
    if ($stmt->fetch()) {
        echo "<p>✅ user_credits tablosu mevcut</p>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_credits");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 user_credits kayıt sayısı: $count</p>";
    } else {
        echo "<p>❌ user_credits tablosu bulunamadı</p>";
    }
    
    // file_uploads tablosu var mı?
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_uploads'");
    if ($stmt->fetch()) {
        echo "<p>✅ file_uploads tablosu mevcut</p>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
        $count = $stmt->fetch()['count'];
        echo "<p>📊 file_uploads kayıt sayısı: $count</p>";
    } else {
        echo "<p>❌ file_uploads tablosu bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Tablo kontrolü hatası: " . $e->getMessage() . "</p>";
}

// 5. Error log kontrolü
echo "<h2>5. Error Log Durumu</h2>";
echo "<p>PHP error_reporting: " . error_reporting() . "</p>";
echo "<p>Display errors: " . (ini_get('display_errors') ? 'Açık' : 'Kapalı') . "</p>";

// 6. Hızlı çözümler
echo "<h2>6. Hızlı Çözümler</h2>";
echo "<div style='background:#f0f0f0; padding:10px; margin:10px 0;'>";
echo "<h3>Eğer 'murat' kullanıcısı bulunamadıysa:</h3>";
echo "<p>1. Veritabanında gerçekten 'murat' kullanıcısı var mı kontrol edin</p>";
echo "<p>2. Kullanıcı adı başka türlü yazılmış olabilir (Murat, MURAT, vb.)</p>";
echo "<p>3. Email adresi 'murat' içeriyor olabilir</p>";

echo "<h3>Hataları tamamen gizlemek için (geçici çözüm):</h3>";
echo "<code>error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);</code>";
echo "<p>Bu kodu config.php'nin başına ekleyebilirsiniz</p>";

echo "<h3>Transactions hatası devam ediyorsa:</h3>";
echo "<p>transactions.php dosyasındaki 210. satırı kontrol edin</p>";
echo "</div>";

echo "<br><hr><br>";
echo "<p><a href='transactions.php'>🔄 Transactions sayfasını test et</a></p>";
echo "<p><a href='users.php?search=murat'>🔍 Users arama testi</a></p>";
echo "<p><a href='test-search.php'>🧪 Detaylı arama testi</a></p>";
?>
