<?php
/**
 * Design System Kurulum Kılavuzu
 * Bu dosya Design sistemini kurmak için gerekli tüm adımları içerir
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Design System Kurulum Kılavuzu</title>
    <meta charset='UTF-8'>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .success { color: #27ae60; background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #27ae60; }
        .error { color: #e74c3c; background: #fdf2f2; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #e74c3c; }
        .info { color: #3498db; background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #3498db; }
        .warning { color: #f39c12; background: #fef9e7; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #f39c12; }
        .step { 
            background: #f8f9fa; 
            border-radius: 10px; 
            padding: 20px; 
            margin: 20px 0; 
            border-left: 5px solid #667eea;
        }
        .step h3 { color: #667eea; margin-top: 0; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 5px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .btn-success { background: linear-gradient(135deg, #27ae60, #229954); }
        .btn-danger { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .btn-warning { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .checklist { background: white; border-radius: 8px; padding: 15px; }
        .checklist li { margin: 8px 0; }
        .completed { color: #27ae60; }
        .incomplete { color: #e74c3c; }
        h1 { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        h2 { color: #34495e; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #ecf0f1;
        }
        .status-icon { font-size: 24px; margin-right: 10px; }
        .progress-bar {
            background: #ecf0f1;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🎨 Design System Kurulum Kılavuzu</h1>";

// Kurulum durumunu kontrol et
$installationStatus = [];

try {
    // 1. Tablo kontrolü
    $requiredTables = [
        'users' => 'Kullanıcı tablosu',
        'design_sliders' => 'Hero slider tablosu',
        'design_settings' => 'Design ayarları tablosu',
        'content_management' => 'İçerik yönetimi tablosu',
        'media_files' => 'Medya dosyaları tablosu'
    ];
    
    $tablesExist = 0;
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table LIMIT 1");
            $installationStatus['tables'][$table] = true;
            $tablesExist++;
        } catch (Exception $e) {
            $installationStatus['tables'][$table] = false;
        }
    }
    
    // 2. Role kontrolü
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
        $roleColumn = $stmt->fetch();
        $installationStatus['role_updated'] = $roleColumn && strpos($roleColumn['Type'], 'design') !== false;
    } catch (Exception $e) {
        $installationStatus['role_updated'] = false;
    }
    
    // 3. Slider veri kontrolü
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM design_sliders");
        $sliderCount = $stmt->fetchColumn();
        $installationStatus['sliders_data'] = $sliderCount > 0;
        $installationStatus['slider_count'] = $sliderCount;
    } catch (Exception $e) {
        $installationStatus['sliders_data'] = false;
        $installationStatus['slider_count'] = 0;
    }
    
    // 4. Design kullanıcı kontrolü
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'design')");
        $designUsers = $stmt->fetchColumn();
        $installationStatus['design_users'] = $designUsers > 0;
        $installationStatus['design_user_count'] = $designUsers;
    } catch (Exception $e) {
        $installationStatus['design_users'] = false;
        $installationStatus['design_user_count'] = 0;
    }
    
    // 5. Klasör kontrolü
    $requiredFolders = [
        'design/' => 'Design panel klasörü',
        'assets/images/' => 'Medya klasörü',
        'includes/' => 'Include dosyaları'
    ];
    
    $foldersExist = 0;
    foreach ($requiredFolders as $folder => $description) {
        if (is_dir($folder)) {
            $installationStatus['folders'][$folder] = true;
            $foldersExist++;
        } else {
            $installationStatus['folders'][$folder] = false;
        }
    }
    
    // Genel ilerleme hesaplama
    $totalSteps = 5;
    $completedSteps = 0;
    
    if ($tablesExist == count($requiredTables)) $completedSteps++;
    if ($installationStatus['role_updated']) $completedSteps++;
    if ($installationStatus['sliders_data']) $completedSteps++;
    if ($installationStatus['design_users']) $completedSteps++;
    if ($foldersExist == count($requiredFolders)) $completedSteps++;
    
    $progressPercentage = ($completedSteps / $totalSteps) * 100;
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Kurulum kontrolü sırasında hata: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// İlerleme Çubuğu
echo "<div class='card'>";
echo "<h2>📊 Kurulum İlerlemesi</h2>";
echo "<div class='progress-bar'>";
echo "<div class='progress-fill' style='width: {$progressPercentage}%'></div>";
echo "</div>";
echo "<p><strong>" . round($progressPercentage) . "% Tamamlandı</strong> ($completedSteps/$totalSteps adım)</p>";
echo "</div>";

// Kurulum Adımları
echo "<h2>🛠️ Kurulum Adımları</h2>";

// Adım 1: Veritabanı Tabloları
echo "<div class='step'>";
echo "<h3>";
if ($tablesExist == count($requiredTables)) {
    echo "<span class='status-icon'>✅</span>Adım 1: Veritabanı Tabloları";
} else {
    echo "<span class='status-icon'>❌</span>Adım 1: Veritabanı Tabloları";
}
echo "</h3>";

echo "<div class='checklist'>";
echo "<ul>";
foreach ($requiredTables as $table => $description) {
    $status = $installationStatus['tables'][$table] ?? false;
    $class = $status ? 'completed' : 'incomplete';
    $icon = $status ? '✅' : '❌';
    echo "<li class='$class'>$icon $description ($table)</li>";
}
echo "</ul>";
echo "</div>";

if ($tablesExist < count($requiredTables)) {
    echo "<a href='install-database.php' class='btn'>📦 Veritabanı Kurulumunu Çalıştır</a>";
}
echo "</div>";

// Adım 2: Kullanıcı Rolleri
echo "<div class='step'>";
echo "<h3>";
if ($installationStatus['role_updated']) {
    echo "<span class='status-icon'>✅</span>Adım 2: Kullanıcı Rolleri";
} else {
    echo "<span class='status-icon'>❌</span>Adım 2: Kullanıcı Rolleri";
}
echo "</h3>";

echo "<div class='checklist'>";
if ($installationStatus['role_updated']) {
    echo "<div class='success'>✅ Users tablosu design rolünü destekliyor</div>";
    echo "<div class='info'>👥 Toplam " . ($installationStatus['design_user_count'] ?? 0) . " admin/design kullanıcısı mevcut</div>";
} else {
    echo "<div class='error'>❌ Users tablosu design rolünü desteklemiyor</div>";
}
echo "</div>";

if (!$installationStatus['role_updated'] || !$installationStatus['design_users']) {
    echo "<a href='update_user_roles.php' class='btn'>👤 Kullanıcı Rollerini Güncelle</a>";
}
echo "</div>";

// Adım 3: Design Slider Verileri
echo "<div class='step'>";
echo "<h3>";
if ($installationStatus['sliders_data']) {
    echo "<span class='status-icon'>✅</span>Adım 3: Hero Slider Verileri";
} else {
    echo "<span class='status-icon'>❌</span>Adım 3: Hero Slider Verileri";
}
echo "</h3>";

echo "<div class='checklist'>";
if ($installationStatus['sliders_data']) {
    echo "<div class='success'>✅ " . ($installationStatus['slider_count'] ?? 0) . " slider verisi mevcut</div>";
} else {
    echo "<div class='error'>❌ Henüz slider verisi yüklenmemiş</div>";
}
echo "</div>";

if (!$installationStatus['sliders_data']) {
    echo "<a href='install_design_sliders.php' class='btn'>🎨 Slider Verilerini Yükle</a>";
}
echo "</div>";

// Adım 4: Klasör Yapısı
echo "<div class='step'>";
echo "<h3>";
if ($foldersExist == count($requiredFolders)) {
    echo "<span class='status-icon'>✅</span>Adım 4: Klasör Yapısı";
} else {
    echo "<span class='status-icon'>❌</span>Adım 4: Klasör Yapısı";
}
echo "</h3>";

echo "<div class='checklist'>";
echo "<ul>";
foreach ($requiredFolders as $folder => $description) {
    $status = $installationStatus['folders'][$folder] ?? false;
    $class = $status ? 'completed' : 'incomplete';
    $icon = $status ? '✅' : '❌';
    echo "<li class='$class'>$icon $description ($folder)</li>";
}
echo "</ul>";
echo "</div>";

if ($foldersExist < count($requiredFolders)) {
    echo "<button onclick='createFolders()' class='btn'>📁 Eksik Klasörleri Oluştur</button>";
}
echo "</div>";

// Adım 5: Son Kontroller
echo "<div class='step'>";
echo "<h3><span class='status-icon'>🔍</span>Adım 5: Son Kontroller ve Test</h3>";

echo "<div class='checklist'>";
echo "<h4>🧪 Test Linkleri:</h4>";
echo "<div class='grid'>";

if ($installationStatus['design_users']) {
    echo "<div class='card'>";
    echo "<h5>🎨 Design Panel</h5>";
    echo "<p>Design yönetim paneline erişin</p>";
    echo "<a href='design/index.php' class='btn btn-success'>Design Panel</a>";
    echo "</div>";
}

echo "<div class='card'>";
echo "<h5>🏠 Ana Sayfa</h5>";
echo "<p>Yeni database temelli ana sayfayı görün</p>";
echo "<a href='index.php' class='btn btn-success'>Ana Sayfa</a>";
echo "</div>";

echo "<div class='card'>";
echo "<h5>🔐 Giriş</h5>";
echo "<p>Test kullanıcıları ile giriş yapın</p>";
echo "<a href='login.php' class='btn btn-warning'>Giriş Yap</a>";
echo "</div>";

echo "<div class='card'>";
echo "<h5>👑 Admin Panel</h5>";
echo "<p>Mevcut admin paneline erişin</p>";
echo "<a href='admin/index.php' class='btn btn-danger'>Admin Panel</a>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";

// Test Kullanıcıları Bilgi
if ($installationStatus['design_users']) {
    echo "<div class='info'>";
    echo "<h4>🔑 Test Kullanıcı Bilgileri:</h4>";
    echo "<strong>Admin Kullanıcı:</strong> admin@mrecu.com / admin123<br>";
    echo "<strong>Design Kullanıcı:</strong> design@mrecu.com / design123<br>";
    echo "<strong>Normal Kullanıcı:</strong> test@mrecu.com / test123";
    echo "</div>";
}

// Kurulum Tamamlandı
if ($progressPercentage == 100) {
    echo "<div class='success'>";
    echo "<h3>🎉 Kurulum Tamamlandı!</h3>";
    echo "<p>Design sistemi başarıyla kuruldu. Artık aşağıdaki özellikleri kullanabilirsiniz:</p>";
    echo "<ul>";
    echo "<li>✅ Database temelli Hero Slider yönetimi</li>";
    echo "<li>✅ Site renk ve tema ayarları</li>";
    echo "<li>✅ İçerik yönetim sistemi</li>";
    echo "<li>✅ Medya dosya yönetimi</li>";
    echo "<li>✅ Typewriter efekti ve animasyonlar</li>";
    echo "<li>✅ Responsive design panel</li>";
    echo "</ul>";
    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<a href='design/index.php' class='btn btn-success' style='font-size: 16px; padding: 15px 30px;'>🚀 Design Panel'e Git</a>";
    echo "</div>";
    echo "</div>";
}

// Troubleshooting
echo "<h2>🔧 Sorun Giderme</h2>";
echo "<div class='warning'>";
echo "<h4>⚠️ Sık Karşılaşılan Sorunlar:</h4>";
echo "<ul>";
echo "<li><strong>Tablolar oluşmuyor:</strong> Veritabanı bağlantı ayarlarını kontrol edin</li>";
echo "<li><strong>Design panel açılmıyor:</strong> Kullanıcı rolünün admin veya design olduğundan emin olun</li>";
echo "<li><strong>Slider görünmüyor:</strong> design_sliders tablosunda veri olduğundan emin olun</li>";
echo "<li><strong>Medya yükleme çalışmıyor:</strong> assets/images/ klasörünün yazma izni olduğundan emin olun</li>";
echo "<li><strong>Typewriter çalışmıyor:</strong> design_settings tablosunda typewriter ayarlarını kontrol edin</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<script>
function createFolders() {
    // AJAX ile klasör oluşturma
    fetch('create_folders.php', {
        method: 'POST'
    })
    .then(response => response.text())
    .then(data => {
        alert('Klasörler oluşturulmaya çalışıldı. Sayfa yeniden yüklenecek.');
        location.reload();
    });
}
</script>";

echo "</body></html>";
?>
