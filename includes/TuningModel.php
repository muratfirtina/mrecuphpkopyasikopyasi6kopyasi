<?php
/**
 * Araç Tuning Verilerini Yöneten Model Sınıfı
 * Mr ECU Projesi
 */

class TuningModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Tüm markaları getir
     */
    public function getAllBrands() {
        $stmt = $this->pdo->query("
            SELECT b.*, COUNT(m.id) as model_count 
            FROM brands b 
            LEFT JOIN models m ON b.id = m.brand_id 
            GROUP BY b.id 
            ORDER BY b.name
        ");
        return $stmt->fetchAll();
    }

    /**
     * Markaya göre modelleri getir
     */
    public function getModelsByBrand($brandId) {
        $stmt = $this->pdo->prepare("
            SELECT m.*, COUNT(sr.id) as series_count 
            FROM models m 
            LEFT JOIN series sr ON m.id = sr.model_id 
            WHERE m.brand_id = ? 
            GROUP BY m.id 
            ORDER BY m.name
        ");
        $stmt->execute([$brandId]);
        return $stmt->fetchAll();
    }

    /**
     * Modele göre serileri getir
     */
    public function getSeriesByModel($modelId) {
        $stmt = $this->pdo->prepare("
            SELECT sr.*, COUNT(e.id) as engine_count 
            FROM series sr 
            LEFT JOIN engines e ON sr.id = e.series_id 
            WHERE sr.model_id = ? 
            GROUP BY sr.id 
            ORDER BY sr.year_range
        ");
        $stmt->execute([$modelId]);
        return $stmt->fetchAll();
    }

    /**
     * Seriye göre motorları getir
     */
    public function getEnginesBySeries($seriesId) {
        $stmt = $this->pdo->prepare("
            SELECT e.*, COUNT(s.id) as stage_count 
            FROM engines e 
            LEFT JOIN stages s ON e.id = s.engine_id AND s.is_active = 1 
            WHERE e.series_id = ? 
            GROUP BY e.id 
            ORDER BY e.name
        ");
        $stmt->execute([$seriesId]);
        return $stmt->fetchAll();
    }

    /**
     * Motora göre stage'leri getir
     */
    public function getStagesByEngine($engineId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM stages 
            WHERE engine_id = ? AND is_active = 1 
            ORDER BY stage_name
        ");
        $stmt->execute([$engineId]);
        return $stmt->fetchAll();
    }

    /**
     * Detaylı arama - tüm bilgilerle
     */
    public function searchDetailed($filters = []) {
        $sql = "SELECT * FROM tuning_search_view WHERE 1=1";
        $params = [];

        if (!empty($filters['brand'])) {
            $sql .= " AND brand_name LIKE ?";
            $params[] = "%{$filters['brand']}%";
        }

        if (!empty($filters['model'])) {
            $sql .= " AND model_name LIKE ?";
            $params[] = "%{$filters['model']}%";
        }

        if (!empty($filters['engine'])) {
            $sql .= " AND engine_name LIKE ?";
            $params[] = "%{$filters['engine']}%";
        }

        if (!empty($filters['fuel_type'])) {
            $sql .= " AND fuel_type = ?";
            $params[] = $filters['fuel_type'];
        }

        if (!empty($filters['min_power'])) {
            $sql .= " AND original_power >= ?";
            $params[] = $filters['min_power'];
        }

        if (!empty($filters['max_power'])) {
            $sql .= " AND original_power <= ?";
            $params[] = $filters['max_power'];
        }

        if (!empty($filters['year_range'])) {
            $sql .= " AND year_range LIKE ?";
            $params[] = "%{$filters['year_range']}%";
        }

        if (!empty($filters['search_text'])) {
            $sql .= " AND (search_text LIKE ? OR fullname LIKE ? OR ecu LIKE ?)";
            $searchTerm = "%{$filters['search_text']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY brand_name, model_name, year_range, engine_name";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Popüler motorları getir (en çok stage'i olanlar)
     */
    public function getPopularEngines($limit = 10) {
        $limit = (int)$limit; // Integer'a çevir
        $stmt = $this->pdo->query("
            SELECT 
                e.*,
                b.name as brand_name,
                m.name as model_name,
                sr.year_range,
                COUNT(s.id) as stage_count,
                AVG(s.difference_power) as avg_power_gain,
                MAX(s.tuning_power) as max_tuning_power
            FROM engines e
            JOIN series sr ON e.series_id = sr.id
            JOIN models m ON sr.model_id = m.id
            JOIN brands b ON m.brand_id = b.id
            LEFT JOIN stages s ON e.id = s.engine_id AND s.is_active = 1
            GROUP BY e.id
            HAVING stage_count > 0
            ORDER BY stage_count DESC, avg_power_gain DESC
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }

    /**
     * En yüksek güç artışlarını getir
     */
    public function getHighestPowerGains($limit = 10) {
        $limit = (int)$limit; // Integer'a çevir
        $stmt = $this->pdo->query("
            SELECT * FROM tuning_search_view 
            WHERE difference_power > 0 
            ORDER BY difference_power DESC 
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }

    /**
     * Yakıt tipine göre istatistikler
     */
    public function getFuelTypeStats() {
        $stmt = $this->pdo->query("
            SELECT 
                fuel_type,
                COUNT(*) as total_engines,
                AVG(original_power) as avg_original_power,
                AVG(tuning_power) as avg_tuning_power,
                AVG(difference_power) as avg_power_gain,
                MAX(difference_power) as max_power_gain
            FROM tuning_search_view 
            GROUP BY fuel_type 
            ORDER BY total_engines DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Marka bazında istatistikler
     */
    public function getBrandStats() {
        $stmt = $this->pdo->query("
            SELECT 
                brand_name,
                COUNT(DISTINCT model_name) as model_count,
                COUNT(DISTINCT engine_name) as engine_count,
                COUNT(*) as total_stages,
                AVG(difference_power) as avg_power_gain,
                MAX(difference_power) as max_power_gain
            FROM tuning_search_view 
            GROUP BY brand_name 
            ORDER BY total_stages DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Belirli bir stage'i ID ile getir (GUID destekli)
     */
    public function getStageById($stageId) {
        // GUID formatını kontrol et
        if (!isValidUUID($stageId)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM tuning_search_view WHERE stage_id = ?");
        $stmt->execute([$stageId]);
        return $stmt->fetch();
    }

    /**
     * Fiyat güncellemesi (GUID destekli)
     */
    public function updateStagePrice($stageId, $price) {
        // GUID formatını kontrol et
        if (!isValidUUID($stageId)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("UPDATE stages SET price = ? WHERE id = ?");
        return $stmt->execute([$price, $stageId]);
    }

    /**
     * Stage aktif/pasif yapma (GUID destekli)
     */
    public function toggleStageStatus($stageId) {
        // GUID formatını kontrol et
        if (!isValidUUID($stageId)) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("UPDATE stages SET is_active = !is_active WHERE id = ?");
        return $stmt->execute([$stageId]);
    }

    /**
     * Benzer motorları bul (aynı güç aralığında) - GUID destekli
     */
    public function getSimilarEngines($stageId, $powerRange = 20) {
        // GUID formatını kontrol et
        if (!isValidUUID($stageId)) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT similar.* FROM tuning_search_view main
            JOIN tuning_search_view similar ON (
                similar.original_power BETWEEN (main.original_power - ?) AND (main.original_power + ?) 
                AND similar.fuel_type = main.fuel_type 
                AND similar.stage_id != main.stage_id
            )
            WHERE main.stage_id = ?
            ORDER BY ABS(similar.original_power - main.original_power)
            LIMIT 10
        ");
        $stmt->execute([$powerRange, $powerRange, $stageId]);
        return $stmt->fetchAll();
    }

    /**
     * En son eklenen stage'ler
     */
    public function getLatestStages($limit = 10) {
        $limit = (int)$limit; // Integer'a çevir
        $stmt = $this->pdo->query("
            SELECT * FROM tuning_search_view 
            ORDER BY stage_id DESC 
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }

    /**
     * JSON formatında tüm veriyi export et
     */
    public function exportToJson() {
        $result = [];
        
        $brands = $this->getAllBrands();
        
        foreach ($brands as $brand) {
            $brandName = $brand['name'];
            $result[$brandName] = [];
            
            $models = $this->getModelsByBrand($brand['id']);
            
            foreach ($models as $model) {
                $modelName = $model['name'];
                $result[$brandName][$modelName] = [];
                
                $series = $this->getSeriesByModel($model['id']);
                
                foreach ($series as $serie) {
                    $yearRange = $serie['year_range'];
                    $result[$brandName][$modelName][$yearRange] = [];
                    
                    $engines = $this->getEnginesBySeries($serie['id']);
                    
                    foreach ($engines as $engine) {
                        $engineName = $engine['name'];
                        $result[$brandName][$modelName][$yearRange][$engineName] = [];
                        
                        $stages = $this->getStagesByEngine($engine['id']);
                        
                        foreach ($stages as $stage) {
                            $result[$brandName][$modelName][$yearRange][$engineName][$stage['stage_name']] = [
                                'fullname' => $stage['fullname'],
                                'original_power' => (int)$stage['original_power'],
                                'tuning_power' => (int)$stage['tuning_power'],
                                'difference_power' => (int)$stage['difference_power'],
                                'original_torque' => (int)$stage['original_torque'],
                                'tuning_torque' => (int)$stage['tuning_torque'],
                                'difference_torque' => (int)$stage['difference_torque'],
                                'fuel' => $engine['fuel_type'],
                                'ECU' => $stage['ecu']
                            ];
                        }
                    }
                }
            }
        }
        
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>