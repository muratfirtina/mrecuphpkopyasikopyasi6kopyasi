<?php
/**
 * Revision approval test script
 */

session_start();
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Revision Approval Test</h1>";

// Admin session simulation for testing
if (!isset($_SESSION['user_id'])) {
    // Test için admin user oluştur/getir
    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        
        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['is_admin'] = 1;
            echo "<p>✅ Test admin session oluşturuldu: " . $admin['id'] . "</p>";
        } else {
            echo "<p>❌ Admin kullanıcı bulunamadı!</p>";
            exit;
        }
    } catch (Exception $e) {
        echo "<p>❌ Admin session oluşturulamadı: " . $e->getMessage() . "</p>";
        exit;
    }
}

try {
    // FileManager instance oluştur
    $fileManager = new FileManager($pdo);
    echo "<p>✅ FileManager instance oluşturuldu</p>";
    
    // Bekleyen revize talebi var mı kontrol et
    $revisions = $fileManager->getAllRevisions(1, 5, 'pending');
    echo "<p>Bekleyen revize sayısı: " . count($revisions) . "</p>";
    
    if (count($revisions) > 0) {
        $testRevision = $revisions[0];
        echo "<h3>Test Revision:</h3>";
        echo "<p>ID: " . $testRevision['id'] . "</p>";
        echo "<p>User: " . $testRevision['username'] . "</p>";
        echo "<p>Status: " . $testRevision['status'] . "</p>";
        echo "<p>File: " . $testRevision['original_name'] . "</p>";
        
        // Test the approval process
        echo "<h3>Testing Approval Process:</h3>";
        
        $result = $fileManager->updateRevisionStatus(
            $testRevision['id'], 
            $_SESSION['user_id'], 
            'in_progress', 
            'Test onaylama - automated test', 
            0
        );
        
        echo "<p>Approval Result:</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            echo "<p>✅ <strong>Revize onaylama başarılı!</strong></p>";
            
            // Status'u geri pending'e çevir (test için)
            $revertResult = $fileManager->updateRevisionStatus(
                $testRevision['id'], 
                $_SESSION['user_id'], 
                'pending', 
                'Test tamamlandı - durumu geri alındı', 
                0
            );
            
            if ($revertResult['success']) {
                echo "<p>✅ Test tamamlandı, durum geri alındı</p>";
            }
            
        } else {
            echo "<p>❌ <strong>Revize onaylama başarısız!</strong></p>";
            echo "<p>Hata: " . $result['message'] . "</p>";
        }
        
    } else {
        echo "<p>⚠️ Test için bekleyen revize talebi bulunamadı</p>";
        
        // Test verisi oluştur
        echo "<h3>Test Verisi Oluşturuluyor:</h3>";
        
        // Test user bul
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
        $testUser = $stmt->fetch();
        
        if (!$testUser) {
            echo "<p>❌ Test kullanıcı bulunamadı</p>";
        } else {
            // Test upload bul
            $stmt = $pdo->prepare("SELECT id FROM file_uploads WHERE user_id = ? LIMIT 1");
            $stmt->execute([$testUser['id']]);
            $testUpload = $stmt->fetch();
            
            if (!$testUpload) {
                echo "<p>❌ Test dosya yüklemesi bulunamadı</p>";
            } else {
                // Test revize talebi oluştur
                $testRevisionId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO revisions (id, upload_id, user_id, request_notes, status, requested_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ");
                
                $insertResult = $stmt->execute([
                    $testRevisionId,
                    $testUpload['id'],
                    $testUser['id'],
                    'Test revize talebi - automated test'
                ]);
                
                if ($insertResult) {
                    echo "<p>✅ Test revize talebi oluşturuldu: " . $testRevisionId . "</p>";
                    echo "<p><a href='admin/revisions.php' target='_blank'>Admin Revise Sayfasını Test Et</a></p>";
                } else {
                    echo "<p>❌ Test revize talebi oluşturulamadı</p>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Test sırasında hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
