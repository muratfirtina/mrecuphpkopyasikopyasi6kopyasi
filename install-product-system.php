<?php
/**
 * Mr ECU - Product Brands ve Products Sistemi Kurulum
 * Bu dosyayı bir kez çalıştırarak sistemin kurulumunu tamamlayın
 */

require_once 'config/database.php';

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Mr ECU - Product Brands Sistemi Kurulum</h2>";
echo "<hr>";

try {
    echo "<h3>1. Product Brands Tablosu Oluşturuluyor...</h3>";
    
    // Product brands tablosu
    $createProductBrandsTable = "
        CREATE TABLE IF NOT EXISTS product_brands (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            logo VARCHAR(500),
            website VARCHAR(255),
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
    
    $pdo->exec($createProductBrandsTable);
    echo "✅ Product brands tablosu oluşturuldu.<br>";

    echo "<h3>2. Products Tablosuna Brand İlişkisi Ekleniyor...</h3>";
    
    // Products tablosuna brand_id kolonu ekle (eğer yoksa)
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN brand_id INT UNSIGNED DEFAULT NULL AFTER category_id");
        echo "✅ Products tablosuna brand_id kolonu eklendi.<br>";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "⚠️ Brand_id kolonu zaten mevcut.<br>";
        } else {
            throw $e;
        }
    }

    // Index ve foreign key ekle
    try {
        $pdo->exec("ALTER TABLE products ADD INDEX idx_brand_id (brand_id)");
        echo "✅ Brand_id için index eklendi.<br>";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate key name") !== false) {
            echo "⚠️ Brand_id index zaten mevcut.<br>";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE products ADD FOREIGN KEY (brand_id) REFERENCES product_brands(id) ON DELETE SET NULL");
        echo "✅ Brand_id için foreign key eklendi.<br>";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate") !== false || strpos($e->getMessage(), "already exists") !== false) {
            echo "⚠️ Brand_id foreign key zaten mevcut.<br>";
        } else {
            throw $e;
        }
    }

    echo "<h3>3. Product Images Tablosu Kontrol Ediliyor...</h3>";
    
    // Product images tablosunu kontrol et, yoksa oluştur
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'product_images'");
        if ($stmt->rowCount() === 0) {
            $createProductImagesTable = "
                CREATE TABLE product_images (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    product_id INT UNSIGNED NOT NULL,
                    image_path VARCHAR(500) NOT NULL,
                    alt_text VARCHAR(255),
                    sort_order INT DEFAULT 0,
                    is_primary TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    INDEX idx_product_id (product_id),
                    INDEX idx_sort_order (sort_order),
                    INDEX idx_is_primary (is_primary)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createProductImagesTable);
            echo "✅ Product images tablosu oluşturuldu.<br>";
        } else {
            echo "⚠️ Product images tablosu zaten mevcut.<br>";
        }
    } catch(PDOException $e) {
        echo "❌ Product images tablosu hatası: " . $e->getMessage() . "<br>";
    }

    echo "<h3>4. Örnek Marka Verileri Ekleniyor...</h3>";
    
    // Örnek marka verileri
    $sampleBrands = [
        [
            'name' => 'AutoTuner',
            'slug' => 'autotuner',
            'description' => 'Profesyonel ECU programlama ve chip tuning cihazları üreticisi. Otomotiv sektöründe öncü teknolojiler sunmaktadır.',
            'website' => 'https://www.autotuner.com',
            'meta_title' => 'AutoTuner - Profesyonel ECU Programlama Cihazları',
            'meta_description' => 'AutoTuner marka ECU programlama ve chip tuning cihazları. Profesyonel kalitede ürünler ve güvenilir çözümler.',
            'is_featured' => 1,
            'sort_order' => 1
        ],
        [
            'name' => 'KESS V2',
            'slug' => 'kess-v2',
            'description' => 'Gelişmiş ECU programlama araçları ve yazılım çözümleri. OBD ve Bench modu desteği ile kapsamlı hizmet.',
            'website' => 'https://www.alientech.to',
            'meta_title' => 'KESS V2 - Gelişmiş ECU Programlama Araçları',
            'meta_description' => 'KESS V2 ECU programlama cihazları ve yazılım çözümleri. OBD ve Bench modu desteği.',
            'is_featured' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'KTM Flash',
            'slug' => 'ktm-flash',
            'description' => 'Motosiklet ve ATV ECU programlama konusunda uzman çözümler. Güvenilir ve hızlı programlama araçları.',
            'meta_title' => 'KTM Flash - Motosiklet ECU Programlama',
            'meta_description' => 'KTM Flash motosiklet ve ATV ECU programlama araçları. Uzman çözümler ve güvenilir hizmet.',
            'is_featured' => 0,
            'sort_order' => 3
        ],
        [
            'name' => 'PCM Flash',
            'slug' => 'pcm-flash',
            'description' => 'OBD ve Bench modu ECU programlama çözümleri. Geniş araç uyumluluğu ve kullanım kolaylığı.',
            'meta_title' => 'PCM Flash - OBD ECU Programlama Çözümleri',
            'meta_description' => 'PCM Flash OBD ve Bench modu ECU programlama araçları. Geniş uyumluluk ve kolay kullanım.',
            'is_featured' => 0,
            'sort_order' => 4
        ],
        [
            'name' => 'CMD Flash',
            'slug' => 'cmd-flash',
            'description' => 'Professional ECU flashing tools for automotive technicians. Advanced diagnostics and programming solutions.',
            'meta_title' => 'CMD Flash - Professional ECU Tools',
            'meta_description' => 'CMD Flash professional ECU programming tools and diagnostic solutions for automotive professionals.',
            'is_featured' => 0,
            'sort_order' => 5
        ]
    ];

    $addedBrands = 0;
    foreach ($sampleBrands as $brand) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM product_brands WHERE slug = ?");
            $stmt->execute([$brand['slug']]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO product_brands (name, slug, description, website, meta_title, meta_description, is_featured, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([$brand['name'], $brand['slug'], $brand['description'], $brand['website'] ?? null, $brand['meta_title'] ?? null, $brand['meta_description'] ?? null, $brand['is_featured'], $brand['sort_order']]);
                $addedBrands++;
            }
        } catch(PDOException $e) {
            echo "⚠️ {$brand['name']} markası eklenirken hata: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "✅ {$addedBrands} yeni marka eklendi.<br>";

    echo "<h3>5. Örnek Ürün Verileri Ekleniyor...</h3>";
    
    // Örnek ürün verisi
    $sampleProducts = [
        [
            'name' => 'AutoTuner ECU Programlama Cihazı - Güç ve Verimliliği Bir Arada Sunun',
            'slug' => 'autotuner-ecu-programlama-cihazi-guc-ve-verimliligi-bir-arada-sunun',
            'short_description' => 'Profesyonel ECU programlama cihazı ile araç performansınızı artırın. Güvenilir teknoloji, kolay kullanım.',
            'description' => '<h3>AutoTuner ECU Programlama Cihazı</h3>
            <p>AutoTuner ECU programlama cihazı, otomotiv sektöründe profesyonel çözümler sunan, güvenilir ve yenilikçi bir üründür. Bu cihaz sayesinde araçlarınızın performansını optimize edebilir, yakıt tüketimini azaltabilir ve motor gücünü artırabilirsiniz.</p>
            
            <h4>Öne Çıkan Özellikler:</h4>
            <ul>
                <li><strong>Geniş Araç Uyumluluğu:</strong> 1000+ araç modeli desteği</li>
                <li><strong>OBD ve Bench Modu:</strong> İki farklı bağlantı seçeneği</li>
                <li><strong>Kullanıcı Dostu Arayüz:</strong> Kolay kurulum ve kullanım</li>
                <li><strong>Güvenli Programlama:</strong> Automatic backup ve recovery</li>
                <li><strong>Hızlı İşlem:</strong> Saniyeler içinde programlama</li>
                <li><strong>Teknik Destek:</strong> 7/24 uzman desteği</li>
            </ul>
            
            <h4>Teknik Özellikler:</h4>
            <p>Cihaz, en son teknoloji işlemci ve gelişmiş yazılım altyapısı ile donatılmıştır. USB 3.0 bağlantı desteği sayesinde yüksek hızda veri transferi sağlar. Ayrıca WiFi özelliği ile kablosuz bağlantı imkanı sunar.</p>
            
            <h4>Güvenlik ve Garanti:</h4>
            <p>Tüm ürünlerimiz CE sertifikası ile güvenlik standartlarını karşılar. 2 yıl kapsamlı garanti ve ücretsiz yazılım güncellemeleri dahildir.</p>',
            'sku' => 'AT-ECU-001',
            'price' => 15999.99,
            'sale_price' => 12999.99,
            'stock_quantity' => 25,
            'weight' => 1.2,
            'dimensions' => '15x10x5 cm',
            'meta_title' => 'AutoTuner ECU Programlama Cihazı | Profesyonel Chip Tuning',
            'meta_description' => 'AutoTuner ECU programlama cihazı ile araç performansınızı artırın. Geniş araç uyumluluğu, güvenli programlama. ✓ 2 Yıl Garanti ✓ Hızlı Kargo',
            'featured' => 1,
            'is_active' => 1,
            'sort_order' => 1,
            'brand_slug' => 'autotuner'
        ],
        [
            'name' => 'KESS V2 Master Professional ECU Tool',
            'slug' => 'kess-v2-master-professional-ecu-tool',
            'short_description' => 'KESS V2 Master ile profesyonel ECU programlama. OBD ve Bench modu desteği, geniş araç uyumluluğu.',
            'description' => '<h3>KESS V2 Master Professional ECU Tool</h3>
            <p>KESS V2 Master, ECU programlama alanında en gelişmiş çözümlerden biridir. Profesyonel teknisyenler için tasarlanan bu araç, hem OBD hem de Bench modu ile çalışma imkanı sunar.</p>
            
            <h4>Profesyonel Özellikler:</h4>
            <ul>
                <li><strong>Master Lisans:</strong> Sınırsız kullanım hakkı</li>
                <li><strong>Tricore Desteği:</strong> En son nesil ECU\'lar</li>
                <li><strong>GPT Mode:</strong> Gelişmiş programlama teknikleri</li>
                <li><strong>Checksum Düzeltme:</strong> Otomatik hata düzeltme</li>
                <li><strong>Clone Özelliği:</strong> ECU kopyalama imkanı</li>
            </ul>',
            'sku' => 'KESS-V2-MASTER',
            'price' => 25999.99,
            'sale_price' => null,
            'stock_quantity' => 15,
            'weight' => 0.8,
            'dimensions' => '12x8x4 cm',
            'meta_title' => 'KESS V2 Master Professional ECU Tool | ECU Programlama',
            'meta_description' => 'KESS V2 Master professional ECU programlama aracı. OBD ve Bench modu, Tricore desteği. Profesyonel çözümler için ideal.',
            'featured' => 1,
            'is_active' => 1,
            'sort_order' => 2,
            'brand_slug' => 'kess-v2'
        ]
    ];

    $addedProducts = 0;
    foreach ($sampleProducts as $product) {
        try {
            // Markayı bul
            $stmt = $pdo->prepare("SELECT id FROM product_brands WHERE slug = ?");
            $stmt->execute([$product['brand_slug']]);
            $brand = $stmt->fetch();
            $brandId = $brand ? $brand['id'] : null;
            
            // Kategoriyi bul (ilk kategoriyi al)
            $stmt = $pdo->query("SELECT id FROM categories WHERE is_active = 1 LIMIT 1");
            $category = $stmt->fetch();
            $categoryId = $category ? $category['id'] : null;
            
            // Ürünün varlığını kontrol et
            $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
            $stmt->execute([$product['slug']]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO products (name, slug, short_description, description, sku, price, sale_price, stock_quantity, category_id, brand_id, weight, dimensions, featured, is_active, sort_order, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $product['name'], $product['slug'], $product['short_description'], 
                    $product['description'], $product['sku'], $product['price'], 
                    $product['sale_price'], $product['stock_quantity'], $categoryId, 
                    $brandId, $product['weight'], $product['dimensions'], 
                    $product['featured'], $product['is_active'], $product['sort_order'],
                    $product['meta_title'], $product['meta_description']
                ]);
                $addedProducts++;
            }
        } catch(PDOException $e) {
            echo "⚠️ {$product['name']} ürünü eklenirken hata: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "✅ {$addedProducts} yeni ürün eklendi.<br>";

    echo "<h3>6. Upload Klasörleri Kontrol Ediliyor...</h3>";
    
    // Upload klasörlerini oluştur
    $uploadDirs = [
        '../uploads/brands',
        '../uploads/products',
        '../uploads/categories'
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

    echo "<h3>7. Kurulum Tamamlandı!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #155724;'>✅ Başarılı!</h4>";
    echo "<p style='color: #155724;'>Product Brands sistemi başarıyla kuruldu. Artık aşağıdaki özellikleri kullanabilirsiniz:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>✅ Admin panelinden marka yönetimi (/admin/product-brands.php)</li>";
    echo "<li>✅ Ürün yönetiminde marka seçimi (/admin/products.php)</li>";
    echo "<li>✅ Ürün detay sayfalarında marka bilgisi</li>";
    echo "<li>✅ SEO dostu URL yapısı</li>";
    echo "<li>✅ Çoklu resim yükleme desteği</li>";
    echo "</ul>";
    echo "</div>";

    echo "<h4>Sonraki Adımlar:</h4>";
    echo "<ol>";
    echo "<li><strong>Admin Panel:</strong> <a href='/admin/product-brands.php' target='_blank'>Marka Yönetimi</a></li>";
    echo "<li><strong>Ürün Yönetimi:</strong> <a href='/admin/products.php' target='_blank'>Ürün Ekle/Düzenle</a></li>";
    echo "<li><strong>.htaccess:</strong> SEO URL'ler için .htaccess dosyasını güncellemeyi unutmayın</li>";
    echo "<li><strong>Bu dosyayı silin:</strong> Güvenlik için bu kurulum dosyasını silin</li>";
    echo "</ol>";

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

code {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
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
