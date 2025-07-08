<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    die('Lütfen giriş yapın');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Dashboard Stats Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>📊 Dashboard İstatistik Debug</h1>";

$user = new User($pdo);
$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

echo "<div class='section'>";
echo "<h2>1. User ID Kontrolü</h2>";
echo "<div>Session User ID: <strong>$userId</strong></div>";
echo "<div>isValidUUID: " . (isValidUUID($userId) ? 'true' : 'false') . "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Index.php'deki İstatistik Sorguları</h2>";

try {
    // İndex.php'deki aynı sorguları çalıştır
    
    // Toplam dosya sayısı (index.php'deki kod)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalUploads = $stmt->fetchColumn();
    echo "<div class='success'>✅ Toplam dosya sayısı: $totalUploads</div>";
    
    // Durum bazında dosya sayıları
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM file_uploads WHERE user_id = ? GROUP BY status");
    $stmt->execute([$userId]);
    $statusCounts = [];
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['count'];
    }
    
    $pendingUploads = $statusCounts['pending'] ?? 0;
    $processingUploads = $statusCounts['processing'] ?? 0;
    $completedUploads = $statusCounts['completed'] ?? 0;
    $rejectedUploads = $statusCounts['rejected'] ?? 0;
    
    echo "<div>Pending: $pendingUploads</div>";
    echo "<div>Processing: $processingUploads</div>";
    echo "<div>Completed: $completedUploads</div>";
    echo "<div>Rejected: $rejectedUploads</div>";
    
    // Bu ayki istatistikler
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND MONTH(upload_date) = MONTH(CURRENT_DATE()) AND YEAR(upload_date) = YEAR(CURRENT_DATE())");
    $stmt->execute([$userId]);
    $monthlyUploads = $stmt->fetchColumn();
    echo "<div class='warning'>Bu ayki yükleme: $monthlyUploads</div>";
    
    // Bu ayki harcama
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM credit_transactions WHERE user_id = ? AND transaction_type = 'deduct' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute([$userId]);
    $monthlySpent = $stmt->fetchColumn() ?: 0;
    echo "<div>Bu ayki harcama: $monthlySpent TL</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ İstatistik hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>3. FileManager ile Karşılaştırma</h2>";
try {
    $userUploads = $fileManager->getUserUploads($userId, 1, 10);
    echo "<div class='success'>✅ FileManager getUserUploads: " . count($userUploads) . " dosya</div>";
    
    $userStats = $fileManager->getUserFileStats($userId);
    echo "<div>FileManager istatistik:</div>";
    echo "<div>- Total: {$userStats['total']}</div>";
    echo "<div>- Pending: {$userStats['pending']}</div>";
    echo "<div>- Processing: {$userStats['processing']}</div>";
    echo "<div>- Completed: {$userStats['completed']}</div>";
    echo "<div>- Rejected: {$userStats['rejected']}</div>";
    
} catch(Exception $e) {
    echo "<div class='error'>❌ FileManager hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Manuel Dosya Listesi</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, original_name, status, upload_date, user_id FROM file_uploads ORDER BY upload_date DESC");
    $stmt->execute();
    $allFiles = $stmt->fetchAll();
    
    echo "<div>Toplam dosya sayısı (tüm kullanıcılar): " . count($allFiles) . "</div>";
    
    echo "<h3>Tüm dosyalar:</h3>";
    echo "<table>";
    echo "<tr><th>File ID</th><th>User ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th><th>Eşleşme</th></tr>";
    
    foreach ($allFiles as $file) {
        $match = $file['user_id'] === $userId ? '✅' : '❌';
        $userIdShort = substr($file['user_id'], 0, 8) . '...';
        $fileIdShort = substr($file['id'], 0, 8) . '...';
        
        echo "<tr>";
        echo "<td>$fileIdShort</td>";
        echo "<td>$userIdShort</td>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['status']}</td>";
        echo "<td>{$file['upload_date']}</td>";
        echo "<td>$match</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(Exception $e) {
    echo "<div class='error'>❌ Manuel liste hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>5. Credit Transactions Debug</h2>";
try {
    // Credit transactions tablosu var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'credit_transactions'");
    $exists = $stmt->fetch() !== false;
    
    if ($exists) {
        echo "<div class='success'>✅ credit_transactions tablosu mevcut</div>";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $transactionCount = $stmt->fetchColumn();
        echo "<div>Bu kullanıcının işlem sayısı: $transactionCount</div>";
        
    } else {
        echo "<div class='error'>❌ credit_transactions tablosu mevcut değil</div>";
    }
    
} catch(Exception $e) {
    echo "<div class='error'>❌ Credit transactions kontrol hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>6. User Credits Debug</h2>";
try {
    $userCredits = $user->getUserCredits($userId);
    echo "<div class='success'>✅ User credits: $userCredits TL</div>";
    
} catch(Exception $e) {
    echo "<div class='error'>❌ User credits hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "</body></html>";
?>
