<?php
/**
 * Mr ECU - Marka Sistemi İyileştirme Önerileri
 * Bu dosya product-brands.php için ek güvenlik önerilerini içerir
 */

// 1. Input Validation İyileştirmesi
function validateBrandInput($data) {
    $errors = [];
    
    // Marka adı kontrolü
    if (empty(trim($data['name']))) {
        $errors[] = 'Marka adı boş olamaz';
    } elseif (strlen(trim($data['name'])) < 2) {
        $errors[] = 'Marka adı en az 2 karakter olmalıdır';
    }
    
    // Website URL kontrolü
    if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Geçersiz website URL\'si';
    }
    
    // Sort order kontrolü
    if (!is_numeric($data['sort_order']) || $data['sort_order'] < 0) {
        $errors[] = 'Sıralama değeri geçerli bir pozitif sayı olmalıdır';
    }
    
    return $errors;
}

// 2. Güvenli Dosya İşlemleri
function safeDeleteFile($filePath) {
    try {
        if (file_exists($filePath)) {
            // Dosya türü kontrolü
            $allowedDirs = ['uploads/brands/', 'uploads/temp/'];
            $isAllowed = false;
            
            foreach ($allowedDirs as $dir) {
                if (strpos($filePath, $dir) !== false) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if ($isAllowed && unlink($filePath)) {
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("Dosya silme hatası: " . $e->getMessage());
        return false;
    }
}

// 3. Gelişmiş Hata Yönetimi
function handleDatabaseError($e, $operation = 'bilinmeyen') {
    // Hata logu
    error_log("Database Error [$operation]: " . $e->getMessage());
    
    // Kullanıcı dostu mesaj
    switch ($operation) {
        case 'insert':
            return 'Marka eklenirken bir hata oluştu. Lütfen tekrar deneyin.';
        case 'update':
            return 'Marka güncellenirken bir hata oluştu. Lütfen tekrar deneyin.';
        case 'delete':
            return 'Marka silinirken bir hata oluştu. Bu markaya bağlı ürünler olabilir.';
        default:
            return 'Bir hata oluştu. Lütfen tekrar deneyin.';
    }
}

// 4. Benzersizlik Kontrolü
function checkBrandUniqueness($pdo, $name, $excludeId = null) {
    $sql = "SELECT id FROM product_brands WHERE name = ?";
    $params = [$name];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch() === false; // true = benzersiz, false = zaten var
}

// 5. Güvenli Slug Oluşturma
function createSafeSlug($text, $pdo, $excludeId = null) {
    $baseSlug = createSlug($text); // Mevcut fonksiyonu kullan
    $slug = $baseSlug;
    $counter = 1;
    
    // Benzersiz slug oluştur
    while (!isSlugUnique($pdo, $slug, $excludeId)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

function isSlugUnique($pdo, $slug, $excludeId = null) {
    $sql = "SELECT id FROM product_brands WHERE slug = ?";
    $params = [$slug];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch() === false;
}

// 6. Güvenli Logo Yükleme
function uploadBrandLogo($file, $oldLogoPath = null) {
    try {
        // Dosya var mı kontrolü
        if (!isset($file['tmp_name']) || !$file['tmp_name']) {
            return null;
        }
        
        // Hata kontrolü
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Dosya yükleme hatası: ' . $file['error']);
        }
        
        // Güvenlik kontrolleri
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Sadece JPG, PNG, GIF ve WEBP formatları desteklenir.');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Dosya boyutu 5MB\'dan büyük olamaz.');
        }
        
        // Dosya içeriği kontrolü (gerçek MIME type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($realMimeType, $allowedTypes)) {
            throw new Exception('Geçersiz dosya formatı.');
        }
        
        // Upload dizini oluştur
        $uploadDir = '../uploads/brands/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Benzersiz dosya adı oluştur
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'brand_' . uniqid() . '_' . time() . '.' . strtolower($extension);
        $filepath = $uploadDir . $filename;
        
        // Dosyayı taşı
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Eski dosyayı sil
            if ($oldLogoPath) {
                safeDeleteFile('../' . $oldLogoPath);
            }
            
            return 'uploads/brands/' . $filename;
        }
        
        throw new Exception('Dosya yüklenirken hata oluştu.');
        
    } catch (Exception $e) {
        error_log("Logo yükleme hatası: " . $e->getMessage());
        throw $e;
    }
}

?>

<!-- 
KURULUM TALİMATLARI:

1. Mevcut product-brands.php dosyasında aşağıdaki değişiklikleri yapın:

2. Input validation ekleyin:
   - Form gönderilmeden önce validateBrandInput() fonksiyonunu kullanın

3. Benzersizlik kontrolü ekleyin:
   - Marka adı ve slug için checkBrandUniqueness() kullanın

4. Hata yönetimini iyileştirin:
   - try-catch blokları için handleDatabaseError() kullanın

5. Logo yükleme fonksiyonunu değiştirin:
   - uploadLogo() yerine uploadBrandLogo() kullanın

6. Slug oluşturmayı iyileştirin:
   - createSlug() yerine createSafeSlug() kullanın

ÖRNEK KULLANIM:

// Form gönderme kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $data = [
        'name' => sanitize($_POST['name']),
        'website' => sanitize($_POST['website']),
        'sort_order' => $_POST['sort_order']
    ];
    
    // Validation
    $validationErrors = validateBrandInput($data);
    if (!empty($validationErrors)) {
        $error = implode('<br>', $validationErrors);
    } else {
        // Benzersizlik kontrolü
        if (!checkBrandUniqueness($pdo, $data['name'])) {
            $error = 'Bu marka adı zaten kullanımda.';
        } else {
            try {
                // Logo yükleme
                $logoPath = null;
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $logoPath = uploadBrandLogo($_FILES['logo']);
                }
                
                // Güvenli slug oluştur
                $slug = createSafeSlug($data['name'], $pdo);
                
                // Veritabanına kaydet...
                
            } catch(Exception $e) {
                $error = handleDatabaseError($e, 'insert');
            }
        }
    }
}
-->
