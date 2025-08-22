<?php
/**
 * Header ve Navigation - Tüm sayfalar için ortak header
 */

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Eğer sayfa başlığı tanımlanmamışsa varsayılan değer ata
if (!isset($pageTitle)) {
    $pageTitle = 'Ana Sayfa';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Profesyonel ECU hizmetleri - Güvenli, hızlı ve kaliteli ECU yazılım çözümleri'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'ECU, chip tuning, ECU yazılım, immobilizer, TCU'; ?>">
    
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS CSS - CDN değiştir -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.1/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo isset($cssPath) ? $cssPath : 'assets/css/style.css'; ?>" rel="stylesheet">
    
    <!-- Modern Navigation Styles -->
    <style>
    /* Modern Navigation Styles */
    .modern-navbar {
        background: rgba(0, 0, 0, 0.1) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
        padding: 1rem 0;
        box-shadow: none;
        z-index: 1050;
    }
    
    .modern-navbar.scrolled {
        background: rgb(13 17 23 / 75%) !important;
        padding: 0.5rem 0;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
    }
    
    /* Brand Styles */
    .modern-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        color: white !important;
        transition: all 0.3s ease;
    }
    
    .brand-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        transition: all 0.3s ease;
    }
    
    .brand-icon i {
        font-size: 1.5rem;
        color: white;
    }
    
    .brand-text {
        display: flex;
        flex-direction: column;
    }
    
    .brand-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }
    
    .brand-tagline {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
        margin: 0;
        font-weight: 400;
    }
    
    .modern-brand:hover .brand-icon {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }
    
    /* Modern Toggler */
    .modern-toggler {
        border: none;
        padding: 0.5rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        width: 40px;
        height: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 3px;
    }
    
    .toggler-line {
        width: 20px;
        height: 2px;
        background: white;
        border-radius: 1px;
        transition: all 0.3s ease;
    }
    
    .modern-toggler:focus {
        box-shadow: none;
    }
    
    .modern-toggler.collapsed .toggler-line:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    
    .modern-toggler.collapsed .toggler-line:nth-child(2) {
        opacity: 0;
    }
    
    .modern-toggler.collapsed .toggler-line:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
    }
    
    /* Navigation Links */
    .modern-nav-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem !important;
        color: rgba(255, 255, 255, 0.8) !important;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
        margin: 0 0.25rem;
    }
    
    .modern-nav-link i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
    }
    
    .modern-nav-link:hover {
        color: white !important;
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-1px);
    }
    
    .modern-nav-link.active {
        color: #dc3545 !important;
        background: rgba(220, 53, 69, 0.1);
        box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
    }
    
    .modern-nav-link.active::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 30px;
        height: 2px;
        background: #dc3545;
        border-radius: 1px;
    }
    
    /* Special Link Styles */
    .upload-link {
        background: rgba(40, 167, 69, 0.1) !important;
        color: #28a745 !important;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }
    
    .upload-link:hover {
        background: rgba(40, 167, 69, 0.2) !important;
        color: #28a745 !important;
        border-color: rgba(40, 167, 69, 0.5);
    }
    
    .login-btn {
        background: rgba(0, 123, 255, 0.1) !important;
        color: #007bff !important;
        border: 1px solid rgba(0, 123, 255, 0.3);
    }
    
    .login-btn:hover {
        background: rgba(0, 123, 255, 0.2) !important;
        color: #007bff !important;
    }
    
    .register-btn {
        background: linear-gradient(135deg, #dc3545, #fd7e14) !important;
        color: white !important;
        border: none;
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
    }
    
    .register-btn:hover {
        background: linear-gradient(135deg, #c82333, #e8690b) !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
    }
    
    /* User Dropdown */
    .user-dropdown {
        display: flex !important;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem !important;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 25px;
    }
    
    .user-avatar {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(220, 53, 69, 0.3);
    }
    
    .user-avatar i {
        color: white;
        font-size: 1rem;
    }
    
    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .username {
        font-weight: 600;
        font-size: 0.9rem;
        line-height: 1;
        margin-bottom: 2px;
    }
    
    .credits {
        font-size: 0.75rem;
        color: #28a745;
        font-weight: 500;
        background: rgba(40, 167, 69, 0.1);
        padding: 2px 6px;
        border-radius: 10px;
        line-height: 1;
    }
    
    /* Notification */
    .notification-link {
        position: relative;
    }
    
    .notification-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
        line-height: 1;
    }
    
    /* Modern Dropdown */
    .modern-dropdown {
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        padding: 0.5rem 0;
        margin-top: 0.5rem;
    }
    
    .modern-dropdown .dropdown-item {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        margin: 0 0.5rem;
        transition: all 0.3s ease;
    }
    
    .modern-dropdown .dropdown-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(3px);
    }
    
    .modern-dropdown .dropdown-header {
        color: rgba(255, 255, 255, 0.6);
        padding: 0.5rem 1.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .modern-dropdown .dropdown-divider {
        border-color: rgba(255, 255, 255, 0.1);
        margin: 0.5rem 1rem;
    }
    
    /* Fixed Top Spacing - Remove for overlay effect */
    body {
        padding-top: 0;
    }
    
    /* Responsive */
    @media (max-width: 991.98px) {
        .brand-text {
            display: none;
        }
        
        .brand-icon {
            width: 40px;
            height: 40px;
        }
        
        .brand-icon i {
            font-size: 1.2rem;
        }
        
        .modern-nav-link span {
            display: none;
        }
        
        .user-info {
            display: none;
        }
    }
    
    @media (max-width: 767.98px) {
        .modern-navbar {
            padding: 0.75rem 0;
        }
        
        .navbar-collapse {
            background: rgba(0, 0, 0, 0.9);
            border-radius: 12px;
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        .modern-nav-link {
            margin: 0.25rem 0;
            justify-content: flex-start;
        }
        
        .modern-nav-link span {
            display: inline;
        }
        
        .user-dropdown {
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            display: flex;
        }
    }
    
    /* Scroll Effect */
    .navbar-scroll-effect {
        background: rgba(13, 17, 23, 0.95) !important;
        padding: 0.5rem 0 !important;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3) !important;
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
    }
    </style>
    
    <!-- Ek CSS dosyaları için -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="shortcut icon" href="assets/images/favicon.svg">
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark modern-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand modern-brand" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">
                <div class="brand-icon">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name"><?php echo SITE_NAME; ?></span>
                    <small class="brand-tagline">Professional ECU Services</small>
                </div>
            </a>
            
            <button class="navbar-toggler modern-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="toggler-line"></span>
                <span class="toggler-line"></span>
                <span class="toggler-line"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link modern-nav-link <?php echo ($pageTitle == 'Ana Sayfa') ? 'active' : ''; ?>" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">
                            <i class="fas fa-home"></i>
                            <span>Ana Sayfa</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link modern-nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#services">
                            <i class="fas fa-cogs"></i>
                            <span>Hizmetler</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link modern-nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#about">
                            <i class="fas fa-info-circle"></i>
                            <span>Hakkımızda</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link modern-nav-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php#contact">
                            <i class="fas fa-envelope"></i>
                            <span>İletişim</span>
                        </a>
                    </li>
                    
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- Giriş yapan kullanıcılar için ek menü öğeleri -->
                        <li class="nav-item">
                            <a class="nav-link modern-nav-link upload-link" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/upload.php">
                                <i class="fas fa-upload"></i>
                                <span>Dosya Yükle</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Kullanıcı Menüsü -->
                <ul class="navbar-nav">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- Notification icon -->
                        <li class="nav-item">
                            <a class="nav-link modern-nav-link notification-link" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge" style="display: none;">0</span>
                            </a>
                            <ul class="dropdown-menu modern-dropdown dropdown-menu-end">
                                <li><h6 class="dropdown-header">Bildirimler</h6></li>
                                <li><a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>admin/notifications.php">Tüm Bildirimleri Gör</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link modern-nav-link dropdown-toggle user-dropdown" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="user-info">
                                    <span class="username"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Kullanıcı'; ?></span>
                                    <?php if (isset($_SESSION['credits'])): ?>
                                        <small class="credits"><?php echo $_SESSION['credits']; ?> Kredi</small>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <ul class="dropdown-menu modern-dropdown dropdown-menu-end">
                                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>admin/">
                                            <i class="fas fa-cog me-2"></i>Admin Panel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>migration-dashboard.php">
                                            <i class="fas fa-exchange-alt me-2"></i>Migration Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/">
                                        <i class="fas fa-dashboard me-2"></i>Panel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/files.php">
                                        <i class="fas fa-file me-2"></i>Dosyalarım
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>user/profile.php">
                                        <i class="fas fa-user me-2"></i>Profil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo isset($basePath) ? $basePath : ''; ?>logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link modern-nav-link login-btn" href="<?php echo isset($basePath) ? $basePath : ''; ?>login.php">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Giriş</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link modern-nav-link register-btn" href="<?php echo isset($basePath) ? $basePath : ''; ?>register.php">
                                <i class="fas fa-user-plus"></i>
                                <span>Kayıt</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Ana içerik başlangıcı -->
    <main class="main-content">

    <!-- Modern Navigation JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.querySelector('.modern-navbar');
        const toggler = document.querySelector('.modern-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        // Scroll Effect
        function handleScroll() {
            if (window.scrollY > 100) {
                navbar.classList.add('navbar-scroll-effect');
            } else {
                navbar.classList.remove('navbar-scroll-effect');
            }
        }
        
        // Toggler Animation
        if (toggler && navbarCollapse) {
            navbarCollapse.addEventListener('show.bs.collapse', function() {
                toggler.classList.add('collapsed');
            });
            
            navbarCollapse.addEventListener('hide.bs.collapse', function() {
                toggler.classList.remove('collapsed');
            });
        }
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    const offsetTop = target.offsetTop - 100; // Account for fixed navbar
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Active link highlighting for single page navigation
        function updateActiveNavLink() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.modern-nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 150;
                if (window.scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes(current) && current !== '') {
                    link.classList.add('active');
                }
            });
        }
        
        // Event listeners
        window.addEventListener('scroll', function() {
            handleScroll();
            updateActiveNavLink();
        });
        
        // Initialize scroll effect
        handleScroll();
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.modern-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                        toggle: false
                    });
                    bsCollapse.hide();
                }
            });
        });
    });
    </script>
