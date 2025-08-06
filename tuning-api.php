<?php
/**
 * Araç Tuning API
 * Mr ECU Projesi
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/TuningModel.php';

try {
    $tuning = new TuningModel($pdo);
    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'data' => null, 'message' => ''];

    switch ($action) {
        case 'brands':
            $response['data'] = $tuning->getAllBrands();
            $response['success'] = true;
            $response['message'] = 'Markalar başarıyla getirildi';
            break;

        case 'models':
            $brandId = $_GET['brand_id'] ?? '';
            if (empty($brandId)) {
                $response['message'] = 'Marka ID gerekli';
            } elseif (!isValidUUID($brandId)) {
                $response['message'] = 'Geçersiz marka GUID formatı';
            } else {
                $response['data'] = $tuning->getModelsByBrand($brandId);
                $response['success'] = true;
                $response['message'] = 'Modeller başarıyla getirildi';
            }
            break;

        case 'series':
            $modelId = $_GET['model_id'] ?? '';
            if (empty($modelId)) {
                $response['message'] = 'Model ID gerekli';
            } elseif (!isValidUUID($modelId)) {
                $response['message'] = 'Geçersiz model GUID formatı';
            } else {
                $response['data'] = $tuning->getSeriesByModel($modelId);
                $response['success'] = true;
                $response['message'] = 'Seriler başarıyla getirildi';
            }
            break;

        case 'engines':
            $seriesId = $_GET['series_id'] ?? '';
            if (empty($seriesId)) {
                $response['message'] = 'Seri ID gerekli';
            } elseif (!isValidUUID($seriesId)) {
                $response['message'] = 'Geçersiz seri GUID formatı';
            } else {
                $response['data'] = $tuning->getEnginesBySeries($seriesId);
                $response['success'] = true;
                $response['message'] = 'Motorlar başarıyla getirildi';
            }
            break;

        case 'stages':
            $engineId = $_GET['engine_id'] ?? '';
            if (empty($engineId)) {
                $response['message'] = 'Motor ID gerekli';
            } elseif (!isValidUUID($engineId)) {
                $response['message'] = 'Geçersiz motor GUID formatı';
            } else {
                $response['data'] = $tuning->getStagesByEngine($engineId);
                $response['success'] = true;
                $response['message'] = 'Stage\'ler başarıyla getirildi';
            }
            break;

        case 'search':
            $filters = [
                'brand' => $_GET['brand'] ?? '',
                'model' => $_GET['model'] ?? '',
                'engine' => $_GET['engine'] ?? '',
                'fuel_type' => $_GET['fuel_type'] ?? '',
                'min_power' => $_GET['min_power'] ?? '',
                'max_power' => $_GET['max_power'] ?? '',
                'year_range' => $_GET['year_range'] ?? '',
                'search_text' => $_GET['q'] ?? '',
                'limit' => $_GET['limit'] ?? 50
            ];
            
            $response['data'] = $tuning->searchDetailed($filters);
            $response['success'] = true;
            $response['message'] = count($response['data']) . ' sonuç bulundu';
            break;

        case 'popular':
            $limit = $_GET['limit'] ?? 10;
            $response['data'] = $tuning->getPopularEngines($limit);
            $response['success'] = true;
            $response['message'] = 'Popüler motorlar getirildi';
            break;

        case 'highest_gains':
            $limit = $_GET['limit'] ?? 10;
            $response['data'] = $tuning->getHighestPowerGains($limit);
            $response['success'] = true;
            $response['message'] = 'En yüksek güç artışları getirildi';
            break;

        case 'fuel_stats':
            $response['data'] = $tuning->getFuelTypeStats();
            $response['success'] = true;
            $response['message'] = 'Yakıt tipi istatistikleri getirildi';
            break;

        case 'brand_stats':
            $response['data'] = $tuning->getBrandStats();
            $response['success'] = true;
            $response['message'] = 'Marka istatistikleri getirildi';
            break;

        case 'stage_detail':
            $stageId = $_GET['stage_id'] ?? '';
            if (empty($stageId)) {
                $response['message'] = 'Stage ID gerekli';
            } elseif (!isValidUUID($stageId)) {
                $response['message'] = 'Geçersiz GUID formatı';
            } else {
                $stage = $tuning->getStageById($stageId);
                if ($stage) {
                    $stage['similar'] = $tuning->getSimilarEngines($stageId);
                    $response['data'] = $stage;
                    $response['success'] = true;
                    $response['message'] = 'Stage detayları getirildi';
                } else {
                    $response['message'] = 'Stage bulunamadı';
                }
            }
            break;

        case 'latest':
            $limit = $_GET['limit'] ?? 10;
            $response['data'] = $tuning->getLatestStages($limit);
            $response['success'] = true;
            $response['message'] = 'En son eklenen stage\'ler getirildi';
            break;

        case 'export':
            $response['data'] = $tuning->exportToJson();
            $response['success'] = true;
            $response['message'] = 'Tüm veriler JSON formatında export edildi';
            break;

        default:
            $response['message'] = 'Geçersiz action parametresi';
            $response['available_actions'] = [
                'brands' => 'Tüm markaları getir',
                'models' => 'Markaya göre modelleri getir (brand_id gerekli)',
                'series' => 'Modele göre serileri getir (model_id gerekli)',
                'engines' => 'Seriye göre motorları getir (series_id gerekli)',
                'stages' => 'Motora göre stage\'leri getir (engine_id gerekli)',
                'search' => 'Detaylı arama (brand, model, engine, fuel_type, min_power, max_power, year_range, q parametreleri)',
                'popular' => 'Popüler motorları getir (limit parametresi)',
                'highest_gains' => 'En yüksek güç artışlarını getir (limit parametresi)',
                'fuel_stats' => 'Yakıt tipi istatistikleri',
                'brand_stats' => 'Marka istatistikleri',
                'stage_detail' => 'Stage detayları (stage_id gerekli)',
                'latest' => 'En son eklenen stage\'ler (limit parametresi)',
                'export' => 'Tüm veriyi JSON formatında export et'
            ];
            break;
    }

} catch (Exception $e) {
    $response['message'] = 'Sistem hatası: ' . $e->getMessage();
    if (DEBUG) {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>