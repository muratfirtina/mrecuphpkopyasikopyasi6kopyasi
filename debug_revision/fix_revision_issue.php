<?php
/**
 * Revize Sorunu Düzeltme - FileManager.php güncelleme
 * Bu dosya FileManager.php'deki requestResponseRevision metodunu düzeltir
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

echo "<h2>Revize Sorunu Düzeltme</h2>";
echo "<p>Bu işlem FileManager.php dosyasındaki requestResponseRevision metodunu düzeltecek.</p>";
echo "<hr>";

// Orijinal FileManager.php'yi yedekle
$originalFile = '../includes/FileManager.php';
$backupFile = '../includes/FileManager.php.backup_' . date('Y-m-d_H-i-s');

if (file_exists($originalFile)) {
    copy($originalFile, $backupFile);
    echo "<p>✓ Orijinal dosya yedeklendi: " . basename($backupFile) . "</p>";
} else {
    echo "<p>✗ Orijinal dosya bulunamadı!</p>";
    exit;
}

// Yeni requestResponseRevision metodunu oluştur
$newMethod = '    // Yanıt dosyası için revize talebi gönder - DÜZELTILMIŞ VERSİYON
    public function requestResponseRevision($responseId, $userId, $revisionNotes) {
        try {
            if (!isValidUUID($responseId) || !isValidUUID($userId)) {
                return [\'success\' => false, \'message\' => \'Geçersiz ID formatı.\'];
            }
            
            // Yanıt dosyası kontrolü - DÜZELTILMIŞ SORGU
            $stmt = $this->pdo->prepare("
                SELECT fr.*, fu.user_id, fu.original_name as upload_original_name,
                       a.username as admin_username
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                LEFT JOIN users a ON fr.admin_id = a.id
                WHERE fr.id = ?
            ");
            $stmt->execute([$responseId]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$response) {
                return [\'success\' => false, \'message\' => \'Yanıt dosyası bulunamadı.\'];
            }
            
            // Kullanıcı yetki kontrolü
            if ($response[\'user_id\'] !== $userId) {
                return [\'success\' => false, \'message\' => \'Bu yanıt dosyasına erişim yetkiniz yok.\'];
            }
            
            // Daha önce bekleyen revize talebi var mı kontrol et
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM revisions 
                WHERE response_id = ? AND status = \'pending\'
            ");
            $stmt->execute([$responseId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing[\'count\'] > 0) {
                return [\'success\' => false, \'message\' => \'Bu yanıt dosyası için zaten bekleyen bir revize talebi bulunuyor.\'];
            }
            
            // Revize talebi oluştur
            $revisionId = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO revisions (
                    id, response_id, upload_id, user_id, request_notes, status, requested_at
                ) VALUES (?, ?, ?, ?, ?, \'pending\', NOW())
            ");
            
            $result = $stmt->execute([$revisionId, $responseId, $response[\'upload_id\'], $userId, $revisionNotes]);
            
            if ($result) {
                return [\'success\' => true, \'message\' => \'Yanıt dosyası için revize talebi başarıyla gönderildi. Admin ekibimiz dosyanızı yeniden değerlendirecektir.\'];
            } else {
                return [\'success\' => false, \'message\' => \'Revize talebi oluşturulurken hata oluştu.\'];
            }
            
        } catch(PDOException $e) {
            error_log(\'requestResponseRevision error: \' . $e->getMessage());
            return [\'success\' => false, \'message\' => \'Veritabanı hatası oluştu.\'];
        }
    }';

// FileManager.php içeriğini oku
$content = file_get_contents($originalFile);

// Eski requestResponseRevision metodunu bul ve değiştir
$pattern = '/\/\/ Yanıt dosyası için revize talebi gönder.*?public function requestResponseRevision.*?}\s*}/s';

if (preg_match($pattern, $content)) {
    $newContent = preg_replace($pattern, $newMethod, $content);
    
    // Dosyayı güncelle
    if (file_put_contents($originalFile, $newContent)) {
        echo "<p>✓ FileManager.php başarıyla güncellendi!</p>";
        echo "<p>✓ requestResponseRevision metodu düzeltildi</p>";
    } else {
        echo "<p>✗ Dosya güncellenirken hata oluştu!</p>";
    }
} else {
    echo "<p>✗ Metod bulunamadı! Manuel olarak düzeltmek gerekebilir.</p>";
}

echo "<hr>";
echo "<h3>Yapılan Değişiklikler:</h3>";
echo "<ol>";
echo "<li>Yanıt dosyası kontrolü sadece responseId ile yapılıyor</li>";
echo "<li>Kullanıcı yetki kontrolü ayrı bir step olarak yapılıyor</li>";
echo "<li>Daha anlaşılır hata mesajları eklendi</li>";
echo "<li>JOIN sorgusu basitleştirildi</li>";
echo "</ol>";

echo "<p><a href='../user/file-detail.php?id=20b37e6d-7aaa-4be4-b5f5-b4b1d2d9fcdc'>Dosya Detay Sayfasını Test Et</a></p>";
echo "<p><a href='debug_revision_issue.php'>Debug Sayfasını Tekrar Kontrol Et</a></p>";
?>