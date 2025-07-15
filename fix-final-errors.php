<?php
/**
 * Revizyon Sistemi - Son Hatalar Düzeltme
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Revizyon Sistemi Hata Düzeltme</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .warning { color: orange; }</style></head><body>";

echo "<h1>🔧 Revizyon Sistemi - Son Hatalar Düzeltme</h1>";

try {
    // 1. Admin file-detail.php'deki $loop hatasını düzelt
    echo "<h2>1. Admin file-detail.php $loop Hatası Düzeltme</h2>";
    
    $adminFilePath = '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/admin/file-detail.php';
    $adminContent = file_get_contents($adminFilePath);
    
    if ($adminContent !== false) {
        // $loop->last kısmını kaldır
        $adminContent = str_replace('<?php if (!$loop->last): ?>', '<?php if (false): // Removed loop check ?>', $adminContent);
        $adminContent = str_replace('<?php endif; ?>', '<?php endif; ?>', $adminContent);
        
        file_put_contents($adminFilePath, $adminContent);
        echo "<p class='success'>✅ Admin file-detail.php'deki \$loop hatası düzeltildi</p>";
    } else {
        echo "<p class='error'>❌ Admin file-detail.php okunamadı</p>";
    }
    
    // 2. User revision-detail.php'deki undefined key hatalarını düzelt
    echo "<h2>2. User revision-detail.php Hatalarını Düzelt</h2>";
    
    $userFilePath = '/Applications/MAMP/htdocs/mrecuphkopyasikopyasi6kopyasi/user/revision-detail.php';
    $userContent = file_get_contents($userFilePath);
    
    if ($userContent !== false) {
        // Undefined key hatalarını düzelt
        $userContent = str_replace('$revision[\'category_name\']', '($revision[\'category_name\'] ?? \'Kategori yok\')', $userContent);
        $userContent = str_replace('$revision[\'revision_filename\']', '($revision[\'revision_filename\'] ?? \'Revizyon dosyası yok\')', $userContent);
        $userContent = str_replace('$revision[\'revision_file_path\']', '($revision[\'revision_file_path\'] ?? \'\')', $userContent);
        
        file_put_contents($userFilePath, $userContent);
        echo "<p class='success'>✅ User revision-detail.php'deki undefined key hataları düzeltildi</p>";
    } else {
        echo "<p class='error'>❌ User revision-detail.php okunamadı</p>";
    }
    
    // 3. Revizyon durumunu kontrol et ve düzelt
    echo "<h2>3. Revizyon Durumu Kontrolü</h2>";
    
    $stmt = $pdo->query("SELECT * FROM revisions ORDER BY requested_at DESC LIMIT 1");
    $latestRevision = $stmt->fetch();
    
    if ($latestRevision) {
        echo "<p><strong>Son Revizyon:</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . substr($latestRevision['id'], 0, 8) . "...</li>";
        echo "<li>Durum: <strong>" . $latestRevision['status'] . "</strong></li>";
        echo "<li>Tarih: " . date('d.m.Y H:i', strtotime($latestRevision['requested_at'])) . "</li>";
        echo "</ul>";
        
        if ($latestRevision['status'] === 'completed') {
            echo "<p class='warning'>⚠️ Revizyon 'completed' durumunda ama revision_files'da kayıt yok</p>";
            echo "<p>Bu revizyonu 'in_progress' durumuna getirip tekrar test edelim:</p>";
            
            if (isset($_POST['reset_revision'])) {
                $stmt = $pdo->prepare("UPDATE revisions SET status = 'in_progress' WHERE id = ?");
                if ($stmt->execute([$latestRevision['id']])) {
                    echo "<p class='success'>✅ Revizyon 'in_progress' durumuna getirildi</p>";
                    echo "<script>setTimeout(() => location.reload(), 2000);</script>";
                } else {
                    echo "<p class='error'>❌ Revizyon durumu güncellenemedi</p>";
                }
            } else {
                echo "<form method='POST'>";
                echo "<button type='submit' name='reset_revision' class='btn' style='background: #ffc107; padding: 10px 20px; border: none; border-radius: 5px;'>Revizyonu In_Progress Yap</button>";
                echo "</form>";
            }
        } elseif ($latestRevision['status'] === 'in_progress') {
            echo "<p class='success'>✅ Revizyon 'in_progress' durumunda - admin dosya yükleyebilir</p>";
        }
    }
    
    // 4. Admin formunun varlığını kontrol et
    echo "<h2>4. Admin Revizyon Formu Kontrolü</h2>";
    
    if (strpos($adminContent, 'name="revision_file"') !== false) {
        echo "<p class='success'>✅ Admin'de revision_file formu var</p>";
        
        if (strpos($adminContent, 'upload_revision') !== false) {
            echo "<p class='success'>✅ Upload_revision POST işlemi var</p>";
        } else {
            echo "<p class='error'>❌ Upload_revision POST işlemi eksik</p>";
        }
        
        // Form görünüp görünmediğini kontrol etmek için
        echo "<p><strong>Test için:</strong></p>";
        echo "<ol>";
        echo "<li>Revizyon 'in_progress' durumuna getirin (yukarıdaki buton)</li>";
        echo "<li><a href='admin/file-detail.php?id=" . ($latestRevision['upload_id'] ?? 'UPLOAD_ID') . "' target='_blank'>Admin dosya detayına git</a></li>";
        echo "<li>Revizyon formu görünüyor mu kontrol edin</li>";
        echo "<li>Test dosyası yükleyin</li>";
        echo "</ol>";
        
    } else {
        echo "<p class='error'>❌ Admin'de revision_file formu eksik</p>";
        echo "<p>Manuel olarak eklemek için:</p>";
        echo "<ol>";
        echo "<li><a href='revision-form-html.php' target='_blank'>Bu HTML kodu</a>nu kopyalayın</li>";
        echo "<li>admin/file-detail.php dosyasını açın</li>";
        echo "<li>Diğer formların yanına yapıştırın</li>";
        echo "</ol>";
    }
    
    // 5. Upload klasörü kontrolü
    echo "<h2>5. Upload Klasörleri Kontrolü</h2>";
    
    $uploadDirs = [
        'revision_files' => '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/uploads/revision_files/',
        'response_files' => '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/uploads/response_files/',
        'user_files' => '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/'
    ];
    
    foreach ($uploadDirs as $name => $path) {
        if (is_dir($path)) {
            $files = glob($path . '*');
            echo "<p class='success'>✅ $name: " . count($files) . " dosya</p>";
        } else {
            echo "<p class='error'>❌ $name: Klasör yok</p>";
            mkdir($path, 0755, true);
            echo "<p class='success'>✅ $name: Klasör oluşturuldu</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><hr><br>";
echo "<h2>🎯 Son Durum</h2>";
echo "<p>Hatalar düzeltildi. Şimdi şunları yapın:</p>";
echo "<ol>";
echo "<li><strong>Revizyonu 'in_progress' yap</strong> (yukarıdaki buton)</li>";
echo "<li><strong>Admin dosya detayına git</strong> ve revizyon formu var mı kontrol et</li>";
echo "<li><strong>Test dosyası yükle</strong></li>";
echo "<li><strong><a href='test-revision-system.php' target='_blank'>Sistem testini tekrar çalıştır</a></strong></li>";
echo "</ol>";

echo "<p class='success'><strong>Hedef:</strong> revision_files tablosunda 1 kayıt görülmeli!</p>";

echo "</body></html>";
?>
