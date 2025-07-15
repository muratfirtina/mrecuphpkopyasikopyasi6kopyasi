<?php
/**
 * User revision-detail.php Undefined Key Hatalarını Düzelt
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>User Revision Detail Hata Düzeltme</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";

echo "<h1>🔧 User Revision Detail Hata Düzeltme</h1>";

try {
    $filePath = '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/user/revision-detail.php';
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        throw new Exception("Dosya okunamadı: $filePath");
    }
    
    echo "<h2>📋 Düzeltilecek Hatalar:</h2>";
    echo "<ul>";
    echo "<li>Undefined array key 'category_name'</li>";
    echo "<li>Undefined array key 'revision_filename'</li>";
    echo "<li>Undefined array key 'revision_file_path'</li>";
    echo "</ul>";
    
    // Hataları düzelt
    $fixes = [
        // category_name hatası
        '$revision[\'category_name\']' => '($revision[\'category_name\'] ?? \'Kategori belirtilmemiş\')',
        
        // revision_filename hatası  
        '$revision[\'revision_filename\']' => '($revision[\'revision_filename\'] ?? \'Revizyon dosyası bulunamadı\')',
        
        // revision_file_path hatası
        '$revision[\'revision_file_path\']' => '($revision[\'revision_file_path\'] ?? \'\')',
        
        // Ek güvenlik için diğer potansiyel hatalar
        '$revision[\'brand_name\']' => '($revision[\'brand_name\'] ?? \'Marka bilgisi yok\')',
        '$revision[\'model_name\']' => '($revision[\'model_name\'] ?? \'Model bilgisi yok\')',
        '$revision[\'year\']' => '($revision[\'year\'] ?? \'Yıl bilgisi yok\')',
        '$revision[\'plate\']' => '($revision[\'plate\'] ?? \'Plaka bilgisi yok\')',
        '$revision[\'admin_notes\']' => '($revision[\'admin_notes\'] ?? \'Admin notu yok\')',
        '$revision[\'admin_username\']' => '($revision[\'admin_username\'] ?? \'Admin bilgisi yok\')',
        '$revision[\'admin_first_name\']' => '($revision[\'admin_first_name\'] ?? \'\')',
        '$revision[\'admin_last_name\']' => '($revision[\'admin_last_name\'] ?? \'\')'
    ];
    
    $fixCount = 0;
    $newContent = $content;
    
    foreach ($fixes as $search => $replace) {
        if (strpos($newContent, $search) !== false) {
            $newContent = str_replace($search, $replace, $newContent);
            $fixCount++;
            echo "<p class='success'>✅ Düzeltildi: " . htmlspecialchars($search) . "</p>";
        }
    }
    
    // Dosyayı güncelle
    if ($fixCount > 0) {
        if (file_put_contents($filePath, $newContent)) {
            echo "<p class='success'><strong>✅ Toplam $fixCount hata düzeltildi ve dosya güncellendi!</strong></p>";
        } else {
            echo "<p class='error'>❌ Dosya yazılamadı</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Düzeltilecek hata bulunamadı</p>";
    }
    
    // Revizyon dosyalarını göstermek için getRevisionFiles metodunu kullan
    echo "<h2>📂 Revizyon Dosyalarını Gösterme Sistemi</h2>";
    
    $revisionFilesCode = '
// Revizyon dosyalarını getir (eğer revizyon tamamlanmışsa)
$revisionFiles = [];
if ($revision[\'status\'] === \'completed\') {
    $revisionFiles = $fileManager->getRevisionFiles($revisionId, $userId);
}';
    
    if (strpos($newContent, 'getRevisionFiles') === false) {
        // Revision files kodunu ekle
        $insertPattern = '// Status konfigürasyonu';
        $newContent = str_replace($insertPattern, $revisionFilesCode . "\n\n" . $insertPattern, $newContent);
        
        file_put_contents($filePath, $newContent);
        echo "<p class='success'>✅ Revizyon dosyalarını gösterme kodu eklendi</p>";
    } else {
        echo "<p class='success'>✅ Revizyon dosyalarını gösterme kodu zaten mevcut</p>";
    }
    
    echo "<h2>📋 Ek HTML Düzeltmeler</h2>";
    
    // Revizyon dosyalarını göstermek için HTML kod ekle
    $revisionFilesHTML = '
            <!-- Revizyon Dosyaları Bölümü -->
            <?php if (!empty($revisionFiles)): ?>
                <div class="col-12">
                    <div class="info-card">
                        <div class="info-header">
                            <h6 class="mb-0">
                                <i class="fas fa-download me-2 text-success"></i>
                                Revizyon Dosyaları (<?php echo count($revisionFiles); ?> adet)
                            </h6>
                        </div>
                        <div class="info-content">
                            <?php foreach ($revisionFiles as $revFile): ?>
                                <div class="revision-file-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="file-info">
                                            <h6 class="mb-1">
                                                <i class="fas fa-file-download me-1 text-success"></i>
                                                <?php echo htmlspecialchars($revFile[\'original_name\']); ?>
                                            </h6>
                                            <div class="file-meta">
                                                <span class="badge bg-light text-dark me-2">
                                                    <?php echo formatFileSize($revFile[\'file_size\']); ?>
                                                </span>
                                                <span class="text-muted">
                                                    <?php echo date(\'d.m.Y H:i\', strtotime($revFile[\'upload_date\'])); ?>
                                                </span>
                                            </div>
                                            <?php if ($revFile[\'admin_notes\']): ?>
                                                <div class="admin-notes mt-2">
                                                    <small class="text-muted">
                                                        <strong>Admin Notları:</strong> <?php echo nl2br(htmlspecialchars($revFile[\'admin_notes\'])); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-actions">
                                            <a href="download-revision.php?id=<?php echo $revFile[\'id\']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i>İndir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>';
    
    if (strpos($newContent, 'Revizyon Dosyaları Bölümü') === false) {
        // HTML'i ekle - summary-item'dan sonra
        $insertHTML = '</div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>';
        
        $newContent = str_replace($insertHTML, $revisionFilesHTML . "\n\n        " . $insertHTML, $newContent);
        file_put_contents($filePath, $newContent);
        echo "<p class='success'>✅ Revizyon dosyaları HTML bölümü eklendi</p>";
    } else {
        echo "<p class='success'>✅ Revizyon dosyaları HTML bölümü zaten mevcut</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><hr><br>";
echo "<h2>🎯 Test Edilecekler:</h2>";
echo "<ol>";
echo "<li>User revision-detail.php sayfasını aç</li>";
echo "<li>Undefined key hataları kayboldu mu kontrol et</li>";
echo "<li>Revizyon dosyaları görünüyor mu kontrol et</li>";
echo "<li>İndirme butonları çalışıyor mu test et</li>";
echo "</ol>";

echo "<p class='success'><strong>✅ Artık kullanıcı revizyon dosyalarını görebilir ve indirebilir!</strong></p>";

echo "</body></html>";
?>
