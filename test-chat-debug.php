<?php
/**
 * Mr ECU - Chat Debug Test
 * Chat sistemini test etmek için debug dosyası
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/ChatManager.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Chat System Debug</h2>";
echo "<pre>";

// 1. Session kontrolü
echo "=== SESSION INFO ===\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n\n";

// 2. Test dosya ID'si
$testFileId = $_GET['file_id'] ?? 'a6ccf23b-38ef-468a-a6aa-a95156aa1b70';
echo "=== TEST FILE ===\n";
echo "File ID: $testFileId\n\n";

// 3. Dosya sahibini kontrol et
if (isset($_SESSION['user_id'])) {
    $chatManager = new ChatManager($pdo);
    
    echo "=== FILE OWNERSHIP CHECK ===\n";
    $sql = "SELECT * FROM file_uploads WHERE id = :file_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':file_id' => $testFileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file) {
        echo "File Found: YES\n";
        echo "File Owner ID: " . $file['user_id'] . "\n";
        echo "Current User ID: " . $_SESSION['user_id'] . "\n";
        echo "Is Owner: " . ($file['user_id'] === $_SESSION['user_id'] ? 'YES' : 'NO') . "\n";
        echo "Is Admin: " . (($_SESSION['role'] ?? 'user') === 'admin' ? 'YES' : 'NO') . "\n\n";
        
        // Owner bilgilerini getir
        $sql = "SELECT id, username, first_name, last_name, role FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $file['user_id']]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "=== FILE OWNER INFO ===\n";
        if ($owner) {
            echo "Owner Username: " . $owner['username'] . "\n";
            echo "Owner Name: " . $owner['first_name'] . " " . $owner['last_name'] . "\n";
            echo "Owner Role: " . $owner['role'] . "\n\n";
        }
        
        // Mevcut mesajları kontrol et
        echo "=== EXISTING MESSAGES ===\n";
        $sql = "SELECT fc.*, u.username, u.first_name, u.last_name 
                FROM file_chats fc 
                JOIN users u ON fc.sender_id = u.id 
                WHERE fc.file_id = :file_id 
                ORDER BY fc.created_at DESC 
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':file_id' => $testFileId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($messages) {
            echo "Message Count: " . count($messages) . "\n";
            foreach ($messages as $msg) {
                echo "- [" . $msg['created_at'] . "] " . $msg['username'] . " (" . $msg['sender_type'] . "): " . substr($msg['message'], 0, 50) . "...\n";
            }
        } else {
            echo "No messages found for this file.\n";
        }
        
    } else {
        echo "File Found: NO\n";
        echo "Error: File with ID '$testFileId' not found in database.\n";
    }
    
    // Test mesaj gönderme
    if (isset($_GET['test_send'])) {
        echo "\n=== TEST MESSAGE SEND ===\n";
        $testMessage = "Test message at " . date('Y-m-d H:i:s');
        $senderType = ($_SESSION['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        
        echo "Attempting to send message...\n";
        echo "Message: $testMessage\n";
        echo "Sender Type: $senderType\n";
        
        $messageId = $chatManager->sendMessage($testFileId, 'upload', $_SESSION['user_id'], $senderType, $testMessage);
        
        if ($messageId) {
            echo "SUCCESS! Message ID: $messageId\n";
        } else {
            echo "FAILED! Check error logs.\n";
        }
    }
    
} else {
    echo "ERROR: Not logged in. Please login first.\n";
}

echo "\n=== CHAT TABLES CHECK ===\n";
$tables = $pdo->query("SHOW TABLES LIKE '%chat%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "Table '$table': $count records\n";
}

echo "</pre>";

echo '<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
echo '<h4>Test Actions:</h4>';
echo '<a href="?file_id=' . $testFileId . '" class="btn btn-primary" style="margin-right: 10px;">Refresh</a>';
echo '<a href="?file_id=' . $testFileId . '&test_send=1" class="btn btn-success">Test Send Message</a>';
echo '</div>';

echo '<div style="margin-top: 20px;">';
echo '<a href="user/file-detail.php?id=' . $testFileId . '" target="_blank">Open User File Detail Page</a> | ';
echo '<a href="admin/file-detail.php?id=' . $testFileId . '" target="_blank">Open Admin File Detail Page</a>';
echo '</div>';
?>
