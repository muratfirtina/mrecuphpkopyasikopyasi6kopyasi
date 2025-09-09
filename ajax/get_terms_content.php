<?php
/**
 * Kullanım Şartları İçeriğini Getir
 */

require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Design settings'den kullanım şartları içeriğini al
    $stmt = $pdo->prepare("SELECT setting_value FROM design_settings WHERE setting_key = ?");
    
    // Başlık al
    $stmt->execute(['terms_of_service_title']);
    $title = $stmt->fetchColumn();
    
    // İçerik al
    $stmt->execute(['terms_of_service_content']);
    $content = $stmt->fetchColumn();
    
    // Varsayılan değerler
    if (!$title) {
        $title = 'Kullanım Şartları';
    }
    
    if (!$content) {
        $content = generateDefaultTermsContent();
    }
    
    // HTML formatını düzenle
    $formattedContent = formatLegalContent($content);
    
    echo json_encode([
        'success' => true,
        'title' => $title,
        'content' => $formattedContent
    ]);
    
} catch (Exception $e) {
    error_log('Terms content error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İçerik yüklenirken hata oluştu.'
    ]);
}

/**
 * Varsayılan kullanım şartları içeriği
 */
function generateDefaultTermsContent() {
    return '
        <h4>1. Genel Hükümler</h4>
        <p>Bu kullanım şartları, ' . SITE_NAME . ' platformunu kullanırken uymanız gereken kuralları belirler.</p>
        
        <h4>2. Hizmet Tanımı</h4>
        <p>Platformumuz, araç ECU dosyalarının optimizasyonu ve tuning hizmetleri sunmaktadır.</p>
        
        <h4>3. Kullanıcı Yükümlülükleri</h4>
        <ul>
            <li>Doğru ve güncel bilgiler sağlamalısınız</li>
            <li>Hesabınızın güvenliğinden sorumlusunuz</li>
            <li>Yasalara uygun kullanım yapmalısınız</li>
            <li>Telif haklarına saygı göstermelisiniz</li>
        </ul>
        
        <h4>4. Hizmet Bedeli ve Ödeme</h4>
        <p>Hizmetlerimiz ücretlidir. Ödeme koşulları ve fiyatlandırma politikamız web sitesinde belirtilmiştir.</p>
        
        <h4>5. Gizlilik</h4>
        <p>Kişisel verileriniz, gizlilik politikamız çerçevesinde işlenir ve korunur.</p>
        
        <h4>6. Sorumluluk Reddi</h4>
        <p>Platformumuz, hizmet kesintileri veya veri kayıplarından sorumlu değildir.</p>
        
        <h4>7. Değişiklikler</h4>
        <p>Bu şartlar, önceden bildirim yapılarak değiştirilebilir.</p>
        
        <h4>8. İletişim</h4>
        <p>Sorularınız için: <a href="mailto:' . SITE_EMAIL . '">' . SITE_EMAIL . '</a></p>
        
        <p class="mt-4"><small><em>Son güncelleme: ' . date('d.m.Y') . '</em></small></p>
    ';
}

/**
 * Yasal içerik formatlaması
 */
function formatLegalContent($content) {
    // Temel HTML temizliği ve formatlaması
    $content = trim($content);
    
    // Paragrafları formatla
    if (strpos($content, '<') === false) {
        // Sadece text ise paragraflara böl
        $content = '<p>' . str_replace("\n\n", '</p><p>', $content) . '</p>';
    }
    
    return $content;
}
?>
