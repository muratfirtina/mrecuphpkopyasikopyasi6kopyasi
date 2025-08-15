<?php
/**
 * Test İptal Talebi Oluştur
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🧪 Test İptal Talebi Oluştur</h1>";

try {
    // 1. Rastgele bir kullanıcı al
    $userStmt = $pdo->query("SELECT id, username, email FROM users WHERE role = 'user' LIMIT 1");
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div style='color: red;'>❌ Test için kullanıcı bulunamadı!</div>";
        exit;
    }
    
    echo "<p>Test kullanıcısı: {$user['username']} ({$user['email']})</p>";
    
    // 2. Rastgele bir dosya al (eğer varsa)
    $fileStmt = $pdo->query("SELECT id, original_name FROM file_uploads WHERE user_id = '{$user['id']}' LIMIT 1");
    $file = $fileStmt->fetch(PDO::FETCH_ASSOC);
    
    $fileId = $file ? $file['id'] : generateUUID(); // Eğer dosya yoksa fake ID
    $fileName = $file ? $file['original_name'] : 'test_file.bin';
    
    echo "<p>Test dosyası: $fileName (ID: " . substr($fileId, 0, 8) . "...)</p>";
    
    // 3. Test iptal talebi oluştur
    $cancellationId = generateUUID();
    $stmt = $pdo->prepare("
        INSERT INTO file_cancellations (
            id, user_id, file_id, file_type, reason, 
            credits_to_refund, requested_at, status
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    
    $result = $stmt->execute([
        $cancellationId,
        $user['id'],
        $fileId,
        'upload',
        'Test iptal talebi - Debug amaçlı oluşturuldu',
        5.50,
        'pending'
    ]);
    
    if ($result) {
        echo "<div style='color: green;'>✅ Test iptal talebi oluşturuldu!</div>";
        echo "<p>Talep ID: " . substr($cancellationId, 0, 8) . "...</p>";
        
        // 4. Oluşan veriyi kontrol et
        $checkStmt = $pdo->prepare("
            SELECT fc.*, u.username, u.email 
            FROM file_cancellations fc
            LEFT JOIN users u ON fc.user_id = u.id
            WHERE fc.id = ?
        ");
        $checkStmt->execute([$cancellationId]);
        $testRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testRecord) {
            echo "<h3>Oluşturulan Test Verisi:</h3>";
            echo "<pre>";
            print_r($testRecord);
            echo "</pre>";
        }
        
    } else {
        echo "<div style='color: red;'>❌ Test iptal talebi oluşturulamadı!</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='debug_cancellations.php'>🔍 Veritabanı Kontrolü</a></p>";
echo "<p><a href='admin/file-cancellations.php?debug=1'>👉 Admin Debug Sayfası</a></p>";
echo "<p><a href='admin/file-cancellations.php'>👉 Normal Admin Sayfası</a></p>";
?>
