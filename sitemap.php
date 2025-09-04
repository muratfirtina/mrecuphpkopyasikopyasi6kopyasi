<?php
/**
 * Mr ECU - Dynamic Sitemap Generator
 * Veritabanından dinamik sitemap oluşturur
 */

require_once 'config/config.php';
require_once 'config/database.php';

// XML header
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

$baseUrl = rtrim(SITE_URL, '/');
$currentDate = date('Y-m-d');
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    
    <!-- Ana Sayfa -->
    <url>
        <loc><?php echo $baseUrl; ?>/</loc>
        <lastmod><?php echo $currentDate; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Statik Sayfalar -->
    <url>
        <loc><?php echo $baseUrl; ?>/hakkimizda</loc>
        <lastmod><?php echo $currentDate; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc><?php echo $baseUrl; ?>/hizmetler</loc>
        <lastmod><?php echo $currentDate; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <url>
        <loc><?php echo $baseUrl; ?>/urunler</loc>
        <lastmod><?php echo $currentDate; ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    
    <url>
        <loc><?php echo $baseUrl; ?>/iletisim</loc>
        <lastmod><?php echo $currentDate; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

<?php
try {
    // Kategorileri sitemap'e ekle
    $stmt = $pdo->query("
        SELECT slug, updated_at 
        FROM categories 
        WHERE is_active = 1 AND slug IS NOT NULL
        ORDER BY name
    ");
    
    while ($category = $stmt->fetch()) {
        $lastmod = $category['updated_at'] ? date('Y-m-d', strtotime($category['updated_at'])) : $currentDate;
        echo "    <url>\n";
        echo "        <loc>{$baseUrl}/kategori/{$category['slug']}</loc>\n";
        echo "        <lastmod>{$lastmod}</lastmod>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.8</priority>\n";
        echo "    </url>\n";
    }
    
    // Ürünleri sitemap'e ekle
    $stmt = $pdo->query("
        SELECT slug, updated_at 
        FROM products 
        WHERE is_active = 1 AND slug IS NOT NULL
        ORDER BY created_at DESC
        LIMIT 1000
    ");
    
    while ($product = $stmt->fetch()) {
        $lastmod = $product['updated_at'] ? date('Y-m-d', strtotime($product['updated_at'])) : $currentDate;
        echo "    <url>\n";
        echo "        <loc>{$baseUrl}/urun/{$product['slug']}</loc>\n";
        echo "        <lastmod>{$lastmod}</lastmod>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.7</priority>\n";
        echo "    </url>\n";
    }
    
    // Markaları sitemap'e ekle
    if ($pdo->query("SHOW TABLES LIKE 'brands'")->rowCount() > 0) {
        $stmt = $pdo->query("
            SELECT slug, updated_at 
            FROM brands 
            WHERE is_active = 1 AND slug IS NOT NULL
            ORDER BY name
        ");
        
        while ($brand = $stmt->fetch()) {
            $lastmod = $brand['updated_at'] ? date('Y-m-d', strtotime($brand['updated_at'])) : $currentDate;
            echo "    <url>\n";
            echo "        <loc>{$baseUrl}/marka/{$brand['slug']}</loc>\n";
            echo "        <lastmod>{$lastmod}</lastmod>\n";
            echo "        <changefreq>monthly</changefreq>\n";
            echo "        <priority>0.6</priority>\n";
            echo "    </url>\n";
        }
    }
    
} catch (Exception $e) {
    error_log('Sitemap generation error: ' . $e->getMessage());
}
?>
    
</urlset>
