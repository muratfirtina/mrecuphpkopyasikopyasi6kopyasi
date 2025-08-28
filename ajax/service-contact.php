<?php
/**
 * Service Contact Form AJAX Handler
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Form verilerini al ve temizle
    $service_id = (int)($_POST['service_id'] ?? 0);
    $service_name = trim($_POST['service_name'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_company = trim($_POST['contact_company'] ?? '');
    $contact_message = trim($_POST['contact_message'] ?? '');
    
    // Validasyon
    $errors = [];
    
    if (empty($contact_name)) {
        $errors[] = 'İsim soyisim gereklidir.';
    }
    
    if (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir email adresi gereklidir.';
    }
    
    if (empty($contact_message)) {
        $errors[] = 'Mesaj gereklidir.';
    }
    
    if ($service_id <= 0) {
        $errors[] = 'Geçerli bir hizmet seçimi gereklidir.';
    }
    
    // Hizmet var mı kontrol et
    if ($service_id > 0) {
        $stmt = $pdo->prepare("SELECT name FROM services WHERE id = ? AND status = 'active'");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        
        if (!$service) {
            $errors[] = 'Seçilen hizmet bulunamadı.';
        } else {
            $service_name = $service['name'];
        }
    }
    
    if (!empty($errors)) {
        $response['message'] = implode(' ', $errors);
        echo json_encode($response);
        exit;
    }
    
    // IP ve User Agent bilgilerini al
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Veritabanına kaydet
    $stmt = $pdo->prepare("
        INSERT INTO service_contacts 
        (service_id, service_name, contact_name, contact_email, contact_phone, 
         contact_company, contact_message, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $service_id,
        $service_name,
        $contact_name,
        $contact_email,
        $contact_phone,
        $contact_company,
        $contact_message,
        $ip_address,
        $user_agent
    ]);
    
    // Email gönderimi (opsiyonel)
    try {
        $to = SITE_EMAIL;
        $subject = "Yeni Hizmet Talebi: " . $service_name;
        $message_body = "
        <html>
        <head>
            <title>Yeni Hizmet Talebi</title>
        </head>
        <body>
            <h2>Yeni Hizmet Talebi - {$service_name}</h2>
            <table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                <tr>
                    <td><strong>Hizmet:</strong></td>
                    <td>{$service_name}</td>
                </tr>
                <tr>
                    <td><strong>Ad Soyad:</strong></td>
                    <td>{$contact_name}</td>
                </tr>
                <tr>
                    <td><strong>E-posta:</strong></td>
                    <td>{$contact_email}</td>
                </tr>
                <tr>
                    <td><strong>Telefon:</strong></td>
                    <td>{$contact_phone}</td>
                </tr>
                <tr>
                    <td><strong>Firma:</strong></td>
                    <td>{$contact_company}</td>
                </tr>
                <tr>
                    <td><strong>Mesaj:</strong></td>
                    <td>" . nl2br(htmlspecialchars($contact_message)) . "</td>
                </tr>
                <tr>
                    <td><strong>Tarih:</strong></td>
                    <td>" . date('d.m.Y H:i:s') . "</td>
                </tr>
                <tr>
                    <td><strong>IP Adresi:</strong></td>
                    <td>{$ip_address}</td>
                </tr>
            </table>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Reply-To: {$contact_email}\r\n";
        
        // Email gönder (opsiyonel - hata olursa da devam et)
        @mail($to, $subject, $message_body, $headers);
        
    } catch (Exception $e) {
        // Email hatası olursa log'a yaz ama işlemi durdurma
        error_log('Service contact email error: ' . $e->getMessage());
    }
    
    $response['success'] = true;
    $response['message'] = 'Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.';
    
} catch (Exception $e) {
    $response['message'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
    error_log('Service contact form error: ' . $e->getMessage());
}

echo json_encode($response);
?>
