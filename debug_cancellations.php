<?php
/**
 * Debug Cancellations Page
 * İptal talepleri sayfası debug scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';
require_once 'includes/FileCancellationManager.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cancellations Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔍 Cancellations Page Debug</h1>";

// 1. Session Check
echo "<div class='step'>";
echo "<h2>1. Session Durumu</h2>";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<div class='info'><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</div>";

if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>✅ Session user_id: " . $_SESSION['user_id'] . "</div>";
    echo "<div class='info'>📧 Email: " . ($_SESSION['email'] ?? 'Not set') . "</div>";
    echo "<div class='info'>👤 Username: " . ($_SESSION['username'] ?? 'Not set') . "</div>";
    echo "<div class='info'>🎭 Role: " . ($_SESSION['role'] ?? 'Not set') . "</div>";
} else {
    echo "<div class='error'>❌ Session user_id bulunamadı. Giriş yapmanız gerekiyor.</div>";
    echo "<div class='warning'>⚠️ Test için manuel user_id set ediliyor...</div>";
    
    // Get first user for testing
    $stmt = $pdo->query("SELECT id, username, email FROM users WHERE role = 'user' LIMIT 1");
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        $_SESSION['user_id'] = $testUser['id'];
        $_SESSION['username'] = $testUser['username'];
        $_SESSION['email'] = $testUser['email'];
        $_SESSION['role'] = 'user';
        
        echo "<div class='success'>✅ Test için user_id set edildi: " . $testUser['id'] . " (" . $testUser['username'] . ")</div>";
    } else {
        echo "<div class='error'>❌ Test için kullanıcı bulunamadı.</div>";
        echo "</div></div></body></html>";
        exit;
    }
}
echo "</div>";

// 2. Check if user exists in database
echo "<div class='step'>";
echo "<h2>2. Kullanıcı Doğrulama</h2>";

try {
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
    
    if ($currentUser) {
        echo "<div class='success'>✅ Kullanıcı veritabanında bulundu</div>";
        echo "<table>";
        echo "<tr><th>ID</th><td>{$currentUser['id']}</td></tr>";
        echo "<tr><th>Username</th><td>{$currentUser['username']}</td></tr>";
        echo "<tr><th>Email</th><td>{$currentUser['email']}</td></tr>";
        echo "<tr><th>Role</th><td>{$currentUser['role']}</td></tr>";
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Kullanıcı veritabanında bulunamadı!</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 3. Check cancellations for this user
echo "<div class='step'>";
echo "<h2>3. Kullanıcının İptal Talepleri (Direkt SQL)</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT fc.*, a.username as admin_username 
        FROM file_cancellations fc
        LEFT JOIN users a ON fc.admin_id = a.id
        WHERE fc.user_id = ?
        ORDER BY fc.requested_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $directCancellations = $stmt->fetchAll();
    
    echo "<div class='info'>📊 SQL Result Count: " . count($directCancellations) . "</div>";
    
    if (!empty($directCancellations)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>File ID</th><th>Type</th><th>Reason</th><th>Credits</th><th>Status</th><th>Date</th></tr>";
        
        foreach ($directCancellations as $cancellation) {
            echo "<tr>";
            echo "<td>" . substr($cancellation['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($cancellation['file_id'], 0, 8) . "...</td>";
            echo "<td>{$cancellation['file_type']}</td>";
            echo "<td>" . substr($cancellation['reason'], 0, 30) . "...</td>";
            echo "<td>{$cancellation['credits_to_refund']}</td>";
            echo "<td>{$cancellation['status']}</td>";
            echo "<td>{$cancellation['requested_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Bu kullanıcı için iptal talebi bulunamadı.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ SQL Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 4. Test FileCancellationManager
echo "<div class='step'>";
echo "<h2>4. FileCancellationManager Test</h2>";

try {
    $cancellationManager = new FileCancellationManager($pdo);
    echo "<div class='success'>✅ FileCancellationManager oluşturuldu</div>";
    
    // Test getUserCancellations method
    $cancellations = $cancellationManager->getUserCancellations($_SESSION['user_id'], 1, 10);
    echo "<div class='info'>📊 Manager Result Count: " . count($cancellations) . "</div>";
    
    if (!empty($cancellations)) {
        echo "<div class='success'>✅ FileCancellationManager veriler döndürüyor</div>";
        echo "<pre>" . json_encode($cancellations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='warning'>⚠️ FileCancellationManager boş dizi döndürüyor</div>";
        
        // Debug the method
        echo "<div class='info'>🔍 Method debug için UUID kontrolü:</div>";
        $isValidUUID = isValidUUID($_SESSION['user_id']);
        echo "<div class='" . ($isValidUUID ? 'success' : 'error') . "'>";
        echo ($isValidUUID ? '✅' : '❌') . " User ID UUID Valid: " . ($isValidUUID ? 'Yes' : 'No');
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ FileCancellationManager Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// 5. Test the actual page logic
echo "<div class='step'>";
echo "<h2>5. Sayfa Mantığı Test</h2>";

try {
    // Mimic the actual page logic
    $user = new User($pdo);
    $userId = $_SESSION['user_id'];
    
    echo "<div class='info'>🔍 User ID format check: " . (isValidUUID($userId) ? 'Valid UUID' : 'Invalid UUID') . "</div>";
    
    if (!isValidUUID($userId)) {
        echo "<div class='error'>❌ Invalid UUID format!</div>";
    } else {
        $cancellationManager = new FileCancellationManager($pdo);
        $page = 1;
        $limit = 10;
        
        echo "<div class='info'>🔍 Calling getUserCancellations with params: userId={$userId}, page={$page}, limit={$limit}</div>";
        
        $cancellations = $cancellationManager->getUserCancellations($userId, $page, $limit);
        
        echo "<div class='info'>📊 Final Result Count: " . count($cancellations) . "</div>";
        
        if (empty($cancellations)) {
            echo "<div class='warning'>⚠️ PROBLEM: Empty result from getUserCancellations</div>";
            echo "<div class='info'>💡 Possible causes:</div>";
            echo "<ul>";
            echo "<li>User ID doesn't match any cancellation records</li>";
            echo "<li>Method has a bug</li>";
            echo "<li>Database connection issue</li>";
            echo "</ul>";
        } else {
            echo "<div class='success'>✅ SUCCESS: Data found!</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Page Logic Error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 6. Quick Fix - Create cancellation for current user
echo "<div class='step'>";
echo "<h2>6. Hızlı Çözüm</h2>";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM file_cancellations WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userCancellationCount = $stmt->fetchColumn();

if ($userCancellationCount == 0) {
    echo "<div class='warning'>⚠️ Bu kullanıcı için iptal talebi yok. Test verisi oluşturuluyor...</div>";
    
    try {
        // Get a sample file for this user or create fake one
        $stmt = $pdo->prepare("SELECT id FROM file_uploads WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $userFile = $stmt->fetch();
        
        $fileId = $userFile ? $userFile['id'] : generateUUID();
        
        $testCancellation = [
            'id' => generateUUID(),
            'user_id' => $_SESSION['user_id'],
            'file_id' => $fileId,
            'file_type' => 'upload',
            'reason' => 'Test iptal talebi - Debug için oluşturuldu',
            'credits_to_refund' => 25.00,
            'status' => 'pending'
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO file_cancellations (
                id, user_id, file_id, file_type, reason, credits_to_refund, status, requested_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $testCancellation['id'],
            $testCancellation['user_id'],
            $testCancellation['file_id'],
            $testCancellation['file_type'],
            $testCancellation['reason'],
            $testCancellation['credits_to_refund'],
            $testCancellation['status']
        ]);
        
        echo "<div class='success'>✅ Bu kullanıcı için test iptal talebi oluşturuldu!</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Test verisi oluşturulamadı: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='info'>✅ Bu kullanıcı için $userCancellationCount iptal talebi mevcut.</div>";
}

echo "</div>";

// 7. Final test link
echo "<div class='step'>";
echo "<h2>7. Final Test</h2>";
echo "<p><strong>Şimdi sayfayı tekrar test edin:</strong></p>";
echo "<p><a href='user/cancellations.php' target='_blank' style='color: #007bff; text-decoration: none; font-size: 18px;'>➡️ İptal Taleplerim Sayfası</a></p>";
echo "<p><small>Bu debug script'ini çalıştırdıktan sonra sayfada veriler görünmelidir.</small></p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
