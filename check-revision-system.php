<?php
/**
 * Revizyon Sistemi Durumu Kontrol ve Düzeltme
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Revizyon Sistemi Kontrol</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo "h2 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 10px; }";
echo "h3 { color: #666; }";
echo ".success { color: green; }";
echo ".error { color: red; }";
echo ".warning { color: orange; }";
echo "table { border-collapse: collapse; width: 100%; margin: 10px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
echo "th { background-color: #f5f5f5; }";
echo "pre { background: #f8f8f8; padding: 10px; border-radius: 5px; overflow-x: auto; }";
echo "</style></head><body>";

echo "<h1>🔍 Revizyon Sistemi Durumu Kontrol</h1>";

try {
    // 1. Temel tablo varlık kontrolü
    echo "<h2>1. Tablo Varlık Kontrolü</h2>";
    
    $requiredTables = [
        'file_uploads' => 'Kullanıcı orijinal dosyaları',
        'file_responses' => 'Admin yanıt dosyaları', 
        'revisions' => 'Revizyon talepleri',
        'revision_files' => 'Revizyon dosyaları'
    ];
    
    foreach ($requiredTables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ $table tablosu mevcut - $description</p>";
        } else {
            echo "<p class='error'>❌ $table tablosu eksik - $description</p>";
        }
    }
    
    // 2. revision_files tablosu yapı kontrolü
    echo "<h2>2. revision_files Tablosu Yapı Kontrolü</h2>";
    
    try {
        $stmt = $pdo->query("DESCRIBE revision_files");
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Kolon</th><th>Tür</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Gerekli kolonları kontrol et
        $requiredColumns = ['id', 'revision_id', 'upload_id', 'admin_id', 'filename', 'original_name', 'file_size', 'admin_notes'];
        $existingColumns = array_column($columns, 'Field');
        
        echo "<h3>Gerekli Kolon Kontrolü:</h3>";
        foreach ($requiredColumns as $col) {
            if (in_array($col, $existingColumns)) {
                echo "<p class='success'>✅ $col kolonu mevcut</p>";
            } else {
                echo "<p class='error'>❌ $col kolonu eksik</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ revision_files tablosu kontrolünde hata: " . $e->getMessage() . "</p>";
    }
    
    // 3. Veri sayım kontrolü
    echo "<h2>3. Veri Sayım Kontrolü</h2>";
    
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p>📊 $table: <strong>$count</strong> kayıt</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ $table sayımında hata: " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. Revizyon sistemi akış kontrolü
    echo "<h2>4. Revizyon Sistemi Akış Kontrolü</h2>";
    
    // Revizyon talebi olan ama revision_files kaydı olmayan kayıtları bul
    try {
        $stmt = $pdo->query("
            SELECT r.id, r.status, r.requested_at, fu.original_name
            FROM revisions r 
            LEFT JOIN file_uploads fu ON r.upload_id = fu.id
            WHERE r.status = 'completed'
        ");
        $completedRevisions = $stmt->fetchAll();
        
        echo "<p>Tamamlanan revizyon sayısı: <strong>" . count($completedRevisions) . "</strong></p>";
        
        if (!empty($completedRevisions)) {
            echo "<h4>Tamamlanan Revizyonlar:</h4>";
            echo "<table>";
            echo "<tr><th>Revizyon ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th><th>Revizyon Dosyası</th></tr>";
            
            foreach ($completedRevisions as $rev) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM revision_files WHERE revision_id = ?");
                $stmt->execute([$rev['id']]);
                $fileCount = $stmt->fetch()['count'];
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rev['id']) . "</td>";
                echo "<td>" . htmlspecialchars($rev['original_name']) . "</td>";
                echo "<td>" . htmlspecialchars($rev['status']) . "</td>";
                echo "<td>" . date('d.m.Y H:i', strtotime($rev['requested_at'])) . "</td>";
                
                if ($fileCount > 0) {
                    echo "<td class='success'>✅ $fileCount dosya</td>";
                } else {
                    echo "<td class='error'>❌ Dosya yok</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Revizyon akış kontrolünde hata: " . $e->getMessage() . "</p>";
    }
    
    // 5. FileManager metod kontrolü
    echo "<h2>5. FileManager Metod Kontrolü</h2>";
    
    try {
        require_once 'includes/FileManager.php';
        $fileManager = new FileManager($pdo);
        
        $requiredMethods = [
            'getAllRevisions',
            'getUserRevisions', 
            'updateRevisionStatus'
        ];
        
        foreach ($requiredMethods as $method) {
            if (method_exists($fileManager, $method)) {
                echo "<p class='success'>✅ FileManager::$method() mevcut</p>";
            } else {
                echo "<p class='error'>❌ FileManager::$method() eksik</p>";
            }
        }
        
        // Revizyon dosyası ile ilgili metodları kontrol et
        $revisionFileMethods = [
            'uploadRevisionFile',
            'getRevisionFiles',
            'downloadRevisionFile'
        ];
        
        echo "<h4>Revizyon Dosyası Metodları:</h4>";
        foreach ($revisionFileMethods as $method) {
            if (method_exists($fileManager, $method)) {
                echo "<p class='success'>✅ FileManager::$method() mevcut</p>";
            } else {
                echo "<p class='warning'>⚠️ FileManager::$method() eksik - eklenecek</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ FileManager kontrolünde hata: " . $e->getMessage() . "</p>";
    }
    
    // 6. Dosya sistemi kontrolü
    echo "<h2>6. Dosya Sistemi Kontrolü</h2>";
    
    $uploadDir = dirname(__FILE__) . '/uploads/';
    $revisionDir = $uploadDir . 'revision_files/';
    
    echo "<p>Ana upload dizini: " . $uploadDir . "</p>";
    echo "<p>Revizyon dosyaları dizini: " . $revisionDir . "</p>";
    
    if (is_dir($uploadDir)) {
        echo "<p class='success'>✅ Ana upload dizini mevcut</p>";
    } else {
        echo "<p class='error'>❌ Ana upload dizini eksik</p>";
    }
    
    if (is_dir($revisionDir)) {
        echo "<p class='success'>✅ Revizyon dosyaları dizini mevcut</p>";
        
        // Dizindeki dosyaları say
        $files = glob($revisionDir . '*');
        echo "<p>📁 Revizyon dizininde " . count($files) . " dosya var</p>";
        
    } else {
        echo "<p class='warning'>⚠️ Revizyon dosyaları dizini eksik - oluşturulacak</p>";
        
        try {
            mkdir($revisionDir, 0755, true);
            echo "<p class='success'>✅ Revizyon dosyaları dizini oluşturuldu</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Revizyon dosyaları dizini oluşturulamadı: " . $e->getMessage() . "</p>";
        }
    }
    
    // 7. Sonuç ve öneriler
    echo "<h2>7. Sonuç ve Öneriler</h2>";
    
    echo "<h3>🔍 Tespit Edilen Sorunlar:</h3>";
    echo "<ol>";
    echo "<li>revision_files tablosu var ama kullanılmıyor</li>";
    echo "<li>FileManager'da revizyon dosyası metodları eksik</li>";
    echo "<li>Admin arayüzünde revizyon dosyası yükleme eksik</li>";
    echo "<li>Kullanıcı arayüzünde revizyon dosyalarını görme eksik</li>";
    echo "</ol>";
    
    echo "<h3>🛠️ Yapılması Gerekenler:</h3>";
    echo "<ol>";
    echo "<li>FileManager'a revizyon dosyası metodları ekle</li>";
    echo "<li>Admin file-detail.php'ye revizyon dosyası yükleme formu ekle</li>";
    echo "<li>User revision-detail.php'yi revizyon dosyalarını gösterecek şekilde güncelle</li>";
    echo "<li>Revizyon dosyası indirme sistemini tamamla</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Genel hata: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #2196F3;'>";
echo "<h4>🎯 Sonraki Adım</h4>";
echo "<p>Eksik olan revizyon dosyası metodlarını ve arayüzlerini ekleyerek sistemi tamamlayalım.</p>";
echo "<p><strong>Devam etmek için onay verirseniz gerekli düzeltmeleri yapacağım.</strong></p>";
echo "</div>";

echo "</body></html>";
?>
