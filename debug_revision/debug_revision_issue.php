<?php
/**
 * Debug - Revize Sorunu Analizi
 * URL: http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/debug_revision/debug_revision_issue.php
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

echo "<h2>Revize Sorunu Debug Analizi</h2>";
echo "<p>Dosya ID: 20b37e6d-7aaa-4be4-b5f5-b4b1d2d9fcdc</p>";
echo "<p>Kullanıcı ID: " . $_SESSION['user_id'] . "</p>";
echo "<hr>";

$fileId = '20b37e6d-7aaa-4be4-b5f5-b4b1d2d9fcdc';
$userId = $_SESSION['user_id'];

// 1. Dosya ana bilgilerini kontrol et
echo "<h3>1. Ana Dosya Bilgileri</h3>";
$stmt = $pdo->prepare("
    SELECT fu.*, u.username, u.first_name, u.last_name 
    FROM file_uploads fu 
    LEFT JOIN users u ON fu.user_id = u.id 
    WHERE fu.id = ?
");
$stmt->execute([$fileId]);
$fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($fileInfo) {
    echo "<pre>";
    print_r($fileInfo);
    echo "</pre>";
    
    // Bu dosya mevcut kullanıcıya ait mi?
    echo "<p><strong>Dosya Kullanıcı Kontrolü:</strong> ";
    if ($fileInfo['user_id'] === $userId) {
        echo "<span style='color:green'>✓ Dosya size ait</span>";
    } else {
        echo "<span style='color:red'>✗ Dosya size ait DEĞİL</span>";
        echo "<br>Dosya sahibi: " . $fileInfo['username'] . " (ID: " . $fileInfo['user_id'] . ")";
        echo "<br>Mevcut kullanıcı: " . $_SESSION['username'] . " (ID: " . $userId . ")";
    }
    echo "</p>";
} else {
    echo "<p style='color:red'>Ana dosya bulunamadı!</p>";
}

// 2. Yanıt dosyalarını kontrol et
echo "<h3>2. Yanıt Dosyaları</h3>";
$stmt = $pdo->prepare("
    SELECT fr.*, fu.user_id as original_user_id, fu.original_name as original_file_name,
           a.username as admin_username
    FROM file_responses fr
    LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
    LEFT JOIN users a ON fr.admin_id = a.id
    WHERE fr.upload_id = ?
    ORDER BY fr.upload_date DESC
");
$stmt->execute([$fileId]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($responses) {
    foreach ($responses as $response) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
        echo "<strong>Yanıt Dosyası ID:</strong> " . $response['id'] . "<br>";
        echo "<strong>Dosya Adı:</strong> " . $response['original_name'] . "<br>";
        echo "<strong>Admin:</strong> " . $response['admin_username'] . "<br>";
        echo "<strong>Orijinal Dosya Kullanıcı ID:</strong> " . $response['original_user_id'] . "<br>";
        echo "<strong>Mevcut Kullanıcı ID:</strong> " . $userId . "<br>";
        echo "<strong>Eşleşme:</strong> ";
        if ($response['original_user_id'] === $userId) {
            echo "<span style='color:green'>✓ Eşleşiyor</span>";
        } else {
            echo "<span style='color:red'>✗ Eşleşmiyor</span>";
        }
        echo "</div>";
    }
} else {
    echo "<p>Yanıt dosyası bulunamadı.</p>";
}

// 3. requestResponseRevision metodunu simüle et
echo "<h3>3. RequestResponseRevision Simülasyonu</h3>";
if ($responses) {
    $responseId = $responses[0]['id'];
    echo "<p>Test edilen yanıt dosyası ID: " . $responseId . "</p>";
    
    // FileManager metodunu simüle et
    $stmt = $pdo->prepare("
        SELECT fr.*, fu.user_id 
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        WHERE fr.id = ? AND fu.user_id = ?
    ");
    $stmt->execute([$responseId, $userId]);
    $response = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Sorgu Sonucu:</strong> ";
    if ($response) {
        echo "<span style='color:green'>✓ Yanıt dosyası bulundu</span>";
        echo "<pre>";
        print_r($response);
        echo "</pre>";
    } else {
        echo "<span style='color:red'>✗ Yanıt dosyası bulunamadı (Bu hatanın sebebi)</span>";
        
        // Ayrı ayrı kontrol et
        echo "<br><br><strong>Ayrı Kontroller:</strong><br>";
        
        // Yanıt dosyası var mı?
        $stmt = $pdo->prepare("SELECT * FROM file_responses WHERE id = ?");
        $stmt->execute([$responseId]);
        $responseExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($responseExists) {
            echo "- Yanıt dosyası mevcut ✓<br>";
            echo "- Upload ID: " . $responseExists['upload_id'] . "<br>";
            
            // Ana dosya var mı?
            $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
            $stmt->execute([$responseExists['upload_id']]);
            $uploadExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($uploadExists) {
                echo "- Ana dosya mevcut ✓<br>";
                echo "- Ana dosya kullanıcı ID: " . $uploadExists['user_id'] . "<br>";
                echo "- Mevcut kullanıcı ID: " . $userId . "<br>";
                
                if ($uploadExists['user_id'] === $userId) {
                    echo "- Kullanıcı eşleşmesi ✓<br>";
                } else {
                    echo "- Kullanıcı eşleşmesi ✗<br>";
                }
            } else {
                echo "- Ana dosya mevcut değil ✗<br>";
            }
        } else {
            echo "- Yanıt dosyası mevcut değil ✗<br>";
        }
    }
    echo "</p>";
}

// 4. Revizyon tablosunu kontrol et
echo "<h3>4. Mevcut Revizyon Talepleri</h3>";
$stmt = $pdo->prepare("
    SELECT r.*, fu.original_name, fr.original_name as response_name 
    FROM revisions r
    LEFT JOIN file_uploads fu ON r.upload_id = fu.id
    LEFT JOIN file_responses fr ON r.response_id = fr.id
    WHERE r.upload_id = ? OR r.response_id IN (
        SELECT fr2.id FROM file_responses fr2 WHERE fr2.upload_id = ?
    )
    ORDER BY r.requested_at DESC
");
$stmt->execute([$fileId, $fileId]);
$revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($revisions) {
    foreach ($revisions as $revision) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
        echo "<strong>Revizyon ID:</strong> " . $revision['id'] . "<br>";
        echo "<strong>Durum:</strong> " . $revision['status'] . "<br>";
        echo "<strong>Talep Tarihi:</strong> " . $revision['requested_at'] . "<br>";
        echo "<strong>Talep Notu:</strong> " . $revision['request_notes'] . "<br>";
        if ($revision['response_id']) {
            echo "<strong>Yanıt Dosyası:</strong> " . $revision['response_name'] . "<br>";
        }
        echo "</div>";
    }
} else {
    echo "<p>Revizyon talebi bulunamadı.</p>";
}

// 5. Tablolardaki GUID formatlarını kontrol et
echo "<h3>5. GUID Format Kontrolü</h3>";
echo "<p>File ID: " . $fileId . " - Format: " . (isValidUUID($fileId) ? "✓ Geçerli" : "✗ Geçersiz") . "</p>";
echo "<p>User ID: " . $userId . " - Format: " . (isValidUUID($userId) ? "✓ Geçerli" : "✗ Geçersiz") . "</p>";

if ($responses) {
    foreach ($responses as $response) {
        echo "<p>Response ID: " . $response['id'] . " - Format: " . (isValidUUID($response['id']) ? "✓ Geçerli" : "✗ Geçersiz") . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='../user/file-detail.php?id=" . $fileId . "'>Dosya Detay Sayfasına Geri Dön</a></p>";
?>