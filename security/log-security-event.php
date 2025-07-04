<?php
/**
 * Mr ECU - Güvenlik Event Logger
 * Frontend'den gelen güvenlik olaylarını kaydet
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once 'SecurityManager.php';

// CORS ve güvenlik headers
header('Access-Control-Allow-Origin: ' . parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('Content-Type: application/json');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// AJAX isteği kontrolü
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    // Security Manager başlat
    $security = new SecurityManager($pdo);
    
    // Rate limiting kontrolü
    if (!$security->checkRateLimit('security_log', null, 20, 60)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    // JSON verisini al
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Required fields kontrolü
    if (!isset($data['event_type']) || !isset($data['details'])) {
        throw new Exception('Missing required fields');
    }
    
    // Event type whitelist kontrolü
    $allowedEventTypes = [
        'dom_manipulation_blocked',
        'malicious_input_detected',
        'csrf_token_missing',
        'unsafe_protocol_detected',
        'cross_origin_request',
        'invalid_url_detected',
        'sensitive_info_console_log',
        'untrusted_event_detected',
        'event_handler_error',
        'sensitive_info_storage_attempt',
        'clickjacking_attempt_detected',
        'malicious_paste_detected',
        'devtools_opened',
        'unhandled_promise_rejection',
        'javascript_error'
    ];
    
    if (!in_array($data['event_type'], $allowedEventTypes)) {
        throw new Exception('Invalid event type');
    }
    
    // Verileri sanitize et
    $eventType = $security->sanitizeInput($data['event_type'], 'alphanumeric');
    $details = $security->sanitizeInput($data['details']);
    $userAgent = $security->sanitizeInput($data['user_agent'] ?? '');
    $pageUrl = $security->sanitizeInput($data['page_url'] ?? '');
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Client IP'yi al
    $clientIp = $security->getClientIp();
    
    // Veritabanına kaydet
    $stmt = $pdo->prepare("
        INSERT INTO security_logs 
        (event_type, ip_address, user_agent, request_uri, details, user_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $eventType,
        $clientIp,
        $userAgent,
        $pageUrl,
        json_encode($details),
        $_SESSION['user_id'] ?? null,
        $timestamp
    ]);
    
    if ($result) {
        // Kritik olaylar için admin bildirim gönder
        $criticalEvents = [
            'dom_manipulation_blocked',
            'malicious_input_detected',
            'clickjacking_attempt_detected',
            'malicious_paste_detected'
        ];
        
        if (in_array($eventType, $criticalEvents)) {
            // Admin email gönder (async olarak)
            $adminEmail = SITE_EMAIL;
            $subject = 'Mr ECU - Kritik Güvenlik Olayı';
            $message = "
                <h2>⚠️ Kritik Güvenlik Olayı Tespit Edildi</h2>
                <p><strong>Olay Türü:</strong> $eventType</p>
                <p><strong>IP Adresi:</strong> $clientIp</p>
                <p><strong>Sayfa:</strong> $pageUrl</p>
                <p><strong>Tarih:</strong> $timestamp</p>
                <p><strong>Detaylar:</strong></p>
                <pre>" . json_encode($details, JSON_PRETTY_PRINT) . "</pre>
                <p><strong>User Agent:</strong> $userAgent</p>
            ";
            
            // Email'i arka planda gönder
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            
            sendEmail($adminEmail, $subject, $message, true);
        }
        
        echo json_encode(['success' => true, 'message' => 'Security event logged']);
    } else {
        throw new Exception('Database insert failed');
    }
    
} catch (Exception $e) {
    error_log('Security event logging error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
