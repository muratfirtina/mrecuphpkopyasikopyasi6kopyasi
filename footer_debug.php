<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Debug & Test - Mr ECU</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .debug { background: #f8f9fa; padding: 1rem; border: 1px solid #ddd; border-radius: 5px; margin: 1rem 0; }
        button { background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; cursor: pointer; margin: 0.5rem; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto; }
        .test-result { margin: 1rem 0; padding: 1rem; border-radius: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Footer Debug & Test Sistemi</h1>
        
        <?php
        require_once 'config/database.php';
        
        $action = $_GET['action'] ?? '';
        
        // 1. SETUP TABLOSU
        if ($action === 'setup' || $action === '') {
            echo "<h2>📊 1. Tablo Kurulumu</h2>";
            
            try {
                // services tablosunu kontrol et
                $checkServices = $pdo->query("SHOW TABLES LIKE 'services'");
                if ($checkServices->rowCount() == 0) {
                    $createServices = "
                    CREATE TABLE services (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        slug VARCHAR(255) NOT NULL,
                        description TEXT,
                        status ENUM('active', 'inactive') DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($createServices);
                    
                    // Varsayılan veriler
                    $services = [
                        ['ECU Yazılımları', 'ecu-yazilimlari', 'Profesyonel ECU yazılım hizmetleri'],
                        ['TCU Yazılımları', 'tcu-yazilimlari', 'Şanzıman kontrol modülü yazılımları'],
                        ['Immobilizer', 'immobilizer', 'Araç güvenlik sistemleri'],
                        ['Chip Tuning', 'chip-tuning', 'Motor performans optimizasyonu'],
                        ['DPF/EGR/AdBlue', 'dpf-egr-adblue', 'Emisyon sistemi çözümleri'],
                        ['Key Programming', 'key-programming', 'Anahtar programlama hizmetleri']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO services (name, slug, description) VALUES (?, ?, ?)");
                    foreach ($services as $service) {
                        $stmt->execute($service);
                    }
                    echo "<div class='success'>✅ <strong>services</strong> tablosu oluşturuldu ve 6 hizmet eklendi.</div>";
                } else {
                    echo "<div class='info'>✅ <strong>services</strong> tablosu zaten mevcut.</div>";
                }
                
                // categories tablosunu kontrol et
                $checkCategories = $pdo->query("SHOW TABLES LIKE 'categories'");
                if ($checkCategories->rowCount() == 0) {
                    $createCategories = "
                    CREATE TABLE categories (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        slug VARCHAR(255) NOT NULL,
                        description TEXT,
                        status ENUM('active', 'inactive') DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($createCategories);
                    
                    // Varsayılan veriler
                    $categories = [
                        ['ECU Modülleri', 'ecu-modulleri', 'Motor kontrol üniteleri'],
                        ['TCU Modülleri', 'tcu-modulleri', 'Şanzıman kontrol üniteleri'],
                        ['Yazılım Araçları', 'yazilim-araclari', 'Profesyonel yazılım araçları'],
                        ['Donanım Ürünleri', 'donanim-urunleri', 'Teknik donanım çözümleri'],
                        ['Kablolar & Adaptörler', 'kablolar-adaptorler', 'Bağlantı çözümleri'],
                        ['Test Ekipmanları', 'test-ekipmanlari', 'Ölçüm ve test araçları']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                    foreach ($categories as $category) {
                        $stmt->execute($category);
                    }
                    echo "<div class='success'>✅ <strong>categories</strong> tablosu oluşturuldu ve 6 kategori eklendi.</div>";
                } else {
                    echo "<div class='info'>✅ <strong>categories</strong> tablosu zaten mevcut.</div>";
                }
                
                // contact_cards tablosunu kontrol et
                $checkContactCards = $pdo->query("SHOW TABLES LIKE 'contact_cards'");
                if ($checkContactCards->rowCount() == 0) {
                    $createContactCards = "
                    CREATE TABLE contact_cards (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) DEFAULT NULL,
                        contact_info TEXT,
                        status ENUM('active', 'inactive') DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($createContactCards);
                    
                    // Varsayılan veri
                    $insertContactCard = "INSERT INTO contact_cards (title, contact_info) VALUES 
                    ('Footer İletişim', 'E-posta: info@mrecu.com\nTelefon: +90 (555) 123 45 67\nWhatsApp: +90 (555) 123 45 67\n\nHızlı destek için bizi arayın!')";
                    $pdo->exec($insertContactCard);
                    echo "<div class='success'>✅ <strong>contact_cards</strong> tablosu oluşturuldu ve iletişim bilgisi eklendi.</div>";
                } else {
                    echo "<div class='info'>✅ <strong>contact_cards</strong> tablosu zaten mevcut.</div>";
                }
                
                // contact_office tablosunu kontrol et
                $checkContactOffice = $pdo->query("SHOW TABLES LIKE 'contact_office'");
                if ($checkContactOffice->rowCount() == 0) {
                    $createContactOffice = "
                    CREATE TABLE contact_office (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        address TEXT NOT NULL,
                        working_hours VARCHAR(255) DEFAULT NULL,
                        phone VARCHAR(50) DEFAULT NULL,
                        email VARCHAR(100) DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($createContactOffice);
                    
                    // Varsayılan veri
                    $insertContactOffice = "INSERT INTO contact_office (address, working_hours, phone, email) VALUES 
                    ('Atatürk Mahallesi, Teknoloji Caddesi No:123\nKadıköy, İstanbul 34740', 'Pazartesi - Cumartesi: 09:00 - 18:00\nPazar: 10:00 - 16:00', '+90 (555) 123 45 67', 'info@mrecu.com')";
                    $pdo->exec($insertContactOffice);
                    echo "<div class='success'>✅ <strong>contact_office</strong> tablosu oluşturuldu ve ofis bilgileri eklendi.</div>";
                } else {
                    echo "<div class='info'>✅ <strong>contact_office</strong> tablosu zaten mevcut.</div>";
                }
                
                echo "<div class='success'><h3>🎉 Tüm tablolar hazır!</h3></div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
            }
        }
        
        // 2. VERİ TESTI
        echo "<h2>📋 2. Veri Test Sistemi</h2>";
        
        try {
            // Tablo sayılarını göster
            $servicesCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
            $categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            $contactCardsCount = $pdo->query("SELECT COUNT(*) FROM contact_cards")->fetchColumn();
            $contactOfficeCount = $pdo->query("SELECT COUNT(*) FROM contact_office")->fetchColumn();
            
            echo "<div class='info'>";
            echo "<h4>📊 Mevcut Veriler:</h4>";
            echo "<p>🔧 <strong>Hizmetler:</strong> {$servicesCount} adet</p>";
            echo "<p>📦 <strong>Kategoriler:</strong> {$categoriesCount} adet</p>";
            echo "<p>📞 <strong>İletişim Kartları:</strong> {$contactCardsCount} adet</p>";
            echo "<p>🏢 <strong>Ofis Bilgileri:</strong> {$contactOfficeCount} adet</p>";
            echo "</div>";
            
            // Footer verilerini test et
            echo "<h3>🧪 Footer Veri Testi</h3>";
            
            // Hizmetler
            $servicesQuery = "SELECT name, slug FROM services ORDER BY name LIMIT 6";
            $servicesStmt = $pdo->prepare($servicesQuery);
            $servicesStmt->execute();
            $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='debug'>";
            echo "<strong>🔧 Hizmetler (" . count($services) . " adet):</strong><br>";
            foreach ($services as $service) {
                echo "• " . htmlspecialchars($service['name']) . "<br>";
            }
            echo "</div>";
            
            // Kategoriler
            $categoriesQuery = "SELECT name, slug FROM categories ORDER BY name LIMIT 6";
            $categoriesStmt = $pdo->prepare($categoriesQuery);
            $categoriesStmt->execute();
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='debug'>";
            echo "<strong>📦 Kategoriler (" . count($categories) . " adet):</strong><br>";
            foreach ($categories as $category) {
                echo "• " . htmlspecialchars($category['name']) . "<br>";
            }
            echo "</div>";
            
            // İletişim bilgileri
            $contactQuery = "SELECT contact_info FROM contact_cards ORDER BY id LIMIT 1";
            $contactStmt = $pdo->prepare($contactQuery);
            $contactStmt->execute();
            $contactInfo = $contactStmt->fetchColumn();
            
            echo "<div class='debug'>";
            echo "<strong>📞 İletişim Bilgileri:</strong><br>";
            echo nl2br(htmlspecialchars($contactInfo ?: 'Veri yok'));
            echo "</div>";
            
            // Ofis bilgileri
            $officeQuery = "SELECT address, working_hours FROM contact_office ORDER BY id LIMIT 1";
            $officeStmt = $pdo->prepare($officeQuery);
            $officeStmt->execute();
            $officeData = $officeStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<div class='debug'>";
            echo "<strong>🏢 Ofis Bilgileri:</strong><br>";
            echo "<strong>Adres:</strong> " . nl2br(htmlspecialchars($officeData['address'] ?? 'Veri yok')) . "<br>";
            echo "<strong>Çalışma Saatleri:</strong> " . htmlspecialchars($officeData['working_hours'] ?? 'Veri yok');
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Veri testi hatası: " . $e->getMessage() . "</div>";
        }
        ?>
        
        <hr>
        
        <!-- 3. FOOTER INCLUDE TESTI -->
        <h2>🔍 3. Footer Include Test</h2>
        
        <div class="info">
            <h4>🧪 includes/footer.php Test Çalıştırılıyor...</h4>
            <div style="border: 2px solid #071e3d; background: #071e3d; color: white; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                <?php 
                // includes/footer.php'yi test et
                try {
                    ob_start();
                    include 'includes/footer.php';
                    $footerOutput = ob_get_clean();
                    
                    // Footer'ın bir kısmını göster (ilk 500 karakter)
                    $preview = substr(strip_tags($footerOutput), 0, 500);
                    echo "<strong>Footer başarıyla yüklendi!</strong><br>";
                    echo "Preview: " . htmlspecialchars($preview) . "...";
                    
                } catch (Exception $e) {
                    echo "<div style='color: #ff6b6b;'>❌ Footer yükleme hatası: " . $e->getMessage() . "</div>";
                }
                ?>
            </div>
        </div>
        
        <hr>
        
        <!-- NAVIGATION LINKS -->
        <h2>🔗 4. Hızlı Erişim</h2>
        <div class="info">
            <h4>Admin & Design Panelleri:</h4>
            <button onclick="window.open('design/footer.php', '_blank')">
                🎨 Footer Yönetim Paneli
            </button>
            <button onclick="window.open('design/', '_blank')">
                🎭 Design Panel
            </button>
            <button onclick="window.open('admin/', '_blank')">
                ⚙️ Admin Panel
            </button>
            
            <h4>Test Sayfaları:</h4>
            <button onclick="window.open('index.php', '_blank')">
                🏠 Ana Sayfa (Footer Test)
            </button>
            <button onclick="window.open('includes/footer.php', '_blank')">
                👁️ Footer Direkt Görünüm
            </button>
            
            <h4>Database İşlemleri:</h4>
            <button onclick="window.location.reload()">
                🔄 Sayfayı Yenile
            </button>
            <button onclick="if(confirm('Tüm footer tablolarını yeniden oluştur?')) window.location='?action=recreate'">
                🗃️ Tabloları Yeniden Oluştur
            </button>
        </div>
        
        <?php if (isset($_GET['action']) && $_GET['action'] === 'recreate'): ?>
            <div class="error">
                <h3>⚠️ Tablolar Yeniden Oluşturma</h3>
                <p>Bu işlem mevcut verileri silecektir!</p>
                <?php
                try {
                    $pdo->exec("DROP TABLE IF EXISTS services");
                    $pdo->exec("DROP TABLE IF EXISTS categories");
                    $pdo->exec("DROP TABLE IF EXISTS contact_cards");
                    $pdo->exec("DROP TABLE IF EXISTS contact_office");
                    echo "<p>✅ Tablolar silindi. <a href='?action=setup'>Yeniden oluştur</a></p>";
                } catch (Exception $e) {
                    echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        <?php endif; ?>
        
        <hr>
        
        <div class="debug">
            <h4>🔧 Debug Bilgileri:</h4>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>PDO Status:</strong> <?php echo isset($pdo) ? '✅ Connected' : '❌ Not Connected'; ?></p>
            <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>File Path:</strong> <?php echo __FILE__; ?></p>
        </div>
        
    </div>
</body>
</html>