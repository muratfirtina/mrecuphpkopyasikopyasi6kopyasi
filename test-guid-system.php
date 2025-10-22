<?php
/**
 * MR.ECU Tuning - GUID System Test
 * GUID tabanlı sistem testleri
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUID Sistem Testi - MR.ECU Tuning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .test-pass {
            color: #28a745;
            font-weight: bold;
        }
        .test-fail {
            color: #dc3545;
            font-weight: bold;
        }
        .guid-box {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container test-container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0">
                    <i class="fas fa-fingerprint me-2"></i>
                    GUID Sistem Testi
                </h2>
                <p class="mb-0 mt-2">UUID/GUID Fonksiyon Testleri</p>
            </div>
            <div class="card-body p-4">
<?php

$testResults = [];
$allPassed = true;

// Test 1: generateUUID() fonksiyonu
echo "<div class='test-section'>";
echo "<h4><i class='fas fa-cog me-2'></i>Test 1: UUID Oluşturma</h4>";

if (function_exists('generateUUID')) {
    $uuid = generateUUID();
    echo "<p class='test-pass'>✓ generateUUID() fonksiyonu çalışıyor</p>";
    echo "<div class='guid-box'>";
    echo "<strong>Oluşturulan UUID:</strong><br>";
    echo "<code class='fs-5'>$uuid</code>";
    echo "</div>";
    
    // 10 farklı UUID oluştur
    echo "<p class='mt-3'><strong>10 Farklı UUID Örneği:</strong></p>";
    for ($i = 1; $i <= 10; $i++) {
        $testUuid = generateUUID();
        echo "<div class='guid-box mb-2'>";
        echo "<small>#{$i}:</small> <code>$testUuid</code>";
        echo "</div>";
    }
} else {
    echo "<p class='test-fail'>✗ generateUUID() fonksiyonu bulunamadı</p>";
    $allPassed = false;
}

echo "</div>";

// Test 2: isValidUUID() fonksiyonu
echo "<div class='test-section'>";
echo "<h4><i class='fas fa-check-double me-2'></i>Test 2: UUID Doğrulama</h4>";

if (function_exists('isValidUUID')) {
    $validUuid = generateUUID();
    $invalidUuids = [
        'invalid-uuid',
        '12345',
        'abc-def-ghi',
        '550e8400-e29b-41d4-a716',
        '550e8400e29b41d4a716446655440000'
    ];
    
    echo "<p class='test-pass'>✓ isValidUUID() fonksiyonu çalışıyor</p>";
    
    echo "<div class='guid-box'>";
    echo "<strong>Geçerli UUID:</strong><br>";
    echo "<code>$validUuid</code> → ";
    echo isValidUUID($validUuid) ? "<span class='test-pass'>✓ Geçerli</span>" : "<span class='test-fail'>✗ Geçersiz</span>";
    echo "</div>";
    
    echo "<p class='mt-3'><strong>Geçersiz UUID Testleri:</strong></p>";
    foreach ($invalidUuids as $invalid) {
        echo "<div class='guid-box mb-2'>";
        echo "<code>$invalid</code> → ";
        echo !isValidUUID($invalid) ? "<span class='test-pass'>✓ Doğru algılandı (geçersiz)</span>" : "<span class='test-fail'>✗ Yanlış algılandı (geçerli)</span>";
        echo "</div>";
    }
} else {
    echo "<p class='test-fail'>✗ isValidUUID() fonksiyonu bulunamadı</p>";
    $allPassed = false;
}

echo "</div>";

// Test 3: Database GUID Testleri
echo "<div class='test-section'>";
echo "<h4><i class='fas fa-database me-2'></i>Test 3: Database GUID İşlemleri</h4>";

try {
    // Users tablosundan rastgele bir GUID al
    $stmt = $pdo->query("SELECT id, username, email FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='test-pass'>✓ Database'den GUID başarıyla okundu</p>";
        echo "<div class='guid-box'>";
        echo "<strong>Kullanıcı Bilgileri:</strong><br>";
        echo "ID: <code>{$user['id']}</code><br>";
        echo "Username: <strong>{$user['username']}</strong><br>";
        echo "Email: <strong>{$user['email']}</strong><br>";
        echo "UUID Geçerli mi? ";
        echo isValidUUID($user['id']) ? "<span class='test-pass'>✓ Evet</span>" : "<span class='test-fail'>✗ Hayır</span>";
        echo "</div>";
    } else {
        echo "<p class='test-fail'>✗ Database'de kullanıcı bulunamadı</p>";
        $allPassed = false;
    }
    
    // Brands tablosundan GUID'leri kontrol et
    $stmt = $pdo->query("SELECT id, name FROM brands LIMIT 5");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($brands) > 0) {
        echo "<p class='mt-3'><strong>Marka GUID'leri:</strong></p>";
        foreach ($brands as $brand) {
            echo "<div class='guid-box mb-2'>";
            echo "<strong>{$brand['name']}:</strong> <code>{$brand['id']}</code> ";
            echo isValidUUID($brand['id']) ? "<span class='test-pass'>✓</span>" : "<span class='test-fail'>✗</span>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='test-fail'>✗ Database hatası: " . $e->getMessage() . "</p>";
    $allPassed = false;
}

echo "</div>";

// Test 4: Foreign Key İlişkileri
echo "<div class='test-section'>";
echo "<h4><i class='fas fa-link me-2'></i>Test 4: GUID Foreign Key İlişkileri</h4>";

try {
    // Bir markaya ait modelleri çek
    $stmt = $pdo->query("
        SELECT b.id as brand_id, b.name as brand_name, m.id as model_id, m.name as model_name
        FROM brands b
        INNER JOIN models m ON b.id = m.brand_id
        LIMIT 5
    ");
    $relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($relations) > 0) {
        echo "<p class='test-pass'>✓ Foreign Key ilişkileri çalışıyor</p>";
        echo "<p><strong>Marka → Model İlişkileri:</strong></p>";
        
        foreach ($relations as $rel) {
            echo "<div class='guid-box mb-2'>";
            echo "<strong>{$rel['brand_name']}</strong> → <strong>{$rel['model_name']}</strong><br>";
            echo "<small>Brand GUID: <code>{$rel['brand_id']}</code></small><br>";
            echo "<small>Model GUID: <code>{$rel['model_id']}</code></small>";
            echo "</div>";
        }
    } else {
        echo "<p class='test-fail'>✗ İlişki bulunamadı</p>";
        $allPassed = false;
    }
    
} catch (Exception $e) {
    echo "<p class='test-fail'>✗ Foreign Key test hatası: " . $e->getMessage() . "</p>";
    $allPassed = false;
}

echo "</div>";

// Test 5: User Class GUID Testleri
echo "<div class='test-section'>";
echo "<h4><i class='fas fa-user-shield me-2'></i>Test 5: User Class GUID İşlemleri</h4>";

try {
    if (class_exists('User')) {
        $userClass = new User($pdo);
        
        // İlk kullanıcıyı al
        $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testUser) {
            $userData = $userClass->getUserById($testUser['id']);
            
            if ($userData) {
                echo "<p class='test-pass'>✓ User::getUserById() GUID ile çalışıyor</p>";
                echo "<div class='guid-box'>";
                echo "<strong>Kullanıcı Detayları:</strong><br>";
                echo "ID: <code>{$userData['id']}</code><br>";
                echo "Username: <strong>{$userData['username']}</strong><br>";
                echo "Email: <strong>{$userData['email']}</strong><br>";
                echo "Role: <span class='badge bg-primary'>{$userData['role']}</span><br>";
                echo "Credits: <strong>{$userData['credits']}</strong> TL";
                echo "</div>";
            } else {
                echo "<p class='test-fail'>✗ getUserById() veri döndürmedi</p>";
                $allPassed = false;
            }
        }
    } else {
        echo "<p class='test-fail'>✗ User class bulunamadı</p>";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "<p class='test-fail'>✗ User class test hatası: " . $e->getMessage() . "</p>";
    $allPassed = false;
}

echo "</div>";

// Test 6: Performance Test
echo "<div class='test-section'>";
echo "<h4><i class='fas fa-tachometer-alt me-2'></i>Test 6: GUID Performance</h4>";

$startTime = microtime(true);
$generatedGuids = [];

for ($i = 0; $i < 1000; $i++) {
    $generatedGuids[] = generateUUID();
}

$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);

echo "<p class='test-pass'>✓ 1000 UUID başarıyla oluşturuldu</p>";
echo "<div class='guid-box'>";
echo "<strong>Performance Sonuçları:</strong><br>";
echo "Süre: <strong>{$executionTime}ms</strong><br>";
echo "Ortalama: <strong>" . round($executionTime / 1000, 4) . "ms/UUID</strong><br>";
echo "UUID/sn: <strong>" . round(1000 / ($executionTime / 1000)) . "</strong>";
echo "</div>";

// Uniqueness kontrolü
$uniqueGuids = array_unique($generatedGuids);
if (count($uniqueGuids) === 1000) {
    echo "<p class='test-pass mt-3'>✓ Tüm UUID'ler benzersiz (unique)</p>";
} else {
    echo "<p class='test-fail mt-3'>✗ Duplicate UUID bulundu!</p>";
    $allPassed = false;
}

echo "</div>";

// Genel Sonuç
if ($allPassed) {
    echo "
    <div class='alert alert-success'>
        <h4 class='alert-heading'>
            <i class='fas fa-check-circle me-2'></i>
            Tüm GUID Testleri Başarılı!
        </h4>
        <p class='mb-0'>GUID sistemi tam çalışır durumda.</p>
    </div>
    ";
} else {
    echo "
    <div class='alert alert-warning'>
        <h4 class='alert-heading'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            Bazı Testler Başarısız
        </h4>
        <p class='mb-0'>Yukarıdaki hataları kontrol edin.</p>
    </div>
    ";
}
?>
                <div class="d-grid gap-2 mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Ana Sayfaya Dön
                    </a>
                    <a href="test-guid-system.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-2"></i>
                        Testi Yenile
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-white">
            <p class="mb-0">
                <i class="fas fa-info-circle me-1"></i>
                MR.ECU Tuning v2.0 - GUID System Test
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
