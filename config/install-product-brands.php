<?php
/**
 * Mr ECU - Product Brands Tablosu Kurulum
 */

require_once 'database.php';

try {
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
    echo "Product brands tablosu oluşturuldu.\n";

    // Products tablosuna brand_id kolonu ekle (eğer yoksa)
    try {
        $addBrandIdColumn = "
            ALTER TABLE products 
            ADD COLUMN brand_id INT UNSIGNED DEFAULT NULL AFTER category_id,
            ADD INDEX idx_brand_id (brand_id),
            ADD FOREIGN KEY (brand_id) REFERENCES product_brands(id) ON DELETE SET NULL";
        
        $pdo->exec($addBrandIdColumn);
        echo "Products tablosuna brand_id kolonu eklendi.\n";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), "Duplicate column name") !== false) {
            echo "Brand_id kolonu zaten mevcut.\n";
        } else {
            echo "Brand_id kolonu eklenirken hata: " . $e->getMessage() . "\n";
        }
    }

    // Örnek marka verileri ekle
    $sampleBrands = [
        [
            'name' => 'AutoTuner',
            'slug' => 'autotuner',
            'description' => 'Profesyonel ECU programlama ve chip tuning cihazları üreticisi',
            'is_featured' => 1,
            'sort_order' => 1
        ],
        [
            'name' => 'KESS V2',
            'slug' => 'kess-v2',
            'description' => 'Gelişmiş ECU programlama araçları ve yazılım çözümleri',
            'is_featured' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'KTM Flash',
            'slug' => 'ktm-flash',
            'description' => 'Motosiklet ve ATV ECU programlama uzmanı',
            'is_featured' => 0,
            'sort_order' => 3
        ],
        [
            'name' => 'PCM Flash',
            'slug' => 'pcm-flash',
            'description' => 'OBD ve Bench modu ECU programlama çözümleri',
            'is_featured' => 0,
            'sort_order' => 4
        ]
    ];

    foreach ($sampleBrands as $brand) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO product_brands (name, slug, description, is_featured, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$brand['name'], $brand['slug'], $brand['description'], $brand['is_featured'], $brand['sort_order']]);
    }
    echo "Örnek marka verileri eklendi.\n";

    echo "\n✅ Product Brands tablosu kurulumu tamamlandı!\n";

} catch(PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
?>
