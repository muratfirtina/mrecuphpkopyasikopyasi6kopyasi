<?php
/**
 * Debug Data - Veri sorunlarını tespit etmek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

echo "<h1>Debug - Veri Analizi</h1>";
echo "<hr>";

// 1. Database bağlantısını test et
echo "<h2>1. Database Bağlantısı</h2>";
try {
    $test = $pdo->query("SELECT 1");
    echo "<div style='color: green;'>✅ Database bağlantısı başarılı</div>";
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ Database hatası: " . $e->getMessage() . "</div>";
}

// 2. Tablolar var mı?
echo "<h2>2. Tablo Kontrolü</h2>";
$tables = ['users', 'file_uploads', 'brands', 'models', 'categories', 'products'];
foreach($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<div style='color: green;'>✅ $table: $count kayıt</div>";
    } catch(Exception $e) {
        echo "<div style='color: red;'>❌ $table tablosu hatası: " . $e->getMessage() . "</div>";
    }
}

// 3. User sınıfını test et
echo "<h2>3. User Sınıfı Test</h2>";
try {
    $user = new User($pdo);
    $userCount = $user->getUserCount();
    echo "<div style='color: green;'>✅ User->getUserCount(): $userCount</div>";
    
    $allUsers = $user->getAllUsers(1, 5);
    echo "<div style='color: green;'>✅ User->getAllUsers(): " . count($allUsers) . " kullanıcı döndü</div>";
    
    if (!empty($allUsers)) {
        echo "<h4>İlk 3 kullanıcı:</h4>";
        echo "<pre>";
        print_r(array_slice($allUsers, 0, 3));
        echo "</pre>";
    }
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ User sınıfı hatası: " . $e->getMessage() . "</div>";
}

// 4. FileManager sınıfını test et
echo "<h2>4. FileManager Sınıfı Test</h2>";
try {
    $fileManager = new FileManager($pdo);
    $allUploads = $fileManager->getAllUploads(1, 5);
    echo "<div style='color: green;'>✅ FileManager->getAllUploads(): " . count($allUploads) . " dosya döndü</div>";
    
    if (!empty($allUploads)) {
        echo "<h4>İlk 3 dosya:</h4>";
        echo "<pre>";
        print_r(array_slice($allUploads, 0, 3));
        echo "</pre>";
    }
    
    $fileStats = $fileManager->getFileStats();
    echo "<div style='color: green;'>✅ FileManager->getFileStats():</div>";
    echo "<pre>";
    print_r($fileStats);
    echo "</pre>";
    
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ FileManager sınıfı hatası: " . $e->getMessage() . "</div>";
}

// 5. Manuel SQL testleri
echo "<h2>5. Manuel SQL Testleri</h2>";

// Users tablosu
try {
    $stmt = $pdo->query("SELECT id, username, email, role, status FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<div style='color: green;'>✅ Manuel users sorgusu: " . count($users) . " kullanıcı</div>";
    if (!empty($users)) {
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    }
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ Manuel users sorgusu hatası: " . $e->getMessage() . "</div>";
}

// File uploads tablosu
try {
    $stmt = $pdo->query("SELECT id, user_id, original_name, status, upload_date FROM file_uploads LIMIT 5");
    $uploads = $stmt->fetchAll();
    echo "<div style='color: green;'>✅ Manuel file_uploads sorgusu: " . count($uploads) . " dosya</div>";
    if (!empty($uploads)) {
        echo "<pre>";
        print_r($uploads);
        echo "</pre>";
    }
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ Manuel file_uploads sorgusu hatası: " . $e->getMessage() . "</div>";
}

// 6. Session bilgileri
echo "<h2>6. Session Bilgileri</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 7. isAdmin() fonksiyonu test
echo "<h2>7. Admin Kontrolü</h2>";
echo "<div>isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "</div>";
echo "<div>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</div>";

// 8. Specific users.php sorgusu test
echo "<h2>8. users.php Benzeri Sorgu Test</h2>";
try {
    $whereClause = "WHERE deleted_at IS NULL";
    $params = [];
    $limit = 5;
    $offset = 0;
    
    // Toplam kullanıcı sayısı
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalUsers = $stmt->fetchColumn();
    echo "<div style='color: green;'>✅ users.php benzeri count sorgusu: $totalUsers kullanıcı</div>";
    
    // Kullanıcıları getir
    $query = "
        SELECT id, username, email, first_name, last_name, phone, role, credits, 
               is_active, email_verified, created_at, last_login
        FROM users 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $users = $stmt->fetchAll();
    echo "<div style='color: green;'>✅ users.php benzeri select sorgusu: " . count($users) . " kullanıcı döndü</div>";
    
    if (!empty($users)) {
        echo "<h4>İlk 3 kullanıcı (users.php benzeri):</h4>";
        echo "<pre>";
        print_r(array_slice($users, 0, 3));
        echo "</pre>";
    }
    
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ users.php benzeri sorgu hatası: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>Admin Ana Sayfa</a> | <a href='users.php'>Kullanıcılar</a> | <a href='uploads.php'>Yüklemeler</a></p>";
?>
