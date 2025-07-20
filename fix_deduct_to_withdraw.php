<?php
/**
 * Deduct to Withdraw Migration Script
 * Bu script veritabanındaki tüm "deduct" transaction_type kayıtlarını "withdraw" olarak günceller
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Deduct to Withdraw Migration Script</h2>\n";
echo "<p>Bu script tüm 'deduct' transaction_type kayıtlarını 'withdraw' olarak güncelleyecek.</p>\n";

try {
    // İlk olarak kaç adet deduct kaydı olduğunu kontrol edelim
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM credit_transactions WHERE transaction_type = 'deduct'");
    $stmt->execute();
    $deductCount = $stmt->fetch()['count'];
    
    echo "<p><strong>Bulunan 'deduct' kayıtları:</strong> {$deductCount} adet</p>\n";
    
    if ($deductCount > 0) {
        // Önce örnek kayıtları gösterelim
        echo "<h3>Örnek Kayıtlar:</h3>\n";
        $stmt = $pdo->prepare("
            SELECT id, user_id, transaction_type, amount, description, created_at 
            FROM credit_transactions 
            WHERE transaction_type = 'deduct' 
            LIMIT 5
        ");
        $stmt->execute();
        $examples = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr>\n";
        foreach ($examples as $example) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($example['id']) . "</td>";
            echo "<td>" . htmlspecialchars($example['user_id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($example['transaction_type']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($example['amount']) . "</td>";
            echo "<td>" . htmlspecialchars($example['description']) . "</td>";
            echo "<td>" . htmlspecialchars($example['created_at']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Onay formu
        if (!isset($_POST['confirm'])) {
            echo "<form method='POST'>";
            echo "<p><strong>DİKKAT:</strong> Bu işlem geri alınamaz! Tüm 'deduct' kayıtları 'withdraw' olarak güncellenecek.</p>";
            echo "<input type='hidden' name='confirm' value='1'>";
            echo "<input type='submit' value='ONAYLA VE GÜNCELLEYİ' style='background: red; color: white; padding: 10px; font-size: 16px;'>";
            echo "</form>";
        } else {
            // Güncelleme işlemini gerçekleştir
            echo "<h3>Güncelleme Yapılıyor...</h3>\n";
            
            $pdo->beginTransaction();
            
            try {
                // transaction_type alanını güncelle
                $stmt = $pdo->prepare("UPDATE credit_transactions SET transaction_type = 'withdraw' WHERE transaction_type = 'deduct'");
                $result = $stmt->execute();
                $affectedRows = $stmt->rowCount();
                
                if ($result) {
                    $pdo->commit();
                    echo "<p style='color: green;'><strong>✅ Başarılı!</strong> {$affectedRows} adet kayıt güncellendi.</p>\n";
                    
                    // Güncellenmiş kayıtları kontrol edelim
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM credit_transactions WHERE transaction_type = 'deduct'");
                    $stmt->execute();
                    $remainingDeduct = $stmt->fetch()['count'];
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM credit_transactions WHERE transaction_type = 'withdraw'");
                    $stmt->execute();
                    $totalWithdraw = $stmt->fetch()['count'];
                    
                    echo "<p><strong>Güncellenmiş durumu:</strong></p>\n";
                    echo "<ul>\n";
                    echo "<li>Kalan 'deduct' kayıtları: {$remainingDeduct}</li>\n";
                    echo "<li>Toplam 'withdraw' kayıtları: {$totalWithdraw}</li>\n";
                    echo "</ul>\n";
                    
                } else {
                    $pdo->rollBack();
                    echo "<p style='color: red;'><strong>❌ Hata!</strong> Güncelleme işlemi başarısız.</p>\n";
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<p style='color: red;'><strong>❌ Hata!</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
        
    } else {
        echo "<p style='color: green;'><strong>✅ Güncelleme gereksiz!</strong> Zaten 'deduct' kaydı bulunmuyor.</p>\n";
    }
    
    // İsteğe bağlı: type alanını da kontrol edelim
    echo "<hr>\n";
    echo "<h3>Type Alanı Kontrolü</h3>\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM credit_transactions WHERE type = 'deduct'");
    $stmt->execute();
    $typeDeductCount = $stmt->fetch()['count'];
    
    echo "<p><strong>'type' alanında bulunan 'deduct' kayıtları:</strong> {$typeDeductCount} adet</p>\n";
    
    if ($typeDeductCount > 0) {
        echo "<p><em>Not: 'type' alanındaki 'deduct' kayıtları da güncellenebilir, ancak bu alan referans amacıyla kullanılıyor olabilir.</em></p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Veritabanı Hatası:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='user/credits.php?debug=1'>Kullanıcı Credits Sayfasını Test Et</a></p>\n";
echo "<p><a href='admin/transactions.php'>Admin Transactions Sayfasını Kontrol Et</a></p>\n";
?>
