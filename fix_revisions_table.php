<?php
/**
 * Revisions Tablosuna is_cancelled Kolonu Ekleme
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Revisions Tablo Güncellemesi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>";

echo "<h1>🛠️ Revisions Tablo Güncellemesi</h1>";

try {
    echo "<div class='section'>";
    echo "<h2>1. is_cancelled Kolonu Ekleme</h2>";
    
    // Kolun var olup olmadığını kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'is_cancelled'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "<div class='info'>is_cancelled kolonu bulunamadı, ekleniyor...</div>";
        
        $sql = "ALTER TABLE revisions ADD COLUMN is_cancelled TINYINT(1) DEFAULT 0 AFTER status";
        $pdo->exec($sql);
        
        echo "<div class='success'>✅ is_cancelled kolonu başarıyla eklendi!</div>";
    } else {
        echo "<div class='info'>is_cancelled kolonu zaten mevcut.</div>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>2. Test - İptal Edilmiş Revizyonları İşaretle</h2>";
    echo "<p>Aşağıdaki revizyonları manuel olarak iptal edebiliriz:</p>";
    
    // Mevcut revizyonları göster
    $stmt = $pdo->prepare("
        SELECT id, credits_charged, request_notes, status, is_cancelled
        FROM revisions 
        WHERE upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
        OR response_id IN (
            SELECT id FROM file_responses WHERE upload_id = '1bb48fe6-f11c-494c-9089-bdbd619211c4'
        )
        ORDER BY requested_at ASC
    ");
    $stmt->execute();
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'>";
    echo "<th>Revision ID</th><th>Kredi</th><th>Durum</th><th>İptal Edildi</th><th>Notlar</th><th>İşlem</th>";
    echo "</tr>";
    
    foreach ($revisions as $revision) {
        $cancelledText = $revision['is_cancelled'] ? 'EVET' : 'HAYIR';
        $cancelledColor = $revision['is_cancelled'] ? 'red' : 'green';
        
        echo "<tr>";
        echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
        echo "<td>" . $revision['credits_charged'] . " TL</td>";
        echo "<td>{$revision['status']}</td>";
        echo "<td style='color: {$cancelledColor};'>{$cancelledText}</td>";
        echo "<td>" . htmlspecialchars(substr($revision['request_notes'], 0, 30)) . "...</td>";
        echo "<td>";
        if (!$revision['is_cancelled']) {
            echo "<a href='?cancel_revision=" . $revision['id'] . "' style='color: red;'>İptal Et</a>";
        } else {
            echo "<a href='?restore_revision=" . $revision['id'] . "' style='color: green;'>Geri Al</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

    // İptal/Geri alma işlemleri
    if (isset($_GET['cancel_revision'])) {
        $revisionId = $_GET['cancel_revision'];
        $stmt = $pdo->prepare("UPDATE revisions SET is_cancelled = 1 WHERE id = ?");
        $stmt->execute([$revisionId]);
        echo "<div class='success'>✅ Revizyon " . substr($revisionId, 0, 8) . "... iptal edildi!</div>";
        echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
    }
    
    if (isset($_GET['restore_revision'])) {
        $revisionId = $_GET['restore_revision'];
        $stmt = $pdo->prepare("UPDATE revisions SET is_cancelled = 0 WHERE id = ?");
        $stmt->execute([$revisionId]);
        echo "<div class='success'>✅ Revizyon " . substr($revisionId, 0, 8) . "... geri alındı!</div>";
        echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
    }

    echo "<div class='section'>";
    echo "<h2>3. Güncellenmiş Tablo Yapısı</h2>";
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f2f2f2;'><th>Kolon</th><th>Tip</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        $highlight = ($column['Field'] === 'is_cancelled') ? 'background: yellow;' : '';
        echo "<tr style='{$highlight}'>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
