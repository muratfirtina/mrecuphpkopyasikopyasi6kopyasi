<?php
/**
 * Revision Files Tablosu Kontrol ve Oluşturma
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    // revision_files tablosunun var olup olmadığını kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'revision_files'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "revision_files tablosu bulunamadı. Oluşturuluyor...\n";
        
        // revision_files tablosunu oluştur
        $createTableSQL = "
        CREATE TABLE revision_files (
            id VARCHAR(36) PRIMARY KEY,
            revision_id VARCHAR(36) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_size BIGINT NOT NULL,
            file_type VARCHAR(100),
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            uploaded_by VARCHAR(36),
            admin_notes TEXT,
            downloaded BOOLEAN DEFAULT FALSE,
            download_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (revision_id) REFERENCES revisions(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_revision_id (revision_id),
            INDEX idx_uploaded_by (uploaded_by),
            INDEX idx_upload_date (upload_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTableSQL);
        echo "revision_files tablosu başarıyla oluşturuldu.\n";
    } else {
        echo "revision_files tablosu zaten mevcut.\n";
        
        // Tablo yapısını göster
        $stmt = $pdo->query("DESCRIBE revision_files");
        $columns = $stmt->fetchAll();
        
        echo "Tablo yapısı:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    }
    
    // revisions tablosunun var olup olmadığını da kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'revisions'");
    $revisionsExists = $stmt->fetch();
    
    if (!$revisionsExists) {
        echo "\nUYARI: revisions tablosu bulunamadı! Bu tablo revision_files için gerekli.\n";
    } else {
        echo "\nrevisions tablosu mevcut.\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
