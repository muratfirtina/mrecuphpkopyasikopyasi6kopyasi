<?php
/**
 * Mr ECU - Categories ve Örnek Veri Kurulum Sistemi
 * Bu dosyayı bir kez çalıştırarak categories tablosunu ve örnek verileri oluşturun
 */

require_once 'config/database.php';

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Mr ECU - Categories Sistemi Kurulum</h2>";
echo "<hr>";

try {
    echo "<h3>1. Categories Tablosu Kontrol Ediliyor...</h3>";
    
    // Categories tablosunu kontrol et, yoksa oluştur
    $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
    if ($stmt->rowCount() === 0) {
        $createCategoriesTable = "
            CREATE TABLE categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                image VARCHAR(500),
                is_featured TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                meta_title VARCHAR(255),
                meta_description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_is_active (is_active),
                INDEX idx_sort_order (sort_order),
                INDEX idx_is_featured (is_featured)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createCategoriesTable);
        echo "✅ Categories tablosu oluşturuldu.<br>";
    } else {
        echo "⚠️ Categories tablosu zaten mevcut.<br>";
    }

    echo "<h3>2. Örnek Kategori Verileri Ekleniyor...</h3>";
    
    // Örnek kategori verileri
    $sampleCategories = [
        [
            'name' => 'ECU Programlama Cihazları',
            'slug' => 'ecu-programlama-cihazlari',
            'description' => 'Profesyonel ECU programlama ve chip tuning cihazları. OBD ve Bench modu desteği ile geniş araç uyumluluğu sunan güvenilir çözümler.',
            'meta_title' => 'ECU Programlama Cihazları | Profesyonel Chip Tuning Araçları',
            'meta_description' => 'ECU programlama cihazları ile araç performansınızı artırın. AutoTuner, KESS, KTM Flash ve daha fazlası. Profesyonel kalitede ürünler.',
            'is_featured' => 1,
            'sort_order' => 1
        ],
        [
            'name' => 'OBD Tarayıcıları',
            'slug' => 'obd-tarayicilari',
            'description' => 'OBD2 tarayıcıları ve teşhis araçları. Araç hatalarını tespit etmek ve temizlemek için professional çözümler.',
            'meta_title' => 'OBD Tarayıcıları | Araç Teşhis Araçları',
            'meta_description' => 'OBD2 tarayıcıları ile araç hatalarını tespit edin. Professional teşhis araçları ve okuyucular. Güvenilir kalitede ürünler.',
            'is_featured' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'Immobilizer Araçları',
            'slug' => 'immobilizer-araclari',
            'description' => 'Immobilizer bypass ve programlama araçları. Güvenlik sistemleri ile ilgili profesyonel çözümler.',
            'meta_title' => 'Immobilizer Araçları | Güvenlik Sistemi Çözümleri',
            'meta_description' => 'Immobilizer bypass ve programlama araçları. Güvenlik sistemi çözümleri için profesyonel araçlar. Güvenilir teknoloji.',
            'is_featured' => 0,
            'sort_order' => 3
        ],
        [
            'name' => 'TCU Programlama',
            'slug' => 'tcu-programlama',
            'description' => 'TCU (Transmission Control Unit) programlama araçları. Şanzıman kontrolü ve optimizasyon çözümleri.',
            'meta_title' => 'TCU Programlama Araçları | Şanzıman Kontrol Ünitesi',
            'meta_description' => 'TCU programlama araçları ile şanzıman performansınızı optimize edin. Transmission control unit çözümleri.',
            'is_featured' => 0,
            'sort_order' => 4
        ],
        [
            'name' => 'Kilometre Sıfırlama',
            'slug' => 'kilometre-sifirlama',
            'description' => 'Kilometre düzeltme ve sıfırlama araçları. Dashboard ve ECU kilometre verilerini düzenlemek için professional çözümler.',
            'meta_title' => 'Kilometre Sıfırlama Araçları | Odomet Düzeltme',
            'meta_description' => 'Kilometre sıfırlama ve düzeltme araçları. Dashboard kilometre verilerini düzenlemek için güvenilir çözümler.',
            'is_featured' => 0,
            'sort_order' => 5
        ],
        [
            'name' => 'Yazılım ve Lisanslar',
            'slug' => 'yazilim-ve-lisanslar',
            'description' => 'ECU programlama yazılımları ve lisanslar. Çeşitli markalar için özel yazılım çözümleri ve güncellemeler.',
            'meta_title' => 'ECU Yazılım ve Lisanslar | Programlama Yazılımları',
            'meta_description' => 'ECU programlama yazılımları ve lisanslar. AutoTuner, KESS, KTM Flash yazılımları ve güncellemeleri.',
            'is_featured' => 1,
            'sort_order' => 6
        ]
    ];

    $addedCategories = 0;
    foreach ($sampleCategories as $category) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$category['slug']]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, meta_title, meta_description, is_featured, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([
                    $category['name'], 
                    $category['slug'], 
                    $category['description'], 
                    $category['meta_title'], 
                    $category['meta_description'], 
                    $category['is_featured'], 
                    $category['sort_order']
                ]);
                $addedCategories++;
            }
        } catch(PDOException $e) {
            echo "⚠️ {$category['name']} kategorisi eklenirken hata: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "✅ {$addedCategories} yeni kategori eklendi.<br>";

    echo "<h3>3. Mevcut Ürünlere Kategori Ataması...</h3>";
    
    // Products tablosunu kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() > 0) {
        // Kategorisi olmayan ürünleri ilk kategoriye ata
        $stmt = $pdo->query("SELECT id FROM categories WHERE is_active = 1 ORDER BY sort_order LIMIT 1");
        $firstCategory = $stmt->fetch();
        
        if ($firstCategory) {
            $stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE category_id IS NULL OR category_id = 0");
            $stmt->execute([$firstCategory['id']]);
            $updatedProducts = $stmt->rowCount();
            echo "✅ {$updatedProducts} ürüne kategori atandı.<br>";
        }
    } else {
        echo "⚠️ Products tablosu bulunamadı. Önce ürün sistemini kurun.<br>";
    }

    echo "<h3>4. Upload Klasörleri Kontrol Ediliyor...</h3>";
    
    // Upload klasörlerini oluştur
    $uploadDirs = [
        './uploads/categories'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "✅ {$dir} klasörü oluşturuldu.<br>";
            } else {
                echo "❌ {$dir} klasörü oluşturulamadı.<br>";
            }
        } else {
            echo "⚠️ {$dir} klasörü zaten mevcut.<br>";
        }
    }

    echo "<h3>5. Kurulum Tamamlandı!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #155724;'>✅ Başarılı!</h4>";
    echo "<p style='color: #155724;'>Categories sistemi başarıyla kuruldu. Artık aşağıdaki özellikler kullanılabilir:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>✅ Header'da Ürünler dropdown menüsü</li>";
    echo "<li>✅ Kategori sayfaları (/kategori/kategori-slug)</li>";
    echo "<li>✅ Kategori-Marka sayfaları (/kategori/kategori-slug/marka/marka-slug)</li>";
    echo "<li>✅ SEO dostu URL yapısı</li>";
    echo "<li>✅ Breadcrumb navigasyon</li>";
    echo "</ul>";
    echo "</div>";

    echo "<h4>Sonraki Adımlar:</h4>";
    echo "<ol>";
    echo "<li><strong>Test:</strong> Ana sayfaya gidip header'daki 'Ürünler' dropdown'ını test edin</li>";
    echo "<li><strong>İçerik:</strong> Admin panelinden kategori açıklamalarını ve resimlerini ekleyin</li>";
    echo "<li><strong>Ürünler:</strong> Ürünleri doğru kategorilere atayın</li>";
    echo "<li><strong>Bu dosyayı silin:</strong> Güvenlik için bu kurulum dosyasını silin</li>";
    echo "</ol>";

    echo "<h4>Test Linkleri:</h4>";
    echo "<ul>";
    echo "<li><a href='/' target='_blank'>Ana Sayfa (Header dropdown'ı test edin)</a></li>";
    echo "<li><a href='/kategori/ecu-programlama-cihazlari' target='_blank'>ECU Programlama Cihazları Kategorisi</a></li>";
    echo "<li><a href='/urunler' target='_blank'>Tüm Ürünler</a></li>";
    echo "</ul>";

} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #721c24;'>❌ Hata!</h4>";
    echo "<p style='color: #721c24;'>Kurulum sırasında hata oluştu: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}

h2, h3, h4 {
    color: #333;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

hr {
    border: none;
    border-top: 2px solid #dee2e6;
    margin: 30px 0;
}
</style>
