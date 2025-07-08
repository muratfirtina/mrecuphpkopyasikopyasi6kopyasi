<?php
/**
 * Modern User Panel Header - Kullanıcı paneli için modern header
 */

// Ana dizinden config dosyalarını include et
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Kullanıcı girişi kontrolü
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Eğer sayfa başlığı tanımlanmamışsa varsayılan değer ata
if (!isset($pageTitle)) {
    $pageTitle = 'Kullanıcı Paneli';
}

// CSS ve JS dosyaları için temel yol
$basePath = '../';
$cssPath = '../assets/css/style.css';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Mr ECU Kullanıcı Paneli - Dosyalarınızı yönetin ve işlemlerinizi takip edin'; ?>">
    <meta name="keywords" content="<?php echo isset($pageKeywords) ? $pageKeywords : 'kullanıcı paneli, dosya yönetimi, ECU işlemleri'; ?>">
    <meta name="theme-color" content="#667eea">
    
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo $cssPath; ?>" rel="stylesheet">
    
    <!-- Modern User Panel Styles -->
    <style>
        /* Global Styles */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --box-shadow-hover: 0 8px 30px rgba(0,0,0,0.12);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8f9fa;
            color: #495057;
            line-height: 1.6;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f3f4;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c8cd;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8b2b9;
        }

        /* Layout */
        .user-panel-wrapper {
            min-height: 100vh;
            background: #f8f9fa;
        }

        .main-content {
            padding: 0;
            margin: 0;
        }

        .content-wrapper {
            padding: 1.5rem;
            min-height: calc(100vh - 56px);
        }

        /* Modern Card Styling */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--box-shadow-hover);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Modern Form Controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        /* Modern Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
        }

        .btn-success {
            background: var(--success-gradient);
            border: none;
        }

        .btn-warning {
            background: var(--warning-gradient);
            border: none;
        }

        .btn-info {
            background: var(--info-gradient);
            border: none;
        }

        .btn-danger {
            background: var(--danger-gradient);
            border: none;
        }

        /* Modern Alerts */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        /* Loading States */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Utility Classes */
        .text-gradient {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .bg-gradient-primary {
            background: var(--primary-gradient) !important;
        }

        .shadow-modern {
            box-shadow: var(--box-shadow) !important;
        }

        .shadow-modern-hover:hover {
            box-shadow: var(--box-shadow-hover) !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a;
                color: #e9ecef;
            }
            
            .card {
                background: #2d3748;
                color: #e9ecef;
            }
            
            .form-control, .form-select {
                background: #2d3748;
                border-color: #4a5568;
                color: #e9ecef;
            }
        }

        /* Print Styles */
        @media print {
            .sidebar,
            .navbar,
            .btn,
            .alert-dismissible .btn-close {
                display: none !important;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
        }
    </style>
    
    <!-- Ek CSS dosyaları için -->
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Meta Tags for Social Media -->
    <meta property="og:title" content="<?php echo $pageTitle . ' - ' . SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Mr ECU Kullanıcı Paneli'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="../assets/images/og-image.jpg">
    
    <!-- PWA Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo SITE_NAME; ?>">
</head>
<body class="user-panel-wrapper">
    
    <!-- Modern Header Include -->
    <?php include __DIR__ . '/../user/_header.php'; ?>

    <!-- Ana içerik başlangıcı -->
    <!-- Header tarafından container açıldı, footer'da kapatılacak -->
