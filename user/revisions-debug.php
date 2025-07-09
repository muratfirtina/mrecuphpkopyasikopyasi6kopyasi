<?php
/**
 * Revize Sistemi Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

if (!isLoggedIn()) {
    die("Giriş yapmanız gerekiyor!");
}

echo "<h1>🔄 Revize Sistemi Debug</h1>";

$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

echo "<h2>1. Database Tabloları Kontrolü:</h2>";

// Revisions tablosu var mı?
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'revisions'");
    $revisionTableExists = $stmt->rowCount() > 0;
    
    if ($revisionTableExists) {
        echo "<p style='color:green;'>✅ Revisions tablosu var</p>";
        
        // Tablo yapısını kontrol et
        $stmt = $pdo->query("DESCRIBE revisions");
        $columns = $stmt->fetchAll();
        echo "<h3>Tablo Yapısı:</h3>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Revize sayısını kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
        $revisionCount = $stmt->fetch()['count'];
        echo "<p>📊 Toplam revize talebi sayısı: <strong>$revisionCount</strong></p>";
        
        if ($revisionCount > 0) {
            $stmt = $pdo->query("SELECT * FROM revisions ORDER BY requested_at DESC LIMIT 5");
            $revisions = $stmt->fetchAll();
            echo "<h3>Son 5 Revize Talebi:</h3>";
            echo "<pre>" . print_r($revisions, true) . "</pre>";
        }
        
    } else {
        echo "<p style='color:red;'>❌ Revisions tablosu YOK - Kurulum gerekli!</p>";
        echo "<p><a href='../install-revisions.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Revize Sistemini Kur</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>2. file_uploads Tablosu Kontrolü:</h2>";

try {
    // file_uploads tablosunda revision_count sütunu var mı?
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'revision_count'");
    $revisionCountExists = $stmt->rowCount() > 0;
    
    if ($revisionCountExists) {
        echo "<p style='color:green;'>✅ file_uploads.revision_count sütunu var</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ file_uploads.revision_count sütunu YOK</p>";
    }
    
    // Completed dosyalar var mı?
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM file_uploads WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedCount = $stmt->fetch()['count'];
    echo "<p>📁 Tamamlanmış dosya sayınız: <strong>$completedCount</strong></p>";
    
    if ($completedCount > 0) {
        $stmt = $pdo->prepare("SELECT id, original_name, upload_date FROM file_uploads WHERE user_id = ? AND status = 'completed' ORDER BY upload_date DESC");
        $stmt->execute([$userId]);
        $completedFiles = $stmt->fetchAll();
        
        echo "<h3>Tamamlanmış Dosyalarınız:</h3>";
        echo "<ul>";
        foreach ($completedFiles as $file) {
            echo "<li>#{$file['id']} - {$file['original_name']} ({$file['upload_date']})</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ file_uploads kontrolü hatası: " . $e->getMessage() . "</p>";
}

if ($revisionTableExists) {
    echo "<h2>3. FileManager Metodları Test:</h2>";
    
    // getUserRevisions metodunu test et
    echo "<h3>getUserRevisions() Test:</h3>";
    try {
        $userRevisions = $fileManager->getUserRevisions($userId, 1, 20);
        echo "<p>Kullanıcı revize sayısı: <strong>" . count($userRevisions) . "</strong></p>";
        
        if (!empty($userRevisions)) {
            echo "<pre>" . print_r($userRevisions[0], true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ getUserRevisions hatası: " . $e->getMessage() . "</p>";
    }
    
    // getAllRevisions metodunu test et (admin için)
    if (isAdmin()) {
        echo "<h3>getAllRevisions() Test (Admin):</h3>";
        try {
            $allRevisions = $fileManager->getAllRevisions(1, 50);
            echo "<p>Tüm revize sayısı: <strong>" . count($allRevisions) . "</strong></p>";
            
            if (!empty($allRevisions)) {
                echo "<pre>" . print_r($allRevisions[0], true) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ getAllRevisions hatası: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>4. Test Revize Talebi Oluştur:</h2>";
    
    if ($completedCount > 0) {
        // İlk completed dosya için test revize talebi oluştur
        $stmt = $pdo->prepare("SELECT id FROM file_uploads WHERE user_id = ? AND status = 'completed' LIMIT 1");
        $stmt->execute([$userId]);
        $testFileId = $stmt->fetch()['id'];
        
        echo "<p>Test dosya ID: $testFileId</p>";
        
        // Zaten revize talebi var mı kontrol et
        if ($revisionTableExists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM revisions WHERE upload_id = ? AND user_id = ?");
            $stmt->execute([$testFileId, $userId]);
            $existingRevisions = $stmt->fetch()['count'];
            
            if ($existingRevisions == 0) {
                echo "<form method='POST'>";
                echo "<input type='hidden' name='test_revision' value='1'>";
                echo "<input type='hidden' name='upload_id' value='$testFileId'>";
                echo "<p><textarea name='test_notes' placeholder='Test revize açıklaması...' style='width:100%; height:60px;'>Test revize talebi - Lütfen dosyada şu değişiklikleri yapın...</textarea></p>";
                echo "<p><button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px;'>🧪 Test Revize Talebi Oluştur</button></p>";
                echo "</form>";
            } else {
                echo "<p style='color:green;'>✅ Bu dosya için zaten revize talebi var</p>";
            }
        }
    } else {
        echo "<p style='color:orange;'>⚠️ Test için tamamlanmış dosya yok</p>";
    }
}

// Test revize talebi işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_revision'])) {
    $testUploadId = sanitize($_POST['upload_id']);
    $testNotes = sanitize($_POST['test_notes']);
    
    echo "<h3>Test Revize Talebi Sonucu:</h3>";
    
    try {
        $result = $fileManager->requestRevision($testUploadId, $userId, $testNotes);
        
        if ($result['success']) {
            echo "<p style='color:green;'>✅ " . $result['message'] . "</p>";
            echo "<p><a href='revisions.php'>Revize taleplerinizi görüntüleyin</a></p>";
        } else {
            echo "<p style='color:red;'>❌ " . $result['message'] . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Test revize hatası: " . $e->getMessage() . "</p>";
    }
}

echo "<br><br>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>📋 Hızlı Linkler</h3>";
echo "<p><a href='files.php'>📁 Dosyalarım</a></p>";
echo "<p><a href='revisions.php'>🔄 Revize Taleplerim</a></p>";
if (isAdmin()) {
    echo "<p><a href='../admin/revisions.php'>⚙️ Admin Revize Yönetimi</a></p>";
}
echo "<p><a href='../install-revisions.php'>🔧 Revize Sistemini Yeniden Kur</a></p>";
echo "</div>";
?>
