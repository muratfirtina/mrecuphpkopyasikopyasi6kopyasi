<?php
/**
 * Mr ECU - Production Security Headers
 * Comprehensive security headers for production environment
 * 
 * OWASP Security Headers Best Practices Implementation
 */

class ProductionSecurityHeaders {
    private static $nonce = null;
    
    /**
     * Set all security headers for production
     */
    public static function setAllHeaders($strict = true) {
        // Only set headers if not already sent
        if (headers_sent()) {
            return;
        }
        
        // Generate CSP nonce
        self::$nonce = base64_encode(random_bytes(16));
        
        // 1. Content Security Policy (CSP)
        self::setCSPHeaders($strict);
        
        // 2. XSS Protection
        self::setXSSHeaders();
        
        // 3. Clickjacking Protection
        self::setFrameOptions();
        
        // 4. MIME Sniffing Protection
        self::setContentTypeOptions();
        
        // 5. HTTPS Security
        self::setHTTPSHeaders();
        
        // 6. Referrer Policy
        self::setReferrerPolicy();
        
        // 7. Feature/Permission Policy
        self::setPermissionPolicy();
        
        // 8. Additional Security Headers
        self::setAdditionalHeaders();
    }
    
    /**
     * Content Security Policy - OWASP Compliant
     */
    private static function setCSPHeaders($strict) {
        if ($strict) {
            // Strict CSP for production
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'nonce-" . self::$nonce . "' https://cdnjs.cloudflare.com https://unpkg.com; " .
                   "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com; " .
                   "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                   "img-src 'self' data: https: blob:; " .
                   "connect-src 'self' https:; " .
                   "media-src 'self' data: blob:; " .
                   "object-src 'none'; " .
                   "base-uri 'self'; " .
                   "form-action 'self'; " .
                   "frame-ancestors 'none'; " .
                   "manifest-src 'self'; " .
                   "worker-src 'self'; " .
                   "upgrade-insecure-requests";
        } else {
            // Relaxed CSP for development
            $csp = "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https: http: blob:; " .
                   "frame-ancestors 'none'";
        }
        
        header("Content-Security-Policy: " . $csp);
    }
    
    /**
     * XSS Protection Headers
     */
    private static function setXSSHeaders() {
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
    }
    
    /**
     * Clickjacking Protection
     */
    private static function setFrameOptions() {
        header("X-Frame-Options: DENY");
    }
    
    /**
     * Content Type Protection
     */
    private static function setContentTypeOptions() {
        header("X-Content-Type-Options: nosniff");
    }
    
    /**
     * HTTPS Security Headers
     */
    private static function setHTTPSHeaders() {
        // Only set HTTPS headers if we're on HTTPS
        if (self::isHTTPS()) {
            // HSTS - HTTP Strict Transport Security
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }
        
        // Expect-CT (Certificate Transparency)
        header("Expect-CT: max-age=86400, enforce");
    }
    
    /**
     * Referrer Policy
     */
    private static function setReferrerPolicy() {
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
    
    /**
     * Feature/Permission Policy
     */
    private static function setPermissionPolicy() {
        $policy = "geolocation=(), " .
                 "microphone=(), " .
                 "camera=(), " .
                 "magnetometer=(), " .
                 "gyroscope=(), " .
                 "speaker=(), " .
                 "vibrate=(), " .
                 "fullscreen=(self), " .
                 "payment=()";
        
        header("Permissions-Policy: " . $policy);
    }
    
    /**
     * Additional Security Headers
     */
    private static function setAdditionalHeaders() {
        // Remove server information
        header_remove("X-Powered-By");
        header_remove("Server");
        
        // Cache control for sensitive pages
        if (self::isSensitivePage()) {
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: 0");
        }
        
        // Cross-Origin Resource Sharing (restrictive)
        header("Cross-Origin-Resource-Policy: same-origin");
        header("Cross-Origin-Opener-Policy: same-origin");
        header("Cross-Origin-Embedder-Policy: require-corp");
    }
    
    /**
     * Get current nonce for inline scripts
     */
    public static function getNonce() {
        return self::$nonce;
    }
    
    /**
     * Check if we're on HTTPS
     */
    private static function isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Check if current page is sensitive (admin, login, etc.)
     */
    private static function isSensitivePage() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $sensitivePatterns = ['/admin', '/login', '/register', '/user', '/settings', '/profile'];
        
        foreach ($sensitivePatterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate script tag with nonce
     */
    public static function scriptTag($src = null, $inline = null) {
        $nonce = self::getNonce();
        
        if ($src) {
            return '<script nonce="' . $nonce . '" src="' . htmlspecialchars($src) . '"></script>';
        } elseif ($inline) {
            return '<script nonce="' . $nonce . '">' . $inline . '</script>';
        }
        
        return '<script nonce="' . $nonce . '">';
    }
    
    /**
     * Generate style tag with nonce (if needed)
     */
    public static function styleTag($href = null, $inline = null) {
        if ($href) {
            return '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
        } elseif ($inline) {
            return '<style>' . $inline . '</style>';
        }
        
        return '<style>';
    }
}

// Auto-apply security headers for production
if (defined('SECURITY_ENABLED') && SECURITY_ENABLED && 
    !defined('DISABLE_AUTO_HEADERS')) {
    ProductionSecurityHeaders::setAllHeaders(true);
}
?>