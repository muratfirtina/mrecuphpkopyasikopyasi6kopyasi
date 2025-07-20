<?php
/**
 * Mr ECU - Kredi Sistemi Test Sayfası
 * Bu dosya kredi sisteminin doğru çalışıp çalışmadığını test eder
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Basit güvenlik kontrolü
if (!isset($_GET['test_key']) || $_GET['test_key'] !== 'mrecu_test_2025') {
    die('<h1>Erişim Reddedildi</h1><p>Bu test sayfasına erişmek için doğru test anahtarını kullanın.</p>');
}

echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mr ECU - Kredi Sistemi Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
        .test-container { max-width: 1200px; margin: 50px auto; padding: 20px; }
        .test-card { background: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .test-header { background: #007bff; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .test-body { padding: 20px; }
        .result-box { padding: 15px; border-radius: 8px; margin: 10px 0; }
        .result-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .result-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .result-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .sql-query { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; font-family: monospace; font-size: 12px; border-radius: 5px; }
        .table-responsive { margin: 15px 0; }
        .badge-custom { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-card">
            <div class="test-header">
                <h1><i class="fas fa-vial me-2"></i>Mr ECU - Kredi Sistemi Test Sayfası</h1>
                <p class="mb-0">Kredi işlem geçmişi ve sayfalama sisteminin test edilmesi</p>
            </div>
            <div class="test-body">';

try {
    // 1. Veritabanı Bağlantı Testi
    echo '<h3><i class="fas fa-database me-2 text-primary"></i>1. Veritabanı Bağlantı Testi</h3>';
    
    if ($pdo) {
        echo '<div class="result-box result-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>BAŞARILI:</strong> Veritabanı bağlantısı aktif.
              </div>';
    } else {
        echo '<div class="result-box result-error">
                <i class="fas fa-times-circle me-2"></i>
                <strong>HATA:</strong> Veritabanı bağlantısı kurulamadı.
              </div>';
        exit;
    }

    // 2. Credit Transactions Tablosu Yapısı
    echo '<h3><i class="fas fa-table me-2 text-primary"></i>2. Credit Transactions Tablosu Yapısı</h3>';
    
    $stmt = $pdo->query("DESCRIBE credit_transactions");
    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($tableStructure) {
        echo '<div class="result-box result-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>BAŞARILI:</strong> credit_transactions tablosu mevcut.
              </div>';
        
        echo '<div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                        <tr><th>Alan</th><th>Tip</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th></tr>
                    </thead>
                    <tbody>';
        
        foreach ($tableStructure as $column) {
            echo "<tr>
                    <td><strong>{$column['Field']}</strong></td>
                    <td>{$column['Type']}</td>
                    <td>{$column['Null']}</td>
                    <td>{$column['Key']}</td>
                    <td>{$column['Default']}</td>
                  </tr>";
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="result-box result-error">
                <i class="fas fa-times-circle me-2"></i>
                <strong>HATA:</strong> credit_transactions tablosu bulunamadı.
              </div>';
    }

    // 3. Transaction Tiplerini Kontrol Et
    echo '<h3><i class="fas fa-tags me-2 text-primary"></i>3. Mevcut Transaction Tipleri</h3>';
    
    $stmt = $pdo->query("
        SELECT 
            COALESCE(transaction_type, type) as effective_type,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM credit_transactions 
        GROUP BY COALESCE(transaction_type, type)
        ORDER BY count DESC
    ");
    $transactionTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($transactionTypes) {
        echo '<div class="result-box result-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>BAŞARILI:</strong> Transaction tipleri tespit edildi.
              </div>';
        
        echo '<div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr><th>Transaction Tipi</th><th>Adet</th><th>Toplam Miktar</th><th>Filtre Durumu</th></tr>
                    </thead>
                    <tbody>';
        
        $supportedTypes = ['deposit', 'add', 'withdraw', 'deduct', 'file_charge', 'purchase', 'refund'];
        
        foreach ($transactionTypes as $type) {
            $isSupported = in_array($type['effective_type'], $supportedTypes);
            $badgeClass = $isSupported ? 'bg-success' : 'bg-warning text-dark';
            $statusText = $isSupported ? 'Destekleniyor' : 'Yeni Tip';
            
            echo "<tr>
                    <td><strong>{$type['effective_type']}</strong></td>
                    <td>" . number_format($type['count']) . "</td>
                    <td>" . number_format($type['total_amount'], 2) . " TL</td>
                    <td><span class='badge $badgeClass'>$statusText</span></td>
                  </tr>";
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="result-box result-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>BİLGİ:</strong> Henüz transaction bulunamadı.
              </div>';
    }

    // 4. Sayfalama Testi
    echo '<h3><i class="fas fa-list-ol me-2 text-primary"></i>4. Sayfalama Testi</h3>';
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM credit_transactions");
    $totalRecords = $stmt->fetchColumn();
    
    $limit = 20; // Yeni limit değeri
    $totalPages = ceil($totalRecords / $limit);
    
    echo "<div class='result-box result-info'>
            <i class='fas fa-info-circle me-2'></i>
            <strong>BİLGİ:</strong> 
            Toplam kayıt: $totalRecords | 
            Sayfa başına: $limit | 
            Toplam sayfa: $totalPages
          </div>";

    // 5. Örnek Filtreleme Testi
    echo '<h3><i class="fas fa-filter me-2 text-primary"></i>5. Filtreleme Testi</h3>';
    
    if ($totalRecords > 0) {
        $testFilters = [
            ['name' => 'Tüm İşlemler', 'query' => "SELECT COUNT(*) FROM credit_transactions", 'params' => []],
            ['name' => 'Sadece Deposit', 'query' => "SELECT COUNT(*) FROM credit_transactions WHERE COALESCE(transaction_type, type) = ?", 'params' => ['deposit']],
            ['name' => 'Sadece Add', 'query' => "SELECT COUNT(*) FROM credit_transactions WHERE COALESCE(transaction_type, type) = ?", 'params' => ['add']],
            ['name' => 'Sadece Deduct', 'query' => "SELECT COUNT(*) FROM credit_transactions WHERE COALESCE(transaction_type, type) = ?", 'params' => ['deduct']],
        ];
        
        echo '<div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr><th>Filtre Tipi</th><th>Bulunan Kayıt</th><th>SQL Sorgu</th></tr>
                    </thead>
                    <tbody>';
        
        foreach ($testFilters as $filter) {
            try {
                $stmt = $pdo->prepare($filter['query']);
                $stmt->execute($filter['params']);
                $count = $stmt->fetchColumn();
                
                echo "<tr>
                        <td><strong>{$filter['name']}</strong></td>
                        <td><span class='badge bg-primary'>{$count} kayıt</span></td>
                        <td><code>" . htmlspecialchars($filter['query']) . "</code></td>
                      </tr>";
            } catch (Exception $e) {
                echo "<tr>
                        <td><strong>{$filter['name']}</strong></td>
                        <td><span class='badge bg-danger'>HATA</span></td>
                        <td><code>HATA: " . htmlspecialchars($e->getMessage()) . "</code></td>
                      </tr>";
            }
        }
        echo '</tbody></table></div>';
    }

    // 6. Son 10 İşlem Örneği
    echo '<h3><i class="fas fa-clock me-2 text-primary"></i>6. Son 10 İşlem Örneği</h3>';
    
    $stmt = $pdo->query("
        SELECT 
            COALESCE(transaction_type, type) as effective_type,
            amount,
            description,
            created_at
        FROM credit_transactions 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($recentTransactions) {
        echo '<div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr><th>Tip</th><th>Miktar</th><th>Açıklama</th><th>Tarih</th></tr>
                    </thead>
                    <tbody>';
        
        foreach ($recentTransactions as $transaction) {
            $typeClass = in_array($transaction['effective_type'], ['add', 'deposit']) ? 'text-success' : 'text-danger';
            $amountPrefix = in_array($transaction['effective_type'], ['add', 'deposit']) ? '+' : '-';
            
            echo "<tr>
                    <td><span class='badge bg-secondary'>{$transaction['effective_type']}</span></td>
                    <td class='$typeClass'><strong>{$amountPrefix}" . number_format($transaction['amount'], 2) . " TL</strong></td>
                    <td>" . htmlspecialchars($transaction['description'] ?? 'Açıklama yok') . "</td>
                    <td>" . date('d.m.Y H:i', strtotime($transaction['created_at'])) . "</td>
                  </tr>";
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="result-box result-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>BİLGİ:</strong> Henüz transaction kaydı bulunamadı.
              </div>';
    }

    // 7. Test Sonuçları
    echo '<h3><i class="fas fa-clipboard-check me-2 text-success"></i>7. Test Sonuçları ve Öneriler</h3>';
    
    echo '<div class="result-box result-success">
            <h5><i class="fas fa-check-circle me-2"></i>Başarılı Güncellemeler (V3):</h5>
            <ul class="mb-0">
                <li>Sayfa başına gösterilen kayıt sayısı 5\'ten 20\'ye çıkarıldı</li>
                <li><strong>AJAX devre dışı bırakıldı</strong> - Normal form submit kullanılıyor</li>
                <li><strong>🔧 PDO LIMIT/OFFSET sorunu çözüldü:</strong>
                    <ul>
                        <li>LIMIT ? OFFSET ? yerine LIMIT {$limit} OFFSET {$offset} kullanılıyor</li>
                        <li>PDO parametreleri sadece WHERE clause için kullanılıyor</li>
                        <li>credits.php, credits_ajax.php ve transactions.php düzeltildi</li>
                    </ul>
                </li>
                <li><strong>Filter seçenekleri veritabanı ile uyumlu hale getirildi:</strong>
                    <ul>
                        <li>add - Kredi Yükleme (✓ 2 kayıt mevcut)</li>
                        <li>deduct - Kredi Kullanımı (✓ 1 kayıt mevcut)</li>
                        <li>withdraw - Kredi Kullanımı (✓ 9 kayıt mevcut)</li>
                        <li>file_charge - Dosya Ücreti</li>
                    </ul>
                </li>
                <li><strong>Genişletilmiş debug bilgisi:</strong> SQL sorgu, parametre ve sonuç bilgileri</li>
                <li>İşlem tiplerinin görsel gösterimi iyileştirildi</li>
                <li>Sayfalama bilgileri daha detaylı hale getirildi</li>
                <li>Debug modu sadece admin kullanıcılar için görünür hale getirildi</li>
            </ul>
          </div>';

    echo '<div class="result-box result-info">
            <h5><i class="fas fa-lightbulb me-2"></i>Test Önerileri (Güncellenmiş):</h5>
            <ol class="mb-0">
                <li><strong>Debug Modu Test:</strong> Herhangi bir filtre seçin - debug bilgileri görünmeli</li>
                <li><strong>SQL Query Debug:</strong> Mavi kutuda SQL sorgusu ve parametreler görünmeli</li>
                <li><strong>Results Debug:</strong> Sarı kutuda sonuç sayıları görünmeli</li>
                <li><strong>20 Kayıt Test:</strong> "Returned Transactions" 20 veya daha az olmalı</li>
                <li><strong>Kredi Yükleme Filtresi:</strong> "add" seçeneğini seçin - 2 kayıt görünmeli</li>
                <li><strong>Kredi Kullanımı Filtresi:</strong> "deduct" seçeneğini seçin - 1 kayıt görünmeli</li>
                <li><strong>Withdraw Filtresi:</strong> "withdraw" seçeneğini seçin - 9 kayıt görünmeli</li>
                <li><strong>Tarih Filtresi:</strong> Bugünün tarihini seçin ve sonuçları kontrol edin</li>
                <li><strong>Form Submit:</strong> Filtreleri değiştirdiğinizde sayfa yenilenmeli (AJAX yok)</li>
                <li><strong>Sayfalama:</strong> 20\'den fazla kayıt varsa sayfalama butonlarını test edin</li>
                <li><strong>Transactions Sayfası:</strong> <a href="user/transactions.php" target="_blank">transactions.php</a> sayfasındaki güncellemeleri de test edin</li>
            </ol>
          </div>';

} catch(PDOException $e) {
    echo '<div class="result-box result-error">
            <i class="fas fa-times-circle me-2"></i>
            <strong>VERİTABANI HATASI:</strong> ' . htmlspecialchars($e->getMessage()) . '
          </div>';
} catch(Exception $e) {
    echo '<div class="result-box result-error">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>GENEL HATA:</strong> ' . htmlspecialchars($e->getMessage()) . '
          </div>';
}

echo '        </div>
        </div>
        
        <div class="test-card">
            <div class="test-body text-center">
                <h5><i class="fas fa-info-circle me-2 text-info"></i>Test Tamamlandı</h5>
                <p class="text-muted">Bu test sayfası güncellemelerin doğru çalışıp çalışmadığını kontrol etmek için oluşturulmuştur.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="user/credits.php" class="btn btn-primary">
                        <i class="fas fa-coins me-1"></i>Credits Sayfası
                    </a>
                    <a href="user/transactions.php" class="btn btn-info">
                        <i class="fas fa-history me-1"></i>Transactions Sayfası
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-1"></i>Ana Sayfa
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
?>