<?php
/**
 * Mr ECU - Güvenlik Yöneticisi
 * SQL Injection, XSS, CSRF, DOM Manipülasyonu ve diğer güvenlik tehditlerine karşı koruma
 */

class SecurityManager {
    private $pdo;
    private $whitelist_patterns = [];
    private $blacklist_patterns = [];
    
    public function __construct($database = null) {
        $this->pdo = $database;
        $this->initializePatterns();
        $this->setSecurityHeaders();
    }
    
    /**
     * Güvenlik başlıklarını ayarla
     */
    private function setSecurityHeaders() {
        // XSS Koruması
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Type Sniffing koruması
        header('X-Content-Type-Options: nosniff');
        
        // Clickjacking koruması
        header('X-Frame-Options: DENY');
        
        // HSTS (HTTPS zorunluluğu)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature Policy / Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // Content Security Policy (CSP)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "connect-src 'self'; " .
               "media-src 'self'; " .
               "object-src 'none'; " .
               "frame-src 'none'";
        header("Content-Security-Policy: $csp");
    }
    
    /**
     * Tehditli pattern'leri başlat
     */
    private function initializePatterns() {
        // SQL Injection pattern'leri
        $this->blacklist_patterns['sql'] = [
            '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute|sp_|xp_)/i',
            '/(\s|^)(or|and)\s+(1=1|true|false|\d+\s*=\s*\d+)/i',
            '/(\s|^)(or|and)\s+.+\s*(=|like|in|exists)/i',
            '/(\'|\"|`).*(\'|\"|`)\s*(or|and|union)/i',
            '/(\s|^)(declare|cast|convert|char|varchar|nvarchar|concat)/i',
            '/(\s|^)(waitfor|delay|benchmark|sleep)/i',
            '/(\s|^)(information_schema|sys\.|mysql\.)/i',
            '/(\s|^)(load_file|into\s+outfile|into\s+dumpfile)/i',
            '/(\s|^)(\|\||\&\&|\/\*|\*\/)/i',
            '/(\s|^)(0x[0-9a-f]+|unhex|hex)/i'
        ];
        
        // XSS pattern'leri
        $this->blacklist_patterns['xss'] = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/<applet[^>]*>.*?<\/applet>/is',
            '/<meta[^>]*>/is',
            '/<link[^>]*>/is',
            '/<form[^>]*>/is',
            '/on\w+\s*=\s*["\'][^"\']*["\'][^>]*/is',
            '/javascript\s*:\s*[^"\';]*/is',
            '/vbscript\s*:\s*[^"\';]*/is',
            '/data\s*:\s*text\/html/is',
            '/expression\s*\(/is',
            '/eval\s*\(/is',
            '/setTimeout\s*\(/is',
            '/setInterval\s*\(/is'
        ];
        
        // DOM Manipülasyon pattern'leri
        $this->blacklist_patterns['dom'] = [
            '/document\.(write|writeln|createElement|getElementById|getElementsBy|querySelector)/is',
            '/window\.(location|open|close|alert|confirm|prompt)/is',
            '/innerHTML\s*=/is',
            '/outerHTML\s*=/is',
            '/insertAdjacentHTML/is',
            '/appendChild|insertBefore|replaceChild/is',
            '/setAttribute\s*\([^)]*on\w+/is',
            '/addEventListener|attachEvent/is'
        ];
        
