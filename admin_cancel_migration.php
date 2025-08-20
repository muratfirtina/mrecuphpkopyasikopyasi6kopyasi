<?php
/**
 * Mr ECU - Admin Cancel System Database Migration
 * Admin iptal sistemi için gerekli database değişiklikleri
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Cancel System Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; background: #f0f8f0; padding: 10px; border: 1px solid green; margin: 10px 0; }
        .error { color: red; background: #f8f0f0; padding: 10px; border: 1px solid red; margin: 10px 0; }
        .info { color: blue; background: #f0f0f8; padding: 10px; border: 1px solid blue; margin: 10px 0; }
        .warning { color: orange; background: #fff8f0; padding: 10px; border: 1px solid orange; margin: 10px 0; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 5px; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h1>🔧 Admin Cancel System Database Migration</h1>";
echo "<p>Bu script admin iptal sistemi için gerekli database değişikliklerini yapar.</p>";

$errors = [];
$successes = [];
$warnings = [];

try {
    // 1. file_cancellations tablosu kontrolü ve oluşturulması
    echo "<h2>1. file_cancellations Tablosu</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_cancellations'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>⚠️ file_cancellations tablosu bulunamadı. Oluşturuluyor...</div>";
        
        $createTable = "
        CREATE TABLE file_cancellations (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            file_id CHAR(36) NOT NULL,
            file_type ENUM('upload', 'response', 'revision', 'additional') NOT NULL,
            reason TEXT NOT NULL,
            credits_to_refund DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_id CHAR(36) NULL,
            admin_notes TEXT NULL,
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_file_id (file_id),
            INDEX idx_status (status),
            INDEX idx_requested_at (requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($pdo->exec($createTable)) {
            $successes[] = "✅ file_cancellations tablosu başarıyla oluşturuldu.";
        } else {
            $errors[] = "❌ file_cancellations tablosu oluşturulamadı.";
        }
    } else {
        $successes[] = "✅ file_cancellations tablosu zaten mevcut.";
    }
    
    // 2. file_uploads tablosuna iptal kolonları ekleme
    echo "<h2>2. file_uploads Tablosu Güncellemeleri</h2>";
    
    // is_cancelled kolonu kontrolü
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'is_cancelled'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>⚠️ file_uploads tablosuna is_cancelled kolonu ekleniyor...</div>";
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN is_cancelled TINYINT(1) DEFAULT 0 AFTER status");
        $successes[] = "✅ file_uploads.is_cancelled kolonu eklendi.";
    } else {
        $successes[] = "✅ file_uploads.is_cancelled kolonu zaten mevcut.";
    }
    
    // cancelled_at kolonu kontrolü
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'cancelled_at'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>⚠️ file_uploads tablosuna cancelled_at kolonu ekleniyor...</div>";
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN cancelled_at DATETIME NULL AFTER is_cancelled");
        $successes[] = "✅ file_uploads.cancelled_at kolonu eklendi.";
    } else {
        $successes[] = "✅ file_uploads.cancelled_at kolonu zaten mevcut.";
    }
    
    // cancelled_by kolonu kontrolü
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'cancelled_by'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>⚠️ file_uploads tablosuna cancelled_by kolonu ekleniyor...</div>";
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN cancelled_by CHAR(36) NULL AFTER cancelled_at");
        $successes[] = "✅ file_uploads.cancelled_by kolonu eklendi.";
    } else {
        $successes[] = "✅ file_uploads.cancelled_by kolonu zaten mevcut.";
    }
    
    // 3. file_responses tablosuna iptal kolonları ekleme
    echo "<h2>3. file_responses Tablosu Güncellemeleri</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_responses'");
    if ($stmt->rowCount() > 0) {
        // is_cancelled kolonu kontrolü
        $stmt = $pdo->query("SHOW COLUMNS FROM file_responses LIKE 'is_cancelled'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE file_responses ADD COLUMN is_cancelled TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE file_responses ADD COLUMN cancelled_at DATETIME NULL");
            $pdo->exec("ALTER TABLE file_responses ADD COLUMN cancelled_by CHAR(36) NULL");
            $successes[] = "✅ file_responses iptal kolonları eklendi.";
        } else {
            $successes[] = "✅ file_responses iptal kolonları zaten mevcut.";
        }
    } else {
        $warnings[] = "⚠️ file_responses tablosu bulunamadı.";
    }
    
    // 4. revision_files tablosuna iptal kolonları ekleme
    echo "<h2>4. revision_files Tablosu Güncellemeleri</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'revision_files'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SHOW COLUMNS FROM revision_files LIKE 'is_cancelled'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE revision_files ADD COLUMN is_cancelled TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE revision_files ADD COLUMN cancelled_at DATETIME NULL");
            $pdo->exec("ALTER TABLE revision_files ADD COLUMN cancelled_by CHAR(36) NULL");
            $successes[] = "✅ revision_files iptal kolonları eklendi.";
        } else {
            $successes[] = "✅ revision_files iptal kolonları zaten mevcut.";
        }
    } else {
        $warnings[] = "⚠️ revision_files tablosu bulunamadı.";
    }
    
    // 5. additional_files tablosuna iptal kolonları ekleme
    echo "<h2>5. additional_files Tablosu Güncellemeleri</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'additional_files'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SHOW COLUMNS FROM additional_files LIKE 'is_cancelled'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE additional_files ADD COLUMN is_cancelled TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE additional_files ADD COLUMN cancelled_at DATETIME NULL");
            $pdo->exec("ALTER TABLE additional_files ADD COLUMN cancelled_by CHAR(36) NULL");
            $successes[] = "✅ additional_files iptal kolonları eklendi.";
        } else {
            $successes[] = "✅ additional_files iptal kolonları zaten mevcut.";
        }
    } else {
        $warnings[] = "⚠️ additional_files tablosu bulunamadı.";
    }
    
    // 6. Admin kullanıcı kontrolü
    echo "<h2>6. Admin Kullanıcı Kontrolü</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        echo "<div class='warning'>⚠️ Hiç admin kullanıcı bulunamadı. Örnek admin oluşturuluyor...</div>";
        
        $adminId = generateUUID();
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password, first_name, last_name, role, status, created_at) 
            VALUES (?, 'admin', 'admin@mrecu.com', ?, 'Admin', 'User', 'admin', 'active', NOW())
        ");
        
        if ($stmt->execute([$adminId, $hashedPassword])) {
            $successes[] = "✅ Admin kullanıcısı oluşturuldu (admin@mrecu.com / admin123)";
        } else {
            $errors[] = "❌ Admin kullanıcısı oluşturulamadı.";
        }
    } else {
        $successes[] = "✅ {$adminCount} admin kullanıcı mevcut.";
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Migration hatası: " . $e->getMessage();
}

// Sonuçları göster
echo "<h2>📋 Migration Sonuçları</h2>";

if (!empty($successes)) {
    echo "<div class='success'>";
    echo "<h3>✅ Başarılı İşlemler:</h3>";
    foreach ($successes as $success) {
        echo "<p>{$success}</p>";
    }
    echo "</div>";
}

if (!empty($warnings)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Uyarılar:</h3>";
    foreach ($warnings as $warning) {
        echo "<p>{$warning}</p>";
    }
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div class='error'>";
    echo "<h3>❌ Hatalar:</h3>";
    foreach ($errors as $error) {
        echo "<p>{$error}</p>";
    }
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>🎉 Migration Tamamlandı!</h3>";
    echo "<p>Admin iptal sistemi için gerekli tüm database değişiklikleri başarıyla uygulandı.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Sonraki Adımlar:</strong></p>";
echo "<ol>";
echo "<li><a href='test_admin_cancel.php'>🧪 Admin İptal Sistemini Test Et</a></li>";
echo "<li><a href='admin/uploads.php'>📁 Admin Panel - Dosyalar</a></li>";
echo "<li><a href='admin/file-cancellations.php'>📋 Admin Panel - İptal Talepleri</a></li>";
echo "</ol>";

echo "</body></html>";
?>
