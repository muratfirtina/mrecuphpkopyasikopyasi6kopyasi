<?php
/**
 * Mr ECU - Environment Manager (GÜVENLI SÜRÜM)
 * Production/Development environment switcher
 * 
 * Bu dosya projeyi production'a deploy ettiğinizde
 * environment ayarlarını otomatik olarak değiştirir.
 */

class EnvironmentManager {
    private $projectRoot;
    private $isProduction;
    
    public function __construct() {
        $this->projectRoot = dirname(__DIR__);
        $this->detectEnvironment();
    }
    
    /**
     * DAHA GÜVENLI environment detection
     */
    private function detectEnvironment() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        
        // SADECE AÇIK PRODUCTION İNDIKATÖRLERİ
        $clearProductionIndicators = [
            // Natro hosting specific (kesin belirtiler)
            strpos($host, '.natro.com') !== false,
            strpos($host, '.natro.net') !== false,
            strpos($docRoot, '/public_html') !== false && !strpos($host, 'localhost'),
            
            // Manuel production marker
            file_exists($this->projectRoot . '/.force-production'),
            
            // Domain name pattern (localhost değilse ve gerçek domain ise)
            !strpos($host, 'localhost') && 
            !strpos($host, '127.0.0.1') && 
            !strpos($host, '.local') && 
            !strpos($host, '.test') && 
            !strpos($host, '.dev') &&
            strpos($host, '.') !== false && 
            strlen($host) > 5 // Gerçek domain en az 5 karakter
        ];
        
        // SADECE NET PRODUCTION İNDİKATÖRLERİ VARSA TRUE
        $this->isProduction = count(array_filter($clearProductionIndicators)) >= 2;
        
        // Debug log
        error_log("Environment Detection: Host=$host, DocRoot=$docRoot, Production=" . ($this->isProduction ? 'TRUE' : 'FALSE'));
    }
    
    /**
     * Setup environment based on detection
     */
    public function setupEnvironment() {
        if ($this->isProduction) {
            $this->setupProduction();
        } else {
            $this->setupDevelopment();
        }
    }
    
    /**
     * Setup production environment
     */
    private function setupProduction() {
        error_log("Setting up PRODUCTION environment");
        
        // Switch to production .env if exists
        if (file_exists($this->projectRoot . '/.env.production')) {
            if (file_exists($this->projectRoot . '/.env')) {
                // Backup current .env
                copy($this->projectRoot . '/.env', $this->projectRoot . '/.env.backup');
            }
            
            // Copy production .env
            copy($this->projectRoot . '/.env.production', $this->projectRoot . '/.env');
            error_log("Switched to production .env");
        }
        
        // Switch to production .htaccess
        if (file_exists($this->projectRoot . '/.htaccess.production')) {
            if (file_exists($this->projectRoot . '/.htaccess')) {
                // Backup current .htaccess
                copy($this->projectRoot . '/.htaccess', $this->projectRoot . '/.htaccess.backup');
            }
            
            // Copy production .htaccess
            copy($this->projectRoot . '/.htaccess.production', $this->projectRoot . '/.htaccess');
            error_log("Switched to production .htaccess");
        }
        
        // Switch to production config if exists
        if (file_exists($this->projectRoot . '/config/config.production.php')) {
            if (file_exists($this->projectRoot . '/config/config.php')) {
                // Backup current config
                copy($this->projectRoot . '/config/config.php', $this->projectRoot . '/config/config.backup.php');
            }
            
            // Copy production config
            copy($this->projectRoot . '/config/config.production.php', $this->projectRoot . '/config/config.php');
            error_log("Switched to production config");
        }
        
        // Create production marker
        file_put_contents($this->projectRoot . '/.production', 'Auto-switched on ' . date('Y-m-d H:i:s'));
    }
    
    /**
     * Setup development environment
     */
    private function setupDevelopment() {
        error_log("Setting up DEVELOPMENT environment");
        
        // Development için özel bir şey yapma, mevcut dosyaları koru
        // Sadece development marker oluştur
        if (file_exists($this->projectRoot . '/.production')) {
            unlink($this->projectRoot . '/.production');
        }
        
        file_put_contents($this->projectRoot . '/.development', 'Auto-switched on ' . date('Y-m-d H:i:s'));
    }
    
    /**
     * Get current environment
     */
    public function getCurrentEnvironment() {
        return $this->isProduction ? 'production' : 'development';
    }
    
    /**
     * Force environment switch (Manuel kontrol)
     */
    public function forceProduction() {
        file_put_contents($this->projectRoot . '/.force-production', 'Forced on ' . date('Y-m-d H:i:s'));
        $this->isProduction = true;
        $this->setupEnvironment();
        error_log("FORCED production environment");
    }
    
    public function forceDevelopment() {
        if (file_exists($this->projectRoot . '/.force-production')) {
            unlink($this->projectRoot . '/.force-production');
        }
        $this->isProduction = false;
        $this->setupEnvironment();
        error_log("FORCED development environment");
    }
}

// SADECE MANUEL ÇAĞRIDA ÇALIŞTIR
// Auto-setup environment when this file is included SADECE config.php'den çağrılırsa
if (basename($_SERVER['PHP_SELF']) === 'config.php' || 
    strpos($_SERVER['REQUEST_URI'] ?? '', '/config/') !== false) {
    
    $envManager = new EnvironmentManager();
    
    // Güvenli setup - sadece gerçekten gerekiyorsa
    if ($envManager->getCurrentEnvironment() === 'production') {
        $envManager->setupEnvironment();
    }
    
    error_log("Environment Manager loaded - Current: " . $envManager->getCurrentEnvironment());
}
?>