<?php
/**
 * Mr ECU - ECU Model Class
 * ECU veritabanı işlemleri için model sınıfı
 */

class EcuModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Tüm ECU'ları listele
     */
    public function getAllEcus($orderBy = 'name', $order = 'ASC') {
        try {
            $allowedOrderBy = ['name', 'created_at', 'updated_at'];
            $allowedOrder = ['ASC', 'DESC'];
            
            if (!in_array($orderBy, $allowedOrderBy)) $orderBy = 'name';
            if (!in_array($order, $allowedOrder)) $order = 'ASC';
            
            $stmt = $this->pdo->prepare("SELECT * FROM ecus ORDER BY {$orderBy} {$order}");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("EcuModel::getAllEcus - Found " . count($result) . " ECUs");
            return $result;
        } catch (Exception $e) {
            error_log("ECU listesi alınamadı: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ID ile ECU getir
     */
    public function getEcuById($id) {
        try {
            if (!isValidUUID($id)) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM ecus WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ECU bulunamadı (ID: {$id}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * İsim ile ECU getir
     */
    public function getEcuByName($name) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ecus WHERE name = ?");
            $stmt->execute([$name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ECU bulunamadı (Name: {$name}): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Yeni ECU ekle
     */
    public function addEcu($name) {
        try {
            $name = trim($name);
            if (empty($name)) {
                return ['success' => false, 'message' => 'ECU adı boş olamaz.'];
            }
            
            // Mevcut kontrolü
            if ($this->getEcuByName($name)) {
                return ['success' => false, 'message' => 'Bu ECU zaten mevcut.'];
            }
            
            $id = generateUUID();
            $stmt = $this->pdo->prepare("INSERT INTO ecus (id, name) VALUES (?, ?)");
            $stmt->execute([$id, $name]);
            
            return [
                'success' => true, 
                'message' => 'ECU başarıyla eklendi.',
                'id' => $id
            ];
            
        } catch (Exception $e) {
            error_log("ECU eklenemedi: " . $e->getMessage());
            return ['success' => false, 'message' => 'ECU eklenirken hata oluştu.'];
        }
    }
    
    /**
     * ECU güncelle
     */
    public function updateEcu($id, $name) {
        try {
            if (!isValidUUID($id)) {
                return ['success' => false, 'message' => 'Geçersiz ECU ID.'];
            }
            
            $name = trim($name);
            if (empty($name)) {
                return ['success' => false, 'message' => 'ECU adı boş olamaz.'];
            }
            
            // ECU var mı kontrol et
            if (!$this->getEcuById($id)) {
                return ['success' => false, 'message' => 'ECU bulunamadı.'];
            }
            
            // Aynı isimde başka ECU var mı
            $existing = $this->getEcuByName($name);
            if ($existing && $existing['id'] !== $id) {
                return ['success' => false, 'message' => 'Bu isimde başka bir ECU zaten mevcut.'];
            }
            
            $stmt = $this->pdo->prepare("UPDATE ecus SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$name, $id]);
            
            return ['success' => true, 'message' => 'ECU başarıyla güncellendi.'];
            
        } catch (Exception $e) {
            error_log("ECU güncellenemedi: " . $e->getMessage());
            return ['success' => false, 'message' => 'ECU güncellenirken hata oluştu.'];
        }
    }
    
    /**
     * ECU sil
     */
    public function deleteEcu($id) {
        try {
            if (!isValidUUID($id)) {
                return ['success' => false, 'message' => 'Geçersiz ECU ID.'];
            }
            
            // ECU var mı kontrol et
            if (!$this->getEcuById($id)) {
                return ['success' => false, 'message' => 'ECU bulunamadı.'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM ecus WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'ECU başarıyla silindi.'];
            
        } catch (Exception $e) {
            error_log("ECU silinemedi: " . $e->getMessage());
            return ['success' => false, 'message' => 'ECU silinirken hata oluştu.'];
        }
    }
    
    /**
     * ECU arama
     */
    public function searchEcus($searchTerm, $limit = 20) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            $stmt = $this->pdo->prepare("SELECT * FROM ecus WHERE LOWER(name) LIKE LOWER(?) ORDER BY name ASC LIMIT ?");
            $stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ECU araması yapılamadı: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Toplam ECU sayısı
     */
    public function getTotalEcuCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM ecus");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (Exception $e) {
            error_log("ECU sayısı alınamadı: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
 * Sayfalı ECU listesi
 */
public function getEcusPaginated($page = 1, $perPage = 20, $orderBy = 'name', $order = 'ASC') {
    try {
        $allowedOrderBy = ['name', 'created_at', 'updated_at'];
        $allowedOrder = ['ASC', 'DESC'];
        
        if (!in_array($orderBy, $allowedOrderBy)) $orderBy = 'name';
        if (!in_array($order, $allowedOrder)) $order = 'ASC';
        
        $offset = ($page - 1) * $perPage;
        
        // ⚠️ PDO'da LIMIT/OFFSET için PARAM_INT kullan
        $stmt = $this->pdo->prepare("SELECT * FROM ecus ORDER BY {$orderBy} {$order} LIMIT ? OFFSET ?");
        $stmt->bindValue(1, (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalCount = $this->getTotalEcuCount();
        
        error_log("EcuModel::getEcusPaginated - Found " . count($data) . " ECUs, total: " . $totalCount);
        
        return [
            'data' => $data,
            'total' => $totalCount,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($totalCount / $perPage)
        ];
    } catch (Exception $e) {
        error_log("Sayfalı ECU listesi alınamadı: " . $e->getMessage());
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
