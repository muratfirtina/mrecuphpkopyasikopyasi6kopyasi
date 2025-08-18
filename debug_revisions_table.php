<?php
/**
 * Revisions Tablosu Yapı Kontrolü
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Revisions Tablo Yapısı</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>";

echo "<h1>🔍 Revisions Tablosu Yapısı</h1>";

try {
    // 1. Revisions tablosu yapısı
    echo "<div class='section'>";
    echo "<h2>1. revisions Tablosu Kolonları</h2>";
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Kolon Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // 2. Mevcut revizyon kayıtları
    echo "<div class='section'>";
    echo "<h2>2. Mevcut Revizyon Kayıtları</h2>";
    $stmt = $pdo->prepare("
        SELECT r.*, fr.original_name as response_file_name
        FROM revisions r 
        LEFT JOIN file_responses fr ON r.response_id = fr.id 
        WHERE r.upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
        OR r.response_id IN (
            SELECT id FROM file_responses WHERE upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
        )
        ORDER BY r.requested_at ASC
    ");
    $stmt->execute();
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($revisions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Upload ID</th><th>Response ID</th><th>Status</th><th>Credits</th><th>Request Notes</th><th>Admin Notes</th><th>Requested At</th><th>Completed At</th></tr>";
        foreach ($revisions as $rev) {
            echo "<tr>";
            echo "<td>" . substr($rev['id'], 0, 8) . "...</td>";
            echo "<td>" . ($rev['upload_id'] ? substr($rev['upload_id'], 0, 8) . "..." : 'NULL') . "</td>";
            echo "<td>" . ($rev['response_id'] ? substr($rev['response_id'], 0, 8) . "..." : 'NULL') . "</td>";
            echo "<td>{$rev['status']}</td>";
            echo "<td>" . ($rev['credits_charged'] ?: '0') . " TL</td>";
            echo "<td>" . htmlspecialchars(substr($rev['request_notes'] ?: '', 0, 50)) . "</td>";
            echo "<td>" . htmlspecialchars(substr($rev['admin_notes'] ?: '', 0, 50)) . "</td>";
            echo "<td>{$rev['requested_at']}</td>";
            echo "<td>" . ($rev['completed_at'] ?: 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Revizyon kaydı bulunamadı.</p>";
    }
    echo "</div>";

    // 3. İptal sistemi kontrol
    echo "<div class='section'>";
    echo "<h2>3. İptal Sistemi Kontrol</h2>";
    
    // file_cancellations tablosu var mı?
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'file_cancellations'");
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "<p>✅ file_cancellations tablosu mevcut</p>";
            
            // Bu dosya için iptal kayıtları
            $stmt = $pdo->prepare("
                SELECT fc.*, 
                       CASE fc.file_type
                           WHEN 'upload' THEN 'Ana Dosya'
                           WHEN 'response' THEN 'Yanıt Dosyası'
                           WHEN 'revision' THEN 'Revizyon Dosyası'
                           ELSE fc.file_type
                       END as file_type_text
                FROM file_cancellations fc
                WHERE fc.file_id IN (
                    SELECT id FROM file_uploads WHERE id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
                    UNION
                    SELECT id FROM file_responses WHERE upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
                    UNION  
                    SELECT r.id FROM revisions r 
                    WHERE r.upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
                    OR r.response_id IN (SELECT id FROM file_responses WHERE upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4')
                )
                ORDER BY fc.requested_at DESC
            ");
            $stmt->execute();
            $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($cancellations)) {
                echo "<table>";
                echo "<tr><th>Cancellation ID</th><th>File ID</th><th>File Type</th><th>Status</th><th>Refund Amount</th><th>Requested At</th></tr>";
                foreach ($cancellations as $cancel) {
                    echo "<tr>";
                    echo "<td>" . substr($cancel['id'], 0, 8) . "...</td>";
                    echo "<td>" . substr($cancel['file_id'], 0, 8) . "...</td>";
                    echo "<td>{$cancel['file_type_text']}</td>";
                    echo "<td>{$cancel['status']}</td>";
                    echo "<td>" . ($cancel['refund_amount'] ?: '0') . " TL</td>";
                    echo "<td>{$cancel['requested_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Bu dosya için iptal kaydı bulunamadı.</p>";
            }
        } else {
            echo "<p>❌ file_cancellations tablosu bulunamadı</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ İptal sistemi kontrol hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // 4. Çözüm önerisi
    echo "<div class='section'>";
    echo "<h2>4. Çözüm Önerisi</h2>";
    
    $has_is_cancelled = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'is_cancelled') {
            $has_is_cancelled = true;
            break;
        }
    }
    
    if (!$has_is_cancelled) {
        echo "<p style='color: red;'>❌ revisions tablosunda 'is_cancelled' kolonu yok!</p>";
        echo "<p><strong>Çözüm 1:</strong> revisions tablosuna is_cancelled kolonu ekle</p>";
        echo "<p><strong>Çözüm 2:</strong> Mevcut status kolonunu kullan (cancelled durumu)</p>";
        echo "<p><strong>Çözüm 3:</strong> file_cancellations tablosunu kullan</p>";
        
        echo "<h3>Önerilen SQL:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>";
        echo "ALTER TABLE revisions ADD COLUMN is_cancelled TINYINT(1) DEFAULT 0;";
        echo "</pre>";
    } else {
        echo "<p style='color: green;'>✅ revisions tablosunda 'is_cancelled' kolonu mevcut</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='color: red;'>Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
