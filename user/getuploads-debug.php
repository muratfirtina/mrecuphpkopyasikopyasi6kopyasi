<?php
/**
 * Error Log Kontrolü ve getUserUploads Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

if (!isLoggedIn()) {
    die("Giriş yapmanız gerekiyor!");
}

echo "<h1>🔍 getUserUploads Debug</h1>";

$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

echo "<h2>1. Error Log Kontrolü:</h2>";
// PHP error log'unu oku
$errorLog = ini_get('error_log');
echo "<p>Error log dosyası: <code>$errorLog</code></p>";

if (file_exists($errorLog)) {
    $logContent = file_get_contents($errorLog);
    $recentLogs = array_slice(explode("\n", $logContent), -20); // Son 20 satır
    echo "<h3>Son Error Log Kayıtları:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
    foreach ($recentLogs as $log) {
        if (strpos($log, 'getUserUploads') !== false || strpos($log, 'FileManager') !== false) {
            echo "<span style='color: red;'>$log</span>\n";
        } else {
            echo "$log\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>Error log dosyası bulunamadı</p>";
}

echo "<h2>2. getUserUploads Metodu Test (Detaylı):</h2>";

// getUserUploads metodunu direkt test et
echo "<h3>Test 1: Normal Çağrı</h3>";
$uploads = $fileManager->getUserUploads($userId, 1, 20);
echo "<p>Sonuç sayısı: <strong>" . count($uploads) . "</strong></p>";

if (!empty($uploads)) {
    echo "<pre>" . print_r($uploads[0], true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Boş sonuç döndü</p>";
}

echo "<h3>Test 2: Direkt SQL Test</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT fu.*, b.name as brand_name, m.name as model_name,
               (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as has_response,
               (SELECT fr.id FROM file_responses fr WHERE fr.upload_id = fu.id LIMIT 1) as response_id
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.user_id = ?
        ORDER BY fu.upload_date DESC
        LIMIT ? OFFSET ?
    ");
    
    echo "<p>📝 SQL Hazırlandı</p>";
    
    $params = [$userId, 20, 0];
    echo "<p>📋 Parametreler: " . json_encode($params) . "</p>";
    
    $result = $stmt->execute($params);
    echo "<p>🚀 Execute sonucu: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    
    if (!$result) {
        echo "<p style='color: red;'>SQL Hatası: " . json_encode($stmt->errorInfo()) . "</p>";
    } else {
        $directUploads = $stmt->fetchAll();
        echo "<p>📊 Sonuç sayısı: <strong>" . count($directUploads) . "</strong></p>";
        
        if (!empty($directUploads)) {
            echo "<h4>İlk sonuç:</h4>";
            echo "<pre>" . print_r($directUploads[0], true) . "</pre>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>PDO Exception: " . $e->getMessage() . "</p>";
}

echo "<h3>Test 3: Parametre Türü Kontrolü</h3>";
echo "<p>userId: " . $userId . " (type: " . gettype($userId) . ")</p>";
echo "<p>limit: 20 (type: " . gettype(20) . ")</p>";
echo "<p>offset: 0 (type: " . gettype(0) . ")</p>";

echo "<h3>Test 4: Integer Cast Test</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT fu.*, b.name as brand_name, m.name as model_name,
               (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as has_response,
               (SELECT fr.id FROM file_responses fr WHERE fr.upload_id = fu.id LIMIT 1) as response_id
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.user_id = ?
        ORDER BY fu.upload_date DESC
        LIMIT ? OFFSET ?
    ");
    
    // Integer cast ile test
    $intUserId = (int)$userId;
    $intLimit = 20;
    $intOffset = 0;
    
    echo "<p>Cast edilmiş parametreler: userId=$intUserId, limit=$intLimit, offset=$intOffset</p>";
    
    $result = $stmt->execute([$intUserId, $intLimit, $intOffset]);
    
    if ($result) {
        $castUploads = $stmt->fetchAll();
        echo "<p>✅ Cast test başarılı - Sonuç sayısı: <strong>" . count($castUploads) . "</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Cast test başarısız: " . json_encode($stmt->errorInfo()) . "</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Cast Test PDO Exception: " . $e->getMessage() . "</p>";
}

echo "<h2>3. FileManager Sınıfı Kontrolü:</h2>";
echo "<p>Sınıf var mı: " . (class_exists('FileManager') ? 'EVET' : 'HAYIR') . "</p>";

if (class_exists('FileManager')) {
    $reflection = new ReflectionClass('FileManager');
    echo "<p>Metod var mı: " . ($reflection->hasMethod('getUserUploads') ? 'EVET' : 'HAYIR') . "</p>";
    
    if ($reflection->hasMethod('getUserUploads')) {
        $method = $reflection->getMethod('getUserUploads');
        $params = $method->getParameters();
        echo "<p>Metod parametreleri:</p>";
        echo "<ul>";
        foreach ($params as $param) {
            echo "<li>" . $param->getName() . " (default: " . ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : 'none') . ")</li>";
        }
        echo "</ul>";
    }
}

echo "<br><a href='files.php'>Dosyalar sayfasına git</a>";
?>
