<?php
/**
 * Mr ECU - Advanced Security Manager
 * Production-Ready Security System
 * 
 * Features:
 * - Rate Limiting
 * - Brute Force Protection  
 * - Session Security
 * - SQL Injection Prevention
 * - XSS Protection
 * - CSRF Protection
 * - Security Event Logging
 */

class AdvancedSecurityManager {
    private $pdo;
    private $config;
    private $sessionTimeout;
    private $maxLoginAttempts;
    private $blockDuration;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->sessionTimeout = SESSION_TIMEOUT ?? 3600;
        $this->maxLoginAttempts = MAX_LOGIN_ATTEMPTS ?? 5;
        $this->blockDuration = LOGIN_BLOCK_DURATION ?? 900; // 15 minutes
        
        $this->initSecurityTables();
    }
    
    // ==========================================
    # 🛡️ AUTHENTICATION SECURITY
    // ==========================================
    
    /**
     * Enhanced rate limiting with 5 attempts / 15 minutes rule
     */
    public function checkBruteForce($identifier, $maxAttempts = null, $blockDuration = null) {
        $maxAttempts = $maxAttempts ?? $this->maxLoginAttempts;
        $blockDuration = $blockDuration ?? $this->blockDuration;
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count,
                       MAX(created_at) as last_attempt
                FROM security_logs 
                WHERE event_type = 'failed_login' 
                AND client_ip = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$identifier, $blockDuration]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['attempt_count'] >= $maxAttempts) {
                $this->logSecurityEvent('brute_force_blocked', [
                    'attempts' => $result['attempt_count'],
                    'identifier' => $identifier,
                    'blocked_until' => date('Y-m-d H:i:s', time() + $blockDuration)
                ], $identifier);
                
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Brute force check failed: ' . $e->getMessage());
            return true; // Fail-safe
        }
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($identifier, $details = []) {
        $this->logSecurityEvent('failed_login', array_merge([
            'identifier' => $identifier,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ], $details), $identifier);
    }
    
    // ==========================================
    # 🔐 SESSION SECURITY
    # ==========================================
    
    /**
     * Enhanced session security with hijacking protection
     */
    public function validateSession() {
        if (!isset($_SESSION['initialized'])) {
            $this->regenerateSession();
            return true;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > $this->sessionTimeout) {
            $this->logSecurityEvent('session_timeout', [
                'user_id' => $_SESSION['user_id'] ?? 'anonymous',
                'last_activity' => $_SESSION['last_activity']
            ], $this->getClientIp());
            $this->destroySession();
            return false;
        }
        
        // User agent check (flexible - log but don't block)
        if (isset($_SESSION['user_agent']) && 
            $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            $this->logSecurityEvent('session_user_agent_change', [
                'user_id' => $_SESSION['user_id'] ?? 'anonymous',
                'old_agent' => $_SESSION['user_agent'],
                'new_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ], $this->getClientIp());
        }
        
        // Regenerate session ID every 30 minutes
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 1800) {
            $this->regenerateSession(false);
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Regenerate session with security
     */
    public function regenerateSession($newSession = true) {
        if ($newSession) {
            session_regenerate_id(true);
            $_SESSION['initialized'] = true;
        } else {
            session_regenerate_id(false);
        }
        
        $_SESSION['last_regeneration'] = time();
        $_SESSION['user_ip'] = $this->getClientIp();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Secure session destroy
     */
    public function destroySession() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    // ==========================================
    # 🚦 RATE LIMITING
    # ==========================================
    
    /**
     * Advanced rate limiting system
     */
    public function checkRateLimit($action, $identifier = null, $limit = 60, $window = 60) {
        $identifier = $identifier ?? $this->getClientIp();
        $key = $action . '_' . $identifier;
        
        try {
            // Check current requests
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as request_count
                FROM rate_limits 
                WHERE limit_key = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$key, $window]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['request_count'] >= $limit) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'action' => $action,
                    'identifier' => $identifier,
                    'limit' => $limit,
                    'window' => $window,
                    'requests' => $result['request_count']
                ], $identifier);
                
                return false;
            }
            
            // Record this request
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (limit_key, created_at) VALUES (?, NOW())
            ");
            $stmt->execute([$key]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Rate limit check failed: ' . $e->getMessage());
            return true; // Fail-safe
        }
    }
    
    // ==========================================
    # 🔍 INPUT VALIDATION & SANITIZATION
    # ==========================================
    
    /**
     * Advanced input sanitization
     */
    public function sanitizeInput($data, $type = 'general') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $data);
        }
        
        // Remove null bytes and normalize
        $data = str_replace("\0", '', $data);
        $data = trim($data);
        
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
                
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '_', $data);
                
            case 'sql':
                // For SQL use prepared statements, but sanitize anyway
                return htmlspecialchars(strip_tags($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
            case 'html':
                // Allow basic HTML but strip dangerous tags
                return strip_tags($data, '<p><br><strong><em><u><a><ul><ol><li>');
                
            default:
            case 'general':
                return htmlspecialchars(strip_tags($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Detect SQL injection attempts
     */
    public function detectSqlInjection($input) {
        $sqlPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(script.*?alert.*?\(.*?\))/i',
            '/([\<\>"\'\;\(\)].*?(script|javascript|vbscript|onload|onerror))/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('sql_injection_attempt', [
                    'input' => substr($input, 0, 200), // Log first 200 chars
                    'pattern' => $pattern,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ], $this->getClientIp());
                
                return true;
            }
        }
        
        return false;
    }
    
    // ==========================================
    # 🛡️ CSRF PROTECTION
    # ==========================================
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) ||
            (time() - $_SESSION['csrf_token_time']) > (CSRF_TOKEN_LIFETIME ?? 3600)) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            $this->logSecurityEvent('csrf_token_missing', [
                'has_session_token' => isset($_SESSION['csrf_token']),
                'provided_token_empty' => empty($token)
            ], $this->getClientIp());
            return false;
        }
        
        // Check token expiry
        if (isset($_SESSION['csrf_token_time']) && 
            (time() - $_SESSION['csrf_token_time']) > (CSRF_TOKEN_LIFETIME ?? 3600)) {
            $this->logSecurityEvent('csrf_token_expired', [
                'token_age' => time() - $_SESSION['csrf_token_time']
            ], $this->getClientIp());
            return false;
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            $this->logSecurityEvent('csrf_token_invalid', [
                'expected_length' => strlen($_SESSION['csrf_token']),
                'provided_length' => strlen($token)
            ], $this->getClientIp());
            return false;
        }
        
        return true;
    }
    
    // ==========================================
    # 📊 SECURITY LOGGING & MONITORING
    # ==========================================
    
    /**
     * Comprehensive security event logging
     */
    public function logSecurityEvent($eventType, $details, $clientIp = null) {
        try {
            $clientIp = $clientIp ?? $this->getClientIp();
            $detailsJson = json_encode($details);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO security_logs (
                    event_type, 
                    event_details, 
                    client_ip, 
                    user_agent,
                    request_uri,
                    user_id,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $eventType,
                $detailsJson,
                $clientIp,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REQUEST_URI'] ?? '',
                $_SESSION['user_id'] ?? null
            ]);
            
            // Critical event alerting
            if (in_array($eventType, ['brute_force_blocked', 'sql_injection_attempt', 'xss_attempt'])) {
                $this->sendSecurityAlert($eventType, $details, $clientIp);
            }
            
        } catch (PDOException $e) {
            error_log('Security logging failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStats($hours = 24) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    event_type,
                    COUNT(*) as event_count,
                    COUNT(DISTINCT client_ip) as unique_ips
                FROM security_logs 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY event_type
                ORDER BY event_count DESC
            ");
            $stmt->execute([$hours]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Security stats failed: ' . $e->getMessage());
            return [];
        }
    }
    
    // ==========================================
    # 🌐 UTILITY FUNCTIONS
    # ==========================================
    
    /**
     * Get real client IP (proxy-aware)
     */
    public function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Initialize security tables
     */
    private function initSecurityTables() {
        try {
            // Security logs table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS security_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    event_details JSON,
                    client_ip VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    request_uri VARCHAR(500),
                    user_id VARCHAR(36),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_event_type (event_type),
                    INDEX idx_client_ip (client_ip),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Rate limits table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    limit_key VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_limit_key (limit_key),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
        } catch (PDOException $e) {
            error_log('Security tables initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Send security alert (email/webhook)
     */
    private function sendSecurityAlert($eventType, $details, $clientIp) {
        // Implementation depends on your notification system
        error_log("SECURITY ALERT: {$eventType} from {$clientIp} - " . json_encode($details));
    }
    
    /**
     * Clean old logs (call periodically)
     */
    public function cleanOldLogs($daysToKeep = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM security_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            
            $stmt = $this->pdo->prepare("
                DELETE FROM rate_limits 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log('Log cleanup failed: ' . $e->getMessage());
        }
    }
}
?>