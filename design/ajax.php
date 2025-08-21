<?php
/**
 * Design Panel AJAX Endpoint'leri
 */

require_once '../config/config.php';
require_once '../config/database.php';

// CORS ve JSON header'ları
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Yetkili kullanıcı kontrolü
$userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
if (!isset($_SESSION['user_id']) || !in_array($userRole, ['admin', 'design'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

$response = ['success' => false, 'message' => 'Geçersiz işlem'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'upload_image':
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['image'];
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                    
                    if (!in_array($file['type'], $allowedTypes)) {
                        throw new Exception('Sadece JPEG, PNG ve WebP formatları desteklenir');
                    }
                    
                    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                        throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz');
                    }
                    
                    $uploadPath = '../assets/images/';
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    
                    // Güvenli dosya adı oluştur
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = 'slider_' . uniqid() . '_' . time() . '.' . $extension;
                    $filePath = $uploadPath . $filename;
                    $webPath = 'assets/images/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        // Veritabanına medya kaydı ekle (isteğe bağlı)
                        try {
                            $mediaId = sprintf(
                                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                                mt_rand(0, 0xffff),
                                mt_rand(0, 0x0fff) | 0x4000,
                                mt_rand(0, 0x3fff) | 0x8000,
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                            );
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO media_files (id, filename, original_name, file_path, file_size, mime_type, file_type, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, 'image', NOW())
                            ");
                            
                            $stmt->execute([
                                $mediaId,
                                $filename,
                                $file['name'],
                                $webPath,
                                $file['size'],
                                $file['type']
                            ]);
                        } catch (Exception $e) {
                            // Medya tablosu yoksa devam et
                            error_log('Media table insert failed: ' . $e->getMessage());
                        }
                        
                        $response = [
                            'success' => true,
                            'message' => 'Resim başarıyla yüklendi',
                            'url' => $webPath,
                            'filename' => $filename,
                            'original_name' => $file['name'],
                            'size' => $file['size'],
                            'debug' => [
                                'upload_path' => $uploadPath,
                                'file_path' => $filePath,
                                'web_path' => $webPath,
                                'file_exists' => file_exists($filePath)
                            ]
                        ];
                    } else {
                        throw new Exception('Dosya yüklenemedi. Dizin izinlerini kontrol edin.');
                    }
                } else {
                    throw new Exception('Geçerli bir resim dosyası seçin');
                }
                break;
                
            case 'get_slider':
                if (isset($_POST['id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM design_sliders WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $slider = $stmt->fetch();
                    
                    if ($slider) {
                        $response = ['success' => true, 'data' => $slider];
                    } else {
                        $response = ['success' => false, 'message' => 'Slider bulunamadı'];
                    }
                }
                break;
                
            case 'update_slider_order':
                if (isset($_POST['sliders']) && is_array($_POST['sliders'])) {
                    $pdo->beginTransaction();
                    
                    foreach ($_POST['sliders'] as $order => $sliderId) {
                        $stmt = $pdo->prepare("UPDATE design_sliders SET sort_order = ? WHERE id = ?");
                        $stmt->execute([$order + 1, $sliderId]);
                    }
                    
                    $pdo->commit();
                    $response = ['success' => true, 'message' => 'Slider sıralaması güncellendi'];
                }
                break;
                
            case 'toggle_slider_status':
                if (isset($_POST['id'])) {
                    $stmt = $pdo->prepare("UPDATE design_sliders SET is_active = !is_active WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    // Yeni durumu al
                    $stmt = $pdo->prepare("SELECT is_active FROM design_sliders WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $newStatus = $stmt->fetchColumn();
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Durum güncellendi',
                        'new_status' => (bool)$newStatus
                    ];
                }
                break;
                
            case 'duplicate_slider':
                if (isset($_POST['id'])) {
                    // Orijinal slider'ı al
                    $stmt = $pdo->prepare("SELECT * FROM design_sliders WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $original = $stmt->fetch();
                    
                    if ($original) {
                        // Yeni UUID oluştur
                        $newId = sprintf(
                            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        
                        // Kopyala
                        $stmt = $pdo->prepare("
                            INSERT INTO design_sliders (
                                id, title, subtitle, description, button_text, button_link,
                                background_image, background_color, text_color, is_active, sort_order,
                                created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())
                        ");
                        
                        $stmt->execute([
                            $newId,
                            $original['title'] . ' (Kopya)',
                            $original['subtitle'],
                            $original['description'],
                            $original['button_text'],
                            $original['button_link'],
                            $original['background_image'],
                            $original['background_color'],
                            $original['text_color'],
                            $original['sort_order'] + 1
                        ]);
                        
                        $response = ['success' => true, 'message' => 'Slider kopyalandı', 'new_id' => $newId];
                    }
                }
                break;
                
            case 'save_settings':
                if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                    $pdo->beginTransaction();
                    
                    foreach ($_POST['settings'] as $key => $value) {
                        // Mevcut ayar var mı kontrol et
                        $checkStmt = $pdo->prepare("SELECT id FROM design_settings WHERE setting_key = ?");
                        $checkStmt->execute([$key]);
                        $existing = $checkStmt->fetch();
                        
                        if ($existing) {
                            // Güncelle
                            $stmt = $pdo->prepare("UPDATE design_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                            $stmt->execute([$value, $key]);
                        } else {
                            // Yeni ekle
                            $newId = sprintf(
                                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                                mt_rand(0, 0xffff),
                                mt_rand(0, 0x0fff) | 0x4000,
                                mt_rand(0, 0x3fff) | 0x8000,
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                            );
                            
                            $stmt = $pdo->prepare("INSERT INTO design_settings (id, setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                            $stmt->execute([$newId, $key, $value]);
                        }
                    }
                    
                    $pdo->commit();
                    $response = ['success' => true, 'message' => 'Ayarlar kaydedildi'];
                }
                break;
                
            case 'get_media_files':
                $page = (int)($_POST['page'] ?? 1);
                $perPage = (int)($_POST['per_page'] ?? 12);
                $offset = ($page - 1) * $perPage;
                
                // Toplam sayı
                $totalStmt = $pdo->query("SELECT COUNT(*) FROM media_files WHERE file_type = 'image'");
                $total = $totalStmt->fetchColumn();
                
                // Dosyalar
                $stmt = $pdo->query("
                    SELECT * FROM media_files 
                    WHERE file_type = 'image' 
                    ORDER BY created_at DESC 
                    LIMIT $perPage OFFSET $offset
                ");
                $files = $stmt->fetchAll();
                
                $response = [
                    'success' => true,
                    'data' => $files,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => ceil($total / $perPage)
                    ]
                ];
                break;
                
            case 'preview_changes':
                // Geçici değişiklikleri session'a kaydet
                if (isset($_POST['preview_data'])) {
                    $_SESSION['design_preview'] = $_POST['preview_data'];
                    $response = ['success' => true, 'message' => 'Önizleme hazır'];
                }
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Bilinmeyen işlem: ' . $action];
                break;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_sliders':
                $stmt = $pdo->query("SELECT * FROM design_sliders ORDER BY sort_order ASC, created_at DESC");
                $sliders = $stmt->fetchAll();
                $response = ['success' => true, 'data' => $sliders];
                break;
                
            case 'get_settings':
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM design_settings WHERE is_active = 1");
                $settings = [];
                while ($row = $stmt->fetch()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                $response = ['success' => true, 'data' => $settings];
                break;
                
            case 'get_content':
                $section = $_GET['section'] ?? '';
                if ($section) {
                    $stmt = $pdo->prepare("SELECT * FROM content_management WHERE section = ? ORDER BY key_name");
                    $stmt->execute([$section]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM content_management ORDER BY section, key_name");
                }
                $content = $stmt->fetchAll();
                $response = ['success' => true, 'data' => $content];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Bilinmeyen GET işlemi'];
                break;
        }
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
    
    // Log the error
    error_log("Design AJAX Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}

echo json_encode($response);
?>
