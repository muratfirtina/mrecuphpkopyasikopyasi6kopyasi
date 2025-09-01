<?php
/**
 * Design System Demo ve Test Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Design System Demo - Mr ECU</title>
    
    <!-- Bootstrap CSS -->
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    
    <!-- Font Awesome -->
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .demo-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 15px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .demo-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 5px;
        }
        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-online { background: #27ae60; color: white; }
        .status-offline { background: #e74c3c; color: white; }
        .status-partial { background: #f39c12; color: white; }
        .hero-demo {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=600&fit=crop');
            background-size: cover;
            background-position: center;
            border-radius: 15px;
            padding: 60px 30px;
            text-align: center;
            color: white;
            margin: 20px 0;
        }
        .typewriter-demo {
            color: #ff6b35;
            font-weight: bold;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 15px 0;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>";

try {
    // Sistem durumunu kontrol et
    $systemStatus = [];
    
    // Database bağlantısı
    try {
        $pdo->query('SELECT 1');
        $systemStatus['database'] = 'online';
    } catch (Exception $e) {
        $systemStatus['database'] = 'offline';
    }
    
    // Design tabloları
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM design_sliders");
        $sliderCount = $stmt->fetchColumn();
        $systemStatus['sliders'] = $sliderCount > 0 ? 'online' : 'partial';
        $systemStatus['slider_count'] = $sliderCount;
    } catch (Exception $e) {
        $systemStatus['sliders'] = 'offline';
        $systemStatus['slider_count'] = 0;
    }
    
    // Design kullanıcıları
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'design')");
        $designUsers = $stmt->fetchColumn();
        $systemStatus['users'] = $designUsers > 0 ? 'online' : 'offline';
        $systemStatus['user_count'] = $designUsers;
    } catch (Exception $e) {
        $systemStatus['users'] = 'offline';
        $systemStatus['user_count'] = 0;
    }
    
    // Medya klasörü
    $systemStatus['media'] = is_dir('assets/images/') && is_writable('assets/images/') ? 'online' : 'offline';
    
} catch (Exception $e) {
    $systemStatus = [
        'database' => 'offline',
        'sliders' => 'offline',
        'users' => 'offline',
        'media' => 'offline'
    ];
}

echo "<div class='demo-container'>";

// Header
echo "<div class='demo-card text-center'>";
echo "<h1 class='display-4 mb-4'>🎨 Design System Demo</h1>";
echo "<p class='lead'>Mr ECU için geliştirilmiş database temelli design yönetim sistemi</p>";

// Sistem Durumu
echo "<div class='row mt-4'>";
echo "<div class='col-md-3'>";
echo "<h6>Database</h6>";
$dbClass = $systemStatus['database'] === 'online' ? 'status-online' : 'status-offline';
echo "<span class='status-badge $dbClass'>" . ucfirst($systemStatus['database']) . "</span>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<h6>Slider Sistemi</h6>";
$sliderClass = $systemStatus['sliders'] === 'online' ? 'status-online' : 
              ($systemStatus['sliders'] === 'partial' ? 'status-partial' : 'status-offline');
echo "<span class='status-badge $sliderClass'>" . ($systemStatus['slider_count'] ?? 0) . " Slider</span>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<h6>Kullanıcılar</h6>";
$userClass = $systemStatus['users'] === 'online' ? 'status-online' : 'status-offline';
echo "<span class='status-badge $userClass'>" . ($systemStatus['user_count'] ?? 0) . " Design User</span>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<h6>Medya Sistemi</h6>";
$mediaClass = $systemStatus['media'] === 'online' ? 'status-online' : 'status-offline';
echo "<span class='status-badge $mediaClass'>" . ucfirst($systemStatus['media']) . "</span>";
echo "</div>";
echo "</div>";

echo "</div>";

// Hero Demo
if ($systemStatus['sliders'] === 'online') {
    echo "<div class='demo-card'>";
    echo "<h2>🖼️ Hero Slider Demosu</h2>";
    echo "<div class='hero-demo'>";
    echo "<h1 class='display-4'>Profesyonel ECU Programlama</h1>";
    echo "<h2 class='display-6'><span class='typewriter-demo' id='typewriterDemo'>Optimize Edin</span></h2>";
    echo "<p class='lead'>Database temelli, dinamik hero slider sistemi</p>";
    echo "<a href='index.php' class='demo-btn' target='_blank'>Canlı Slider'ı Gör</a>";
    echo "</div>";
    echo "</div>";
}

// Özellikler
echo "<div class='demo-card'>";
echo "<h2>⚡ Özellikler</h2>";
echo "<div class='demo-grid'>";

echo "<div class='feature-card text-center'>";
echo "<i class='bi bi-sliders-h feature-icon'></i>";
echo "<h5>Hero Slider Yönetimi</h5>";
echo "<p>Database temelli slider sistemi. Resim, metin, buton ve renkleri kolayca düzenleyin.</p>";
echo "<a href='design/sliders.php' class='demo-btn'>Slider Panel</a>";
echo "</div>";

echo "<div class='feature-card text-center'>";
echo "<i class='bi bi-palette feature-icon'></i>";
echo "<h5>Tema Ayarları</h5>";
echo "<p>Site renklerini, typewriter efektini ve genel tasarım ayarlarını yönetin.</p>";
echo "<a href='design/settings.php' class='demo-btn'>Ayarlar</a>";
echo "</div>";

echo "<div class='feature-card text-center'>";
echo "<i class='bi bi-edit feature-icon'></i>";
echo "<h5>İçerik Yönetimi</h5>";
echo "<p>Sayfa içeriklerini, metinleri ve diğer elemanları database üzerinden düzenleyin.</p>";
echo "<a href='design/content.php' class='demo-btn'>İçerik Panel</a>";
echo "</div>";

echo "<div class='feature-card text-center'>";
echo "<i class='bi bi-images feature-icon'></i>";
echo "<h5>Medya Yönetimi</h5>";
echo "<p>Resim yükleme, düzenleme ve organize etme sistemi.</p>";
echo "<a href='design/media.php' class='demo-btn'>Medya Panel</a>";
echo "</div>";

echo "<div class='feature-card text-center'>";
echo "<i class='bi bi-mobile-alt feature-icon'></i>";
echo "<h5>Responsive Design</h5>";
echo "<p>Tüm cihazlarda mükemmel görünüm ve kullanıcı deneyimi.</p>";
echo "<span class='demo-btn' style='background: #27ae60;'>✓ Responsive</span>";
echo "</div>";

echo "<div class='feature-card text-center'>";
echo "<i class='bi bi-magic feature-icon'></i>";
echo "<h5>Typewriter Efekti</h5>";
echo "<p>Dinamik yazma efekti ile hero bölümünde etkileyici animasyonlar.</p>";
echo "<span class='demo-btn' style='background: #e74c3c;'>✓ Animasyonlu</span>";
echo "</div>";

echo "</div>";
echo "</div>";

// Teknik Detaylar
echo "<div class='demo-card'>";
echo "<h2>🔧 Teknik Detaylar</h2>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h5>Database Tabloları:</h5>";
echo "<ul>";
echo "<li><code>design_sliders</code> - Hero slider verileri</li>";
echo "<li><code>design_settings</code> - Site ayarları</li>";
echo "<li><code>content_management</code> - İçerik yönetimi</li>";
echo "<li><code>media_files</code> - Medya dosyaları</li>";
echo "</ul>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h5>Teknolojiler:</h5>";
echo "<ul>";
echo "<li>PHP 8.0+ & PDO</li>";
echo "<li>Bootstrap 5.3</li>";
echo "<li>Font Awesome 6.4</li>";
echo "<li>jQuery & AJAX</li>";
echo "<li>SweetAlert2</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<h5>Örnek Database Yapısı:</h5>";
echo "<div class='code-block'>";
echo "CREATE TABLE design_sliders (
    id VARCHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    button_text VARCHAR(100),
    button_link VARCHAR(500),
    background_image VARCHAR(500),
    background_color VARCHAR(20) DEFAULT '#667eea',
    text_color VARCHAR(20) DEFAULT '#ffffff',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);";
echo "</div>";

echo "</div>";

// Hızlı Linkler
echo "<div class='demo-card'>";
echo "<h2>🚀 Hızlı Erişim</h2>";
echo "<div class='row text-center'>";

echo "<div class='col-md-3 mb-3'>";
echo "<a href='design/index.php' class='demo-btn' style='width: 100%; padding: 20px;'>";
echo "<i class='bi bi-tachometer-alt d-block mb-2' style='font-size: 2rem;'></i>";
echo "Design Dashboard";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3 mb-3'>";
echo "<a href='index.php' class='demo-btn' style='width: 100%; padding: 20px; background: #27ae60;'>";
echo "<i class='bi bi-home d-block mb-2' style='font-size: 2rem;'></i>";
echo "Ana Sayfa";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3 mb-3'>";
echo "<a href='login.php' class='demo-btn' style='width: 100%; padding: 20px; background: #e74c3c;'>";
echo "<i class='bi bi-sign-in-alt d-block mb-2' style='font-size: 2rem;'></i>";
echo "Giriş Yap";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3 mb-3'>";
echo "<a href='design_installation_guide.php' class='demo-btn' style='width: 100%; padding: 20px; background: #f39c12;'>";
echo "<i class='bi bi-wrench d-block mb-2' style='font-size: 2rem;'></i>";
echo "Kurulum Kılavuzu";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</div>";

// Test Kullanıcıları
if ($systemStatus['users'] === 'online') {
    echo "<div class='demo-card'>";
    echo "<h2>👤 Test Kullanıcıları</h2>";
    echo "<div class='row'>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='feature-card text-center'>";
    echo "<i class='bi bi-crown text-danger' style='font-size: 2rem;'></i>";
    echo "<h6 class='mt-2'>Admin</h6>";
    echo "<p><strong>Email:</strong> admin@mrecu.com<br><strong>Şifre:</strong> admin123</p>";
    echo "<span class='badge bg-danger'>Tam Yetki</span>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='feature-card text-center'>";
    echo "<i class='bi bi-paint-brush text-primary' style='font-size: 2rem;'></i>";
    echo "<h6 class='mt-2'>Designer</h6>";
    echo "<p><strong>Email:</strong> design@mrecu.com<br><strong>Şifre:</strong> design123</p>";
    echo "<span class='badge bg-primary'>Design Yetkisi</span>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='feature-card text-center'>";
    echo "<i class='bi bi-user text-success' style='font-size: 2rem;'></i>";
    echo "<h6 class='mt-2'>User</h6>";
    echo "<p><strong>Email:</strong> test@mrecu.com<br><strong>Şifre:</strong> test123</p>";
    echo "<span class='badge bg-success'>Normal Kullanıcı</span>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
}

echo "</div>";

// JavaScript
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "<script>
// Typewriter Demo
class TypeWriterDemo {
    constructor(element, words, wait = 3000) {
        this.element = element;
        this.words = words;
        this.wait = parseInt(wait, 10);
        this.txt = '';
        this.wordIndex = 0;
        this.isDeleting = false;
        this.type();
    }

    type() {
        const current = this.wordIndex % this.words.length;
        const fullTxt = this.words[current];

        if (this.isDeleting) {
            this.txt = fullTxt.substring(0, this.txt.length - 1);
        } else {
            this.txt = fullTxt.substring(0, this.txt.length + 1);
        }

        this.element.innerHTML = this.txt;

        let typeSpeed = 150;

        if (this.isDeleting) {
            typeSpeed /= 2;
        }

        if (!this.isDeleting && this.txt === fullTxt) {
            typeSpeed = this.wait;
            this.isDeleting = true;
        } else if (this.isDeleting && this.txt === '') {
            this.isDeleting = false;
            this.wordIndex++;
            typeSpeed = 500;
        }

        setTimeout(() => this.type(), typeSpeed);
    }
}

// Initialize demo
document.addEventListener('DOMContentLoaded', function() {
    const typewriterElement = document.querySelector('#typewriterDemo');
    if (typewriterElement) {
        const words = ['Optimize Edin', 'Güçlendirin', 'Geliştirin', 'Modernleştirin'];
        new TypeWriterDemo(typewriterElement, words, 2000);
    }
});
</script>";

echo "</body></html>";
?>
