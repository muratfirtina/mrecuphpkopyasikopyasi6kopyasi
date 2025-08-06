<?php
/**
 * Araç Tuning Verilerini JSON'dan Veritabanına Import Eden Sistem
 * Mr ECU Projesi
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

class TuningDataImporter {
    private $pdo;
    private $stats = [
        'brands' => 0,
        'models' => 0,
        'series' => 0,
        'engines' => 0,
        'stages' => 0,
        'errors' => []
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Yakıt tipini normalize et
     */
    private function normalizeFuelType($fuel) {
        if (empty($fuel)) return 'Benzin';
        
        $fuel = trim(strtolower($fuel));
        
        // Türkçe normalizasyon
        $fuelMap = [
            'benzin' => 'Benzin',
            'petrol' => 'Benzin',
            'gasoline' => 'Benzin',
            'gas' => 'Benzin',
            'dizel' => 'Diesel',
            'diesel' => 'Diesel',
            'mazot' => 'Diesel',
            'gasoil' => 'Diesel',
            'hibrit' => 'Hybrid',
            'hybrid' => 'Hybrid',
            'elektrik' => 'Electric',
            'electric' => 'Electric',
            'lpg' => 'LPG',
            'cng' => 'CNG',
            'doğalgaz' => 'CNG',
            'bilinmeyen' => 'Unknown',
            'unknown' => 'Unknown'
        ];
        
        $normalized = $fuelMap[$fuel] ?? null;
        
        // Eğer mapping'de yoksa, ilk harfi büyük yapıp kontrol et
        if (!$normalized) {
            $fuelCapitalized = ucfirst($fuel);
            $allowedTypes = ['Benzin', 'Diesel', 'Hybrid', 'Electric', 'LPG', 'CNG', 'Unknown'];
            
            if (in_array($fuelCapitalized, $allowedTypes)) {
                $normalized = $fuelCapitalized;
            }
        }
        
        // Debug için log ekle
        if (!$normalized) {
            error_log("[TUNING DEBUG] Bilinmeyen yakıt tipi: '$fuel' - Benzin olarak ayarlanıyor");
        }
        
        // Son çare: varsayılan değer
        return $normalized ?? 'Benzin';
    }
    private function createSlug($text) {
        $turkishChars = [
            'ç' => 'c', 'Ç' => 'C',
            'ğ' => 'g', 'Ğ' => 'G',
            'ı' => 'i', 'I' => 'I',
            'İ' => 'i', 'i' => 'i',
            'ö' => 'o', 'Ö' => 'O',
            'ş' => 's', 'Ş' => 'S',
            'ü' => 'u', 'Ü' => 'U'
        ];
        
        $text = strtr($text, $turkishChars);
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\-\s]/', '', $text);
        $text = preg_replace('/[\s\-]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }

    /**
     * Marka ekleme/bulma
     */
    private function getBrandId($brandName) {
        $slug = $this->createSlug($brandName);
        
        // Önce var mı kontrol et
        $stmt = $this->pdo->prepare("SELECT id FROM brands WHERE name = ? OR slug = ?");
        $stmt->execute([$brandName, $slug]);
        $brand = $stmt->fetch();
        
        if ($brand) {
            return $brand['id'];
        }
        
        // Yoksa ekle - GUID oluştur
        $brandId = generateUUID();
        $stmt = $this->pdo->prepare("INSERT INTO brands (id, name, slug) VALUES (?, ?, ?)");
        $stmt->execute([$brandId, $brandName, $slug]);
        $this->stats['brands']++;
        
        return $brandId;
    }

    /**
     * Model ekleme/bulma
     */
    private function getModelId($brandId, $modelName) {
        $slug = $this->createSlug($modelName);
        
        // Önce var mı kontrol et
        $stmt = $this->pdo->prepare("SELECT id FROM models WHERE brand_id = ? AND name = ?");
        $stmt->execute([$brandId, $modelName]);
        $model = $stmt->fetch();
        
        if ($model) {
            return $model['id'];
        }
        
        // Yoksa ekle - GUID oluştur
        $modelId = generateUUID();
        $stmt = $this->pdo->prepare("INSERT INTO models (id, brand_id, name, slug) VALUES (?, ?, ?, ?)");
        $stmt->execute([$modelId, $brandId, $modelName, $slug]);
        $this->stats['models']++;
        
        return $modelId;
    }

    /**
     * Seri ekleme/bulma
     */
    private function getSeriesId($modelId, $yearRange) {
        $seriesName = $yearRange;
        $slug = $this->createSlug($yearRange);
        
        // Önce var mı kontrol et
        $stmt = $this->pdo->prepare("SELECT id FROM series WHERE model_id = ? AND year_range = ?");
        $stmt->execute([$modelId, $yearRange]);
        $series = $stmt->fetch();
        
        if ($series) {
            return $series['id'];
        }
        
        // Yoksa ekle - GUID oluştur
        $seriesId = generateUUID();
        $stmt = $this->pdo->prepare("INSERT INTO series (id, model_id, name, year_range, slug) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$seriesId, $modelId, $seriesName, $yearRange, $slug]);
        $this->stats['series']++;
        
        return $seriesId;
    }

    /**
     * Motor ekleme/bulma
     */
    private function getEngineId($seriesId, $engineName, $fuelType) {
        // Debug log ekle
        error_log("[TUNING DEBUG] Motor ekleniyor - Ad: '$engineName', Yakıt: '$fuelType', Seri ID: $seriesId");
        
        $slug = $this->createSlug($engineName);
        
        // Önce var mı kontrol et
        $stmt = $this->pdo->prepare("SELECT id FROM engines WHERE series_id = ? AND name = ?");
        $stmt->execute([$seriesId, $engineName]);
        $engine = $stmt->fetch();
        
        if ($engine) {
            return $engine['id'];
        }
        
        // Yoksa ekle - GUID oluştur
        try {
            $engineId = generateUUID();
            $stmt = $this->pdo->prepare("INSERT INTO engines (id, series_id, name, slug, fuel_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$engineId, $seriesId, $engineName, $slug, $fuelType]);
            $this->stats['engines']++;
            
            error_log("[TUNING DEBUG] Motor başarıyla eklendi - ID: $engineId");
            
            return $engineId;
        } catch (Exception $e) {
            error_log("[TUNING ERROR] Motor eklenirken hata: " . $e->getMessage());
            error_log("[TUNING ERROR] Parametreler - Seri: $seriesId, Motor: '$engineName', Yakıt: '$fuelType'");
            throw $e;
        }
    }

    /**
     * Stage ekleme
     */
    private function addStage($engineId, $stageName, $stageData) {
        // Önce var mı kontrol et
        $stmt = $this->pdo->prepare("SELECT id FROM stages WHERE engine_id = ? AND stage_name = ?");
        $stmt->execute([$engineId, $stageName]);
        $existingStage = $stmt->fetch();
        
        if ($existingStage) {
            // Güncelle
            $stmt = $this->pdo->prepare("
                UPDATE stages SET 
                    fullname = ?, 
                    original_power = ?, 
                    tuning_power = ?, 
                    difference_power = ?, 
                    original_torque = ?, 
                    tuning_torque = ?, 
                    difference_torque = ?, 
                    ecu = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE engine_id = ? AND stage_name = ?
            ");
            $stmt->execute([
                $stageData['fullname'],
                $stageData['original_power'],
                $stageData['tuning_power'],
                $stageData['difference_power'],
                $stageData['original_torque'],
                $stageData['tuning_torque'],
                $stageData['difference_torque'],
                $stageData['ECU'],
                $engineId,
                $stageName
            ]);
            return $existingStage['id'];
        }
        
        // Yeni ekle - GUID oluştur
        $stageId = generateUUID();
        $stmt = $this->pdo->prepare("
            INSERT INTO stages (
                id, engine_id, stage_name, fullname, original_power, tuning_power, 
                difference_power, original_torque, tuning_torque, difference_torque, ecu
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $stageId,
            $engineId,
            $stageName,
            $stageData['fullname'],
            $stageData['original_power'],
            $stageData['tuning_power'],
            $stageData['difference_power'],
            $stageData['original_torque'],
            $stageData['tuning_torque'],
            $stageData['difference_torque'],
            $stageData['ECU']
        ]);
        
        $this->stats['stages']++;
        return $stageId;
    }

    /**
     * JSON string'den import
     */
    public function importFromJsonString($jsonString) {
        try {
            $data = json_decode($jsonString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON Parse Hatası: ' . json_last_error_msg());
            }
            
            return $this->importFromArray($data);
            
        } catch (Exception $e) {
            $this->stats['errors'][] = 'JSON Import Hatası: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Array'den import
     */
    public function importFromArray($data) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($data as $brandName => $models) {
                $brandId = $this->getBrandId($brandName);
                
                foreach ($models as $modelName => $series) {
                    $modelId = $this->getModelId($brandId, $modelName);
                    
                    foreach ($series as $yearRange => $engines) {
                        $seriesId = $this->getSeriesId($modelId, $yearRange);
                        
                        foreach ($engines as $engineName => $stages) {
                            // Yakıt tipini belirle ve normalize et
                            $fuelType = 'Benzin'; // Varsayılan
                            if (isset($stages['Stage1']['fuel'])) {
                                $fuelType = $this->normalizeFuelType($stages['Stage1']['fuel']);
                            }
                            
                            $engineId = $this->getEngineId($seriesId, $engineName, $fuelType);
                            
                            foreach ($stages as $stageName => $stageData) {
                                // Boş veriler varsa atla
                                if (empty($stageData['fullname']) || $stageData['original_power'] == 0) {
                                    continue;
                                }
                                
                                $this->addStage($engineId, $stageName, $stageData);
                            }
                        }
                    }
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->stats['errors'][] = 'Import Hatası: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * İstatistikleri getir
     */
    public function getStats() {
        return $this->stats;
    }

    /**
     * Veritabanını temizle
     */
    public function clearDatabase() {
        try {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->pdo->exec("TRUNCATE TABLE stages");
            $this->pdo->exec("TRUNCATE TABLE engines");
            $this->pdo->exec("TRUNCATE TABLE series");
            $this->pdo->exec("TRUNCATE TABLE models");
            $this->pdo->exec("TRUNCATE TABLE brands");
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            return true;
        } catch (Exception $e) {
            $this->stats['errors'][] = 'Temizleme Hatası: ' . $e->getMessage();
            return false;
        }
    }
}

// Eğer bu dosya direkt çalıştırılırsa
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        echo "<!DOCTYPE html>
        <html lang='tr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Araç Tuning Verileri Import</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
                .btn:hover { background: #0056b3; }
                .btn-danger { background: #dc3545; }
                .btn-danger:hover { background: #c82333; }
                .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
                .stats { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                textarea { width: 100%; height: 200px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; padding: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>🚗 Araç Tuning Verileri Import Sistemi</h1>";

        $importer = new TuningDataImporter($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['clear_database'])) {
                if ($importer->clearDatabase()) {
                    echo "<div class='success'>✅ Veritabanı başarıyla temizlendi!</div>";
                } else {
                    echo "<div class='error'>❌ Veritabanı temizlenirken hata oluştu!</div>";
                }
            } 
            elseif (isset($_POST['import_sample'])) {
                // Örnek JSON verisini kullan
                $sampleJsonFile = __DIR__ . '/sample-tuning-data.json';
                
                if (file_exists($sampleJsonFile)) {
                    $sampleJson = file_get_contents($sampleJsonFile);
                    
                    if ($importer->importFromJsonString($sampleJson)) {
                        echo "<div class='success'>✅ Örnek veriler başarıyla import edildi!</div>";
                    } else {
                        echo "<div class='error'>❌ Import sırasında hata oluştu!</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Örnek JSON dosyası bulunamadı!</div>";
                }
                
                $stats = $importer->getStats();
                echo "<div class='stats'>
                        <h3>📊 Import İstatistikleri:</h3>
                        <ul>
                            <li>Markalar: {$stats['brands']}</li>
                            <li>Modeller: {$stats['models']}</li>
                            <li>Seriler: {$stats['series']}</li>
                            <li>Motorlar: {$stats['engines']}</li>
                            <li>Stage'ler: {$stats['stages']}</li>
                        </ul>";
                
                if (!empty($stats['errors'])) {
                    echo "<h4>❌ Hatalar:</h4><ul>";
                    foreach ($stats['errors'] as $error) {
                        echo "<li>{$error}</li>";
                    }
                    echo "</ul>";
                }
                echo "</div>";
            }
            elseif (isset($_POST['import_custom']) && !empty($_POST['json_data'])) {
                $jsonData = trim($_POST['json_data']);
                
                if ($importer->importFromJsonString($jsonData)) {
                    echo "<div class='success'>✅ Özel veriler başarıyla import edildi!</div>";
                } else {
                    echo "<div class='error'>❌ Import sırasında hata oluştu!</div>";
                }
                
                $stats = $importer->getStats();
                echo "<div class='stats'>
                        <h3>📊 Import İstatistikleri:</h3>
                        <ul>
                            <li>Markalar: {$stats['brands']}</li>
                            <li>Modeller: {$stats['models']}</li>
                            <li>Seriler: {$stats['series']}</li>
                            <li>Motorlar: {$stats['engines']}</li>
                            <li>Stage'ler: {$stats['stages']}</li>
                        </ul>";
                
                if (!empty($stats['errors'])) {
                    echo "<h4>❌ Hatalar:</h4><ul>";
                    foreach ($stats['errors'] as $error) {
                        echo "<li>{$error}</li>";
                    }
                    echo "</ul>";
                }
                echo "</div>";
            }
        }

        echo "
                <form method='post'>
                    <h3>⚡ Hızlı İşlemler</h3>
                    <button type='submit' name='import_sample' class='btn'>📁 Örnek Veriyi Import Et</button>
                    <button type='submit' name='clear_database' class='btn btn-danger' onclick='return confirm(\"Tüm tuning verileri silinecek! Emin misiniz?\")'>🗑️ Veritabanını Temizle</button>
                </form>

                <form method='post'>
                    <h3>📝 Özel JSON Verisi Import Et</h3>
                    <textarea name='json_data' placeholder='JSON verilerinizi buraya yapıştırın...'></textarea>
                    <br>
                    <button type='submit' name='import_custom' class='btn'>🚀 Import Et</button>
                </form>

                <div style='margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 5px;'>
                    <h3>📋 Kullanım Talimatları</h3>
                    <ol>
                        <li>Önce veritabanı tablolarını oluşturun: <code>config/install-tuning-system.sql</code></li>
                        <li>Örnek veriyi import etmek için \"Örnek Veriyi Import Et\" butonuna tıklayın</li>
                        <li>Kendi JSON verilerinizi eklemek için textarea'ya yapıştırıp \"Import Et\" butonuna tıklayın</li>
                        <li>İhtiyaç halinde veritabanını temizleyebilirsiniz</li>
                    </ol>
                </div>
            </div>
        </body>
        </html>";

    } catch (Exception $e) {
        echo "<div class='error'>Sistem Hatası: " . $e->getMessage() . "</div>";
    }
}
?>