<?php
/**
 * Mr ECU - .htaccess Updater for Product URLs
 */

echo "<h1>Mr ECU - Ürün URL'lerini Aktif Etme</h1>";
echo "<hr>";

$fullHtaccess = '# Mr ECU - Full Configuration with Product URLs

# Prevent directory browsing
Options -Indexes

# Hide sensitive files
<FilesMatch "\.(ini|log|conf|sql|md)$">
    Require all denied
</FilesMatch>

# Hide system directories
RedirectMatch 403 ^/(.git|config|logs|temp|vendor|node_modules)

# Basic security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Enable URL Rewriting
RewriteEngine On

# Remove trailing slashes (except for directories)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{THE_REQUEST} /([^?\s]*)/[?\s] [NC]
RewriteRule ^(.*)/ $1 [R=301,L]

# ====================================
# PRODUCT SYSTEM URL ROUTES
# ====================================

# Product detail page: /urun/product-slug
RewriteRule ^urun/([a-zA-Z0-9\-]+)/?$ product-detail.php?slug=$1 [QSA,L]

# Product category page: /kategori/category-slug  
RewriteRule ^kategori/([a-zA-Z0-9\-]+)/?$ product-category.php?slug=$1 [QSA,L]

# Product brand page: /marka/brand-slug
RewriteRule ^marka/([a-zA-Z0-9\-]+)/?$ product-brand.php?slug=$1 [QSA,L]

# Products listing page: /urunler
RewriteRule ^urunler/?$ products.php [QSA,L]

# Products with category filter: /urunler/category-slug
RewriteRule ^urunler/([a-zA-Z0-9\-]+)/?$ products.php?category=$1 [QSA,L]

# ====================================
# PAGE ROUTES
# ====================================

# User login: /giris
RewriteRule ^giris/?$ login.php [QSA,L]

# User register: /kayit
RewriteRule ^kayit/?$ register.php [QSA,L]

# Services page: /hizmetlerimiz
RewriteRule ^hizmetlerimiz/?$ services.php [QSA,L]

# About page: /hakkimizda  
RewriteRule ^hakkimizda/?$ about.php [QSA,L]

# Contact page: /iletisim
RewriteRule ^iletisim/?$ contact.php [QSA,L]

# ====================================
# TUNING SYSTEM URL ROUTES
# ====================================

# Tuning search page: /chip-tuning-arama
RewriteRule ^chip-tuning-arama/?$ tuning-search.php [QSA,L]

# Tuning results page: /chip-tuning-sonuclari
RewriteRule ^chip-tuning-sonuclari/?$ tuning-results.php [QSA,L]

# ====================================
# ERROR PAGES
# ====================================

ErrorDocument 404 /mrecuphpkopyasikopyasi6kopyasi/404.php
ErrorDocument 500 /mrecuphpkopyasikopyasi6kopyasi/500.php

# ====================================
# API ROUTES (if needed)
# ====================================

# Simple test rewrite (for testing)
RewriteRule ^test-simple/?$ test-rewrite.php?test=simple [QSA,L]
';

try {
    // Backup current .htaccess
    if (file_exists('.htaccess')) {
        $backup = file_get_contents('.htaccess');
        file_put_contents('.htaccess_backup_' . date('Y-m-d_H-i-s'), $backup);
        echo "✅ Mevcut .htaccess yedeklendi<br>";
    }
    
    // Write new .htaccess
    file_put_contents('.htaccess', $fullHtaccess);
    echo "✅ Yeni .htaccess dosyası oluşturuldu<br>";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Başarılı!</h3>";
    echo "<p>Ürün URL'leri aktif edildi. Artık aşağıdaki URL'leri kullanabilirsiniz:</p>";
    echo "<ul>";
    echo "<li><code>/urun/product-slug</code> - Ürün detay sayfası</li>";
    echo "<li><code>/urunler</code> - Ürün listeleme sayfası</li>";
    echo "<li><code>/marka/brand-slug</code> - Marka sayfası</li>";
    echo "<li><code>/kategori/category-slug</code> - Kategori sayfası</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test URL'leri:</h3>";
    echo "<p>Aşağıdaki linkleri test edin:</p>";
    echo "<ul>";
    echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/urunler' target='_blank'>/urunler (Ürün listesi)</a></li>";
    if (file_exists('product-detail.php')) {
        echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/urun/autotuner-ecu-programlama-cihazi-guc-ve-verimliligi-bir-arada-sunun' target='_blank'>Örnek ürün detay</a></li>";
    }
    echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/test-simple' target='_blank'>Rewrite test</a></li>";
    echo "</ul>";
    
    echo "<h3>📋 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/admin/product-brands.php' target='_blank'>Admin'de marka ekleyin</a></li>";
    echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/admin/products.php' target='_blank'>Admin'de ürün ekleyin</a></li>";
    echo "<li>Test URL'lerini deneyin</li>";
    echo "<li><strong>install-product-system.php</strong> ve diğer test dosyalarını silin</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Hata!</h3>";
    echo "<p>Hata: " . $e->getMessage() . "</p>";
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

h1, h2, h3 { color: #333; }
code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
ul li { margin-bottom: 5px; }
ol li { margin-bottom: 10px; }
</style>
