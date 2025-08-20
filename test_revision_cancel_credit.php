<?php
/**
 * Test Revizyon İptal ve Kredi İadesi
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Bu sayfaya erişim yetkiniz yok.');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Revizyon İptal Kredi Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🧪 Revizyon İptal ve Kredi İadesi Test</h1>";

try {
    
    // 1. Aktif revizyon taleplerini listele
    echo "<div class='step'>";
    echo "<h2>1. Aktif Revizyon Talepleri</h2>";
    
    $stmt = $pdo->query("
        SELECT r.*, u.username, u.first_name, u.last_name, u.credit_quota, u.credit_used, 
               (u.credit_quota - u.credit_used) as available_credits
        FROM revisions r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.is_cancelled = 0 OR r.is_cancelled IS NULL
        ORDER BY r.requested_at DESC
        LIMIT 10
    ");
    $revisions = $stmt->fetchAll();
    
    if (!empty($revisions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Kullanıcı</th><th>Krediler (Kullanılan/Kota/Kullanılabilir)</th><th>Durum</th><th>Kredi Ücreti</th><th>Tarih</th><th>İşlemler</th></tr>";
        
        foreach ($revisions as $revision) {
            echo "<tr>";
            echo "<td>" . substr($revision['id'], 0, 8) . "...</td>";
            echo "<td>{$revision['username']} ({$revision['first_name']} {$revision['last_name']})</td>";
            echo "<td>{$revision['credit_used']}/{$revision['credit_quota']}/{$revision['available_credits']}</td>";
            echo "<td>" . strtoupper($revision['status']) . "</td>";
            echo "<td>{$revision['credits_charged']} TL</td>";
            echo "<td>{$revision['requested_at']}</td>";
            echo "<td>";
            if (empty($revision['is_cancelled'])) {
                echo "<a href='?test_cancel={$revision['id']}' class='btn btn-danger' onclick='return confirm(\"Bu revizyon talebini test için iptal etmek istediğinizden emin misiniz?\")'>Test İptal</a>";
            } else {
                echo "<span style='color: #666;'>İptal Edilmiş</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>❌ Hiç aktif revizyon talebi bulunamadı.</div>";
    }
    echo "</div>";
    
    // 2. İptal test işlemi
    if (isset($_GET['test_cancel'])) {
        $revisionId = sanitize($_GET['test_cancel']);
        
        echo "<div class='step'>";
        echo "<h2>2. İptal İşlemi Test Ediliyor</h2>";
        echo "<div class='info'>Test edilen revizyon ID: {$revisionId}</div>";
        
        // Revizyon bilgilerini al
        $stmt = $pdo->prepare("
            SELECT r.*, u.username, u.credit_quota, u.credit_used
            FROM revisions r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$revisionId]);
        $revision = $stmt->fetch();
        
        if ($revision) {
            echo "<h3>İptal Öncesi Durum:</h3>";
            echo "<ul>";
            echo "<li><strong>Kullanıcı:</strong> {$revision['username']}</li>";
            echo "<li><strong>Kredi Kullanımı:</strong> {$revision['credit_used']}/{$revision['credit_quota']}</li>";
            echo "<li><strong>Kullanılabilir Kredi:</strong> " . ($revision['credit_quota'] - $revision['credit_used']) . "</li>";
            echo "<li><strong>Revizyon Ücreti:</strong> {$revision['credits_charged']} TL</li>";
            echo "</ul>";
            
            // FileCancellationManager ile iptal et
            require_once 'includes/FileCancellationManager.php';
            $cancellationManager = new FileCancellationManager($pdo);
            
            $result = $cancellationManager->adminDirectCancellation(
                $revisionId, 
                'revision_request', 
                $_SESSION['user_id'], 
                'Test iptal işlemi - kredi iadesi kontrolü'
            );
            
            if ($result['success']) {
                echo "<div class='success'>✅ İptal başarılı: {$result['message']}</div>";
                
                // İptal sonrası durumu kontrol et
                $stmt = $pdo->prepare("
                    SELECT u.credit_quota, u.credit_used, r.is_cancelled
                    FROM users u
                    LEFT JOIN revisions r ON r.user_id = u.id
                    WHERE u.id = ? AND r.id = ?
                ");
                $stmt->execute([$revision['user_id'], $revisionId]);
                $afterCancel = $stmt->fetch();
                
                echo "<h3>İptal Sonrası Durum:</h3>";
                echo "<ul>";
                echo "<li><strong>Kredi Kullanımı:</strong> {$afterCancel['credit_used']}/{$afterCancel['credit_quota']}</li>";
                echo "<li><strong>Kullanılabilir Kredi:</strong> " . ($afterCancel['credit_quota'] - $afterCancel['credit_used']) . "</li>";
                echo "<li><strong>İptal Durumu:</strong> " . ($afterCancel['is_cancelled'] ? 'EVET' : 'HAYIR') . "</li>";
                echo "</ul>";
                
                // Kredi değişimi
                $creditChange = $revision['credit_used'] - $afterCancel['credit_used'];
                if ($creditChange > 0) {
                    echo "<div class='success'>✅ {$creditChange} TL kredi iade edildi!</div>";
                } else {
                    echo "<div class='error'>❌ Kredi iadesi yapılmadı veya hatalı.</div>";
                }
                
                // Son kredi işlemlerini göster
                echo "<h3>Son Kredi İşlemleri:</h3>";
                $stmt = $pdo->prepare("
                    SELECT * FROM credit_transactions 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                $stmt->execute([$revision['user_id']]);
                $transactions = $stmt->fetchAll();
                
                if (!empty($transactions)) {
                    echo "<table>";
                    echo "<tr><th>Tip</th><th>Type</th><th>Miktar</th><th>Açıklama</th><th>Tarih</th></tr>";
                    foreach ($transactions as $tx) {
                        $colorClass = $tx['transaction_type'] === 'deposit' ? 'success' : 'danger';
                        echo "<tr>";
                        echo "<td><span style='color: " . ($tx['transaction_type'] === 'deposit' ? 'green' : 'red') . ";'>{$tx['transaction_type']}</span></td>";
                        echo "<td>{$tx['type']}</td>";
                        echo "<td>{$tx['amount']} TL</td>";
                        echo "<td>{$tx['description']}</td>";
                        echo "<td>{$tx['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<div class='warning'>⚠️ Hiç kredi işlemi bulunamadı.</div>";
                }
                
            } else {
                echo "<div class='error'>❌ İptal başarısız: {$result['message']}</div>";
            }
            
        } else {
            echo "<div class='error'>❌ Revizyon bulunamadı.</div>";
        }
        echo "</div>";
    }
    
    // 3. İptal edilmiş revizyonları göster
    echo "<div class='step'>";
    echo "<h2>3. İptal Edilmiş Revizyonlar</h2>";
    
    $stmt = $pdo->query("
        SELECT r.*, u.username, fc.credits_to_refund, fc.status as cancel_status, fc.processed_at
        FROM revisions r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN file_cancellations fc ON fc.file_id = r.id AND fc.file_type = 'revision_request'
        WHERE r.is_cancelled = 1
        ORDER BY r.cancelled_at DESC
        LIMIT 10
    ");
    $cancelledRevisions = $stmt->fetchAll();
    
    if (!empty($cancelledRevisions)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Kullanıcı</th><th>Orijinal Ücret</th><th>İade Edilen</th><th>İptal Durumu</th><th>İptal Tarihi</th></tr>";
        
        foreach ($cancelledRevisions as $cancelled) {
            echo "<tr>";
            echo "<td>" . substr($cancelled['id'], 0, 8) . "...</td>";
            echo "<td>{$cancelled['username']}</td>";
            echo "<td>{$cancelled['credits_charged']} TL</td>";
            echo "<td style='color: green;'>{$cancelled['credits_to_refund']} TL</td>";
            echo "<td>" . strtoupper($cancelled['cancel_status'] ?? 'N/A') . "</td>";
            echo "<td>{$cancelled['processed_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>📝 Henüz iptal edilmiş revizyon yok.</div>";
    }
    echo "</div>";
    
    // 4. Özet
    echo "<div class='step'>";
    echo "<h2>4. İptal Sistemi Özeti</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_revisions,
            SUM(CASE WHEN is_cancelled = 1 THEN 1 ELSE 0 END) as cancelled_revisions,
            SUM(CASE WHEN is_cancelled = 1 THEN credits_charged ELSE 0 END) as total_cancelled_credits
        FROM revisions
    ");
    $summary = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_cancellations,
            SUM(credits_to_refund) as total_refunded,
            SUM(CASE WHEN status = 'approved' THEN credits_to_refund ELSE 0 END) as approved_refunds
        FROM file_cancellations 
        WHERE file_type = 'revision_request'
    ");
    $cancelSummary = $stmt->fetch();
    
    echo "<ul>";
    echo "<li><strong>Toplam Revizyon Sayısı:</strong> {$summary['total_revisions']}</li>";
    echo "<li><strong>İptal Edilmiş Revizyon:</strong> {$summary['cancelled_revisions']}</li>";
    echo "<li><strong>İptal Edilen Toplam Ücret:</strong> {$summary['total_cancelled_credits']} TL</li>";
    echo "<li><strong>Toplam İptal Talebi:</strong> {$cancelSummary['total_cancellations']}</li>";
    echo "<li><strong>Onaylanmış İade:</strong> {$cancelSummary['approved_refunds']} TL</li>";
    echo "</ul>";
    
    if ($summary['total_cancelled_credits'] == $cancelSummary['approved_refunds']) {
        echo "<div class='success'>✅ Kredi iadesi sistemi düzgün çalışıyor!</div>";
    } else {
        echo "<div class='warning'>⚠️ Kredi iadesi miktarları eşleşmiyor.</div>";
    }
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔗 Test Linkleri:</h3>";
    echo "<p><a href='admin/revisions.php' class='btn'>Admin Revizyon Sayfası</a></p>";
    echo "<p><a href='{$_SERVER['PHP_SELF']}' class='btn btn-success'>Sayfayı Yenile</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";
echo "</body></html>";
?>
