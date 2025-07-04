<?php
/**
 * getAllUploads Debug - Spesifik debug
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

echo "<h1>🔍 getAllUploads() Debug</h1>";

try {
    $fileManager = new FileManager($pdo);
    
    echo "<h2>1. Parametresiz Test:</h2>";
    $uploads1 = $fileManager->getAllUploads();
    echo "<p>Sonuç sayısı: " . count($uploads1) . "</p>";
    
    echo "<h2>2. Parametreli Test:</h2>";
    $uploads2 = $fileManager->getAllUploads(1, 50);
    echo "<p>Sonuç sayısı: " . count($uploads2) . "</p>";
    
    echo "<h2>3. Status filtreli Test:</h2>";
    $uploads3 = $fileManager->getAllUploads(1, 50, 'pending');
    echo "<p>Sonuç sayısı: " . count($uploads3) . "</p>";
    
    echo "<h2>4. Manuel SQL Test (Aynı sorgu):</h2>";
    
    // Aynı sorguyu manuel çalıştır
    $stmt = $pdo->prepare("
        SELECT fu.*, u.username, u.email, u.phone, b.name as brand_name, m.name as model_name,
               (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as response_count
        FROM file_uploads fu
        LEFT JOIN users u ON fu.user_id = u.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        ORDER BY fu.upload_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([50, 0]);
    $manualResults = $stmt->fetchAll();
    echo "<p>Manuel SQL sonuç sayısı: " . count($manualResults) . "</p>";
    
    if (!empty($manualResults)) {
        echo "<h3>Manuel SQL ilk sonuç:</h3>";
        echo "<pre>" . print_r($manualResults[0], true) . "</pre>";
    }
    
    echo "<h2>5. Status filtreli Manuel SQL:</h2>";
    $stmt = $pdo->prepare("
        SELECT fu.*, u.username, u.email, u.phone, b.name as brand_name, m.name as model_name,
               (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as response_count
        FROM file_uploads fu
        LEFT JOIN users u ON fu.user_id = u.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.status = ?
        ORDER BY fu.upload_date DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(['pending', 50, 0]);
    $manualPending = $stmt->fetchAll();
    echo "<p>Manuel pending SQL sonuç sayısı: " . count($manualPending) . "</p>";
    
    if (!empty($manualPending)) {
        echo "<h3>Manuel pending SQL ilk sonuç:</h3>";
        echo "<pre>" . print_r($manualPending[0], true) . "</pre>";
    }
    
    echo "<h2>6. FileManager Metodunu İnceleme:</h2>";
    
    // FileManager kodunu debug et
    $reflection = new ReflectionClass($fileManager);
    $method = $reflection->getMethod('getAllUploads');
    echo "<p>getAllUploads metodu mevcut: ✅</p>";
    
    // Son bir test - direkt çağrı
    echo "<h2>7. Direkt Çağrı Debug:</h2>";
    
    // Test parametreleri
    $page = 1;
    $limit = 50;
    $status = null;
    
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    $offset = ($page - 1) * $limit;
    
    echo "<p>Test parametreleri:</p>";
    echo "<p>page: $page (type: " . gettype($page) . ")</p>";
    echo "<p>limit: $limit (type: " . gettype($limit) . ")</p>";
    echo "<p>offset: $offset (type: " . gettype($offset) . ")</p>";
    echo "<p>status: " . ($status ?: 'null') . "</p>";
    
    $whereClause = '';
    $params = [];
    
    if ($status) {
        $whereClause = 'WHERE fu.status = ?';
        $params[] = $status;
    }
    
    echo "<p>whereClause: '$whereClause'</p>";
    echo "<p>params before limit: " . print_r($params, true) . "</p>";
    
    $sql = "
        SELECT fu.*, u.username, u.email, u.phone, b.name as brand_name, m.name as model_name,
               (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as response_count
        FROM file_uploads fu
        LEFT JOIN users u ON fu.user_id = u.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        {$whereClause}
        ORDER BY fu.upload_date DESC
        LIMIT ? OFFSET ?
    ";
    
    // Parametreleri sıraya koy
    $params[] = $limit;
    $params[] = $offset;
    
    echo "<p>Final SQL:</p>";
    echo "<pre>" . $sql . "</pre>";
    echo "<p>Final params: " . print_r($params, true) . "</p>";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    echo "<p>Execute result: " . ($result ? 'true' : 'false') . "</p>";
    
    $directResults = $stmt->fetchAll();
    echo "<p>Direct sonuç sayısı: " . count($directResults) . "</p>";
    
    if (!empty($directResults)) {
        echo "<h3>Direct ilk sonuç:</h3>";
        echo "<pre>" . print_r($directResults[0], true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><hr><br>";
echo "<a href='uploads.php'>📁 Uploads sayfasına git</a>";
?>
