<?php
/**
 * Mr ECU - Chat Tabloları Kurulum
 * Chat/mesajlaşma tabloları kurulumu
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    echo "<h2>Chat Tabloları Kurulum</h2>";
    echo "<pre>";
    
    // file_chats tablosunu oluştur
    $sql1 = "CREATE TABLE IF NOT EXISTS `file_chats` (
      `id` char(36) NOT NULL PRIMARY KEY,
      `file_id` char(36) NOT NULL,
      `file_type` enum('upload','response','revision') NOT NULL DEFAULT 'upload',
      `sender_id` char(36) NOT NULL,
      `sender_type` enum('user','admin') NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      
      INDEX `idx_file_chat` (`file_id`, `file_type`),
      INDEX `idx_sender` (`sender_id`),
      INDEX `idx_created` (`created_at`),
      INDEX `idx_read_status` (`is_read`),
      
      CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql1);
    echo "✓ file_chats tablosu oluşturuldu.\n";
    
    // chat_unread_counts tablosunu oluştur
    $sql2 = "CREATE TABLE IF NOT EXISTS `chat_unread_counts` (
      `id` char(36) NOT NULL PRIMARY KEY,
      `file_id` char(36) NOT NULL,
      `user_id` char(36) NOT NULL,
      `unread_count` int DEFAULT 0,
      `last_read_at` timestamp DEFAULT NULL,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      
      UNIQUE KEY `unique_file_user` (`file_id`, `user_id`),
      INDEX `idx_file_unread` (`file_id`),
      INDEX `idx_user_unread` (`user_id`),
      
      CONSTRAINT `fk_unread_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql2);
    echo "✓ chat_unread_counts tablosu oluşturuldu.\n";
    
    // Tabloların var olduğunu kontrol et
    $tables = $pdo->query("SHOW TABLES LIKE '%chat%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\n<strong>Mevcut Chat Tabloları:</strong>\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Test verisi ekleyelim mi?
    echo "\n<strong>Kurulum Tamamlandı!</strong>\n";
    echo "Chat tabloları başarıyla oluşturuldu.\n";
    echo "</pre>";
    
    echo '<div style="margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">';
    echo '<strong>✓ Başarılı!</strong> Chat sistemi kullanıma hazır.';
    echo '</div>';
    
    echo '<div style="margin-top: 20px;">';
    echo '<a href="index.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Ana Sayfaya Dön</a>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "<strong style='color: red;'>Hata:</strong> " . $e->getMessage() . "\n";
    echo "</pre>";
    
    echo '<div style="margin-top: 20px; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
    echo '<strong>✗ Hata!</strong> Tablo oluşturulurken bir sorun oluştu. Lütfen hata mesajını kontrol edin.';
    echo '</div>';
}
?>
