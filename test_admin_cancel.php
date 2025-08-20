<?php
/**
 * Mr ECU - Admin Direct Cancel Test
 * Test dosyası: Admin'in direkt dosya iptal etme özelliğini test eder
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/FileCancellationManager.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Admin Direct Cancel Test</h1>";

// Admin kullanıcı ID'sini bul (test için)
try {
    $stmt = $pdo->query("SELECT id, username, role FROM users WHERE role = 'admin' LIMIT 1");
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        die("Test için admin kullanıcı bulunamadı. Role kolonu 'admin' olan kullanıcı gerekli.");
    }
    
    echo "<p><strong>Test Admin:</strong> {$adminUser['username']} ({$adminUser['id']})</p>";
    
    // Test dosyası bul (basit yaklaşım)
    $stmt = $pdo->query("
        SELECT id, original_name, user_id
        FROM file_uploads 
        ORDER BY upload_date DESC
        LIMIT 1
    ");
    $testFile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testFile) {
        die("Test için uygun dosya bulunamadı (herhangi bir ana dosya).");
    }
    
    echo "<p><strong>Test Dosyası:</strong> {$testFile['original_name']} (ID: {$testFile['id']})</p>";
    echo "<p><strong>Dosya Sahibi:</strong> {$testFile['user_id']}</p>";
    
    // FileCancellationManager'ı test et
    $cancellationManager = new FileCancellationManager($pdo);
    
    echo "<hr><h2>Admin Direct Cancel Test</h2>";
    
    if (isset($_POST['test_cancel'])) {
        $result = $cancellationManager->adminDirectCancellation(
            $testFile['id'],
            'upload',
            $adminUser['id'],
            'Test amaçlı iptal - ' . date('Y-m-d H:i:s')
        );
        
        echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; ";
        echo $result['success'] ? "background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724;" : "background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;";
        echo "'>";
        echo "<strong>" . ($result['success'] ? 'BAŞARILI' : 'HATA') . ":</strong> " . $result['message'];
        echo "</div>";
        
        if ($result['success']) {
            echo "<p><em>Dosya başarıyla iptal edildi. Veritabanını kontrol edebilirsiniz.</em></p>";
            
            // Güncellenmiş durumu göster
            try {
                // Tablo yapısını kontrol et
                $checkStmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'is_cancelled'");
                $hasCancelledColumn = $checkStmt->rowCount() > 0;
                
                if ($hasCancelledColumn) {
                    $stmt = $pdo->prepare("SELECT is_cancelled, cancelled_at, cancelled_by FROM file_uploads WHERE id = ?");
                    $stmt->execute([$testFile['id']]);
                    $updatedFile = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<p><strong>Güncellenmiş Durum:</strong></p>";
                    echo "<ul>";
                    echo "<li>İptal Edildi: " . ($updatedFile['is_cancelled'] ? 'Evet' : 'Hayır') . "</li>";
                    echo "<li>İptal Tarihi: " . ($updatedFile['cancelled_at'] ?? 'Belirtilmemiş') . "</li>";
                    echo "<li>İptal Eden Admin: " . ($updatedFile['cancelled_by'] ?? 'Belirtilmemiş') . "</li>";
                    echo "</ul>";
                } else {
                    echo "<p><em>Not: file_uploads tablosunda is_cancelled kolonu bulunamadı. Bu normal olabilir.</em></p>";
                }
            } catch (Exception $e) {
                echo "<p><em>Durum kontrolü yapılamadı: " . $e->getMessage() . "</em></p>";
            }
            
            // İptal kaydını kontrol et
            $stmt = $pdo->prepare("
                SELECT fc.*, u.username as user_username 
                FROM file_cancellations fc 
                LEFT JOIN users u ON fc.user_id = u.id 
                WHERE fc.file_id = ? AND fc.file_type = 'upload' 
                ORDER BY fc.requested_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$testFile['id']]);
            $cancellationRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cancellationRecord) {
                echo "<p><strong>İptal Kaydı:</strong></p>";
                echo "<ul>";
                echo "<li>Kayıt ID: {$cancellationRecord['id']}</li>";
                echo "<li>Kullanıcı: {$cancellationRecord['user_username']}</li>";
                echo "<li>Durum: {$cancellationRecord['status']}</li>";
                echo "<li>İade Edilen Kredi: {$cancellationRecord['credits_to_refund']}</li>";
                echo "<li>Admin Notu: {$cancellationRecord['admin_notes']}</li>";
                echo "<li>Talep Tarihi: {$cancellationRecord['requested_at']}</li>";
                echo "<li>İşlem Tarihi: {$cancellationRecord['processed_at']}</li>";
                echo "</ul>";
            }
        }
    }
    
    // Test butonları
    if (!isset($_POST['test_cancel'])) {
        echo "<form method='POST'>";
        echo "<button type='submit' name='test_cancel' value='1' style='padding: 10px 20px; background-color: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test: Dosyayı İptal Et</button>";
        echo "</form>";
        echo "<p><em>Bu test, seçilen dosyayı gerçekten iptal edecektir. Dikkatli olun!</em></p>";
    }
    
    echo "<hr><h2>Mevcut İptal Talepleri</h2>";
    
    // Mevcut iptal taleplerini göster
    $stmt = $pdo->query("
        SELECT fc.*, u.username, u.first_name, u.last_name,
               CASE fc.file_type
                   WHEN 'upload' THEN fu.original_name
                   WHEN 'response' THEN fr.original_name
                   WHEN 'revision' THEN rf.original_name
                   WHEN 'additional' THEN af.original_name
               END as file_name
        FROM file_cancellations fc
        LEFT JOIN users u ON fc.user_id = u.id
        LEFT JOIN file_uploads fu ON fc.file_type = 'upload' AND fc.file_id = fu.id
        LEFT JOIN file_responses fr ON fc.file_type = 'response' AND fc.file_id = fr.id
        LEFT JOIN revision_files rf ON fc.file_type = 'revision' AND fc.file_id = rf.id
        LEFT JOIN additional_files af ON fc.file_type = 'additional' AND fc.file_id = af.id
        ORDER BY fc.requested_at DESC
        LIMIT 10
    ");
    $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cancellations)) {
        echo "<p>Henüz iptal talebi bulunmuyor.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f8f9fa;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Kullanıcı</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Dosya</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Tip</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Durum</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Kredi İadesi</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Tarih</th>";
        echo "</tr>";
        
        foreach ($cancellations as $cancellation) {
            $statusColor = [
                'pending' => '#ffc107',
                'approved' => '#28a745', 
                'rejected' => '#dc3545'
            ][$cancellation['status']] ?? '#6c757d';
            
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$cancellation['username']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($cancellation['file_name'] ?? 'Bilinmiyor') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . strtoupper($cancellation['file_type']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd; color: {$statusColor}; font-weight: bold;'>" . strtoupper($cancellation['status']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$cancellation['credits_to_refund']} kredi</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . date('d.m.Y H:i', strtotime($cancellation['requested_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<strong>HATA:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='admin/uploads.php'>Admin Panel - Dosyalar</a> | <a href='admin/file-cancellations.php'>İptal Talepleri</a></p>";
echo "<p><em>Test tamamlandı - " . date('Y-m-d H:i:s') . "</em></p>";
?>
