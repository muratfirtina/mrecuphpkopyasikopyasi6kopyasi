<?php
/**
 * Brands Debug Sayfası
 */

// Hata gösterimi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Brands Debug</h1>";

try {
    echo "<p>1. Config yükleniyor...</p>";
    require_once __DIR__ . '/../config/config.php';
    echo "<p>✅ Config OK</p>";

    echo "<p>2. TuningModel yükleniyor...</p>";
    require_once __DIR__ . '/../includes/TuningModel.php';
    echo "<p>✅ TuningModel OK</p>";

    echo "<p>3. Admin kontrolü...</p>";
    if (!isLoggedIn() || !isAdmin()) {
        echo "<p>❌ Admin girişi gerekli!</p>";
        exit;
    }
    echo "<p>✅ Admin OK</p>";

    echo "<p>4. createSlug fonksiyonu kontrol...</p>";
    if (function_exists('createSlug')) {
        echo "<p>✅ createSlug mevcut</p>";
        $test = createSlug('Test Marka');
        echo "<p>Test slug: $test</p>";
    } else {
        echo "<p>❌ createSlug fonksiyonu yok!</p>";
    }

    echo "<p>5. generateUUID fonksiyonu kontrol...</p>";
    if (function_exists('generateUUID')) {
        echo "<p>✅ generateUUID mevcut</p>";
        $testUuid = generateUUID();
        echo "<p>Test UUID: $testUuid</p>";
    } else {
        echo "<p>❌ generateUUID fonksiyonu yok!</p>";
    }

    echo "<p>6. Database bağlantısı...</p>";
    $tuning = new TuningModel($pdo);
    echo "<p>✅ Database OK</p>";

    echo "<p>7. Brands tablosunu kontrol...</p>";
    $brands = $tuning->getAllBrands();
    echo "<p>✅ " . count($brands) . " marka bulundu</p>";

    // Test marka ekleme
    echo "<p>8. Test marka ekleme...</p>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_add'])) {
        $name = 'Test Marka ' . date('H:i:s');
        $slug = createSlug($name);
        $brandId = generateUUID();
        
        $stmt = $pdo->prepare("INSERT INTO brands (id, name, slug) VALUES (?, ?, ?)");
        if ($stmt->execute([$brandId, $name, $slug])) {
            echo "<p>✅ Test marka eklendi: $name</p>";
        } else {
            echo "<p>❌ Test marka eklenemedi</p>";
            print_r($stmt->errorInfo());
        }
    }

} catch (Exception $e) {
    echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>

<form method="post">
    <button type="submit" name="test_add">Test Marka Ekle</button>
</form>

<hr>
<a href="brands.php">← Brands Sayfasına Dön</a>
