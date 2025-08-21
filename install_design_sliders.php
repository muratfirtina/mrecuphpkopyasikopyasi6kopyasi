<?php
/**
 * Design Sliders - Örnek Veriler Ekleme
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Design Sliders - Örnek Veriler</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🎨 Design Sliders Örnek Veriler</h1>";

try {
    // UUID generator function
    function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    // Mevcut veri kontrolü
    $stmt = $pdo->query("SELECT COUNT(*) FROM design_sliders");
    $existingCount = $stmt->fetchColumn();
    
    if ($existingCount > 0) {
        echo "<div class='info'>✅ Zaten $existingCount slider mevcut. Ekleme işlemi atlanıyor.</div>";
    } else {
        echo "<h2>🔄 Slider verilerini ekleniyor...</h2>";
        
        // Slider verileri
        $sliders = [
            [
                'id' => generateUUID(),
                'title' => 'Profesyonel ECU Programlama',
                'subtitle' => 'Optimize Edin',
                'description' => 'Magic Motorsport FLEX, Alientech KESS3, AutoTuner ve Launch anza tespit cihazları. Kaliteli yazılım tecrübemiz ve dosya sistemimizle işinizi büyütün.',
                'button_text' => 'Cihazları İncele',
                'button_link' => '#devices',
                'background_image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=1080&fit=crop',
                'background_color' => '#667eea',
                'text_color' => '#ffffff',
                'is_active' => 1,
                'sort_order' => 1
            ],
            [
                'id' => generateUUID(),
                'title' => 'Yüksek Performans',
                'subtitle' => 'Chip Tuning',
                'description' => 'Aracınızın motor performansını maksimuma çıkarın. Güvenli ve profesyonel chip tuning hizmetimizle güç ve tork artışı sağlayın.',
                'button_text' => 'Performans Artışı',
                'button_link' => '#services',
                'background_image' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=1920&h=1080&fit=crop',
                'background_color' => '#e74c3c',
                'text_color' => '#ffffff',
                'is_active' => 1,
                'sort_order' => 2
            ],
            [
                'id' => generateUUID(),
                'title' => 'Güvenlik Sistemleri',
                'subtitle' => 'Immobilizer Çözümleri',
                'description' => 'Anahtar programlama, immobilizer bypass ve güvenlik sistemi çözümleri. Uzman ekibimizle tüm marka ve modeller desteklenir.',
                'button_text' => 'Güvenlik Çözümleri',
                'button_link' => '#security',
                'background_image' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=1920&h=1080&fit=crop',
                'background_color' => '#27ae60',
                'text_color' => '#ffffff',
                'is_active' => 1,
                'sort_order' => 3
            ],
            [
                'id' => generateUUID(),
                'title' => 'Şanzıman Kontrolü',
                'subtitle' => 'TCU Yazılımları',
                'description' => 'Şanzıman kontrol ünitesi yazılımları ile vites geçiş performansını optimize edin. Daha yumuşak ve hızlı vites değişimleri.',
                'button_text' => 'TCU Hizmetleri',
                'button_link' => '#transmission',
                'background_image' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920&h=1080&fit=crop',
                'background_color' => '#9b59b6',
                'text_color' => '#ffffff',
                'is_active' => 1,
                'sort_order' => 4
            ],
            [
                'id' => generateUUID(),
                'title' => 'Kesintisiz Hizmet',
                'subtitle' => '7/24 Teknik Destek',
                'description' => 'Uzman ekibimiz 7 gün 24 saat hizmetinizde. Acil durumlarınızda anında çözüm üretiyoruz. Güvenilir ve hızlı destek garantisi.',
                'button_text' => 'Hemen İletişim',
                'button_link' => 'contact.php',
                'background_image' => 'https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=1920&h=1080&fit=crop',
                'background_color' => '#34495e',
                'text_color' => '#ffffff',
                'is_active' => 1,
                'sort_order' => 5
            ]
        ];

        // Slider verilerini ekle
        $insertSQL = "INSERT INTO design_sliders (
            id, title, subtitle, description, button_text, button_link, 
            background_image, background_color, text_color, is_active, sort_order,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($insertSQL);
        
        foreach ($sliders as $slider) {
            $stmt->execute([
                $slider['id'],
                $slider['title'],
                $slider['subtitle'],
                $slider['description'],
                $slider['button_text'],
                $slider['button_link'],
                $slider['background_image'],
                $slider['background_color'],
                $slider['text_color'],
                $slider['is_active'],
                $slider['sort_order']
            ]);
            
            echo "<div class='success'>✅ Slider eklendi: " . htmlspecialchars($slider['title']) . "</div>";
        }
        
        echo "<div class='success'>🎉 Toplam " . count($sliders) . " slider başarıyla eklendi!</div>";
    }

    // Design ayarları kontrolü ve eklenmesi
    echo "<h2>⚙️ Design ayarları kontrol ediliyor...</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM design_settings");
    $settingsCount = $stmt->fetchColumn();
    
    if ($settingsCount < 5) {
        $settings = [
            [
                'id' => generateUUID(),
                'setting_key' => 'site_theme_color',
                'setting_value' => '#667eea',
                'description' => 'Ana tema rengi'
            ],
            [
                'id' => generateUUID(),
                'setting_key' => 'site_secondary_color',
                'setting_value' => '#764ba2',
                'description' => 'İkincil tema rengi'
            ],
            [
                'id' => generateUUID(),
                'setting_key' => 'hero_typewriter_enable',
                'setting_value' => '1',
                'description' => 'Hero typewriter efektini etkinleştir'
            ],
            [
                'id' => generateUUID(),
                'setting_key' => 'hero_typewriter_words',
                'setting_value' => 'Optimize Edin,Güçlendirin,Geliştirin',
                'description' => 'Typewriter efekti kelimeleri'
            ],
            [
                'id' => generateUUID(),
                'setting_key' => 'hero_animation_speed',
                'setting_value' => '5000',
                'description' => 'Hero slider animasyon hızı (ms)'
            ]
        ];

        $insertSettingsSQL = "INSERT INTO design_settings (
            id, setting_key, setting_value, description, created_at, updated_at
        ) VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($insertSettingsSQL);
        
        foreach ($settings as $setting) {
            // Mevcut ayar var mı kontrol et
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM design_settings WHERE setting_key = ?");
            $checkStmt->execute([$setting['setting_key']]);
            
            if ($checkStmt->fetchColumn() == 0) {
                $stmt->execute([
                    $setting['id'],
                    $setting['setting_key'],
                    $setting['setting_value'],
                    $setting['description']
                ]);
                
                echo "<div class='success'>✅ Ayar eklendi: " . htmlspecialchars($setting['setting_key']) . "</div>";
            }
        }
    } else {
        echo "<div class='info'>✅ Design ayarları zaten mevcut.</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<h3>🔗 Sonraki Adımlar:</h3>";
echo "<ul>";
echo "<li><a href='design/index.php'>🎨 Design Panel</a></li>";
echo "<li><a href='index.php'>🏠 Ana Sayfa (Yeni Slider ile)</a></li>";
echo "<li><a href='design/sliders.php'>🖼️ Slider Yönetimi</a></li>";
echo "</ul>";

echo "</body></html>";
?>
