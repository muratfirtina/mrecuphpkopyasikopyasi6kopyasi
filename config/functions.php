<?php
/**
 * Mr ECU - Helper Functions
 * Common functions used throughout the application
 */

// Prevent direct access
if (!defined('BASE_URL')) {
    exit('Direct access not allowed');
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
    return in_array($userRole, ['admin', 'design']);
}

/**
 * Redirect to a URL
 * @param string $url
 * @param bool $permanent
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    
    // Check if URL is relative or absolute
    if (strpos($url, 'http') !== 0) {
        // Relative URL - add base URL if needed
        if (strpos($url, '/') !== 0) {
            $url = BASE_URL . '/' . $url;
        } else {
            $url = BASE_URL . $url;
        }
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * Check if request is AJAX
 * @return bool
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
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
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Create URL-friendly slug from text
 * @param string $text
 * @param string $divider
 * @return string
 */
function createSlug($text, $divider = '-') {
    // Turkish characters
    $search = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
    $replace = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'i', 'o', 's', 'u'];
    
    $text = str_replace($search, $replace, $text);
    $text = preg_replace('/[^a-zA-Z0-9\-_]/', $divider, $text);
    $text = preg_replace('/[' . preg_quote($divider) . ']+/', $divider, $text);
    
    return strtolower(trim($text, $divider));
}

/**
 * Truncate text to specified length
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Format date in Turkish format
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    if (empty($date)) {
        return '';
    }
    
    return date($format, strtotime($date));
}

/**
 * Send JSON response
 * @param array $data
 * @param int $statusCode
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Log error message
 * @param string $message
 * @param string $file
 */
function logError($message, $file = 'error.log') {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    
    $logFile = dirname(__DIR__) . '/logs/' . $file;
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Check if file upload is secure
 * @param array $file $_FILES array element
 * @param array $allowedTypes
 * @param int $maxSize Max size in bytes
 * @return array ['success' => bool, 'message' => string]
 */
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size too large (max: ' . formatFileSize($maxSize) . ')'];
    }
    
    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    if (isset($allowedMimes[$extension]) && $mimeType !== $allowedMimes[$extension]) {
        return ['success' => false, 'message' => 'File content does not match extension'];
    }
    
    return ['success' => true, 'message' => 'File is valid'];
}

/**
 * Resize image
 * @param string $source Source file path
 * @param string $destination Destination file path
 * @param int $width New width
 * @param int $height New height
 * @param int $quality JPEG quality (0-100)
 * @return bool
 */
function resizeImage($source, $destination, $width, $height, $quality = 90) {
    if (!file_exists($source)) {
        return false;
    }
    
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($width / $originalWidth, $height / $originalHeight);
    $newWidth = intval($originalWidth * $ratio);
    $newHeight = intval($originalHeight * $ratio);
    
    // Create source image
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Create destination image
    $destImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Create destination directory if it doesn't exist
    $destDir = dirname($destination);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    // Save image
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($destImage, $destination, $quality);
            break;
        case 'image/png':
            $result = imagepng($destImage, $destination);
            break;
        case 'image/gif':
            $result = imagegif($destImage, $destination);
            break;
        case 'image/webp':
            $result = imagewebp($destImage, $destination, $quality);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($destImage);
    
    return $result;
}
