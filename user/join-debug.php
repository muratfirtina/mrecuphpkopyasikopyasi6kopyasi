<?php
/**
 * JOIN Sorunu Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>🔗 JOIN Sorunu Debug</h1>";

echo "<h2>1. Upload ID=1 Kontrolü:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = 1");
    $stmt->execute();
    $upload = $stmt->fetch();
    
    if ($upload) {
        echo "<p style='color:green;'>✅ Upload ID=1 bulundu</p>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        foreach ($upload as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ Upload ID=1 bulunamadı!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Revize Talebi Kontrolü:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM revisions WHERE id = 1");
    $stmt->execute();
    $revision = $stmt->fetch();
    
    if ($revision) {
        echo "<p style='color:green;'>✅ Revise ID=1 bulundu</p>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        foreach ($revision as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<h2>3. getUserRevisions SQL Test:</h2>";
try {
    // FileManager'daki getUserRevisions sorgusunu manuel test et
    $userId = 2;
    
    $stmt = $pdo->prepare("
        SELECT r.*, fu.original_name, a.username as admin_username
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN users a ON r.admin_id = a.id
        WHERE r.user_id = ?
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([$userId]);
    $userRevisions = $stmt->fetchAll();
    
    echo "<p>getUserRevisions SQL sonuç sayısı: <strong>" . count($userRevisions) . "</strong></p>";
    
    if (!empty($userRevisions)) {
        echo "<pre>" . print_r($userRevisions[0], true) . "</pre>";
    } else {
        echo "<p style='color:red;'>❌ JOIN sorgusu boş döndü</p>";
        
        // Sadece revisions tablosundan çek
        $stmt = $pdo->prepare("SELECT * FROM revisions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $directRevisions = $stmt->fetchAll();
        
        echo "<p>Direkt revisions sorgusu: <strong>" . count($directRevisions) . "</strong></p>";
        if (!empty($directRevisions)) {
            echo "<pre>" . print_r($directRevisions[0], true) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ getUserRevisions SQL hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>4. getAllRevisions SQL Test:</h2>";
try {
    // FileManager'daki getAllRevisions sorgusunu manuel test et
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.email, fu.original_name, b.name as brand_name, m.name as model_name
        FROM revisions r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute();
    $allRevisions = $stmt->fetchAll();
    
    echo "<p>getAllRevisions SQL sonuç sayısı: <strong>" . count($allRevisions) . "</strong></p>";
    
    if (!empty($allRevisions)) {
        echo "<pre>" . print_r($allRevisions[0], true) . "</pre>";
    } else {
        echo "<p style='color:red;'>❌ getAllRevisions JOIN sorgusu boş döndü</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ getAllRevisions SQL hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Basit Test:</h2>";
try {
    // En basit sorgu
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
    $count = $stmt->fetch()['count'];
    echo "<p>Toplam revize sayısı: <strong>$count</strong></p>";
    
    $stmt = $pdo->query("SELECT * FROM revisions LIMIT 1");
    $revision = $stmt->fetch();
    if ($revision) {
        echo "<p style='color:green;'>✅ Basit revize sorgusu çalışıyor</p>";
        echo "<pre>" . print_r($revision, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Basit test hatası: " . $e->getMessage() . "</p>";
}

echo "<br><a href='revisions-debug.php'>🔙 Ana debug'a dön</a>";
?>