        // Path Traversal pattern'leri
        $this->blacklist_patterns['path'] = [
            '/\.\.(\/|\\\\)/i',
            '/\.(\/|\\\\)\./i',
            '/(\/|\\\\)\.\.(\/|\\\\)/i',
            '/\0/i',
            '/%00/i',
            '/%2e%2e/i',
            '/%252e%252e/i'
        ];
    }
    
    /**
     * Genel input sanitization (SQL Injection + XSS koruması)
     */
    public function sanitizeInput($input, $type = 'general') {
        if (is_array($input)) {
            return array_map(function($value) use ($type) {
                return $this->sanitizeInput($value, $type);
            }, $input);
        }
        
        if (!is_string($input)) {
            return $input;
        }
        
        // Null byte koruması
        $input = str_replace(["\0", "%00"], '', $input);
        
        switch ($type) {
            case 'email':
                return $this->sanitizeEmail($input);
            case 'phone':
                return $this->sanitizePhone($input);
            case 'numeric':
                return $this->sanitizeNumeric($input);
            case 'alphanumeric':
                return $this->sanitizeAlphanumeric($input);
            case 'filename':
                return $this->sanitizeFilename($input);
            case 'url':
                return $this->sanitizeUrl($input);
            case 'html':
                return $this->sanitizeHtml($input);
            case 'sql':
                return $this->sanitizeSql($input);
            default:
                return $this->sanitizeGeneral($input);
        }
    }
    
    /**
     * Genel güvenlik temizleme
     */
    private function sanitizeGeneral($input) {
        // Trim ve basic sanitization
        $input = trim($input);
        
        // SQL Injection koruması
        $input = $this->detectAndCleanSqlInjection($input);
        
        // XSS koruması
        $input = $this->detectAndCleanXss($input);
        
        // DOM Manipülasyon koruması
        $input = $this->detectAndCleanDomManipulation($input);
        
        return $input;
    }
    
    /**
     * SQL Injection tespiti ve temizleme
     */
    public function detectAndCleanSqlInjection($input) {
        if (!is_string($input)) return $input;
        
        foreach ($this->blacklist_patterns['sql'] as $pattern) {
            if (preg_match($pattern, $input)) {
                // Log kaydet
                $this->logSecurityEvent('sql_injection_attempt', $input, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                
                // Tehlikeli kısmı temizle
                $input = preg_replace($pattern, ' [FILTERED] ', $input);
            }
        }
        
        return $input;
    }
    
    /**
     * XSS tespiti ve temizleme
     */
    public function detectAndCleanXss($input) {
        if (!is_string($input)) return $input;
        
        foreach ($this->blacklist_patterns['xss'] as $pattern) {
            if (preg_match($pattern, $input)) {
                // Log kaydet
                $this->logSecurityEvent('xss_attempt', $input, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                
                // Tehlikeli kısmı temizle
                $input = preg_replace($pattern, '[XSS_FILTERED]', $input);
            }
        }
        
        // HTML özel karakterleri encode et
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * DOM Manipülasyon tespiti ve temizleme
     */
    public function detectAndCleanDomManipulation($input) {
        if (!is_string($input)) return $input;
        
        foreach ($this->blacklist_patterns['dom'] as $pattern) {
            if (preg_match($pattern, $input)) {
                // Log kaydet
                $this->logSecurityEvent('dom_manipulation_attempt', $input, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                
                // Tehlikeli kısmı temizle
                $input = preg_replace($pattern, '[DOM_FILTERED]', $input);
            }
        }
        
        return $input;
    }
    
    /**
     * Email sanitization
     */
    private function sanitizeEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }
    
    /**
     * Telefon numarası sanitization
     */
    private function sanitizePhone($phone) {
        return preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
    }
    
    /**
     * Numerik değer sanitization
     */
    private function sanitizeNumeric($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Alfanumerik sanitization
     */
    private function sanitizeAlphanumeric($input) {
        return preg_replace('/[^a-zA-Z0-9]/', '', $input);
    }
    
    /**
     * Dosya adı sanitization
     */
    private function sanitizeFilename($filename) {
        // Path traversal koruması
        foreach ($this->blacklist_patterns['path'] as $pattern) {
            if (preg_match($pattern, $filename)) {
                $this->logSecurityEvent('path_traversal_attempt', $filename, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
                $filename = preg_replace($pattern, '', $filename);
            }
        }
        
        // Güvenli karakterleri koruyarak temizle
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/[._-]{2,}/', '_', $filename);
        
        return $filename;
    }
    
    /**
     * URL sanitization
     */
    private function sanitizeUrl($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    
    /**
     * HTML içerik sanitization (güvenli HTML'e izin ver)
     */
    private function sanitizeHtml($html) {
        // İzin verilen HTML tagları
        $allowed_tags = '<p><br><strong><em><u><span><div><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img>';
        
        // HTML'i temizle
        $html = strip_tags($html, $allowed_tags);
        
        // Güvenli olmayan attribute'ları temizle
        $html = preg_replace('/\s(on\w+|javascript:|vbscript:|data:)\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        return $html;
    }
    
    /**
     * SQL için özel sanitization (PDO prepared statements ile birlikte kullanılır)
     */
    private function sanitizeSql($input) {
        // Basit SQL karakter temizleme (PDO placeholder ile birlikte ekstra güvenlik)
        $input = str_replace(['--', '/*', '*/', ';'], '', $input);
        return $input;
    }
    
    /**
     * CSRF Token oluştur
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time();
        
        // Eski token'ları temizle (1 saat eski)
        foreach ($_SESSION['csrf_tokens'] as $t => $timestamp) {
            if (time() - $timestamp > 3600) {
                unset($_SESSION['csrf_tokens'][$t]);
            }
        }
        
        return $token;
    }
    
    /**
     * CSRF Token doğrula
     */
    public function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            $this->logSecurityEvent('csrf_token_invalid', $token, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            return false;
        }
        
        // Token'ı kullan ve sil (tek kullanımlık)
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }
    
    /**
     * Rate Limiting kontrol
     */
    public function checkRateLimit($action, $identifier = null, $limit = 10, $window = 300) {
        if (!$identifier) {
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $cache_key = "rate_limit_{$action}_{$identifier}";
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = [];
        }
        
        $now = time();
        $requests = &$_SESSION[$cache_key];
        
        // Eski kayıtları temizle
        $requests = array_filter($requests, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($requests) >= $limit) {
            $this->logSecurityEvent('rate_limit_exceeded', $action, $identifier);
            return false;
        }
        
        $requests[] = $now;
        return true;
    }
    
    /**
     * Dosya yükleme güvenlik kontrolü
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
        $errors = [];
        
        // Dosya var mı kontrol
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Geçersiz dosya yükleme.';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Dosya boyutu kontrol
        $maxSize = $maxSize ?: MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $errors[] = 'Dosya boyutu çok büyük. Maksimum ' . ($maxSize / 1024 / 1024) . 'MB olabilir.';
        }
        
        // MIME type kontrol
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Dosya uzantısı kontrol
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = $allowedTypes ?: ALLOWED_EXTENSIONS;
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Desteklenmeyen dosya formatı. İzin verilen formatlar: ' . implode(', ', $allowedTypes);
        }
        
        // İçerik kontrolü (binary dosya olmalı)
        $fileContent = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if ($this->containsHtmlOrScript($fileContent)) {
            $errors[] = 'Dosya içeriği güvenlik kontrolünden geçemedi.';
            $this->logSecurityEvent('malicious_file_upload', $file['name'], $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        
        // Dosya adı güvenlik kontrolü
        $safeName = $this->sanitizeFilename($file['name']);
        if ($safeName !== $file['name']) {
            $this->logSecurityEvent('unsafe_filename', $file['name'], $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'safe_name' => $safeName,
            'mime_type' => $mimeType
        ];
    }
    
    /**
     * Dosya içeriğinde HTML/Script kontrolü
     */
    private function containsHtmlOrScript($content) {
        $patterns = [
            '/<\s*script[^>]*>/i',
            '/<\s*html[^>]*>/i',
            '/<\s*body[^>]*>/i',
            '/<\s*iframe[^>]*>/i',
            '/<\s*object[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Güvenlik olaylarını logla
     */
    public function logSecurityEvent($event_type, $details, $ip_address) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'ip_address' => $ip_address,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'details' => $details
        ];
        
        // Database'e kaydet
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO security_logs 
                    (event_type, ip_address, user_agent, request_uri, details, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $event_type,
                    $ip_address,
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $_SERVER['REQUEST_URI'] ?? '',
                    json_encode($details),
                    $_SESSION['user_id'] ?? null
                ]);
            } catch (PDOException $e) {
                // Log hatası için dosyaya yaz
                error_log("Security log database error: " . $e->getMessage());
            }
        }
        
        // Dosyaya da kaydet
        $log_file = __DIR__ . '/../logs/security.log';
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * IP Adresini güvenli al
     */
    public function getClientIp() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // IP validasyonu
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Brute force koruması
     */
    public function checkBruteForce($identifier, $maxAttempts = 5, $timeWindow = 900) {
        $cache_key = "brute_force_{$identifier}";
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = [];
        }
        
        $now = time();
        $attempts = &$_SESSION[$cache_key];
        
        // Eski kayıtları temizle
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $maxAttempts) {
            $this->logSecurityEvent('brute_force_detected', $identifier, $this->getClientIp());
            return false;
        }
        
        return true;
    }
    
    /**
     * Brute force denemesi kaydet
     */
    public function recordBruteForceAttempt($identifier) {
        $cache_key = "brute_force_{$identifier}";
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = [];
        }
        
        $_SESSION[$cache_key][] = time();
    }
    
    /**
     * SQL Injection için prepared statement wrapper
     */
    public function executeSafeQuery($query, $params = []) {
        try {
            // Query'yi kontrol et
            if ($this->containsSqlInjection($query)) {
                $this->logSecurityEvent('unsafe_query_detected', $query, $this->getClientIp());
                throw new Exception('Güvenlik hatası: Unsafe query detected');
            }
            
            $stmt = $this->pdo->prepare($query);
            
            // Parametreleri sanitize et
            $safe_params = [];
            foreach ($params as $key => $value) {
                $safe_params[$key] = $this->sanitizeInput($value, 'sql');
            }
            
            $result = $stmt->execute($safe_params);
            
            if (!$result) {
                throw new PDOException('Query execution failed');
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logSecurityEvent('database_error', $e->getMessage(), $this->getClientIp());
            throw $e;
        }
    }
    
    /**
     * Query'de SQL injection var mı kontrol
     */
    private function containsSqlInjection($query) {
        $dangerous_patterns = [
            '/;\s*(drop|delete|update|insert|create|alter)\s+/i',
            '/union\s+select/i',
            '/exec\s*\(/i',
            '/sp_\w+/i',
            '/xp_\w+/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Session güvenliği
     */
    public function secureSession() {
        // Session ayarları sadece session aktif değilse
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
        }
        
        // Session hijacking koruması
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->logSecurityEvent('session_hijack_attempt', 'User agent mismatch', $this->getClientIp());
            session_destroy();
            session_start();
        }
        
        // Session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
        
        // Session ID regeneration
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 dakika
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}
?>
