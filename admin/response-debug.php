<?php
/**
 * Response File Debug
 * Response dosyası detaylarını debug et
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Admin yetkisi gerekiyor!');
}

$uploadId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

echo "<h1>🔍 Response File Debug</h1>";
echo "<p>Upload ID: <strong>$uploadId</strong></p>";

if (!$uploadId || !isValidUUID($uploadId)) {
    echo "<p style='color:red;'>❌ Geçersiz Upload ID</p>";
    exit;
}

echo "<h2>1. Upload Dosyası Kontrolü</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
    $stmt->execute([$uploadId]);
    $upload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($upload) {
        echo "<p style='color:green;'>✅ Upload dosyası bulundu</p>";
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Alan</th><th>Değer</th></tr>";
        echo "<tr><td>ID</td><td>{$upload['id']}</td></tr>";
        echo "<tr><td>Original Name</td><td>{$upload['original_name']}</td></tr>";
        echo "<tr><td>Status</td><td>{$upload['status']}</td></tr>";
        echo "<tr><td>Upload Date</td><td>{$upload['upload_date']}</td></tr>";
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ Upload dosyası bulunamadı</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Upload dosyası hatası: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Response Dosyaları Kontrolü</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT fr.*, fu.original_name as upload_name
        FROM file_responses fr
        LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
        WHERE fr.upload_id = ?
        ORDER BY fr.upload_date DESC
    ");
    $stmt->execute([$uploadId]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($responses) {
        echo "<p style='color:green;'>✅ " . count($responses) . " response dosyası bulundu</p>";
        
        foreach ($responses as $i => $response) {
            echo "<h3>Response #" . ($i + 1) . "</h3>";
            echo "<table border='1' style='border-collapse:collapse;'>";
            echo "<tr><th>Alan</th><th>Değer</th></tr>";
            echo "<tr><td>Response ID</td><td>{$response['id']}</td></tr>";
            echo "<tr><td>Original Name</td><td>{$response['original_name']}</td></tr>";
            echo "<tr><td>Filename</td><td>{$response['filename']}</td></tr>";
            echo "<tr><td>File Size</td><td>{$response['file_size']}</td></tr>";
            echo "<tr><td>Upload Date</td><td>{$response['upload_date']}</td></tr>";
            echo "<tr><td>Admin ID</td><td>{$response['admin_id']}</td></tr>";
            echo "</table>";
            
            // Dosya yolu kontrolü
            $filePath = '../uploads/response_files/' . $response['filename'];
            $fullPath = realpath($filePath);
            
            echo "<h4>Dosya Yolu Kontrolü:</h4>";
            echo "<p><strong>Relative Path:</strong> $filePath</p>";
            echo "<p><strong>Real Path:</strong> " . ($fullPath ? $fullPath : 'Bulunamadı') . "</p>";
            echo "<p><strong>Dosya Var mı:</strong> " . (file_exists($filePath) ? '✅ Evet' : '❌ Hayır') . "</p>";
            
            if (file_exists($filePath)) {
                echo "<p><strong>Dosya Boyutu:</strong> " . filesize($filePath) . " bytes</p>";
            }
            
            // Download testi
            echo "<h4>Download Test:</h4>";
            echo "<p><a href='download-file.php?id={$response['id']}&type=response' target='_blank'>Download Test</a></p>";
            
            echo "<hr>";
        }
    } else {
        echo "<p style='color:red;'>❌ Response dosyası bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Response dosyası hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Response Revisions Kontrolü</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT r.*, fr.original_name as response_name
        FROM revisions r
        LEFT JOIN file_responses fr ON r.response_id = fr.id
        WHERE r.upload_id = ?
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute([$uploadId]);
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($revisions) {
        echo "<p style='color:green;'>✅ " . count($revisions) . " revision bulundu</p>";
        
        foreach ($revisions as $i => $revision) {
            echo "<h3>Revision #" . ($i + 1) . "</h3>";
            echo "<table border='1' style='border-collapse:collapse;'>";
            echo "<tr><th>Alan</th><th>Değer</th></tr>";
            echo "<tr><td>Revision ID</td><td>{$revision['id']}</td></tr>";
            echo "<tr><td>Response ID</td><td>{$revision['response_id']}</td></tr>";
            echo "<tr><td>Status</td><td>{$revision['status']}</td></tr>";
            echo "<tr><td>Request Notes</td><td>{$revision['request_notes']}</td></tr>";
            echo "<tr><td>Requested At</td><td>{$revision['requested_at']}</td></tr>";
            echo "</table>";
            echo "<hr>";
        }
    } else {
        echo "<p style='color:orange;'>⚠️ Revision bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Revision hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>4. File Detail Page Links</h2>";
echo "<p><a href='file-detail.php?id={$uploadId}' target='_blank'>Normal File Detail</a></p>";
echo "<p><a href='file-detail.php?id={$uploadId}&type=response' target='_blank'>Response File Detail</a></p>";

echo "<hr>";
echo "<p><em>Debug tamamlandı: " . date('Y-m-d H:i:s') . "</em></p>";
?>
