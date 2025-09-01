<?php
/**
 * Contact Tabloları Test ve Kurulum
 * Basit ve güvenilir kurulum scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Contact Tabloları Kurulum</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 900px; }
        .step { margin: 15px 0; padding: 15px; border-radius: 10px; }
        .step.success { background: #d1e7dd; border-left: 4px solid #198754; }
        .step.error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .step.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .step.info { background: #cff4fc; border-left: 4px solid #0dcaf0; }
    </style>
</head>
<body>
    <div class='container py-4'>";

echo "<h1 class='text-center mb-4'><i class='bi bi-phone me-2'></i>Contact Sayfası Database Kurulumu</h1>";

$steps_completed = 0;
$total_steps = 4;

try {
    // Step 1: Database Test
    echo "<div class='step info'>";
    echo "<h4><i class='bi bi-plug me-2'></i>1. Database Bağlantısı</h4>";
    $pdo->query('SELECT 1');
    echo "<p class='mb-0'><i class='bi bi-check-circle text-success me-2'></i>Database bağlantısı başarılı!</p>";
    echo "</div>";
    $steps_completed++;

    // Step 2: Create Tables
    echo "<div class='step info'>";
    echo "<h4><i class='bi bi-table me-2'></i>2. Tabloları Oluşturuluyor</h4>";
    
    // contact_settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_title VARCHAR(255) NOT NULL DEFAULT 'İletişim',
        page_description TEXT NULL,
        header_title VARCHAR(255) NOT NULL DEFAULT 'İletişim',
        header_subtitle TEXT NULL,
        google_maps_embed TEXT NULL,
        form_success_message TEXT NULL,
        privacy_policy_content TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>contact_settings tablosu hazır</p>";
    
    // contact_cards
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(100) NOT NULL DEFAULT 'bi bi-phone',
        icon_color VARCHAR(50) NOT NULL DEFAULT 'text-primary',
        contact_info VARCHAR(255) NOT NULL,
        contact_link VARCHAR(500) NULL,
        button_text VARCHAR(100) NULL,
        button_color VARCHAR(50) DEFAULT 'btn-outline-primary',
        availability_text VARCHAR(255) NULL,
        order_no INT NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>contact_cards tablosu hazır</p>";
    
    // contact_office
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_office (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL DEFAULT 'Ofisimizi Ziyaret Edin',
        description TEXT NULL,
        address TEXT NOT NULL,
        working_hours TEXT NOT NULL,
        transportation TEXT NULL,
        google_maps_link VARCHAR(500) NULL,
        image_url VARCHAR(500) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>contact_office tablosu hazır</p>";
    
    // contact_form_settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_form_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_title VARCHAR(255) NOT NULL DEFAULT 'Bize Mesaj Gönderin',
        form_subtitle TEXT NULL,
        success_message TEXT NULL,
        subject_options TEXT NULL,
        form_fields TEXT NULL,
        enable_privacy_checkbox TINYINT(1) DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>contact_form_settings tablosu hazır</p>";
    
    // contact_messages (eğer mevcut değilse)
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>contact_messages tablosu hazır</p>";
    
    echo "</div>";
    $steps_completed++;

    // Step 3: Insert Sample Data
    echo "<div class='step info'>";
    echo "<h4><i class='bi bi-database me-2'></i>3. Örnek Veriler Ekleniyor</h4>";
    
    // contact_settings sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_settings");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO contact_settings (page_title, page_description, header_title, header_subtitle, google_maps_embed, form_success_message, privacy_policy_content) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'İletişim',
            'Mr ECU ile iletişime geçin. 7/24 destek, profesyonel hizmet ve hızlı çözümler için bizimle iletişime geçin.',
            'İletişim',
            'Sorularınız mı var? Yardıma mı ihtiyacınız var? 7/24 uzman ekibimiz size yardımcı olmaya hazır.',
            '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3010.206298417604!2d28.97609197408332!3d41.04621897133748!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cab761f6b89c31%3A0x9d55b02b23dd7e4e!2sŞişli%2C%20İstanbul!5e0!3m2!1str!2str!4v1693123456789" width="100%" height="400" style="border:0; border-radius: 0.5rem;" allowfullscreen="" loading="lazy"></iframe>',
            'Mesajınız başarıyla gönderildi. En kısa sürede size geri dönüş yapacağız.',
            '<h6>Kişisel Verilerin Korunması</h6><p>Mr ECU olarak kişisel verilerinizin gizliliğini korumak önceliğimizdir.</p><h6>İletişim Formunda Toplanan Veriler</h6><ul><li>Ad, soyad bilgileri</li><li>E-posta adresi</li><li>Telefon numarası (isteğe bağlı)</li><li>Mesaj içeriği</li></ul>'
        ]);
        echo "<p><i class='bi bi-plus text-success me-2'></i>Contact settings örnek verisi eklendi</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Contact settings zaten mevcut</p>";
    }
    
    // contact_cards sample data  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_cards");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $cards_data = [
            ['Telefon Desteği', '7/24 telefon desteği alın. Uzman ekibimiz her zaman yanınızda.', 'bi bi-phone', 'text-primary', '+90 (533) 924 29 48', 'tel:+905339242948', 'Hemen Ara', 'btn-outline-primary', 'Pazartesi - Pazar | 24 Saat', 1],
            ['E-posta Desteği', 'Detaylı sorularınız için e-posta gönderin. 2 saat içinde yanıt alın.', 'bi bi-envelope', 'text-success', 'info@mrecufile.com.tr', 'mailto:info@mrecufile.com.tr', 'E-posta Gönder', 'btn-outline-success', 'Ortalama yanıt süresi: 2 saat', 2],
            ['WhatsApp Desteği', 'Anlık destek için WhatsApp\'tan yazın. Hızlı ve pratik çözümler.', 'bi bi-whatsapp', 'text-info', '+90 (533) 924 29 48', 'https://wa.me/905339242948?text=Merhaba,%20ECU%20hizmetleri%20hakkında%20bilgi%20almak%20istiyorum.', 'WhatsApp\'ta Yaz', 'btn-outline-info', '7/24 Aktif | Anlık Yanıt', 3]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO contact_cards (title, description, icon, icon_color, contact_info, contact_link, button_text, button_color, availability_text, order_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($cards_data as $data) {
            $stmt->execute($data);
        }
        echo "<p><i class='bi bi-plus text-success me-2'></i>Contact cards örnek verileri eklendi (3 kayıt)</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Contact cards zaten mevcut</p>";
    }
    
    // contact_office sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_office");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO contact_office (title, description, address, working_hours, transportation, google_maps_link) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Ofisimizi Ziyaret Edin',
            'İstanbul merkezindeki modern ofisimizde uzman ekibimizle yüz yüze görüşebilir, projelerinizi detaylı olarak konuşabilirsiniz.',
            "Örnek Mahallesi, Teknoloji Caddesi No: 123\nŞişli / İstanbul",
            "Pazartesi - Cuma: 09:00 - 18:00\nCumartesi: 10:00 - 16:00",
            "Metro: Şişli-Mecidiyeköy\nOtobüs: 54, 42A, 181",
            'https://maps.google.com'
        ]);
        echo "<p><i class='bi bi-plus text-success me-2'></i>Contact office örnek verisi eklendi</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Contact office zaten mevcut</p>";
    }
    
    // contact_form_settings sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_form_settings");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $subject_options = '["Genel Bilgi", "ECU Tuning", "Chip Tuning", "İmmobilizer", "DPF/EGR Off", "Teknik Destek", "Faturalama", "Şikayet", "Öneri", "Diğer"]';
        $form_fields = '{"name":{"required":true,"label":"Ad Soyad"},"email":{"required":true,"label":"E-posta Adresi"},"phone":{"required":false,"label":"Telefon Numarası"},"subject":{"required":true,"label":"Konu"},"message":{"required":true,"label":"Mesajınız","min_length":10}}';
        
        $stmt = $pdo->prepare("INSERT INTO contact_form_settings (form_title, form_subtitle, success_message, subject_options, form_fields) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'Bize Mesaj Gönderin',
            'Formu doldurarak bizimle iletişime geçebilirsiniz',
            'Mesajınız başarıyla gönderildi. En kısa sürede size geri dönüş yapacağız.',
            $subject_options,
            $form_fields
        ]);
        echo "<p><i class='bi bi-plus text-success me-2'></i>Contact form settings örnek verisi eklendi</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Contact form settings zaten mevcut</p>";
    }
    
    echo "</div>";
    $steps_completed++;

    // Step 4: Final Verification
    echo "<div class='step info'>";
    echo "<h4><i class='bi bi-check-double me-2'></i>4. Kurulum Doğrulanıyor</h4>";
    
    $tables = ['contact_settings', 'contact_cards', 'contact_office', 'contact_form_settings', 'contact_messages'];
    $all_good = true;
    $total_records = 0;
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}`");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $total_records += $count;
        
        if ($table !== 'contact_messages' && $count > 0) {
            echo "<p><i class='bi bi-check text-success me-2'></i>✅ {$table}: {$count} kayıt</p>";
        } elseif ($table === 'contact_messages') {
            echo "<p><i class='bi bi-check text-success me-2'></i>✅ {$table}: Tablo hazır ({$count} mesaj)</p>";
        } else {
            echo "<p><i class='bi bi-times text-danger me-2'></i>❌ {$table}: Boş tablo!</p>";
            $all_good = false;
        }
    }
    
    if ($all_good) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<i class='bi bi-party-horn me-2'></i><strong>Tüm tablolar başarıyla oluşturuldu!</strong><br>";
        echo "Contact sayfası hazır.";
        echo "</div>";
        $steps_completed++;
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='step error'>";
    echo "<h4><i class='bi bi-exclamation-triangle me-2'></i>❌ Kurulum Hatası</h4>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (DEBUG) {
        echo "<details class='mt-3'><summary>Debug Detayları</summary>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</details>";
    }
    echo "</div>";
}

// Progress bar
$progress = ($steps_completed / $total_steps) * 100;
echo "<div class='mt-4'>";
echo "<h5>Kurulum İlerlemesi</h5>";
echo "<div class='progress' style='height: 20px;'>";
echo "<div class='progress-bar bg-success progress-bar-striped' style='width: {$progress}%;'>{$progress}%</div>";
echo "</div>";
echo "<p class='mt-2 text-muted'>{$steps_completed}/{$total_steps} adım tamamlandı</p>";
echo "</div>";

if ($steps_completed === $total_steps) {
    // Başarı mesajı
    echo "<div class='step success text-center'>";
    echo "<h2><i class='bi bi-check-circle me-3 text-success'></i>🎉 Kurulum Tamamlandı!</h2>";
    echo "<p class='mb-4'>Contact sayfanız artık database entegreli!</p>";
    
    echo "<div class='row g-3'>";
    echo "<div class='col-md-4'>";
    echo "<a href='design/contact.php' class='btn btn-primary w-100'>";
    echo "<i class='bi bi-pencil-square me-2'></i>İçerik Yönetimi";
    echo "</a>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<a href='contact.php' target='_blank' class='btn btn-success w-100'>";
    echo "<i class='bi bi-eye me-2'></i>Sayfayı Görüntüle";
    echo "</a>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<a href='design/' class='btn btn-info w-100'>";
    echo "<i class='bi bi-tachometer-alt me-2'></i>Design Panel";
    echo "</a>";
    echo "</div>";
    echo "</div>";
    
    echo "<hr class='my-4'>";
    echo "<h4><i class='bi bi-rocket me-2'></i>Contact Sayfası Özellikleri</h4>";
    echo "<div class='row text-center'>";
    echo "<div class='col-md-3'><i class='bi bi-phone-alt fa-2x text-primary mb-2'></i><br><strong>İletişim Kartları</strong><br><small>Telefon, E-posta, WhatsApp</small></div>";
    echo "<div class='col-md-3'><i class='bi bi-envelope fa-2x text-success mb-2'></i><br><strong>Dinamik Form</strong><br><small>Özelleştirilebilir Konular</small></div>";
    echo "<div class='col-md-3'><i class='bi bi-map-marker-alt fa-2x text-info mb-2'></i><br><strong>Ofis Bilgileri</strong><br><small>Google Maps Entegreli</small></div>";
    echo "<div class='col-md-3'><i class='bi bi-comments fa-2x text-warning mb-2'></i><br><strong>Mesaj Yönetimi</strong><br><small>Gelen Mesajları Takip</small></div>";
    echo "</div>";
    echo "</div>";
    
    // Cleanup option
    echo "<div class='step warning text-center'>";
    echo "<h5><i class='bi bi-broom me-2'></i>Temizlik (İsteğe Bağlı)</h5>";
    echo "<p>Kurulum tamamlandı. Güvenlik için kurulum dosyalarını silebilirsiniz:</p>";
    echo "<a href='?cleanup=1' class='btn btn-warning' onclick='return confirm(\"Kurulum dosyalarını silmek istediğinizden emin misiniz?\")'>"; 
    echo "<i class='bi bi-trash me-2'></i>Kurulum Dosyalarını Sil";
    echo "</a>";
    echo "</div>";
    
} else {
    echo "<div class='step error text-center'>";
    echo "<h3><i class='bi bi-times-circle me-2 text-danger'></i>Kurulum Tamamlanamadı</h3>";
    echo "<p>Lütfen hataları kontrol edin ve tekrar deneyin.</p>";
    echo "<a href='?' class='btn btn-primary'><i class='bi bi-redo me-2'></i>Tekrar Dene</a>";
    echo "</div>";
}

// Cleanup
if (isset($_GET['cleanup']) && $_GET['cleanup'] == '1') {
    echo "<div class='step warning'>";
    echo "<h4><i class='bi bi-broom me-2'></i>Dosya Temizleme</h4>";
    
    $cleanup_files = [
        'install_contact_tables.php',
        'create_contact_tables.sql'
    ];
    
    foreach ($cleanup_files as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<p><i class='bi bi-check text-success me-2'></i>Silindi: {$file}</p>";
            } else {
                echo "<p><i class='bi bi-times text-danger me-2'></i>Silinemedi: {$file}</p>";
            }
        } else {
            echo "<p><i class='bi bi-info text-muted me-2'></i>Dosya bulunamadı: {$file}</p>";
        }
    }
    
    echo "<div class='alert alert-success mt-3'>";
    echo "<i class='bi bi-check-circle me-2'></i>Temizlik tamamlandı!";
    echo "</div>";
    
    echo "<script>setTimeout(function(){ window.location.href='design/contact.php'; }, 2000);</script>";
    echo "<p class='text-center'><em>2 saniye sonra contact yönetim paneline yönlendirileceksiniz...</em></p>";
    echo "</div>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>