<?php
/**
 * Mr ECU - Database Table Structure Documentation
 * Veritabanı tablo yapılarını gösterir (sadece yapı, veri değil)
 * 
 * Bu dosya projedeki tüm tabloların yapısını analiz eder ve
 * kolon detaylarını, indexleri ve foreign key'leri gösterir.
 */

require_once 'config/database.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Table Structure - Mr ECU</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f5f5f5; 
            line-height: 1.6; 
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        h2 {
            color: #34495e;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 25px 0 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        h3 {
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
            padding-left: 15px;
            background: #fef9f9;
            padding: 10px 15px;
            margin: 20px 0 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #e3f2fd;
        }
        .primary-key {
            background: #fff3cd !important;
            font-weight: bold;
            color: #856404;
        }
        .foreign-key {
            background: #d4edda !important;
            color: #155724;
        }
        .index-key {
            background: #cce5ff !important;
            color: #004085;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            opacity: 0.9;
            font-size: 0.9em;
        }
        .summary {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .table-info {
            background: #f0f8ff;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 10px 0;
            border-radius: 0 8px 8px 0;
        }
        .export-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .column-type {
            font-family: 'Courier New', monospace;
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        .null-yes { color: #e74c3c; }
        .null-no { color: #27ae60; font-weight: bold; }
        .default-value {
            font-style: italic;
            color: #7f8c8d;
        }
        .navigation {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-height: 400px;
            overflow-y: auto;
            min-width: 200px;
        }
        .navigation h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .navigation a {
            display: block;
            color: #3498db;
            text-decoration: none;
            padding: 5px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .navigation a:hover {
            color: #2980b9;
            background: #f8f9fa;
            padding-left: 10px;
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .navigation {
                position: relative;
                width: 100%;
                margin-bottom: 20px;
            }
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>";

try {
    // Veritabanı bağlantısını kontrol et
    if (!isset($pdo)) {
        throw new Exception('Veritabanı bağlantısı kurulamadı');
    }

    // Genel bilgiler
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    
    echo "<div class='container'>";
    echo "<h1>🗄️ Database Table Structure</h1>";
    
    echo "<div class='summary'>";
    echo "<h3>📊 Genel Bilgiler</h3>";
    echo "<div class='stats'>";
    echo "<div class='stat-card'>";
    echo "<span class='stat-number'>$dbName</span>";
    echo "<span class='stat-label'>Veritabanı Adı</span>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<span class='stat-number'>$tableCount</span>";
    echo "<span class='stat-label'>Toplam Tablo</span>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<span class='stat-number'>" . date('Y-m-d H:i:s') . "</span>";
    echo "<span class='stat-label'>Analiz Tarihi</span>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Tüm tabloları al
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "<div class='export-info'>⚠️ Veritabanında tablo bulunamadı!</div>";
        echo "</div></body></html>";
        exit;
    }

    // Navigasyon menüsü
    echo "<div class='navigation'>";
    echo "<h4>📋 Tablolar</h4>";
    foreach ($tables as $table) {
        $recordCount = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<a href='#table_$table'>$table ($recordCount kayıt)</a>";
    }
    echo "</div>";

    // Her tablo için detaylı analiz
    foreach ($tables as $table) {
        echo "<h2 id='table_$table'>📋 Tablo: $table</h2>";
        
        // Tablo genel bilgileri
        $recordCount = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $tableStatus = $pdo->query("SHOW TABLE STATUS LIKE '$table'")->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='table-info'>";
        echo "<strong>Kayıt Sayısı:</strong> " . number_format($recordCount) . " | ";
        echo "<strong>Engine:</strong> " . ($tableStatus['Engine'] ?? 'N/A') . " | ";
        echo "<strong>Charset:</strong> " . ($tableStatus['Collation'] ?? 'N/A');
        if ($tableStatus['Data_length']) {
            echo " | <strong>Boyut:</strong> " . formatBytes($tableStatus['Data_length']);
        }
        echo "</div>";

        // Kolonlar
        echo "<h3>🔧 Kolonlar</h3>";
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr>";
        echo "<th>Kolon Adı</th>";
        echo "<th>Veri Tipi</th>";
        echo "<th>Null</th>";
        echo "<th>Key</th>";
        echo "<th>Default</th>";
        echo "<th>Extra</th>";
        echo "<th>Açıklama</th>";
        echo "</tr>";
        
        foreach ($columns as $col) {
            $rowClass = '';
            if ($col['Key'] === 'PRI') $rowClass = 'primary-key';
            elseif ($col['Key'] === 'MUL') $rowClass = 'foreign-key';
            elseif ($col['Key'] === 'UNI') $rowClass = 'index-key';
            
            echo "<tr class='$rowClass'>";
            echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
            echo "<td><span class='column-type'>" . htmlspecialchars($col['Type']) . "</span></td>";
            
            $nullClass = $col['Null'] === 'YES' ? 'null-yes' : 'null-no';
            echo "<td class='$nullClass'>" . $col['Null'] . "</td>";
            
            $keyText = $col['Key'];
            if ($keyText === 'PRI') $keyText = '🔑 PRIMARY';
            elseif ($keyText === 'MUL') $keyText = '🔗 FOREIGN';
            elseif ($keyText === 'UNI') $keyText = '🔒 UNIQUE';
            echo "<td>" . $keyText . "</td>";
            
            $defaultValue = $col['Default'] ?? '';
            if ($defaultValue === 'CURRENT_TIMESTAMP') $defaultValue = '⏰ CURRENT_TIMESTAMP';
            elseif (empty($defaultValue) && $col['Null'] === 'YES') $defaultValue = 'NULL';
            echo "<td class='default-value'>" . htmlspecialchars($defaultValue) . "</td>";
            
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "<td>" . getColumnDescription($table, $col['Field']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // İndeksler
        try {
            $stmt = $pdo->query("SHOW INDEX FROM `$table`");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($indexes)) {
                echo "<h3>🔍 İndeksler</h3>";
                echo "<table>";
                echo "<tr><th>İndeks Adı</th><th>Kolon</th><th>Unique</th><th>Type</th></tr>";
                
                foreach ($indexes as $index) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($index['Key_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($index['Column_name']) . "</td>";
                    echo "<td>" . ($index['Non_unique'] == 0 ? '✅ Yes' : '❌ No') . "</td>";
                    echo "<td>" . htmlspecialchars($index['Index_type']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<div class='export-info'>⚠️ İndeks bilgileri alınamadı: " . $e->getMessage() . "</div>";
        }

        // Foreign Key'ler
        try {
            $stmt = $pdo->query("
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME,
                    UPDATE_RULE,
                    DELETE_RULE
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '$table'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($foreignKeys)) {
                echo "<h3>🔗 Foreign Key İlişkileri</h3>";
                echo "<table>";
                echo "<tr><th>Kolon</th><th>Referans Tablo</th><th>Referans Kolon</th><th>Update</th><th>Delete</th></tr>";
                
                foreach ($foreignKeys as $fk) {
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($fk['COLUMN_NAME']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . "</td>";
                    echo "<td>" . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . "</td>";
                    echo "<td>" . htmlspecialchars($fk['UPDATE_RULE']) . "</td>";
                    echo "<td>" . htmlspecialchars($fk['DELETE_RULE']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<div class='export-info'>⚠️ Foreign Key bilgileri alınamadı: " . $e->getMessage() . "</div>";
        }

        echo "<hr style='margin: 30px 0; border: none; height: 2px; background: linear-gradient(90deg, #3498db, #2980b9);'>";
    }

    echo "<div class='export-info'>";
    echo "<h3>💾 Export Bilgileri</h3>";
    echo "<p><strong>Oluşturulma Tarihi:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Toplam Tablo:</strong> " . count($tables) . "</p>";
    echo "<p><strong>Veritabanı:</strong> $dbName</p>";
    echo "<p><strong>Bu dosya sadece tablo yapılarını gösterir, herhangi bir veri içermez.</strong></p>";
    echo "</div>";

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='container'>";
    echo "<div style='color: red; background: #ffe6e6; padding: 20px; border-radius: 8px;'>";
    echo "<h3>❌ Hata Oluştu</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    echo "</div>";
}

echo "</body></html>";

/**
 * Dosya boyutunu okunabilir formata çevirir
 */
function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

/**
 * Kolon açıklamaları döndürür
 */
function getColumnDescription($table, $column) {
    $descriptions = [
        'id' => 'Benzersiz tanımlayıcı (UUID)',
        'user_id' => 'Kullanıcı ID referansı',
        'admin_id' => 'Admin kullanıcı ID referansı', 
        'upload_id' => 'Dosya yükleme ID referansı',
        'file_id' => 'Dosya ID referansı',
        'created_at' => 'Kayıt oluşturulma tarihi',
        'updated_at' => 'Son güncelleme tarihi',
        'deleted_at' => 'Silme tarihi (soft delete)',
        'username' => 'Kullanıcı adı',
        'email' => 'E-posta adresi',
        'password' => 'Şifrelenmiş parola',
        'role' => 'Kullanıcı rolü (user/admin)',
        'status' => 'Durum bilgisi',
        'credits' => 'Kullanıcı kredi bakiyesi',
        'original_name' => 'Orijinal dosya adı',
        'filename' => 'Sunucudaki dosya adı',
        'file_size' => 'Dosya boyutu (bytes)',
        'file_path' => 'Dosya yolu',
        'notes' => 'Kullanıcı notları',
        'admin_notes' => 'Admin notları',
        'is_cancelled' => 'İptal edildi mi?',
        'cancelled_at' => 'İptal edilme tarihi',
        'cancelled_by' => 'İptal eden kullanıcı',
        'credits_charged' => 'Düşürülen kredi miktarı',
        'credits_to_refund' => 'İade edilecek kredi',
        'completed_at' => 'Tamamlanma tarihi',
        'processed_at' => 'İşlenme tarihi',
        'requested_at' => 'Talep tarihi'
    ];
    
    return $descriptions[$column] ?? '';
}

/**
 * UUID generator
 */
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
?>