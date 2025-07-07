<?php
/**
 * File Detail Debug Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>File Detail Debug</h2>";

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "✅ Includes loaded<br>";

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "❌ Admin access denied<br>";
    echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NULL') . "<br>";
    echo "Session role: " . ($_SESSION['role'] ?? 'NULL') . "<br>";
    echo "<a href='../login.php'>Login Required</a><br>";
    exit;
}

echo "✅ Admin access OK<br>";

// Upload ID kontrolü
$uploadId = $_GET['id'] ?? '5c308aa4-770a-4db3-b361-97bcc696dde2'; // Varsayılan test ID

if (!$uploadId) {
    echo "❌ No upload ID provided<br>";
    echo "Expected URL format: file-detail.php?id=UPLOAD_ID<br>";
    echo "<h3>Test Links:</h3>";
    echo "<a href='?id=5c308aa4-770a-4db3-b361-97bcc696dde2'>Test with Default ID</a><br>";
    echo "<a href='uploads.php'>Go to Uploads Page</a><br>";
    exit;
}

echo "Upload ID: $uploadId<br>";

if (!isValidUUID($uploadId)) {
    echo "❌ Invalid UUID format<br>";
    echo "UUID should be in format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx<br>";
    exit;
}

echo "✅ Valid UUID format<br>";

try {
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
    echo "✅ Classes instantiated<br>";
    
    // Dosya detaylarını al
    $upload = $fileManager->getUploadById($uploadId);
    
    if (!$upload) {
        echo "❌ Upload not found in database<br>";
        echo "Let's check what uploads exist:<br>";
        
        $stmt = $pdo->query("SELECT id, original_name, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 5");
        $uploads = $stmt->fetchAll();
        
        echo "<ul>";
        foreach ($uploads as $u) {
            echo "<li><a href='?id=" . $u['id'] . "'>" . htmlspecialchars($u['original_name']) . "</a> (" . $u['upload_date'] . ")</li>";
        }
        echo "</ul>";
        exit;
    }
    
    echo "✅ Upload found in database<br>";
    echo "<h3>Upload Details:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    foreach ($upload as $key => $value) {
        // NULL değerleri için güvenli output
        $safeValue = $value !== null ? htmlspecialchars($value) : '<em>NULL</em>';
        echo "<tr><td><strong>$key</strong></td><td>$safeValue</td></tr>";
    }
    echo "</table>";
    
    // Response files kontrol et
    echo "<h3>Response Files Check:</h3>";
    $stmt = $pdo->prepare("
        SELECT fr.*, u.username as admin_username, u.first_name, u.last_name
        FROM file_responses fr
        LEFT JOIN users u ON fr.admin_id = u.id
        WHERE fr.upload_id = ?
        ORDER BY fr.upload_date DESC
    ");
    $stmt->execute([$uploadId]);
    $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Response files found: " . count($responseFiles) . "<br>";
    
    if ($responseFiles) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Filename</th><th>Upload Date</th><th>Admin</th></tr>";
        foreach ($responseFiles as $rf) {
            echo "<tr>";
            echo "<td>" . $rf['id'] . "</td>";
            echo "<td>" . htmlspecialchars($rf['filename']) . "</td>";
            echo "<td>" . $rf['upload_date'] . "</td>";
            echo "<td>" . htmlspecialchars($rf['admin_username']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Credit history kontrol et
    echo "<h3>Credit History Check:</h3>";
    $stmt = $pdo->prepare("
        SELECT * FROM credit_transactions 
        WHERE user_id = ? AND description LIKE ?
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$upload['user_id'], '%' . $uploadId . '%']);
    $creditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Related credit transactions: " . count($creditHistory) . "<br>";
    
    // System logs kontrol et
    echo "<h3>System Logs Check:</h3>";
    try {
        // Kolun var olduğunu kontrol et
        $stmt = $pdo->query("SHOW COLUMNS FROM system_logs LIKE 'details'");
        $hasDetailsColumn = $stmt->fetch();
        
        if ($hasDetailsColumn) {
            $stmt = $pdo->prepare("
                SELECT * FROM system_logs 
                WHERE (details LIKE ? OR details LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute(['%' . $uploadId . '%', '%' . $upload['original_name'] . '%']);
            $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Related system logs: " . count($systemLogs) . "<br>";
        } else {
            // 'details' kolonu yok, alternatif alan kullan
            $stmt = $pdo->prepare("
                SELECT * FROM system_logs 
                WHERE (description LIKE ? OR description LIKE ?)
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute(['%' . $uploadId . '%', '%' . $upload['original_name'] . '%']);
            $systemLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Related system logs (via description): " . count($systemLogs) . "<br>";
        }
    } catch (Exception $e) {
        echo "System logs table structure issue: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>✅ All Data Retrieved Successfully!</h3>";
    echo "<p>The file detail page should work correctly now.</p>";
    
    echo "<h3>Test Links:</h3>";
    echo "<a href='file-detail.php?id=$uploadId' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Real Detail Page</a><br><br>";
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<a href='uploads.php'>← Back to Uploads</a>";
?>
