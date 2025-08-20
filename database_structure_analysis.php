<?php
/**
 * Mr ECU - Current Database Structure Analysis
 * Mevcut database yapısını tam olarak analiz eder
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Structure Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .success { color: green; background: #f0f8f0; }
        .error { color: red; background: #f8f0f0; }
        .warning { color: orange; background: #fff8f0; }
        .info { color: blue; background: #f0f0f8; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 5px; }
        .exists { color: green; font-weight: bold; }
        .missing { color: red; font-weight: bold; }
        .highlight { background-color: yellow; }
    </style>
</head>
<body>";

echo "<h1>🔍 Current Database Structure Analysis</h1>";
echo "<p>Mevcut database yapısını analiz edip admin cancel sistemi için gereken kolonları kontrol edelim.</p>";

try {
    // 1. Tüm tabloları listele
    echo "<div class='section'>";
    echo "<h2>1. Mevcut Tablolar</h2>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Toplam " . count($tables) . " tablo bulundu:</strong></p>";
    echo "<div style='columns: 3; column-gap: 20px;'>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<div>📋 <strong>$table</strong> ($count kayıt)</div>";
    }
    echo "</div>";
    echo "</div>";
    
    // 2. users tablosu analizi
    echo "<div class='section'>";
    echo "<h2>2. users Tablosu Analizi</h2>";
    
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("DESCRIBE users");
        $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($userColumns as $col) {
            $class = '';
            if ($col['Field'] === 'role') $class = 'highlight';
            if ($col['Field'] === 'is_admin') $class = 'highlight';
            
            echo "<tr class='$class'>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Admin kullanıcı kontrolü
        $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        echo "<p><strong>Admin kullanıcı sayısı:</strong> $adminCount</p>";
        
        if ($adminCount > 0) {
            $adminUsers = $pdo->query("SELECT id, username, email, role FROM users WHERE role = 'admin' LIMIT 3")->fetchAll();
            echo "<p><strong>Admin kullanıcılar:</strong></p>";
            echo "<ul>";
            foreach ($adminUsers as $admin) {
                echo "<li>{$admin['username']} ({$admin['email']}) - Role: {$admin['role']}</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='error'>❌ users tablosu bulunamadı!</p>";
    }
    echo "</div>";
    
    // 3. Dosya tabloları analizi
    $fileTables = ['file_uploads', 'file_responses', 'revision_files', 'additional_files','revisions'];
    
    foreach ($fileTables as $fileTable) {
        echo "<div class='section'>";
        echo "<h2>3. $fileTable Tablosu Analizi</h2>";
        
        if (in_array($fileTable, $tables)) {
            $stmt = $pdo->query("DESCRIBE $fileTable");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // İptal kolonlarını kontrol et
            $hasCancelled = false;
            $hasCancelledAt = false;
            $hasCancelledBy = false;
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th><th>Status</th></tr>";
            foreach ($columns as $col) {
                $status = '';
                $class = '';
                
                if ($col['Field'] === 'is_cancelled') {
                    $hasCancelled = true;
                    $status = '<span class="exists">✅ Var</span>';
                    $class = 'success';
                }
                if ($col['Field'] === 'cancelled_at') {
                    $hasCancelledAt = true;
                    $status = '<span class="exists">✅ Var</span>';
                    $class = 'success';
                }
                if ($col['Field'] === 'cancelled_by') {
                    $hasCancelledBy = true;
                    $status = '<span class="exists">✅ Var</span>';
                    $class = 'success';
                }
                
                echo "<tr class='$class'>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "<td>{$col['Extra']}</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // İptal kolonları özeti
            echo "<p><strong>İptal Kolonları Durumu:</strong></p>";
            echo "<ul>";
            echo "<li>is_cancelled: " . ($hasCancelled ? '<span class="exists">✅ Var</span>' : '<span class="missing">❌ Yok</span>') . "</li>";
            echo "<li>cancelled_at: " . ($hasCancelledAt ? '<span class="exists">✅ Var</span>' : '<span class="missing">❌ Yok</span>') . "</li>";
            echo "<li>cancelled_by: " . ($hasCancelledBy ? '<span class="exists">✅ Var</span>' : '<span class="missing">❌ Yok</span>') . "</li>";
            echo "</ul>";
            
            // Kayıt sayısı
            $recordCount = $pdo->query("SELECT COUNT(*) FROM $fileTable")->fetchColumn();
            echo "<p><strong>Toplam kayıt:</strong> $recordCount</p>";
            
        } else {
            echo "<p class='warning'>⚠️ $fileTable tablosu bulunamadı!</p>";
        }
        echo "</div>";
    }
    
    // 4. file_cancellations tablosu kontrolü
    echo "<div class='section'>";
    echo "<h2>4. file_cancellations Tablosu Kontrolü</h2>";
    
    if (in_array('file_cancellations', $tables)) {
        $stmt = $pdo->query("DESCRIBE file_cancellations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✅ file_cancellations tablosu mevcut!</p>";
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $cancellationCount = $pdo->query("SELECT COUNT(*) FROM file_cancellations")->fetchColumn();
        echo "<p><strong>İptal talebi sayısı:</strong> $cancellationCount</p>";
        
    } else {
        echo "<p class='error'>❌ file_cancellations tablosu bulunamadı!</p>";
    }
    echo "</div>";
    
    // 5. İhtiyaç analizi
    echo "<div class='section'>";
    echo "<h2>5. Admin Cancel Sistemi İhtiyaç Analizi</h2>";
    
    $needsMigration = false;
    $missingItems = [];
    
    // file_cancellations tablosu kontrolü
    if (!in_array('file_cancellations', $tables)) {
        $needsMigration = true;
        $missingItems[] = "file_cancellations tablosu";
    }
    
    // Dosya tablolarında iptal kolonları kontrolü
    foreach ($fileTables as $fileTable) {
        if (in_array($fileTable, $tables)) {
            $stmt = $pdo->query("SHOW COLUMNS FROM $fileTable LIKE 'is_cancelled'");
            if ($stmt->rowCount() == 0) {
                $needsMigration = true;
                $missingItems[] = "$fileTable.is_cancelled kolonu";
            }
            
            $stmt = $pdo->query("SHOW COLUMNS FROM $fileTable LIKE 'cancelled_at'");
            if ($stmt->rowCount() == 0) {
                $needsMigration = true;
                $missingItems[] = "$fileTable.cancelled_at kolonu";
            }
            
            $stmt = $pdo->query("SHOW COLUMNS FROM $fileTable LIKE 'cancelled_by'");
            if ($stmt->rowCount() == 0) {
                $needsMigration = true;
                $missingItems[] = "$fileTable.cancelled_by kolonu";
            }
        }
    }
    
    if ($needsMigration) {
        echo "<div class='error'>";
        echo "<h3>❌ Migration Gerekli!</h3>";
        echo "<p>Aşağıdaki öğeler eksik:</p>";
        echo "<ul>";
        foreach ($missingItems as $item) {
            echo "<li>$item</li>";
        }
        echo "</ul>";
        echo "<p><strong>Önerilen eylem:</strong> <a href='admin_cancel_migration.php'>Migration scriptini çalıştır</a></p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Sistem Hazır!</h3>";
        echo "<p>Admin cancel sistemi için gerekli tüm database yapıları mevcut.</p>";
        echo "<p><strong>Önerilen eylem:</strong> <a href='test_admin_cancel.php'>Test scriptini çalıştır</a></p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Hata!</h3>";
    echo "<p>Database analizi sırasında hata oluştu: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Sonraki Adımlar:</strong></p>";
echo "<ul>";
echo "<li><a href='admin_cancel_migration.php'>🔧 Migration Script (eksikse)</a></li>";
echo "<li><a href='test_admin_cancel.php'>🧪 Test Script</a></li>";
echo "<li><a href='admin/uploads.php'>📁 Admin Panel</a></li>";
echo "</ul>";

echo "</body></html>";
?>
