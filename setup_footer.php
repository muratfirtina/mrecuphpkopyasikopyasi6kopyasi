<?php
/**
 * Footer Tables Quick Setup
 * Footer için gerekli tabloları hızlıca oluştur
 */

require_once 'config/database.php';

echo "<h2>🚀 Footer Tabloları Kurulum Kontrol</h2>";

try {
    // 1. services tablosunu kontrol et
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
        echo "<p>✅ <strong>services</strong> tablosu oluşturuldu ve 6 varsayılan hizmet eklendi.</p>";
    } else {
        echo "<p>✅ <strong>services</strong> tablosu zaten mevcut.</p>";
    }
    
    // 2. categories tablosunu kontrol et
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
        echo "<p>✅ <strong>categories</strong> tablosu oluşturuldu ve 6 varsayılan kategori eklendi.</p>";
    } else {
        echo "<p>✅ <strong>categories</strong> tablosu zaten mevcut.</p>";
    }
    
    // 3. contact_cards tablosunu kontrol et
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
        echo "<p>✅ <strong>contact_cards</strong> tablosu oluşturuldu ve varsayılan iletişim bilgisi eklendi.</p>";
    } else {
        echo "<p>✅ <strong>contact_cards</strong> tablosu zaten mevcut.</p>";
    }
    
    // 4. contact_office tablosunu kontrol et
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
        echo "<p>✅ <strong>contact_office</strong> tablosu oluşturuldu ve varsayılan ofis bilgileri eklendi.</p>";
    } else {
        echo "<p>✅ <strong>contact_office</strong> tablosu zaten mevcut.</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎉 Kurulum Tamamlandı!</h3>";
    echo "<p><strong>👉 Footer Yönetimi:</strong> <a href='design/footer.php' target='_blank'>design/footer.php</a></p>";
    echo "<p><strong>👉 Footer Görünüm:</strong> <a href='index.php' target='_blank'>index.php</a> (sayfanın altında)</p>";
    echo "<p><strong>👉 Design Panel:</strong> <a href='design/' target='_blank'>design/</a></p>";
    
    // Tablo sayılarını göster
    echo "<hr>";
    echo "<h4>📊 Mevcut Veriler:</h4>";
    
    $servicesCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    echo "<p>🔧 <strong>Hizmetler:</strong> {$servicesCount} adet</p>";
    
    $categoriesCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    echo "<p>📦 <strong>Kategoriler:</strong> {$categoriesCount} adet</p>";
    
    $contactCardsCount = $pdo->query("SELECT COUNT(*) FROM contact_cards")->fetchColumn();
    echo "<p>📞 <strong>İletişim Kartları:</strong> {$contactCardsCount} adet</p>";
    
    $contactOfficeCount = $pdo->query("SELECT COUNT(*) FROM contact_office")->fetchColumn();
    echo "<p>🏢 <strong>Ofis Bilgileri:</strong> {$contactOfficeCount} adet</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>❌ Hata Oluştu:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 2rem; }
h2, h3, h4 { color: #333; }
p { margin: 0.5rem 0; }
hr { margin: 1.5rem 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>