<?php
/**
 * Revize işlemi hata debug scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Revize İşlemi Debug</h1>";

// 1. Veritabanı bağlantı kontrolü
try {
    echo "<h2>1. Database Connection Check</h2>";
    if ($pdo) {
        echo "✅ PDO bağlantısı başarılı<br>";
        echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "<br>";
    } else {
        echo "❌ PDO bağlantısı başarısız<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Revisions tablosu kontrolü
echo "<h2>2. Revisions Table Check</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'revisions'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Revisions tablosu mevcut<br>";
        
        // Tablo yapısını kontrol et
        $columns = $pdo->query("DESCRIBE revisions")->fetchAll();
        echo "<h3>Tablo Yapısı:</h3>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        // Örnek veri kontrolü
        $count = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
        echo "<br>Toplam kayıt: " . $count . "<br>";
        
    } else {
        echo "❌ Revisions tablosu bulunamadı<br>";
    }
} catch (Exception $e) {
    echo "❌ Revisions table error: " . $e->getMessage() . "<br>";
}

// 3. Session kontrolü
echo "<h2>3. Session Check</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    echo "✅ User ID session: " . $_SESSION['user_id'] . "<br>";
    echo "UUID Valid: " . (isValidUUID($_SESSION['user_id']) ? 'Yes' : 'No') . "<br>";
    echo "Admin: " . (isAdmin() ? 'Yes' : 'No') . "<br>";
} else {
    echo "❌ User ID session yok<br>";
}

// 4. FileManager test
echo "<h2>4. FileManager Test</h2>";
try {
    $fileManager = new FileManager($pdo);
    echo "✅ FileManager instance oluşturuldu<br>";
    
    // Bir test revize getir
    $revisions = $fileManager->getAllRevisions(1, 5);
    echo "Revisions count: " . count($revisions) . "<br>";
    
    if (count($revisions) > 0) {
        $testRevision = $revisions[0];
        echo "<h3>Test Revision:</h3>";
        echo "ID: " . $testRevision['id'] . "<br>";
        echo "Status: " . $testRevision['status'] . "<br>";
        echo "User ID: " . $testRevision['user_id'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ FileManager error: " . $e->getMessage() . "<br>";
}

// 5. User class test
echo "<h2>5. User Class Test</h2>";
try {
    $user = new User($pdo);
    echo "✅ User instance oluşturuldu<br>";
    
    if (isset($_SESSION['user_id'])) {
        $userInfo = $user->getUserById($_SESSION['user_id']);
        if ($userInfo) {
            echo "User found: " . $userInfo['username'] . "<br>";
            echo "Credits: " . $userInfo['credits'] . "<br>";
        } else {
            echo "❌ User not found<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ User class error: " . $e->getMessage() . "<br>";
}

// 6. Test updateRevisionStatus method
echo "<h2>6. Test UpdateRevisionStatus</h2>";
try {
    if (isset($_SESSION['user_id']) && count($revisions) > 0) {
        $testRevisionId = $revisions[0]['id'];
        
        echo "Test Revision ID: " . $testRevisionId . "<br>";
        echo "Admin ID: " . $_SESSION['user_id'] . "<br>";
        
        // Test the method with safe parameters (no credit charge)
        $result = $fileManager->updateRevisionStatus(
            $testRevisionId, 
            $_SESSION['user_id'], 
            'in_progress', 
            'Test güncelleme - debug', 
            0
        );
        
        echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "<br>";
        echo "Message: " . $result['message'] . "<br>";
        
    } else {
        echo "Test için uygun veri bulunamadı<br>";
    }
    
} catch (Exception $e) {
    echo "❌ UpdateRevisionStatus error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

?>
