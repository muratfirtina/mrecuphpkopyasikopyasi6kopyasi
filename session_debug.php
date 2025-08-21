<?php
/**
 * Session Debug - Kullanıcı Session Kontrolü
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 15px 0; border-left: 4px solid #007bff; }
        .session-value { background: #e9ecef; padding: 10px; border-radius: 4px; margin: 5px 0; }
        .success { color: green; background: #d1edff; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Session Debug - Kullanıcı Yetkisi Kontrolü</h1>";

// Session durumunu kontrol et
if (isset($_SESSION['user_id'])) {
    echo "<div class='success'>✅ Kullanıcı giriş yapmış!</div>";
    
    echo "<div class='debug-card'>";
    echo "<h3>📊 Session Bilgileri:</h3>";
    echo "<div class='session-value'><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'YOK') . "</div>";
    echo "<div class='session-value'><strong>Username:</strong> " . ($_SESSION['username'] ?? 'YOK') . "</div>";
    echo "<div class='session-value'><strong>Email:</strong> " . ($_SESSION['email'] ?? 'YOK') . "</div>";
    echo "<div class='session-value'><strong>Role:</strong> " . ($_SESSION['role'] ?? 'YOK') . "</div>";
    echo "<div class='session-value'><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'YOK') . "</div>";
    echo "<div class='session-value'><strong>Is Admin:</strong> " . ($_SESSION['is_admin'] ?? 'YOK') . "</div>";
    echo "</div>";
    
    // Yetki kontrolü
    echo "<div class='debug-card'>";
    echo "<h3>🔐 Yetki Kontrolü:</h3>";
    
    $hasUserRole = isset($_SESSION['user_role']);
    $userRole = $_SESSION['user_role'] ?? 'YOK';
    $allowedRoles = ['admin', 'design'];
    $hasAccess = $hasUserRole && in_array($userRole, $allowedRoles);
    
    echo "<div class='session-value'><strong>user_role var mı?</strong> " . ($hasUserRole ? '✅ EVET' : '❌ HAYIR') . "</div>";
    echo "<div class='session-value'><strong>Kullanıcı Rolü:</strong> " . $userRole . "</div>";
    echo "<div class='session-value'><strong>İzinli Roller:</strong> " . implode(', ', $allowedRoles) . "</div>";
    echo "<div class='session-value'><strong>Design Panel Erişimi:</strong> " . ($hasAccess ? '✅ VAR' : '❌ YOK') . "</div>";
    
    if ($hasAccess) {
        echo "<div class='success'>✅ Bu kullanıcı Design Panel'e erişebilir!</div>";
        echo "<a href='design/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>🎨 Design Panel'e Git</a>";
    } else {
        echo "<div class='error'>❌ Bu kullanıcı Design Panel'e erişemez!</div>";
        
        if (!$hasUserRole) {
            echo "<div class='warning'>⚠️ SORUN: Session'da user_role tanımlı değil!</div>";
        } else if (!in_array($userRole, $allowedRoles)) {
            echo "<div class='warning'>⚠️ SORUN: Kullanıcı rolü ('$userRole') izinli roller arasında değil!</div>";
        }
    }
    echo "</div>";
    
} else {
    echo "<div class='error'>❌ Kullanıcı giriş yapmamış!</div>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>🔐 Giriş Yap</a>";
}

// Tüm session'ı göster
echo "<div class='debug-card'>";
echo "<h3>🗂️ Tüm Session Verileri:</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Test linkleri
echo "<div class='debug-card'>";
echo "<h3>🔗 Test Linkleri:</h3>";
echo "<a href='login.php' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>🔐 Login</a>";
echo "<a href='logout.php' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>🚪 Logout</a>";
echo "<a href='design/' style='background: #6f42c1; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>🎨 Design Panel</a>";
echo "<a href='admin/' style='background: #fd7e14; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>⚙️ Admin Panel</a>";
echo "</div>";

echo "</body></html>";
?>
