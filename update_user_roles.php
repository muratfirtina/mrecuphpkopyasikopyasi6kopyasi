<?php
/**
 * Users Tablosu Güncelleme - Design Rolü Ekleme
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Users Güncelleme - Design Rolü</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff8f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>👤 Users Tablosu Güncelleme</h1>";

try {
    // 1. Mevcut role enum'ını kontrol et
    echo "<h2>1. Mevcut Role Yapısını Kontrol Ediliyor...</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn) {
        echo "<div class='info'>✅ Role kolonu mevcut: " . htmlspecialchars($roleColumn['Type']) . "</div>";
        
        // Design rolü var mı kontrol et
        if (strpos($roleColumn['Type'], 'design') !== false) {
            echo "<div class='success'>✅ Design rolü zaten mevcut!</div>";
        } else {
            echo "<div class='warning'>⚠️ Design rolü mevcut değil, ekleniyor...</div>";
            
            // Role enum'ını güncelle - Mevcut enum'ı al
            if (strpos($roleColumn['Type'], "enum('user','admin')") !== false) {
                $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'design') DEFAULT 'user'");
                echo "<div class='success'>✅ Design rolü başarıyla eklendi!</div>";
            } else {
                // Başka enum yapısı varsa genel güncelleme
                $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'design') DEFAULT 'user'");
                echo "<div class='success'>✅ Role enum'ı güncellendi ve design rolü eklendi!</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ Role kolonu bulunamadı!</div>";
        echo "<div class='warning'>⚠️ Role kolonu ekleniyor...</div>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'design') DEFAULT 'user' AFTER phone");
        echo "<div class='success'>✅ Role kolonu ve design rolü eklendi!</div>";
    }

    // 2. Mevcut kullanıcıları listele
    echo "<h2>2. Mevcut Kullanıcılar</h2>";
    
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $stmt->fetchAll();
    
    if (!empty($users)) {
        echo "<div style='overflow-x: auto;'>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Kullanıcı Adı</th>";
        echo "<th style='padding: 8px;'>Email</th>";
        echo "<th style='padding: 8px;'>Rol</th>";
        echo "<th style='padding: 8px;'>Oluşturulma</th>";
        echo "<th style='padding: 8px;'>İşlemler</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $roleClass = $user['role'] === 'admin' ? 'color: red; font-weight: bold;' : 
                        ($user['role'] === 'design' ? 'color: blue; font-weight: bold;' : '');
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars(substr($user['id'], 0, 8)) . "...</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td style='padding: 8px; $roleClass'>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td style='padding: 8px;'>" . date('d.m.Y H:i', strtotime($user['created_at'])) . "</td>";
            echo "<td style='padding: 8px;'>";
            echo "<form method='POST' style='display: inline; margin-right: 5px;'>";
            echo "<input type='hidden' name='action' value='make_design'>";
            echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($user['id']) . "'>";
            echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;'>Design Yap</button>";
            echo "</form>";
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='action' value='make_admin'>";
            echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($user['id']) . "'>";
            echo "<button type='submit' style='background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;'>Admin Yap</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ Henüz kullanıcı bulunmuyor.</div>";
    }

    // 3. Test design kullanıcısı oluştur
    echo "<h2>3. Test Design Kullanıcısı</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'design'");
    $stmt->execute();
    $designUserCount = $stmt->fetchColumn();
    
    if ($designUserCount == 0) {
        echo "<div class='warning'>⚠️ Design rolünde kullanıcı bulunamadı. Test kullanıcısı oluşturuluyor...</div>";
        
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
        
        $designUserId = generateUUID();
        $hashedPassword = password_hash('design123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password, first_name, last_name, role, status, credits, created_at) 
            VALUES (?, 'designer', 'design@mrecu.com', ?, 'Design', 'User', 'design', 'active', 100.00, NOW())
        ");
        $stmt->execute([$designUserId, $hashedPassword]);
        
        echo "<div class='success'>✅ Test design kullanıcısı oluşturuldu:</div>";
        echo "<div class='info'>";
        echo "<strong>Kullanıcı Adı:</strong> designer<br>";
        echo "<strong>Email:</strong> design@mrecu.com<br>";
        echo "<strong>Şifre:</strong> design123<br>";
        echo "<strong>Rol:</strong> design";
        echo "</div>";
    } else {
        echo "<div class='success'>✅ $designUserCount design kullanıcısı mevcut.</div>";
    }

    // 4. POST işlemleri (rol değiştirme)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'make_design' && isset($_POST['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'design' WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            echo "<div class='success'>✅ Kullanıcı design rolüne atandı!</div>";
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        } elseif ($_POST['action'] === 'make_admin' && isset($_POST['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            echo "<div class='success'>✅ Kullanıcı admin rolüne atandı!</div>";
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        }
    }

    // 5. Rol istatistikleri
    echo "<h2>4. Rol İstatistikleri</h2>";
    
    $roleStats = $pdo->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        GROUP BY role 
        ORDER BY count DESC
    ")->fetchAll();
    
    echo "<div class='info'>";
    echo "<strong>Kullanıcı Rolleri:</strong><br>";
    foreach ($roleStats as $stat) {
        $emoji = $stat['role'] === 'admin' ? '👑' : 
                ($stat['role'] === 'design' ? '🎨' : '👤');
        echo "$emoji " . ucfirst($stat['role']) . ": " . $stat['count'] . " kullanıcı<br>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<h3>🔗 Sonraki Adımlar:</h3>";
echo "<ul>";
echo "<li><a href='install_design_sliders.php'>🎨 Design Slider Verilerini Yükle</a></li>";
echo "<li><a href='design/index.php'>🏠 Design Panel</a></li>";
echo "<li><a href='login.php'>🔐 Giriş Yap (design@mrecu.com / design123)</a></li>";
echo "<li><a href='index.php'>🌟 Ana Sayfa (Yeni Slider ile)</a></li>";
echo "</ul>";

echo "</body></html>";
?>
