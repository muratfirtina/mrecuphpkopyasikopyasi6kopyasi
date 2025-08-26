<?php
/**
 * Mr ECU - Ürünler Dropdown Sistemi Test Sayfası
 * Kurulumu test etmek ve demo yapmak için
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Ürünler Dropdown Sistemi Test - Mr ECU</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { padding: 2rem 0; background: #f8f9fa; }
        .test-card { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .test-success { border-left: 4px solid #28a745; background: #d4edda; }
        .test-warning { border-left: 4px solid #ffc107; background: #fff3cd; }
        .test-error { border-left: 4px solid #dc3545; background: #f8d7da; }
        .test-info { border-left: 4px solid #17a2b8; background: #d1ecf1; }
        .btn-test { margin: 0.25rem; }
    </style>
</head>
<body>";

echo "<div class='container'>
    <div class='row justify-content-center'>
        <div class='col-lg-10'>";

echo "<div class='test-card'>
    <h1 class='text-center mb-4'>
        <i class='fas fa-shopping-bag text-primary me-3'></i>
        Ürünler Dropdown Sistemi Test
    </h1>
    <p class='text-center lead text-muted'>
        Header'da Ürünler dropdown menüsü ve ilgili sayfa yapısının test paneli
    </p>
</div>";

// 1. Veritabanı Bağlantı Testi
echo "<div class='test-card test-info'>
    <h3><i class='fas fa-database me-2'></i>1. Veritabanı Bağlantısı</h3>";

try {
    if ($pdo) {
        echo "<div class='alert alert-success'>✅ Veritabanı bağlantısı başarılı</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Veritabanı bağlantısı başarısız</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Veritabanı hatası: " . $e->getMessage() . "</div>";
}

echo "</div>";

// 2. Tablo Varlık Kontrolü
echo "<div class='test-card test-info'>
    <h3><i class='fas fa-table me-2'></i>2. Tablo Varlık Kontrolü</h3>";

$requiredTables = ['categories', 'products', 'product_brands', 'product_images'];
$tablesStatus = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $tablesStatus[$table] = true;
            echo "<div class='alert alert-success mb-2'>✅ <strong>$table</strong> tablosu mevcut</div>";
        } else {
            $tablesStatus[$table] = false;
            echo "<div class='alert alert-warning mb-2'>⚠️ <strong>$table</strong> tablosu bulunamadı</div>";
        }
    } catch (Exception $e) {
        $tablesStatus[$table] = false;
        echo "<div class='alert alert-danger mb-2'>❌ <strong>$table</strong> tablosu kontrol hatası</div>";
    }
}

echo "</div>";

// 3. Veri Kontrolü
echo "<div class='test-card test-info'>
    <h3><i class='fas fa-chart-bar me-2'></i>3. Veri Kontrolü</h3>";

$dataCounts = [];

foreach ($requiredTables as $table) {
    if ($tablesStatus[$table]) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            $dataCounts[$table] = $count;
            
            $alertClass = $count > 0 ? 'alert-success' : 'alert-warning';
            $icon = $count > 0 ? '✅' : '⚠️';
            echo "<div class='alert $alertClass mb-2'>$icon <strong>$table:</strong> $count kayıt</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger mb-2'>❌ <strong>$table</strong> veri sayma hatası</div>";
        }
    }
}

echo "</div>";

// 4. Kurulum Dosyaları
echo "<div class='test-card test-warning'>
    <h3><i class='fas fa-tools me-2'></i>4. Kurulum İşlemleri</h3>
    <p>Aşağıdaki kurulum dosyalarını çalıştırarak sistemi kurabilirsiniz:</p>";

if (!$tablesStatus['categories'] || $dataCounts['categories'] == 0) {
    echo "<a href='install-categories-system.php' class='btn btn-primary btn-test' target='_blank'>
        <i class='fas fa-tags me-2'></i>Categories Sistemi Kur
    </a>";
}

if (!$tablesStatus['product_brands'] || $dataCounts['product_brands'] == 0) {
    echo "<a href='install-product-system.php' class='btn btn-success btn-test' target='_blank'>
        <i class='fas fa-award me-2'></i>Product Brands Sistemi Kur
    </a>";
}

echo "<p class='mt-3 text-muted'>
    <i class='fas fa-info-circle me-2'></i>
    Kurulum dosyalarını çalıştırdıktan sonra güvenlik için silin.
</p>";

echo "</div>";

// 5. Sayfa Testleri
echo "<div class='test-card test-success'>
    <h3><i class='fas fa-globe me-2'></i>5. Sayfa Testleri</h3>
    <div class='row'>";

$testPages = [
    ['name' => 'Ana Sayfa', 'url' => '/', 'desc' => 'Header dropdown\'ı test edin'],
    ['name' => 'Tüm Ürünler', 'url' => '/urunler', 'desc' => 'Ürün listesi sayfası'],
    ['name' => 'ECU Kategorisi', 'url' => '/kategori/ecu-programlama-cihazlari', 'desc' => 'Kategori sayfası örneği'],
    ['name' => 'AutoTuner ECU', 'url' => '/kategori/ecu-programlama-cihazlari/marka/autotuner', 'desc' => 'Kategori+marka sayfası'],
    ['name' => 'Ürün Detayı', 'url' => '/urun/autotuner-ecu-programlama-cihazi-guc-ve-verimliligi-bir-arada-sunun', 'desc' => 'Ürün detay sayfası']
];

foreach ($testPages as $page) {
    echo "<div class='col-lg-6 mb-3'>
        <div class='card h-100'>
            <div class='card-body'>
                <h5 class='card-title'>{$page['name']}</h5>
                <p class='card-text text-muted'>{$page['desc']}</p>
                <a href='{$page['url']}' class='btn btn-outline-primary' target='_blank'>
                    <i class='fas fa-external-link-alt me-2'></i>Test Et
                </a>
            </div>
        </div>
    </div>";
}

echo "</div></div>";

// 6. Dropdown Test Kodu
if ($tablesStatus['categories'] && $dataCounts['categories'] > 0) {
    echo "<div class='test-card test-success'>
        <h3><i class='fas fa-code me-2'></i>6. Header Dropdown Test</h3>
        <p>Header'daki dropdown menü şu şekilde çalışıyor:</p>";
    
    try {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
            WHERE c.is_active = 1 
            GROUP BY c.id 
            HAVING product_count > 0
            ORDER BY c.sort_order, c.name
            LIMIT 10
        ");
        $headerCategories = $stmt->fetchAll();
        
        if (!empty($headerCategories)) {
            echo "<div class='alert alert-success'>";
            echo "<h5>Dropdown'da Görünecek Kategoriler:</h5>";
            echo "<ul>";
            foreach ($headerCategories as $category) {
                echo "<li><strong>" . htmlspecialchars($category['name']) . "</strong> ({$category['product_count']} ürün)</li>";
            }
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>Dropdown'da gösterilecek aktif kategori bulunamadı.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Dropdown test hatası: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// 7. Sistem Durumu Özet
echo "<div class='test-card test-info'>
    <h3><i class='fas fa-check-circle me-2'></i>7. Sistem Durumu</h3>";

$allTablesOk = array_reduce($tablesStatus, function($carry, $item) { return $carry && $item; }, true);
$hasData = array_sum($dataCounts) > 0;

if ($allTablesOk && $hasData) {
    echo "<div class='alert alert-success text-center'>
        <h4>🎉 Sistem Hazır!</h4>
        <p>Tüm tablolar mevcut ve veriler yüklenmiş. Ana sayfaya gidip header'daki 'Ürünler' dropdown'ını test edebilirsiniz.</p>
        <a href='/' class='btn btn-success btn-lg'>
            <i class='fas fa-home me-2'></i>Ana Sayfaya Git
        </a>
    </div>";
} else if ($allTablesOk && !$hasData) {
    echo "<div class='alert alert-warning text-center'>
        <h4>⚠️ Veri Eksik</h4>
        <p>Tablolar mevcut ancak örnek veriler yüklenmemiş. Kurulum dosyalarını çalıştırın.</p>
    </div>";
} else {
    echo "<div class='alert alert-danger text-center'>
        <h4>❌ Sistem Eksik</h4>
        <p>Bazı tablolar eksik. Kurulum dosyalarını çalıştırarak sistemi kurun.</p>
    </div>";
}

echo "</div>";

echo "</div></div></div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";
?>
