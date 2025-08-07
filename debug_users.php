<?php
/**
 * User and Admin Status Check
 * Kullanıcı ve admin durumu kontrolü
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Kullanıcı ve Admin Durumu Kontrolü</h1>";

try {
    // Users tablosunu kontrol et
    echo "<h2>Users Tablosu Yapısı</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Tüm kullanıcıları listele
    echo "<h2>Mevcut Kullanıcılar</h2>";
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name, role, status FROM users ORDER BY role DESC, username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>Hiç kullanıcı bulunamadı!</p>";
    } else {
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Role</th><th>Status</th></tr>";
        foreach ($users as $user) {
            $roleColor = $user['role'] === 'admin' ? 'color: red; font-weight: bold;' : '';
            $statusColor = $user['status'] === 'active' ? 'color: green;' : 'color: orange;';
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($user['id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
            echo "<td style='$roleColor'>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td style='$statusColor'>" . htmlspecialchars($user['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Admin kullanıcı sayısını kontrol et
    echo "<h2>Admin Kullanıcıları Detayı</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
    $totalAdmins = $stmt->fetchColumn();
    echo "<p>Toplam admin sayısı: $totalAdmins</p>";

    $stmt = $pdo->query("SELECT COUNT(*) as active_admins FROM users WHERE role = 'admin' AND status = 'active'");
    $activeAdmins = $stmt->fetchColumn();
    echo "<p>Aktif admin sayısı: $activeAdmins</p>";

    if ($activeAdmins == 0) {
        echo "<h3>⚠️ Aktif Admin Kullanıcısı Yok!</h3>";
        echo "<p>Bildirim sistemi çalışabilmesi için en az bir aktif admin kullanıcısı gereklidir.</p>";
        
        if ($totalAdmins > 0) {
            echo "<h4>Mevcut Admin Kullanıcılarını Aktif Hale Getir</h4>";
            $stmt = $pdo->query("SELECT id, username, status FROM users WHERE role = 'admin'");
            $inactiveAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($inactiveAdmins as $admin) {
                if ($admin['status'] !== 'active') {
                    echo "<p>Admin {$admin['username']} - Status: {$admin['status']}</p>";
                }
            }
            
            echo "<form method='post'>";
            echo "<input type='hidden' name='action' value='activate_admins'>";
            echo "<button type='submit'>Tüm Admin Kullanıcıları Aktif Hale Getir</button>";
            echo "</form>";
        } else {
            echo "<h4>Yeni Admin Kullanıcısı Oluştur</h4>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='action' value='create_admin'>";
            echo "<p>Username: <input type='text' name='username' value='admin' required></p>";
            echo "<p>Email: <input type='email' name='email' value='admin@mrecu.com' required></p>";
            echo "<p>First Name: <input type='text' name='first_name' value='Admin' required></p>";
            echo "<p>Last Name: <input type='text' name='last_name' value='User' required></p>";
            echo "<p>Password: <input type='password' name='password' value='admin123' required></p>";
            echo "<button type='submit'>Admin Kullanıcısı Oluştur</button>";
            echo "</form>";
        }
    }

    // POST işlemleri
    if ($_POST) {
        echo "<hr><h2>İşlem Sonuçları</h2>";
        
        if ($_POST['action'] === 'activate_admins') {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE role = 'admin'");
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<p style='color: green;'>✓ Tüm admin kullanıcıları aktif hale getirildi!</p>";
                } else {
                    echo "<p style='color: red;'>✗ Admin kullanıcıları aktif hale getirilemedi</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
            }
        }
        
        if ($_POST['action'] === 'create_admin') {
            try {
                $adminId = 'admin-' . uniqid();
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (id, username, email, first_name, last_name, password, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())
                ");
                
                $result = $stmt->execute([
                    $adminId,
                    $_POST['username'],
                    $_POST['email'],
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $hashedPassword
                ]);
                
                if ($result) {
                    echo "<p style='color: green;'>✓ Admin kullanıcısı başarıyla oluşturuldu!</p>";
                    echo "<p>Username: {$_POST['username']}<br>Password: {$_POST['password']}</p>";
                } else {
                    echo "<p style='color: red;'>✗ Admin kullanıcısı oluşturulamadı</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
            }
        }
        
        // Sayfayı yenile
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='debug_notification_system.php'>Bildirim Sistemi Debug</a> | <a href='index.php'>Ana Sayfa</a></p>";
?>