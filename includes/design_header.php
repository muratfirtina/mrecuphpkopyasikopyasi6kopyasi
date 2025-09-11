<?php
/**
 * Design Panel Header
 * Sadece Admin ve Design rollerine sahip kullanıcılar erişebilir
 */

// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?message=Giriş yapmanız gerekiyor');
    exit;
}

// Kullanıcı yetkisi kontrol et (sadece admin veya design rolü)
// Hem role hem user_role'ü kontrol et (tutarlılık için)
$userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
if (!$userRole || !in_array($userRole, ['admin', 'design'])) {
    header('Location: ../index.php?error=Bu sayfaya erişim yetkiniz yok');
    exit;
}

// CSS ve JS dosyalarının yolunu belirle
$basePath = '../';
if (strpos($_SERVER['REQUEST_URI'], '/design/') === false) {
    $basePath = './';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Design Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Design Panel - ' . SITE_NAME; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'design, admin, panel'; ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    
     <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" crossorigin="anonymous">
    
    <!-- jQuery (CSP uyumlu CDN) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" crossorigin="anonymous"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" crossorigin="anonymous"></script>
    
    <!-- jQuery Yüklenme Kontrol -->
    <script>
        // jQuery yüklenme kontrolü ve fallback
        (function() {
            function checkjQuery() {
                if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                    console.error('jQuery yüklenemedi! CSP uyumlu fallback yükleniyor...');
                    
                    // CSP uyumlu fallback - script element ile yükleme
                    var script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js';
                    script.crossOrigin = 'anonymous';
                    script.async = false;
                    script.onload = function() {
                        console.log('jQuery fallback başarıyla yüklendi');
                        window.jQueryReady = true;
                    };
                    script.onerror = function() {
                        console.error('jQuery fallback da yüklenemedi!');
                        // Son çare olarak yerel kopya
                        loadLocalJQuery();
                    };
                    document.head.appendChild(script);
                } else {
                    console.log('jQuery başarıyla yüklendi');
                    window.jQueryReady = true;
                }
            }
            
            function loadLocalJQuery() {
                // Eğer lokal jQuery dosyası varsa
                var localScript = document.createElement('script');
                localScript.src = '../assets/js/jquery.min.js';
                localScript.onload = function() {
                    console.log('Yerel jQuery yüklendi');
                    window.jQueryReady = true;
                };
                localScript.onerror = function() {
                    console.error('Hiçbir jQuery kaynağı yüklenemedi!');
                    // jQuery olmadan çalışacak basit fonksiyonlar sağla
                    providejQueryFallback();
                };
                document.head.appendChild(localScript);
            }
            
            function providejQueryFallback() {
                // Basit AJAX fonksiyonu sağla
                window.$ = function(selector) {
                    if (typeof selector === 'function') {
                        // Document ready
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', selector);
                        } else {
                            selector();
                        }
                        return;
                    }
                    return document.querySelector(selector);
                };
                
                window.$.ajax = function(options) {
                    var xhr = new XMLHttpRequest();
                    xhr.open(options.method || 'GET', options.url, true);
                    
                    if (options.headers) {
                        for (var header in options.headers) {
                            xhr.setRequestHeader(header, options.headers[header]);
                        }
                    }
                    
                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            if (options.success) options.success(xhr.responseText);
                        } else {
                            if (options.error) options.error(xhr);
                        }
                        if (options.complete) options.complete();
                    };
                    
                    xhr.onerror = function() {
                        if (options.error) options.error(xhr);
                        if (options.complete) options.complete();
                    };
                    
                    if (options.beforeSend) options.beforeSend();
                    
                    if (options.data instanceof FormData) {
                        xhr.send(options.data);
                    } else if (options.data) {
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send(new URLSearchParams(options.data).toString());
                    } else {
                        xhr.send();
                    }
                };
                
                console.log('jQuery fallback fonksiyonları sağlandı');
                window.jQueryReady = true;
            }
            
            // Sayfa yüklendikten sonra kontrol et
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', checkjQuery);
            } else {
                checkjQuery();
            }
            
            // Global hazır olma kontrolü
            window.waitForjQuery = function(callback, timeout) {
                timeout = timeout || 5000;
                var startTime = Date.now();
                
                function check() {
                    if (window.jQueryReady || typeof jQuery !== 'undefined') {
                        callback();
                    } else if (Date.now() - startTime < timeout) {
                        setTimeout(check, 100);
                    } else {
                        console.error('jQuery yükleme zaman aşımı!');
                        callback(); // Yine de devam et
                    }
                }
                
                check();
            };
        })();
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
    
    <!-- Design Panel Custom CSS -->
    <style>
        :root {
            --design-primary: #667eea;
            --design-secondary: #764ba2;
            --design-success: #06d6a0;
            --design-danger: #ef476f;
            --design-warning: #ffd166;
            --design-info: #118ab2;
            --design-light: #f8f9fc;
            --design-dark: #2c3e50;
            --sidebar-width: 280px;
        }

        body {
            background: linear-gradient(135deg, var(--design-light) 0%, #e3f2fd 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styles */
        .design-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--design-primary) 0%, var(--design-secondary) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .design-sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h4 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sidebar-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(180deg);
        }

        .nav-section {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        .design-nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .design-nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: rgba(255,255,255,0.5);
        }

        .design-nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
        }

        .design-nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        /* Main Content */
        .design-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .design-main.expanded {
            margin-left: 70px;
        }

        .design-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-bottom: 1px solid #eee;
        }

        .design-header .breadcrumb {
            background: none;
            margin: 0;
            padding: 0;
        }

        .design-content {
            padding: 2rem;
        }

        /* Cards */
        .design-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: none;
            transition: all 0.3s ease;
        }

        .design-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .design-card-header {
            background: linear-gradient(135deg, var(--design-primary), var(--design-secondary));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }

        /* Buttons */
        .btn-design-primary {
            background: linear-gradient(135deg, var(--design-primary), var(--design-secondary));
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-design-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Form Elements */
        .form-control:focus {
            border-color: var(--design-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-select:focus {
            border-color: var(--design-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-left: 4px solid var(--design-primary);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--design-primary);
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* User Info */
        .user-info {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.5rem;
        }

        .user-name {
            font-size: 0.9rem;
            margin: 0;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .design-sidebar {
                transform: translateX(-100%);
            }
            
            .design-sidebar.show {
                transform: translateX(0);
            }
            
            .design-main {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block !important;
            }
        }

        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--design-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Color Picker Custom */
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #ddd;
            display: inline-block;
            cursor: pointer;
            margin-left: 10px;
        }

        /* Image Upload Preview */
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            padding: 10px;
            margin-top: 10px;
        }

        .upload-area {
            border: 2px dashed var(--design-primary);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: rgba(102, 126, 234, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: var(--design-secondary);
        }

        .upload-area {
            border: 2px dashed var(--design-primary);
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            background: rgba(102, 126, 234, 0.03);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .upload-area:hover {
            background: rgba(102, 126, 234, 0.08);
            border-color: var(--design-secondary);
            transform: translateY(-2px);
        }

        .upload-area.dragover {
            background: rgba(102, 126, 234, 0.15);
            border-color: var(--design-secondary);
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .upload-area i {
            display: block;
            margin-bottom: 1rem;
        }
        
        .upload-area h5 {
            color: var(--design-primary);
            margin-bottom: 0.5rem;
        }
        
        /* Progress bar */
        .upload-progress .progress {
            height: 8px;
            border-radius: 10px;
            background: #f0f0f0;
        }
        
        .upload-progress .progress-bar {
            background: linear-gradient(135deg, var(--design-primary), var(--design-secondary));
            border-radius: 10px;
        }
        
        /* Tab Pills Styling */
        .nav-pills .nav-link {
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--design-primary), var(--design-secondary));
        }
    </style>
    
    <!-- Additional page specific styles -->
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle d-lg-none" onclick="toggleSidebar()">
        <i class="bi bi-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="design-sidebar" id="designSidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <button class="sidebar-toggle d-none d-lg-block" onclick="toggleSidebar()">
                <i class="bi bi-bars"></i>
            </button>
            <h4><i class="bi bi-paint-brush me-2"></i>Design Panel</h4>
        </div>

        <!-- User Info -->
        <div class="user-info">
            <div class="user-avatar">
                <i class="bi bi-person"></i>
            </div>
            <p class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
            <p class="user-role"><?php echo ucfirst($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'admin'); ?></p>
        </div>

        <!-- Navigation -->
        <div class="nav-section">
            <div class="nav-section-title">Ana Panel</div>
            <a href="index.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer"></i>
                <span>Dashboard</span>
            </a>
            <a href="../admin/" class="design-nav-link">
                <i class="bi bi-gear"></i>
                <span>Admin Panel</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Design Yönetimi</div>
            <a href="sliders.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'sliders.php') ? 'active' : ''; ?>">
                <i class="bi bi-images"></i>
                <span>Hero Slider</span>
            </a>
            <a href="settings.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
                <i class="bi bi-palette"></i>
                <span>Site Ayarları</span>
            </a>
            <a href="content.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'content.php') ? 'active' : ''; ?>">
                <i class="bi bi-pencil-square"></i>
                <span>İçerik Yönetimi</span>
            </a>
            <a href="media.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'media.php') ? 'active' : ''; ?>">
                <i class="bi bi-photo-video"></i>
                <span>Medya Yönetimi</span>
            </a>
            <a href="services.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>Hizmet Yönetimi</span>
            </a>
            <a href="footer.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'footer.php') ? 'active' : ''; ?>">
                <i class="bi bi-shoe-prints"></i>
                <span>Footer Yönetimi</span>
            </a>
            <a href="about.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">
                <i class="bi bi-info-circle"></i>
                <span>Hakkımızda</span>
            </a>
            <a href="testimonials.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'testimonials.php') ? 'active' : ''; ?>">
                <i class="bi bi-chat-left-text"></i>
                <span>Yorumlar</span>
            </a>
            <a href="contact.php" class="design-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
                <i class="bi bi-telephone-fill"></i>
                <span>İletişim</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Önizleme</div>
            <a href="../index.php" target="_blank" class="design-nav-link">
                <i class="bi bi-external-link-alt"></i>
                <span>Site Önizleme</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Hesap</div>
            <a href="../logout.php" class="design-nav-link">
                <i class="bi bi-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="design-main" id="designMain">
        <!-- Header -->
        <header class="design-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-0"><?php echo isset($pageTitle) ? $pageTitle : 'Design Panel'; ?></h1>
                    <?php if (isset($breadcrumbs)): ?>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <?php foreach ($breadcrumbs as $item): ?>
                                    <?php if (isset($item['url'])): ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?php echo $item['url']; ?>"><?php echo $item['title']; ?></a>
                                        </li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active"><?php echo $item['title']; ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        <i class="bi bi-sync-alt"></i> Yenile
                    </button>
                    <a href="../index.php" target="_blank" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-eye"></i> Önizleme
                    </a>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="design-content fade-in">
