<?php
/**
 * Admin File Detail Revizyon Formu Ekleme
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin File Detail Güncelleme</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";

echo "<h1>🔧 Admin File Detail Revizyon Formu Ekleme</h1>";

try {
    $filePath = '/Applications/MAMP/htdocs/mrecuphpkopyasikopyasi6kopyasi/admin/file-detail.php';
    
    // Dosyayı oku
    $content = file_get_contents($filePath);
    
    if ($content === false) {
        throw new Exception("Dosya okunamadı: $filePath");
    }
    
    // revision_file formu zaten var mı kontrol et
    if (strpos($content, 'name="revision_file"') !== false) {
        echo "<p class='success'>✅ revision_file formu zaten mevcut</p>";
    } else {
        echo "<p class='error'>❌ revision_file formu eksik - eklenecek</p>";
        
        // Revizyon formu kodu
        $revisionFormCode = '
// Revizyon dosyası yükleme (yeni eklenen)
if (isset($_FILES[\'revision_file\']) && isset($_POST[\'upload_revision\'])) {
    error_log("Revision file upload request started");
    $revisionId = sanitize($_POST[\'revision_id\']);
    $adminNotes = sanitize($_POST[\'revision_notes\'] ?? \'\');
    
    if (!isValidUUID($revisionId)) {
        $error = \'Geçersiz revizyon ID formatı.\';
        error_log("Invalid revision ID format: " . $revisionId);
    } else {
        // Dosya yükleme hatası kontrolü
        if (!isset($_FILES[\'revision_file\']) || $_FILES[\'revision_file\'][\'error\'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => \'Dosya çok büyük (php.ini limit)\',
                UPLOAD_ERR_FORM_SIZE => \'Dosya çok büyük (form limit)\',
                UPLOAD_ERR_PARTIAL => \'Dosya kısmen yüklendi\',
                UPLOAD_ERR_NO_FILE => \'Dosya seçilmedi\',
                UPLOAD_ERR_NO_TMP_DIR => \'Geçici dizin yok\',
                UPLOAD_ERR_CANT_WRITE => \'Diske yazılamadı\',
                UPLOAD_ERR_EXTENSION => \'Uzantı yüklemeyi durdurdu\'
            ];
            
            $fileError = $_FILES[\'revision_file\'][\'error\'] ?? UPLOAD_ERR_NO_FILE;
            $error = \'Revizyon dosyası yükleme hatası: \' . ($errorMessages[$fileError] ?? \'Bilinmeyen hata (\' . $fileError . \')\');
            error_log("Revision file upload error: " . $error);
        } else {
            error_log("Processing revision file upload - Notes: " . $adminNotes);
            
            // Session kontrolü
            if (!isset($_SESSION[\'user_id\'])) {
                throw new Exception("User session not found");
            }
            
            $result = $fileManager->uploadRevisionFile($revisionId, $_FILES[\'revision_file\'], $adminNotes);
            
            error_log("Revision upload result: " . print_r($result, true));
            
            if ($result[\'success\']) {
                $success = $result[\'message\'];
                $user->logAction($_SESSION[\'user_id\'], \'revision_file_upload\', "Revizyon dosyası yüklendi: {$revisionId}");
                
                // Başarılı yükleme sonrası redirect
                header("Location: file-detail.php?id={$uploadId}&type={$fileType}&success=" . urlencode($success));
                exit;
            } else {
                $error = $result[\'message\'];
                error_log("Revision upload failed: " . $error);
            }
        }
    }
}';
        
        // POST işlemlerinin sonuna ekle (} catch öncesine)
        $insertPattern = '} catch (Exception $e) {
        $error = \'İşlem sırasında hata oluştu: \' . $e->getMessage();';
        
        $newContent = str_replace($insertPattern, $revisionFormCode . "\n        \n        " . $insertPattern, $content);
        
        if ($newContent !== $content) {
            // Dosyayı güncelle
            if (file_put_contents($filePath, $newContent)) {
                echo "<p class='success'>✅ POST işlemi başarıyla eklendi!</p>";
            } else {
                echo "<p class='error'>❌ Dosya yazılamadı</p>";
            }
        } else {
            echo "<p class='error'>❌ POST işlemi eklenecek yer bulunamadı</p>";
        }
    }
    
    echo "<h2>🎯 Test İçin Yapılacaklar:</h2>";
    echo "<ol>";
    echo "<li><a href='make-revision-in-progress.php' target='_blank'>Revizyon durumunu in_progress yap</a></li>";
    echo "<li>Admin file-detail.php sayfasına git</li>";
    echo "<li>Revizyon dosyası yükleme formunun görünüp görünmediğini kontrol et</li>";
    echo "<li>Test dosyası yükle</li>";
    echo "<li><a href='test-revision-system.php' target='_blank'>Sistem testini çalıştır</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
