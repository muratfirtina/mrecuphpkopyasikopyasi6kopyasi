<?php
/**
 * completed_at sütunlarını ekleyen migration script
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Admin kontrolü
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('❌ Unauthorized access. Admin login required.');
}

echo "🔧 completed_at Sütunları Migration\n";
echo "====================================\n\n";

try {
    // 1. file_uploads tablosuna completed_at sütunu ekle
    echo "1. file_uploads tablosuna completed_at sütunu ekleniyor...\n";
    
    // Önce sütun var mı kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'completed_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN completed_at DATETIME NULL AFTER upload_date");
        echo "   ✅ file_uploads.completed_at sütunu eklendi\n";
    } else {
        echo "   ⚠️ file_uploads.completed_at sütunu zaten mevcut\n";
    }
    
    // 2. revisions tablosuna completed_at sütunu ekle
    echo "\n2. revisions tablosuna completed_at sütunu ekleniyor...\n";
    
    // Önce sütun var mı kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'completed_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE revisions ADD COLUMN completed_at DATETIME NULL AFTER requested_at");
        echo "   ✅ revisions.completed_at sütunu eklendi\n";
    } else {
        echo "   ⚠️ revisions.completed_at sütunu zaten mevcut\n";
    }
    
    // 3. Mevcut completed dosyalar için completed_at güncelle
    echo "\n3. Mevcut completed dosyalar için completed_at güncelleniyor...\n";
    
    // file_uploads tablosunda completed olanları güncelle
    $stmt = $pdo->prepare("
        UPDATE file_uploads 
        SET completed_at = upload_date 
        WHERE status = 'completed' AND completed_at IS NULL
    ");
    $stmt->execute();
    $updatedFiles = $stmt->rowCount();
    echo "   ✅ $updatedFiles dosya kaydı güncellendi\n";
    
    // revisions tablosunda completed olanları güncelle
    $stmt = $pdo->prepare("
        UPDATE revisions 
        SET completed_at = requested_at 
        WHERE status = 'completed' AND completed_at IS NULL
    ");
    $stmt->execute();
    $updatedRevisions = $stmt->rowCount();
    echo "   ✅ $updatedRevisions revizyon kaydı güncellendi\n";
    
    // 4. Tablo yapılarını kontrol et
    echo "\n4. Güncellenmiş tablo yapıları:\n";
    
    // file_uploads yapısı
    echo "\n📋 file_uploads tablosu:\n";
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        $nullable = $column['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ? "DEFAULT {$column['Default']}" : '';
        echo "   - {$column['Field']}: {$column['Type']} $nullable $default\n";
    }
    
    // revisions yapısı
    echo "\n📋 revisions tablosu:\n";
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        $nullable = $column['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ? "DEFAULT {$column['Default']}" : '';
        echo "   - {$column['Field']}: {$column['Type']} $nullable $default\n";
    }
    
    // 5. Test completed_at sütunlarının çalışıp çalışmadığını
    echo "\n5. completed_at sütunları test ediliyor...\n";
    
    // Rastgele bir file_uploads kaydı al
    $stmt = $pdo->query("SELECT id, original_name, status, completed_at FROM file_uploads LIMIT 1");
    $testFile = $stmt->fetch();
    
    if ($testFile) {
        echo "   📄 Test dosyası: {$testFile['original_name']}\n";
        echo "   📊 Status: {$testFile['status']}\n";
        echo "   📅 Completed At: " . ($testFile['completed_at'] ?? 'NULL') . "\n";
    }
    
    // Rastgele bir revisions kaydı al
    $stmt = $pdo->query("SELECT id, status, completed_at FROM revisions LIMIT 1");
    $testRevision = $stmt->fetch();
    
    if ($testRevision) {
        echo "   🔄 Test revizyon: {$testRevision['id']}\n";
        echo "   📊 Status: {$testRevision['status']}\n";
        echo "   📅 Completed At: " . ($testRevision['completed_at'] ?? 'NULL') . "\n";
    }
    
    echo "\n✅ Migration tamamlandı!\n";
    echo "🎉 Artık FileManager metodları completed_at sütunlarını kullanabilir.\n\n";
    
    echo "📝 Yapılan değişiklikler:\n";
    echo "   - file_uploads.completed_at sütunu eklendi\n";
    echo "   - revisions.completed_at sütunu eklendi\n";
    echo "   - Mevcut completed kayıtlar güncellendi\n";
    echo "   - uploadResponseFile() metodu artık çalışacak\n";
    echo "   - uploadRevisionFile() metodu artık çalışacak\n\n";
    
} catch (Exception $e) {
    echo "❌ Migration hatası: " . $e->getMessage() . "\n";
    echo "📞 Lütfen veritabanı bağlantısını ve yetkileri kontrol edin.\n";
}

echo "\n🔗 Admin paneli: /admin/\n";
echo "🔗 Dosya yükleme testi: /admin/files.php\n";
?>
