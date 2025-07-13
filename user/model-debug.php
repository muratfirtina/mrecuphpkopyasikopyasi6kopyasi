<?php
/**
 * Model AJAX Debug Test
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h2>Model AJAX Debug Test</h2>";

// 1. Veritabanında markaları kontrol et
echo "<h3>1. Markalar Kontrol</h3>";
try {
    $stmt = $pdo->query("SELECT id, name FROM brands ORDER BY name LIMIT 5");
    $brands = $stmt->fetchAll();
    
    echo "<p>Bulunan marka sayısı: " . count($brands) . "</p>";
    
    foreach ($brands as $brand) {
        echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ccc;'>";
        echo "<strong>Marka:</strong> " . htmlspecialchars($brand['name']) . "<br>";
        echo "<strong>ID:</strong> " . $brand['id'] . "<br>";
        echo "<strong>ID Tipi:</strong> " . gettype($brand['id']) . "<br>";
        echo "<strong>GUID Geçerli mi:</strong> " . (isValidUUID($brand['id']) ? 'EVET' : 'HAYIR') . "<br>";
        
        // Bu marka için modelleri kontrol et
        $modelStmt = $pdo->prepare("SELECT COUNT(*) as model_count FROM models WHERE brand_id = ?");
        $modelStmt->execute([$brand['id']]);
        $modelCount = $modelStmt->fetch();
        echo "<strong>Model sayısı:</strong> " . $modelCount['model_count'] . "<br>";
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Marka kontrol hatası: " . $e->getMessage() . "</p>";
}

// 2. FileManager test
echo "<h3>2. FileManager Test</h3>";
try {
    $fileManager = new FileManager($pdo);
    $brands = $fileManager->getBrands();
    
    echo "<p>FileManager ile bulunan marka sayısı: " . count($brands) . "</p>";
    
    if (!empty($brands)) {
        $firstBrand = $brands[0];
        echo "<p>İlk marka: " . htmlspecialchars($firstBrand['name']) . " (ID: " . $firstBrand['id'] . ")</p>";
        
        // Bu marka için modelleri getir
        $models = $fileManager->getModelsByBrand($firstBrand['id']);
        echo "<p>Bu marka için bulunan model sayısı: " . count($models) . "</p>";
        
        if (!empty($models)) {
            echo "<p>İlk model: " . htmlspecialchars($models[0]['name']) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>FileManager hatası: " . $e->getMessage() . "</p>";
}

// 3. AJAX Simulation Test
echo "<h3>3. AJAX Simülasyon Test</h3>";
if (!empty($brands)) {
    $testBrandId = $brands[0]['id'];
    echo "<p>Test edilecek marka ID: " . $testBrandId . "</p>";
    
    // GET parametrelerini simüle et
    $_GET['get_models'] = '1';
    $_GET['brand_id'] = $testBrandId;
    
    try {
        $brandId = sanitize($_GET['brand_id']);
        echo "<p>Sanitize edilmiş brand ID: " . $brandId . "</p>";
        
        // GUID format kontrolü
        if (!isValidUUID($brandId)) {
            echo "<p style='color: red;'>GUID format hatası!</p>";
            echo "<p>Received ID: " . $brandId . "</p>";
            echo "<p>ID Length: " . strlen($brandId) . "</p>";
        } else {
            echo "<p style='color: green;'>GUID format geçerli</p>";
            
            $models = $fileManager->getModelsByBrand($brandId);
            echo "<p>Model sayısı: " . count($models) . "</p>";
            
            if (!empty($models)) {
                echo "<p>JSON çıktısı:</p>";
                echo "<pre>" . json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>AJAX simülasyon hatası: " . $e->getMessage() . "</p>";
    }
    
    unset($_GET['get_models']);
    unset($_GET['brand_id']);
}

// 4. Models tablosu kontrol
echo "<h3>4. Models Tablosu Kontrol</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM models");
    $result = $stmt->fetch();
    echo "<p>Toplam model sayısı: " . $result['total'] . "</p>";
    
    $stmt = $pdo->query("SELECT id, name, brand_id FROM models LIMIT 3");
    $models = $stmt->fetchAll();
    
    foreach ($models as $model) {
        echo "<div style='margin: 5px; padding: 5px; background: #f0f0f0;'>";
        echo "Model: " . htmlspecialchars($model['name']) . " (ID: " . $model['id'] . ", Brand ID: " . $model['brand_id'] . ")";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Models tablo hatası: " . $e->getMessage() . "</p>";
}

?>

<script>
// JavaScript GUID test
function testGUID() {
    console.log('GUID Test başlıyor...');
    
    const testIds = [
        '<?php echo isset($brands[0]['id']) ? $brands[0]['id'] : ''; ?>',
        '12345678-1234-1234-1234-123456789012',
        'invalid-guid',
        ''
    ];
    
    testIds.forEach(id => {
        const isValid = isValidGUID(id);
        console.log(`ID: ${id} - Geçerli: ${isValid}`);
    });
}

function isValidGUID(guid) {
    const guidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
    return guidPattern.test(guid);
}

// Test'i çalıştır
testGUID();
</script>

<p><button onclick="testGUID()">JavaScript GUID Test</button></p>
<p><a href="upload.php">Upload sayfasına dön</a></p>
