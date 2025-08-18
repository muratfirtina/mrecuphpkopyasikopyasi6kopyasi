<?php
/**
 * İptal Sistemi Debug - Hangi dosyalar iptal edilmiş?
 * ID: 1bb48fe6-f11c-494c-9089-bdbd619211c4
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>İptal Sistemi Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .cancelled { background-color: #ffebee; }
        .active { background-color: #e8f5e8; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>";

$fileId = '1bb48fe6-f11c-494c-9089-bdbd619211c4';
echo "<h1>🔍 İptal Sistemi Debug: " . substr($fileId, 0, 8) . "...</h1>";

try {
    // 1. Ana dosya bilgisi
    echo "<div class='section'>";
    echo "<h2>1. Ana Dosya Bilgisi</h2>";
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
    $stmt->execute([$fileId]);
    $upload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($upload) {
        echo "<p><strong>Dosya:</strong> " . htmlspecialchars($upload['original_name']) . "</p>";
        echo "<p><strong>Kullanıcı ID:</strong> {$upload['user_id']}</p>";
        $userId = $upload['user_id'];
    } else {
        die("Ana dosya bulunamadı!");
    }
    echo "</div>";

    // 2. Yanıt dosyaları (iptal durumu ile)
    echo "<div class='section'>";
    echo "<h2>2. Yanıt Dosyaları</h2>";
    $stmt = $pdo->prepare("
        SELECT fr.*, u.username as admin_username,
               CASE WHEN fr.is_cancelled = 1 THEN 'İPTAL' ELSE 'AKTİF' END as status_text
        FROM file_responses fr 
        LEFT JOIN users u ON fr.admin_id = u.id 
        WHERE fr.upload_id = ? 
        ORDER BY fr.upload_date ASC
    ");
    $stmt->execute([$fileId]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($responses)) {
        echo "<table>";
        echo "<tr><th>Response ID</th><th>Dosya Adı</th><th>Kredi</th><th>İptal Durumu</th><th>Tarih</th></tr>";
        foreach ($responses as $response) {
            $class = ($response['is_cancelled'] == 1) ? 'cancelled' : 'active';
            echo "<tr class='{$class}'>";
            echo "<td>" . substr($response['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($response['original_name']) . "</td>";
            echo "<td>" . ($response['credits_charged'] ?: '0') . " TL</td>";
            echo "<td>{$response['status_text']}</td>";
            echo "<td>{$response['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Yanıt dosyası bulunamadı.</p>";
    }
    echo "</div>";

    // 3. Revizyon talepleri (iptal durumu ile)
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
        echo "<tr><th>Revision ID</th><th>Tip</th><th>Hedef Dosya</th><th>Kredi</th><th>Durum</th><th>Tarih</th></tr>";
        foreach ($revisions as $revision) {
            $type = $revision['upload_id'] ? 'Ana Dosya' : 'Yanıt Dosyası';
            $targetFile = $revision['response_file_name'] ?: 'Ana Dosya';
            $class = ($revision['status'] == 'cancelled') ? 'cancelled' : 'active';
            
            echo "<tr class='{$class}'>";
            echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
            echo "<td>{$type}</td>";
            echo "<td>" . htmlspecialchars($targetFile) . "</td>";
            echo "<td>" . ($revision['credits_charged'] ?: '0') . " TL</td>";
            echo "<td>{$revision['status']}</td>";
            echo "<td>{$revision['requested_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Revizyon talebi bulunamadı.</p>";
    }
    echo "</div>";

    // 4. İptal işlemleri tablosu kontrol (eğer varsa)
    echo "<div class='section'>";
    echo "<h2>4. İptal İşlemleri (file_cancellations)</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT fc.*, u.username as user_username, a.username as admin_username
            FROM file_cancellations fc
            LEFT JOIN users u ON fc.user_id = u.id
            LEFT JOIN users a ON fc.admin_id = a.id
            WHERE fc.file_id = ? OR fc.file_id IN (
                SELECT id FROM file_responses WHERE upload_id = ?
            )
            ORDER BY fc.requested_at DESC
        ");
        $stmt->execute([$fileId, $fileId]);
        $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($cancellations)) {
            echo "<table>";
            echo "<tr><th>Cancellation ID</th><th>Dosya ID</th><th>Dosya Tipi</th><th>Kullanıcı</th><th>Admin</th><th>Durum</th><th>Kredi İadesi</th><th>Tarih</th></tr>";
            foreach ($cancellations as $cancel) {
                $class = ($cancel['status'] == 'approved') ? 'cancelled' : 'active';
                echo "<tr class='{$class}'>";
                echo "<td>" . substr($cancel['id'], 0, 8) . "...</td>";
                echo "<td>" . substr($cancel['file_id'], 0, 8) . "...</td>";
                echo "<td>{$cancel['file_type']}</td>";
                echo "<td>" . htmlspecialchars($cancel['user_username'] ?: 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($cancel['admin_username'] ?: 'N/A') . "</td>";
                echo "<td>{$cancel['status']}</td>";
                echo "<td>" . ($cancel['refund_amount'] ?: '0') . " TL</td>";
                echo "<td>{$cancel['requested_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>İptal işlemi bulunamadı.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>file_cancellations tablosu bulunamadı veya hata: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // 5. Kredi geçmişi - son 10 işlem
    echo "<div class='section'>";
    echo "<h2>5. Kredi Geçmişi (Son 10 İşlem)</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT ct.*, 
                   CASE 
                       WHEN ct.amount > 0 THEN 'EKLEME'
                       WHEN ct.amount < 0 THEN 'KESME'
                       ELSE 'NÖTREl'
                   END as transaction_type
            FROM credit_transactions ct
            WHERE ct.user_id = ? 
            AND (ct.description LIKE ? OR ct.description LIKE ?)
            ORDER BY ct.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$userId, '%' . $fileId . '%', '%' . substr($fileId, 0, 8) . '%']);
        $creditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($creditHistory)) {
            echo "<table>";
            echo "<tr><th>Transaction ID</th><th>Miktar</th><th>Tip</th><th>Açıklama</th><th>Tarih</th></tr>";
            foreach ($creditHistory as $credit) {
                $class = ($credit['amount'] > 0) ? 'active' : 'cancelled';
                echo "<tr class='{$class}'>";
                echo "<td>" . substr($credit['id'], 0, 8) . "...</td>";
                echo "<td>" . $credit['amount'] . " TL</td>";
                echo "<td>{$credit['transaction_type']}</td>";
                echo "<td>" . htmlspecialchars($credit['description']) . "</td>";
                echo "<td>{$credit['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Bu dosya ile ilgili kredi işlemi bulunamadı.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Kredi geçmişi hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // 6. Şu andaki toplam hesaplama
    echo "<div class='section'>";
    echo "<h2>6. Şu Andaki Toplam Hesaplama</h2>";
    
    // Aktif yanıt dosyaları
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(credits_charged), 0) as total_response_cost
        FROM file_responses 
        WHERE upload_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)
    ");
    $stmt->execute([$fileId]);
    $activeResponseCost = $stmt->fetchColumn() ?: 0;
    echo "<p><strong>Aktif yanıt dosyaları:</strong> {$activeResponseCost} TL</p>";
    
    // İptal edilen yanıt dosyaları
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(credits_charged), 0) as total_cancelled_cost
        FROM file_responses 
        WHERE upload_id = ? AND is_cancelled = 1
    ");
    $stmt->execute([$fileId]);
    $cancelledResponseCost = $stmt->fetchColumn() ?: 0;
    echo "<p><strong>İptal edilen yanıt dosyaları:</strong> {$cancelledResponseCost} TL</p>";
    
    // İptal edilen revizyonlar (YENİ)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(credits_charged), 0) as total_cancelled_revision_cost
        FROM revisions 
        WHERE (upload_id = ? OR response_id IN (SELECT id FROM file_responses WHERE upload_id = ?))
        AND user_id = ? AND is_cancelled = 1
    ");
    $stmt->execute([$fileId, $fileId, $userId]);
    $cancelledRevisionCost = $stmt->fetchColumn() ?: 0;
    echo "<p><strong>İptal edilen revizyonlar (YENİ):</strong> {$cancelledRevisionCost} TL</p>";
    
    // Aktif revizyonlar (YENİ - is_cancelled kontrolü ile)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(r.credits_charged), 0) as total_active_revision_cost
        FROM revisions r 
        INNER JOIN file_responses fr ON r.response_id = fr.id 
        WHERE fr.upload_id = ? AND r.user_id = ? 
        AND r.status IN ('completed', 'pending', 'processing')
        AND (r.is_cancelled IS NULL OR r.is_cancelled = 0)
        AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
    ");
    $stmt->execute([$fileId, $userId]);
    $activeRevisionCost = $stmt->fetchColumn() ?: 0;
    echo "<p><strong>Aktif revizyonlar (YENİ):</strong> {$activeRevisionCost} TL</p>";
    
    $totalActiveSpent = $activeResponseCost + $activeRevisionCost;
    $totalCancelledSpent = $cancelledResponseCost + $cancelledRevisionCost;
    
    echo "<h3>ÖZET:</h3>";
    echo "<p><strong>Toplam Aktif Harcama:</strong> {$totalActiveSpent} TL</p>";
    echo "<p><strong>Toplam İptal Edilen:</strong> {$totalCancelledSpent} TL</p>";
    echo "<p><strong>Net Harcama (Aktif):</strong> {$totalActiveSpent} TL</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='color: red;'>Genel hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
