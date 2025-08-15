<?php
/**
 * User Revisions HTML Output Debug
 */

session_start();
require_once 'config/config.php';
require_once 'config/database.php';

// Debug kullanıcı ID'si
$_SESSION['user_id'] = '3fbe9c59-53de-4bcd-a83b-21634f467203';
$_SESSION['role'] = 'user';
$_SESSION['username'] = 'debug_user';

// Output buffering başlat
ob_start();

// User revisions sayfasını include et
include 'user/revisions.php';

// HTML çıktısını al
$htmlOutput = ob_get_clean();

// HTML'i analiz et
echo "<h2>User Revisions HTML Output Analysis</h2>";

// Table body kısmını bul
$tbodyStart = strpos($htmlOutput, '<tbody>');
$tbodyEnd = strpos($htmlOutput, '</tbody>') + 8;

if ($tbodyStart !== false && $tbodyEnd !== false) {
    $tbodyContent = substr($htmlOutput, $tbodyStart, $tbodyEnd - $tbodyStart);
    
    echo "<h3>Table Body İçeriği:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ccc; max-height: 600px; overflow-y: auto;'>";
    echo htmlspecialchars($tbodyContent);
    echo "</pre>";
    
    // TR sayısını say
    $trCount = substr_count($tbodyContent, '<tr class="revision-row"');
    echo "<h3>Bulunan TR Sayısı: <span style='color: " . ($trCount == 2 ? "green" : "red") . ";'>" . $trCount . "</span></h3>";
    
    // Her TR'nin data-revision-id'sini çıkar
    preg_match_all('/data-revision-id="([^"]+)"/', $tbodyContent, $matches);
    echo "<h3>TR'lerin Revision ID'leri:</h3>";
    foreach ($matches[1] as $i => $revisionId) {
        echo "<div style='background: #e1f5fe; padding: 10px; margin: 5px 0; border-left: 4px solid #0277bd;'>";
        echo "TR " . ($i + 1) . ": " . substr($revisionId, 0, 8) . "...";
        echo "</div>";
    }
    
    // Target file bilgilerini çıkar
    preg_match_all('/<strong class="text-([^"]+)">([^<]+):<\/strong>/', $tbodyContent, $targetMatches);
    echo "<h3>Target File Types:</h3>";
    foreach ($targetMatches[2] as $i => $targetType) {
        $color = $targetMatches[1][$i];
        echo "<div style='background: #f3e5f5; padding: 10px; margin: 5px 0; border-left: 4px solid #8e24aa;'>";
        echo "Target " . ($i + 1) . ": <strong style='color: " . ($color == 'primary' ? 'blue' : ($color == 'warning' ? 'orange' : 'green')) . ";'>" . $targetType . "</strong>";
        echo "</div>";
    }
    
} else {
    echo "<p style='color: red;'>Table body bulunamadı!</p>";
}

// JavaScript console log'larını kontrol et
if (strpos($htmlOutput, 'console.log') !== false) {
    echo "<h3>JavaScript Console Logs Mevcut ✅</h3>";
} else {
    echo "<h3>JavaScript Console Logs YOK ❌</h3>";
}

// Cache buster kontrolü
if (strpos($htmlOutput, 'Page version: 2.0') !== false) {
    echo "<h3>Cache Buster Aktif ✅</h3>";
} else {
    echo "<h3>Cache Buster YOK ❌</h3>";
}
?>
