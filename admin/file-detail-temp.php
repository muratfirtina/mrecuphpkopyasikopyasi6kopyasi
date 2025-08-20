<?php

/**
 * Mr ECU - File Detail Page (Updated for Response Files)
 * Yanıt dosyaları desteği ile güncellenmiş dosya detay sayfası
 */

// Hata raporlamasını aç
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

try {
    require_once '../config/config.php';
    require_once '../config/database.php';

    // PDO kontrolü
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }

    // Gerekli fonksiyonları kontrol et ve eksikse tanımla
    if (!function_exists('isValidUUID')) {
        function isValidUUID($uuid)
        {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
        }
    }

    if (!function_exists('sanitize')) {
        function sanitize($data)
        {
            if (is_array($data)) {
                return array_map('sanitize', $data);
            }
            return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    if (!function_exists('redirect')) {
        function redirect($url)
        {
            if (headers_sent()) {
                echo "<script>window.location.href = '$url';</script>";
                echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
            } else {
                header("Location: " . $url);
            }
            exit();
        }
    }

    if (!function_exists('isLoggedIn')) {
        function isLoggedIn()
        {
            return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        }
    }

    if (!function_exists('isAdmin')) {
        function isAdmin()
        {
            if (isset($_SESSION['role'])) {
                return $_SESSION['role'] === 'admin';
            }
            return isset($_SESSION['is_admin']) && ((int)$_SESSION['is_admin'] === 1);
        }
    }

    if (!function_exists('formatFileSize')) {
        function formatFileSize($bytes)
        {
            if ($bytes === 0) return '0 B';
            $k = 1024;
            $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = floor(log($bytes) / log($k));
            return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
        }
    }

    if (!function_exists('formatDate')) {
        function formatDate($date)
        {
            return date('d.m.Y H:i', strtotime($date));
        }
    }

    // Class includes
    require_once '../includes/FileManager.php';
    require_once '../includes/User.php';
    require_once '../includes/NotificationManager.php';
    require_once '../includes/ChatManager.php';

    // Session kontrolü
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Admin kontrolü
    if (!isLoggedIn() || !isAdmin()) {
        redirect('../login.php?error=access_denied');
    }
} catch (Exception $e) {
    error_log('File detail init error: ' . $e->getMessage());
    echo "<div class='alert alert-danger'>Sistem hatası: " . $e->getMessage() . "</div>";
    echo "<p><a href='../login.php'>Giriş sayfasına dön</a></p>";
    exit;
}
