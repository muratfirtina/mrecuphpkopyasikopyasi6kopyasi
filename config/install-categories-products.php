<?php
/**
 * Mr ECU - Kategori ve Ürün Tabloları Kurulum
 */

require_once 'database.php';

try {
    // Kategoriler tablosu
    $createCategoriesTable = "
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            image VARCHAR(500),
            parent_id INT DEFAULT NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            meta_title VARCHAR(255),
            meta_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
            INDEX idx_parent_id (parent_id),
            INDEX idx_slug (slug),
            INDEX idx_is_active (is_active),
            INDEX idx_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createCategoriesTable);
    echo "Kategoriler tablosu oluşturuldu.\n";

    // Ürünler tablosu
    $createProductsTable = "
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            short_description TEXT,
            sku VARCHAR(100) UNIQUE,
            price DECIMAL(10,2) DEFAULT 0.00,
            sale_price DECIMAL(10,2) DEFAULT NULL,
            stock_quantity INT DEFAULT 0,
            manage_stock TINYINT(1) DEFAULT 1,
            stock_status ENUM('in_stock', 'out_of_stock', 'on_backorder') DEFAULT 'in_stock',
            weight DECIMAL(8,2) DEFAULT NULL,
            dimensions VARCHAR(100),
            category_id INT NOT NULL,
            featured TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            meta_title VARCHAR(255),
            meta_description TEXT,
            views INT DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            INDEX idx_category_id (category_id),
            INDEX idx_slug (slug),
            INDEX idx_sku (sku),
            INDEX idx_is_active (is_active),
            INDEX idx_featured (featured),
            INDEX idx_stock_status (stock_status),
            INDEX idx_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createProductsTable);
    echo "Ürünler tablosu oluşturuldu.\n";

    // Ürün fotoğrafları tablosu
    $createProductImagesTable = "
        CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
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
    echo "Ürün fotoğrafları tablosu oluşturuldu.\n";

    // Ürün özellikleri tablosu
    $createProductAttributesTable = "
        CREATE TABLE IF NOT EXISTS product_attributes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            attribute_name VARCHAR(100) NOT NULL,
            attribute_value TEXT NOT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_product_id (product_id),
            INDEX idx_attribute_name (attribute_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createProductAttributesTable);
    echo "Ürün özellikleri tablosu oluşturuldu.\n";

    // Örnek kategoriler ekleyelim
    $sampleCategories = [
        ['name' => 'ECU Tuning', 'slug' => 'ecu-tuning', 'description' => 'Motor kontrol ünitesi tuning hizmetleri'],
        ['name' => 'Chip Tuning', 'slug' => 'chip-tuning', 'description' => 'Performans chip tuning hizmetleri'],
        ['name' => 'DPF Delete', 'slug' => 'dpf-delete', 'description' => 'Partikül filtresi silme hizmetleri'],
        ['name' => 'EGR Delete', 'slug' => 'egr-delete', 'description' => 'EGR valfi silme hizmetleri'],
        ['name' => 'Otomatik Vites', 'slug' => 'otomatik-vites', 'description' => 'Otomatik vites tuning hizmetleri']
    ];

    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($sampleCategories as $category) {
        $stmt->execute([$category['name'], $category['slug'], $category['description']]);
    }
    echo "Örnek kategoriler eklendi.\n";

    echo "Tüm tablolar başarıyla oluşturuldu!\n";
    
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
