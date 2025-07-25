<?php
/**
 * MR.ECU - Global Functions
 * GUID tabanlı yardımcı fonksiyonlar
 */

// UUID/GUID oluşturma fonksiyonu
if (!function_exists('generateUUID')) {
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// String sanitization fonksiyonu
if (!function_exists('sanitize')) {
    function sanitize($str) {
        return trim(htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8'));
    }
}

// Session kontrol fonksiyonları
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

// UUID doğrulama fonksiyonu
if (!function_exists('isValidUUID')) {
    function isValidUUID($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }
}

// Token oluşturma fonksiyonu
if (!function_exists('generateToken')) {
    function generateToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }
}

// Email gönderme fonksiyonu (basit)
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $message) {
        // Basit email gönderme
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: noreply@mrecu.com' . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
}

// Güvenli dosya yükleme
if (!function_exists('secureFileUpload')) {
    function secureFileUpload($file, $uploadDir, $allowedTypes = []) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            return false;
        }
        
        $newFileName = generateUUID() . '.' . $extension;
        $uploadPath = rtrim($uploadDir, '/') . '/' . $newFileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $newFileName;
        }
        
        return false;
    }
}

// Array'den CSV oluşturma
if (!function_exists('arrayToCSV')) {
    function arrayToCSV($array, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($array)) {
            // Header satırı
            fputcsv($output, array_keys($array[0]));
            
            // Veri satırları
            foreach ($array as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}

// CSV'den array'e dönüştürme
if (!function_exists('csvToArray')) {
    function csvToArray($filename, $delimiter = ',') {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }
        
        $header = null;
        $data = [];
        
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
}

// Tarih formatı dönüştürme
if (!function_exists('convertDateFormat')) {
    function convertDateFormat($date, $fromFormat = 'Y-m-d H:i:s', $toFormat = 'd.m.Y H:i') {
        if (empty($date)) return '';
        
        $dateTime = DateTime::createFromFormat($fromFormat, $date);
        if ($dateTime === false) {
            // Alternatif parse
            $dateTime = new DateTime($date);
        }
        
        return $dateTime->format($toFormat);
    }
}

// Slug oluşturma
if (!function_exists('createSlug')) {
    function createSlug($text) {
        // Türkçe karakter dönüştürme
        $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
        $english = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
        $text = str_replace($turkish, $english, $text);
        
        // Küçük harfe çevir ve temizle
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }
}

// IP adresi alma
if (!function_exists('getRealIP')) {
    function getRealIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Sistem sabitlerini tanımla
if (!defined('DEFAULT_CREDITS')) {
    define('DEFAULT_CREDITS', 0);
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'MR.ECU');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost:8889/mrecuphpkopyasikopyasi6/');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../uploads/');
}

if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 52428800); // 50MB
}

// Session başlatma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlama (development)
if (!defined('PRODUCTION')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone ayarı
date_default_timezone_set('Europe/Istanbul');

// Session tabanlı fonksiyonlar
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Admin kontrol fonksiyonu
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        if (!isLoggedIn() || !isAdmin()) {
            redirect('../login.php?error=access_denied');
        }
    }
}

// Kullanıcı kontrol fonksiyonu
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirect('../login.php?error=login_required');
        }
    }
}

// Dosya boyutunu insan okunabilir formata çevir
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes, $precision = 2) {
        if ($bytes == 0) {
            return '0 B';
        }
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $base = log($bytes, 1024);
        $index = floor($base);
        
        if ($index >= count($units)) {
            $index = count($units) - 1;
        }
        
        $size = round(pow(1024, $base - $index), $precision);
        
        return $size . ' ' . $units[$index];
    }
}

// Tarih formatı düzenleme
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd.m.Y H:i') {
        if (empty($date)) {
            return 'Belirtilmemiş';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($timestamp === false) {
            return 'Geçersiz tarih';
        }
        
        return date($format, $timestamp);
    }
}

// Türkçe karakter temizleme
if (!function_exists('turkishToEnglish')) {
    function turkishToEnglish($text) {
        $search = array('Ğ','Ü','Ş','İ','Ö','Ç','ğ','ü','ş','ı','ö','ç');
        $replace = array('G','U','S','I','O','C','g','u','s','i','o','c');
        return str_replace($search, $replace, $text);
    }
}

// URL dostu slug oluşturma
if (!function_exists('createSlug')) {
    function createSlug($text) {
        $text = turkishToEnglish($text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}

?>
