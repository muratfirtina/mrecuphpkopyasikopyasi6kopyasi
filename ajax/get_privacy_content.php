<?php
/**
 * Gizlilik Politikası İçeriğini Getir
 */

require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Design settings'den gizlilik politikası içeriğini al
    $stmt = $pdo->prepare("SELECT setting_value FROM design_settings WHERE setting_key = ?");
    
    // Başlık al
    $stmt->execute(['privacy_policy_title']);
    $title = $stmt->fetchColumn();
    
    // İçerik al
    $stmt->execute(['privacy_policy_content']);
    $content = $stmt->fetchColumn();
    
    // Varsayılan değerler
    if (!$title) {
        $title = 'Gizlilik Politikası';
    }
    
    if (!$content) {
        $content = generateDefaultPrivacyContent();
    }
    
    // HTML formatını düzenle
    $formattedContent = formatLegalContent($content);
    
    echo json_encode([
        'success' => true,
        'title' => $title,
        'content' => $formattedContent
    ]);
    
} catch (Exception $e) {
    error_log('Privacy content error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'İçerik yüklenirken hata oluştu.'
    ]);
}

/**
 * Varsayılan gizlilik politikası içeriği
 */
function generateDefaultPrivacyContent() {
    return '
        <h4>1. Veri Sorumlusu</h4>
        <p>' . SITE_NAME . ' olarak, kişisel verilerinizin korunmasını önemsiyoruz.</p>
        
        <h4>2. Toplanan Veriler</h4>
        <ul>
            <li><strong>Kimlik Bilgileri:</strong> Ad, soyad, kullanıcı adı</li>
            <li><strong>İletişim Bilgileri:</strong> E-posta adresi, telefon numarası</li>
            <li><strong>Teknik Bilgiler:</strong> IP adresi, çerez bilgileri</li>
            <li><strong>İşlem Bilgileri:</strong> Dosya yükleme geçmişi, ödeme bilgileri</li>
        </ul>
        
        <h4>3. Veri Kullanım Amaçları</h4>
        <ul>
            <li>Hizmet sunumu ve müşteri desteği</li>
            <li>Hesap yönetimi ve güvenlik</li>
            <li>Yasal yükümlülüklerin yerine getirilmesi</li>
            <li>Hizmet geliştirme ve analiz</li>
        </ul>
        
        <h4>4. Veri Paylaşımı</h4>
        <p>Kişisel verileriniz, yasal zorunluluklar dışında üçüncü taraflarla paylaşılmaz.</p>
        
        <h4>5. Veri Güvenliği</h4>
        <p>Verileriniz SSL şifreleme ve güvenlik önlemleri ile korunur.</p>
        
        <h4>6. Çerezler (Cookies)</h4>
        <p>Web sitemiz, kullanıcı deneyimini iyileştirmek için çerezler kullanır.</p>
        
        <h4>7. Kullanıcı Hakları</h4>
        <p>KVKK kapsamında aşağıdaki haklarınız bulunmaktadır:</p>
        <ul>
            <li>Veri işlenip işlenmediğini öğrenme</li>
            <li>Verilerin düzeltilmesini isteme</li>
            <li>Verilerin silinmesini talep etme</li>
            <li>Veri taşınabilirliği hakkı</li>
        </ul>
        
        <h4>8. İletişim</h4>
        <p>Gizlilik ile ilgili sorularınız için: <a href="mailto:' . SITE_EMAIL . '">' . SITE_EMAIL . '</a></p>
        
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
