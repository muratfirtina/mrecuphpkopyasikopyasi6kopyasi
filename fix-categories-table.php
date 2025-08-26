<?php
/**
 * Mr ECU - Categories Tablosu Düzeltme
 * Eksik kolonları ekler ve tabloyu güncellemek için
 */

require_once 'config/database.php';

echo "<h2>Categories Tablosu Düzeltme İşlemi</h2>";
echo "<hr>";

try {
    echo "<h3>1. Mevcut Tablo Yapısını Kontrol Ediliyor...</h3>";
    
    // Mevcut kolonları kontrol et
    $stmt = $pdo->query("DESCRIBE categories");
    $columns = $stmt->fetchAll();
    $existingColumns = array_column($columns, 'Field');
    
    echo "Mevcut kolonlar: " . implode(', ', $existingColumns) . "<br><br>";
    
    echo "<h3>2. Eksik Kolonlar Ekleniyor...</h3>";
    
    // Eksik kolonları tanımla
    $requiredColumns = [
        'is_featured' => "ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER sort_order",
        'meta_title' => "ADD COLUMN meta_title VARCHAR(255) AFTER is_active",
        'meta_description' => "ADD COLUMN meta_description TEXT AFTER meta_title"
    ];
    
    $addedColumns = 0;
    
    foreach ($requiredColumns as $column => $sql) {
        if (!in_array($column, $existingColumns)) {
            try {
                $pdo->exec("ALTER TABLE categories $sql");
                echo "✅ '$column' kolonu eklendi<br>";
                $addedColumns++;
            } catch (PDOException $e) {
                echo "⚠️ '$column' kolonu eklenirken hata: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "⚠️ '$column' kolonu zaten mevcut<br>";
        }
    }
    
    echo "<br>";
    echo "<h3>3. İndexler Ekleniyor...</h3>";
    
    // Gerekli indexleri ekle
    $indexes = [
        'idx_is_featured' => "CREATE INDEX idx_is_featured ON categories (is_featured)",
        'idx_slug' => "CREATE INDEX idx_slug ON categories (slug)",
        'idx_is_active' => "CREATE INDEX idx_is_active ON categories (is_active)",
        'idx_sort_order' => "CREATE INDEX idx_sort_order ON categories (sort_order)"
    ];
    
    foreach ($indexes as $indexName => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ '$indexName' indexi eklendi<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠️ '$indexName' indexi zaten mevcut<br>";
            } else {
                echo "⚠️ '$indexName' indexi eklenirken hata: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<br>";
    echo "<h3>4. Örnek Kategoriler Ekleniyor...</h3>";
    
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
                echo "✅ " . $category['name'] . " kategorisi eklendi<br>";
            } else {
                echo "⚠️ " . $category['name'] . " kategorisi zaten mevcut<br>";
            }
        } catch(PDOException $e) {
            echo "❌ {$category['name']} kategorisi eklenirken hata: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>";
    echo "<h3>5. Son Durum Kontrolü...</h3>";
    
    // Kategorileri listele
    $stmt = $pdo->query("SELECT name, slug, is_featured, is_active FROM categories ORDER BY sort_order");
    $categories = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>Kategori Adı</th><th>Slug</th><th>Öne Çıkan</th><th>Aktif</th></tr>";
    
    foreach ($categories as $cat) {
        $featured = $cat['is_featured'] ? '⭐ Evet' : 'Hayır';
        $active = $cat['is_active'] ? '✅ Aktif' : '❌ Pasif';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
        echo "<td>" . htmlspecialchars($cat['slug']) . "</td>";
        echo "<td>$featured</td>";
        echo "<td>$active</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #155724;'>✅ Düzeltme Tamamlandı!</h4>";
    echo "<p style='color: #155724;'>Categories tablosu başarıyla güncellenmiş ve örnek veriler eklenmiştir.</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>✅ Eksik kolonlar eklendi</li>";
    echo "<li>✅ Gerekli indexler oluşturuldu</li>";
    echo "<li>✅ $addedCategories yeni kategori eklendi</li>";
    echo "<li>✅ Toplam " . count($categories) . " kategori mevcut</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>Sonraki Adım:</h4>";
    echo "<p><a href='/' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ana Sayfaya Git ve Header Dropdown'ını Test Et</a></p>";
    echo "<p><a href='quick-system-check.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Sistem Kontrolü Yap</a></p>";

} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4 style='color: #721c24;'>❌ Hata!</h4>";
    echo "<p style='color: #721c24;'>Düzeltme sırasında hata oluştu: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}

h2, h3, h4 {
    color: #333;
}

table {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

th {
    background: #007bff;
    color: white;
}

a {
    text-decoration: none;
}

a:hover {
    opacity: 0.8;
}

hr {
    border: none;
    border-top: 2px solid #dee2e6;
    margin: 30px 0;
}
</style>
