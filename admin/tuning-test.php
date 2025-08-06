<?php
/**
 * Tuning Management Test Sayfası
 * Minimal versiyonu
 */

// Hata gösterimi
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Tuning Management Test</h1>";

try {
    echo "<p>1. Config dahil ediliyor...</p>";
    require_once __DIR__ . '/../config/config.php';
    echo "<p>✅ Config OK</p>";

    echo "<p>2. TuningModel dahil ediliyor...</p>";
    require_once __DIR__ . '/../includes/TuningModel.php';
    echo "<p>✅ TuningModel OK</p>";

    echo "<p>3. Admin kontrolü...</p>";
    if (!isLoggedIn() || !isAdmin()) {
        echo "<p>❌ Admin girişi gerekli!</p>";
        echo "<a href='../login.php'>Giriş Yap</a>";
        exit;
    }
    echo "<p>✅ Admin kontrolü OK</p>";

    echo "<p>4. TuningModel instance oluşturuluyor...</p>";
    $tuning = new TuningModel($pdo);
    echo "<p>✅ TuningModel instance OK</p>";

    echo "<p>5. Brand stats getiriliyor...</p>";
    $brandStats = $tuning->getBrandStats();
    echo "<p>✅ Brand stats OK - " . count($brandStats) . " marka</p>";

    echo "<p>6. Header dahil ediliyor...</p>";
    include __DIR__ . '/../includes/admin_header.php';
    echo "<p>✅ Header OK</p>";

    echo "<h2>Test Başarılı!</h2>";
    echo "<p><a href='tuning-management.php'>Gerçek Admin Panel'e Git</a></p>";

    include __DIR__ . '/../includes/admin_footer.php';

} catch (Exception $e) {
    echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
