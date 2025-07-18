<?php
/**
 * Users Tablosu Yapı Kontrol Testi
 */

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Users Tablosu Yapı Kontrolü</h1>";

try {
    // Database bağlantısı
    require_once 'config/database.php';
    
    if (!isset($pdo) || !$pdo) {
        echo "<p style='color: red;'>❌ PDO bağlantısı bulunamadı!</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database bağlantısı başarılı!</p>";
    
    // Users tablosu yapısını kontrol et
    echo "<h2>Users Tablosu Yapısı:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    $hasCredits = false;
    $hasCreditQuota = false;
    $hasCreditUsed = false;
    
    foreach ($columns as $column) {
        $highlight = '';
        if ($column['Field'] === 'credits') {
            $highlight = 'background: yellow;';
            $hasCredits = true;
        } elseif ($column['Field'] === 'credit_quota') {
            $highlight = 'background: lightgreen;';
            $hasCreditQuota = true;
        } elseif ($column['Field'] === 'credit_used') {
            $highlight = 'background: lightblue;';
            $hasCreditUsed = true;
        }
        
        echo "<tr style='$highlight'>";
        echo "<td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Kredi sistemi durumu
    echo "<h2>Kredi Sistemi Durumu:</h2>";
    
    if ($hasCredits) {
        echo "<p style='color: orange;'>⚠️ Eski kredi sistemi kolonu bulundu: <strong>credits</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Eski kredi sistemi kolonu yok: <strong>credits</strong></p>";
    }
    
    if ($hasCreditQuota) {
        echo "<p style='color: green;'>✅ Ters kredi sistemi kolonu bulundu: <strong>credit_quota</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Ters kredi sistemi kolonu yok: <strong>credit_quota</strong></p>";
    }
    
    if ($hasCreditUsed) {
        echo "<p style='color: green;'>✅ Ters kredi sistemi kolonu bulundu: <strong>credit_used</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Ters kredi sistemi kolonu yok: <strong>credit_used</strong></p>";
    }
    
    // Sistem durumu
    echo "<h2>Sistem Durumu:</h2>";
    
    if ($hasCreditQuota && $hasCreditUsed) {
        echo "<p style='color: green; font-weight: bold;'>✅ TERS KREDİ SİSTEMİ KURULU!</p>";
        
        // Örnek veri kontrol et
        $stmt = $pdo->query("SELECT id, username, credit_quota, credit_used, (credit_quota - credit_used) as available_credits FROM users WHERE role = 'user' LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($users)) {
            echo "<h3>Örnek Kullanıcı Verileri:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Username</th><th>Kredi Kotası</th><th>Kullanılan</th><th>Kullanılabilir</th>";
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['credit_quota']) . "</td>";
                echo "<td>" . htmlspecialchars($user['credit_used']) . "</td>";
                echo "<td>" . htmlspecialchars($user['available_credits']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } elseif ($hasCredits) {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ ESKİ KREDİ SİSTEMİ AKTİF!</p>";
        echo "<p>Ters kredi sistemine geçmek için migration çalıştırılmalı.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ KREDİ SİSTEMİ BULUNAMADI!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/'>Admin Paneline Dön</a></p>";
?>
