<?php
/**
 * Contact Demo Mesajları Oluşturma
 * Admin paneli demo için örnek mesajlar
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Demo mesajları
$demo_messages = [
    [
        'name' => 'Ahmet Yılmaz',
        'email' => 'ahmet.yilmaz@gmail.com',
        'phone' => '+90 (555) 123 45 67',
        'subject' => 'ECU Tuning',
        'message' => 'Merhaba,

BMW 320i 2015 model aracım için ECU tuning hizmeti almak istiyorum. Aracımın performansını artırmak ve yakıt tüketimini optimize etmek istiyorum. 

Hizmetiniz hakkında detaylı bilgi alabilir miyim? Fiyat konusunda da bilgilendirirseniz sevinirim.

Teşekkür ederim.',
        'status' => 'new',
        'created_at' => '2025-08-29 09:15:00'
    ],
    [
        'name' => 'Mehmet Kaya',
        'email' => 'mehmet.kaya@hotmail.com', 
        'phone' => '',
        'subject' => 'DPF/EGR Off',
        'message' => 'Selamlar,

2018 model Ford Transit aracım için DPF ve EGR kapatma işlemi yaptırmak istiyorum. Sürekli hata vermeye başladı ve servis maliyetleri çok yüksek.

Bu işlemi yapabilir misiniz? Ne kadar sürer ve maliyeti ne kadardır?

Saygılarımla.',
        'status' => 'read',
        'created_at' => '2025-08-28 16:30:00'
    ],
    [
        'name' => 'Fatma Demir',
        'email' => 'fatma.demir@yahoo.com',
        'phone' => '+90 (532) 987 65 43',
        'subject' => 'İmmobilizer',
        'message' => 'Merhaba,

Opel Corsa 2016 model aracımda immobilizer sorunu yaşıyorum. Araç bazen çalışmıyor, bazen de normal çalışıyor. 

Bu sorunu çözebilir misiniz? Acil durumum var, en kısa sürede geri dönüş yapabilir misiniz?

İyi günler.',
        'status' => 'replied',
        'created_at' => '2025-08-27 14:45:00'
    ],
    [
        'name' => 'Can Özkan',
        'email' => 'can.ozkan@outlook.com',
        'phone' => '+90 (505) 111 22 33', 
        'subject' => 'Genel Bilgi',
        'message' => 'Merhabalar,

Chip tuning konusunda yeniyim ve bu konu hakkında bilgi edinmek istiyorum. 

Hangi araçlara chip tuning yapılabilir? Garantiye etkisi nasıldır? Riskleri nelerdir?

Detaylı bilgilendirme yapabilir misiniz?

Teşekkürler.',
        'status' => 'new',
        'created_at' => '2025-08-26 11:20:00'
    ],
    [
        'name' => 'Zeynep Arslan',
        'email' => 'zeynep.arslan@gmail.com',
        'phone' => '+90 (543) 666 77 88',
        'subject' => 'Teknik Destek', 
        'message' => 'İyi günler,

Daha önce sizden hizmet aldım ve çok memnun kaldım. Ancak şimdi küçük bir sorun yaşıyorum.

Tuning sonrası ara ara motor ışığı yanıyor. Normal midir yoksa bir sorun mu var?

En kısa sürede bilgi verirseniz sevinirim.

Saygılar.',
        'status' => 'archived',
        'created_at' => '2025-08-25 19:10:00'
    ]
];

try {
    // Önce mevcut demo mesajlarını temizle
    $pdo->exec("DELETE FROM contact_messages WHERE email LIKE '%@gmail.com' OR email LIKE '%@hotmail.com' OR email LIKE '%@yahoo.com' OR email LIKE '%@outlook.com'");
    
    // Demo mesajları ekle
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, status, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($demo_messages as $msg) {
        $stmt->execute([
            $msg['name'],
            $msg['email'],
            $msg['phone'],
            $msg['subject'],
            $msg['message'],
            $msg['status'],
            '127.0.0.1', // Demo IP
            'Mozilla/5.0 (Demo Message)', // Demo user agent
            $msg['created_at']
        ]);
    }
    
    echo "<div style='text-align:center; margin-top:50px; font-family:Arial;'>";
    echo "<h2>✅ Demo Mesajları Başarıyla Eklendi!</h2>";
    echo "<p>" . count($demo_messages) . " adet örnek mesaj contact_messages tablosuna eklendi.</p>";
    echo "<div style='margin: 30px 0;'>";
    echo "<a href='admin/contact-messages.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>📧 Admin Paneli</a>";
    echo "<a href='design/contact.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ İçerik Yönetimi</a>";
    echo "<a href='contact.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👁️ İletişim Sayfası</a>";
    echo "</div>";
    echo "<p><strong>Admin Paneli Şifresi:</strong> admin123</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; text-align: center; margin-top: 50px;'>";
    echo "<h2>❌ Hata!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>