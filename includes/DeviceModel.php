<?php
/**
 * Mr ECU - Device Model Class
 * Device veritabanı işlemleri için model sınıfı
 */

class DeviceModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Tüm device'ları listele
     */
    public function getAllDevices($orderBy = 'name', $order = 'ASC') {
        try {
            $allowedOrderBy = ['name', 'created_at', 'updated_at'];
            $allowedOrder = ['ASC', 'DESC'];
            
            if (!in_array($orderBy, $allowedOrderBy)) $orderBy = 'name';
            if (!in_array($order, $allowedOrder)) $order = 'ASC';
            
            $stmt = $this->pdo->prepare("SELECT * FROM devices ORDER BY {$orderBy} {$order}");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DeviceModel::getAllDevices - Found " . count($result) . " devices");
            return $result;
        } catch (Exception $e) {
            error_log("Device listesi alınamadı: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ID ile device getir
     */
    public function getDeviceById($id) {
        try {
            if (!isValidUUID($id)) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM devices WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Device bulunamadı (ID: {$id}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * İsim ile device getir
     */
    public function getDeviceByName($name) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM devices WHERE name = ?");
            $stmt->execute([$name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Device bulunamadı (Name: {$name}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Yeni device ekle
     */
    public function addDevice($name) {
        try {
            $name = trim($name);
            if (empty($name)) {
                return ['success' => false, 'message' => 'Device adı boş olamaz.'];
            }
            
            // Mevcut kontrolü
            if ($this->getDeviceByName($name)) {
                return ['success' => false, 'message' => 'Bu device zaten mevcut.'];
            }
            
            $id = generateUUID();
            $stmt = $this->pdo->prepare("INSERT INTO devices (id, name) VALUES (?, ?)");
            $stmt->execute([$id, $name]);
            
            return [
                'success' => true, 
                'message' => 'Device başarıyla eklendi.',
                'id' => $id
            ];
            
        } catch (Exception $e) {
            error_log("Device eklenemedi: " . $e->getMessage());
            return ['success' => false, 'message' => 'Device eklenirken hata oluştu.'];
        }
    }
    
    /**
     * Device güncelle
     */
    public function updateDevice($id, $name) {
        try {
            if (!isValidUUID($id)) {
                return ['success' => false, 'message' => 'Geçersiz device ID.'];
            }
            
            $name = trim($name);
            if (empty($name)) {
                return ['success' => false, 'message' => 'Device adı boş olamaz.'];
            }
            
            // Device var mı kontrol et
            if (!$this->getDeviceById($id)) {
                return ['success' => false, 'message' => 'Device bulunamadı.'];
            }
            
            // Aynı isimde başka device var mı
            $existing = $this->getDeviceByName($name);
            if ($existing && $existing['id'] !== $id) {
                return ['success' => false, 'message' => 'Bu isimde başka bir device zaten mevcut.'];
            }
            
            $stmt = $this->pdo->prepare("UPDATE devices SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$name, $id]);
            
            return ['success' => true, 'message' => 'Device başarıyla güncellendi.'];
            
        } catch (Exception $e) {
            error_log("Device güncellenemedi: " . $e->getMessage());
            return ['success' => false, 'message' => 'Device güncellenirken hata oluştu.'];
        }
    }
    
    /**
     * Device sil
     */
    public function deleteDevice($id) {
        try {
            if (!isValidUUID($id)) {
                return ['success' => false, 'message' => 'Geçersiz device ID.'];
            }
            
            // Device var mı kontrol et
            if (!$this->getDeviceById($id)) {
                return ['success' => false, 'message' => 'Device bulunamadı.'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM devices WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Device başarıyla silindi.'];
            
        } catch (Exception $e) {
            error_log("Device silinemedi: " . $e->getMessage());
            return ['success' => false, 'message' => 'Device silinirken hata oluştu.'];
        }
    }
    
    /**
     * Device arama
     */
    public function searchDevices($searchTerm, $limit = 20) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            $stmt = $this->pdo->prepare("SELECT * FROM devices WHERE LOWER(name) LIKE LOWER(?) ORDER BY name ASC LIMIT ?");
            $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Device araması yapılamadı: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Toplam device sayısı
     */
    public function getTotalDeviceCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM devices");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            error_log("Device sayısı alınamadı: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Sayfalı device listesi
     */
    public function getDevicesPaginated($page = 1, $perPage = 20, $orderBy = 'name', $order = 'ASC') {
        try {
            $allowedOrderBy = ['name', 'created_at', 'updated_at'];
            $allowedOrder = ['ASC', 'DESC'];
            
            if (!in_array($orderBy, $allowedOrderBy)) $orderBy = 'name';
            if (!in_array($order, $allowedOrder)) $order = 'ASC';
            
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->pdo->prepare("SELECT * FROM devices ORDER BY {$orderBy} {$order} LIMIT ? OFFSET ?");
            $stmt->bindValue(1, (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalCount = $this->getTotalDeviceCount();
            
            error_log("DeviceModel::getDevicesPaginated - Found " . count($data) . " devices, total: " . $totalCount);
            
            return [
                'data' => $data,
                'total' => $totalCount,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($totalCount / $perPage)
            ];
        } catch (Exception $e) {
            error_log("Sayfalı device listesi alınamadı: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'perPage' => $perPage,
                'totalPages' => 0
            ];
        }
    }
}
?>
