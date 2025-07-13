<?php
/**
 * Revisions table structure check and fix
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Revisions Table Structure Check</h1>";

try {
    // 1. Revisions tablosunun var olup olmadığını kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'revisions'");
    
    if ($stmt->rowCount() == 0) {
        echo "<h2>❌ Revisions tablosu bulunamadı!</h2>";
        echo "<p>Revisions tablosunu oluşturuluyor...</p>";
        
        // Revisions tablosunu oluştur
        $createTable = "
            CREATE TABLE IF NOT EXISTS `revisions` (
                `id` varchar(36) NOT NULL,
                `upload_id` varchar(36) NOT NULL,
                `response_id` varchar(36) DEFAULT NULL,
                `user_id` varchar(36) NOT NULL,
                `admin_id` varchar(36) DEFAULT NULL,
                `request_notes` text NOT NULL,
                `admin_notes` text DEFAULT NULL,
                `status` enum('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
                `credits_charged` decimal(10,2) DEFAULT 0.00,
                `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `completed_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_upload_id` (`upload_id`),
                KEY `idx_response_id` (`response_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_admin_id` (`admin_id`),
                KEY `idx_status` (`status`),
                KEY `idx_requested_at` (`requested_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTable);
        echo "<p>✅ Revisions tablosu oluşturuldu!</p>";
    } else {
        echo "<h2>✅ Revisions tablosu mevcut</h2>";
    }
    
    // 2. Tablo yapısını kontrol et
    $columns = $pdo->query("DESCRIBE revisions")->fetchAll();
    
    echo "<h3>Mevcut Tablo Yapısı:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $existingColumns = [];
    foreach ($columns as $column) {
        $existingColumns[] = $column['Field'];
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Eksik kolonları kontrol et ve ekle
    $requiredColumns = [
        'id' => "varchar(36) NOT NULL",
        'upload_id' => "varchar(36) NOT NULL",
        'response_id' => "varchar(36) DEFAULT NULL",
        'user_id' => "varchar(36) NOT NULL",
        'admin_id' => "varchar(36) DEFAULT NULL",
        'request_notes' => "text NOT NULL",
        'admin_notes' => "text DEFAULT NULL",
        'status' => "enum('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending'",
        'credits_charged' => "decimal(10,2) DEFAULT 0.00",
        'requested_at' => "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'completed_at' => "timestamp NULL DEFAULT NULL",
        'updated_at' => "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    echo "<h3>Eksik Kolon Kontrolü:</h3>";
    $missingColumns = [];
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            $missingColumns[] = $columnName;
            echo "<p>❌ Eksik kolon: <strong>" . $columnName . "</strong></p>";
            
            // Eksik kolonu ekle
            try {
                $alterSQL = "ALTER TABLE revisions ADD COLUMN `" . $columnName . "` " . $columnDef;
                $pdo->exec($alterSQL);
                echo "<p>✅ Kolon eklendi: <strong>" . $columnName . "</strong></p>";
            } catch (Exception $e) {
                echo "<p>❌ Kolon eklenirken hata: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>✅ Kolon mevcut: <strong>" . $columnName . "</strong></p>";
        }
    }
    
    if (empty($missingColumns)) {
        echo "<p><strong>✅ Tüm gerekli kolonlar mevcut!</strong></p>";
    }
    
    // 4. Örnek revize verileri kontrol et
    echo "<h3>Veri Kontrolü:</h3>";
    $revisionCount = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
    echo "<p>Toplam revize kayıt sayısı: <strong>" . $revisionCount . "</strong></p>";
    
    if ($revisionCount > 0) {
        $sampleRevisions = $pdo->query("SELECT * FROM revisions LIMIT 3")->fetchAll();
        echo "<h4>Örnek Kayıtlar:</h4>";
        echo "<pre>";
        foreach ($sampleRevisions as $rev) {
            print_r($rev);
            echo "\n---\n";
        }
        echo "</pre>";
    }
    
    // 5. Foreign key kontrolleri
    echo "<h3>Referans Kontrolleri:</h3>";
    
    // Users tablosu kontrolü
    $userCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    echo "<p>Users tablosu: " . ($userCheck > 0 ? "✅ Mevcut" : "❌ Yok") . "</p>";
    
    // File uploads tablosu kontrolü
    $uploadCheck = $pdo->query("SHOW TABLES LIKE 'file_uploads'")->rowCount();
    echo "<p>File_uploads tablosu: " . ($uploadCheck > 0 ? "✅ Mevcut" : "❌ Yok") . "</p>";
    
    // File responses tablosu kontrolü
    $responseCheck = $pdo->query("SHOW TABLES LIKE 'file_responses'")->rowCount();
    echo "<p>File_responses tablosu: " . ($responseCheck > 0 ? "✅ Mevcut" : "❌ Yok") . "</p>";
    
    echo "<h2>✅ Kontrol tamamlandı!</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ Hata oluştu:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 5px 10px; text-align: left; }
    th { background-color: #f0f0f0; }
    h1, h2, h3 { color: #333; }
    .success { color: green; }
    .error { color: red; }
</style>
