<?php
/**
 * Mr ECU - Legacy Files Manager
 * Eski dosyaları yönetmek için sınıf
 */

class LegacyFilesManager
{
    private $pdo;
    private $uploadPath;

    public function __construct($database)
    {
        $this->pdo = $database;
        $this->uploadPath = '../uploads/legacy_files/';
        
        // Upload klasörünü oluştur
        if (!file_exists($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                error_log('Legacy files upload klasörü oluşturulamadı: ' . $this->uploadPath);
            }
        }
        
        // Tablo var mı kontrol et
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'legacy_files'");
            if ($stmt->rowCount() === 0) {
                error_log('Legacy files tablosu bulunamadı!');
            }
        } catch (Exception $e) {
            error_log('Legacy files tablo kontrol hatası: ' . $e->getMessage());
        }
    }

    /**
     * Kullanıcıya ait tüm eski dosyaları getir (plaka bazında gruplu)
     * Filtreleme ve arama özellikleri ile
     */
    public function getUserLegacyFiles($userId, $plateSearch = '', $fileTypeFilter = '', $sortBy = 'date_desc')
    {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }

            // Base SQL
            $sql = "
                SELECT plate_number, 
                       COUNT(*) as file_count,
                       GROUP_CONCAT(id) as file_ids,
                       MAX(upload_date) as last_upload
                FROM legacy_files 
                WHERE user_id = ?
            ";
            
            $params = [$userId];
            
            // Plaka arama filtresi
            if (!empty($plateSearch)) {
                $sql .= " AND plate_number LIKE ?";
                $params[] = "%{$plateSearch}%";
            }
            
            // Dosya tipi filtresi
            if (!empty($fileTypeFilter)) {
                $sql .= " AND original_filename LIKE ?";
                $params[] = "%.{$fileTypeFilter}";
            }
            
            $sql .= " GROUP BY plate_number";
            
            // Sıralama
            switch ($sortBy) {
                case 'date_asc':
                    $sql .= " ORDER BY last_upload ASC";
                    break;
                case 'plate_asc':
                    $sql .= " ORDER BY plate_number ASC";
                    break;
                case 'plate_desc':
                    $sql .= " ORDER BY plate_number DESC";
                    break;
                case 'files_desc':
                    $sql .= " ORDER BY file_count DESC";
                    break;
                default: // date_desc
                    $sql .= " ORDER BY last_upload DESC";
                    break;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Güvenlik kontrolü
            return is_array($result) ? $result : [];
        } catch (PDOException $e) {
            error_log('getUserLegacyFiles error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Belirli bir plakaya ait dosyaları getir
     */
    public function getPlateFiles($userId, $plateNumber)
    {
        try {
            if (!isValidUUID($userId)) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT lf.*, 
                       u.username as uploaded_by_name
                FROM legacy_files lf
                LEFT JOIN users u ON lf.uploaded_by_admin = u.id
                WHERE lf.user_id = ? AND lf.plate_number = ?
                ORDER BY lf.upload_date DESC
            ");
            $stmt->execute([$userId, $plateNumber]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Güvenlik kontrolü
            return is_array($result) ? $result : [];
        } catch (PDOException $e) {
            error_log('getPlateFiles error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Admin için dosya yükleme
     */
    public function uploadFileForUser($userId, $plateNumber, $files, $adminId)
    {
        try {
            if (!isValidUUID($userId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz kullanıcı ID formatı'];
            }

            $uploadedFiles = [];
            $errors = [];

            // Kullanıcı klasörünü oluştur
            $userFolder = $this->uploadPath . $userId . '/';
            if (!file_exists($userFolder)) {
                if (!mkdir($userFolder, 0755, true)) {
                    return ['success' => false, 'message' => 'Kullanıcı klasörü oluşturulamadı'];
                }
            }

            // Plaka klasörünü oluştur
            $plateFolder = $userFolder . $plateNumber . '/';
            if (!file_exists($plateFolder)) {
                if (!mkdir($plateFolder, 0755, true)) {
                    return ['success' => false, 'message' => 'Plaka klasörü oluşturulamadı'];
                }
            }

            foreach ($files['tmp_name'] as $index => $tmpName) {
                if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Dosya yükleme hatası: ' . $files['name'][$index];
                    continue;
                }

                $originalName = $files['name'][$index];
                $fileSize = $files['size'][$index];
                $fileType = $files['type'][$index];

                // Güvenli dosya adı oluştur
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $safeFileName = $plateNumber . '_' . time() . '_' . uniqid() . '.' . $extension;
                $filePath = $plateFolder . $safeFileName;

                if (move_uploaded_file($tmpName, $filePath)) {
                    // Veritabanına kaydet
                    $fileId = generateUUID();
                    $stmt = $this->pdo->prepare("
                        INSERT INTO legacy_files 
                        (id, user_id, plate_number, original_filename, stored_filename, file_path, file_size, file_type, uploaded_by_admin, upload_date, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $currentDateTime = date('Y-m-d H:i:s');
                    $result = $stmt->execute([
                        $fileId,
                        $userId,
                        $plateNumber,
                        $originalName,
                        $safeFileName,
                        $filePath,
                        $fileSize,
                        $fileType,
                        $adminId,
                        $currentDateTime,
                        $currentDateTime
                    ]);

                    if ($result) {
                        $uploadedFiles[] = $originalName;
                    } else {
                        $errors[] = 'Veritabanı kayıt hatası: ' . $originalName;
                        unlink($filePath); // Başarısız kaydedilenleri sil
                    }
                } else {
                    $errors[] = 'Dosya taşıma hatası: ' . $originalName;
                }
            }

            if (count($uploadedFiles) > 0) {
                return [
                    'success' => true, 
                    'message' => count($uploadedFiles) . ' dosya başarıyla yüklendi.',
                    'uploaded_files' => $uploadedFiles,
                    'errors' => $errors
                ];
            } else {
                return [
                    'success' => false, 
                    'message' => 'Hiçbir dosya yüklenemedi.',
                    'errors' => $errors
                ];
            }

        } catch (Exception $e) {
            error_log('uploadFileForUser error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Dosya yükleme sırasında hata oluştu: ' . $e->getMessage()];
        }
    }

    /**
     * Dosya indirme
     */
    public function downloadFile($fileId, $userId)
    {
        try {
            if (!isValidUUID($fileId) || !isValidUUID($userId)) {
                return ['success' => false, 'message' => 'Geçersiz dosya ID'];
            }

            $stmt = $this->pdo->prepare("
                SELECT * FROM legacy_files 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$fileId, $userId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                return ['success' => false, 'message' => 'Dosya bulunamadı'];
            }

            if (!file_exists($file['file_path'])) {
                return ['success' => false, 'message' => 'Dosya sistemde bulunamadı'];
            }

            return [
                'success' => true,
                'file' => $file
            ];

        } catch (PDOException $e) {
            error_log('downloadFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Dosya indirme hatası'];
        }
    }

    /**
     * Admin için tüm kullanıcıları ve dosya sayılarını listele
     */
    public function getAllUsersWithLegacyFiles()
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.first_name, u.last_name,
                       COUNT(lf.id) as legacy_file_count,
                       COUNT(DISTINCT lf.plate_number) as plate_count,
                       MAX(lf.upload_date) as last_upload
                FROM users u
                LEFT JOIN legacy_files lf ON u.id = lf.user_id
                WHERE u.role = 'user'
                GROUP BY u.id
                ORDER BY legacy_file_count DESC, u.username ASC
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Güvenlik kontrolü
            return is_array($result) ? $result : [];
        } catch (PDOException $e) {
            error_log('getAllUsersWithLegacyFiles error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kullanıcı var mı kontrol et
     */
    public function userExists($userId)
    {
        try {
            if (!isValidUUID($userId)) {
                return false;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'user'");
            $stmt->execute([$userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Dosya silme (admin)
     */
    public function deleteFile($fileId, $adminId)
    {
        try {
            if (!isValidUUID($fileId) || !isValidUUID($adminId)) {
                return ['success' => false, 'message' => 'Geçersiz ID formatı'];
            }

            // Dosya bilgilerini al
            $stmt = $this->pdo->prepare("SELECT * FROM legacy_files WHERE id = ?");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                return ['success' => false, 'message' => 'Dosya bulunamadı'];
            }

            // Fiziksel dosyayı sil
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Veritabanından sil
            $stmt = $this->pdo->prepare("DELETE FROM legacy_files WHERE id = ?");
            $result = $stmt->execute([$fileId]);

            if ($result) {
                return ['success' => true, 'message' => 'Dosya başarıyla silindi'];
            } else {
                return ['success' => false, 'message' => 'Dosya silinirken hata oluştu'];
            }

        } catch (Exception $e) {
            error_log('deleteFile error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Dosya silme hatası'];
        }
    }

    /**
     * İstatistikler
     */
    public function getStats()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_files,
                    COUNT(DISTINCT user_id) as users_with_files,
                    COUNT(DISTINCT plate_number) as total_plates,
                    SUM(file_size) as total_size
                FROM legacy_files
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getStats error: ' . $e->getMessage());
            return [
                'total_files' => 0,
                'users_with_files' => 0,
                'total_plates' => 0,
                'total_size' => 0
            ];
        }
    }
}
