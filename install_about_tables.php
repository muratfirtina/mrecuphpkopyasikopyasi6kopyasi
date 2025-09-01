<?php
/**
 * About Tabloları Test ve Kurulum
 * Basit ve güvenilir kurulum scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>About Tabloları Kurulum</title>
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

echo "<h1 class='text-center mb-4'><i class='bi bi-database me-2'></i>About Sayfası Database Kurulumu</h1>";

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
    
    // about_content
    $pdo->exec("CREATE TABLE IF NOT EXISTS about_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(500) NULL,
        description TEXT NULL,
        main_content TEXT NULL,
        image_url VARCHAR(500) NULL,
        features TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>about_content tablosu hazır</p>";
    
    // about_core_values
    $pdo->exec("CREATE TABLE IF NOT EXISTS about_core_values (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(100) NULL,
        icon_color VARCHAR(50) NULL,
        background_color VARCHAR(50) NULL,
        order_no INT NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>about_core_values tablosu hazır</p>";
    
    // about_service_features  
    $pdo->exec("CREATE TABLE IF NOT EXISTS about_service_features (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        icon_url VARCHAR(500) NULL,
        icon VARCHAR(100) NULL,
        order_no INT NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>about_service_features tablosu hazır</p>";
    
    // about_vision
    $pdo->exec("CREATE TABLE IF NOT EXISTS about_vision (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle TEXT NULL,
        description TEXT NULL,
        main_content TEXT NULL,
        image_url VARCHAR(500) NULL,
        features TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p><i class='bi bi-check text-success me-2'></i>about_vision tablosu hazır</p>";
    
    echo "</div>";
    $steps_completed++;

    // Step 3: Insert Sample Data
    echo "<div class='step info'>";
    echo "<h4><i class='bi bi-database me-2'></i>3. Örnek Veriler Ekleniyor</h4>";
    
    // about_content sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM about_content");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO about_content (title, subtitle, description, main_content, image_url, features) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Neden Biz?',
            'Mr. ECU Teknoloji ve Otomotiv Çözümleri',
            'Mr. ECU Teknoloji ve Otomotiv Çözümleri olarak, otomotiv teknolojisindeki uzmanlığımızı yenilikçi bir vizyonla birleştirerek global ölçekte hizmet sunan bir marka olma yolunda hızla ilerlemekteyiz.',
            "Online Chiptuning Dosya Hizmeti sunduğumuz en önemli çözümlerden biridir.\n\nNerede olursanız olun, araçlarınızın performansını artırmak veya özelleştirmek için gerekli yazılım dosyalarına anında erişim sağlayarak fark yaratıyoruz.",
            'https://storage.acerapps.io/app-1580/images/about-img.webp',
            '[{"title":"Anında Erişim","icon":"bi bi-check-circle text-success"},{"title":"Global Hizmet","icon":"bi bi-check-circle text-success"},{"title":"Yenilikçi Vizyon","icon":"bi bi-check-circle text-success"},{"title":"10+ Yıl Deneyim","icon":"bi bi-check-circle text-success"}]'
        ]);
        echo "<p><i class='bi bi-plus text-success me-2'></i>Ana içerik örnek verisi eklendi</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Ana içerik zaten mevcut</p>";
    }
    
    // Core values sample data  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM about_core_values");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $core_values_data = [
            ['Müşteri Memnuniyeti Odaklı', 'Müşteri memnuniyeti bizim için en önemli öncelik. Açık ve kesintisiz iletişim ilkemizle, müşterilerimizin her türlü sorusuna yanıt vermeye hazırız.', 'bi bi-heart', 'text-primary', 'bg-primary bg-opacity-10', 1],
            ['Güvenilir ve Çözüm Odaklı Yaklaşım', 'Sektördeki en güncel ve güvenilir teknolojileri kullanıyoruz. Çözüm odaklı yaklaşımımız sayesinde iş ortaklarımıza daima destek oluyoruz.', 'bi bi-shield-alt', 'text-success', 'bg-success bg-opacity-10', 2]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO about_core_values (title, description, icon, icon_color, background_color, order_no) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($core_values_data as $data) {
            $stmt->execute($data);
        }
        echo "<p><i class='bi bi-plus text-success me-2'></i>Temel değerler örnek verileri eklendi (2 kayıt)</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Temel değerler zaten mevcut</p>";
    }
    
    // Service features sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM about_service_features");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $features_data = [
            ['Profesyonel Hizmet Anlayışı', 'Müşteri memnuniyetini ön planda tutan profesyonel hizmet anlayışımız ile sektörde fark yaratıyoruz.', 'https://storage.acerapps.io/app-1580/professional-development.png', 'bi bi-user-tie', 1],
            ['Güçlü ve Güvenilir Yazılım', 'Araç performansını optimize eden güvenilir yazılım çözümleri ile en iyi sonuçları elde edin.', 'https://storage.acerapps.io/app-1580/convenience.png', 'bi bi-laptop-code', 2],
            ['Sektördeki Deneyim ve Uzmanlık', 'Sektörde 10 yıllık deneyim ve uzmanlık ile size en kaliteli hizmeti sunuyoruz.', 'https://storage.acerapps.io/app-1580/professional.png', 'bi bi-medal', 3],
            ['Teknik Destek', 'Her adımda yanınızda olan kapsamlı teknik destek hizmeti ile sorunlarınıza anında çözüm.', 'https://storage.acerapps.io/app-1580/development.png', 'bi bi-headset', 4]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO about_service_features (title, description, icon_url, icon, order_no) VALUES (?, ?, ?, ?, ?)");
        foreach ($features_data as $data) {
            $stmt->execute($data);
        }
        echo "<p><i class='bi bi-plus text-success me-2'></i>Hizmet özellikleri örnek verileri eklendi (4 kayıt)</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Hizmet özellikleri zaten mevcut</p>";
    }
    
    // Vision sample data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM about_vision");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO about_vision (title, subtitle, description, main_content, image_url, features) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Vizyonumuz',
            'Bizi tercih eden müşteriler, güvenilirliği ve kalitesiyle fark yaratan bir iş ortağı ile çalışmanın ayrıcalığını deneyimler.',
            'Otomotiv sektöründe teknolojinin gücünü kullanarak, müşterilerimizin işlerini kolaylaştırmak ve başarılarına ortak olmak en büyük motivasyonumuz.',
            'Sürekli gelişen teknoloji ile birlikte kendimizi yeniliyoruz ve müşterilerimize en güncel çözümleri sunuyoruz.',
            'https://storage.acerapps.io/app-1580/images/ch3.webp',
            '[{"title":"Sürekli İnovasyon","description":"Teknolojik gelişmeleri yakından takip ediyoruz","icon":"bi bi-rocket text-primary"},{"title":"Global Perspektif","description":"Dünya çapında hizmet sunma hedefi","icon":"bi bi-globe text-primary"}]'
        ]);
        echo "<p><i class='bi bi-plus text-success me-2'></i>Vizyon örnek verisi eklendi</p>";
    } else {
        echo "<p><i class='bi bi-info text-warning me-2'></i>Vizyon içeriği zaten mevcut</p>";
    }
    
    echo "</div>";
    $steps_completed++;

    // Step 4: Final Verification
    echo "<div class='step info'>";
    echo "<h4><i class='bi bi-check-double me-2'></i>4. Kurulum Doğrulanıyor</h4>";
    
    $tables = ['about_content', 'about_core_values', 'about_service_features', 'about_vision'];
    $all_good = true;
    $total_records = 0;
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}`");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $total_records += $count;
        
        if ($count > 0) {
            echo "<p><i class='bi bi-check text-success me-2'></i>✅ {$table}: {$count} kayıt</p>";
        } else {
            echo "<p><i class='bi bi-times text-danger me-2'></i>❌ {$table}: Boş tablo!</p>";
            $all_good = false;
        }
    }
    
    if ($all_good) {
        echo "<div class='alert alert-success mt-3'>";
        echo "<i class='bi bi-party-horn me-2'></i><strong>Tüm tablolar başarıyla oluşturuldu!</strong><br>";
        echo "Toplam {$total_records} kayıt hazır.";
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
    echo "<p class='mb-4'>About sayfanız artık database entegreli!</p>";
    
    echo "<div class='row g-3'>";
    echo "<div class='col-md-4'>";
    echo "<a href='design/about.php' class='btn btn-primary w-100'>";
    echo "<i class='bi bi-pencil-square me-2'></i>İçerik Yönetimi";
    echo "</a>";
    echo "</div>";
    echo "<div class='col-md-4'>";
    echo "<a href='about.php' target='_blank' class='btn btn-success w-100'>";
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
    echo "<h4><i class='bi bi-rocket me-2'></i>Yeni Özellikler</h4>";
    echo "<div class='row text-center'>";
    echo "<div class='col-md-3'><i class='bi bi-cloud-upload fa-2x text-primary mb-2'></i><br><strong>Resim Upload</strong><br><small>Drag & Drop Desteği</small></div>";
    echo "<div class='col-md-3'><i class='bi bi-eye fa-2x text-success mb-2'></i><br><strong>Canlı Önizleme</strong><br><small>Anında Görüntüleme</small></div>";
    echo "<div class='col-md-3'><i class='bi bi-mobile-alt fa-2x text-info mb-2'></i><br><strong>Responsive</strong><br><small>Mobile Uyumlu</small></div>";
    echo "<div class='col-md-3'><i class='bi bi-shield-alt fa-2x text-warning mb-2'></i><br><strong>Güvenli</strong><br><small>Dosya Doğrulama</small></div>";
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
        'install_about_tables.php',
        'create_about_tables.sql'
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
    
    echo "<script>setTimeout(function(){ window.location.href='design/about.php'; }, 2000);</script>";
    echo "<p class='text-center'><em>2 saniye sonra about yönetim paneline yönlendirileceksiniz...</em></p>";
    echo "</div>";
}

echo "    </div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>