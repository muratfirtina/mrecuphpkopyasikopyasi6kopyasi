<?php
/**
 * Mr ECU - Upload System Test
 * Dosya yükleme sisteminin çalışıp çalışmadığını test eder
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Mr ECU Upload System Test</h1>";

try {
    // Config dosyasını yükle
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    echo "✅ Config dosyaları başarıyla yüklendi<br>";
    
    // Database bağlantısını test et
    if (isset($pdo) && $pdo !== null) {
        echo "✅ Database bağlantısı başarılı<br>";
        
        // Gerekli tabloları kontrol et
        $tables = ['users', 'brands', 'models', 'file_uploads'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Tablo mevcut: $table<br>";
                
                if ($table === 'file_uploads') {
                    // file_uploads tablosunun yapısını kontrol et
                    $columns = $pdo->query("DESCRIBE file_uploads")->fetchAll();
                    echo "📋 file_uploads tablosu yapısı:<br>";
                    echo "<ul>";
                    foreach ($columns as $column) {
                        echo "<li>{$column['Field']} - {$column['Type']}</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "❌ Tablo eksik: $table<br>";
            }
        }
        
        // Marka ve model verilerini kontrol et
        $brandsCount = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
        echo "📊 Brands tablosunda $brandsCount adet marka<br>";
        
        if ($brandsCount > 0) {
            $brands = $pdo->query("SELECT id, name FROM brands LIMIT 3")->fetchAll();
            echo "🏢 İlk 3 marka:<br>";
            foreach ($brands as $brand) {
                echo "- {$brand['name']} (ID: {$brand['id']})<br>";
                
                // Bu markanın modellerini kontrol et
                $modelsCount = $pdo->prepare("SELECT COUNT(*) FROM models WHERE brand_id = ?");
                $modelsCount->execute([$brand['id']]);
                $count = $modelsCount->fetchColumn();
                echo "&nbsp;&nbsp;↳ $count adet model<br>";
            }
        }
        
        // FileManager sınıfını test et
        if (class_exists('FileManager')) {
            echo "✅ FileManager sınıfı mevcut<br>";
            
            $fileManager = new FileManager($pdo);
            $brands = $fileManager->getBrands();
            echo "🔧 FileManager getBrands() test: " . count($brands) . " marka döndü<br>";
            
            if (count($brands) > 0) {
                $firstBrand = $brands[0];
                $models = $fileManager->getModelsByBrand($firstBrand['id']);
                echo "🔧 FileManager getModelsByBrand() test: " . count($models) . " model döndü<br>";
            }
            
            // uploadFile metodunu kontrol et
            if (method_exists($fileManager, 'uploadFile')) {
                echo "✅ uploadFile metodu mevcut<br>";
            } else {
                echo "❌ uploadFile metodu eksik!<br>";
            }
        } else {
            echo "❌ FileManager sınıfı bulunamadı<br>";
        }
        
        // User sınıfını test et
        if (class_exists('User')) {
            echo "✅ User sınıfı mevcut<br>";
        } else {
            echo "❌ User sınıfı bulunamadı<br>";
        }
        
        // Upload dizinlerini kontrol et
        $uploadDirs = [
            UPLOAD_PATH . 'user_files/',
            UPLOAD_PATH . 'response_files/',
            UPLOAD_PATH . 'revision_files/'
        ];
        
        foreach ($uploadDirs as $dir) {
            if (is_dir($dir)) {
                echo "✅ Upload dizini mevcut: $dir<br>";
                if (is_writable($dir)) {
                    echo "&nbsp;&nbsp;✅ Yazılabilir<br>";
                } else {
                    echo "&nbsp;&nbsp;❌ Yazılamaz!<br>";
                }
            } else {
                echo "❌ Upload dizini eksik: $dir<br>";
                // Dizini oluşturmaya çalış
                if (mkdir($dir, 0755, true)) {
                    echo "&nbsp;&nbsp;✅ Dizin oluşturuldu<br>";
                } else {
                    echo "&nbsp;&nbsp;❌ Dizin oluşturulamadı<br>";
                }
            }
        }
        
        // Session kontrolü
        if (session_status() === PHP_SESSION_ACTIVE) {
            echo "✅ Session aktif<br>";
        } else {
            echo "❌ Session aktif değil<br>";
        }
        
        // Helper fonksiyonları kontrol et
        $functions = ['generateUUID', 'isValidUUID', 'sanitize', 'formatFileSize'];
        foreach ($functions as $func) {
            if (function_exists($func)) {
                echo "✅ Fonksiyon mevcut: $func<br>";
            } else {
                echo "❌ Fonksiyon eksik: $func<br>";
            }
        }
        
        echo "<br><h2>🎯 Test Sonucu</h2>";
        echo "Upload sistemi test edildi. Yukarıdaki sonuçları kontrol edin.<br>";
        echo "Eğer tüm kontroller ✅ ise sistem çalışmaya hazır olmalıdır.<br>";
        
    } else {
        echo "❌ Database bağlantısı başarısız<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Test sırasında hata: " . $e->getMessage() . "<br>";
    echo "🔍 Hata dosyası: " . $e->getFile() . " (Satır: " . $e->getLine() . ")<br>";
}

echo "<br><p><a href='user/upload.php'>Upload sayfasına git</a></p>";
?>
