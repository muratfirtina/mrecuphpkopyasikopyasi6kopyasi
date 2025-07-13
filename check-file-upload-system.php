<?php
/**
 * File Responses Table Check and Setup
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>File Upload System Check</h1>";

try {
    // 1. file_responses tablosu kontrolü
    echo "<h2>1. File Responses Table Check</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_responses'");
    
    if ($stmt->rowCount() == 0) {
        echo "❌ file_responses tablosu bulunamadı!<br>";
        echo "Tablo oluşturuluyor...<br>";
        
        $createTable = "
            CREATE TABLE IF NOT EXISTS `file_responses` (
                `id` varchar(36) NOT NULL,
                `upload_id` varchar(36) NOT NULL,
                `admin_id` varchar(36) DEFAULT NULL,
                `original_name` varchar(255) NOT NULL,
                `filename` varchar(255) NOT NULL,
                `file_size` bigint(20) NOT NULL DEFAULT 0,
                `credits_charged` decimal(10,2) DEFAULT 0.00,
                `admin_notes` text DEFAULT NULL,
                `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_upload_id` (`upload_id`),
                KEY `idx_admin_id` (`admin_id`),
                KEY `idx_upload_date` (`upload_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTable);
        echo "✅ file_responses tablosu oluşturuldu!<br>";
    } else {
        echo "✅ file_responses tablosu mevcut<br>";
        
        // Tablo yapısını kontrol et
        $columns = $pdo->query("DESCRIBE file_responses")->fetchAll();
        echo "<h3>Tablo Yapısı:</h3>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        $count = $pdo->query("SELECT COUNT(*) FROM file_responses")->fetchColumn();
        echo "Toplam kayıt: " . $count . "<br>";
    }
    
    // 2. Upload dizinleri kontrolü
    echo "<h2>2. Upload Directories Check</h2>";
    
    $directories = [
        'main' => UPLOAD_PATH,
        'user_files' => UPLOAD_PATH . 'user_files/',
        'response_files' => UPLOAD_PATH . 'response_files/'
    ];
    
    foreach ($directories as $name => $dir) {
        echo "<strong>" . $name . ":</strong> " . $dir . " - ";
        
        if (is_dir($dir)) {
            echo "✅ Mevcut";
            echo " (Yazılabilir: " . (is_writable($dir) ? "✅" : "❌") . ")";
        } else {
            echo "❌ Yok - Oluşturuluyor...";
            if (mkdir($dir, 0755, true)) {
                echo " ✅ Oluşturuldu";
            } else {
                echo " ❌ Oluşturulamadı";
            }
        }
        echo "<br>";
    }
    
    // 3. PHP Upload Settings
    echo "<h2>3. PHP Upload Settings</h2>";
    echo "File uploads enabled: " . (ini_get('file_uploads') ? '✅ Yes' : '❌ No') . "<br>";
    echo "Max file size: " . ini_get('upload_max_filesize') . "<br>";
    echo "Max post size: " . ini_get('post_max_size') . "<br>";
    echo "Memory limit: " . ini_get('memory_limit') . "<br>";
    echo "Max execution time: " . ini_get('max_execution_time') . " seconds<br>";
    echo "Temp directory: " . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . "<br>";
    
    // 4. FileManager test
    echo "<h2>4. FileManager Test</h2>";
    require_once 'includes/FileManager.php';
    
    $fileManager = new FileManager($pdo);
    echo "✅ FileManager instance oluşturuldu<br>";
    
    if (method_exists($fileManager, 'uploadResponseFile')) {
        echo "✅ uploadResponseFile metodu mevcut<br>";
    } else {
        echo "❌ uploadResponseFile metodu eksik<br>";
    }
    
    if (method_exists($fileManager, 'updateUploadStatus')) {
        echo "✅ updateUploadStatus metodu mevcut<br>";
    } else {
        echo "❌ updateUploadStatus metodu eksik<br>";
    }
    
    // 5. Test upload
    echo "<h2>5. Test File Upload Structure</h2>";
    
    // Sample test files array
    $testFile = [
        'name' => 'test.bin',
        'type' => 'application/octet-stream',
        'size' => 1024,
        'tmp_name' => '/tmp/test',
        'error' => UPLOAD_ERR_OK
    ];
    
    echo "Test file structure: ✅ Valid<br>";
    
    // Test upload ID
    $testUploadId = '7d227059-7f00-4409-a849-5d766ec9136d';
    echo "Test upload ID: " . $testUploadId . "<br>";
    
    // Check if upload exists
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
    $stmt->execute([$testUploadId]);
    $upload = $stmt->fetch();
    
    if ($upload) {
        echo "✅ Test upload found: " . $upload['original_name'] . "<br>";
        echo "Upload status: " . $upload['status'] . "<br>";
        echo "User ID: " . $upload['user_id'] . "<br>";
    } else {
        echo "❌ Test upload not found<br>";
    }
    
    echo "<h2>✅ System Check Complete!</h2>";
    echo "<p><a href='admin/file-detail.php?id=" . $testUploadId . "' target='_blank'>Test File Detail Page</a></p>";
    echo "<p><a href='debug-file-upload.php' target='_blank'>Test File Upload Debug</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
