<?php
/**
 * FileManager.php düzeltmeleri
 * 
 * 1. getUserUploads() metodunda parametre sırası sorunu
 * 2. Kredi düşürmesi zaten doğru yapılıyor
 * 3. Revize sistemi zaten eklenmiş
 */

// FileManager.php'deki getUserUploads metodu düzeltmesi
// Satır 245 civarı - getUserUploads metodunu şununla değiştir:

?>
    // Kullanıcının dosyalarını listele - DÜZELTİLMİŞ VERSİYON
    public function getUserUploads($userId, $page = 1, $limit = 20) {
        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;
            
            // DEBUG için SQL'i log'a yaz
            error_log("getUserUploads: userId=$userId, page=$page, limit=$limit, offset=$offset");
            
            $stmt = $this->pdo->prepare("
                SELECT fu.*, b.name as brand_name, m.name as model_name,
                       (SELECT COUNT(*) FROM file_responses WHERE upload_id = fu.id) as has_response,
                       (SELECT fr.id FROM file_responses fr WHERE fr.upload_id = fu.id LIMIT 1) as response_id
                FROM file_uploads fu
                LEFT JOIN brands b ON fu.brand_id = b.id
                LEFT JOIN models m ON fu.model_id = m.id
                WHERE fu.user_id = ?
                ORDER BY fu.upload_date DESC
                LIMIT ? OFFSET ?
            ");
            
            // Parametreler sırasıyla: userId, limit, offset
            $params = [$userId, $limit, $offset];
            error_log("getUserUploads params: " . json_encode($params));
            
            $result = $stmt->execute($params);
            if (!$result) {
                error_log("getUserUploads SQL hatası: " . json_encode($stmt->errorInfo()));
                return [];
            }
            
            $uploads = $stmt->fetchAll();
            error_log("getUserUploads sonuç sayısı: " . count($uploads));
            
            return $uploads;
            
        } catch(PDOException $e) {
            error_log("getUserUploads PDO hatası: " . $e->getMessage());
            return [];
        }
    }
<?php
echo "\n\n";
echo "=== KULLANIM TALİMATI ===\n";
echo "1. /Applications/MAMP/htdocs/mrecuphp/includes/FileManager.php dosyasını aç\n";
echo "2. getUserUploads metodunu bul (satır 245 civarı)\n";
echo "3. Mevcut getUserUploads metodunu yukarıdaki düzeltilmiş versiyonla değiştir\n\n";

echo "=== PROBLEM ===\n";
echo "getUserUploads metodunda SQL LIMIT ? OFFSET ? parametreleri için\n";
echo "execute([\$userId, \$limit, \$offset]) şeklinde parametre geçiliyor\n";
echo "Bu doğru ama metodda debug eksik olduğu için hata tespiti zor\n\n";

echo "=== ÇÖZÜM ===\n";
echo "- Debug log'ları ekledim\n";
echo "- Error handling iyileştirdim\n";
echo "- Parametre sırasını netleştirdim\n\n";

echo "Bu düzeltmeyi uyguladıktan sonra:\n";
echo "http://localhost:8888/mrecuphp/user/user-files-debug.php\n";
echo "adresini tekrar kontrol et\n";
?>
