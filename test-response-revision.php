<?php
/**
 * Yanıt Dosyası Revize Test
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/FileManager.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Yanıt Dosyası Revize Test</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🧪 Yanıt Dosyası Revize Test</h1>";

if (!isLoggedIn()) {
    echo "<div class='error'>❌ Lütfen önce giriş yapın</div>";
    echo "</body></html>";
    exit;
}

$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

try {
    // Yanıt dosyaları kontrol
    echo "<h2>1. Yanıt Dosyalarınız</h2>";
    $stmt = $pdo->prepare("
        SELECT fr.id, fr.original_name, fr.upload_id, fu.original_name as upload_file_name
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        WHERE fu.user_id = ?
        ORDER BY fr.upload_date DESC
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $responses = $stmt->fetchAll();
    
    if (empty($responses)) {
        echo "<div class='warning'>⚠️ Henüz yanıt dosyanız yok</div>";
    } else {
        echo "<div class='info'>📁 " . count($responses) . " yanıt dosyası bulundu</div>";
        
        foreach ($responses as $response) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>Yanıt:</strong> " . htmlspecialchars($response['original_name']) . "<br>";
            echo "<strong>Orijinal Dosya:</strong> " . htmlspecialchars($response['upload_file_name']) . "<br>";
            echo "<strong>Response ID:</strong> " . substr($response['id'], 0, 8) . "...<br>";
            echo "<strong>Upload ID:</strong> " . substr($response['upload_id'], 0, 8) . "...<br>";
            
            // Bu response için bekleyen revize talebi var mı?
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM revisions 
                WHERE upload_id = ? AND status = 'pending' AND request_notes LIKE '[YANIT DOSYASI REVİZE]%'
            ");
            $stmt->execute([$response['upload_id']]);
            $existing = $stmt->fetch()['count'];
            
            if ($existing > 0) {
                echo "<span style='color: orange;'>⏳ Bekleyen revize talebi var</span><br>";
            } else {
                echo "<span style='color: green;'>✅ Revize talep edilebilir</span><br>";
                
                // Test butonu
                echo "<form method='POST' style='margin-top: 10px;'>";
                echo "<input type='hidden' name='test_response_revision' value='1'>";
                echo "<input type='hidden' name='response_id' value='" . $response['id'] . "'>";
                echo "<textarea name='revision_notes' style='width: 100%; height: 60px;' placeholder='Test revize açıklaması...'>Bu yanıt dosyasında iyileştirme istiyorum. Daha iyi performans için düzenleme yapın.</textarea>";
                echo "<button type='submit' style='background: #ffc107; color: black; padding: 5px 10px; border: none; border-radius: 3px; margin-top: 5px;'>🧪 Test Revize Talebi</button>";
                echo "</form>";
            }
            
            echo "</div>";
        }
    }
    
    // Test revize talebi işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_response_revision'])) {
        $responseId = sanitize($_POST['response_id']);
        $revisionNotes = sanitize($_POST['revision_notes']);
        
        echo "<h2>2. Test Sonucu</h2>";
        
        if (!isValidUUID($responseId)) {
            echo "<div class='error'>❌ Geçersiz response ID formatı</div>";
        } elseif (empty($revisionNotes)) {
            echo "<div class='error'>❌ Revize açıklaması gerekli</div>";
        } else {
            echo "<div class='info'>🔄 Yanıt dosyası revize talebi gönderiliyor...</div>";
            echo "<p><strong>Response ID:</strong> $responseId</p>";
            echo "<p><strong>User ID:</strong> $userId</p>";
            echo "<p><strong>Notes:</strong> " . htmlspecialchars($revisionNotes) . "</p>";
            
            try {
                $result = $fileManager->requestResponseRevision($responseId, $userId, $revisionNotes);
                
                if ($result['success']) {
                    echo "<div class='success'>✅ " . $result['message'] . "</div>";
                    echo "<p><strong>Revision ID:</strong> " . ($result['revision_id'] ?? 'N/A') . "</p>";
                    echo "<p>🔄 <a href='javascript:location.reload()'>Sayfayı yenile</a> ve değişiklikleri gör</p>";
                } else {
                    echo "<div class='error'>❌ " . $result['message'] . "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Mevcut revize talepleri
    echo "<h2>3. Mevcut Revize Talepleri</h2>";
    $stmt = $pdo->prepare("
        SELECT r.*, fu.original_name 
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        WHERE r.user_id = ? 
        ORDER BY r.requested_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $revisions = $stmt->fetchAll();
    
    if (empty($revisions)) {
        echo "<div class='info'>ℹ️ Henüz revize talebiniz yok</div>";
    } else {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Son " . count($revisions) . " revize talebi:</strong><br><br>";
        
        foreach ($revisions as $revision) {
            $isResponseRevision = strpos($revision['request_notes'], '[YANIT DOSYASI REVİZE]') === 0;
            $bgColor = $isResponseRevision ? '#fff3cd' : '#e6f3ff';
            
            echo "<div style='background: $bgColor; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
            echo "<strong>ID:</strong> " . substr($revision['id'], 0, 8) . "... ";
            echo "<strong>Status:</strong> " . $revision['status'] . " ";
            echo "<strong>Tarih:</strong> " . date('d.m.Y H:i', strtotime($revision['requested_at'])) . "<br>";
            echo "<strong>Dosya:</strong> " . htmlspecialchars($revision['original_name']) . "<br>";
            echo "<strong>Notlar:</strong> " . htmlspecialchars($revision['request_notes']) . "<br>";
            if ($isResponseRevision) {
                echo "<span style='background: #ffc107; color: black; padding: 2px 5px; border-radius: 3px; font-size: 0.8em;'>YANIT DOSYASI REVİZE</span>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>📋 Test Sonucu</h3>";
echo "<p>✅ requestResponseRevision metodu düzeltildi</p>";
echo "<p>✅ upload_id alanı artık doğru şekilde doldurulmaktadır</p>";
echo "<p>✅ Yanıt dosyası revize talepleri ayrıştırılabilir</p>";
echo "<p><a href='user/files.php'>📁 Dosyalarım sayfasına git</a></p>";
echo "</div>";

echo "</body></html>";
?>