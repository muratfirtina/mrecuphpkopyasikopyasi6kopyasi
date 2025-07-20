<?php
/**
 * Pagination Özelliklerini Test Etme Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Basit authentication check
if (!isset($_SESSION['user_id'])) {
    echo "<h2>Test için giriş yapın</h2>";
    echo "<p><a href='login.php'>Giriş Yap</a></p>";
    exit;
}

echo "<h1>🎉 Pagination Özellikleri Başarıyla Eklendi!</h1>";

echo "<div style='background: #e7f3ff; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007bff;'>";
echo "<h3>✅ Yapılan Geliştirmeler:</h3>";
echo "<ul>";
echo "<li><strong>User Credits Sayfası:</strong> İşlem sayısı seçimi (5, 10, 20, 50, 100)</li>";
echo "<li><strong>Admin Credits Sayfası:</strong> Kullanıcı sayısı seçimi (10, 20, 50, 100)</li>";
echo "<li><strong>Pagination Links:</strong> Tüm sayfalama linklerinde limit parametresi korunuyor</li>";
echo "<li><strong>Form Memory:</strong> Filtreler ve limit ayarları sayfa değişikliklerinde korunuyor</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
echo "<h3>🔗 Test Linkleri:</h3>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='user/credits.php' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>👤 User Credits</a>";
echo "<a href='admin/credits.php' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>⚙️ Admin Credits</a>";
echo "<a href='user/credits.php?limit=5' style='background: #6f42c1; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>📊 5 İşlem Test</a>";
echo "<a href='admin/credits.php?limit=10' style='background: #fd7e14; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>👥 10 Kullanıcı Test</a>";
echo "</div>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h3>📋 Test Senaryoları:</h3>";
echo "<ol>";
echo "<li><strong>User Credits:</strong>";
echo "<ul>";
echo "<li>Sayfa başı işlem sayısını değiştirin (5, 10, 20, 50, 100)</li>";
echo "<li>Bir filtre uygulayın (tarih veya işlem tipi)</li>";
echo "<li>Sayfa değiştirin - limit ve filtreler korunmalı</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Admin Credits:</strong>";
echo "<ul>";
echo "<li>Sayfa başı kullanıcı sayısını değiştirin</li>";
echo "<li>Arama yapın</li>";
echo "<li>Sayfa değiştirin - limit ve arama korunmalı</li>";
echo "</ul>";
echo "</li>";
echo "</ol>";
echo "</div>";

// Mevcut database'deki verileri göster
try {
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users WHERE role = 'user'");
    $userCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as transaction_count FROM credit_transactions");
    $transactionCount = $stmt->fetchColumn();
    
    echo "<div style='background: #d4edda; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #28a745;'>";
    echo "<h3>📊 Mevcut Veriler:</h3>";
    echo "<ul>";
    echo "<li><strong>Toplam Kullanıcı:</strong> {$userCount} adet</li>";
    echo "<li><strong>Toplam İşlem:</strong> {$transactionCount} adet</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #dc3545;'>";
    echo "<h3>⚠️ Database Bağlantısı:</h3>";
    echo "<p>Veritabanı bilgileri alınamadı: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='background: #e2e3e5; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
echo "<h3>🛠️ Geliştirici Notları:</h3>";
echo "<ul>";
echo "<li>User credits default limit: 10 işlem</li>";
echo "<li>Admin credits default limit: 20 kullanıcı</li>";
echo "<li>Minimum limit: 5 (user), 10 (admin)</li>";
echo "<li>Maksimum limit: 100 (her ikisi için)</li>";
echo "<li>URL parametreleri: <code>?page=1&limit=20&search=...</code></li>";
echo "</ul>";
echo "</div>";
?>
