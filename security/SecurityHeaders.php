<?php
/**
 * Mr ECU - Güvenlik Header Helper
 * Güvenlik başlıklarını ayarlama yardımcısı
 */

class SecurityHeaders {
    private static $cspNonce = null;
    
    /**
     * Tüm güvenlik başlıklarını ayarla
     */
    public static function setAllHeaders($strictMode = true) {
        // XSS Koruması
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Type Sniffing koruması
        header('X-Content-Type-Options: nosniff');
        
        // Clickjacking koruması
        header('X-Frame-Options: DENY');
        
        // HSTS (HTTPS zorunluluğu)
        if (self::isHTTPS()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Feature Policy / Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        
        // Content Security Policy
        self::setCSP($strictMode);
        
        // Expect-CT (Certificate Transparency)
        if (self::isHTTPS()) {
            header('Expect-CT: max-age=86400, enforce');
        }
        
        // Server bilgisini gizle
        header('Server: MrECU/1.0');
        
        // Powered-by bilgisini gizle
        header_remove('X-Powered-By');
    }
    
    /**
     * Content Security Policy ayarla
     */
    public static function setCSP($strictMode = true) {
        $nonce = self::generateNonce();
        
        if ($strictMode) {
            // Sıkı CSP politikası
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'nonce-$nonce' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "style-src 'self' 'nonce-$nonce' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "connect-src 'self'; " .
                   "media-src 'self'; " .
                   "object-src 'none'; " .
                   "frame-src 'none'; " .
                   "base-uri 'self'; " .
                   "form-action 'self'";
        } else {
            // Gevşek CSP politikası (geliştirme için)
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                   "connect-src 'self'; " .
                   "media-src 'self'; " .
                   "object-src 'none'";
        }
        
        header("Content-Security-Policy: $csp");
    }
    
    /**
     * CSP Nonce oluştur
     */
    public static function generateNonce() {
        if (self::$cspNonce === null) {
            self::$cspNonce = base64_encode(random_bytes(16));
        }
        return self::$cspNonce;
    }
    
    /**
     * CSP Nonce'u al
     */
    public static function getNonce() {
        return self::$cspNonce ?: self::generateNonce();
    }
    
    /**
     * HTTPS kontrolü
     */
    private static function isHTTPS() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    /**
     * API için CORS başlıkları
     */
    public static function setCORSHeaders($allowedOrigins = null, $allowedMethods = ['GET', 'POST'], $allowedHeaders = ['Content-Type', 'Authorization']) {
        // Allowed origins
        if ($allowedOrigins === null) {
            $allowedOrigins = [parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST)];
        }
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        // Allowed methods
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        
        // Allowed headers
        header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
        
        // Credentials
        header('Access-Control-Allow-Credentials: true');
        
        // Max age
        header('Access-Control-Max-Age: 3600');
    }
    
    /**
     * Dosya indirme için güvenlik başlıkları
     */
    public static function setFileDownloadHeaders($filename, $contentType = 'application/octet-stream') {
        // Dosya adını güvenli hale getir
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Güvenlik başlıkları
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
    
    /**
     * JSON API için güvenlik başlıkları
     */
    public static function setJSONHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Admin sayfaları için ekstra güvenlik başlıkları
     */
    public static function setAdminHeaders() {
        self::setAllHeaders(true);
        
        // Admin sayfalar için ekstra başlıklar
        header('Cache-Control: no-cache, no-store, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Admin CSP daha sıkı
        $nonce = self::generateNonce();
        $csp = "default-src 'self'; " .
               "script-src 'self' 'nonce-$nonce'; " .
               "style-src 'self' 'nonce-$nonce'; " .
               "img-src 'self' data:; " .
               "font-src 'self'; " .
               "connect-src 'self'; " .
               "media-src 'none'; " .
               "object-src 'none'; " .
               "frame-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'";
        
        header("Content-Security-Policy: $csp");
    }
}

/**
 * Meta tag helper fonksiyonları
 */
function securityMetaTags() {
    $nonce = SecurityHeaders::getNonce();
    echo "<meta name=\"csrf-token\" content=\"" . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '') . "\">\n";
    echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
    echo "<meta name=\"robots\" content=\"noindex, nofollow\">\n";
    return $nonce;
}

function securityScriptTag($src = null, $content = null) {
    $nonce = SecurityHeaders::getNonce();
    
    if ($src) {
        echo "<script nonce=\"$nonce\" src=\"$src\"></script>\n";
    } elseif ($content) {
        echo "<script nonce=\"$nonce\">$content</script>\n";
    }
}

function securityStyleTag($href = null, $content = null) {
    $nonce = SecurityHeaders::getNonce();
    
    if ($href) {
        echo "<link nonce=\"$nonce\" rel=\"stylesheet\" href=\"$href\">\n";
    } elseif ($content) {
        echo "<style nonce=\"$nonce\">$content</style>\n";
    }
}
?>
