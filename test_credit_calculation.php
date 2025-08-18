<?php
/**
 * Kredi Hesaplama Test Dosyası
 * file-detail.php'deki düzeltmeyi test etmek için
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
    <title>Kredi Hesaplama Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .old-calculation { background-color: #ffebee; }
        .new-calculation { background-color: #e8f5e8; }
    </style>
</head>
<body>";

echo "<h1>🧮 Kredi Hesaplama Test</h1>";
echo "<p>Bu sayfa, <code>file-detail.php</code>'deki revizyon ücretlerinin çift sayılması sorununu test eder.</p>";

// Test için örnek dosya ID'leri al
try {
    $stmt = $pdo->prepare("
        SELECT fu.id, fu.original_name, fu.user_id,
               COUNT(fr.id) as response_count,
               COUNT(r.id) as revision_count
        FROM file_uploads fu
        LEFT JOIN file_responses fr ON fu.id = fr.upload_id
        LEFT JOIN revisions r ON fu.id = r.upload_id OR r.response_id = fr.id
        WHERE fu.user_id IS NOT NULL
        GROUP BY fu.id, fu.original_name, fu.user_id
        HAVING response_count > 0 OR revision_count > 0
        ORDER BY fu.upload_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $testFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($testFiles)) {
        echo "<div class='section warning'>
                <h3>⚠️ Test Verisi Bulunamadı</h3>
                <p>Sistemde yanıt dosyası veya revizyon talebi olan dosya bulunamadı.</p>
              </div>";
    } else {
        echo "<div class='section'>
                <h2>📊 Test Sonuçları</h2>
                <p>Aşağıdaki dosyalar için eski ve yeni hesaplama metodları karşılaştırılıyor:</p>
              </div>";

        foreach ($testFiles as $testFile) {
            $fileId = $testFile['id'];
            $userId = $testFile['user_id'];
            
            echo "<div class='section'>
                    <h3>📄 " . htmlspecialchars($testFile['original_name']) . "</h3>
                    <p><strong>Dosya ID:</strong> " . substr($fileId, 0, 8) . "... 
                       <strong>Yanıt Sayısı:</strong> {$testFile['response_count']} 
                       <strong>Revizyon Sayısı:</strong> {$testFile['revision_count']}</p>";

            // ESKİ HESAPLAMA METODu (Çift sayım olan)
            $oldTotal = 0;
            try {
                // Eski metod - Ana dosya için yanıt dosyalarında harcanan krediler
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM file_responses WHERE upload_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)");
                $stmt->execute([$fileId]);
                $oldResponseCredits = $stmt->fetchColumn() ?: 0;
                $oldTotal += $oldResponseCredits;

                // Eski metod - Ana dosya için revizyon talepleri
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM revisions WHERE upload_id = ? AND user_id = ?");
                $stmt->execute([$fileId, $userId]);
                $oldRevisionCredits = $stmt->fetchColumn() ?: 0;
                $oldTotal += $oldRevisionCredits;

                // Eski metod - Yanıt dosyalarının revizyon talepleri (ÇİFT SAYIM!)
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(r.credits_charged), 0) as total_credits 
                    FROM revisions r 
                    INNER JOIN file_responses fr ON r.response_id = fr.id 
                    WHERE fr.upload_id = ? AND r.user_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
                ");
                $stmt->execute([$fileId, $userId]);
                $oldResponseRevisionCredits = $stmt->fetchColumn() ?: 0;
                $oldTotal += $oldResponseRevisionCredits;

                echo "<div class='old-calculation'>
                        <h4>❌ Eski Hesaplama (Hatalı - Çift Sayım)</h4>
                        <ul>
                            <li>Yanıt dosyası kredileri: {$oldResponseCredits} TL</li>
                            <li>Ana dosya revizyon kredileri: {$oldRevisionCredits} TL</li>
                            <li>Yanıt dosyası revizyon kredileri: {$oldResponseRevisionCredits} TL <span style='color:red;'>(ÇIFT SAYIM!)</span></li>
                            <li><strong>Toplam: {$oldTotal} TL</strong></li>
                        </ul>
                      </div>";

            } catch (Exception $e) {
                echo "<div class='error'>Eski hesaplama hatası: " . $e->getMessage() . "</div>";
            }

            // YENİ HESAPLAMA METODu (Düzeltilmiş)
            $newTotal = 0;
            try {
                // Yeni metod - Yanıt dosyalarının orijinal ücretleri (revizyon hariç)
                $stmt = $pdo->prepare("
                    SELECT fr.id, fr.credits_charged,
                           COALESCE(
                               (SELECT SUM(r.credits_charged) 
                                FROM revisions r 
                                WHERE r.response_id = fr.id AND r.user_id = ?), 0
                           ) as revision_credits
                    FROM file_responses fr 
                    WHERE fr.upload_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
                ");
                $stmt->execute([$userId, $fileId]);
                $responseFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $newResponseCredits = 0;
                foreach ($responseFiles as $respFile) {
                    $originalCredits = max(0, ($respFile['credits_charged'] ?: 0) - ($respFile['revision_credits'] ?: 0));
                    $newResponseCredits += $originalCredits;
                }
                $newTotal += $newResponseCredits;

                // Yeni metod - Ana dosya için direkt revizyon talepleri
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(credits_charged), 0) as total_credits FROM revisions WHERE upload_id = ? AND user_id = ? AND response_id IS NULL");
                $stmt->execute([$fileId, $userId]);
                $newDirectRevisionCredits = $stmt->fetchColumn() ?: 0;
                $newTotal += $newDirectRevisionCredits;

                // Yeni metod - Yanıt dosyalarının revizyon talepleri (TEK SAYIM)
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(r.credits_charged), 0) as total_credits 
                    FROM revisions r 
                    INNER JOIN file_responses fr ON r.response_id = fr.id 
                    WHERE fr.upload_id = ? AND r.user_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
                ");
                $stmt->execute([$fileId, $userId]);
                $newResponseRevisionCredits = $stmt->fetchColumn() ?: 0;
                $newTotal += $newResponseRevisionCredits;

                echo "<div class='new-calculation'>
                        <h4>✅ Yeni Hesaplama (Düzeltilmiş)</h4>
                        <ul>
                            <li>Yanıt dosyası orijinal kredileri: {$newResponseCredits} TL</li>
                            <li>Ana dosya direkt revizyon kredileri: {$newDirectRevisionCredits} TL</li>
                            <li>Yanıt dosyası revizyon kredileri: {$newResponseRevisionCredits} TL <span style='color:green;'>(TEK SAYIM)</span></li>
                            <li><strong>Toplam: {$newTotal} TL</strong></li>
                        </ul>
                      </div>";

                $difference = $oldTotal - $newTotal;
                if ($difference > 0) {
                    echo "<div class='success'>
                            <h4>💰 Tasarruf: {$difference} TL</h4>
                            <p>Yeni hesaplama metodu ile <strong>{$difference} TL</strong> daha az ücret hesaplanıyor (çift sayım düzeltildi).</p>
                          </div>";
                } elseif ($difference < 0) {
                    echo "<div class='error'>
                            <h4>⚠️ Dikkat: " . abs($difference) . " TL fark</h4>
                            <p>Yeni hesaplama daha yüksek çıktı, kontrol edilmeli.</p>
                          </div>";
                } else {
                    echo "<div class='warning'>
                            <h4>➡️ Aynı Sonuç</h4>
                            <p>Bu dosya için her iki metod da aynı sonucu veriyor.</p>
                          </div>";
                }

            } catch (Exception $e) {
                echo "<div class='error'>Yeni hesaplama hatası: " . $e->getMessage() . "</div>";
            }

            echo "</div>";
        }
    }

} catch (Exception $e) {
    echo "<div class='error'>Genel hata: " . $e->getMessage() . "</div>";
}

echo "<div class='section'>
        <h2>🔧 Düzeltme Özeti</h2>
        <p><strong>Sorun:</strong> Yanıt dosyasına revizyon talebi yapıldığında, revizyon ücreti hem yanıt dosyasının ücretine ekleniyordu hem de ayrıca revizyon tablosundan tekrar ekleniyordu.</p>
        <p><strong>Çözüm:</strong> Yanıt dosyasının orijinal ücretini hesaplayıp (toplam ücret - revizyon ücretleri) sonra revizyon ücretlerini ayrı olarak ekliyoruz.</p>
        <p><strong>Sonuç:</strong> Revizyon ücretleri artık sadece bir kez sayılıyor.</p>
      </div>";

echo "<div class='section'>
        <h3>📋 Test Edilecek URL'ler</h3>
        <p>Aşağıdaki dosyalar için <code>user/file-detail.php</code> sayfasını kontrol edebilirsiniz:</p>
        <ul>";

foreach ($testFiles as $file) {
    echo "<li><a href='user/file-detail.php?id={$file['id']}' target='_blank'>" . htmlspecialchars($file['original_name']) . "</a></li>";
}

echo "  </ul>
      </div>";

echo "</body></html>";
?>
