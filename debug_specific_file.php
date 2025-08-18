<?php
/**
 * Spesifik Dosya Kredi Hesaplama Debug
 * ID: f6c3ddd1-4ced-49a8-affb-fdb1c31faf60
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Basit admin kontrolü
if (!isset($_SESSION['user_id'])) {
    die('Lütfen önce giriş yapın.');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Spesifik Dosya Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .code { background: #f5f5f5; padding: 10px; font-family: monospace; }
        .highlight { background: yellow; }
    </style>
</head>
<body>";

$fileId = '1bb48fe6-f11c-494c-9089-bdbd619211c4';

echo "<h1>🔍 Dosya Kredi Debug: " . substr($fileId, 0, 8) . "...</h1>";

try {
    // 1. Ana dosya bilgisi
    echo "<div class='section'>";
    echo "<h2>1. Ana Dosya Bilgisi</h2>";
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
    $stmt->execute([$fileId]);
    $upload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($upload) {
        echo "<table>";
        echo "<tr><th>Alan</th><th>Değer</th></tr>";
        echo "<tr><td>ID</td><td>{$upload['id']}</td></tr>";
        echo "<tr><td>Dosya Adı</td><td>" . htmlspecialchars($upload['original_name']) . "</td></tr>";
        echo "<tr><td>Kullanıcı ID</td><td>{$upload['user_id']}</td></tr>";
        echo "<tr><td>Durum</td><td>{$upload['status']}</td></tr>";
        echo "<tr><td>Kredi (Upload)</td><td>" . ($upload['credits_charged'] ?: '0') . " TL</td></tr>";
        echo "</table>";
        $userId = $upload['user_id'];
    } else {
        echo "<p class='error'>❌ Ana dosya bulunamadı!</p>";
        die();
    }
    echo "</div>";

    // 2. Yanıt dosyaları
    echo "<div class='section'>";
    echo "<h2>2. Yanıt Dosyaları</h2>";
    $stmt = $pdo->prepare("
        SELECT fr.*, u.username as admin_username 
        FROM file_responses fr 
        LEFT JOIN users u ON fr.admin_id = u.id 
        WHERE fr.upload_id = ? 
        ORDER BY fr.upload_date ASC
    ");
    $stmt->execute([$fileId]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($responses)) {
        echo "<table>";
        echo "<tr><th>Response ID</th><th>Dosya Adı</th><th>Admin</th><th>Kredi</th><th>Tarih</th></tr>";
        foreach ($responses as $response) {
            echo "<tr>";
            echo "<td>" . substr($response['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($response['original_name']) . "</td>";
            echo "<td>" . htmlspecialchars($response['admin_username'] ?: 'N/A') . "</td>";
            echo "<td>" . ($response['credits_charged'] ?: '0') . " TL</td>";
            echo "<td>{$response['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Yanıt dosyası bulunamadı.</p>";
    }
    echo "</div>";

    // 3. Revizyon talepleri
    echo "<div class='section'>";
    echo "<h2>3. Revizyon Talepleri</h2>";
    $stmt = $pdo->prepare("
        SELECT r.*, 
               fr.original_name as response_file_name,
               u.username as user_username,
               a.username as admin_username
        FROM revisions r 
        LEFT JOIN file_responses fr ON r.response_id = fr.id 
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users a ON r.admin_id = a.id
        WHERE r.upload_id = ? OR r.response_id IN (
            SELECT id FROM file_responses WHERE upload_id = ?
        )
        ORDER BY r.requested_at ASC
    ");
    $stmt->execute([$fileId, $fileId]);
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($revisions)) {
        echo "<table>";
        echo "<tr><th>Revision ID</th><th>Tip</th><th>Hedef Dosya</th><th>Kullanıcı</th><th>Admin</th><th>Kredi</th><th>Durum</th><th>Tarih</th></tr>";
        foreach ($revisions as $revision) {
            $type = $revision['upload_id'] ? 'Ana Dosya' : 'Yanıt Dosyası';
            $targetFile = $revision['response_file_name'] ?: 'Ana Dosya';
            
            echo "<tr>";
            echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
            echo "<td>{$type}</td>";
            echo "<td>" . htmlspecialchars($targetFile) . "</td>";
            echo "<td>" . htmlspecialchars($revision['user_username'] ?: 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($revision['admin_username'] ?: 'N/A') . "</td>";
            echo "<td class='highlight'>" . ($revision['credits_charged'] ?: '0') . " TL</td>";
            echo "<td>{$revision['status']}</td>";
            echo "<td>{$revision['requested_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ Revizyon talebi bulunamadı.</p>";
    }
    echo "</div>";

    // 4. Şu andaki hesaplama (ESKİ)
    echo "<div class='section'>";
    echo "<h2>4. Mevcut Hesaplama (ESKİ METHOD)</h2>";
    
    $currentTotal = 0;
    
    // Ana dosya için yanıt dosyalarında harcanan krediler
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM file_responses WHERE upload_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)");
    $stmt->execute([$fileId]);
    $responseCredits = $stmt->fetchColumn() ?: 0;
    $currentTotal += $responseCredits;
    echo "<p><strong>1. Yanıt dosyası kredileri:</strong> {$responseCredits} TL</p>";

    // Ana dosya için revizyon talepleri
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM revisions WHERE upload_id = ? AND user_id = ?");
    $stmt->execute([$fileId, $userId]);
    $directRevisionCredits = $stmt->fetchColumn() ?: 0;
    $currentTotal += $directRevisionCredits;
    echo "<p><strong>2. Ana dosya revizyon kredileri:</strong> {$directRevisionCredits} TL</p>";

    // Yanıt dosyalarının revizyon talepleri
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(r.credits_charged), 0) as total_credits 
        FROM revisions r 
        INNER JOIN file_responses fr ON r.response_id = fr.id 
        WHERE fr.upload_id = ? AND r.user_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
    ");
    $stmt->execute([$fileId, $userId]);
    $responseRevisionCredits = $stmt->fetchColumn() ?: 0;
    $currentTotal += $responseRevisionCredits;
    echo "<p><strong>3. Yanıt dosyası revizyon kredileri:</strong> <span class='highlight'>{$responseRevisionCredits} TL</span></p>";

    echo "<p class='code'><strong>MEVCUT TOPLAM: {$currentTotal} TL</strong></p>";
    echo "</div>";

    // 5. YENİ hesaplama metodu (DÜZELTİLMİŞ)
    echo "<div class='section'>";
    echo "<h2>5. YENİ Hesaplama Metodu (DÜZELTİLMİŞ)</h2>";
    
    $newTotal = 0;
    
    // Yanıt dosyalarının orijinal ücretleri (tümü)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(credits_charged), 0) as total_response_cost
        FROM file_responses 
        WHERE upload_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)
    ");
    $stmt->execute([$fileId]);
    $allResponseCost = $stmt->fetchColumn() ?: 0;
    $newTotal += $allResponseCost;
    echo "<p><strong>1. Tüm yanıt dosyaları orijinal ücretleri:</strong> {$allResponseCost} TL</p>";

    // Ana dosyaya direkt revizyon talepleri
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(credits_charged), 0) as total_direct_revision_cost
        FROM revisions 
        WHERE upload_id = ? AND response_id IS NULL AND user_id = ?
        AND status IN ('completed', 'pending', 'processing')
    ");
    $stmt->execute([$fileId, $userId]);
    $directRevisionCost = $stmt->fetchColumn() ?: 0;
    $newTotal += $directRevisionCost;
    echo "<p><strong>2. Ana dosya direkt revizyon ücretleri:</strong> {$directRevisionCost} TL</p>";

    // Yanıt dosyalarının revizyon talepleri
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(r.credits_charged), 0) as total_response_revision_cost
        FROM revisions r 
        INNER JOIN file_responses fr ON r.response_id = fr.id 
        WHERE fr.upload_id = ? AND r.user_id = ? 
        AND r.status IN ('completed', 'pending', 'processing')
        AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
    ");
    $stmt->execute([$fileId, $userId]);
    $responseRevisionCost = $stmt->fetchColumn() ?: 0;
    $newTotal += $responseRevisionCost;
    echo "<p><strong>3. Yanıt dosyaları revizyon ücretleri:</strong> <span class='highlight'>{$responseRevisionCost} TL</span></p>";

    echo "<p class='code'><strong>YENİ TOPLAM (DÜZELTİLMİŞ): {$newTotal} TL</strong></p>";
    echo "</div>";

    // 6. Karşılaştırma
    echo "<div class='section'>";
    echo "<h2>6. Karşılaştırma</h2>";
    $difference = $currentTotal - $newTotal;
    
    if ($difference > 0) {
        echo "<div class='error'>";
        echo "<h3>❌ Mevcut hesaplama FAZLA sayıyor!</h3>";
        echo "<p><strong>Mevcut:</strong> {$currentTotal} TL</p>";
        echo "<p><strong>Düzeltilmiş:</strong> {$newTotal} TL</p>";
        echo "<p><strong>Fark:</strong> {$difference} TL (çift sayım)</p>";
        echo "</div>";
    } elseif ($difference < 0) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Düzeltilmiş hesaplama daha yüksek!</h3>";
        echo "<p>Bu beklenmeyen bir durum, kontrol edilmeli.</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Her iki hesaplama da aynı sonucu veriyor</h3>";
        echo "<p>Bu dosya için çift sayım sorunu yok.</p>";
        echo "</div>";
    }
    echo "</div>";

    // 7. Revizyon dosyaları (eğer varsa)
    echo "<div class='section'>";
    echo "<h2>7. Revizyon Dosyaları</h2>";
    
    $stmt = $pdo->prepare("
        SELECT rf.*, r.request_notes, r.credits_charged as revision_cost
        FROM revision_files rf
        INNER JOIN revisions r ON rf.revision_id = r.id
        WHERE r.upload_id = ? OR r.response_id IN (
            SELECT id FROM file_responses WHERE upload_id = ?
        )
        ORDER BY rf.upload_date DESC
    ");
    $stmt->execute([$fileId, $fileId]);
    $revisionFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($revisionFiles)) {
        echo "<table>";
        echo "<tr><th>Dosya ID</th><th>Dosya Adı</th><th>Revizyon Ücreti</th><th>Tarih</th></tr>";
        foreach ($revisionFiles as $revFile) {
            echo "<tr>";
            echo "<td>" . substr($revFile['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($revFile['original_name']) . "</td>";
            echo "<td>" . ($revFile['revision_cost'] ?: '0') . " TL</td>";
            echo "<td>{$revFile['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Henüz revizyon dosyası bulunmuyor.</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
